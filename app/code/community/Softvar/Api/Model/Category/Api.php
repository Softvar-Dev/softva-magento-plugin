<?php
class Softvar_Api_Model_Category_Api extends Mage_Api_Model_Resource_Abstract
{
    /**
     * List Category
     * @param  integer $offset
     * @return array
     */
    public function items($offset = 0, $magento_ids = array())
    {
        $result = array('success' => true, 'categories' => array(), 'offset' => $offset);

        try {
            $categories = Mage::getModel("catalog/category")
                ->getCollection()
                ->addAttributeToSelect('*')
                // ->setPageSize(100)
                // ->setCurPage($offset)
                ->setOrder(array('type' => 'ASC', 'entity_id' => 'DESC'));

            if (!empty($magento_ids)) {
                $categories->addAttributeToFilter('entity_id', array('in' => $magento_ids));
            }

            $result['last_page'] = $categories->getLastPageNumber();
            if ($offset <= $result['last_page']) {
                foreach ($categories as $category) {
                    $prepare = array();
                    
                    if(!$offset) {
                        $offset = 0;
                    }

                    $prepare['magento_id']          = $category->getId();
                    $prepare['parent_magento_id']   = $category->getParentId();
                    $prepare['name']                = $category->getName();
                    
                    $result['categories'][] = $prepare;
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
     * Create category
     * @param  array $categories
     * @return array
     */
	public function create($categories)
	{
        //$result = array('exceptions' => false, 'categories' => array());
        $result = array();

        if(!$categories) {
            $result['success']  = false;
            $result['msg']      = 'Nenhum dado informado.';

            return $result;
        }

        foreach($categories as $categoryData) {
            try {
                $category = Mage::getModel('catalog/category')->load($categoryData->magento_id);
                $categoryData->parent_magento_id = isset($categoryData->parent_magento_id) ? $categoryData->parent_magento_id : null;
               
                if ($category->getId()) {
                    $category->setPath($this->categoryPath($categoryData->parent_magento_id, "/" . $category->getId(), false));
                } else {
                    $category = new Mage_Catalog_Model_Category();
                    $category->setEntityId($categoryData->magento_id);
                    $category->setPath($this->categoryPath($categoryData->parent_magento_id, '', true));
                    $category->setIncludeInMenu(0);
                    $category->setUrlKey('');
                    $category->setIsAnchor(1);
                }

                $category->setName($categoryData->name);
                $category->setDisplayMode('PRODUCTS');
                $category->setIsActive($categoryData->active);
                $category->save();

                $exceptions = array();
                $prepare    = array();

                
                $prepare['magento_id']    = $category->getId(); 
                $prepare['path']          = $this->categoryPath($categoryData->parent_magento_id, '', true);
                $prepare['active']        = $category->getActive();
                
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
     * Get Category Path
     * @param  integer  $categoryId
     * @param  string  $updatePath
     * @param  boolean $new
     * @return string
     */
    private function categoryPath($categoryId, $updatePath = "", $new = false) 
    {
        if ($categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            if ($new == true) {
                $categoryPath = $category->getPath();
            } else {
                $categoryPath = $category->getPath() . $updatePath;
            }
        } else {
            $websites = Mage::app()->getWebsites();
            $storeId = $websites[1]->getDefaultStore()->getId();
            $rootCategoryId = Mage::app()->getStore($storeId)->getRootCategoryId();
            $rootCategory = Mage::getModel('catalog/category')->load($rootCategoryId);
            $categoryPath = $rootCategory->getPath() . $updatePath;
        }

        return $categoryPath;
    }
}