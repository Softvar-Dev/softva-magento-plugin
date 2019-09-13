<?php
class Softvar_Api_Model_Stock_Api extends Mage_Api_Model_Resource_Abstract
{
	/**
	 * List stock of product
	 * @param  integer $offset
	 * @return array
	 */
	public function items($offset = 0)
	{
		$result = array('success' => false, 'stocks' => array(), 'offset' => $offset);

		try {

			if(!$offset) {
				$offset = 0;
			}

			$products = Mage::getModel("catalog/product")
				->getCollection()
				->addAttributeToSelect('*')
				->addAttributeToFilter('type_id', array('eq' => 'simple'))
				->setPageSize(100)
				->setCurPage($offset)
				->setOrder(array('type' => 'ASC', 'entity_id' => 'DESC'));

			$result['last_page'] = $products->getLastPageNumber();
			if ($offset <= $result['last_page']) {
				foreach ($products as $product) {
					$stock = Mage::getModel('cataloginventory/stock_item');
					$stock->loadByProduct($product->getId());

					$prepare = array();
					$prepare['in_stock'] 	= $stock->getIsInStock();
					$prepare['qty'] 		= $stock->getQty();
					$prepare['sku'] 		= $product->getSku();

					$result['stocks'][] = $prepare;
				}
			}

			$result['success'] = true;
		} catch (Exception $e) {
			$result['success'] = false;
			$result['msg'] = 'ERROR: ' . $e->getMessage();
		}

		return $result;
	}

	/**
	 * Update stock of product
	 * @param  array $stocks
	 * @return array
	 */
	public function update($stocks)
	{
		$result = array();
		foreach ($stocks as $stockData) {
			try {
				$prepare = array();

				$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $stockData->sku);

				if ($product) {

					if (!(is_a($stock = $product->getStockItem(), 'Mage_CatalogInventory_Model_Stock_Item'))) {
						$stock = Mage::getModel('cataloginventory/stock_item');
						$stock->assignProduct($product)
							->setData('stock_id', 1)
							->setData('store_id', 1);
					}

					$stock->setData('qty', $stockData->qty);
					$stock->setData('is_in_stock', $stockData->in_stock);
					$stock->setData('manage_stock', 1);
					$stock->setData('use_config_manage_stock', 0);
					$stock->save();

					$prepare['qty'] = $stock->getQty();
					$prepare['sku'] = $product->getSku();
					$prepare['success'] = true;
				} else {
					$prepare['success'] = false;
					$prepare['msg'] = "Produto Sku '{$stockData->sku}' não encontrado";
				}

			} catch (Exception $e) {
				$prepare['success'] = false;
				$prepare['msg'] = 'ERROR: ' . $e->getMessage();
			}

			$result[] = $prepare;
		}

		return $result;
	}
}