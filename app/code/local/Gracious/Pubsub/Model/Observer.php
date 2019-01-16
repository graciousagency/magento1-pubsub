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

    /**
     * @param $observer
     * @throws Exception
     */
    public function salesOrderInvoicePay($observer)
    {
        if (!$this->helper->isModuleEnabled()) {
            Mage::log('Not publishing order to pubsub, module disabled!', null, 'pubsub.log');
            return;
        }

        $this->publish($observer->getEvent()->getInvoice()->getOrder());
    }

    /**
     * @param $observer
     * @throws Exception
     */
    public function orderCancelAfter($observer)
    {
        if (!$this->helper->isModuleEnabled()) {
            Mage::log('Not publishing order to pubsub, module disabled!', null, 'pubsub.log');
            return;
        }

        $this->publish($observer->getEvent()->getOrder());
    }


    /**
     * @param $observer
     * @throws Exception
     */
    public function salesOrderCreditmemoRefund($observer)
    {
        if (!$this->helper->isModuleEnabled()) {
            Mage::log('Not publishing order to pubsub, module disabled!', null, 'pubsub.log');
            return;
        }

        // Cloning the order, don't want to mutate the system's Order object.
        $order = clone $observer->getEvent()->getCreditmemo()->getOrder();
        $data = $order->getData();
        $data['state'] = 'closed';
        $data['status'] = 'closed';
        $order->setData($data);
        $this->publish($order);
    }

    /**
     * @param $order
     * @throws Exception
     */
    private function publish($order)
    {
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
