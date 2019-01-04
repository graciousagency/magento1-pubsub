<?php

class Gracious_Pubsub_Model_System_Config_Source_Statuses
{

    /**
     * @return array
     * @throws Mage_Core_Exception
     */
    public function toOptionArray(): array
    {

        return Mage::getModel('sales/order_status')->getCollection()->toOptionArray();

    }
}