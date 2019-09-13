<?php
class Softvar_Api_Model_Customer_Api extends Mage_Api_Model_Resource_Abstract
{
    /**
     * List Category
     * @param  integer $offset
     * @return array
     */
    public function items($offset = 0)
    {
        $result = array('success' => true, 'customers' => array(), 'offset' => $offset);

        try {
            $customers = Mage::getModel("customer/customer")
                ->getCollection()
                ->addAttributeToSelect('*')
                ->setPageSize(100)
                ->setCurPage($offset)
                ->setOrder(array('type' => 'ASC', 'entity_id' => 'DESC'));

            $result['last_page'] = $customers->getLastPageNumber();
            if ($offset <= $result['last_page']) {
                foreach ($customers as $customer) {
                    $prepare = array();
                    
                    if(!$offset) {
                        $offset = 0;
                    }

                    $prepare          = $customer->getData();
                    
                    unset($prepare['password_hash']);
                    $customerAddressId = $customer->getDefaultShipping();
                    if ($customerAddressId){
                    	$address = Mage::getModel('customer/address')->load($customerAddressId);
                    	$prepare['shipping_address'] = $address->getData();
                    }

                    $customerAddressId = $customer->getDefaultBilling();
                    if ($customerAddressId){
                    	$address = Mage::getModel('customer/address')->load($customerAddressId);
                    	$prepare['billing_address'] = $address->getData();
                    }

                    $result['customers'][] = $prepare;
                }
            }
            
            $result['success'] = true;
        } catch (Exception $e) {
            $result['success'] = false;
            $result['msg'] = 'ERROR: ' . $e->getMessage();
        }

        return $result;
    }
}