<?php
  class Betaout_Amplify_Model_CronStatus
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => false, 'label'=>Mage::helper('adminhtml')->__('NO')),
            array('value' => true, 'label'=>Mage::helper('adminhtml')->__('YES')),
        );
    }

}

