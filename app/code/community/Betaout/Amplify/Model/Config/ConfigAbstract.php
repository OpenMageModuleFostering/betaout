<?php

abstract class Betaout_Amplify_Model_Config_ConfigAbstract
    extends Mage_Core_Model_Abstract
    implements Betaout_Amplify_Model_Config_ConfigInterface
{
    protected $_nextHandler = null;

    public function _construct()
    {
        if (isset($this->_data[0])) {
            $this->_nextHandler = $this->_data[0];
        }
    }
    public function getRewrites(
        Betaout_Amplify_Model_Core_Config_Element $config,
        $rewrites = array()
    )
    {
        if (!is_null($this->_nextHandler)) {
            return $this->_nextHandler->getRewrites($config, $rewrites);
        } else {
            return $rewrites;
        }
    }
    protected function _findRewrites(
        Betaout_Amplify_Model_Core_Config_Element $config,
        &$rewrites = array()
    )
    {
        $reflect = new ReflectionObject($config);
        $props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $module  = $prop->getName();
            $reflect = new ReflectionObject($config->$module);
            if ($reflect->hasProperty('rewrite')) {
                $rewrite    = new ReflectionObject($config->$module->rewrite);
                $properties = $rewrite->getProperties(ReflectionProperty::IS_PUBLIC);
                foreach ($properties as $property) {
                    $class = $property->name;
                    $rewrites[$this->_type][$module][$class][]
                           = (string)$config->$module->rewrite->$class;
                }
            }
        }
    }
}
