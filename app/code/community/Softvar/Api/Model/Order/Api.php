<?php

class Softvar_Api_Model_Order_Api extends Mage_Api_Model_Resource_Abstract
{
	/**
	 * List orders
	 * @param  integer $offset 
	 * @return array
	 */
	public function items($data)
	{
		$result = array('success' => false, 'orders' => array(), 'offset' => $data->offset);

		try {
			
			if(!$data->offset) {
				$offset = 0;
			}

			$orders = Mage::getModel("sales/order")
				->getCollection()
				->addAttributeToSelect('*')
				->setPageSize(100)
				->setCurPage($offset)
				->setOrder('entity_id', 'DESC');

			$result['last_page'] = $orders->getLastPageNumber();
				$attributeInscricaoEstadual 	= Mage::helper('softvar')->getConfig('softvar_group','softvar_customer_attribute_ie');
				$attributeDataEntrega           = Mage::helper('softvar')->getConfig('softvar_group', 'softvar_dataentrega');

			if ($offset <= $result['last_page']) {
				foreach ($orders as $order) {
					$customer 	= Mage::getSingleton('customer/customer')->load($order->getCustomerId());
					
					$prepare 	= array();
					$prepare['increment_id'] 						= $order->getIncrementId();
					$prepare['state'] 								= $order->getState();
					$prepare['status'] 								= $order->getStatus();
					$prepare['coupon_code'] 						= $order->getCouponCode();
					$prepare['protected_code'] 						= $order->getProtectedCode();
					$prepare['created_at'] 							= $order->getCreatedAt();
					$prepare['updated_at'] 							= $order->getCreatedAt();
					$prepare['discount'] 							= $order->getDiscountAmount();
					$prepare['store_id'] 							= $order->getStoreId();
					$prepare['store_name'] 							= $order->getStoreName();
					$prepare['grand_total'] 						= $order->getGrandTotal();
					$prepare['subtotal'] 							= $order->getSubtotal();
					$prepare['tax_amount'] 							= $order->getTaxAmount();
					$prepare['customer']['id'] 						= $order->getCustomerId();
					$prepare['customer']['firstname'] 				= $order->getCustomerFirstname();
					$prepare['customer']['lastname'] 				= $order->getCustomerLastname();
					$prepare['customer']['email'] 					= $order->getCustomerEmail();
					$prepare['customer']['dob'] 					= $order->getCustomerDob();
					$prepare['customer']['taxvat'] 					= $order->getCustomerTaxvat();
					$prepare['customer']['customer_is_guest'] 		= $order->getCustomerIsGuest();
					
					$prepare['customer']['inscricao_estadual'] = null;
					if ($attributeInscricaoEstadual) {
						$prepare['customer']['inscricao_estadual']  = $customer->getData($attributeInscricaoEstadual);
					}

					$genderText = $customer->getAttribute('gender')->getSource()->getOptionText($customer->getGender());
					
					$prepare['customer']['customer_gender'] = null;
					if ($genderText) {
						$prepare['customer']['customer_gender'] = $genderText;
					}

					$prepare['shipping']['description'] 			= $order->getShippingMethod();
					$prepare['shipping']['method'] 					= $order->getShippingMethod();
					$prepare['shipping']['amount'] 					= $order->getShippingAmount();
					
					$prepare['shipping']['data_entrega']            = null;
					if ($attributeDataEntrega) {
						$prepare['shipping']['data_entrega']        = $attributeDataEntrega;
					}

					$prepare['billing_address'] = $this->_setAddress($order->getBillingAddress());
					
					if ($order->getIsVirtual()) {
						$prepare['shipping_address'] = $prepare['billing_address'];
					} else {
						$prepare['shipping_address'] = $this->_setAddress($order->getShippingAddress());
					}
					
					foreach ($order->getAllItems() as $item) {
						//if($item->getProductType() == 'simple' || $item->getProductType() == 'configurable') {
							$prepare['items'][] = array (
								'item_id' 			=> $item->getId(),
								'order_id' 			=> $item->getOrderId(),
								'qty' 				=> $item->getQtyOrdered(),
								'sku' 				=> $item->getSku(),
								'name' 				=> $item->getName(),
								'additional_data' 	=> $item->getAdditionalData(),
								'price' 			=> $item->getPrice(),
								'discount' 			=> $item->getDiscountAmount()
							);
						//}
					}

					$prepare['payment']['cc_owner'] 				= $order->getPayment()->getCcOwner();
					$prepare['payment']['cc_type'] 					= $order->getPayment()->getCcType();
					$prepare['payment']['method'] 					= $order->getPayment()->getMethod();
					$prepare['payment']['additional_data'] 			= $order->getPayment()->getAdditionalData();
					$prepare['payment']['additional_information'] 	= $order->getPayment()->getAdditionalInformation();

					$result['orders'][] = $prepare;
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
     * Add comment to order
     *
     * @param string $orderIncrementId
     * @param string $status
     * @param string $comment
     * @param boolean $notify
     * @return array
     */
    public function addComment($comments)
    {
        $result = array();
     	foreach($comments as $comment) {
	        try {
	        	$prepare = array();

	        	if(!$comment->increment_id) {
	        		throw new Exception('Increment id não informado.');
	        	}

				$order = $this->_getOrder($comment->increment_id);

				$historyItem = $order->addStatusHistoryComment($comment->comment);
	    		$historyItem->setIsCustomerNotified($comment->notify)->save();
				
				$order->sendOrderUpdateEmail($comment->notify, $comment->comment);

				$prepare['success'] = true;
				$prepare['increment_id'] = $order->getIncrementId();
	        } catch (Exception $e) {
	            $prepare['success'] = false;
				$prepare['msg'] = 'ERROR: ' . $e->getMessage();
	        }

	        $result[] = $prepare;
	    }

        return $result;
    }

    /**
     * Invoice
     * @param  integer $incrementId 
     * @param  boolean] $sendEmail   
     * @param  string $captureType 
     * @return array
     */
	public function invoice($invoices) 
	{
		$result = array();

		foreach ($invoices as $dataInvoice) {		
			try {
	            $prepare = array();

	            $order = $this->_getOrder($dataInvoice->increment_id);

				if(!$order->canInvoice()) {
	                throw new Exception('Order cannot be invoiced');
	            }
	 
	            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

	            $invoice->setRequestedCaptureCase($this->_getCaptureType($dataInvoice->capture_type));
	            $invoice->register();

	            $invoice->getOrder()->setCustomerNoteNotify(false);          
	            $invoice->getOrder()->setIsInProcess(true);

	            $transactionSave = Mage::getModel('core/resource_transaction')
	                ->addObject($invoice)
	                ->addObject($invoice->getOrder());

	            $transactionSave->save();

	            if ($dataInvoice->send_email && !is_null($order->getCustomerEmail())) {
	        		$invoice->sendEmail(true);
	        		$invoice->setEmailSent(true);
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
	 * Shipment
	 * @return array
	 */
	public function shipment($shipments) 
	{
		$result = array();

		foreach ($shipments as $dataShipment) {
			try {
				$prepare = array();
				$order = $this->_getOrder($dataShipment->increment_id);

				if (!$order->getId()) {
	        		throw new Exception("Order does not exist, for the Shipment process to complete");
	    		}

	    		if (!$order->canShip()) {
	        		throw new Exception("Order cannot be shipped");
	    		}

	            $shipment = $order->prepareShipment();
	            
	            if(property_exists($dataShipment,'track_number')) {
		            $arrTracking = array(
		                'carrier_code' 	=> $order->getShippingCarrier()->getCarrierCode(),
		                'title' 		=> $order->getShippingCarrier()->getConfigData('title'),
		                'number' 		=> $dataShipment->track_number,
		                'softvar_url'   => $dataShipment->url
		            );
		 
		            $track = Mage::getModel('sales/order_shipment_track')->addData($arrTracking);
		            $shipment->addTrack($track);
		        }

	            $shipment->register();
	            $order->setIsInProcess(true);

	            $transactionSave = Mage::getModel('core/resource_transaction')
	                ->addObject($shipment)
	                ->addObject($shipment->getOrder())
	                ->save();

	            $emailSentStatus = $shipment->getData('email_sent');
	    		
	    		if (!is_null($order->getCustomerEmail()) && !$emailSentStatus && $dataShipment->send_email) {
	        		$shipment->sendEmail(true);
	        		$shipment->setEmailSent(true);
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
	 * Get orders with invoiced for import
	 * @param  integer $offset 
	 * @return array          
	 */
	public function import($offset = 1,$incrementIds = null)
	{
		$result = array('success' => false, 'orders' => array(), 'offset' => $offset);

		try {
			
			if(!$offset) {
				$offset = 1;
			}

			$orders = Mage::getModel('sales/order')->getCollection();
			$orders->addAttributeToSelect('*');
			$orders->addAttributeToFilter('total_invoiced', array('gt' => 0));
			$orders->addAttributeToFilter('state', 'processing');
			$orders->setPageSize(100);
			$orders->setCurPage($offset);

			if (!empty($incrementIds)) {
                $orders->addAttributeToFilter('increment_id', array('in' => $incrementIds));
            }
            
			$orders->setOrder('entity_id', 'DESC');
			$orders->getSelect()->where('softvar_imported IS NULL');

			$result['last_page'] = $orders->getLastPageNumber();

			if ($offset <= $result['last_page']) {
				foreach ($orders as $order) {
					$customer 	= Mage::getSingleton('customer/customer')->load($order->getCustomerId());
					$region 	= Mage::getModel('directory/region');

					$prepare 	= array();
					$prepare['increment_id'] 						= $order->getIncrementId();
					$prepare['state'] 								= $order->getState();
					$prepare['status'] 								= $order->getStatus();
					$prepare['coupon_code'] 						= $order->getCouponCode();
					$prepare['protected_code'] 						= $order->getProtectedCode();
					$prepare['created_at'] 							= $order->getCreatedAt();
					$prepare['updated_at'] 							= $order->getCreatedAt();
					$prepare['discount'] 							= $order->getDiscountAmount();
					$prepare['store_id'] 							= $order->getStoreId();
					$prepare['store_name'] 							= $order->getStoreName();
					$prepare['grand_total'] 						= $order->getGrandTotal();
					$prepare['subtotal'] 							= $order->getSubtotal();
					$prepare['tax_amount'] 							= $order->getTaxAmount();
					$prepare['customer']['id'] 						= $order->getCustomerId();
					$prepare['customer']['firstname'] 				= $order->getCustomerFirstname();
					$prepare['customer']['lastname'] 				= $order->getCustomerLastname();
					$prepare['customer']['email'] 					= $order->getCustomerEmail();
					$prepare['customer']['dob'] 					= $order->getCustomerDob();
					$prepare['customer']['taxvat'] 					= $order->getCustomerTaxvat();
					$prepare['customer']['customer_is_guest'] 		= $order->getCustomerIsGuest();
					
					$prepare['customer']['inscricao_estadual'] = null;
					if ($attributeInscricaoEstadual) {
						$prepare['customer']['inscricao_estadual']  = $customer->getData($attributeInscricaoEstadual);
					}

					$genderText = $customer->getAttribute('gender')->getSource()->getOptionText($customer->getGender());
					
					$prepare['customer']['customer_gender'] = null;
					if ($genderText) {
						$prepare['customer']['customer_gender'] = $genderText;
					}

					$prepare['shipping']['description'] 			= $order->getShippingMethod();
					$prepare['shipping']['method'] 					= $order->getShippingMethod();
					$prepare['shipping']['amount'] 					= $order->getShippingAmount();
					
					$prepare['shipping']['data_entrega']            = null;
					if ($attributeDataEntrega) {
						$prepare['shipping']['data_entrega']        = $attributeDataEntrega;
					}

					$prepare['billing_address'] = $this->_setAddress($order->getBillingAddress());
					
					if ($order->getIsVirtual()) {
						$prepare['shipping_address'] = $prepare['billing_address'];
					} else {
						$prepare['shipping_address'] = $this->_setAddress($order->getShippingAddress());
					}

					foreach ($order->getAllVisibleItems() as $item) {
						if($item->getProductType() == 'simple' || $item->getProductType() == 'configurable') {
							$prepare['items'][] = array (
								'item_id' 			=> $item->getId(),
								'order_id' 			=> $item->getOrderId(),
								'qty' 				=> $item->getQtyOrdered(),
								'sku' 				=> $item->getSku(),
								'name' 				=> $item->getName(),
								'additional_data' 	=> $item->getAdditionalData(),
								'price' 			=> $item->getPrice(),
								'discount' 			=> $item->getDiscountAmount()
							);
						}
					}

					$prepare['payment']['cc_owner'] 				= $order->getPayment()->getCcOwner();
					$prepare['payment']['cc_type'] 					= $order->getPayment()->getCcType();
					$prepare['payment']['method'] 					= $order->getPayment()->getMethod();
					$prepare['payment']['additional_data'] 			= $order->getPayment()->getAdditionalData();
					$prepare['payment']['additional_information'] 	= $order->getPayment()->getAdditionalInformation();
                    if(is_array($prepare['payment']['additional_information'])) {
                        $prepare['payment']['additional_information'] = implode("\\n",$prepare['payment']['additional_information']);
                    }
					$result['orders'][] = $prepare;
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
	 * Set order as imported
	 * @param integer $incrementIds
	 * @return  array
	 */
	public function setImport($incrementIds)
	{
		$result = array();

		foreach($incrementIds as $incrementId) {
			try {
				$prepare = array();
				$order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
				$order->setSoftvarImported(1);
				$order->save();

				$prepare['increment_id'] = $order->getIncrementId();
				$prepare['success'] 	 = true;
			} catch (Exception $e) {
				$prepare['success'] = false;
				$prepare['msg'] = 'ERROR: ' . $e->getMessage();
			}

			$result[] = $prepare;
		}

		return $result;
	}


	/**
	 * List all shipping methods actives
	 * @return array
	 */
	public function listShippingMethods()
	{
		$result = array();

		$methods = Mage::getSingleton('shipping/config')->getActiveCarriers();

    	try {
    		if (!$methods) {
    			throw new Exception('Nenhum forma de entrega encontrada ou estão todas desabilitadas.');
    		}

		    foreach($methods as $_code => $_method)
		    {
		        $result[] = array('identication' => $_code, 'description' => Mage::getStoreConfig("carriers/$_code/title"));
		    }

		    if($isMultiSelect)
		    {
		        array_unshift($result, array('identication'=> '', 'description'=> Mage::helper('adminhtml')->__('--Please Select--')));
		    }

		} catch (Exception $e) {
			$result['success'] 	= false;
			$result['msg'] 		= "ERROR: ".$e->getMessage();
		}

	    return $result;
	}

	/**
	 * List all payment methods actives
	 * @return array
	 */
	public function listPaymentMethods()
	{
		$result = array();

		try {
			$payments = Mage::getSingleton('payment/config')->getActiveMethods();
			if(!$payments) {
				throw new Exception('Nenhuma forma de pagamento encontrada ou estão todas desabilitadas.');
			}

			foreach ($payments as $paymentCode=>$paymentModel) {
				$result[] = array('description'   	=> Mage::getStoreConfig('payment/'.$paymentCode.'/title'), 'identication' 		=> $paymentCode);
			}
		} catch (Exception $e) {
			$result['success'] = false;
			$result['msg']	= "ERROR: ".$e->getMessage();
		}

		return $result;
	}

	/**
	 * Define address
	 * @param object $address
	 */
	private function _setAddress($address)
	{
		$region 						= Mage::getModel('directory/region');
		$attributeRua 					= Mage::helper('softvar')->getConfig('softvar_street','rua');
		$attributeNumero 				= Mage::helper('softvar')->getConfig('softvar_street','numero');
		$attributeComplemento 			= Mage::helper('softvar')->getConfig('softvar_street','complemento');
		$attributeBairro 				= Mage::helper('softvar')->getConfig('softvar_street','bairro');
		
		$prepare = array();
		$prepare['region_id'] 		= $address->getRegionId();
		$prepare['postcode'] 		= $address->getPostcode();
		$prepare['firstname'] 		= $address->getFirstname();
		$prepare['lastname'] 		= $address->getLastname();
			
		if($address->getStreet()) {
			$prepare['rua']	= null;
			if ($attributeRua) {
				$prepare['rua']	= $address->getStreet($attributeRua);
			}

			$prepare['numero'] = null;
			if ($attributeNumero) {
				$prepare['numero']	= $address->getStreet($attributeNumero);
			}

			$prepare['complemento'] = null;
			if ($attributeComplemento) {
				$prepare['complemento']	= $address->getStreet($attributeComplemento);
			}

			$prepare['bairro'] = null;
			if ($attributeBairro) {
				$prepare['bairro']	= $address->getStreet($attributeBairro);
			}
		}

		$prepare['city'] 			= $address->getCity();
		$prepare['email'] 			= $address->getEmail();
		$prepare['telephone'] 		= $address->getTelephone();
		$prepare['country_id'] 		= $address->getCountryId();
		
		if ($address->getRegionId()) {
			$region->load($address->getRegionId());
			
			if ($region->getCode()) {
				$prepare['region']	= $region->getCode();							
			}
		}

		return $prepare;
	}

	/**
	 * Capture Type
	 * @param  string $type 
	 * @return string
	 */
	private function _getCaptureType($type) 
	{
		switch ($type) {
			case 'online':
				return Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE;
				break;

			case 'not_capture':
				return Mage_Sales_Model_Order_Invoice::NOT_CAPTURE;
				break;
			default:
				return Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE;
		}
	}

	/**
	 * Load Order By Increment Id
	 * @param  integer $incrementId
	 * @return object
	 */
	private function _getOrder($incrementId) 
	{
		return Mage::getModel('sales/order')->load($incrementId,'increment_id');
	}
}
