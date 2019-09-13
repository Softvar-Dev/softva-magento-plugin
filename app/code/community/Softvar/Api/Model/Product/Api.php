<?php
class Softvar_Api_Model_Product_Api extends Mage_Api_Model_Resource_Abstract
{
	/**
	 * List product
	 * @param  integer $offset
	 * @return array
	 */
	public function items($offset = 0)
	{
		$result = array('success' => false, 'products' => array(), 'offset' => $offset);

		try {
			if(!$offset) {
				$offset = 0;
			}

			$attributeVariacaoHorizontal 	= Mage::helper('softvar')->getConfig('softvar_group','softvar_variacao_horizontal');
			$attributeVariacaoVertical 		= Mage::helper('softvar')->getConfig('softvar_group','softvar_variacao_vertical');
			$attributeAltura 				= Mage::helper('softvar')->getConfig('softvar_group','softvar_altura');
			$attributeLargura 				= Mage::helper('softvar')->getConfig('softvar_group','softvar_largura');
			$attributeComprimento			= Mage::helper('softvar')->getConfig('softvar_group','softvar_comprimento');
			$attributeFreteGratis			= Mage::helper('softvar')->getConfig('softvar_group','softvar_fretegratis');
			$attributeFabricante			= Mage::helper('softvar')->getConfig('softvar_group','softvar_fabricante');
			$attributeEan					= Mage::helper('softvar')->getConfig('softvar_group','softvar_ean');
			$attributeStoreId               = Mage::helper('softvar')->getConfig('softvar_group','softvar_store_id');

			$products = Mage::getModel("catalog/product")
				->getCollection()
				->addAttributeToSelect('*')
				->addAttributeToFilter('type_id', array('simple','configurable'))
				->setPageSize(100)
				->setCurPage($offset)
				->setOrder(array('type' => 'ASC', 'entity_id' => 'DESC'));


			$result['last_page'] = $products->getLastPageNumber();
			if ($offset <= $result['last_page']) {
				foreach ($products as $product) {
					$prepare = array();

					$prepare['id'] 					= $product->getId();
					$prepare['name'] 				= $product->getName();
					$prepare['sku'] 				= $product->getSku();
					$prepare['type']				= $product->getTypeId();
					$prepare['visibility']			= $product->getVisibility();
					$prepare['status']				= $product->getStatus();
					$prepare['description'] 		= $product->getDescription();
					$prepare['short_description'] 	= $product->getShortDescription();
					$prepare['status'] 				= $product->getStatus();
					$prepare['price'] 				= $product->getPrice();
					$prepare['special_price'] 		= $product->getSpecialPrice();
					$prepare['special_from_date']	= $product->getSpecialFromDate();
    				$prepare['special_to_date']     = $product->getSpecialToDate();
					$prepare['weight'] 				= $product->getWeight();
					$prepare['categories'] 			= $product->getCategoryIds();
					$prepare['meta_title']		    = $product->getMetaTitle();
					$prepare['meta_keyword']		= $product->getMetaKeyword();
					$prepare['meta_description']	= $product->getMetaDescription();

    				$attributes = $product->getAttributes();

					foreach ($attributes as $attribute) {
						$attributeCode = $attribute->getAttributeCode();

						if ($attributeCode == $attributeFabricante) {
							$value = $attribute->getFrontend()->getValue($product);
							$prepare['manufacturer'] = $value;
						}

						if ($attributeCode == $attributeAltura) {
							$value = $attribute->getFrontend()->getValue($product);
							$prepare['height'] = $value;
						}

						if ($attributeCode == $attributeLargura) {
							$value = $attribute->getFrontend()->getValue($product);
							$prepare['width'] = $value;
						}

						if ($attributeCode == $attributeComprimento) {
							$value = $attribute->getFrontend()->getValue($product);
							$prepare['length'] = $value;
						}

						if ($attributeCode == $attributeFreteGratis) {
							$value = $attribute->getFrontend()->getValue($product);
							$prepare['frete_gratis'] = $value;
						}

						if ($attributeCode == $attributeVariacaoHorizontal) {
							$value = $attribute->getFrontend()->getValue($product);
							$prepare['variacao_horizontal'] = $value;
						}

						if ($attributeCode == $attributeVariacaoVertical) {
							$value = $attribute->getFrontend()->getValue($product);
							$prepare['variacao_vertical'] = $value;
						}

						if ($attributeCode == $attributeEan) {
							$value = $attribute->getFrontend()->getValue($product);
							$prepare['ean'] = $value;
						}
					}

					if ($product->type_id == 'configurable') {
						$options = array();
						$productAttributesOptions = $product->getTypeInstance(true)->getConfigurableOptions($product);
						foreach ($productAttributesOptions as $productAttributeOption) {
							foreach ($productAttributeOption as $optionValues) {
								$val = $optionValues['option_title'];
								$options[$optionValues['sku']][$optionValues['attribute_code']] = $val;
							}
						}

						$prepare['options'] = $options;
					}

					$result['products'][] = $prepare;
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
	 * Create/Update product
	 * @param  array $products
	 * @return array
	 */
	public function create($products)
	{
		if(!$products) {
			throw new Exception('Nenhum dado informado.');
		}

		foreach($products as $product) {
			try {
				$prepare = array();

				$this->_createProductSimple($product);
				$simpleSkus 	= array();
				foreach ($product->Skus as $sku) {
					$simpleSkus[] = $sku->IDSku;
				}

				$prepare['IDSKu'] = $simpleSkus;

				if ($product->Grade) {
					$configurableProduct = $this->_createProductConfigurable($product);

					if ($configurableProduct) {
						$prepare['IDProduto'] 	= $product->IDProduto;
						$this->_associateSimpleProduct($configurableProduct, $simpleSkus);
					}
				}

				$prepare['success'] = true;
			} catch (Exception $e) {
				$prepare['success'] = false;
				$prepare['msg'] = 'ERROR: ' . $e->getMessage();
			}

			$result[] = $prepare;
		}

		return $result;
	}

	/**
	 * Create Simple Product
	 * @param  array $product
	 * @param  integer $visibility
	 * @return array
	 */
	private function _createProductSimple($products)
	{
		foreach($products->Skus as $product) {
		 	$sProduct = Mage::getModel('catalog/product')->loadByAttribute('sku',$product->IDSku);

		 	if(!$sProduct) {
		 		$sProduct = Mage::getModel('catalog/product');
		 		$sProduct->setAttributeSetId(Mage::helper('softvar')->getConfig('softvar_group','attributes_id'));
		 	}

		    $sProduct
			    ->setStoreId(Mage::helper('softvar')->getConfig('softvar_group','softvar_store_id'))
			    ->setWebsiteIds(Mage::helper('softvar')->getAllWebsiteIds())
			    ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
			    ->setSku($product->IDSku)
			    ->setName($product->Referencia)
			    ->setWeight($product->Peso)
			    ->setTaxClassId(0)
			    ->setHasOptions(0)
			    ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
			    ->setPrice($product->PrecoVenda)
			    ->setSpecialPrice($product->PrecoPor)
			    ->setSpecialFromDate($product->DataExpirarPrecoEspecialDe)
    			->setSpecialToDate($product->DataExpirarPrecoEspecialAte)
			    ->setDescription($product->DescricaoLonga)
			    ->setShortDescription($product->Descricao)
			    ->setStatus($this->getFormatedProductStatus($product->Inativo))
			    ->setMetaTitle($product->TituloSite)
    			->setMetaKeyword($product->PalavrasChaves)
    			->setMetaDescription($product->MetaTag)
    			->setEan($product->CodigoBarras);

    		if (!$products->Grade) {
    			$sProduct->setCategoryIds($products->Categorias);
    			$sProduct->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);

    			$AttributeFabricante = Mage::helper('softvar')->getConfig('softvar_group','softvar_fabricante');

				if($products->Fabricante != null){
					$fabricanteOptionId = $this->_getAttributeOptionValue($AttributeFabricante, $products->Fabricante);

					if (!$fabricanteOptionId) {
						$fabricanteOptionId = $this->_addAttributeOption($AttributeFabricante, $products->Fabricante);
					}

					$sProduct->setData($AttributeFabricante, $fabricanteOptionId);
				}
    		}

			$attributeVariacaoHorizontal 	= Mage::helper('softvar')->getConfig('softvar_group','softvar_variacao_horizontal');
			$attributeVariacaoVertical 		= Mage::helper('softvar')->getConfig('softvar_group','softvar_variacao_vertical');
			$attributeAltura 				= Mage::helper('softvar')->getConfig('softvar_group','softvar_altura');
			$attributeLargura 				= Mage::helper('softvar')->getConfig('softvar_group','softvar_largura');
			$attributeComprimento			= Mage::helper('softvar')->getConfig('softvar_group','softvar_comprimento');
			$attributeFreteGratis			= Mage::helper('softvar')->getConfig('softvar_group','softvar_fretegratis');
			$attributeEan					= Mage::helper('softvar')->getConfig('softvar_group','softvar_ean');

			if($product->CodigoBarras != null) {
				$sProduct->setData($attributeEan, $product->CodigoBarras);
			}

			if($product->VariacaoVertical != null){
				$variacaoVerticalOptionId = $this->_getAttributeOptionValue($attributeVariacaoVertical, $product->VariacaoVertical->Descricao);

				if (!$variacaoVerticalOptionId) {
		        	$variacaoVerticalOptionId = $this->_addAttributeOption($attributeVariacaoVertical, $product->VariacaoVertical->Descricao);
		    	}

				$sProduct->setData($attributeVariacaoVertical, $variacaoVerticalOptionId);
			}

			if($product->VariacaoHorizontal != null){
				$variacaoHorizontalOptionId = $this->_getAttributeOptionValue($attributeVariacaoHorizontal, $product->VariacaoHorizontal->Descricao);

				if (!$variacaoHorizontalOptionId) {
		        	$variacaoHorizontalOptionId = $this->_addAttributeOption($attributeVariacaoHorizontal, $product->VariacaoHorizontal->Descricao);
		    	}

				$sProduct->setData($attributeVariacaoHorizontal, $variacaoHorizontalOptionId);
			}

			if($product->Altura != null && $attributeAltura){
				$sProduct->setData($attributeAltura, $product->Altura);
			}

			if($product->Largura != null && $attributeLargura){
				$sProduct->setData($attributeLargura, $product->Largura);
			}

			if($product->Comprimento != null && $attributeComprimento){
				$sProduct->setData($attributeComprimento, $product->Comprimento);
			}

			if($product->FreteGratis != null && $attributeFreteGratis){
				$sProduct->setData($attributeFreteGratis, $product->FreteGratis);
			}

			$sProduct->save();
		}


		return $sProduct->getSku();
	}

	/**
	 * Create Configurable Product
	 * @param  array $product
	 * @return boolean
	 */
	private function _createProductConfigurable($product)
	{
	 	$configProduct = Mage::getModel('catalog/product')->loadByAttribute('sku',$product->IDProduto);

	 	if(!$configProduct) {
	 		$configProduct = Mage::getModel('catalog/product');
	 	}

		$configProduct
	        ->setStoreId(Mage::helper('softvar')->getConfig('softvar_group','softvar_store_id'))
	        ->setWebsiteIds(Mage::helper('softvar')->getAllWebsiteIds())
	        ->setAttributeSetId(Mage::helper('softvar')->getConfig('softvar_group','attributes_id'))
	        ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
	        ->setCreatedAt(strtotime('now'))
	        ->setSku($product->IDProduto)
	        ->setName($product->Referencia)
	        ->setTaxClassId(0)
	        ->setHasOptions(0)
	        ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
	        ->setDescription($product->DescricaoLonga)
	        ->setShortDescription($product->Descricao)
	        ->setCategoryIds($product->Categorias)
	        ->setStatus($this->getFormatedProductStatus($product->Inativo))
	        ->setPrice(0.00);

		$attributeFabricante = Mage::helper('softvar')->getConfig('softvar_group','softvar_fabricante');

		if($product->Fabricante != null){
			$fabricanteOptionId = $this->_getAttributeOptionValue($attributeFabricante, $product->Fabricante);

			if (!$fabricanteOptionId) {
				$fabricanteOptionId = $this->_addAttributeOption($attributeFabricante, $product->Fabricante);
			}

			$configProduct->setData($attributeFabricante, $fabricanteOptionId);
		}

	  	if (!$configProduct->getId())
	    {
	        $AttributeVariacaoHorizontal 	= Mage::helper('softvar')->getConfig('softvar_group','softvar_variacao_horizontal');
			$AttributeVariacaoVertical 		= Mage::helper('softvar')->getConfig('softvar_group','softvar_variacao_vertical');
	        $AttributeId 					= array();

	        if($AttributeVariacaoHorizontal != null){
	        	$AttributeId[] = Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', $AttributeVariacaoHorizontal);
	    	}

	    	if($AttributeVariacaoVertical != null){
	        	$AttributeId[] = Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', $AttributeVariacaoVertical);
	    	}

	        $configProduct->getTypeInstance()->setUsedProductAttributeIds($AttributeId); //attribute ID of attribute 'color' in my store
	        $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();

	        $configProduct->setCanSaveConfigurableAttributes(true);
	        $configProduct->setConfigurableAttributesData($configurableAttributesData);

		    //coloca para gerenciar o estoque nos configuraveis
		    $configProduct->setStockData(array('use_config_manage_stock' => 1,'is_in_stock' => 1,'is_salable' => 1));
	    }

	     return $configProduct->save();
	}

	/**
	 * Assoc simple product in configurable product
	 * @param  object $configurableProduct
	 * @param  array $arraySkus
	 * @return N/A
	 */
	private function _associateSimpleProduct($configurableProduct, $arraySkus)
	{
		$simpleIds = array();
		foreach ($arraySkus as $key => $sku) {
			$simpleProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
			$simpleIds[$simpleProduct->getId()] = 1;
		}

		Mage::getResourceModel('catalog/product_type_configurable')->saveProducts($configurableProduct, array_keys($simpleIds));
	}

	/**
	 * Get value option of attribute
	 * @param  string $arg_attribute
	 * @param  string $arg_value
	 * @return string
	 */
	private function _getAttributeOptionValue($arg_attribute, $arg_value)
	{
	    $attribute_model        	= Mage::getModel('eav/entity_attribute');
	    $attribute_options_model	= Mage::getModel('eav/entity_attribute_source_table') ;

	    $attribute_code         = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
	    $attribute              = $attribute_model->load($attribute_code);

	    $attribute_table        = $attribute_options_model->setAttribute($attribute);
	    $options                = $attribute_options_model->getAllOptions(false);

	    foreach($options as $option) {
	        if ($option['label'] == $arg_value) {
	            return $option['value'];
	        }
	    }

	    return false;
	    //$optionValue = $this->getAttributeOptionValue("size", "XL");
	}

	/**
	 * Get option of attribute
	 * @param  string $arg_attribute
	 * @param  string $arg_value
	 * @return string
	 */
	private function _addAttributeOption($arg_attribute, $arg_value)
	{
	    $attribute_model        = Mage::getModel('eav/entity_attribute');
	    $attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;

	    $attribute_code         = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
	    $attribute              = $attribute_model->load($attribute_code);

	    $attribute_table        = $attribute_options_model->setAttribute($attribute);
	    $options                = $attribute_options_model->getAllOptions(false);

	    $value['option'] = array($arg_value,$arg_value);
	    $result = array('value' => $value);
	    $attribute->setData('option',$result);
	    $attribute->save();

	    return $this->_getAttributeOptionValue($arg_attribute, $arg_value);
	    //$optionValue = $this->addAttributeOption("size", "XXL");
	}

	private function getFormatedProductStatus($inativo)
	{
		if ($inativo == 1) {
			return Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
		}

		return Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
	}
}