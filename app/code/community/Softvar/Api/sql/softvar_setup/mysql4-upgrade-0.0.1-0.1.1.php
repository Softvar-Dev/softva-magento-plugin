<?php
$installer = $this;

$installer->startSetup();
$column 	= 'softvar_imported';
$hasColumn 	= $this->_conn->fetchAll("SHOW COLUMNS FROM sales_flat_order WHERE field = '".$column."';");

if (count($hasColumn) == 0) { // Se não existir, adiciona a coluna.
	$installer->run("ALTER TABLE  `sales_flat_order` ADD  `".$column."` CHAR(1) NULL");
}

$installer->endSetup();	 