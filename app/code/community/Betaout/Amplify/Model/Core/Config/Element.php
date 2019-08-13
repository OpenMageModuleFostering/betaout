<?php

/**
 * XML Configuration element
 *
 * @package   Betaout_Amplify
 */
class Betaout_Amplify_Model_Core_Config_Element
    extends Betaout_Amplify_Model_Lib_Varien_Simplexml_Element
{
    public function is($var, $value = true)
    {
        $flag = $this->$var;

        if ($value === true) {
            $flag = strtolower((string)$flag);
            if (!empty($flag) && 'false' !== $flag && 'off' !== $flag) {
                return true;
            } else {
                return false;
            }
        }

        return !empty($flag) && (0 === strcasecmp($value, (string)$flag));
    }

    /**
     * Get node class name
     *
     * @return string
     * @access public
     */
    public function getClassName()
    {
        if ($this->class) {
            $model = (string)$this->class;
        } elseif ($this->model) {
            $model = (string)$this->model;
        } else {
            return false;
        }

        return Mage::getConfig()->getModelClassName($model);
    }
}
