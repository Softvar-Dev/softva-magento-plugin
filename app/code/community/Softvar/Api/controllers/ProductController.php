<?php
class Softvar_Api_ProductController extends Softvar_Api_Controller_Abstract 
{
	public function _construct() {
		$this->_setToken($_SERVER['HTTP_TOKEN']);
	}

	public function itemsAction() {
		try {
			$result = array();

			if (is_array($this->validateModule())) {
				throw new Exception('ERROR: O módulo ainda não foi totalmente configurado, após a configuração completa será possivel usar a api.');
			}

			$post 	= $this->_post();
			
			if($this->getToken() != $this->_token || !$this->_token) {
				throw new Exception('ERROR: Token inválido.');	
			}

			$result = Mage::getModel("softvar/product_api")->items($post->offset);
		} catch(Exception $e) {
			$result['success'] =  false;
			$result['msg'] = $e->getMessage();
		}
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
	}

	public function createAction() {
		try {
			$result = array();

			if (is_array($this->validateModule())) {
				throw new Exception('ERROR: O módulo ainda não foi totalmente configurado, após a configuração completa será possivel usar a api.');
			}

			$post 	= $this->_post();
			
			if($this->getToken() != $this->_token || !$this->_token) {
				throw new Exception('ERROR: Token inválido.');	
			}

			$result = Mage::getModel("softvar/product_api")->create($post);
		} catch(Exception $e) {
			$result['success'] =  false;
			$result['msg'] = $e->getMessage();
		}
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
	}
}