<?php

class Betaout_Amplify_Block_Conflictprinter
    extends Mage_Adminhtml_Block_Template
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('betaout_amplify/conflict.phtml');
    }

    public function getParity()
    {
        return $this->i++ % 2 ? 'even' : '';
    }
}
