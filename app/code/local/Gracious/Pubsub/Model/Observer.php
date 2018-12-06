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
    /** @var Gracious_Pubsub_Helper_Data  */
    private $helper;

    public function __construct()
    {
        $this->helper = Mage::helper('pubsub');
        $this->topic = $this->helper->getTopic();
    }

    public function salesOrderPlaceAfter($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $result = $this->helper->publishOrder($order, $this->topic);
    }



}
