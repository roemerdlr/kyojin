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
 *  @author Shopgate GmbH <interfaces@shopgate.com>
 */

class Shopgate_Framework_Model_Sales_Quote_Address_Total_ShopgatePaymentFee extends Mage_Sales_Model_Quote_Address_Total_Abstract
{

    protected $_code = 'shopgate_payment_fee';

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return Mage::helper('shopgate')->__('Payment Fee');
    }


    /**
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return $this|Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        if (Mage::helper("shopgate/config")->getIsMagentoVersionLower1410()) {
            $items         = $address->getAllItems();
        } else {
            $items         = $this->_getAddressItems($address);
        }
        $shopgateOrder = Mage::getSingleton("core/session")->getData("shopgate_order");
        if (!count($items)
            || is_null($shopgateOrder)
        ) {
            return $this; //this makes only address type shipping to come through
        }

        $quote             = $address->getQuote();
        $amountShopPayment = $shopgateOrder->getAmountShopPayment();

        if ($amountShopPayment >= 0) {
            return $this;
        }

        $address->setShopgatePaymentFee($amountShopPayment);
        $address->setBaseShopgatePaymentFee($amountShopPayment);

        $quote->setShopgatePaymentFee($amountShopPayment);
        $quote->setBaseShopgatePaymentFee($amountShopPayment);

        $address->setGrandTotal($address->getGrandTotal() + $address->getShopgatePaymentFee());
        $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBaseShopgatePaymentFee());

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return $this
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amt = $address->getShopgatePaymentFee();
        if ($amt != 0) {
            $address->addTotal(
                array('code' => $this->getCode(), 'title' => $this->getLabel(), 'value' => $amt)
            );
        }

        return $this;
    }
} 