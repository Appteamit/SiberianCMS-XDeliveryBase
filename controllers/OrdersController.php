<?php

/**
 * Class Xdelivery_OrdersController
 */
class Xdelivery_OrdersController extends Application_Controller_Default
{

    /**
     *
     */
    public function listAction()
    {
    	$this->loadPartials();
    }

        /**
     *
     */
    public function transactionsAction()
    {
    	$this->loadPartials();
    }

    /**
     * fetch all orders
     */
     public function findAllAction() {
        
        try {
            $request = $this->getRequest();
            $limit = $request->getParam("perPage", 25);
            $offset = $request->getParam("offset", 0);
            $sorts = $request->getParam("sorts", []);
            $queries = $request->getParam("queries", []);
            $value_id = (new Xdelivery_Model_Xdelivery())->getCurrentValueId();
            $settingModel = (new Xdelivery_Model_Settings())->find(['value_id' => $value_id ]);
            $settings = $settingModel->getData();
            $search = null;
            if (array_key_exists("search", $queries)) {
                $search = $queries["search"];
            }
            $filter = [];
            if (array_key_exists("date_type", $queries) && (array_key_exists("from", $queries) || array_key_exists("to", $queries))) {
                $filter['date_type'] = $queries["date_type"];
                if($filter['date_type'] == 'booking_slot'){
                    $filter['from'] = strtotime($queries["from"]);
                    $filter['to'] = strtotime($queries["to"]);
                }else{
                    $filter['from'] = date('Y-m-d 00:00:00', strtotime($queries["from"]));
                    $filter['to'] = date('Y-m-d 23:59:59', strtotime($queries["to"]));
                }
                
            }
            
            if (array_key_exists("store_id", $queries)) {
                $store_id = $queries["store_id"];
            }
            if (array_key_exists("status", $queries)) {
                $status = $queries["status"];
            }

            $params = [
                "limit" => $limit,
                "offset" => $offset,
                "sorts" => $sorts,
                "search" => $search,
                "filter" => $filter,
                "store_id" => $store_id,
                "status" => $status
            ]; 
            $status = (new Xdelivery_Model_Orders)->getStatus();
            $value_id = (new Xdelivery_Model_Xdelivery())->getCurrentValueId();
            $application = $this->getApplication();
            
            $orders = (new Xdelivery_Model_Orders())
                ->findByValueId($value_id, $params);

            $countAll = (new Xdelivery_Model_Orders())->countAllForApp($value_id);
            $countFiltered =   (new Xdelivery_Model_Orders())->countAllForApp($value_id, $params);

            $ordersJson = [];
            foreach ($orders as $order) {
                $data = $order->getData();
                $data['order_status'] = $status[$data['order_status']];
                $data['order_status']  = p__('xdelivery', ucfirst($data['order_status']));
                $data['currency'] = $this->getApplication()->getCurrency();
                $data['total_amount'] = $this->getApplication()->getCurrency().' '.$data['total_amount'];
                $data['delivery_method'] = $data['delivery_method'] == 'delivery' ? p__('xdelivery', 'Home  Delivery') : p__('xdelivery', 'Pick-up');
                $data['payment_status'] = $data['payment_status'] == 'success' ? p__('xdelivery', 'Paid') : p__('xdelivery', 'Unpaid');

                $data['delivery_slot'] = date($settings['date_format'], $data['delivery_date']).'<br>'.date($settings['time_format'], $data['delivery_time']);
                $data['created_at']=  date($settings['date_format']. ' '. $settings['time_format'], strtotime($data['created_at']));
                // $data['delivery_date'] =  date('jS M, Y', $data['delivery_date']);
                $data['delivery_date'] =  date($settings['date_format'], $data['delivery_date']);
                $data['delivery_time'] =  date($settings['time_format'], $data['delivery_time']);
                $data['is_return_request'] = (integer) $data['is_return_request'];
                
                if($data['is_return_request'] && $data['order_status'] != 'refunded'){
                    if($data['is_return_request'] == 1){
                        $data['order_status'] =  p__('xdelivery', 'Return request');
                    }
                    if($data['is_return_request'] == 2){
                        $data['order_status'] =  p__('xdelivery', 'Return processing');
                    }
                    if($data['is_return_request'] == 3){
                        $data['order_status'] =  p__('xdelivery', 'Return Rejected');
                    }
                }

                $ordersJson[] = $data;
            }

            $payload = [
                "records" => $ordersJson,
                "queryRecordCount" => $countFiltered[0],
                "totalRecordCount" => $countAll[0],
                "settings" => $settings,
                "params" => $params
            ];

        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

  /**
     * fetch all transactions
     */
     public function findAllTransactionsAction() {
        
        try {
            $request = $this->getRequest();
            $limit = $request->getParam("perPage", 25);
            $offset = $request->getParam("offset", 0);
            $sorts = $request->getParam("sorts", []);
            $queries = $request->getParam("queries", []);
            
            $search = null;
            if (array_key_exists("search", $queries)) {
                $search = $queries["search"];
            }
            $filter = [];
            $params = [
                "limit" => $limit,
                "offset" => $offset,
                "sorts" => $sorts,
                "search" => $search,
                "filter" => $filter
            ]; 
            $status = (new Xdelivery_Model_Orders)->getStatus();
            $value_id = (new Xdelivery_Model_Xdelivery())->getCurrentValueId();
            $application = $this->getApplication();
            
            $orders = (new Xdelivery_Model_OrderTransactions())
                ->findByValueId($value_id, $params);

            $countAll = (new Xdelivery_Model_OrderTransactions())->countAllForApp($value_id);
            $countFiltered =   (new Xdelivery_Model_OrderTransactions())->countAllForApp($value_id, $params);

            $ordersJson = [];
            foreach ($orders as $order) {
                $data = $order->getData();
                $data['order_status'] = $status[$data['order_status']];
                $data['order_status']  = p__('xdelivery', ucfirst($data['order_status']));
                $data['currency'] = $this->getApplication()->getCurrency();
                $data['total_amount'] = $this->getApplication()->getCurrency().' '.$data['total_amount'];
                $data['payment_status'] =  p__('xdelivery', ucfirst($data['payment_status']));

               $ordersJson[] = $data;
            }

            $payload = [
                "records" => $ordersJson,
                "queryRecordCount" => $countFiltered[0],
                "totalRecordCount" => $countAll[0],
                "params" => $params
            ];

        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function detailsAction()
    {

        $model = (new Xdelivery_Model_Orders());  
        if ($order_id = $this->getRequest()->getParam('id')) { 
                $order = $model->findOrderById($order_id); 
                if (empty($order)) {
                    $this->getSession()->addError(p__("xdelivery",  "This order does not exist."));
                    $this->redirect('xdelivery/orders/list');
                }

                $order['order_date']=  date('jS M, Y g:i A', strtotime($order['created_at']));
                $order['currency'] = $this->getApplication()->getCurrency();
               
                $order['delivery_date']=  date('jS M, Y', $order['delivery_date']);
                $order['delivery_time']=  date('g:i A', $order['delivery_time']);
                $order['is_return_request'] = (integer) $order['is_return_request'];
                $items = (new Xdelivery_Model_OrderItems())->findItemByOrderId($order_id);
                
                $itemsJson = [];
                foreach ($items as $key => $value) {
                    $value['choices'] = json_decode($value['choices']);
                    $value['varients'] = (new Xdelivery_Model_ProductVariant())->getProductVariantValues($value['product_id']);
                    $itemsJson[] = $value;
                }
                $order['items'] = $itemsJson;
                
                $txnModel = (new Xdelivery_Model_OrderTransactions())
                    ->findAll(['order_id' => $order_id])->toArray();
            
            $this->loadPartials();
            $this->getLayout()->getPartial('content')->setOrder($order)->setTxn($txnModel);
        }      
    }

    public function statusOrderUpdateAction(){
        if ($order_id = $this->getRequest()->getParam('id')) {
            $status = $this->getRequest()->getParam('status');
                try {
 
                   $modal = (new Xdelivery_Model_Orders())
                        ->find(['id' => $order_id]);
                    $modal->setStatus($status);
                    $modal->save();
                 
                    $modelOrder = (new Xdelivery_Model_Orders()); 
                    $order = $modelOrder->findOrderById($order_id); 

                    $settingModel = (new Xdelivery_Model_Settings())->find(['value_id' => $order['value_id'] ]);
                    $settings = $settingModel->getData();
                    $currency = $this->getApplication()->getCurrency();

                    if($order['payment_status'] == 'success' && ($order['order_status'] == 'canceled' || $order['order_status'] == 'refunded' || $order['order_status'] == 'failed')){
                        $modalTxn = (new Xdelivery_Model_OrderTransactions());
                        $modalTxn->setOrderId($order_id);
                        $modalTxn->setStatus('refunded');
                        $modalTxn->setAmount($order['total_amount']);
                        $modalTxn->setPaymentMethodId($order['payment_method_id']);
                        $modalTxn->setPaymentMethod($order['payment_method']);
                        $modalTxn->save();
                    
                        $this->cancelOrderWalletFuncations($order);

                        $this->_cancelPrint($order_id);
                    }

                    $order['order_date']=  date('jS M, Y g:i A', strtotime($order['created_at']));
                    $order['today_date']=  date('jS M, Y g:i A');
                    $order['currency'] = $this->getApplication()->getCurrency() ;
                    $order['delivery_date']=  date('jS M, Y', $order['delivery_date']);
                    $order['delivery_time']=  date('g:i A', $order['delivery_time']);
                    $items = (new Xdelivery_Model_OrderItems())->findAll(['order_id' => $order_id])->toArray();
                    $itemsJson = [];
                    foreach ($items as $key => $value) {
                        $value['choices'] = json_decode($value['choices']);
                        $value['price_with_currency'] = Xdelivery_Model_Utility::displayPrice($value['price'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                        $value['choice_price_with_currency'] = Xdelivery_Model_Utility::displayPrice($value['choice_price'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                        $value['total_with_currency'] = Xdelivery_Model_Utility::displayPrice($value['total'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                        if($order['order_status'] == 'canceled' || $order['order_status'] == 'refunded' || $order['order_status'] == 'failed'){
                            $this->updateProductInventory($value['product_id'], $value['qty'], 'increase');
                        }
                        $itemsJson[] = $value;
                    }
                    $order['items'] = $itemsJson;

                    $statusList = (new Xdelivery_Model_Orders)->getStatus();
                    $order['order_status'] = $statusList[$order['order_status']];
                    $order['order_status']  = p__('xdelivery', ucfirst($order['order_status']));

                      /*with currency */
                    $order['sub_amount_with_currency'] = Xdelivery_Model_Utility::displayPrice($order['sub_amount'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                    $order['total_tax_with_currency'] = Xdelivery_Model_Utility::displayPrice($order['total_tax'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                    $order['total_amount_with_currency'] = Xdelivery_Model_Utility::displayPrice($order['total_amount'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                    $order['delivery_cost_with_currency'] = Xdelivery_Model_Utility::displayPrice($order['delivery_cost'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);

                    $mailParams = [];
                    $mailParams['order'] = $order;
                    $mailParams['sender_email'] = $order['store_email'];
                    $mailParams['sender_name'] = $order['store_name'];
                    $mailParams['email'] = $order['customer_email'];
                    $mailParams['order'] = $order;
                    $mailParams['subject'] = p__('xdelivery', '%s Order #%s is %s!', $this->getApplication()->getName(), $mailParams['order']['order_number'], $mailParams['order']['order_status']);
                    $mailParams['headline'] = p__('xdelivery', 'Your order is %s!',  $status);
                    $mailParams['message'] = p__('xdelivery', 'Thank you for placing your order with %s. This email is to confirm your order is %s successfully.', $this->getApplication()->getName(), $status);
                    
                    $this->_sendCustomerOrderEmail($mailParams);

                if(!empty($settings['order_status_push']) && $settings['order_status_push']) {
                    //Send Push notifications
                    $param = [];      
                    $param['app_id'] = $this->getApplication()->getId();
                    $param['value_id'] = $order['value_id'];
                    $param['title'] = p__('xdelivery', 'Order %s!',  $status);
                    $param['text'] =  p__('xdelivery', 'Your %s order #%s has been %s!', $this->getApplication()->getName(), $mailParams['order']['order_number'], $mailParams['order']['order_status']);
                    $param['receiver_id'] = $order['customer_id'];
                    (new Xdelivery_Model_Push())->send($param);
                }
                if(!empty($settings['whatsender_key']) && !empty($order['customer_phone'])){
                    //Whatsapp sent              
                   $wParams = ['phone' => $order['customer_phone'], 'message' => $mailParams['subject'],  'sender' => $settings['whatsender_sender']];
                   
                    $whatsapp_status = (new Xdelivery_Model_Whatsender)->sent($settings['whatsender_key'], $wParams);
                }

                $payload = [
                        'success' => true,
                        'message' => p__('xdelivery', 'Update Status successfully'),
                    ];

                    } catch (\Exception $e) {
                    $payload = [
                        'error' => true,
                        'message' => $e->getMessage(),
                    ];
                }

                $this->_sendJson($payload);
            }
    }

    public function returnOrderUpdateAction(){
        if ($order_id = $this->getRequest()->getParam('id')) {
            $status = $this->getRequest()->getParam('status');
             
                if($status == 'reject'){
                    $statusValue = 3;
                     $status = p__('xdelivery', 'Return request rejected');
                }

                if($status == 'accept'){
                    $statusValue = 2;
                    $status = p__('xdelivery', 'Return request accepted');
                }
                try {
 
                   $modal = (new Xdelivery_Model_Orders())
                        ->find(['id' => $order_id]);
                    $modal->setIsReturnRequest($statusValue);
                    $modal->save();
                 
                    $modelOrder = (new Xdelivery_Model_Orders()); 
                    $order = $modelOrder->findOrderById($order_id); 

                    $settingModel = (new Xdelivery_Model_Settings())->find(['value_id' => $order['value_id'] ]);
                    $settings = $settingModel->getData();
                    $currency = $this->getApplication()->getCurrency();

                    $order['order_date']=  date('jS M, Y g:i A', strtotime($order['created_at']));
                    $order['today_date']=  date('jS M, Y g:i A');
                    $order['currency'] = $this->getApplication()->getCurrency() ;
                    $order['delivery_date']=  date('jS M, Y', $order['delivery_date']);
                    $order['delivery_time']=  date('g:i A', $order['delivery_time']);
                    $items = (new Xdelivery_Model_OrderItems())->findAll(['order_id' => $order_id])->toArray();
                    $itemsJson = [];
                    foreach ($items as $key => $value) {
                        $value['choices'] = json_decode($value['choices']);
                        $value['price_with_currency'] = Xdelivery_Model_Utility::displayPrice($value['price'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                        $value['choice_price_with_currency'] = Xdelivery_Model_Utility::displayPrice($value['choice_price'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                        $value['total_with_currency'] = Xdelivery_Model_Utility::displayPrice($value['total'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                        
                        if($order['order_status'] == 'canceled' || $order['order_status'] == 'refunded' || $order['order_status'] == 'failed'){
                            $this->updateProductInventory($value['product_id'], $value['qty'], 'increase');
                        }
                        $itemsJson[] = $value;
                    }
                    $order['items'] = $itemsJson;

                      /*with currency */
                    $order['sub_amount_with_currency'] = Xdelivery_Model_Utility::displayPrice($order['sub_amount'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                    $order['total_tax_with_currency'] = Xdelivery_Model_Utility::displayPrice($order['total_tax'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                    $order['total_amount_with_currency'] = Xdelivery_Model_Utility::displayPrice($order['total_amount'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);
                    $order['delivery_cost_with_currency'] = Xdelivery_Model_Utility::displayPrice($order['delivery_cost'], $currency, $settings['number_of_decimals'], $settings['decimal_separator'], $settings['thousand_separator'], $settings['currency_position']);

                    $statusList = (new Xdelivery_Model_Orders)->getStatus();
                    $order['order_status'] = $statusList[$order['order_status']];
                    $order['order_status']  = p__('xdelivery', ucfirst($order['order_status']));

                    $mailParams = [];
                    $mailParams['order'] = $order;
                    $mailParams['sender_email'] = $order['store_email'];
                    $mailParams['sender_name'] = $order['store_name'];
                    $mailParams['email'] = $order['customer_email'];
                    $mailParams['order'] = $order;
                    $mailParams['subject'] = p__('xdelivery', '%s Order #%s %s!', $this->getApplication()->getName(), $mailParams['order']['order_number'], $mailParams['order']['order_status']);
                    $mailParams['headline'] = p__('xdelivery', 'Your order %s!',  $status);
                    $mailParams['message'] = p__('xdelivery', 'Thank you for placing your order with %s. This email is to confirm your order %s successfully.', $this->getApplication()->getName(), $status);
                    
                    $this->_sendCustomerOrderEmail($mailParams);

                   $payload = [
                        'success' => true,
                        'message' => p__('xdelivery', 'Update Status successfully'),
                    ];

                    } catch (\Exception $e) {
                    $payload = [
                        'error' => true,
                        'message' => $e->getMessage(),
                    ];
                }

                $this->_sendJson($payload);
            }
    }

    public function statusTransactionUpdateAction(){
        if ($order_id = $this->getRequest()->getParam('id')) {
            $status = $this->getRequest()->getParam('status');
                try {
 
                   $modal = (new Xdelivery_Model_OrderTransactions())
                        ->find(['id' => $order_id]);
                    $modal->setStatus($status);
                    $modal->save();
                       
                    $payload = [
                        'success' => true,
                        'message' => p__('xdelivery', 'Update Status successfully'),
                    ];

                    } catch (\Exception $e) {
                    $payload = [
                        'error' => true,
                        'message' => $e->getMessage(),
                    ];
                }

                $this->_sendJson($payload);
            }
    }

    private function _cancelPrint($sib_order_id) {

        if(class_exists("Migaprintv2_Model_Config")) {

            $application = $this->getApplication();
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => __get('main_domain')."/migaprintv2/services/cancelorder",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => array('app_id' => $application->getId(),
                    'key' => $application->getKey(),
                    'commerce' => 'Xdelivery','sib_order_id' => $sib_order_id),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
        }
    }

    private function _sendCustomerOrderEmail($param){
        if(empty($param)){
            return false;
        }

        $config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $sender = $config->getOption('sendermail');
        $layout = $this->getLayout()->loadEmail('xdelivery', 'xdelivery_new_order');

        $layout->getPartial('content_email')
            ->setEmail($param['sender_email'])
            ->setHeadline($param['headline'])
            ->setMessage($param['message'])
            ->setOrder($param['order'])
            ->setApp($this->getApplication()->getName())->setIcon($this->getApplication()->getIcon());

        $content = $layout->render();
        $mail = new Siberian_Mail();
        $mail->_is_default_mailer = false;
        $mail->setBodyHtml($content);
        $mail->setFrom($param['sender_email'], $this->getApplication()->getName());
        $mail->_sender_name = $param['sender_name'].' via '.$this->getApplication()->getName();
        $mail->addTo($param['email'], "");
        $mail->setSubject($param['subject']);

        $mail->send();

    }

    /**
     * @param @array $order
     * @return bool
     * @throws Exception
     * @throws Zend_Session_Exception
     */
    private function cancelOrderWalletFuncations($param){

        $value_id = (new Ewallet_Model_Ewallet())->getCurrentValueId();
        $current_value_id = (new Xdelivery_Model_Xdelivery())->getCurrentValueId();
                $appId = $this->getApplication()->getId();
                $receiverId = $param['customer_id'];               
                $senderId = 0;
                $date = new Siberian_Date();
                $last_updated= $date->toString("yyyy-MM-dd HH:mm:ss");
              
               /* receiver  customer with debit amount */
                $receiverCustomerWallet = (new Ewallet_Model_Wallet())
                                ->find(['customer_id' => $receiverId ]);
                $receiverCustomerWalletId = $receiverCustomerWallet->getWalletId();
                $receiverCustomerCurrentBalance = number_format( $receiverCustomerWallet->getBalance() , 2,'.','');
                $receiverNewBalance = $receiverCustomerCurrentBalance + $param['total_amount'];
                $senderTransacrtionId = $senderCustomerWalletId.''.time();
                $senderWalletHistory = (new Ewallet_Model_History())
                            ->setWalletId($receiverCustomerWalletId)
                            ->setValueId($value_id)
                            ->setCustomerId($receiverId)
                            ->setAppId($appId)                          
                            ->setAmount($param['total_amount'])
                            ->setPreviousBalance($receiverCustomerCurrentBalance)
                            ->setNewBalance($receiverNewBalance)
                            ->setPaymentType('credit')
                            ->setPaymentMode('transfer')
                            ->setPaymentMethod('wallet')
                            ->setModuleValueId($current_value_id)
                            ->setStatus('success')
                            ->setTransacrtionId($senderTransacrtionId)
                            ->setShotRemark('Refund Order #'.$param['order_number'])
                            ->setOrderId($param['order_id'])
                            ->setLastUpdated($last_updated)
                            ->setToCustomerId(0)
                            ->setToStoreId($senderId)
                            ->setRemark('Refund Order #'.$param['order_number'])
                            ->setMethod('payToModule')
                            ->save();

                $receiverWallet = (new Ewallet_Model_Wallet())
                    ->find(['customer_id' => $receiverId ])
                    ->setBalance($receiverNewBalance)
                    ->setLastUpdated($last_updated)
                    ->save();
        return true;
    }

    public function updateProductInventory($product_id, $qty, $type = 'increase'){

        $product = (new Xdelivery_Model_Products());
        $product->find($product_id);

        if ($product->getId()) {
            if($product->getManageStock()){
                $qty = (integer) $qty;
                $product_qty = (integer) $product->getQty();
                
                if($type == 'increase'){
                    $new_qty = $product_qty + $qty;
                }
                if($type == 'descrease'){
                    $new_qty = $product_qty - $qty;
                }

                $product->setQty($new_qty);
                $product->save();
            }
        }
    }


   //function for delete product  
    public function printAction() {
      try {

            if(!class_exists("Migaprintv2_Model_Config")) {
                throw new Exception(__('Print features is not available, Please contact to administrator.'));
            }
            
            $request = $this->getRequest();
            $order_id = $request->getParam("id", null);
            $application = $this->getApplication();

            $status = (new Xdelivery_Model_Orders)->getStatus();
            $modelOrder = (new Xdelivery_Model_Orders()); 
            $order = $modelOrder->findOrderById($order_id);
            $items = (new Xdelivery_Model_OrderItems())->findAll(['order_id' => $order_id])->toArray();
             
            $postvars = array('app_id' => $application->getId(),
                'key' => $application->getKey(),
                'commerce' => 'Xdelivery',
                'order[store_id]' => $order['store_id'],
                'order[customer_id]' => $order['customer_id'],
                'order[sib_order_id]' => $order['order_id'],
                'order[order_number]' => $order['order_number'],
                'order[order_payment_method]' => $order['label_name'],
                'order[order_delivery_method]' => $order['delivery_method'],
                'order[order_customer_firstname]' => $order['customer_firstname'],
                'order[order_customer_lastname]' => $order['customer_lastname'],
                'order[order_customer_email]' => $order['customer_email'],
                'order[order_customer_street]' => $order['customer_street'],
                'order[order_customer_postcode]' => $order['customer_postcode'],
                'order[order_customer_city]' => $order['customer_city'],
                'order[order_customer_phone]' => $order['customer_phone'],
                'order[order_deliverytime]' =>  date('Y-m-d', $order['delivery_date']).' '.date(' H:i:s', $order['delivery_time']),
                'order[order_delivery_note]' => $order['notes'],
                'order[order_total]' => $order['total_amount'],
                'order[order_paid_amount]' => $order['total_amount'],
                'order[order_date]' => $order['created_at'],
                'order[order_module]' => '3',
                'order[order_commerce]' => 'Xdelivery',
                'order[order_total]' => $order['total_amount'],
                'order[order_status]' => $status[$order['order_status']]
            );

            foreach ($items as $key => $value) {
                $postvars['products[sib_product_id]['.$key.']'] = $value['product_id'];
                $postvars['products[product_name]['.$key.']'] = $value['name'];
                $postvars['products[product_quantity]['.$key.']'] = $value['qty'];
                $postvars['products[product_total]['.$key.']']  = $value['price_incl_tax'];
 
                $optionsNew = [];
                foreach (json_decode($value['choices']) as $ckey => $cvalue) {
                    $cData = [  'option_id' => $cvalue->id,
                                'name' => $cvalue->name, 
                                'base_price' => $cvalue->price,
                                'price' =>  $cvalue->price,
                                'price_incl_tax' => $cvalue->price,
                                'qty' => $value['qty']
                            ];
                    $optionsNew[] = $cData;
                }

                $varients = (new Xdelivery_Model_ProductVariant())->getProductVariantValues($value['product_id']);

                $choiceNew = [];
                foreach ($varients as $vKey => $vValue) {
                    $vData = ['format_id' => $order_id.''.$value['product_id'],
                              'format_name' => $vValue['attribute_name'].': '.$vValue['value_name'],
                          ];
                    $choiceNew[] = $vData;
                }

                $postvars['products[product_choices]['.$key.']']  = serialize($choiceNew);
                $postvars['products[product_options]['.$key.']']  = serialize($optionsNew);
                $postvars['products[product_format]['.$key.']']  = 'a:0:{}';
            }
         
           // Send POST query via cURL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->getRequest()->getBaseUrl()."/migaprintv2/services/neworder");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_ENCODING, '');
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
           // curl_setopt($curl, CURLOPT_TIMEOUT, 50);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postvars);
            $response = curl_exec($curl);
            if(curl_errno($curl)){
                echo 'Request Error:' . curl_error($curl);die;
            }

            curl_close($curl);
            
            $response = json_decode($response, true);
           
            if(!empty($response['error'])){
                $payload = [
                    'error' => true,
                    'message' => p__('xdelivery', $response['error']),
                    'postvars' => $postvars,
                    'response' => $response                    
                ];
            }else{ 
                 
                $payload = [
                    'success' => true,
                    'message' => p__('xdelivery', 'Successfully printed
                    '),
                    'postvars' => $postvars,
                    'response' => $response,
               ];
            } 

        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }



    public function emailprintAction() {
        $order_id = 1;

        $order = (new Xdelivery_Model_Orders())->findOrderById($order_id);
        $layout = $this->getLayout()->loadEmail('xdelivery', 'xdelivery_new_order');

        $order['order_date']=  date('jS M, Y g:i A', strtotime($order['created_at']));
        $order['today_date']=  date('jS M, Y g:i A');
        $order['currency'] = $this->getApplication()->getCurrency() ;
        $order['delivery_date']=  date('jS M, Y', $order['delivery_date']);
        $order['delivery_time']=  date('g:i A', $order['delivery_time']);


        $items = (new Xdelivery_Model_OrderItems())->findAll(['order_id' => $order_id])->toArray();
        $itemsJson = [];
        foreach ($items as $key => $value) {
            $value['choices'] = json_decode($value['choices']);
            $itemsJson[] = $value;
        }

        $order['items'] = $itemsJson;
        $status = (new Xdelivery_Model_Orders)->getStatus();
        $order['order_status'] = $status[$order['order_status']];
        $order['order_status']  = p__('xdelivery', ucfirst($order['order_status']));

        $mailParams = [];
        $mailParams['order'] = $order;
        $mailParams['sender_email'] = $order['store_email'];
        $mailParams['sender_name'] = $order['store_name'];
        $mailParams['email'] = $order['customer_email'];
        $mailParams['order'] = $order;
        $mailParams['subject'] = p__('xdelivery', '%s Order #%s is %s!', $this->getApplication()->getName(), $mailParams['order']['order_number'], $mailParams['order']['order_status']);
        $mailParams['headline'] = p__('xdelivery', 'Your order is %s!',  $mailParams['order']['order_status']);
        $mailParams['message'] = p__('xdelivery', 'This email is to confirm your order is %s successfully.', $this->getApplication()->getName(), $mailParams['order']['order_status']);


        $layout->getPartial('content_email')
            ->setEmail($mailParams['sender_email'])
            ->setHeadline($mailParams['headline'])
            ->setMessage($mailParams['message'])
            ->setOrder($mailParams['order'])
            ->setApp($this->getApplication()->getName())->setIcon($this->getApplication()->getIcon());

        $content = $layout->render();

        echo "<pre>";
        print_r($order);



        print_r($content);
        die;
    }

    public function additionalsaveAction(){
         if($param = $this->getRequest()->getPost()) {     
              
           try {  
         
            $model = (new Xdelivery_Model_Orders())
                ->find(['id' => $param['order_id']])
                ->setAdminRemark($param['admin_remark'])
                ->setTrackingType($param['tracking_type'])
                ->setTrackingNumberUrl($param['tracking_number_url']);
            $model->save();
            
            $this->getSession()->addSuccess(p__('xdelivery', "Info successfully saved"));
               
           
            $html = [
                "success" => 1
            ];

          }catch(Exception $e) {
                $html = [
                    "error" => 1,
                    "message" => $e->getMessage(),
                    'message_button' => 1,
                    'message_loader' => 1
                ];
            }

            $this->getResponse()->setBody(Zend_Json::encode($html))->sendResponse();
            die;

        }
    }


}