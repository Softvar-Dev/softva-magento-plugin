<?php
class Softvar_Api_Model_Config_Attribute
{	

	public function toOptionArray()
	{
	    $attributes = Mage::getModel('catalog/product')->getAttributes();
	    $attributeArray = array();
	    $attributeArray[] = 'Selecione...'; 
	    foreach($attributes as $a){
	        foreach ($a->getEntityType()->getAttributeCodes() as $attributeName) {

	            $attributeArray[$attributeName] = $attributeName;
	        }
	         break;         
	    }
	    return $attributeArray; 
	}
}