<?php
/**
 * Shopgate GmbH
 *
 * URHEBERRECHTSHINWEIS
 *
 * Dieses Plugin ist urheberrechtlich geschützt. Es darf ausschließlich von Kunden der Shopgate GmbH
 * zum Zwecke der eigenen Kommunikation zwischen dem IT-System des Kunden mit dem IT-System der
 * Shopgate GmbH über www.shopgate.com verwendet werden. Eine darüber hinausgehende Vervielfältigung, Verbreitung,
 * öffentliche Zugänglichmachung, Bearbeitung oder Weitergabe an Dritte ist nur mit unserer vorherigen
 * schriftlichen Zustimmung zulässig. Die Regelungen der §§ 69 d Abs. 2, 3 und 69 e UrhG bleiben hiervon unberührt.
 *
 * COPYRIGHT NOTICE
 *
 * This plugin is the subject of copyright protection. It is only for the use of Shopgate GmbH customers,
 * for the purpose of facilitating communication between the IT system of the customer and the IT system
 * of Shopgate GmbH via www.shopgate.com. Any reproduction, dissemination, public propagation, processing or
 * transfer to third parties is only permitted where we previously consented thereto in writing. The provisions
 * of paragraph 69 d, sub-paragraphs 2, 3 and paragraph 69, sub-paragraph e of the German Copyright Act shall remain unaffected.
 *
 * @author Shopgate GmbH <interfaces@shopgate.com>
 */

/**
 * User: pliebig
 * Date: 20.03.14
 * Time: 11:31
 * E-Mail: p.liebig@me.com
 */

/**
 * settings export
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Export_Settings extends Shopgate_Framework_Model_Export_Abstract
{
    /**
     * @var null
     */
    protected $_defaultSettings = null;

    /**
     * @var null
     */
    protected $_actionCache = null;

    /**
     * @return array
     */
    public function generateData()
    {
        foreach (array_keys($this->_defaultSettings) as $key) {
            if (!count(array_keys($this->_defaultSettings[$key]))) {
                $action = "_set" . uc_words($key, '', '_');
                if (empty($this->_actionCache[$action])) {
                    $this->_actionCache[] = $action;
                }

                continue;
            }

            foreach (array_keys($this->_defaultSettings[$key]) as $subkey) {
                $action = "_set" . uc_words($subkey, '', '_');
                if (empty($this->_actionCache[$action])) {
                    $this->_actionCache[] = $action;
                }
            }
        }

        foreach ($this->_actionCache as $_action) {
            if (method_exists($this, $_action)) {
                $this->{$_action}();
            }
        }

        return $this->_defaultSettings;
    }

    /**
     * @param $defaultRow
     * @return Shopgate_Framework_Model_Export_Settings
     */
    public function setDefaultRow($defaultRow)
    {
        $this->_defaultSettings = $defaultRow;
        return $this;
    }

    /**
     * set product tax classes
     */
    protected function _setProductTaxClasses()
    {
        $classes = array();

        $taxCollection = Mage::getModel("tax/class")
                             ->getCollection()
                             ->setClassTypeFilter(Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT);

        foreach ($taxCollection as $tax) {
            /* @var $tax  Mage_Tax_Model_Class */

            $classes[] = array(
                "id"  => $tax->getId(),
                "key" => $tax->getClassName()
            );
        }

        $this->_defaultSettings["tax"]["product_tax_classes"] = $classes;
    }

    /**
     * export customer tax classes
     */
    protected function _setCustomerTaxClasses()
    {
        $classes      = array();
        $defaultTaxId = Mage::getModel('customer/group')
                            ->load(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID)
                            ->getData('tax_class_id');

        $taxCollection = Mage::getModel("tax/class")
                             ->getCollection()
                             ->setClassTypeFilter(Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER);

        foreach ($taxCollection as $tax) {
            /* @var $tax  Mage_Tax_Model_Class */
            $taxId = $tax->getId();

            $classes[] = array(
                "id"         => $taxId,
                "key"        => $tax->getClassName(),
                "is_default" => $defaultTaxId == $taxId ? '1' : '0',
            );
        }

        $this->_defaultSettings["tax"]["customer_tax_classes"] = $classes;
    }

    /**
     * export every payment method id
     */
    protected function _setPaymentMethods()
    {
        /** @var Mage_Sales_Model_quote $quote */
        $store                  = Mage::app()->getStore()->getId();
        $shopgatePaymentMethods = array();
        $paymentMethods         = Mage::getStoreConfig(
                                      Mage_Payment_Helper_Data::XML_PATH_PAYMENT_METHODS,
                                      $store
        );
		$emptyQuote = Mage::getModel('sales/quote');
        foreach ( $paymentMethods as $code => $methodConfig) {
            $prefix = 'payment/' . $code . '/';
            $model = Mage::getStoreConfig($prefix . 'model', $store);
            if (!$model) {
                continue;
            }
            $methodInstance = Mage::getModel($model);
            if (!$methodInstance) {
                continue;
            }
            $methodInstance->setStore($store);
            $shopgatePaymentMethod              = array();
			$shopgatePaymentMethod['id']        = $code;
            $shopgatePaymentMethod['title']     = $methodConfig['title'];
			$shopgatePaymentMethod['is_active'] = (int)$methodInstance->isAvailable($emptyQuote);
            $shopgatePaymentMethods[]           = $shopgatePaymentMethod;
        }
        $this->_defaultSettings["payment_methods"] = $shopgatePaymentMethods;
    }


    /**
     * set tax rates
     */
    protected function _setTaxRates()
    {
        $rates          = array();
        $rateCollection = Mage::getModel("tax/calculation_rate")
                              ->getCollection();

        foreach ($rateCollection as $rate) {
            /* @var $rate Mage_Tax_Model_Calculation_Rate */

            $zipCodeType = "all";
            if ($rate->getZipIsRange()) {
                $zipCodeType = "range";
            } else {
                if ($rate->getTaxPostcode() && $rate->getTaxPostcode() != "*") {
                    $zipCodeType = "pattern";
                }
            }

            $state = "";
            if ($regionId = $rate->getTaxRegionId()) {
                /* @var $region Mage_Directory_Model_Region */
                $region = Mage::getModel("directory/region")->load($regionId);

                $a     = new Varien_Object(
                    array(
                        'region_code' => $region->getCode(),
                        'country_id'  => $rate->getTaxCountryId()
                    )
                );
                $state = $this->_getHelper()->getIsoStateByMagentoRegion($a);
            }

            $_rates = array(
                "id"                 => $rate->getId(),
                "key"                => $rate->getId(),
                "display_name"       => $rate->getCode(),
                "tax_percent"        => round($rate->getRate(), 4),
                "country"            => $rate->getTaxCountryId(),
                "state"              => $state,
                "zipcode_type"       => $zipCodeType,
                "zipcode_pattern"    => $zipCodeType == "pattern" ? $rate->getTaxPostcode() : "",
                "zipcode_range_from" => $zipCodeType == "range" ? $rate->getZipFrom() : "",
                "zipcode_range_to"   => $zipCodeType == "range" ? $rate->getZipTo() : "",
            );

            $rates[] = $_rates;
        }

        $this->_defaultSettings["tax"]["tax_rates"] = $rates;
    }

    /**
     * set tax rules
     */
    protected function _setTaxRules()
    {
        $rules          = array();
        $ruleCollection = Mage::getModel("tax/calculation_rule")->getCollection();

        foreach ($ruleCollection as $rule) {
            /* @var $rule Mage_Tax_Model_Calculation_Rule */
            $_rule = array(
                "id"                   => $rule->getId(),
                "name"                 => $rule->getCode(),
                "priority"             => $rule->getPriority(),
                "product_tax_classes"  => array(),
                "customer_tax_classes" => array(),
                "tax_rates"            => array(),
            );

            foreach (array_unique($rule->getProductTaxClasses()) as $taxClass) {
                $_rule["product_tax_classes"][] = array(
                    "id"  => $taxClass,
                    "key" => $taxClass
                );
            }

            foreach (array_unique($rule->getCustomerTaxClasses()) as $taxClass) {
                $_rule["customer_tax_classes"][] = array(
                    "id"  => $taxClass,
                    "key" => $taxClass
                );
            }

            foreach (array_unique($rule->getRates()) as $taxRates) {
                $_rule["tax_rates"][] = array(
                    "id"  => $taxRates,
                    "key" => $taxRates
                );
            }

            $rules[] = $_rule;
        }
        $this->_defaultSettings["tax"]["tax_rules"] = $rules;
    }

    /**
     * export customer tax classes
     */
    protected function _setCustomerGroups()
    {
        $groups = array();

        $customerGroupCollection = Mage::getModel('customer/group')->getCollection();
        $taxClassCollection      = Mage::getModel('tax/class')->getCollection();

        $defaultGroupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
        foreach ($customerGroupCollection->getItems() as $customerGroup) {
            $group = array();

            $group["id"]         = $customerGroup->getId();
            $group["name"]       = $customerGroup->getCustomerGroupCode();
            $group["is_default"] = $customerGroup->getId() == $defaultGroupId ? 1 : 0;

            $matchingTaxClasses = $taxClassCollection->getItemsByColumnValue('class_id', $customerGroup->getTaxClassId());

            if (count($matchingTaxClasses)) {
                $group["customer_tax_class_key"] = $matchingTaxClasses[0]->getClassName();
            }

            $groups[] = $group;
        }

        $this->_defaultSettings["customer_groups"] = $groups;
    }

    /**
     * set allowedAddressCountries
     */
    protected function _setAllowedAddressCountries()
    {
        $allowedAddressCountriesRaw = explode(",", Mage::getStoreConfig('general/country/allow', $this->_getConfig()
                                                                                                      ->getStoreViewId()));
        $allowedShippingCountries   = $this->_defaultSettings['allowed_shipping_countries'];

        $allowedShippingCountriesMap = array_map(
            create_function('$country', 'return $country["country"];'),
            $allowedShippingCountries
        );

        $allowedAddressCountries = array();
        foreach ($allowedAddressCountriesRaw as $addressCountry) {
            $state  = array_search($addressCountry, $allowedShippingCountriesMap);
            $states = $state !== false ? $allowedShippingCountries[$state]['state'] : array('All');

            $entry = array(
                'country' => $addressCountry,
                'state'   => $states,
            );

            $allowedAddressCountries[] = $entry;
        }

        $this->_defaultSettings["allowed_address_countries"] = $allowedAddressCountries;
    }

    /**
     * get allowed shipping countries in raw
     */
    protected function _getAllowedShippingCountriesRaw()
    {
        $allowedCountries = array_fill_keys(explode(",", Mage::getStoreConfig('general/country/allow', $this->_getConfig()
                                                                                                            ->getStoreViewId())), array());

        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();

        $specificCountryCollection = array();
        foreach ($methods as $code => $method) {
            /* skip shopgate cause its a container carrier */
            if ($code == 'shopgate') {
                continue;
            }
            /* if any carrier is using the allowed_countries collection, merge this into the result */
            if (Mage::getStoreConfig('carriers/' . $code . '/sallowspecific', $this->_getConfig()
                                                                                   ->getStoreViewId()) === "0"
            ) {
                $specificCountryCollection = array_merge_recursive($specificCountryCollection, $allowedCountries);
                continue;
            }
            /* fetching active shipping targets from rates direct from the database */
            if ($code == "tablerate") {
                $website    = Mage::app()->getStore($this->_getConfig()->getStoreViewId())->getWebsite()->getId();
                $collection = Mage::getResourceModel('shipping/carrier_tablerate_collection')
                                  ->setWebsiteFilter($website);

                $specificCountries = array();
                foreach ($collection as $rate) {
                    $specificCountries[$rate->getDestCountryId()][$rate->getDestRegion() ? $rate->getDestRegion() : 'All'] = true;
                }
                $specificCountryCollection = array_merge_recursive($specificCountries, $specificCountryCollection);
                continue;
            }

            $specificCountries = Mage::getStoreConfig('carriers/' . $code . '/specificcountry', $this->_getConfig()
                                                                                                     ->getStoreViewId());
            if ($specificCountries != "") {
                $specificCountryCollection = array_merge_recursive($specificCountryCollection, array_fill_keys(explode(",", $specificCountries), array()));
            }
        }

        foreach ($specificCountryCollection as $countryCode => $item) {
            if (!isset($allowedCountries[$countryCode])) {
                unset($specificCountryCollection[$countryCode]);
            }
        }

        return $specificCountryCollection;
    }

    /**
     * set allowed shipping countries
     */
    protected function _setAllowedShippingCountries()
    {
        $allowedShippingCountriesRaw = $this->_getAllowedShippingCountriesRaw();

        $allowedShippingCountries = array();
        foreach ($allowedShippingCountriesRaw as $countryCode => $states) {
            $states = count($states) < 1 ? array('All' => true) : $states;
            $states = array_filter(array_keys($states),
                                   create_function('$st', 'return is_string($st) ? $st : "All";')
            );

            $states = in_array('All', $states) ? array('All') : $states;

            $result = array_walk($states,
                                 create_function('&$state, $key, $country', '$state = $state == "All" ? $state : $country . "-" . $state;'),
                                 $countryCode
            );

            $entry                      = array(
                'country' => $countryCode,
                'state'   => $states,
            );
            $allowedShippingCountries[] = $entry;
        }

        $this->_defaultSettings["allowed_shipping_countries"] = $allowedShippingCountries;
    }
}
