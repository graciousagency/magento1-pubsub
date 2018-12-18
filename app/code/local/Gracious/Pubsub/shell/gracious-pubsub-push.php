<?php
/**
 * Magento
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 * @category    Mage
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once 'abstract.php';

/**
 * Magento Compiler Shell Script
 * @category    Mage
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Gracious_Pubsub_Shell_Test extends Mage_Shell_Abstract
{
    /** @var Gracious_Pubsub_Helper_Data */
    private $helper;

    /**
     * Run script
     */
    public function run()
    {
        $this->helper = Mage::helper('pubsub');
        /** @var \Google\Cloud\PubSub\Topic $topic */
        $topic = $this->helper->getTopic();
        $orderIds = $this->getArg('order_ids');
        $from = (string)$this->getArg('from');
        $to = (string)$this->getArg('to');
        if ($this->getArg('help') || (!$orderIds && !$from)) {
            echo $this->usageHelp();

            return;
        }
        $orders = null;
        if ($orderIds) {
            $orders = $this->getOrdersByOrderIds($orderIds);
        }
        if ($from) {
            $orders = $this->getOrdersByFromTo($from, $to);
        }
        if (null === $orders) {
            echo $this->usageHelp();
            return;
        }
        /** @var Mage_Sales_Model_Order $order */
        foreach ($orders as $order) {
            echo 'Publishing order: ' . $order->getId() . PHP_EOL;
            if ($this->helper->publishOrder($order, $topic)) {
                $this->helper->setOrderPublished($order);
            }
        }
    }

    /**
     * Retrieve Usage Help Message
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php gracious-pubsub-push.php [options]

  --order_ids <profiles> Order entity ids separated by comma or single id 
  --from YYYY-mm-dd
  --to YYYY-mm-dd 
  
Using order_ids in combination with from/to is not supported!

USAGE;
    }

    /**
     * @param string $orderIds
     *
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    private function getOrdersByOrderIds(string $orderIds): Mage_Sales_Model_Resource_Order_Collection
    {
        $orderIds = strpos($orderIds, ',') !== false ? explode(',', $orderIds) : [$orderIds];

        return Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('state', ['in' => $this->helper->getOrderStatesToExport()])
            ->addFieldToFilter('entity_id', [
                'in' => $orderIds,
            ]);
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    private function getOrdersByFromTo(string $from, string $to): Mage_Sales_Model_Resource_Order_Collection
    {

        if (empty($to)) {
            $to = date('Y-m-d');
        }

        return Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('state', ['in' => $this->helper->getOrderStatesToExport()])
            ->addFieldToFilter('created_at', [
                'from' => $from,
                'to' => $to,
            ]);
    }

}

$shell = new Gracious_Pubsub_Shell_Test();
$shell->run();
