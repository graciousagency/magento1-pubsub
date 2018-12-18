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
        if(!$this->helper->isModuleEnabled())   {
            Mage::log('Not publishing order to pubsub, module disabled!', null, 'pubsub.log');
        }
        $collection = $this->getCollection();
        foreach ($collection as $order) {
            $published = $this->helper->publishOrder($order, $this->topic);
            if ($published) {
                Mage::log('Published order ' . $order->getId() . ' to pubsub', null, 'pubsub.log');
                $this->helper->setOrderPublished($order);
            }
            if (!$published) {
                Mage::log('FAILED to publish order ' . $order->getId() . ' to pubsub', null, 'pubsub.log');
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