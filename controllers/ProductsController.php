<?php

/**
 * Class Xdelivery_ProductsController
 */
class Xdelivery_ProductsController extends Application_Controller_Default
{

    /**
     *load products
     */
    public function listAction()
    {
    	$this->loadPartials();
    }

    /**
     *add products
     */
    public function addAction()
    {
    	$this->loadPartials();
    }

    /**
     *add products variants
     */
    public function variantsAction()
    {
        $product = (new Xdelivery_Model_Products());  
        if ($product_id = $this->getRequest()->getParam('id')) {
            
            $product->find($product_id); 
            if (!$product->getId()) {
                   
                    $this->getSession()->addError(p__("xdelivery",  "This product does not exist."));
                    $this->redirect('xdelivery/products/list');
            }

            $products = $product->getData();
            $productAttribute = (new Xdelivery_Model_ProductAttribute())->getProductAttribute($products['parent_id']);

            $productImage = (new Xdelivery_Model_ProductImages())->find(['product_id' => $product_id, 'is_base' => 1]);
            $products['image'] = $productImage->getProductImage();
       }
        
        $this->loadPartials();
        $this->getLayout()->getPartial('content')->setProduct($products)->setProductAttributes($productAttribute);
    }

    //function for create a new variant product  
    public function createVariantAction() {
      
      try {
        
        $product = (new Xdelivery_Model_Products());  
        if ($product_id = $this->getRequest()->getParam('id')) {
            $product->find($product_id); 
            if (!$product->getId()) {
                    $this->getSession()->addError(p__("xdelivery",  "This product does not exist."));
                    $this->redirect('xdelivery/products/list');
            }

           $storeModel = (new Xdelivery_Model_Store())->find(['value_id' =>  $product->getValueId()]);
           $store_id = $storeModel->getId();
            
            $modelProduct = (new Xdelivery_Model_Products())
                    ->setParentId($product_id)
                    ->setStoreId($store_id)
                    ->setValueId($product->getValueId())
                    ->setProductName('Copy - '.$product->getProductName())
                    ->setIsActive(0)
                    ->save();

            $new_product_id = $modelProduct->getId();

            $payload = [
                'success' => true,
                'product_id' =>  $new_product_id,
                'message' => p__('xdelivery', 'Successfully Create a variant'),
            ];

        }else{
            $payload = [
                'error' => true,
                'message' => p__('xdelivery', 'Product id must required'),
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

    /**
     *Edit products
     */
    public function editAction()
    {
        $product = (new Xdelivery_Model_Products());  
        if ($product_id = $this->getRequest()->getParam('id')) {
            
            $product->find($product_id); 
            if (!$product->getId()) {
                     $this->getSession()->addError(p__("xdelivery",  "This product does not exist."));
                    $this->redirect('xdelivery/products/list');
            }

            $products = $product->getData();

            $productCategory = (new Xdelivery_Model_ProductCategories())->findAll(['product_id' => $product_id])->toArray();
            
            $categories = [];
            foreach ($productCategory as $key => $value) {
                $categories[] = $value['category_id'];
            }

            $productAttribute = (new Xdelivery_Model_ProductAttribute())->getProductAttribute($product_id);
            
            $productImage = (new Xdelivery_Model_ProductImages())->find(['product_id' => $product_id, 'is_base' => 1]);
            $products['image'] = $productImage->getProductImage();

            $products['gallery'] = (new Xdelivery_Model_ProductImages())->findAll(['product_id' => $product_id, 'is_base' => 0])->toArray();

            $varientsData = (new Xdelivery_Model_ProductVariant())->getProductVariants($product_id);
                $varients = [];
                foreach ($varientsData as $key => $value) {
                   $varients[$value['product_id']]['parent_product_id'] = (integer) $value['parent_product_id'];
                    $varients[$value['product_id']]['price'] = $value['price'];
                     $varients[$value['product_id']]['varient_product_id'] = (integer) $value['varient_product_id'];
                    $varients[$value['product_id']]['tax_rate'] = $value['tax_rate']; 
                    $varients[$value['product_id']]['product_image'] = $value['product_image'];
                    $varients[$value['product_id']]['special_price'] = $value['special_price'];
                    $varients[$value['product_id']]['sku'] = $value['sku'];
                    $varients[$value['product_id']]['special_price_start'] = $value['special_price_start'];
                    $varients[$value['product_id']]['special_price_end'] = $value['special_price_end'];
                    $varients[$value['product_id']]['is_active'] = (integer) $value['is_active'];
                    $varients[$value['product_id']]['manage_stock'] = (integer) $value['manage_stock'];
                    $varients[$value['product_id']]['qty'] = (integer) $value['qty'];
                    $varients[$value['product_id']]['low_stock_threshold'] = (integer) $value['low_stock_threshold'];
                    $varients[$value['product_id']]['in_stock'] = (integer) $value['in_stock'];

                    $v = [];
                    $v['attribute_name'] = $value['attribute_name'];
                    $v['value_name'] = $value['value_name']; 
                    $varients[$value['product_id']]['values'][] = $v;
                } 
    
        }

        $this->loadPartials();
        $this->getLayout()->getPartial('content')->setProduct($products)->setProductCategory($categories)->setProductAttributes($productAttribute)->setChildProduct($varients);

    }

     /**
     *save products
     */
    public function saveAction()
    {
        if($param = $this->getRequest()->getPost()) {           
            try{
                $special_price_end = $special_price_start = $new_from = $new_to = null;
           
                if(!empty($param['special_price_start'])){
                    $special_price_start = (new Siberian_Date(strtotime($param['special_price_start'])))->toString("yyyy-MM-dd");
                }

                if(!empty($param['special_price_end'])){
                    $special_price_end = (new Siberian_Date(strtotime($param['special_price_end'])))->toString("yyyy-MM-dd");
                }
                if(!empty($param['new_from'])){
                     $new_from = (new Siberian_Date(strtotime($param['new_from'])))->toString("yyyy-MM-dd");
                }
                if(!empty($param['new_to'])){
                    $new_to = (new Siberian_Date(strtotime($param['new_to'])))->toString("yyyy-MM-dd");
                }

                if(empty($param['manage_stock'])){
                    $param['manage_stock'] = 0;
                }           
                
                $productSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $param['product_name'])));

                if(!empty($param['sku'])){
                    $modelProduct = (new Xdelivery_Model_Products())
                        ->find(['sku' => $param['sku']]);
                    if($modelProduct->getId()){
                        throw new Siberian_Exception(p__('xdelivery', 'Product SKU already exist!'));
                    }
                }

                if(empty($param['is_active'])) {
                    $param['is_active'] = 0;
                }   

                $store_id = $param['store_id'];

                $modelProduct = (new Xdelivery_Model_Products())
                    ->find(['id' => $param['id']])
                    ->setStoreId($store_id)
                    ->setValueId($param['value_id'])
                    ->setProductName($param['product_name'])
                    ->setShortDescription($param['short_description'])
                    ->setProductSlug($productSlug)
                    ->setSku($param['sku'])
                    ->setBrandId($param['brand_id'])
                    ->setPrice($param['price'])
                    ->setSpecialPrice($param['special_price'])
                    ->setSpecialPriceStart($special_price_start)
                    ->setSpecialPriceEnd($special_price_end)
                    ->setIsActive($param['is_active'])
                    ->setManageStock($param['manage_stock'])
                    ->setQty($param['qty'])
                    ->setLowStockThreshold($param['low_stock_threshold'])
                    ->setInStock($param['in_stock'])
                    ->setProductType($param['product_type'])
                    ->setTaxId($param['tax_id'])
                    ->setDescription($param['description'])
                    ->setNewFrom($new_from)
                    ->setNewTo($new_to)
                    ->save();

            $product_id = $modelProduct->getId();

            $images = [];
            if(!empty($param['images'])){
                foreach ($param['images'] as $key => $value) {
                   if (file_exists(Core_Model_Directory::getTmpDirectory(true) . "/" . $value)) {
                            list($relativePath, $filename) = $this->_getImageData($value);
                            $images[] = $relativePath . '/' . $filename;                            
                    }
                }
            }

            foreach ($images as $key => $value) {
                $modelProductImage = (new Xdelivery_Model_ProductImages())
                    ->setProductId($product_id)
                    ->setProductImage($value)
                    ->setIsBase(1)
                    ->save();
            }

            $gallery = [];
            if(!empty($param['gallery'])){
                foreach ($param['gallery'] as $key => $value) {
                   if (file_exists(Core_Model_Directory::getTmpDirectory(true) . "/" . $value)) {
                            list($relativePath, $filename) = $this->_getImageData($value);
                            $gallery[] = $relativePath . '/' . $filename;                            
                    }
                }
            }

            foreach ($gallery as $key => $value) {
                $modelProductImage = (new Xdelivery_Model_ProductImages())
                    //->find(['product_id' => $product_id])
                    ->setProductId($product_id)
                    ->setProductImage($value)
                    ->setIsBase(0)
                    ->save();
            }

            foreach ($param['product_category'] as $key => $value) {
                 $modelProductCategory = (new Xdelivery_Model_ProductCategories())
                    ->find(['product_id' => $product_id, 'category_id' => $value])
                    ->setProductId($product_id)
                    ->setCategoryId($value)
                    ->save();
            }

            if(!empty($param['extra_option'])){
               foreach ($param['extra_option'] as $key => $value) {
                 $modelOptionCategory = (new Xdelivery_Model_ExtraOptionProduct())
                        ->find(['product_id' => $product_id, 'option_id' => $value])
                        ->setOptionId($value)
                        ->setProductId($product_id)
                        ->save();
                } 
            }
 
            foreach ($param['attribute_values'] as $key => $value) {
                $splitValue = explode ("-", $value);  
                $attribute_id = $splitValue[0];
                $attribute_value_id = $splitValue[1];

                $modelProductAttribute = (new Xdelivery_Model_ProductAttribute())
                    ->find(['product_id' => $product_id, 'attribute_id' => $attribute_id, 'attribute_value_id' => $attribute_value_id])
                    ->setAttributeId($attribute_id)
                    ->setAttributeValueId($attribute_value_id)
                    ->setProductId($product_id)
                    ->save();
            }

            $this->getSession()->addSuccess(p__('xdelivery', "Info successfully saved"));            
            
            $html = [
                "success" => 1,
                "product_type" => $param['product_type'],
                "product_id" => $product_id
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


    /**
     *save products
     */
    public function updateAction()
    {
        if($param = $this->getRequest()->getPost()) {           
            try{
                $special_price_end = $special_price_start = $new_from = $new_to = null;

                if(!empty($param['special_price_start'])){
                    $special_price_start = (new Siberian_Date(strtotime($param['special_price_start'])))->toString("yyyy-MM-dd");
                }

                if(!empty($param['special_price_end'])){
                    $special_price_end = (new Siberian_Date(strtotime($param['special_price_end'])))->toString("yyyy-MM-dd");
                }
                if(!empty($param['new_from'])){
                     $new_from = (new Siberian_Date(strtotime($param['new_from'])))->toString("yyyy-MM-dd");
                }
                if(!empty($param['new_to'])){
                    $new_to = (new Siberian_Date(strtotime($param['new_to'])))->toString("yyyy-MM-dd");
                }

                if(empty($param['manage_stock'])){
                    $param['manage_stock'] = 0;
                }    

                if(empty($param['is_active'])) {
                    $param['is_active'] = 0;
                }                
       
                $productSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $param['product_name'])));

                $store_id = $param['store_id'];
                
                $modelProduct = (new Xdelivery_Model_Products())
                    ->find(['id' => $param['id']])
                    ->setValueId($param['value_id'])
                    ->setStoreId($store_id)
                    ->setProductName($param['product_name'])
                    ->setShortDescription($param['short_description'])
                    ->setProductSlug($productSlug)
                    ->setSku($param['sku'])
                    ->setBrandId($param['brand_id'])
                    ->setPrice($param['price'])
                    ->setSpecialPrice($param['special_price'])
                    ->setSpecialPriceStart($special_price_start)
                    ->setSpecialPriceEnd($special_price_end)
                    ->setIsActive($param['is_active'])
                    ->setManageStock($param['manage_stock'])
                    ->setQty($param['qty'])
                    ->setLowStockThreshold($param['low_stock_threshold'])
                    ->setInStock($param['in_stock'])
                    ->setProductType($param['product_type'])
                    ->setTaxId($param['tax_id'])
                    ->setDescription($param['description'])
                    ->setNewFrom($new_from)
                    ->setNewTo($new_to)
                    ->save();

            $product_id = $modelProduct->getId();
         
            $images = [];
            if(!empty($param['images'])){
                foreach ($param['images'] as $key => $value) {
                   if (file_exists(Core_Model_Directory::getTmpDirectory(true) . "/" . $value)) {
                            list($relativePath, $filename) = $this->_getImageData($value);
                            $images[] = $relativePath . '/' . $filename;                            
                    }
                }
            }

            foreach ($images as $key => $value) {
                $modelProductImage = (new Xdelivery_Model_ProductImages())
                    ->find(['product_id' => $product_id])
                    ->setProductId($product_id)
                    ->setProductImage($value)
                    ->setIsBase(1)
                    ->save();
            }


            $gallery = [];
            if(!empty($param['gallery'])){
                foreach ($param['gallery'] as $key => $value) {
                   if (file_exists(Core_Model_Directory::getTmpDirectory(true) . "/" . $value)) {
                            list($relativePath, $filename) = $this->_getImageData($value);
                            $gallery[] = $relativePath . '/' . $filename;                            
                    }
                }
            }

            foreach ($gallery as $key => $value) {
                $modelProductImage = (new Xdelivery_Model_ProductImages())
                    //->find(['product_id' => $product_id])
                    ->setProductId($product_id)
                    ->setProductImage($value)
                    ->setIsBase(0)
                    ->save();
            }


            $deleteCategory = (new Xdelivery_Model_Products())->deleteQuery('xdelivery_product_categories', 'product_id', $product_id );
            
            if(!empty($param['product_category'])){
                foreach ($param['product_category'] as $key => $value) {
                     $modelProductCategory = (new Xdelivery_Model_ProductCategories())
                        ->find(['product_id' => $product_id, 'category_id' => $value])
                        ->setProductId($product_id)
                        ->setCategoryId($value)
                        ->save();
                }
            }

           $updateOption  = (new Xdelivery_Model_Products())->updateStatusQuery('xdelivery_extra_option_products', 'product_id', $product_id );

            if(!empty($param['extra_option'])){
               foreach ($param['extra_option'] as $key => $value) {
                 $modelOptionCategory = (new Xdelivery_Model_ExtraOptionProduct())
                        ->find(['product_id' => $product_id, 'option_id' => $value])
                        ->setOptionId($value)
                        ->setProductId($product_id)
                        ->setIsActive(1)
                        ->save();
                } 
            }

           
         $update  = (new Xdelivery_Model_Products())->updateStatusQuery('xdelivery_product_attribute', 'product_id', $product_id );
            
            foreach ($param['attribute_values'] as $key => $value) {
                $splitValue = explode ("-", $value);  
                $attribute_id = $splitValue[0];
                $attribute_value_id = $splitValue[1];
                $is_variant = !empty($param['is_variant_'.$attribute_id]) ? 1 : 0;
                $modelProductAttribute = (new Xdelivery_Model_ProductAttribute())
                    ->find(['product_id' => $product_id, 'attribute_id' => $attribute_id, 'attribute_value_id' => $attribute_value_id])
                    ->setAttributeId($attribute_id)
                    ->setAttributeValueId($attribute_value_id)
                    ->setProductId($product_id)
                    ->setIsVariant($is_variant)
                    ->setIsActive(1)
                    ->save();
            }

            $html = [
                "success" => 1,
                "product_type" => $param['product_type'],
                "product_id" => $product_id
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
      

    /**
     *update Variant products
     */
    public function updateVariantAction()
    {
        if($param = $this->getRequest()->getPost()) {           
            try{

                if(count(array_filter($param['variant_values'])) == 0){
                    throw new Siberian_Exception(p__('xdelivery', 'Please Select a attributes'));
                }

                if(empty($param['price'])){
                    throw new Siberian_Exception(p__('xdelivery', 'Please enter a price'));
                }

                $special_price_end = $special_price_start = $new_from = $new_to = null;

                if(!empty($param['special_price_start'])){
                    $special_price_start = (new Siberian_Date(strtotime($param['special_price_start'])))->toString("yyyy-MM-dd");
                }

                if(!empty($param['special_price_end'])){
                    $special_price_end = (new Siberian_Date(strtotime($param['special_price_end'])))->toString("yyyy-MM-dd");
                }
              
                if(empty($param['manage_stock'])){
                    $param['manage_stock'] = 0;
                }    

                if(empty($param['is_active'])) {
                    $param['is_active'] = 0;
                }                
            
               $storeModel = (new Xdelivery_Model_Store())->find(['value_id' => $param['value_id']]);
                $store_id = $storeModel->getId();
   
                $modelProduct = (new Xdelivery_Model_Products())
                    ->find(['id' => $param['id']])
                    ->setStoreId($store_id)
                    ->setValueId($param['value_id'])
                    ->setSku($param['sku'])
                    ->setPrice($param['price'])
                    ->setSpecialPrice($param['special_price'])
                    ->setSpecialPriceStart($special_price_start)
                    ->setSpecialPriceEnd($special_price_end)
                    ->setIsActive($param['is_active'])
                    ->setManageStock($param['manage_stock'])
                    ->setQty($param['qty'])
                    ->setLowStockThreshold($param['low_stock_threshold'])
                    ->setInStock($param['in_stock'])
                    ->setTaxId($param['tax_id'])
                    ->setDescription($param['description'])
                    ->save();

            $product_id = $modelProduct->getId();
         
            $images = [];
            if(!empty($param['images'])){
                foreach ($param['images'] as $key => $value) {
                   if (file_exists(Core_Model_Directory::getTmpDirectory(true) . "/" . $value)) {
                            list($relativePath, $filename) = $this->_getImageData($value);
                            $images[] = $relativePath . '/' . $filename;                            
                    }
                }
            }

            foreach ($images as $key => $value) {
                $modelProductImage = (new Xdelivery_Model_ProductImages())
                    ->find(['product_id' => $product_id])
                    ->setProductId($product_id)
                    ->setProductImage($value)
                    ->setIsBase(1)
                    ->save();
            }

            if(!empty($param['variant_values'])){
                foreach ($param['variant_values'] as $key => $value) {
                    if(!empty($value)){
                    $splitValue = explode ("-", $value);  
                    $attribute_id = $splitValue[1];
                    $product_attribute_id = $splitValue[0];
                    $mVariant = (new Xdelivery_Model_ProductVariant())
                        ->find(['product_id' => $product_id, 'attribute_id' => $attribute_id])
                        ->setProductAttributeId($product_attribute_id)
                        ->setProductId($product_id)
                        ->setAttributeId($attribute_id)
                        ->save();
                    }
                } 
            }
 
            $html = [
                "success" => 1,
                "product_type" => $param['product_type'],
                "product_id" => $product_id
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

    /**
     *add products
     */
    public function addattributesAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {

           try {
            
                $attribute = (new Xdelivery_Model_Attribute());  
                $attribute->find($id); 
                if (!$attribute->getId()) {
                    throw new Siberian_Exception(p__('xdelivery', 'This attributes does not exist.'));
                }
                $attributesValues = (new Xdelivery_Model_AttributeValues())
                    ->findAll(['attribute_id' => $attribute->getId(), 'is_active != ?' => 2 ], 'position ASC')->toArray();   
                
                if(empty($attributesValues)){
                    throw new Siberian_Exception(p__('xdelivery', 'No values available for this attribute'));
                }
            
                $html = '';
                $html .= '<div class="form-group" id="attribute_value_box_'.$id.'">';
                $html .= '<div class="col-sm-3"><label for="values">'.$attribute->getAttributeName().'</label></div>';
                $html .= '<div class="col-sm-7"><select class="styled-select" name="attribute_values[]" id="attribute_values_'.$id.'" multiple>';
               
                foreach ($attributesValues as $key => $value) {
                     $html .= '<option value="'.$id.'-'.$value['id'].'">'.$value['value_name'].'</option>';
                }               

                $html .= '</select></div><div class="col-sm-2"><button type="button" class="btn btn-danger remove_value_button" data-new="1" data-id="'.$id.'"><i class="fa fa-remove"></i></button></div>';
                $html .= '<div class="col-sm-3">'.p__("xdelivery", "Used for variations").'</div><div class="col-sm-9"><input type="checkbox" value="" class="checkbox color-blue" name="is_variant_'.$attribute->getId().'" /></div></div>';

                $payload = [
                    'success' => true,
                    'message' => p__('xdelivery', 'New row add successfully, Please select values'),
                    'html' => $html,
                    'id' => $id
                ];

            } catch (\Exception $e) {

                $payload = [
                    'error' => true,
                    'message' => $e->getMessage(),
                ];
            }
        }else{
            $payload = [
                    'error' => true,
                    'message' =>  p__('xdelivery', 'Please select attribute.'),
                ];
        }

        $this->_sendJson($payload);
         
    }

    /**
    * fetch products
    */
    public function findAllAction() {
        
        try {
            $request = $this->getRequest();
            $limit = $request->getParam("perPage", 25);
            $offset = $request->getParam("offset", 0);
            $sorts = $request->getParam("sorts", []);
            $queries = $request->getParam("queries", []);             
            $current_date = (new Siberian_Date())->toString("yyyy-MM-dd");


            $filter = null;
            $store_id = null;
            if (array_key_exists("search", $queries)) {
                $search = $queries["search"];
            }

            if (array_key_exists("store_id", $queries)) {
                $store_id = $queries["store_id"];
            }
            
            $params = [
                "limit" => $limit,
                "offset" => $offset,
                "sorts" => $sorts,
                "search" => $search,
                "store_id" => $store_id                
            ];
          
            $value_id = (new Xdelivery_Model_Xdelivery())->getCurrentValueId();
            $application = $this->getApplication();
            
            $products = (new Xdelivery_Model_Products())
                ->findByValueId($value_id, $params);

            $countAll = (new Xdelivery_Model_Products())->countAllForApp($value_id);
            $countFiltered =   (new Xdelivery_Model_Products())->countAllForApp($value_id, $params);

            $productsJson = [];
            foreach ($products as $product) {
                $data = $product->getData();
                /*Stock Manage*/
                if($data['manage_stock'] == "1"){
                    if($data['qty'] == 0){
                        $data['stock'] = p__('xdelivery', 'Out Of Stock');
                    }else{
                        if($data['qty'] <= $data['low_stock_threshold']){
                            $data['stock'] = p__('xdelivery', 'Low Stock');
                        }else{
                            $data['stock'] = p__('xdelivery', 'In Stock');
                        }
                    }
                }else{
                    $data['stock'] = $data['in_stock'] == 1 ? p__('xdelivery', 'In Stock') :  p__('xdelivery', 'Out of Stock');
                }
                /*End Stock Manage*/

                $data['active_special_price'] = 0;
                if(!empty($data['special_price_start']) && !empty($data['special_price_end'])){
                     if (($current_date >= $data['special_price_start']) && ($current_date <= $data['special_price_end'])){
                        $data['active_special_price'] = 1;
                    }
                }
 
                if(empty($data['special_price_start']) && empty($data['special_price_end']) && !empty($data['special_price'])) {
                    $data['active_special_price'] = 1;
                }                   
            

                $data['special_price'] = $this->getApplication()->getCurrency().''.$data['special_price'];
                $data['price'] = $this->getApplication()->getCurrency().''.$data['price'];

                if($data['product_type'] == 'variable'){
                    if(empty($data['max_amount'])){
                        $data['price'] = "-";
                    }else{
                       $data['price'] = $this->getApplication()->getCurrency().''.$data['min_amount'].' - '.$this->getApplication()->getCurrency().''.$data['max_amount'];
                    }
             
                }
 
                $data['is_active'] = $data['is_active'] == 1 ? p__('xdelivery', 'Active') : p__('xdelivery', 'In Active');

                $data['product_image'] = empty($data['product_image']) ? '/app/local/modules/Xdelivery/resources/design/desktop/flat/images/shop-placeholder.png' : '/images/application/'.$data['product_image'];

                $data['sku'] = !empty($data['sku']) ? $data['sku'] : '';
                $productsJson[] = $data;
            }

            $payload = [
                "records" => $productsJson,
                "queryRecordCount" => $countFiltered[0],
                "totalRecordCount" => $countAll[0]
            ];

        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }


    public function deleteAttributeValuesAction() {
        if ($attribute_id = $this->getRequest()->getParam('id')) {
            $product_id = $this->getRequest()->getParam('product_id');
                try {
 
                   // Delete all attributes
                    $attributes = (new Xdelivery_Model_ProductAttribute())
                        ->findAll(['product_id' => $product_id, 'attribute_id' => $attribute_id]);

                    foreach ($attributes as $attribute) {
                        $attribute->delete();
                    }

                       
                    $payload = [
                        'success' => true,
                        'message' => p__('xdelivery', 'Delete successfully'),
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


     //function for delete image 
    public function deleteimageAction() {
        try {
            $request = $this->getRequest();
            
            $imageId = $request->getParam("image_id", null);
            $modelProductImage = (new Xdelivery_Model_ProductImages())
                    ->find($imageId);

            if (!$modelProductImage->getId()) {
                throw new \Siberian\Exception("#07888-01" . p__('xdelivery', 'We are unable to delete this image!'));
            }

            $modelProductImage->delete();

            $payload = [
                'success' => true,
                'message' => p__('xdelivery', 'Successfully deleted'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
   
    }

    //function for delete product  
    public function deleteAction() {
      try {
            $request = $this->getRequest();
            $id = $request->getParam("id", null);
            $model = new Xdelivery_Model_Products();
            $model->find(array('id' => $id));
            $model->setIsActive(2);
            $model->save();

            $payload = [
                'success' => true,
                'message' => p__('xdelivery', 'Successfully deleted'),
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
     * @param $image
     * @return array
     * @throws Siberian_Exception
     */
    private function _getImageData($image)
    {

        $img_src = Core_Model_Directory::getTmpDirectory(true) . "/" . $image;

        $info = pathinfo($img_src);

        $filename = $info['basename'];

        $relativePath = $this->getCurrentOptionValue()->getImagePathTo();

        $img_dst = Application_Model_Application::getBaseImagePath() . $relativePath;

        if (!is_dir($img_dst)) {
            mkdir($img_dst, 0777, true);
        }
        $img_dst .= '/' . $filename;
        rename($img_src, $img_dst);
        
        if (!file_exists($img_dst)) {
            throw new Siberian_Exception(p__('xdelivery', 'An error occurred while saving your picture. Please try againg later.'));
        }
        return [$relativePath, $filename];
    }


    public function qrcodeAction()
    {

         if ($id = $this->getRequest()->getParam('id')) {
           
            $html = '';
             try {
               $model = (new Xdelivery_Model_Products()); 
                $model->find($id); 
                if (!$model->getId()) {
                    throw new Exception(__("An error occurred while retrieving QRCode. Please try again later"));
                }

                $dir_image = Core_Model_Directory::getBasePathTo("/images/application/".$this->getApplication()->getId());

                if(!is_dir($dir_image)) mkdir($dir_image, 0775, true);
                if(!is_dir($dir_image."/application")) mkdir($dir_image."/application", 0775, true);
                if(!is_dir($dir_image."/application/xdelivery/products")) mkdir($dir_image."/application/xdelivery/products", 0775, true);

                $dir_image .= "/application/xdelivery/products/";
                $image_name = $id."-product.png";

                if(!is_file($dir_image.$image_name)) {
                    copy('https://api.qrserver.com/v1/create-qr-code/?color=000000&bgcolor=FFFFFF&data=sendback%3A'.$id.'&qzone=1&margin=0&size=520x520&ecc=L', $dir_image.$image_name);
                }

                $img = imagecreatefrompng($dir_image.$image_name);
                $readable_name = $model->getProductName().'_'.$id.'_QR_code';
                header('Content-Type: image/png');
                header('Content-Disposition: attachment; filename="'.$readable_name.'.png"');
                imagepng($img);
                imagedestroy($img);
                die();

            } catch (Exception $e) {
                $html = $e->getMessage();
            }

            echo $html; die();

        }
    }
 

}