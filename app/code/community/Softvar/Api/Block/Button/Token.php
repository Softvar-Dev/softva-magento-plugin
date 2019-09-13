<?php
class Softvar_Api_Block_Button_Token extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url  = Mage::helper("adminhtml")->getUrl("softvar/adminhtml_generate/token");
        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('Gerar token')
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }
}