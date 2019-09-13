<?php
class Softvar_Api_Model_Price_Api extends Mage_Api_Model_Resource_Abstract
{
    /**
     * List prices
     * @param  integer $offset
     * @return array
     */
    public function items($offset = 1)
    {
        $result = array('success' => false, 'prices' => array(), 'offset' => $offset);

        try {

            if(!$offset) {
                $offset = 0;
            }

            $products = Mage::getModel("catalog/product")
                ->getCollection()
                ->addAttributeToSelect('*')
                ->setPageSize(100)
                ->setCurPage($offset)
                ->setOrder(array('type' => 'ASC', 'entity_id' => 'DESC'));

            $result['last_page'] = $products->getLastPageNumber();
            if ($offset <= $result['last_page']) {
                foreach ($products as $product) {
                    $prepare = array();

                    $prepare['sku'] = $product->getSku();
                    $prepare['price'] = $product->getPrice();
                    $prepare['special_price'] = $product->getSpecialPrice();

                    $result['prices'][] = $prepare;
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
     * Update price of product
     * @param  array $prices
     * @return array
     */
	public function update($prices)
	{
        $result = array();

        foreach ($prices as $priceData) {
            try {
                $prepare = array();
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $priceData->sku);

                if ($product) {
                    $product->setPrice($priceData->price);
                    $product->setSpecialPrice(isset($priceData->special_price) ? $priceData->special_price : null);
                    $product->save();

                    $prepare['price']       = $product->getPrice();
                    $prepare['sku']         = $product->getSKu();
                    $prepare['success']     = true;
                } else {
                    $prepare['success']     = false;
                    $prepare['msg']         = "Produto Sku '{$priceData->sku}' não encontrado";
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