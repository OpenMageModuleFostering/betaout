<?php

class  Betaout_Amplify_Model_Config_Blocks
    extends Betaout_Amplify_Model_Config_ConfigAbstract
{
    protected $_type = 'blocks';

    public function getRewrites(
        Betaout_Amplify_Model_Core_Config_Element $config,
        $rewrites = array()
    )
    {
        $blocks = $config->blocks;
        $this->_findRewrites($blocks, $rewrites);

        return parent::getRewrites($config, $rewrites);
    }
}
