<?php
class Softvar_Api_StockController extends Softvar_Api_Controller_Abstract
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

			$result = Mage::getModel('softvar/stock_api')->items($post->offset);

		} catch(Exception $e) {
			$result['success'] =  false;
			$result['msg'] = $e->getMessage();
		}

		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
	}

	public function updateAction() {
		try {
			$result = array();

			if (is_array($this->validateModule())) {
				throw new Exception('ERROR: O módulo ainda não foi totalmente configurado, após a configuração completa será possivel usar a api.');
			}

			$post 	= $this->_post();

			// if($this->getToken() != $this->_token || !$this->_token) {
			// 	throw new Exception('ERROR: Token inválido.');
			// }

			$result = Mage::getModel('softvar/stock_api')->update($this->_post());
		} catch(Exception $e) {
			$result['success'] =  false;
			$result['msg'] = $e->getMessage();
		}

		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
	}
}