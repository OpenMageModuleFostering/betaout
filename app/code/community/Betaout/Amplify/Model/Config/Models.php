<?php

/**
 * Model Config Conflict Checker
 *
 * @package   Betaout_Amplify
 */
class Betaout_Amplify_Model_Config_Models
    extends Betaout_Amplify_Model_Config_ConfigAbstract
{
    /**
     * Type of rewrite
     *
     * @var string
     * @access protected
     */
    protected $_type = 'models';

    /**
     * Check models section for rewrites
     *
     * @param Betaout_Amplify_Model_Core_Config_Element $config   Config node
     * @param array                                   $rewrites Existing rewrites
     *
     * @return array rewrites
     * @access public
     */
    public function getRewrites(
        Betaout_Amplify_Model_Core_Config_Element $config,
        $rewrites = array()
    )
    {
        $models = $config->models;
        $this->_findRewrites($models, $rewrites);

        return parent::getRewrites($config, $rewrites);
    }
}
