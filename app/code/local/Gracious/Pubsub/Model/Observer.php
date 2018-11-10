<?php

/**
 * Created by PhpStorm.
 * User: pepijnblom
 * Date: 10/11/2018
 * Time: 17:24
 */
class Gracious_Pubsub_Model_Observer
{

    public function salesOrderPlaceAfter($observer)
    {
        Mage::log(__METHOD__ . ' @ ' . __LINE__ . ' -- $observer = ' . get_class($observer), null, 'gracious.log');
        $order = $observer->getEvent()->getOrder();
        Zend_Debug::dump($order);
        die;
    }

}
