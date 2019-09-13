<?php
class Softvar_Api_Model_Config_CustomerAttribute
{	

	public function toOptionArray()
	{
	    $attributes = Mage::getModel('customer/customer')->getAttributes();
	    $attributeArray 	= array();
	    $attributeArray[] 	= 'Selecione...';

	    foreach($attributes as $a){
	        foreach ($a->getEntityType()->getAttributeCodes() as $attributeName) {
	            $attributeArray[$attributeName] = $attributeName;
	        }
	         break;         
	    }
	    return $attributeArray; 
	}
}