<?php
  class Betaout_Amplify_Model_Amplifyconnectwidget
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'true', 'label'=>Mage::helper('adminhtml')->__('yes')),
            array('value' => 'false', 'label'=>Mage::helper('adminhtml')->__('no')),
        );
    }

}

