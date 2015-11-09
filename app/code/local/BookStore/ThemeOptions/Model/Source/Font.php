<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class BookStore_ThemeOptions_Model_Source_Font {

    public function toOptionArray() {
        return array(
            array('value' => 'serif', 'label' =>
                Mage::helper('themeoptions')->__('Georgia,Times New Roman, Times, serif')),
            array('value' => 'sansserif', 'label' =>
                Mage::helper('themeoptions')->__('Arial, Helvetica,sans-serif')),
            array('value' => 'monospace', 'label' =>
                Mage::helper('themeoptions')->__('"Courier New",Courier, monospace'))
        );
    }

}
