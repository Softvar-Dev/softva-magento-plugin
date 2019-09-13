<?php

class Softvar_Api_Model_Config_Address
{
    public function toOptionArray()
    {
        return array(
        	array('value' => 0, 'label' => Mage::helper('softvar')->__('Selecione...')),
            array('value' => 1, 'label' => Mage::helper('softvar')->__('Street 1')),
            array('value' => 2, 'label' => Mage::helper('softvar')->__('Street 2')),
            array('value' => 3, 'label' => Mage::helper('softvar')->__('Street 3')),
            array('value' => 4, 'label' => Mage::helper('softvar')->__('Street 4'))
        );
    }

}