<?php
 
class Betaout_Amplify_Model_Order extends Mage_Sales_Model_Order
{
 
    public function setState($state, $status = false, $comment = '', $isCustomerNotified = null)
    {    
        Mage::dispatchEvent('sales_order_status_change', array('order' => $this, 'state' => $state, 'status' => $status, 'comment' => $comment, 'isCustomerNotified' => $isCustomerNotified));
         
        return parent::setState($state, $status, $comment, $isCustomerNotified);
    }
}