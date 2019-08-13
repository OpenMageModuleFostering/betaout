<?php

class Betaout_Amplify_Block_Adminhtml_Widget_Button_Conflict extends Mage_Adminhtml_Block_Widget_Button
{
    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    protected function _construct()
    {
        $this->setLabel('Check Module Conflicts');
        $this->setOnClick("checkConflicts(); return false;");
    }
}
