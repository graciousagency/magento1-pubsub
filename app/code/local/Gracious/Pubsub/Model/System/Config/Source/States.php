<?php

class Gracious_Pubsub_Model_System_Config_Source_States
{

    /**
     * @return array
     * @throws Mage_Core_Exception
     */
    public function toOptionArray(): array
    {

        $states = [
            Mage_Sales_Model_Order::STATE_CANCELED,
            Mage_Sales_Model_Order::STATE_CLOSED,
            Mage_Sales_Model_Order::STATE_COMPLETE,
            Mage_Sales_Model_Order::STATE_HOLDED,
            Mage_Sales_Model_Order::STATE_NEW,
            Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
            Mage_Sales_Model_Order::STATE_PROCESSING,
        ];
        $options = [];
        foreach ($states as $state) {
            $options[] = [
                'label' => $state,
                'value' => $state
            ];
        }
        return $options;

    }
}