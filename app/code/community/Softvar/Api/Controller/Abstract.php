<?php

abstract class Softvar_Api_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
    protected $_token;

    /**
     * Get post
     * @return object
     */
    protected function _post()
    {
        return json_decode(file_get_contents('php://input'));
    }

    /**
     * Get Post Array
     * @return array
     */
    protected function _postArray() {
    	return json_decode(file_get_contents('php://input'), true);
    }

    protected function validateModule() {
        return Mage::helper('softvar')->validateModule();
    }

    protected function getToken() {
        return Mage::helper('softvar')->getConfig('softvar_group','softvar_token');
    }

    protected function _setToken($token) {
        $this->_token = $token;
    }
}