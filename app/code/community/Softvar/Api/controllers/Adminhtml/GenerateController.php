<?php
class Softvar_Api_Adminhtml_GenerateController extends Mage_Adminhtml_Controller_Action {
	public function tokenAction() {
		try {
			$token =  bin2hex(mcrypt_create_iv(20, MCRYPT_DEV_RANDOM));
			Mage::getModel('core/config')->saveConfig('softvar_section/softvar_group/softvar_token', $token);
			Mage::getSingleton('adminhtml/session')->addSuccess('Token criado com sucesso!');
		} catch(Exception $e) {
			Mage::getSingleton('adminhtml/session')->addError('Não foi possivel gerar o token.');
		}

		$this->_redirectReferer();
	}
}