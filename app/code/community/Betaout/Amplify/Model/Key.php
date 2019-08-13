<?php
require_once Mage::getModuleDir('Model', 'Betaout_Amplify').DS.'Model/Amplify.php';
//require_once('Amplify.php');
//require_once('app/Mage.php');

// Need to send default shopping cart url during installation of magento plugin
//30 8 * * 6 home/path/to/command/the_command.sh >/dev/null
//curl -s -o /dev/null http://www.YOURDOMAIN.com/PATH_TO_MAGENTO/cron.php > /dev/null
class Betaout_Amplify_Model_Key extends Mage_Core_Model_Abstract {
    /* @var $this Betaout_Amplify_Model_Key */

    public $key;
    public $secret;
    public $projectId;
    public $bdebug=0;
    public $verified;
    public $host = 'y0v.in';
    public $amplify;
    public $allitems;
    public $checkstring;
    public $email = '';
    public $installDate;
    public $_process_date;
    public $_schedule = '0 0 0 1 12 4090';

    const XML_PATH_KEY = 'betaout_amplify_options/settings/amplify_key';
    const XML_PATH_SECRET = 'betaout_amplify_options/settings/amplify_secret';
    const XML_PATH_PROJECTID = 'betaout_amplify_options/settings/amplify_projectId';
    const XML_PATH_SEND_ORDER_STATUS = 'betaout_amplify_options/order/status1';
    const XML_PATH_DEBUG = 'betaout_amplify_options/settings/amplify_debug';
    const MAIL_TO = 'dharmendra@getamplify.com';
    const MAIL_SUB = 'Magento Info';
    const XML_PATH_MAX_RUNNING_TIME = 'system/cron/max_running_time';
    const XML_PATH_EMAIL_TEMPLATE = 'system/cron/error_email_template';
    const XML_PATH_EMAIL_IDENTITY = 'system/cron/error_email_identity';
    const XML_PATH_EMAIL_RECIPIENT = 'system/cron/error_email';

    public function __construct($key_string) {
        try {

            $this->key = Mage::getStoreConfig(self::XML_PATH_KEY);
            $this->secret = Mage::getStoreConfig(self::XML_PATH_SECRET);
            $this->projectId = Mage::getStoreConfig(self::XML_PATH_PROJECTID);
            $this->verified = 1;
            $this->bdebug = Mage::getStoreConfig(self::XML_PATH_DEBUG);
            $this->amplify = new Amplify($this->key,$this->projectId);
            $this->verified = 1;
            $this->_process_date = Mage::getStoreConfig('betaout_amplify_options/settings/_process_date');
        } catch (Exception $ex) {
            
        }
    }

    public function getToken() {
        $visitorData = Mage::getSingleton('core/session')->getVisitorData();
        return $visitorData['visitor_id'];
    }

    
    public function getAmplifyConfigChangeObserver($evnt) {
    }

    public function getAmplifyEventRemoveFromCart(Varien_Event_Observer $observer) {

        try {
            if ($this->verified && is_object($observer)) {

                $product = $observer->getEvent()->getQuote_item();
                $actionData = array();
                $actionData[0]['id'] = $product->getProductId();
                $actionData[0]['name'] = $product->getName();
                $actionData[0]['sku'] = $product->getSku();
                $actionData[0]['price'] = $product->getPrice();
                $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                $actionData[0]['image_url'] = $product->getImageUrl();
                $actionData[0]['product_url'] = $product->getProductUrl();
                $actionData[0]['quantity'] = (int) $product->getQty();
                
                $subprice = (int) $product->getQty() * $product->getPrice();
                $subprice=Mage::helper('core')->currency($subprice , false, false);
                $cart = Mage::getSingleton('checkout/cart');
                $cart_id=$cart->getQuote()->getId();
                $subTotalPrice = $cart->getQuote()->getGrandTotal();
                
                $cartInfo["total"] = $subTotalPrice - $subprice;
                $cartInfo["revenue"] = $subTotalPrice - $subprice;
                $cartInfo['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
                
                $actionDescription = array(
                    'activity_type' => 'remove_from_cart',
                    'identifiers' => $this->getCustomerIdentity(),
                    'cart_info' => $cartInfo,
                    'products' => $actionData
                );
                $res = $this->amplify->customer_action($actionDescription);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventAddToCart(Varien_Event_Observer $evnt) {
        try {

            if ($this->verified) {

                $event = $evnt->getEvent();
                $product = $event->getProduct();
                $productId = $product->getId();
                $catCollection = $product->getCategoryCollection();
                $categs = $catCollection->exportToArray();
                $cateHolder = array();
                foreach ($categs as $cat) {
                    $cateName = Mage::getModel('catalog/category')->load($cat['entity_id']);
                    $name = $cateName->getName();
                    $id = $cateName->getEntityId();
                    $pid = $cateName->getParent_id();
                    if ($pid == 1) {
                        $pid = 0;
                    }
                    $cateHolder[] = array("cat_id"=>$id,"cat_name" => $name, "parent_cat_id" => $pid);
                }

                $productName = $product->getName();
                $sku = $productName . "_" . $product->getSku();
                $qty = $product->getPrice();
                $cart = Mage::getSingleton('checkout/cart');
                
                $cart_id=$cart->getQuote()->getId();
                $actionData = array();

                $actionData[0]['id'] = $product->getId();
                $actionData[0]['name'] = $product->getName();
                $actionData[0]['sku'] = $product->getSku();
                $actionData[0]['price'] = $product->getFinalPrice();
                $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                $actionData[0]['image_url'] = $product->getImageUrl();
                $actionData[0]['product_url'] = $product->getProductUrl();
                $actionData[0]['quantity'] = (int) $product->getQty();
                $actionData[0]['categories'] = $cateHolder;
              
                $subTotalPrice = $cart->getQuote()->getGrandTotal();
              
                $cartInfo["total"] =$subTotalPrice;
                $cartInfo["revenue"] = $subTotalPrice;
                $cartInfo['abandon_cart_url'] = Mage::getUrl('checkout/cart');
                $cartInfo['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
                
                $actionDescription = array(
                    'activity_type' => 'add_to_cart',
                    'identifiers' => $this->getCustomerIdentity(),
                    'cart_info' => $cartInfo,
                    'products' => $actionData
                );
                 if($this->bdebug){
                  mail("rohit@getamplify.com","DEBUG product add to cart",  json_encode($actionDescription));
                 }
               
               $res = $this->amplify->customer_action($actionDescription);
             
            }
        } catch (Exception $ex) {
            
        }
    }

     public function getAmplify_cartUpdate(Varien_Event_Observer $observer) {
        try {
            if ($this->verified) {
                $i = 0;
                $subdiff = 0;
                $actionData = array();
                foreach ($observer->getCart()->getQuote()->getAllVisibleItems() as $product) {
                   
                    if ($product->hasDataChanges()) {
                        $productId = $product->getProductId();
                        
                        $actionData[$i]['id'] = $product->getProductId();
                        $actionData[$i]['name'] = $product->getName();
                        $actionData[$i]['sku'] = $product->getSku();
                        $actionData[$i]['price'] = $product->getPrice();
                        $actionData[$i]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                        
                        $actionData[$i]['image_url'] = $product->getImageUrl();
                        $actionData[$i]['product_url'] = $product->getProductUrl();
                       
                        $oldQty = (int) $product->getOrigData('qty');
                        $newQty = (int) $product->getQty();
                        $qtyDiff = 0;
                        $subdiff = $subdiff + ($newQty - $oldQty) * $product->getPrice();
                        $actionData[$i]['quantity'] = (int) $product->getQty();
                        $i++;
                    }
                }
                $subdiff=Mage::helper('core')->currency($subdiff , false, false);
                
                $cart = Mage::getSingleton('checkout/cart');
                $cart_id=$cart->getQuote()->getId();
                $subTotalPrice = $cart->getQuote()->getGrandTotal(); 
                $totals = Mage::getSingleton('checkout/cart')->getQuote()->getTotals();

                
                $cartInfo["total"] =$subTotalPrice + $subdiff;
                $cartInfo["revenue"] = $subTotalPrice + $subdiff;
                $cartInfo['abandon_cart_url'] = Mage::getUrl('checkout/cart');
                $cartInfo['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
                
                $actionDescription = array(
                    'activity_type' => 'update_cart',
                    'identifiers' => $this->getCustomerIdentity(),
                    'cart_info' => $cartInfo,
                    'products' => $actionData
                );
                
               //mail("rohit@getamplify.com","update cart",json_encode($actionDescription));
                $res = $this->amplify->customer_action($actionDescription);
            }
        } catch (Exception $ex) {
            
        }
    }
    
    public function getAmplifyEventReview($evnt) {
        try {
            if ($this->verified) {

                $event = $evnt->getEvent();
                $action = $event->getControllerAction();
                $product = $evnt->getProduct();
                $stock_data = $product->getIs_in_stock();
                $catCollection = $product->getCategoryCollection();
                $categs = $catCollection->exportToArray();

                $cateHolder = array();
                foreach ($categs as $cat) {
                    $cateName = Mage::getModel('catalog/category')->load($cat['entity_id']);
                    $name = $cateName->getName();
                    $id = $cateName->getEntityId();
                    $pid = $cateName->getParent_id();
                    if ($pid == 1) {
                        $pid = 0;
                    }
                    $cateHolder[] = array("cat_id"=>$id,"cat_name" => $name, "parent_cat_id" => $pid);
                }

                $actionData = array();
                $actionData[0]['id'] = $product->getId();
                $actionData[0]['name'] = $product->getName();
                $actionData[0]['sku'] = $product->getSku();
                $actionData[0]['price'] = $product->getPrice();
                $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                
                $actionData[0]['image_url'] = $product->getImageUrl();
                $actionData[0]['product_url'] = $product->getProductUrl();
                $actionData[0]['categories'] = $cateHolder;
                
                 $actionDescription = array(
                    'activity_type' => 'review',
                    'identifiers' => $this->getCustomerIdentity(),
                    'products' => $actionData
                );
                
                $res = $this->amplify->customer_action($actionDescription);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventVote($evnt) {
        
    }

    public function getAmplifyEventCustomerLogout($evnt) {
        try {
            if ($this->verified) {

                $this->event('customer_logout');
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventCustomerLogin($evnt) {
        try {
            if ($this->verified) {
                $data=array();
                $c = Mage::getSingleton('customer/session')->getCustomer();
                $customer = Mage::getModel('customer/customer')->load($c->getId());
                $email = $customer->getEmail();
                $custName = $customer->getFirstname();
                $custName = $custName . " " . $customer->getLastname();
                
                $person = array();
                $customerAddressId = $c->getDefaultShipping();
                if ($customerAddressId) {
                    $customer = Mage::getModel('customer/address')->load($customerAddressId);
                }



                if (is_object($customer)) {
                    $person['firstname'] = $customer->getFirstname();
                    $person['lastname'] = $customer->getLastname();
                    $person['postcode'] = $customer->getPostcode();
                    $person['fax'] = $customer->getfax();
                    $person['company'] = $customer->getCompany();
                    $person['street'] = $customer->getStreetFull();
                    
                    $data['email']=$email;
                    $data['phone'] = $customer->getTelephone();
                    $data['customer_id'] = $customer->getCustomerId();
                    try {
                      $data=  array_filter($data);
                      $this->amplify->identify($data);
                     } catch (Exception $ex) {
                    }
                    $person = array_filter($person);
                    $properties['update']=$person;
                    $res = $this->amplify->userProperties($data, $properties);
                }else{
                  try {
                     $data['email']=$email;
                     $this->amplify->identify($data);
                   } catch (Exception $ex) {

                   }
                }
                
                $this->amplify->event($data, "customer_login");
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventNewsletter($evnt) {
        try {
            if ($this->verified) {

                $subscriber = $evnt->getEvent()->getSubscriber();
                $identity['email']=$subscriber->subscriber_email;
                try{
                $this->amplify->identify($identity);
                }catch(Exception $ex){
                    
                }

                if ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                   $this->event('subscribed_to_newsletter');
                } elseif ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                   $this->event('unsubscribed_from_newsletter');
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getCustomerIdentity($true = 1) {
        try {
           $data=array();
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {

                $c = Mage::getSingleton('customer/session')->getCustomer();
                $customer = Mage::getModel('customer/customer')->load($c->getId());
                $email = $customer->getEmail();
                $custName = $customer->getFirstname();
                $custName = $custName . " " . $customer->getLastname();
                $data = json_decode(base64_decode(Mage::getModel('core/cookie')->get('_ampUSER')),true);
                $data['email']=$email;
            } else {
                $data = json_decode(base64_decode(Mage::getModel('core/cookie')->get('_ampUSER')),true);
            }
            if ($true)
                return $data;
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventOrder($evnt) {
        try {
            if ($this->verified) {
                $this->event('place_order_clicked');
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getamplifyEventCustomerSave($evnt) {
        
    }

    public function getCustomereventInfo($customer) {
        try {
            if ($this->verified) {

                $person = array();
                $person['email'] = $customer->getEmail();
                $person['customer_id'] = $customer->getId();
                $person['first_name'] = $customer->getFirstname();
                $person['last_name'] = $customer->getLastname();
                $person['created'] = $customer->getCreatedAt();
//                $person['unique_id'] = $customer->getEmail();

                return $person;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function eventCustomerFromCheckout($evnt) {
        
    }

    public function getAmplifyEventCustomerRegisterSuccess($evnt) {
        try {
            if ($this->verified) {

                $customer = $evnt->getCustomer();
                $person = array();
                $person = $this->getCustomereventInfo($customer);
                $identifyData['email']=$person['email'];
                $identifyData['customer_id']=$person['customer_id'];
                $this->amplify->identify($identifyData, $person['first_name']);
                $properties['update']=array("first_name"=>$person['first_name'],'last_name'=>$person['first_name']);
                $this->amplify->event($identifyData, $properties);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventCoupon($evnt) {

        try {
            if ($this->verified) {

                $action = $evnt->getEvent()->getControllerAction();
                $coupon_code = trim($action->getRequest()->getParam('coupon_code'));
                $oCoupon = Mage::getModel('salesrule/coupon')->load($coupon_code, 'code');
                $oRule = Mage::getModel('salesrule/rule')->load($oCoupon->getRuleId());
                $coupon_code = $oRule->getCoupon_code();

                if (isset($coupon_code) && !empty($coupon_code)) {
                    $this->event('coupon_success');
                } else {
                 $this->event('coupon_unsuccess');                }
                return $this;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function event($event) {
        try {
            if ($this->verified) {
             $this->amplify->event($this->getCustomerIdentity(), $event);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function eventPerson($person, $additional = array()) {
        
    }

    public function event_revenue($identifier, $revenue) {
        
    }

    public function getAmplifyEventWishlist($evnt) {
        try {
            if ($this->verified) {

                $event = $evnt->getEvent();
                $eventname = $event->getName();
                $product = $event->getProduct();

                $catCollection = $product->getCategoryCollection();
                $categs = $catCollection->exportToArray();
                $cateHolder = array();

               foreach ($categs as $cat) {
                    $cateName = Mage::getModel('catalog/category')->load($cat['entity_id']);
                    $name = $cateName->getName();
                    $id = $cateName->getEntityId();
                    $pid = $cateName->getParent_id();
                    if ($pid == 1) {
                        $pid = 0;
                    }
                    $cateHolder[] = array("cat_id"=>$id,"cat_name" => $name, "parent_cat_id" => $pid);
                }
                
                $actionData = array();
                $actionData[0]['productId'] = $product->getId();
                $actionData[0]['productTitle'] = $product->getName();
                $actionData[0]['sku'] = $product->getSku();
                $actionData[0]['price'] = $product->getPrice();
                $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                
                $actionData[0]['image_url'] = $product->getImageUrl();
                $actionData[0]['product_url'] = $product->getProductUrl();

                $actionData[0]['categories'] = $cateHolder;
                
                $actionDescription = array(
                    'activity_type' => 'wishlist',
                    'identifiers' => $this->getCustomerIdentity(),
                    'products' => $actionData
                );
                $res = $this->amplify->customer_action($actionDescription);
            }
        } catch (Exception $ex) {
            
        }
    }

    /**
     * @author Rohit Tyagi
     * @desc verify key and secret while saving them
     */
    public function getAmplifyOrderSuccessPageView(Varien_Event_Observer $evnt) {
        try {
            if($this->bdebug){
             mail("rohit@getamplify.com","DEBUG","order start");
            }
            if ($this->verified) {
               $orderIds = $evnt->getData('order_ids');
               if (empty($orderIds) || !is_array($orderIds)) {
                 
                    $this->event('Order Id Missing');
               }else{
               foreach($orderIds as $_orderId){
                $order = Mage::getModel("sales/order")->load($_orderId);
                $order_id = $order->getIncrementId();
                $person = array();
                $data=array();
                $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
                if ($customerAddressId) {
                     $customer = $order->getShippingAddress();

                    if (is_object($customer)) {
                        $data['email']=$customer->getEmail();
                        $data['phone'] = $customer->getTelephone();
                        $data['customer_id'] = $customer->getCustomerId();
                        $person['firstname'] = $customer->getFirstname();
                        $person['lastname'] = $customer->getLastname();
                        $person['postcode'] = $customer->getPostcode();
                        $person['fax'] = $customer->getfax();
                        $person['company'] = $customer->getCompany();
                        $person['street'] = $customer->getStreetFull();
                    }
                   try {
                      $this->amplify->identify($data);
                     } catch (Exception $ex) {
                    }
                    $person = array_filter($person);
                    $properties['update']=$person;
                    $data=  array_filter($data);
                    $res = $this->amplify->userProperties($data, $properties);
                    
                } else {
                    $customer = $order->getShippingAddress();
                    if (is_object($customer)) {
                        $data['email']=$customer->getEmail();
                        $data['phone'] = $customer->getTelephone();
                        $data['customer_id'] = $customer->getCustomerId();
                        $person['firstname'] = $customer->getFirstname();
                        $person['lastname'] = $customer->getLastname();
                        $person['postcode'] = $customer->getPostcode();
                        $person['fax'] = $customer->getfax();
                        $person['company'] = $customer->getCompany();
                        $person['street'] = $customer->getStreetFull();
                        try {
                         $this->amplify->identify($data);
                          } catch (Exception $ex) {
                          }
                       $person = array_filter($person);
                       $properties['update']=$person;
                       $data=  array_filter($data);
                      $res = $this->amplify->userProperties($data, $properties);
                    }
                }

                $items = $order->getAllVisibleItems();
                $itemcount = count($items);
                
                $i = 0;
                $actionData = array();

                foreach ($items as $itemId => $item) {
                    $product = $item;

                    $product = Mage::getModel('catalog/product')->load($product->getProductId());
                    $cateHolder = array();
                    try{
                        $catCollection = $product->getCategoryCollection();
                        $categs = $catCollection->exportToArray();
                        foreach ($categs as $cat) {
                            $cateName = Mage::getModel('catalog/category')->load($cat['entity_id']);
                            $name = $cateName->getName();
                            $id = $cateName->getEntityId();
                            $pid = $cateName->getParent_id();
                            if ($pid == 1) {
                                $pid = 0;
                            }
                            $cateHolder[] = array_filter(array("cat_id"=>$id,"cat_name" => $name, "parent_cat_id" => $pid));
                        }
                    }catch(Exception $e){
                        
                    }
                    $cateHolder=  array_filter($cateHolder);
                    $actionData[$i]['id'] = $product->getId();
                    $actionData[$i]['name'] = $product->getName();
                    $actionData[$i]['sku'] = $product->getSku();
                    $actionData[$i]['price'] = $product->getPrice();
                    $actionData[$i]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                    $actionData[$i]['image_url'] = $product->getImageUrl();
                    $actionData[$i]['product_url'] = $product->getProductUrl();
                    $actionData[$i]['brandname'] = $product->getResource()->getAttribute('manufacturer') ? $product->getAttributeText('manufacturer') : false;
                    $actionData[$i]['quantity'] = (int) $item->getQtyOrdered();
                    $actionData[$i]['categories'] = $cateHolder;
                    $i++;
                }

           
                $cart_id=Mage::getModel('core/cookie')->get('_ampCart');
                
                 
                $TotalPrice = $order->getGrandTotal();
                $totalShippingPrice = $order->getShippingAmount();
                $TotalPrice = $TotalPrice;
                $subTotalPrice = $order->getSubtotal();
                
                $orderInfo["revenue"]  = $subTotalPrice - abs($order->getDiscountAmount());
                $orderInfo["total"]    = $TotalPrice;
                $orderInfo["shipping"] = $totalShippingPrice;
                $orderInfo['order_id'] = $order->getIncrementId();
                $orderInfo['coupon']= $order->getCouponCode();
                $orderInfo['discount'] = abs($order->getDiscountAmount());
               
                $orderInfo['currency'] = $order->getOrderCurrencyCode();
                $orderInfo['status'] = 'completed';
                
                $orderInfo['tax'] = $order->getShippingTaxAmount();
                if(!is_object($order->getPayment())){
                   $orderInfo['payment_method']="Custom";
                 }else{
                  $orderInfo['payment_method'] = $order->getPayment()->getMethodInstance()->getCode();
                 }
             
                $actionDescription = array(
                    'activity_type' => 'purchase',
                    'identifiers' => $data,
                    'order_info' => $orderInfo,
                    'products' => $actionData
                );
                if($this->bdebug){
                 mail("rohit@getamplify.com","DEBUG order data",  json_encode($actionDescription));
                }
                $res = $this->amplify->customer_action($actionDescription);
                 if($this->bdebug){
                  mail("rohit@getamplify.com","DEBUG order response",  json_encode($res));
                 }
              }
             }
            }
        } catch (Exception $ex) {
            $this->event('error_one');
        }
    }

    public function getAmplifyOrderSaveSuccess(Varien_Event_Observer $evnt) {
    }

    public function getAmplify_checkout_allow_guest($evnt) {
        try {
            if ($this->verified) {
                $getquote = $evnt->getQuote();
                $data = array_filter($getquote->getData());
                if(isset($data['customer_email']) && $data['customer_email']!=""){
                Mage::getModel('core/cookie')->set('amplify_email', $data['customer_email']);
                $person = array();
                $person['webId'] = Mage::app()->getWebsite()->getId();
                $person['storeId'] = Mage::app()->getStore()->getId();
                $person['firstName'] = $data['customer_firstname'];
                $person['lastName'] = $data['customer_lastname'];
                $person = array_filter($person);
                $identifierData['email']=$data['customer_email'];
                $identifierData['customer_id']="";
                $identifierData['phone']="";
                $this->amplify->identify($identifierData);
                $properties['update']=$person;
                $res = $this->amplify->userProperties($identifierData, $properties);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyCatalog_product_save_after($observer) {
          try {
        $product = $observer->getProduct();
        $catCollection = $product->getCategoryCollection();

               $categs = $catCollection->exportToArray();
               $cateHolder = array();
               foreach ($categs as $cat) {
                   $cateName = Mage::getModel('catalog/category')->load($cat['entity_id']);
                   $name = $cateName->getName();
                   $id = $cateName->getEntityId();
                   $pid = $cateName->getParent_id();
                   if ($pid == 1) {
                       $pid = 0;
                   }
                   $cateHolder[] = array("cat_id"=>$id,"cat_name" => $name, "parent_cat_id" => $pid);
               }
           $actionData = array();
           $actionData[0]['id'] = $product->getId();
           $actionData[0]['name'] = $product->getName();
           $actionData[0]['sku'] = $product->getSku();
           $actionData[0]['price'] = $product->getPrice();
           $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
           $actionData[0]['image_url'] = $product->getImageUrl();
           $actionData[0]['product_url'] = $product->getProductUrl(); 
           $actionData[0]['categories'] = $cateHolder;
           $actionDescription = array(
                    "identifiers" => $this->getCustomerIdentity(),
                    'products' => $actionData
            );
           $this->amplify->product_add($actionDescription);
           } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyCatalog_product_delete_after_done($evnt) {
        
    }

    public function getAmplifyCatalogProductView(Varien_Event_Observer $evnt) {
        try {
            if ($this->verified) {
               
                $product = $evnt->getEvent()->getProduct();
                $catCollection = $product->getCategoryCollection();

                $categs = $catCollection->exportToArray();
                $cateHolder = array();
                foreach ($categs as $cat) {
                    $cateName = Mage::getModel('catalog/category')->load($cat['entity_id']);
                    $name = $cateName->getName();
                    $id = $cateName->getEntityId();
                    $pid = $cateName->getParent_id();
                    if ($pid == 1) {
                        $pid = 0;
                    }
                    $cateHolder[] = array("cat_id"=>$id,"cat_name" => $name, "parent_cat_id" => $pid);
                }


                $event = $evnt->getEvent();
                $action = $event->getControllerAction();
                $stock_data = $product->getIs_in_stock();
                $actionData = array();
                $actionData[0]['id'] = $product->getId();
                $actionData[0]['name'] = $product->getName();
                $actionData[0]['sku'] = $product->getSku();
                $actionData[0]['price'] = $product->getPrice();
                $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                $actionData[0]['image_url'] = $product->getImageUrl();
                $actionData[0]['product_url'] = $product->getProductUrl(); 
                $actionData[0]['categories'] = $cateHolder;
                
             
               // $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $actionDescription = array(
                    'activity_type' => 'view',
                    "identifiers" => $this->getCustomerIdentity(),
                    'products' => $actionData
                );
               
                $res = $this->amplify->customer_action($actionDescription);
            }
        } catch (Exception $ex) {
            
        }
    }
    
    public function getAmplifyCustomerAdressSaveAfter($evnt) {
        
    }

    /**
     * @param Varien_Event_Observer $observer
     * @author Dharam <dharmendra@socialcrawler.in>
     *
     */

    public function getAmplifyCancelOrderItem($observer) {
        
    }

    public function sendData() {
        
    }

}
