<?php
class Betaout_Amplify_Helper_Data
    extends Mage_Core_Helper_Data
{
    /**
     * Description for const
     */
    const XML_PATH_ROUNDTRIP_ROOT = 'betaout_amplify/settings/';

    protected $_name = 'Betaout Advanced Configuration';

    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        true;
    }

    public function getName()
    {
        return $this->__($this->_name);
    }

    public function getPath($pathend)
    {
        return self::XML_PATH_ROUNDTRIP_ROOT . $pathend;
    }

    public function setStatus($path, $value, $scope = null, $scopeId = null)
    {
        $scope   = (in_array($scope, array('default', 'websites', 'stores'))) ? $scope : 'default';
        $scopeId = (is_int($scopeId)) ? $scopeId : 0;

        return Mage::getSingleton('core/config')
            ->saveConfig($path, $value, $scope, $scopeId);
    }
}
