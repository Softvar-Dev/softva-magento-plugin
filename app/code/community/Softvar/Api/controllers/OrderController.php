<?php
class Softvar_Api_OrderController extends Softvar_Api_Controller_Abstract 
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
	
			$result = Mage::getModel('softvar/order_api')->items($post);
		} catch(Exception $e) {
			$result['success'] =  false;
			$result['msg'] = $e->getMessage();
		}
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
	}

	public function addCommentAction() {
		try {
			$result = array();

			if (is_array($this->validateModule())) {
				throw new Exception('ERROR: O módulo ainda não foi totalmente configurado, após a configuração completa será possivel usar a api.');
			}

			$post 	= $this->_post();
			
			if($this->getToken() != $this->_token || !$this->_token) {
				throw new Exception('ERROR: Token inválido.');	
			}
			
			$result = Mage::getModel('softvar/order_api')->addComment($post);
			
		} catch(Exception $e) {
			$result['success'] =  false;
			$result['msg'] = $e->getMessage();
		}
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
	}

	public function invoiceAction() {
		try {
			$result = array();

			if (is_array($this->validateModule())) {
				throw new Exception('ERROR: O módulo ainda não foi totalmente configurado, após a configuração completa será possivel usar a api.');
			}

			$post 	= $this->_post();
			
			if($this->getToken() != $this->_token || !$this->_token) {
				throw new Exception('ERROR: Token inválido.');	
			}

			$result = Mage::getModel('softvar/order_api')->invoice($post);	
		} catch(Exception $e) {
			$result['success'] =  false;
			$result['msg'] = $e->getMessage();
		}
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
	}

	public function shipmentAction() {
		try {
			$result = array();

			if (is_array($this->validateModule())) {
				throw new Exception('ERROR: O módulo ainda não foi totalmente configurado, após a configuração completa será possivel usar a api.');
			}

			$post 	= $this->_post();
			
			if($this->getToken() != $this->_token || !$this->_token) {
				throw new Exception('ERROR: Token inválido.');	
			}
		
			$result = Mage::getModel('softvar/order_api')->shipment($post);
		} catch(Exception $e) {
			$result['success'] =  false;
			$result['msg'] = $e->getMessage();
		}
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
	}

	public function importAction() {
		try {
			$result = array();

			if (is_array($this->validateModule())) {
				throw new Exception('ERROR: O módulo ainda não foi totalmente configurado, após a configuração completa será possivel usar a api.');
			}

			$post 	= $this->_post();
			
			if($this->getToken() != $this->_token || !$this->_token) {
				throw new Exception('ERROR: Token inválido.');	
			}
			$result = Mage::getModel('softvar/order_api')->import($post->offset,$post->increment_ids);
		} catch(Exception $e) {
			$result['success'] =  false;
			$result['msg'] = $e->getMessage();
		}
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
	}

	public function setImportAction() {
		try {
			$result = array();

			if (is_array($this->validateModule())) {
				throw new Exception('ERROR: O módulo ainda não foi totalmente configurado, após a configuração completa será possivel usar a api.');
			}

			$post 	= $this->_post();
			
			if($this->getToken() != $this->_token || !$this->_token) {
				throw new Exception('ERROR: Token inválido.');	
			}

			$result = Mage::getModel('softvar/order_api')->setImport($post);
		} catch(Exception $e) {
			$result['success'] =  false;
			$result['msg'] = $e->getMessage();
		}
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
	}

	public function listShippingMethodsAction() {
		if (is_array($this->validateModule())) {
			$this->getResponse()->setHeader('Content-type', 'application/json');
			$this->getResponse()->setBody(json_encode($this->validateModule()));
		} else {
			$result = Mage::getModel('softvar/order_api')->listShippingMethods();	
			$this->getResponse()->setHeader('Content-type', 'application/json');
			$this->getResponse()->setBody(json_encode($result));
		}
	}

	public function listPaymentMethodsAction() {
		if (is_array($this->validateModule())) {
			$this->getResponse()->setHeader('Content-type', 'application/json');
			$this->getResponse()->setBody(json_encode($this->validateModule()));
		} else {
			$result = Mage::getModel('softvar/order_api')->listPaymentMethods();
			$this->getResponse()->setHeader('Content-type', 'application/json');
			$this->getResponse()->setBody(json_encode($result));
		}
	}
}