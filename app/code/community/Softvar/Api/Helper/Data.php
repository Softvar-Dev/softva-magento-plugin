<?php
class Softvar_Api_Helper_Data extends Mage_Core_Helper_Abstract
{
	private $allWebsiteIds = array();

	/**
	 * Map of attributes system configuration
	 * @var array
	 */
	public $_mapAttributesSystemCofiguration = array(
		'softvar_store_id',
		'softvar_customer_attribute_ie',
		'attributes_id',
		'softvar_variacao_horizontal',
		'softvar_variacao_vertical',
		'softvar_fabricante',
		'softvar_altura',
		'softvar_largura',
		'softvar_comprimento',
		'softvar_fretegratis',
		'softvar_ean'
	);
	/**
	 * Return config module system
	 * @param  string $group
	 * @param  string $code]
	 * @return string
	 */
	public function getConfig($group, $code) {
		return Mage::getStoreConfig('softvar_section/'.$group.'/'.$code);
	}

	public function validateModule() {
		foreach ($this->_mapAttributesSystemCofiguration as $code) {
			if (!Mage::getStoreConfig('softvar_section/softvar_group/'.$code)) {
				return false;
			}
		}

		return true;
	}

	public function getAllWebsiteIds()
	{
		if (!$this->allWebsiteIds) {
			foreach (Mage::app()->getWebsites() as $website) {
				$this->allWebsiteIds[] = $website->getId();
			}
		}

		return $this->allWebsiteIds;
	}
}