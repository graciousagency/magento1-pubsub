<?php

use Google\Cloud\PubSub\Topic;
use Google\Cloud\PubSub\PubSubClient;

class Gracious_Pubsub_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Check is module exists and enabled in global config.
     *
     * @param string $moduleName the full module name, example Mage_Core
     * @return boolean
     */
    public function isModuleEnabled($moduleName = null)
    {
        return (bool)Mage::getStoreConfig('pubsub/pubsub_default/is_enabled');
    }

    /**
     * @return array
     */
    public function getOrderStatusesToExport(): array
    {
        $config = Mage::getStoreConfig('pubsub/pubsub_default/order_statuses');
        return strpos($config, ',') !== false ? explode(',', $config) : [$config];
    }

    /**
     * @param int $days
     * @return DateTime
     * @throws Exception
     */
    public function convertSubDaysToDate(int $days): \DateTime
    {
        $datetime = new \DateTime('now', $this->getTimezone());
        return $datetime->sub($this->getInterval($days));
    }

    /**
     * @param int $days
     *
     * @return DateInterval
     * @throws Exception
     */
    public function getInterval(int $days): DateInterval
    {
        return new \DateInterval('P' . $days . 'D');
    }

    /**
     * @return DateTimeZone
     */
    public function getTimezone(): \DateTimeZone
    {
        return new \DateTimeZone(Mage::getStoreConfig('general/locale/timezone'));
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    public function getStartFromDate(): \DateTime
    {
        return $this->convertSubDaysToDate($this->getStartFromDays());
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    public function getEndFromDate(): \DateTime
    {
        return $this->convertSubDaysToDate($this->getEndFromDays());
    }


    /**
     * @return int
     */
    public function getStartFromDays(): int
    {
        $days = Mage::getStoreConfig('pubsub/pubsub_default/start_from_days');
        if (empty($days)) {
            $days = 0;
        }

        return (int)$days;
    }

    /**
     * @return int
     */
    public function getEndFromDays(): int
    {
        $days = Mage::getStoreConfig('pubsub/pubsub_default/end_from_days');
        if (empty($days)) {
            $days = 0;
        }

        return (int)$days;
    }

    /**
     * @return Topic
     */
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
     * @return bool
     */
    public function publishOrder(Mage_Sales_Model_Order $order, Topic $topic, bool $dump = false): bool
    {
        Mage::log('Publishing order ' . $order->getId(), null, 'pubsub.log');
        $items = [];
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getItemsCollection() as $item) {

            if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
            }

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
        $orderData = $order->getData();
        $items = $this->getOrderItemData($order);
        if (!empty($items)) {
            $orderData['order_items'] = $items;
        }
        $addressData = $this->processOrderAddresses($order, $orderData);
        foreach ($addressData as $key => $value) {
            $orderData[$key] = $value;
        }
        $orderData = \json_encode($orderData);
        if ($dump) {
            Mage::log('Dumping order ' . $order->getId(), null, 'pubsub.log');
            Mage::log(print_r($orderData, true), null, 'pubsub.log');
        }
        if (!$dump) {
            $message = ['data' => $orderData, 'attributes' => ['type' => 'order']];
            $response = $topic->publish($message);
            if (isset($response['messageIds'][0])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool
     * @throws Exception
     */
    public function setOrderPublished(Mage_Sales_Model_Order $order)
    {
        try {
            $order->setPubsubExported(true);
            $order->addStatusHistoryComment('Order has been sent to PubSub.');
            $order->save();
        } catch (Exception $e) {
            return false;
        }

        return true;
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

    /**
     * @param $product
     *
     * @return array
     */
    private function getProductAttributes($product): array
    {
        $productData = [];
        if ($product->getId()) {
            $attributes = $product->getAttributes();
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            foreach ($attributes as $attribute) {
                $productData[$attribute->getAttributeCode()] = $this->convertToBoolean($attribute->getFrontend()->getValue($product));
            }
        }

        return $productData;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    private function getOrderItemData(Mage_Sales_Model_Order $order): array
    {
        $items = [];
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getItemsCollection() as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                $itemData = $item->getData();
                /** @var Mage_Catalog_Model_Product $product */
                $product = $item->getProduct();
                $productData = $this->getProductAttributes($product);
                $itemData['product'] = $productData;
                $items[] = $itemData;
            }
        }

        return $items;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return mixed
     */
    private function processOrderAddresses(Mage_Sales_Model_Order $order)
    {
        $addressData = [];
        $orderShippingAddress = $order->getShippingAddress();
        $orderBillingAddress = $order->getBillingAddress();
        $address = $orderShippingAddress->getData();
        if (!isset($address['street']) || (isset($address['street']) && empty($address['street']))) {
            $address = $orderBillingAddress->getData();
        }
        foreach ($address as $key => $value) {
            $i = 1;
            if ($key == 'street') {
                $street = explode("\n", $value);
                foreach ($street as $streetKey => $streetValue) {
                    $addressData['shipping_street_' . $i] = trim($streetValue);
                    $i++;
                }
            }
            if ($key !== 'street') {
                $shippingKey = 'shipping_' . $key;
                if ($shippingKey == 'shipping_email' && empty($value)) {
                    $value = $order->getCustomerEmail();
                }
                $addressData[$shippingKey] = $value;
            }

        }
        return $addressData;
    }


}
	 