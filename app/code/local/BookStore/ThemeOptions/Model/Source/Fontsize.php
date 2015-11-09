<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class BookStore_ThemeOptions_Model_Source_Fontsize {

    public function toOptionArray() {
        return array(
            array('value' => '12px', 'label' =>
                Mage::helper('themeoptions')->__('12px')),
            array('value' => '13px', 'label' =>
                Mage::helper('themeoptions')->__('13px')),
            array('value' => '14px', 'label' =>
                Mage::helper('themeoptions')->__('14px')),
            array('value' => '15px', 'label' =>
                Mage::helper('themeoptions')->__('15px')),
            array('value' => '16px', 'label' =>
                Mage::helper('themeoptions')->__('16px'))
        );
    }

}
