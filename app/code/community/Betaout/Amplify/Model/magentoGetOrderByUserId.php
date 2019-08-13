<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
chdir(dirname(__FILE__));
echo "running";
echo "111111111111111111111111111";
require 'app/Mage.php';
echo "433333333333333333333";
if (!Mage::isInstalled()) {
    echo "Application is not installed yet, please complete install wizard first.";
    exit;
}


 umask( 0 );
 Mage :: app( "default" );
if (Mage::getSingleton('customer/session')->isLoggedIn()) {

/* Get the customer data */
$customer = Mage::getSingleton('customer/session')->getCustomer();
/* Get the customer's email address */
$customer_email = $customer->getEmail();

}

$customer_email='raviraz@gmail.com';

$collection = Mage::getModel('sales/order')
                    ->getCollection()
                      ->addAttributeToFilter('customer_email',array('like'=>$customer_email));

foreach($collection as $order){
    //do something
    $order_id = $order->getId();



$order = Mage::getModel("sales/order")->load($order_id); //load order by order id 

$ordered_items = $order->getAllItems(); 

foreach($ordered_items as $item){     //item detail     

echo $item->getItemId(); //product id     

echo $item->getSku();    
 echo $item->getQtyOrdered(); //ordered qty of item    

 echo $item->getName();   

 } 
}



//require_once('app/Mage.php');
//Mage::app();
//
//$orders = Mage::getModel('sales/order')->getCollection()
//    ->addFieldToFilter('status', 'complete')
//    ->addAttributeToSelect('customer_email')
//    ;
//foreach ($orders as $order) {
//    $email = $order->getCustomerEmail();
//    echo $email . "\n";
//}




/* Format our dates */
$fromDate = date('Y-m-d H:i:s', strtotime($fromDate));
$toDate = date('Y-m-d H:i:s', strtotime($toDate));
 
/* Get the collection */
$orders = Mage::getModel('sales/order')->getCollection()
    ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))
    ->addAttributeToFilter('status', array('eq' => Mage_Sales_Model_Order::STATE_COMPLETE));