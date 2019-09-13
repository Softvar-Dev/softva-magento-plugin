<?php
class Softvar_Api_Model_Media_Image extends Mage_Api_Model_Resource_Abstract
{
	const 	ATTRIBUTE_CODE 	= 'media_gallery';
	private $_mimeTypes 	= array('image/jpeg' => 'jpg', 'image/gif'  => 'gif', 'image/png'  => 'png');
	
	/**
	 * Create Image
	 * @param  string 	$sku 
	 * @param  array 	$data      
	 * @param  integer 	$store
	 * @return array
	 */
	public function create($datas) 
	{
		$result = array();

		foreach ($datas as $data) {	
			try {
				$prepare 				= array();
				$prepare['sku'] 		= $data['sku'];
				$prepare['position'] 	= $data['position'];
				$prepare['file'] 		= $this->_createImageBase64($data['sku'], $data, $data['store']);
				$prepare['success'] 	= true;
			} catch (Exception $e) {
				$prepare['success'] 	= false;
	            $prepare['msg'] 		= 'ERROR: ' . $e->getMessage();
			}

			$result[] = $prepare;
		}
			
		return $result;
	}

	/**
	 * Convert imagebase64
	 * @param  string $sku
	 * @param  array $data      
	 * @param  integer $store   
	 * @return string           
	 */
	private function _createImageBase64($sku, $data, $store = null) 
	{
		$product = $this->_initProduct($sku, $store);
		$gallery = $this->_getGalleryAttribute($product);

		if (!isset($data['file']) || !isset($data['file']['mime']) || !isset($data['file']['content'])) {
			throw new Exception(Mage::helper('catalog')->__('Image not specified.'));
		}

		if (!isset($this->_mimeTypes[$data['file']['mime']])) {
			throw new Exception(Mage::helper('catalog')->__('Invalid image type.'));
		}

		$fileContent = @base64_decode($data['file']['content'], true);

		if (!$fileContent) {
			throw new Exception(Mage::helper('catalog')->__('Image content is not valid base64 data.'));
		}

		unset($data['file']['content']);

		$tmpDirectory = Mage::getBaseDir('var') . DS . 'softvarimgbase64' . DS . uniqid();

		if (isset($data['file']['name']) && $data['file']['name']) {
			$fileName  = $data['file']['name'];
		} else {
			$fileName  = 'image';
		}
		
		$fileName .= '.' . $this->_mimeTypes[$data['file']['mime']];

		$ioAdapter = new Varien_Io_File();

		// Create temporary directory for api
		$ioAdapter->checkAndCreateFolder($tmpDirectory);
		$ioAdapter->open(array('path'=>$tmpDirectory));

		// Write image file
		$ioAdapter->write($fileName, $fileContent, 0775);
		unset($fileContent);

		// Adding image to gallery
		$file = $gallery->getBackend()->addImage($product, $tmpDirectory . DS . $fileName, null, true);

		// Remove temporary directory
		$ioAdapter->rmdir($tmpDirectory, true);

		$gallery->getBackend()->updateImage($product, $file, $data);

		if (isset($data['types'])) {
			$gallery->getBackend()->setMediaAttribute($product, $data['types'], $file);
		}

		$product->save();
		return $gallery->getBackend()->getRenamedImage($file);
	}

	public function update($datas)
	{
		$result = array();

		foreach ($datas as $data) {
			try {
				$prepare 				= array();
				$prepare['sku'] 		= $data['sku'];
				$prepare['position'] 	= $data['position'];
				$prepare['file'] 		= $this->_updateImage($data['sku'], $data['oldFile'], $data, $data['store']);
				$prepare['success'] 	= true;
			} catch (Exception $e) {
				$prepare['success'] 	= false;
	            $prepare['msg'] 		= 'ERROR: ' . $e->getMessage();
			}

			$result[] = $prepare;
		}

		return $result;
	}

	 /**
      * Update image data
      *
      * @param int|string $sku
      * @param string $file
      * @param array $data
      * @param string|int $store
      * @return boolean
      */
	private function _updateImage($sku, $file, $data, $store = null)
	{

		$product = $this->_initProduct($sku, $store);

		$gallery = $this->_getGalleryAttribute($product);

		if (!$gallery->getBackend()->getImage($product, $file)) {
			throw new Exception(Mage::helper('catalog')->__('Image not exists.'));
		}

		if (isset($data['file']['mime']) && isset($data['file']['content'])) {
			if (!isset($this->_mimeTypes[$data['file']['mime']])) {
				throw new Exception(Mage::helper('catalog')->__('Invalid image type.'));
			}

			$fileContent = @base64_decode($data['file']['content'], true);
			if (!$fileContent) {
				throw new Exception(Mage::helper('catalog')->__('Image content is not valid base64 data.'));
			}

			unset($data['file']['content']);

			$ioAdapter = new Varien_Io_File();
			try {
				$fileName = Mage::getBaseDir('media'). DS . 'catalog' . DS . 'product' . $file;
				$ioAdapter->open(array('path'=>dirname($fileName)));
				$ioAdapter->write(basename($fileName), $fileContent, 0666);
			} catch(Exception $e) {
				return $e->getMessage();
			}
		}

		$gallery->getBackend()->updateImage($product, $file, $data);

		if (isset($data['types']) && is_array($data['types'])) {
			$oldTypes = array();
			foreach ($product->getMediaAttributes() as $attribute) {
				if ($product->getData($attribute->getAttributeCode()) == $file) {
					$oldTypes[] = $attribute->getAttributeCode();
				}
			}

			$clear = array_diff($oldTypes, $data['types']);

			if (count($clear) > 0) {
				$gallery->getBackend()->clearMediaAttribute($product, $clear);
			}

			$gallery->getBackend()->setMediaAttribute($product, $data['types'], $file);
		}


		if ($product->save())
			return $gallery->getBackend()->getRenamedImage($file);

		return false;
	}
 
 	public function remove($skus) 
 	{	
		$result = array();

		foreach ($skus as $sku) {	
			try {
				$prepare 				= array();
				$prepare['sku'] 		= $sku['sku'];
				$prepare['file'] 		= $this->_removeImage($sku);
				$prepare['success'] 	= true;
			} catch (Exception $e) {
				$prepare['success'] 	= false;
	            $prepare['msg'] 		= 'ERROR: ' . $e->getMessage();
			}

			$result[] = $prepare;
		}
			
		return $result;
 	}
	/**
	* Remove images from product
	*
	* @param int|string $sku
	* @param string $file
	* @return boolean
	*/
	public function _removeImage($sku)
	{
		$_product 	= $this->_initProduct($sku['sku']);
		$mediaApi 	= Mage::getModel("catalog/product_attribute_media_api");
	    $items 		= $mediaApi->items($_product->getId());
		$result 	= array();

		foreach($items as $item) {
			$prepare = array();
			if(strcmp($item['file'], $sku['file']) == 0) {
				if ($mediaApi->remove($_product->getId(), $item['file'])) {
					@unlink(Mage::getBaseDir('media') . '/catalog/product/' . $item['file']);
					$prepare['arquivo'] = $item['file'];
					$prepare['remove'] = true;
				} else {
					$prepare['arquivo'] = $item['file'];
					$prepare['remove'] = false;
				}
				$result[] = $prepare;
			}
		}

		return $result;
	}

	/**
	 * Get Store Id
	 * @param  integer $store
	 * @return integer
	 */
	private function _getStoreId($store)
	{
		if (is_null($store)) {
			$store = ($this->_getSession()->hasData($this->_storeIdSessionField) ? $this->_getSession()->getData($this->_storeIdSessionField) : 0);
		}
 
		return Mage::app()->getStore($store)->getId();
	}

	/**
	 * Load product
	 * @param  string $sku
	 * @param  integer $store
	 * @return object
	 */
	private function _initProduct($sku, $store)
	{
		$product = Mage::getModel('catalog/product')
			->setStoreId($this->_getStoreId($store));

		$idBySku = $product->getIdBySku($sku);
		
		if ($idBySku) {
			$sku = $idBySku;
		}
		
		$product->load($sku);

		if (!$product->getId()) {
			throw new Exception('Product not exists.');
		}

		return $product;
	}

	/**
	 * Get attribute gallery
	 * @param  object $product
	 * @return array
	 */
	private function _getGalleryAttribute($product)
	{
	 	$attributes = $product->getTypeInstance(true)->getSetAttributes($product);
 
         if (!isset($attributes[self::ATTRIBUTE_CODE])) {
             throw new Exception('not media');
         }
 
         return $attributes[self::ATTRIBUTE_CODE];
	}
}