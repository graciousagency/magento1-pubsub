<?php

use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\Storage\StorageClient;

/**
 * Created by PhpStorm.
 * User: pepijnblom
 * Date: 10/11/2018
 * Time: 17:24
 */
class Gracious_Pubsub_Model_Observer
{

    private $pubSub;
    private $topic;

    public function __construct()
    {

        Mage::log(__METHOD__ . ' @ ' . __LINE__, null, 'gracious.log');
        $ordersTopic = Mage::getStoreConfig('pubsub/pubsub_default/orders_topic');
        $serviceAccount = Mage::getStoreConfig('pubsub/pubsub_default/service_account');
        Mage::log(__METHOD__ . ' @ ' . __LINE__ . ' -- $ordersTopic = ' . $ordersTopic, null, 'gracious.log');
        Mage::log(__METHOD__ . ' @ ' . __LINE__ . ' -- $serviceAccount = ' . $serviceAccount, null, 'gracious.log');
        $this->pubSub = new PubSubClient(
            ['keyFile' => json_decode($serviceAccount, true)]
        );
        $this->topic = $this->pubSub->topic($ordersTopic);

    }

    public function salesOrderPlaceAfter($observer)
    {

        Mage::log(__METHOD__ . ' @ ' . __LINE__ . ' -- $observer = ' . get_class($observer), null, 'gracious.log');
        $order = $observer->getEvent()->getOrder();
        $message = ['data' => json_encode($order->getData()), 'attributes' => ['type' => 'order']];
        $result = $this->topic->publish($message);
        Mage::log(__METHOD__ . ' @ ' . __LINE__ . ' -- $result = ' . print_r($result,true), null, 'gracious.log');
    }

}
