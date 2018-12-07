<?php

class Gracious_Pubsub_Model_Cron
{

    /** @var Gracious_Pubsub_Helper_Data */
    private $helper;
    /** @var \Google\Cloud\PubSub\Topic */
    private $topic;

    public function __construct()
    {
        Mage::log('Starting pubsub cron', null, 'pubsub.log');
        $this->helper = Mage::helper('pubsub');
        $this->topic = $this->helper->getTopic();
    }

    public function processOrders()
    {
        $collection = $this->getCollection();
        Mage::log('Publishing ' . $collection->getSize() . ' orders to pubsub', null, 'pubsub.log');
        foreach ($collection as $order) {
            Mage::log('Publishing order ' . $order->getId() . ' to pubsub', null, 'pubsub.log');
            $published = $this->helper->publishOrder($order, $this->topic);
            if ($published) {
                Mage::log('Published order ' . $order->getId() . ' to pubsub', null, 'pubsub.log');
            }
            if (!$published) {
                Mage::log('Failed to publish order ' . $order->getId() . ' to pubsub', null, 'pubsub.log');
            }
        }
    }

    private function getCollection()
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('pubsub_exported', 0)
            ->addFieldToFilter('state', [
                'in' => [
                    Mage_Sales_Model_Order::STATE_COMPLETE,
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                ],
            ]);

        return $collection;
    }


}