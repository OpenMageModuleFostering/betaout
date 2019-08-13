<?php

class Betaout_Amplify_Model_Core_Config_Base
    extends Betaout_Amplify_Model_Lib_Varien_Simplexml_Config
{
    public function __construct($sourceData = null)
    {
        $this->_elementClass = 'Betaout_Amplify_Model_Core_Config_Element';
        parent::__construct($sourceData);
    }
}
