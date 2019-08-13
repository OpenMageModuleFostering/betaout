<?php

class Betaout_Amplify_Model_Config_Datastore
    extends Mage_Core_Model_Abstract
{
    protected $_store = array();

    public function addRewrite(
        $oldValue,
        $newValue,
        $configFile = 'Unavailable',
        $path = 'Unavailable'
    )
    {
        if ('Unavailable' != $configFile) {
            //  +1 just removes the starting '/' from the path
            $configFile = substr($configFile, strlen(Mage::getBaseDir()) + 1, strlen($configFile));
        }
        $this->_store[] = array(
            'oldValue' => $oldValue,
            'newValue' => $newValue,
            'file'     => $configFile,
            'path'     => $path
        );
    }

    public function getRewriteConflicts()
    {
        return $this->_store;
    }
}
