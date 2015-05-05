<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Packt_Helloworld_Model_Observer {
    /* public function registerVisit(Varien_Event_Observer
      $observer) {
      Mage::log('Registered');
      } */

    public function registerVisit($observer) {
        $product = $observer->getProduct();
        $category = $observer->getCategory();
        Mage::log($product->debug());
        Mage::log($category->debug());
    }

}
