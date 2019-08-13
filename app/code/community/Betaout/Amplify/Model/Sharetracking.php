<?php

  class Betaout_Amplify_Model_Sharetracking
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' =>'true', 'label'=>Mage::helper('adminhtml')->__('on')),
            array('value' =>'false', 'label'=>Mage::helper('adminhtml')->__('off')),
        );
    }

}
