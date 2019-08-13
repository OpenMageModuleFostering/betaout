<?php

require_once 'Amplify.php';
require_once('app/Mage.php');

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
    const MAIL_TO = 'raijiballia@gmail.com';
    const MAIL_SUB = 'Magento Error Reporter';
    const XML_PATH_MAX_RUNNING_TIME = 'system/cron/max_running_time';
    const XML_PATH_EMAIL_TEMPLATE = 'system/cron/error_email_template';
    const XML_PATH_EMAIL_IDENTITY = 'system/cron/error_email_identity';
    const XML_PATH_EMAIL_RECIPIENT = 'system/cron/error_email';

    public function __construct($key_string) {
        try {
            $this->key = Mage::getStoreConfig(self::XML_PATH_KEY);
            $this->secret = Mage::getStoreConfig(self::XML_PATH_SECRET);
            $this->projectId = Mage::getStoreConfig(self::XML_PATH_PROJECTID);
            $this->verified = Mage::getStoreConfig('betaout_amplify_options/settings/amplify_verified');
            $this->amplify = new Amplify($this->key, $this->secret, $this->projectId);
//            $this->_process_date = Mage::getStoreConfig('betaout_amplify_options/settings/_process_date');
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyCheckOrderStatus($observer) {

        try {
            if ($this->verified && is_object($observer)) {
                $order = $observer->getEvent()->getOrder();


//       $order = Mage::getModel('sales/order')->load($orderId);
                $order = $observer->getEvent()->getOrder();
                $state = $observer->getEvent()->getState();
                $status = $observer->getEvent()->getStatus();
                $orderId = $order->getIncrement_id();
                $this->amplify->update_order($orderId, $state);
                $items = $order->getAllItems();
                foreach ($items as $itemId => $item) {
                    $product = $item;

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
                    $actionData[0]['state'] = $observer->getEvent()->getState();
                    $actionData[0]['size'] = false;
                    $actionData[0]['color'] = false;
                    $actionData[0]['qty'] = $product->getQtyOrdered();
                    $actionData[0]['category'] = $categoryName;
                }
                $actionDescription = array(
                    'action' => 'completed',
                    'email' => $this->getCustomerIdentity(),
                    'products' => $actionData
                );
                if ($order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE)
                    $res = $this->amplify->customer_action($actionDescription);
                if ($order->getState() == Mage_Sales_Model_Order::STATE_CLOSED) {
                    $actionDescription['action'] = 'Closed';
                    $res = $this->amplify->customer_action($actionDescription);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyConfigChangeObserver($evnt) {
        if (!($this->key && $this->secret && $this->projectId)) {
            $this->key = Mage::getStoreConfig(self::XML_PATH_KEY);
            $this->secret = Mage::getStoreConfig(self::XML_PATH_SECRET);
            $this->projectId = Mage::getStoreConfig(self::XML_PATH_PROJECTID);
            $this->amplify = new Amplify($this->key, $this->secret, $this->projectId);
        }
        $result = $this->amplify->verify();
        if ($result['responseCode'] == 200) {
            if (!Mage::getStoreConfig('betaout_amplify_options/settings/beta_start_date')) {
                Mage::getModel('core/config')->saveConfig('betaout_amplify_options/settings/beta_start_date', gmdate('Y-m-d H:i:s'));
                Mage::getModel('core/config')->saveConfig('betaout_amplify_options/order/cron_setting', '*/5 * * * *');
                Mage::getModel('core/config')->saveConfig('betaout_amplify_options/settings/_process_date', gmdate('Y-m-d H:i:s'));
            }
            Mage::getModel('core/config')->saveConfig('betaout_amplify_options/settings/amplify_verified', TRUE);
        } else {
            Mage::getModel('core/config')->saveConfig('betaout_amplify_options/settings/amplify_verified', false);
            throw new Mage_Core_Exception("Configuration could not be saved. Check your key and secret.$result");
        }
    }

    public function getAmplifyEventRemoveFromCart(Varien_Event_Observer $observer) {
        try {
            if ($this->verified && is_object($observer)) {
                $product = $observer->getEvent()->getQuote_item();

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
                $actionData[0]['size'] = false;
                $actionData[0]['color'] = false;
                $actionData[0]['qty'] = false;
                $actionData[0]['category'] = $categoryName;
                $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $cart = Mage::getSingleton('checkout/cart');
                $subTotalPrice = $cart->getQuote()->getGrandTotal();
                $orderInfo["subtotalPrice"] = $subTotalPrice;
                $actionDescription = array(
                    'action' => 'removed_from_cart',
                    'email' => $this->getCustomerIdentity(),
                    'cartInfo' => $orderInfo,
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
                    $cateName = Mage::getModel('catalog/category')->load($cat['entity_id'])->getName();
                    $cateHolder[] = $cateName;
                }
                $categoryName = implode(",", $cateHolder);


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
                $actionData[0]['category'] = $categoryName;
                $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $subTotalPrice = $cart->getQuote()->getGrandTotal();
                $orderInfo["subtotalPrice"] = $subTotalPrice;
                $orderInfo['abandonedCheckoutUrl'] = Mage::getUrl('checkout/cart');
                $actionDescription = array(
                    'cartInfo' => $orderInfo,
                    'email' => $this->getCustomerIdentity(),
                    'action' => 'add_to_cart',
                    'products' => $actionData
                );

//                $startTime = microtime(true);
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
                    $cateName = Mage::getModel('catalog/category')->load($cat['entity_id'])->getName();
                    $cateHolder[] = $cateName;
                }
                $categoryName = implode(",", $cateHolder);
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
                $actionData[0]['category'] = $categoryName;
                $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $actionDescription = array(
                    'action' => 'reviewed',
                    'email' => $this->getCustomerIdentity(),
                    'products' => $actionData
                );
                $res = $this->amplify->customer_action($actionDescription);
//                $this->event('product_reviewed', $actionDescription);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventVote($evnt) {
        try {
            if ($this->verified) {

                $vote = $evnt->getVote()->getData();
                $poll = $evnt->getPoll()->getData();

                $this->event('polled_voted', array('action' => 'polled_voted',
                    'poll_title' => $poll['poll_title'],
                    'poll_answer_id' => $vote['poll_answer_id'],
                    'unique_id' => $this->getCustomerIdentity()));
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventCustomerLogout($evnt) {
        try {
            if ($this->verified) {

                $this->event('customer_logout', array('action' => 'logout',
                    'unique_id' => $this->getCustomerIdentity()));
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
                    $this->amplify->identify($this->getCustomerIdentity(), $custName);
                } catch (Exception $ex) {
                    
                }
                $this->event('customer_login', array('action' => 'login',
                    'unique_id' => $this->getCustomerIdentity()));



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
                }
                $person = array_filter($person);
                $res = $this->amplify->update($email, $person);

//$customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
//    if ($customerAddressId){
//        $address = Mage::getModel('customer/address')->load($customerAddressId);
//    }
////               
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventNewsletter($evnt) {
        try {
            if ($this->verified) {

                $subscriber = $evnt->getEvent()->getSubscriber();

                if ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {

                    $this->event('subscribed_to_newsletter', array('action' => 'subscribed_to_newsletter', 'unique_id' => $this->getCustomerIdentity()));
                } elseif ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {

                    $this->event('unsubscribed_from_newsletter', array('action' => 'unsubscribed_from_newsletter', 'unique_id' => $this->getCustomerIdentity()));
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getCustomerIdentity() {
        try {

            if (Mage::getSingleton('customer/session')->isLoggedIn()) {

                $c = Mage::getSingleton('customer/session')->getCustomer();
                $customer = Mage::getModel('customer/customer')->load($c->getId());


                $email = $customer->getEmail();
            } else {
                $email = Mage::getModel('core/cookie')->get('amplify_email');
            }
            $this->amplify->identify($email);
            return $email;
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventOrder($evnt) {
        try {
            if ($this->verified) {
                $this->event('place_order_clicked');
                return 1;
                $order = $evnt->getEvent()->getOrder();
                $quote = $order->getQuote();
//
                $customer_id = $order->getCustomerId();
                $customer = Mage::getModel('customer/customer')->load($customer_id);
                $customer_email = $customer->getEmail();

                $skus = array();
                foreach ($order->getItemsCollection() as $item) {

                    $product_id = $item->getProductId();

                    $product = Mage::getModel('catalog/product')
                            ->setStoreId(Mage::app()->getStore()->getId())
                            ->load($product_id);

                    $skus[] = $product->getSku();
                }
                $order_date = $quote->getUpdatedAt();
                $order_date = str_replace(' ', 'T', $order_date);

                $revenue = $quote->getBaseGrandTotal();

                $person = array();
                $person = $this->getCustomereventInfo($customer);
                $additional = array();
                $additional['lastOrder'] = $order_date;
                $additional['skus'] = $skus;
                $this->eventPerson($person, $additional);
                $this->event_revenue($customer_email, $revenue);
                $skus = 0;
                $this->event('place_order_clicked', array('skus' => $skus,
//                    'order_date' => $order_date,
//                    'order_total' => $revenue,
//                    'unique_id' => $this->getCustomerIdentity()
                ));
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getamplifyEventCustomerSave($evnt) {
        try {
            return 1;
            if ($this->verified) {
                $c = Mage::getSingleton('customer/session')->getCustomer();
                $customer = Mage::getModel('customer/customer')->load($c->getId());
                $customer = $customer->getAddresses();

                if (is_array($customer)) {
                    $ekey = array_keys($customer);
                    $customer = $customer[$ekey[0]];
                }
                $person['firstname'] = $customer->getFirstname();
                $person['lastname'] = $customer->getLastname();

                $person['postcode'] = $customer->getPostcode();
                $person['telephone'] = $customer->getTelephone();
                $person['fax'] = $customer->getfax();
                $person['regionId'] = $customer->getRegionId();
                $street = $customer->getStreet();
                $person['street'] = $street[0];
                $person['customer_id'] = $customer->getCustomer_id();
                $res = $this->amplify->update($customer->getEmail(), $person);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getCustomereventInfo($customer) {
        try {
            if ($this->verified) {

                $person = array();
                $person['email'] = $customer->getEmail();
                $person['first_name'] = $customer->getFirstname();
                $person['last_name'] = $customer->getLastname();
                $person['created'] = $customer->getCreatedAt();
                $person['unique_id'] = $customer->getEmail();

                return $person;
            }
        } catch (Exception $ex) {
            
        }
    }

    public function eventCustomerFromCheckout($evnt) {
        try {
            if ($this->verified) {

                $order = $evnt->getEvent()->getOrder();
                $quoteId = $order->getQuoteId();
                $quote = Mage::getModel('sales/quote')->load($quoteId);

                $method = $quote->getCheckoutMethod(true);

                if ($method == 'register') {

                    $customer_id = $order->getCustomerId();
                    $customer = Mage::getModel('customer/customer')->load($customer_id);
                    $customer_email = $customer->getEmail();

                    $person = array();
                    $person = $this->getCustomereventInfo($customer);
//                    $this->eventPerson($person);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyEventCustomerRegisterSuccess($evnt) {
        try {
            if ($this->verified) {

                $customer = $evnt->getCustomer();

                $customer_email = $customer->getEmail();
//            $this->alias($customer_email);
//

                $person = array();
                $person = $this->getCustomereventInfo($customer);
                $this->amplify->event($customer_email, array("create_account" => false));
                $this->amplify->identify($person['email'], $person['first_name']);
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
                    $this->amplify->event($this->getCustomerIdentity(), $params);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function eventPerson($person, $additional = array()) {
        try {
            if ($this->verified) {

                $params = array();
                $params['email'] = $person['email'];
                $params['firstName'] = $person['first_name'];
                $params['lastName'] = $person['last_name'];
//            $params['created'] = $person['created'];
//            $params['ip'] = $_SERVER['REMOTE_ADDR'];

                if (!empty($additional)) {
                    foreach ($additional as $key => $value) {
                        $params[$key] = $value;
                    }
                }
//        $custid = Mage::getModel('betaout_amplify/key');
//        $identity = $custid->getCustomerIdentity();
                if ($this->verified) {
//                    $this->amplify->identify($person['email'], $person['first_name']);
//                    $this->amplify->add($person['email'], $params);
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function event_revenue($identifier, $revenue) {
        try {
            if ($this->verified) {

                $params = array(
                    'time' => date('Y-m-d') . '-T' . date('H:i:s'),
                    'GMV' => (float) $revenue,
                    'unique_id' => $identifier
                );
                $event_revenue_att = array(
                    "event_revenue_transactions" => $params
                );
//         $amplify = new Amplify($this->key, $this->secret, $this->projectId);
                if ($this->verified)
                    $res = $this->amplify->event($this->getCustomerIdentity(), $event_revenue_att);
            }
        } catch (Exception $ex) {
            
        }
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
                    $cateName = Mage::getModel('catalog/category')->load($cat['entity_id'])->getName();
                    $cateHolder[] = $cateName;
                }
                $categoryName = implode(",", $cateHolder);

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
                $actionData[0]['category'] = $categoryName;
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
                        $cateName = Mage::getModel('catalog/category')->load($cat)->getName();
                        $cateHolder[] = $cateName;
                    }
                    $categoryName = implode(",", $cateHolder);
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
                    $actionData[$i]['category'] = $categoryName;
                    $actionData[$i]['orderId'] = $order_id;
//                    $actionData[$i]['couponCode'] = Mage::getSingleton('checkout/session')->getQuote()->getCouponCode() ;
                    $actionData[$i]['discount'] = $item->getDiscountAmount();
//                    $actionData[$i]['discount'] = $item->getBaseDiscountAmount();
                    $i++;
                }

                $cart = Mage::getSingleton('checkout/cart');
                $TotalPrice = $order->getGrandTotal();
                $totalShippingPrice = $order->getShippingAmount();
                $subTotalPrice = $TotalPrice - $totalShippingPrice;
                $subTotalPrice = $order->getSubtotal();
                $orderInfo["subtotalPrice"] = $subTotalPrice;
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
                    'cartInfo' => $orderInfo,
                    'products' => $actionData
                );
                $res = $this->amplify->customer_action($actionDescription);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplify_checkout_allow_guest($evnt) {
        try {
            if ($this->verified) {
//                $mageObj = Mage::getSingleton('core/session');
//                $visitor_data = $mageObj->visitor_data;

                $getquote = $evnt->getQuote();
//                $checkout = Mage::getSingleton('checkout/type_onepage');
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
        try {
            if ($this->verified) {

                $event = $evnt->getEvent();
                $product = $event->getProduct();
                $cat_id = $product->getCategory_ids();
                $cateHolder = array();
                foreach ($cat_id as $id) {
                    $category = Mage::getModel('catalog/category')->load($id);
                    $cateHolder[] = $category->getName();
                }
                $categoryName = implode(",", $cateHolder);
                $productName = $product->getName();
                $sku = $product->getsku();
                $price = $product->getPrice();
                $pr = $product->getData();
                $stock_data = $product->getStock_data();

                $productDeatails = array(
                    'productId' => $product->getId(),
                    'productTitle' => $product->getName(),
                    'sku' => $product->getsku(),
                    'price' => $product->getPrice(),
                    'currency' => Mage::app()->getStore()->getBaseCurrencyCode(),
                    'specialPrice' => $product->getPrice(),
                    'status' => $product->getStatus(),
//            'description' => $product->getDescription(),
                    'productPictureUrl' => $product->getImage() ? Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage()) : '',
                    'pageUrl' => $product->getProductUrl(),
                    'qty' => $stock_data['qty'],
                    'stockAvailability' => $stock_data['is_in_stock'] == 1 ? $stock_data['is_in_stock'] : 2,
                    'category' => $categoryName
                );

                $res = $this->amplify->product_add($productDeatails);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyCatalog_product_delete_after_done($evnt) {
        try {

            if ($this->verified) {

                $event = $evnt->getEvent();
                $product = $event->getProduct();
                $sku = $product->getsku();
                $this->amplify->product_delete($sku);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyCatalog_product_edit_action($evnt) {
        try {

            if ($this->verified) {

                $event = $evnt->getEvent();
                $product = $event->getProduct();
                $pr = $product->getData();
                $stock_data = $product->getStock_data();
                $productDeatails = array(
                    'productId' => $product->getId(),
                    'productTitle' => $product->getName(),
                    'price' => $product->getPrice(),
                    'status' => $product->getStatus(),
//            'description' => $product->getDescription(),
                    'productPictureUrl' => $product->getImageUrl(),
                    'pageUrl' => $product->getProductUrl(),
//            'qty' => $stock_data['qty'],
                );
                $sku = $product->getsku();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifyCatalogProductView(Varien_Event_Observer $evnt) {
        try {

            if ($this->verified) {


                $product = $evnt->getEvent()->getProduct();
                $catCollection = $product->getCategoryCollection();
                $categs = $catCollection->exportToArray();
                $cateHolder = array();
                foreach ($categs as $cat) {
                    $cateName = Mage::getModel('catalog/category')->load($cat['entity_id'])->getName();
                    $cateHolder[] = $cateName;
                }
                $categoryName = implode(",", $cateHolder);
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
                $actionData[0]['qty'] = false;
                $actionData[0]['category'] = $categoryName;
                $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $actionDescription = array(
                    'action' => 'viewed',
                    "email" => $this->getCustomerIdentity(),
                    'products' => $actionData
                );
//              $startTime = microtime(true);
                $res = $this->amplify->customer_action($actionDescription);
//                $endTime = microtime(true);
//                echo "total Execution time ==" . ($endTime - $startTime);
            }
        } catch (Exception $ex) {
            
        }
    }

//    public function wishlistShare(Mage_Framework_Event_Observer $evnt) {
//        $wishListItemCollection = $evnt->getEvent()->getWishlist()->getItemCollection();
//        if (count($wishListItemCollection)) {
//            $arrProductIds = array();
//
//            foreach ($wishListItemCollection as $item) {
//                /* @var $product Mage_Catalog_Model_Product */
//                    $product = $item->getProduct();
////                    $arrProductIds[] = $product->getId();
//                $stock_data = $product->getIs_in_stock();
//                $actionDescription = array(
//                    'action' => 'share',
//                    'productTitle' => $product->getName(),
//                    'sku' => $product->getsku(),
//                    'price' => $product->getPrice(),
//                    'currency' => Mage::app()->getStore()->getBaseCurrencyCode(),
//                    'specialPrice' => $product->getFinalPrice(),
//                    'status' => $product->getStatus(),
////                    'description' => $product->getDescription(),
//                    'productPictureUrl' => $product->getImage(),
//                    'pageUrl' => $product->getUrl_key(),
//                    'qty' => false,
//                    'stockAvailability' => $stock_data ? $stock_data : 2,
//                    'size' => false,
//                    'color' => false
//                );
//                $res = $this->amplify->customer_action($actionDescription);
//            }
//        }
//         $product = $evnt->getItemCollection();
//        $product=Mage::registry('wishlist')->getProductCollection();
//    }
    public function getAmplifySendfriendProduct(Varien_Event_Observer $evnt) {
        try {
            if ($this->verified) {

                $product = $evnt->getProduct();
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
                $actionData[0]['category'] = $categoryName;
                $actionData[0]['discount'] = abs($product->getPrice() - $product->getFinalPrice());
                $actionDescription = array(
                    'action' => 'shared',
                    'email' => $this->getCustomerIdentity(),
                    'products' => $actionData
                );
                $this->amplify->customer_action($actionDescription);
            }
        } catch (Exception $ex) {
            
        }
    }

//    public function catalogProductCompareAddProduct(Varien_Event_Observer $evnt) {
//        if ($this->verified) {
//            $productId = $evnt->getEvent()->getProduct();
//            $product = $evnt->getProduct();
//            $stock_data = $product->getIs_in_stock();
//            $actionDescription = array(
//                'action' => 'add_to_cart',
//                'productTitle' =>$product->getName(),
//                'sku' => $product->getsku(),
//                'price' => $product->getPrice(),
//                'currency' => Mage::app()->getStore()->getBaseCurrencyCode(),
//                'specialPrice' => $product->getFinalPrice(),
//                'status' => $product->getStatus(),
//                'description' => $product->getDescription(),
//                'productPictureUrl' => $product->getImage(),
//                'pageUrl' => $product->getUrl_key(),
//                'qty' => false,
//                'stockAvailability' => $stock_data ? $stock_data : 2,
//                'size' => false,
//                'color' => false
//            );
//            $res = $this->amplify->customer_action($actionDescription);
//        }
//    }

    public function getAmplifyCustomerAdressSaveAfter($evnt) {
        return true;
        try {

            if ($this->verified) {
                $data = $evnt->getCustomer_address()->getData();
                $propetyArray = array(
                    'lastName' => $data['lastname'],
                    "telephone" => $data['telephone'],
                    "company" => $data['company'],
                    "region" => $data['region'],
                    "street" => $data['street']
                );
                $this->amplify->event($this->getCustomerIdentity(), array("account_edit" => false));
                $this->amplify->update($this->getCustomerIdentity(), $propetyArray);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function getAmplifySales_order_save_commit_after($observer) {

        try {
            if ($this->verified) {
                $order = $observer->getEvent()->getOrder();
                $state = $observer->getEvent()->getState();
                $status = $observer->getEvent()->getStatus();
                $order = $observer->getOrder();
                $items = $order->getAllItems();
                foreach ($items as $itemId => $item) {
                    $product = $item;
                    $actionData = array();
                    $actionData[0]['productId'] = $product->getId();
                    $actionData[0]['productTitle'] = $product->getName();
                    $actionData[0]['sku'] = $product->getSku();
                    $actionData[0]['price'] = $product->getPrice();
                    $actionData[0]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                    $actionData[0]['specialPrice'] = $product->getFinalPrice();
                    $actionData[0]['status'] = $product->getStatus();
//                $actionData[0]['productPictureUrl'] = $product->getImageUrl();
//                $actionData[$i]['pageUrl'] = $product->getProductUrl();
                    $actionData[0]['size'] = false;
                    $actionData[0]['color'] = false;
                    $actionData[0]['qty'] = false;
                    $actionData[0]['category'] = $categoryName;
                    $actionDescription = array(
                        'action' => 'viewed',
                        'email' => $this->getCustomerIdentity(),
                        'products' => $actionData
                    );
//                    $res = $this->amplify->customer_action($actionDescription);
                }

                if ($order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE) {
                    
                }
            }
        } catch (Exception $ex) {
            
        }
    }

// checkout_cart_update_items_after

    /**
     * @param Varien_Event_Observer $observer
     * @author Dharam <dharmendra@socialcrawler.in>
     *
     */
    public function getAmplify_cartUpdate(Varien_Event_Observer $observer) {
        $i = 0;
        $actionData = array();
        foreach ($observer->getCart()->getQuote()->getAllVisibleItems() as $product /* @var $item Mage_Sales_Model_Quote_Item */) {
            if ($product->hasDataChanges()) {
                $pr = $product;
                $product = $product->getProduct();

                $actionData[$i]['productId'] = $product->getId();
                $actionData[$i]['productTitle'] = $product->getName();
                $actionData[$i]['sku'] = $product->getSku();
                $actionData[$i]['price'] = $product->getPrice();
                $actionData[$i]['currency'] = Mage::app()->getStore()->getBaseCurrencyCode();
                $actionData[$i]['specialPrice'] = $product->getPrice();
                $actionData[$i]['status'] = $product->getStatus();
                $actionData[$i]['productPictureUrl'] = $product->getImageUrl();
                $actionData[$i]['pageUrl'] = $product->getProductUrl();
                $actionData[$i]['qty'] = $pr->getQty();
                $actionData[$i]['discount'] = ($product->getPrice() - $product->getFinalPrice());
                $actionDescription = array(
                    'action' => 'update_cart',
                    'email' => $this->getCustomerIdentity(),
                    'products' => $actionData
                );

                $i++;
            }
        }
        $res = $this->amplify->customer_action($actionDescription);
    }

    public function getAmplifyCancelOrderItem($observer) {
        $item = $observer->getEvent()->getItem();
        $orderId = $item->getOrderId();
        if ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            $order_id = $order->getIncrementId();
        }
        $children = $item->getChildrenItems();
        $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();

        if ($item->getId() && ($productId = $item->getProductId()) && empty($children) && $qty) {
            Mage::getSingleton('cataloginventory/stock')->backItemQty($productId, $qty);
        }
        $state = "cancel";
        $this->amplify->update_order($order_id, $state);
        return $this;
    }

    public function sendData() {
        Mage::app()->getCacheInstance()->cleanType('config');
        $sendOrderFlag = Mage::getStoreConfig(self::XML_PATH_SEND_ORDER_STATUS);
        if ($sendOrderFlag && $this->verified) {
//            $startTime = microtime(true);
            $processDate = Mage::getStoreConfig('betaout_amplify_options/settings/_process_date');
            $orders = Mage::getModel('sales/order')->getCollection()
//                         ->addAttributeToSelect(array('increment_id',base_grand_total))
                    ->addAttributeToSelect('customer_email')
                    ->addAttributeToSelect('customer_firstname')
                    ->addAttributeToSelect('customer_lastname')
                    ->addAttributeToSelect('shipping_description')
                    ->addAttributeToSelect('order_currency_code')
                    ->addAttributeToSelect('increment_id')->
                    addAttributeToSelect('grand_total')->
                    addAttributeToSelect('subtotal')->
                    addAttributeToSelect('remote_ip')->
                    addAttributeToSelect('store_id')->
                    addAttributeToSelect('discount_amount')->
                    addAttributeToSelect('coupon_code')->
//                        addAttributeToSelect('total_qty_ordered')->
                    addAttributeToSelect('shipping_address_id')->
                    addAttributeToSelect('billing_address_id')->
                    addAttributeToSelect('created_at')->
                    addAttributeToSelect('shipping_incl_tax')->
                    addAttributeToSelect('created_at')
                    ->addAttributeToSelect('status')
                    ->addAttributeToSort('created_at', 'DESC')
                    ->addAttributeToFilter('created_at', array('lt' => $processDate, 'date' => true))
                    ->addAttributeToFilter('status', array('eq' => Mage_Sales_Model_Order::STATE_COMPLETE))
//                        ->addAttributeToFilter('created_at', array('from' => $fromDate, 'to' => $toDate))
                    ->setPageSize(10);

//            $pages = $orders->getLastPageNumber();
            $currentPage = 1;
//                if ($currentPage >= $pages) {
//                    $processDate = gmdate('Y-m-d H:i:s', strtotime($processDate, '-1 days'));
//                }
//                do {
            $orders->setCurPage($currentPage);
            $orders->load();

            $count = count($orders);
            if ($count <= 0)
                Mage::getModel('core/config')->saveConfig('betaout_amplify_options/order/cron_setting', $this->_schedule);


            foreach ($orders as $order) {
                $order_id = $order->getIncrementId();
                $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
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
                        $cateName = Mage::getModel('catalog/category')->load($cat)->getName();
                        $cateHolder[] = $cateName;
                    }
                    $categoryName = implode(",", $cateHolder);
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
                    $actionData[$i]['category'] = $categoryName;
//                    $actionData[$i]['couponCode'] = Mage::getSingleton('checkout/session')->getQuote()->getCouponCode() ;
                    $actionData[$i]['discount'] = $item->getDiscountAmount();
//                    $actionData[$i]['discount'] = $item->getBaseDiscountAmount();
                    $i++;
                }

                $cart = Mage::getSingleton('checkout/cart');
                $TotalPrice = $order->getGrandTotal();
                $totalShippingPrice = $order->getShippingAmount();
                $subTotalPrice = $TotalPrice - $totalShippingPrice;
                $subTotalPrice = $order->getSubtotal();
                $orderInfo["subtotalPrice"] = $subTotalPrice;
                $orderInfo["totalPrice"] = $TotalPrice;
                $orderInfo["totalShippingPrice"] = $totalShippingPrice;
                $orderInfo['orderId'] = $order_id;
                $orderInfo['ip'] = $order->getRemoteIp();
                $orderInfo['createdTime'] = $processDate = $order->getCreatedAt();
                $orderInfo['promocode'] = $order->getCouponCode();
                $orderInfo['totalDiscount'] = abs($order->getDiscountAmount());
//                        $orderInfo['DiscountPer'] = abs($order->getDiscountPercent());
//                        $orderInfo['DiscountDesc'] = $order->getDiscountDescription();
                $orderInfo['currency'] = $order->getOrderCurrencyCode();
//                        $orderInfo['abandonedCheckoutUrl'] = Mage::getUrl('checkout/cart');
                $orderInfo['totalTaxes'] = $order->getShippingTaxAmount();
                $orderInfo['ordStatus'] = $order->getStatus();

//                $orderInfo['totalQty'] = $order->totalQty();

                $actionDescription = array(
                    'action' => 'purchased',
                    'email' => $order->getCustomerEmail(),
                    'cartInfo' => array_filter($orderInfo),
                    'products' => array_filter($actionData)
                );
                $res = $this->amplify->send_old_order($actionDescription);
            }
            Mage::getConfig()->saveConfig('betaout_amplify_options/settings/_process_date', $processDate)->cleanCache();
            Mage::app()->reinitStores();
            Mage::getModel('core/config')->saveConfig('betaout_amplify_options/settings/_process_date', $processDate);
//            Mage::getModel('core/variable')->loadByCode('myproject_next_api_id');
//            $endTime = microtime(true);
//                    $currentPage++;
//                    //clear collection and free memory
//                    $orders->clear();
//                } while ($currentPage <= $pages);
//       
        }
    }

    public function disableModuless($moduleName = "Betaout_Amplify") {
        // Disable the module itself
        $nodePath = "modules/$moduleName/active";
        if (Mage::helper('core/data')->isModuleEnabled($moduleName)) {
            Mage::getConfig()->setNode($nodePath, 'false', true);
        }

        // Disable its output as well (which was already loaded)
        $outputPath = "advanced/modules_disable_output/$moduleName";
        if (!Mage::getStoreConfig($outputPath)) {
            Mage::app()->getStore()->setConfig($outputPath, true);
        }
//        ->addAttributeToSort('order', 'ASC')
//        $customerCollection = Mage::getModel('customer/customer')->getCollection();
//    $customerCollection->setPageSize($limit);
//    $customerCollection->setCurPage(1);
    }

    public function getCustomerssss() {
        $customerCollection = Mage::getModel('customer/customer')->getCollection(); //Fetch all Customers from magento
        $defaultData = array();
        $finalData = array();
        foreach ($customerCollection as $cust) {
            $data = array();
            $customer = Mage::getModel('customer/customer')->load($cust->getId()); // Get Customer info from Customer Id
            $defaultData = $customer->getData(); //Get Customer data

            if (array_key_exists('default_billing', $defaultData)) {
                $billingAddress = Mage::getModel('customer/address')->load($defaultData['default_billing']);
                $data['default_billing'] = $billingAddress->getData();
            }

            if (array_key_exists('default_shipping', $defaultData)) {
                $shippingAddress = Mage::getModel('customer/address')->load($defaultData['default_shipping']);
                $data['default_shipping'] = $shippingAddress->getData();
            }

            $finalData[] = array_merge($customer->getData(), $data);
        }
        return $finalData;
    }

    public function updateIndexss() {
        $productsCollection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array('name', 'image', 'url_key', 'price', 'visibility'));

        $productsCollection->setPageSize(100);

        $pages = $productsCollection->getLastPageNumber();
        $currentPage = 1;

        do {
            $productsCollection->setCurPage($currentPage);
            $productsCollection->load();

            foreach ($productsCollection as $_product) {

                $insertData = array(
                    'entity_id' => $_product->getId(),
                    'title' => $_product->getName(),
                    'image' => $_product->getImage(),
                    'url' => $_product->getUrlKey(),
                    'price' => $_product->getFinalPrice(),
                );
//
//                $this->_getWriteAdapter()->insertOnDuplicate(
//                        $this->getTable('atwix_sonar/suggestions'), $insertData, array('title', 'image', 'url', 'price')
//                );
            }

            $currentPage++;
            //clear collection and free memory
            $productsCollection->clear();
        } while ($currentPage <= $pages);




        /**
         * $pages = $productsCollection->getLastPageNumber();
          for($currentPage = 1; $currentPage <= $pages; $currentPage++ {
          __ $productsCollection->setCurPage($currentPage);
          __ foreach ($productsCollection as $_product) {
          ____ // do things
          __ }
          __ $productsCollection->clear();
          }
         */
        /*
         * fech cart info
         * $collection = Mage::getResourceModel('reports/quote_collection');
          $collection->prepareForAbandonedReport();
          $output = $collection->load()->toArray();

         */
    }

    protected function _prepareCollectionssss() {
        $customer = Mage::registry('current_customer');
        $storeIds = Mage::app()->getWebsite($this->getWebsiteId())->getStoreIds();

        $quote = Mage::getModel('sales/quote')
                ->setSharedStoreIds($storeIds)
                ->loadByCustomer($customer);

        if ($quote) {
            $collection = $quote->getItemsCollection(false);
        } else {
            $collection = new Varien_Data_Collection();
        }

        $collection->addFieldToFilter('parent_item_id', array('null' => true));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    function getAllStoreIdsss() {

        //How to get all Store Ids in Magento
        $allStores = Mage::app()->getStores();
        foreach ($allStores as $_eachStoreId => $val) {
            $_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
            $_storeName = Mage::app()->getStore($_eachStoreId)->getName();
            $_storeId = Mage::app()->getStore($_eachStoreId)->getId();
            echo $_storeId;
            echo $_storeCode;
            echo $_storeName;
        }
    }

}
?>

