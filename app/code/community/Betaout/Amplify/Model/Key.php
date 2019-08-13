<?php
require_once 'Amplify.php';
require_once('app/Mage.php');

// Need to send default shopping cart url during installation of magento plugin
//30 8 * * 6 home/path/to/command/the_command.sh >/dev/null
//curl -s -o /dev/null http://www.YOURDOMAIN.com/PATH_TO_MAGENTO/cron.php > /dev/null
class Betaout_Amplify_Model_Key extends Mage_Core_Model_Abstract {
    /* @var $this Betaout_Amplify_Model_Key */

    public $key;
    public $secret;
    public $projectId;
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
            $this->verified =1;// Mage::getStoreConfig('betaout_amplify_options/settings/amplify_verified');
            $this->amplify = new Amplify($this->key, $this->secret, $this->projectId);
            $this->verified = 1;
            $this->_process_date = Mage::getStoreConfig('betaout_amplify_options/settings/_process_date');
        } catch (Exception $ex) {
            
        }
    }

    public function getToken() {
        $visitorData = Mage::getSingleton('core/session')->getVisitorData();
        return $visitorData['visitor_id'];
    }

    public function getAmplifyCheckOrderStatus($observer) {

    }

    public function getAmplifyConfigChangeObserver($evnt) {
//        if (($this->key && $this->secret && $this->projectId)) {
//            $this->key = Mage::getStoreConfig(self::XML_PATH_KEY);
//            $this->secret = Mage::getStoreConfig(self::XML_PATH_SECRET);
//            $this->projectId = Mage::getStoreConfig(self::XML_PATH_PROJECTID);
//            $this->amplify = new Amplify($this->key, $this->secret, $this->projectId);
//            Mage::getModel('core/config')->saveConfig('betaout_amplify_options/settings/amplify_verified', TRUE);
//             Mage::getStoreConfig('betaout_amplify_options/settings/beta_start_date');
//           if (!Mage::getStoreConfig('betaout_amplify_options/settings/beta_start_date')) {
//              
//                  try {
//
//                      $this->setUser();
//                      $website = Mage::getBaseUrl();
//                      $this->informBetaout("$this->projectId is used by a magento client $website");
//                  } catch (Exception $exc) {
//
//                  }
//
//                  Mage::getModel('core/config')->saveConfig('betaout_amplify_options/settings/beta_start_date', gmdate('Y-m-d H:i:s'));
//                  Mage::getModel('core/config')->saveConfig('betaout_amplify_options/order/cron_setting', '*/5 * * * *');
//                  Mage::getModel('core/config')->saveConfig('betaout_amplify_options/settings/_process_date', gmdate('Y-m-d H:i:s', strtotime("+1 hour")));
//              }else{
//                 
//                  //Mage::getStoreConfig('betaout_amplify_options/settings/_process_date');
//              }
//          }
//       
        
    }

    public function getAmplifyEventRemoveFromCart(Varien_Event_Observer $observer) {
       
        try {
            if ($this->verified && is_object($observer)) {
              
                $product = $observer->getEvent()->getQuote_item();
                
                $actionData = array();
                $actionData[0]['productId'] = $product->getProductId();
                $actionData[0]['productTitle'] = $product->getName();
                $actionData[0]['sku'] = $product->getSku();
                $actionData[0]['price'] = $product->getPrice();
                $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                $actionData[0]['specialPrice'] = $product->getFinalPrice();
                $actionData[0]['status'] = $product->getStatus();
                $actionData[0]['productPictureUrl'] = $product->getImageUrl();
                $actionData[0]['pageUrl'] = $product->getProductUrl();
                $actionData[0]['weight'] = $product->getWeight();
                $actionData[0]['size'] = false;
                $actionData[0]['color'] = false;
                $actionData[0]['qty'] = (int) $product->getQty();
                $actionData[0]['category'] = "";
                $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $subprice=(int) $product->getQty()*$product->getPrice();
                $cart = Mage::getSingleton('checkout/cart');
                $subTotalPrice = $cart->getQuote()->getGrandTotal();
                $orderInfo["subtotalPrice"] = $subTotalPrice-$subprice;
                $actionDescription = array(
                    'action' => 'removed_from_cart',
                    'email' => $this->getCustomerIdentity(),
                    'or' => $orderInfo,
                    'pd' => $actionData
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
                    $name=$cateName->getName();
                    $id=$cateName->getEntityId();
                    $pid=$cateName->getParent_id();
                    if($pid==1){
                        $pid=0;
                    }
                    $cateHolder[$id] = array("n"=>$name,"p"=>$pid);
                }
               
                $productName = $product->getName();
                $sku = $productName . "_" . $product->getSku();
                $qty = $product->getPrice();
                $cart = Mage::getSingleton('checkout/cart');


//                $this->event('add_to_cart', array('product_name' => false));
                $stock_data = $product->getIs_in_stock();
//                $product = Mage::getModel('catalog/product')->load($productId);
                $actionData = array();

                $actionData[0]['productId'] = $product->getId();
                $actionData[0]['productTitle'] = $product->getName();
                $actionData[0]['sku'] = $product->getSku();
                $actionData[0]['price'] = $product->getPrice();
                $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                $actionData[0]['specialPrice'] = $product->getPrice();
                $actionData[0]['status'] = $product->getStatus();
                $actionData[0]['productPictureUrl'] = $product->getImageUrl();
                $actionData[0]['pageUrl'] = $product->getProductUrl();
                $actionData[0]['weight'] = $product->getWeight();
                $actionData[0]['size'] = $product->getResource()->getAttribute('shirt_size') ? $product->getAttributeText('shirt_size') : false;
                $actionData[0]['color'] = $product->getResource()->getAttribute('color') ? $product->getAttributeText('color') : false;
                $actionData[0]['brandName'] = $product->getResource()->getAttribute('manufacturer') ? $product->getAttributeText('manufacturer') : false;
                $actionData[0]['qty'] = (int) $product->getQty();
                $actionData[0]['category'] = $cateHolder;
                $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $subTotalPrice = $cart->getQuote()->getGrandTotal();
                $orderInfo["subtotalPrice"] = $subTotalPrice;
                $orderInfo['abandonedCheckoutUrl'] = Mage::getUrl('checkout/cart');
                $orderInfo['currency']=Mage::app()->getStore()->getBaseCurrencyCode();
                $actionDescription = array(
                    'or' => $orderInfo,
                    'email' => $this->getCustomerIdentity(),
                    'action' => 'add_to_cart',
                    'pd' => $actionData
                );
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
                    $name=$cateName->getName();
                    $id=$cateName->getEntityId();
                    $pid=$cateName->getParent_id();
                    if($pid==1){
                        $pid=0;
                    }
                    $cateHolder[$id] = array("n"=>$name,"p"=>$pid);
                }
               
                $actionData = array();
                $actionData[0]['productId'] = $product->getId();
                $actionData[0]['productTitle'] = $product->getName();
                $actionData[0]['sku'] = $product->getSku();
                $actionData[0]['price'] = $product->getPrice();
                $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                $actionData[0]['specialPrice'] = $product->getFinalPrice();
                $actionData[0]['status'] = $product->getStatus();
                $actionData[0]['productPictureUrl'] = $product->getImageUrl();
                $actionData[0]['pageUrl'] = $product->getProductUrl();
                $actionData[0]['weight'] = $product->getWeight();
                $actionData[0]['stockAvailability'] = $stock_data ? $stock_data : 2;
                $actionData[0]['size'] = false;
                $actionData[0]['color'] = false;
                $actionData[0]['qty'] = false;
                $actionData[0]['category'] = $cateHolder;
                $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $actionDescription = array(
                    'action' => 'reviewed',
                    'email' => $this->getCustomerIdentity(),
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

                $this->event('customer_logout', array('action' => 'logout'));
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventCustomerLogin($evnt) {
        try {
            if ($this->verified) {

                $c = Mage::getSingleton('customer/session')->getCustomer();
                $customer = Mage::getModel('customer/customer')->load($c->getId());
                $email = $customer->getEmail();
                $custName = $customer->getFirstname();
                $custName = $custName . " " . $customer->getLastname();
                try {
                    $this->amplify->identify($email, $custName);
                } catch (Exception $ex) {
                    
                }
                $this->amplify->event($email, array("customer_login" => 1));


                $person = array();
                $person['webId'] = $customer->getWebsiteId();
                $person['storeId'] = $customer->getStoreId();
                $person['groupId]'] = $customer->getGroupId();
                $res = $this->amplify->add($email, $person, 1);
                $person = array();
                $customerAddressId = $c->getDefaultShipping();
                if ($customerAddressId) {
                    $customer = Mage::getModel('customer/address')->load($customerAddressId);
                }



                if (is_object($customer)) {
                    $person['firstname'] = $customer->getFirstname();
                    $person['lastname'] = $customer->getLastname();

                    $person['postcode'] = $customer->getPostcode();
                    $person['telephone'] = $customer->getTelephone();
                    $person['fax'] = $customer->getfax();
                    $person['customerId'] = $customer->getCustomerId();
                    $person['company'] = $customer->getCompany();
//     $person['region'] = $customer->getRegion();
                    $person['street'] = $customer->getStreetFull();
                    $person = array_filter($person);
                    $res = $this->amplify->update($email, $person);
                }
          
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventNewsletter($evnt) {
        try {
            if ($this->verified) {

                $subscriber = $evnt->getEvent()->getSubscriber();
                $this->amplify->identify($subscriber->subscriber_email);
              
                if ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {

                    $this->event('subscribed_to_newsletter', array('action' => 'subscribed_to_newsletter'));
                } elseif ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {

                    $this->event('unsubscribed_from_newsletter', array('action' => 'unsubscribed_from_newsletter'));
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getCustomerIdentity($true = 1) {
        try {

            if (Mage::getSingleton('customer/session')->isLoggedIn()) {

                $c = Mage::getSingleton('customer/session')->getCustomer();
                $customer = Mage::getModel('customer/customer')->load($c->getId());
                $email = $customer->getEmail();
                $custName = $customer->getFirstname();
                $custName = $custName . " " . $customer->getLastname();
            } else {
                $email = base64_decode(Mage::getModel('core/cookie')->get('_ampEm'));
            }
            if ($true)
                
            return $email;
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
                $this->amplify->identify($person['email'], $person['first_name']);
                $this->amplify->event($person['email'], array("create_account" => 1));
//                $this->eventPerson($person);
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
                    $this->event('coupon_success', array('code' => $coupon_code));
                    $this->amplify->add($this->getCustomerIdentity(), array("coupon_used" => 'couponCode_' . $coupon_code));
                } else {

                    $this->event('coupon_unsuccess', array('code' => $coupon_code));
//                    $this->amplify->add($this->getCustomerIdentity(), array("coupon_used" => 'couponCode_' . $coupon_code));
                }
                return $this;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function event($event, $properties = array()) {
        try {
            if ($this->verified) {

                $params = array(
                    $event => false
                );
                if ($this->verified)
                    $this->amplify->event($this->getCustomerIdentity(1), $params);
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

                $productName = $product->getName();
                $sku = $product->getSku();
                $qty = $product->getPrice();
                $catCollection = $product->getCategoryCollection();
                $categs = $catCollection->exportToArray();
                $cateHolder = array();
                  
                 foreach ($categs as $cat) {
                    $cateName = Mage::getModel('catalog/category')->load($cat['entity_id']);
                    $name=$cateName->getName();
                    $id=$cateName->getEntityId();
                    $pid=$cateName->getParent_id();
                    $cateHolder[$id] = array("n"=>$name,"p"=>$pid);
                   }
                $wishList = Mage::getSingleton('wishlist/wishlist')->loadByCustomer($customer);
                $wishListItemCollection = $wishList->getItemCollection();
                if (count($wishListItemCollection)) {
                    $arrProductIds = array();

                    foreach ($wishListItemCollection as $item) {
                        /* @var $product Mage_Catalog_Model_Product */
                        $product = $item->getProduct();
                        $arrProductIds[] = $product->getId();
                    }
                }
                $whlistDeatails = array(
                    'product_name' => $productName,
                    'sku' => $sku,
                );
                $eventArr = array(
                    $eventname => $whlistDeatails
                );
//                $this->amplify->event($this->getCustomerIdentity(), $eventArr);
                $stock_data = $product->getIs_in_stock();
                $actionData = array();
                $actionData[0]['productId'] = $product->getId();
                $actionData[0]['productTitle'] = $product->getName();
                $actionData[0]['sku'] = $product->getSku();
                $actionData[0]['price'] = $product->getPrice();
                $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                $actionData[0]['specialPrice'] = $product->getFinalPrice();
                $actionData[0]['status'] = $product->getStatus();
                $actionData[0]['productPictureUrl'] = $product->getImageUrl();
                $actionData[0]['pageUrl'] = $product->getProductUrl();

                $actionData[0]['weight'] = $product->getWeight();
                $actionData[0]['stockAvailability'] = $stock_data ? $stock_data : 2;
                $actionData[0]['size'] = false;
                $actionData[0]['color'] = false;
                $actionData[0]['qty'] = false;
                $actionData[0]['category'] = $cateHolder;
                $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $actionDescription = array(
                    'action' => 'wishlist',
                    'email' => $this->getCustomerIdentity(),
                    'products' => $actionData
                );
                $res = $this->amplify->customer_action($actionDescription);
            }
        } catch (Exception $ex) {
            
        }
    }

    /**
     * @author Dharmendra Rai
     * @desc verify key and secret while saving them
     */
    public function getAmplifyOrderSuccessPageView(Varien_Event_Observer $evnt) {
        try {
            if ($this->verified) {

                $order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
                $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);


                $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
                if ($customerAddressId) {
                    $customer = Mage::getModel('customer/address')->load($customerAddressId);

                    if (is_object($customer)) {
                        $person['firstname'] = $customer->getFirstname();
                        $person['lastname'] = $customer->getLastname();

                        $person['postcode'] = $customer->getPostcode();
                        $person['telephone'] = $customer->getTelephone();
                        $person['fax'] = $customer->getfax();
                        $person['customerId'] = $customer->getCustomerId();
                        $person['company'] = $customer->getCompany();
//     $person['region'] = $customer->getRegion();
                        $person['street'] = $customer->getStreetFull();
                    }
                    $person = array_filter($person);
                    $res = $this->amplify->update($email, $person);
                } else {
                    $customer = $order->getShippingAddress();
                    if (is_object($customer)) {
                        $person['firstname'] = $customer->getFirstname();
                        $person['lastname'] = $customer->getLastname();

                        $person['postcode'] = $customer->getPostcode();
                        $person['telephone'] = $customer->getTelephone();
                        $person['fax'] = $customer->getfax();
                        $person['customerId'] = $customer->getCustomerId();
                        $person['company'] = $customer->getCompany();
//     $person['region'] = $customer->getRegion();
                        $person['street'] = $customer->getStreetFull();
                    }
                }



                $items = $order->getAllVisibleItems();
                
                $itemcount = count($items);
                $name = array();
                $unitPrice = array();
                $sku = array();
                $ids = array();
                $qty = array();
                $i = 0;
                $actionData = array();

                foreach ($items as $itemId => $item) {
                   $product = $item;
                  
                    $product = Mage::getModel('catalog/product')->load($product->getProductId());
                    $categoryIds = $product->getCategoryIds();
                    $cateHolder = array();
                    
                    foreach ($categoryIds as $cat) {
                    $cateName = Mage::getModel('catalog/category')->load($cat['entity_id']);
                    $name=$cateName->getName();
                    $id=$cateName->getEntityId();
                    $pid=$cateName->getParent_id();
                    if($pid==1){
                        $pid=0;
                    }
                    $cateHolder[$id] = array("n"=>$name,"p"=>$pid);
                   }
                    
                    $actionData[$i]['productId'] = $product->getId();
                    $actionData[$i]['productTitle'] = $product->getName();
                    $actionData[$i]['sku'] = $product->getSku();
                    $actionData[$i]['price'] = $product->getPrice();
                    $actionData[$i]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                    $actionData[$i]['specialPrice'] = $product->getFinalPrice();
                    $actionData[$i]['status'] = $product->getStatus();
                    $actionData[$i]['productPictureUrl'] = $product->getImageUrl();
                    $actionData[$i]['pageUrl'] = $product->getProductUrl();
                    $actionData[$i]['weight'] = $product->getWeight();
                    $actionData[$i]['stockAvailability'] = $stock_data ? $stock_data : 2;
                    $actionData[$i]['size'] = $product->getResource()->getAttribute('size') ? $product->getAttributeText('size') : false;
                    $actionData[$i]['color'] = $product->getResource()->getAttribute('color') ? $product->getAttributeText('color') : false;
                    $actionData[$i]['brandName'] = $product->getResource()->getAttribute('manufacturer') ? $product->getAttributeText('manufacturer') : false;
                    $actionData[$i]['qty'] = (int) $item->getQtyOrdered();
                    $actionData[$i]['category'] = $cateHolder;
                    $actionData[$i]['orderId'] = $order_id;
                    $actionData[$i]['totalProductPrice']=$item->getRowTotal()-$item->getDiscountAmount();
//                    $actionData[$i]['couponCode'] = Mage::getSingleton('checkout/session')->getQuote()->getCouponCode() ;
                    $actionData[$i]['discountPrice'] = $item->getDiscountAmount();
                    $i++;
                }

                $cart = Mage::getSingleton('checkout/cart');
                $TotalPrice = $order->getGrandTotal();
                $totalShippingPrice = $order->getShippingAmount();
                $TotalPrice = $TotalPrice;
                $subTotalPrice = $order->getSubtotal();
                $orderInfo["subtotalPrice"] = $subTotalPrice-abs($order->getDiscountAmount());
                $orderInfo["totalPrice"] = $TotalPrice;
                $orderInfo["totalShippingPrice"] = $totalShippingPrice;
                $orderInfo['orderId'] = $order_id;
                $orderInfo['promocode'] = $order->getCouponCode();
                $orderInfo['totalDiscount'] = abs($order->getDiscountAmount());
                $orderInfo['DiscountPer'] = abs($order->getDiscountPercent());
                $orderInfo['DiscountDesc'] = $order->getDiscountDescription();
                $orderInfo['currency'] = $order->getOrderCurrencyCode();
                $orderInfo['financialStatus'] = 'paid';
                $orderInfo['abandonedCheckoutUrl'] = Mage::getUrl('checkout/cart');
                $orderInfo['totalTaxes'] = $order->getShippingTaxAmount();

//                $orderInfo['totalQty'] = $order->totalQty();
                $actionDescription = array(
                    'action' => 'purchased',
                    'email' => $this->getCustomerIdentity(),
                    'or' => $orderInfo,
                    'pd' => $actionData
                );
              
          $res = $this->amplify->customer_action($actionDescription);
               
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplify_checkout_allow_guest($evnt) {
        try {
            if ($this->verified) {
              
                $getquote = $evnt->getQuote();
                $data = array_filter($getquote->getData());
                Mage::getModel('core/cookie')->set('amplify_email', $data['customer_email']);
                $person = array();
                $person['webId'] = Mage::app()->getWebsite()->getId();
                $person['storeId'] = Mage::app()->getStore()->getId();
                $person = array_filter($person);
                $this->amplify->identify($data['customer_email'], $data['customer_firstname']);
                $res = $this->amplify->add($data['customer_email'], $person, 1);
            }
        } catch (Exception $ex) {
            
        }
    }

   public function getAmplifyCatalog_product_save_after($evnt) {

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
                    $name=$cateName->getName();
                    $id=$cateName->getEntityId();
                    $pid=$cateName->getParent_id();
                    if($pid==1){
                        $pid=0;
                    }
                    $cateHolder[$id] = array("n"=>$name,"p"=>$pid);
                }
              
               
                $event = $evnt->getEvent();
                $action = $event->getControllerAction();
                $stock_data = $product->getIs_in_stock();
                $actionData = array();
                $actionData[0]['productId'] = $product->getId();
                $actionData[0]['productTitle'] = $product->getName();
                $actionData[0]['sku'] = $product->getSku();
                $actionData[0]['price'] = $product->getPrice();
                $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                $actionData[0]['specialPrice'] = $product->getFinalPrice();
                $actionData[0]['status'] = $product->getStatus();
                $actionData[0]['productPictureUrl'] = $product->getImageUrl();
                $actionData[0]['pageUrl'] = $product->getProductUrl();
                $actionData[0]['weight'] = $product->getWeight();
                $actionData[0]['stockAvailability'] = $stock_data ? $stock_data : 2;
                $actionData[0]['size'] = false;
                $actionData[0]['color'] = false;
                $actionData[0]['qty'] = 1;
                $actionData[0]['category'] = $cateHolder;
                $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $actionDescription = array(
                    'action' => 'viewed',
                    "email" => $this->getCustomerIdentity(),
                    'pd' => $actionData
                );
              
                $res = $this->amplify->customer_action($actionDescription);
                
            }
        } catch (Exception $ex) {
            
        }
    }

   public function wishlistShare(Mage_Framework_Event_Observer $evnt) {

  }
    public function getAmplifySendfriendProduct(Varien_Event_Observer $evnt) {

    }

    public function catalogProductCompareAddProduct(Varien_Event_Observer $evnt) {

    }
    public function getAmplifyCustomerAdressSaveAfter($evnt) {
       
    }

    public function getAmplifySales_order_save_commit_after($observer) {
  
    }

    /**
     * @param Varien_Event_Observer $observer
     * @author Dharam <dharmendra@socialcrawler.in>
     *
     */
    public function getAmplify_cartUpdate(Varien_Event_Observer $observer) {
        try {
            if ($this->verified) {
             $i=0;
             $subdiff=0;
             $actionData = array();
            foreach ($observer->getCart()->getQuote()->getAllVisibleItems() as $product) {
                
              if ($product->hasDataChanges()) {
                 // print_r($product);
                $productId = $product->getProductId();
                $catCollection = $product->getCategoryCollection();
                 
                $categs = array();//$catCollection->exportToArray();
                $cateHolder = array();
               
                $productName = $product->getName();
                $sku = $productName . "_" . $product->getSku();
                $qty = $product->getPrice();
                $stock_data = $product->getIs_in_stock();
            
                $actionData[$i]['productId'] = $product->getProductId();
                $actionData[$i]['productTitle'] = $product->getName();
                $actionData[$i]['sku'] = $product->getSku();
                $actionData[$i]['price'] = $product->getPrice();
                $actionData[$i]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                $actionData[$i]['specialPrice'] = $product->getPrice();
                $actionData[$i]['status'] = $product->getStatus();
                $actionData[$i]['productPictureUrl'] = $product->getImageUrl();
                $actionData[$i]['pageUrl'] = $product->getProductUrl();
                $actionData[$i]['weight'] = $product->getWeight();
                $oldQty=(int)$product->getOrigData('qty');
                $newQty=(int) $product->getQty();
                $qtyDiff=0;
                $subdiff=$subdiff+($newQty-$oldQty)*$product->getPrice();
               
                $actionData[$i]['qty'] = (int) $product->getQty();
                $actionData[$i]['category'] = $cateHolder;
                $i++;
                }
              }
                $cart = Mage::getSingleton('checkout/cart');
                $subTotalPrice = $cart->getQuote()->getGrandTotal();
                $orderInfo["subtotalPrice"] = $subTotalPrice+$subdiff;
                $orderInfo['abandonedCheckoutUrl'] = Mage::getUrl('checkout/cart');
                $orderInfo['currency']=Mage::app()->getStore()->getBaseCurrencyCode();
                $actionDescription = array(
                    'or' => $orderInfo,
                    'email' => $this->getCustomerIdentity(),
                    'action' => 'update_cart',
                    'pd' => $actionData
                );
               
               $res = $this->amplify->customer_action($actionDescription);
                       
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyCancelOrderItem($observer) {

    }

    public function sendData() {
    }

}