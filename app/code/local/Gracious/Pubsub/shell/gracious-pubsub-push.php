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

    /**
     * Run script
     */
    public function run()
    {

        $orderId = $this->getArg('order_id');

        if($this->getArg('help'))  {
            echo $this->usageHelp();
            return;
        }

        if (!$orderId) {
            echo $this->usageHelp();
            return;
        }


    }

    /**
     * Retrieve Usage Help Message
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f generate_feeds.php -- [options]

  --profiles <profiles> Profiles ids or names separated by comma
  help          This help
  no argument   Generate all profiles

USAGE;
    }
}

$shell = new Mage_Shell_Generate_Profiles();
$shell->run();
