<?php

use Google\Cloud\PubSub\Topic;
use Google\Cloud\PubSub\PubSubClient;

class Gracious_Pubsub_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getTopic()
    {
        $ordersTopic = Mage::getStoreConfig('pubsub/pubsub_default/orders_topic');
        $serviceAccount = Mage::getStoreConfig('pubsub/pubsub_default/service_account');
        $this->pubSub = new PubSubClient(
            ['keyFile' => json_decode($serviceAccount, true)]
        );

        return $this->pubSub->topic($ordersTopic);
    }


    /**
     * @param Mage_Sales_Model_Order $order
     * @param Topic $topic
     *
     * @return array
     */
    public function publishOrder(Mage_Sales_Model_Order $order, Topic $topic): array
    {
        Mage::log('Publishing order ' . $order->getId(), null, 'pubsub.log');
        $items = [];
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getItemsCollection() as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                $itemData = $item->getData();
                /** @var Mage_Catalog_Model_Product $product */
                $product = $item->getProduct();
                $itemData['product'] = [];
                if ($product->getId()) {
                    $productData = [];
                    $attributes = $product->getAttributes();
                    /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
                    foreach ($attributes as $attribute) {
                        $productData[$attribute->getAttributeCode()] = $this->convertToBoolean($attribute->getFrontend()->getValue($product));
                    }
                    $itemData['product'] = $productData;
                }
                $items[] = $itemData;
            }
        }
        $orderData = $order->getData();
        if (!empty($items)) {
            $orderData['order_items'] = $items;
        }
        $message = ['data' => json_encode($orderData), 'attributes' => ['type' => 'order']];
        $response = $topic->publish($message);
        if (isset($response['messageIds'][0])) {
            return true;
        }

        return false;
    }

    public function setOrderPublished(Mage_Sales_Model_Order $order)
    {
        $order->setPubsubExported(true);
        $order->save();
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function convertToBoolean($value)
    {
        switch ($value) {
            case 'No':
            case 'Nee':
            case '':
                return false;
            case 'Yes':
            case 'Ja':
                return true;
            default:
                return $value;
        }
    }


}
	 