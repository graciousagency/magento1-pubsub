<?php

/**
 * Created by PhpStorm.
 * User: pepijnblom
 * Date: 10/11/2018
 * Time: 17:24
 */
class Gracious_Pubsub_Model_Observer
{
    /** @var PubSubClient */
    private $pubSub;
    /** @var Topic */
    private $topic;
    /** @var Gracious_Pubsub_Helper_Data */
    private $helper;

    public function __construct()
    {
        $this->helper = Mage::helper('pubsub');
        $this->topic = $this->helper->getTopic();
    }

    public function salesOrderPlaceAfter($observer)
    {
        if (!$this->helper->isModuleEnabled()) {
            Mage::log('Not publishing order to pubsub, module disabled!', null, 'pubsub.log');
            return;
        }
        $order = $observer->getEvent()->getOrder();
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
