<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    ALTER TABLE {$installer->getTable('sales/order')} ADD COLUMN pubsub_exported BOOL DEFAULT 0 NOT NULL;
    ALTER TABLE {$installer->getTable('sales/order')} ADD INDEX `gs_pubsub_exported_idx` (`pubsub_exported`);
");
$installer->endSetup();