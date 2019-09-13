<?php
class Softvar_Api_Model_Config_Attributeset
{	
	public function toOptionArray()
	{
		$entityType = Mage::getModel('catalog/product')->getResource()->getTypeId();
		$collection = Mage::getResourceModel('eav/entity_attribute_set_collection')->setEntityTypeFilter($entityType);
		$allSet = array();
		$allSet[] = 'Selecione...';
		
		foreach($collection as $coll){
		 $attributeSet['label'] = $coll->getAttributeSetName();
		 $attributeSet['value'] = $coll->getAttributeSetId();
		 $allSet[] = $attributeSet;
		}
		return $allSet;
	}
}