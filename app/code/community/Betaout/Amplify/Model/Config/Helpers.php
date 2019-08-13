<?php

class Betaout_Amplify_Model_Config_Helpers
    extends Betaout_Amplify_Model_Config_ConfigAbstract
{
    protected $_type = 'helpers';

    public function getRewrites(
        Betaout_Amplify_Model_Core_Config_Element $config,
        $rewrites = array()
    )
    {
        $helpers = $config->helpers;
        $this->_findRewrites($helpers, $rewrites);

        return parent::getRewrites($config, $rewrites);
    }
}
