<?php
class Packt_Helloworld_Model_Mysql4_Helloworld extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {   
        $this->_init('helloworld/helloworld', 'helloworld_id');
    }
}