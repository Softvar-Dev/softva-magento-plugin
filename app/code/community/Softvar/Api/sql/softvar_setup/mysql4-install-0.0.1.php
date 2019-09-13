<?php
$installer = $this;
$installer->startSetup();

// >> ATTRIBUTE FOR CATEGORY
$entityTypeId     = $installer->getEntityTypeId('catalog_category');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$installer->addAttribute('catalog_category', 'softvar_id',  array(
    'type'     => 'int',
    'label'    => 'Softvar Category Id',
    'input'    => 'text',
    'global'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'filterable'    => true,
    'visible'           => true,
    'required'          => false,
    'user_defined'      => true,
    'default'           => -1
));

$installer->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'softvar_id',
    '11'                    //last Magento's attribute position in General tab is 10
);

$attributeId = $installer->getAttributeId($entityTypeId, 'softvar_id');
$installer->run("
INSERT INTO `{$installer->getTable('catalog_category_entity_int')}`
(`entity_type_id`, `attribute_id`, `entity_id`, `value`)
    SELECT '{$entityTypeId}', '{$attributeId}', `entity_id`, '1'
        FROM `{$installer->getTable('catalog_category_entity')}`;
");
// << ATTRIBUTE FOR CATEGORY

$installer->endSetup();
?>