<?php

class Betaout_Amplify_BetaoutorderController extends Mage_Core_Controller_Front_Action {

public function importAction(){
   try{
    $startDate=isset($_GET['startDate'])?$_GET['startDate']:date("Y-m-d 00:00:00");
    $endDate=isset($_GET['endDate'])?$_GET['endDate']:date("Y-m-d 23:00:00");
    $status=isset($_GET['status'])?$_GET['status']:"completed";
    $yesterday=date("Y-m-d 00:00:00",  strtotime($startDate));
    $today=date("Y-m-d 23:59:59",  strtotime($endDate));
    $orders = Mage::getModel('sales/order')->getCollection()
         ->addFieldToFilter('status', $status)
         ->addAttributeToFilter('created_at', array("from" =>  $yesterday, "to" =>  $today, "datetime" => true))
         ->addAttributeToSelect('entity_id');
    
 echo $count=$orders->Count();
      if($count){
foreach ($orders as $order)  {
                $orderId = $order->getId();
                $order = Mage::getModel("sales/order")->load($orderId);
                $order_id = $order->getIncrementId();
                $email=  $order->getData('customer_email');
                $email=  $order->getData('customer_email');
                $data=array();
                $data['email']=$email;
                $customer = $order->getShippingAddress();
                 if (is_object($customer)) {
                 $data['email']=$customer->getEmail();
                 $data['phone'] = $customer->getTelephone();
                 $data['customer_id'] = $customer->getCustomerId();
                 }
                 $data= array_filter($data);
                $items = $order->getAllVisibleItems();
                $itemcount = count($items);
                $i = 0;
                $actionData = array();

                foreach ($items as $itemId => $item) {
                    $product = $item;
                    $product = Mage::getModel('catalog/product')->load($product->getProductId());
                    $categoryIds = $product->getCategoryIds();
                    $cateHolder = array();

                    foreach ($categoryIds as $cat) {
                        $cateName = Mage::getModel('catalog/category')->load($cat['entity_id']);
                        $name = $cateName->getName();
                        $id = $cateName->getEntityId();
                        $pid = $cateName->getParent_id();
                        if ($pid == 1) {
                            $pid = 0;
                        }
                        $cateHolder[] = array("cat_id"=>$id,"cat_name" => $name, "parent_cat_id" => $pid);
                    }

                    $actionData[$i]['id'] = $product->getId();
                    $actionData[$i]['name'] = $product->getName();
                    $actionData[$i]['sku'] = $product->getSku();
                    $actionData[$i]['price'] = $product->getPrice();
                    $actionData[$i]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                   
                    $actionData[$i]['image_url'] = $product->getImageUrl();
                    $actionData[$i]['product_url'] = $product->getProductUrl();
                    $actionData[$i]['brandname'] = $product->getResource()->getAttribute('manufacturer') ? $product->getAttributeText('manufacturer') : false;
                    $actionData[$i]['quantity'] = (int) $item->getQtyOrdered();
                    $actionData[$i]['category'] = $cateHolder;
                   
                    $i++;
                }

              
                $TotalPrice = $order->getGrandTotal();
                $totalShippingPrice = $order->getShippingAmount();
                $TotalPrice = $TotalPrice;
                $subTotalPrice = $order->getSubtotal();
                $orderInfo["revenue"] = $subTotalPrice - abs($order->getDiscountAmount());
                $orderInfo["total"] = $TotalPrice;
                $orderInfo["shipping"] = $totalShippingPrice;
                $orderInfo['order_id'] = $order->getIncrementId();
                $orderInfo['coupon'] = $order->getCouponCode();
                $orderInfo['discount'] = abs($order->getDiscountAmount());
                $orderInfo['currency'] = $order->getOrderCurrencyCode();
                $orderInfo['status'] = 'completed';
                $orderInfo['tax'] = $order->getShippingTaxAmount();
                $orderInfo['payment_method']="Custom";
                
                $actionDescription = array(
                    'activity_type' => 'purchase',
                    'identifiers' => $data,
                    'order_info' => $orderInfo,
                    'products' => $actionData,
                    'timestamp'=>Mage::getModel('core/date')->timestamp($order->getData('created_at'))
                );
                echo "Time".$order->getData('created_at');
//             
              
               self::sendData($actionDescription,'ecommerce/activities/');
               
        }
      }else{
          
      }
        
   }catch(Exception $e){
       echo json_encode(array("error"=>$e->getMessage()));
   }
        
}

public function addProductAction(){
    $limit=isset($_GET['limit'])?$_GET['limit']:"5";
    $cpage=isset($_GET['pageNo'])?$_GET['pageNo']:1;
 $products = Mage::getModel('catalog/product')->getCollection()
->addAttributeToSelect('*') // select all attributes
->setPageSize($limit) // limit number of results returned
->setCurPage($cpage); // set the offset (useful for pagination)

 $productData=array();
 $i=0;
// we iterate through the list of products to get attribute values
foreach ($products as $product) {
   $productData[$i]['name']=$product->getName(); //get name
   $productData[$i]['price']=(float) $product->getPrice(); //get price as cast to float
   $productData[$i]['id']=$product->getId();
   $productData[$i]['sku']= $product->getSku();
   $productData[$i]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
   $productData[$i]['image_url'] = $product->getImageUrl();
   $productData[$i]['product_url'] = $product->getProductUrl();
   $productData[$i]['brandname'] = $product->getResource()->getAttribute('manufacturer') ? $product->getAttributeText('manufacturer') : false;
  $categories=array();
   $categoryIds = $product->getCategoryIds();
  // getCategoryIds(); returns an array of category IDs associated with the product
  foreach ($categoryIds as $category_id) {
      $cateName = Mage::getModel('catalog/category')->load($category_id['entity_id']);
       $name = $cateName->getName();
       $id = $cateName->getEntityId();
       $pid = $cateName->getParent_id();
       if ($pid == 1) {
        $pid = 0;
    }
   $categories[] = array("cat_id"=>$id,"cat_name" => $name, "parent_cat_id" => $pid);
  }
  $productData[$i]['categories']=$categories;
  $i++;
}
 $actionDescription = array(
                    'products' => $productData,
                    'timestamp'=> time()
                );
  self::sendData($actionDescription,'ecommerce/products/');
}
public function sendData($data,$path){
        $key=Mage::getStoreConfig('betaout_amplify_options/settings/amplify_key');
        $projectId = Mage::getStoreConfig('betaout_amplify_options/settings/amplify_projectId');
        $url="https://api.betaout.com/v2/".$path;
        $data['apikey']=$key;
        $data['project_id']=$projectId;
        $data['useragent'] = $_SERVER['HTTP_USER_AGENT'];
        $jdata = json_encode($data);
        $curl = curl_init($url);
        curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 3000);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jdata);
        $result     = curl_exec($curl);
        $response   = json_decode($result);
        curl_close($curl);
    }
}
