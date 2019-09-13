<?php
$installer = $this;

$installer->startSetup();
$column 	= 'softvar_url';
$hasColumn 	= $this->_conn->fetchAll("SHOW COLUMNS FROM sales_flat_shipment_track WHERE field = '".$column."';");

if (count($hasColumn) == 0) { // Se não existir, adiciona a coluna.
	$installer->run("ALTER TABLE `sales_flat_shipment_track` ADD `".$column."` TEXT NULL");
}

$installer->endSetup();	 