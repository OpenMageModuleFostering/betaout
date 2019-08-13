<?php
class Betaout_Amplify_Model_Config_Resources
    extends Betaout_Amplify_Model_Config_ConfigAbstract
{
    protected $_type = 'resources';

    public function getRewrites(
        Betaout_Amplify_Model_Core_Config_Element $config,
        $rewrites = array()
    )
    {
        $resources = $config->resources;
        $this->_findRewrites($resources, $rewrites);

        return parent::getRewrites($config, $rewrites);
    }
}
