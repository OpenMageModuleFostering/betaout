<?php

class Betaout_Amplify_Adminhtml_ConflictcheckerController extends Mage_Adminhtml_Controller_Action {

     public function AjaxvalidationAction()
    {
        $globalDataStore = Mage::getModel('betaout_amplify/config_datastore');
        Mage::register('conflict_datastore', $globalDataStore);
        $config = Mage::getModel('betaout_amplify/core_config');
        $config->reinit();

        //  Chain of Responsibility
        //  each checker looks through its designated area for rewrites
        $blocks    = Mage::getModel('betaout_amplify/config_blocks');
        $models    = Mage::getModel('betaout_amplify/config_models', array($blocks));
        $helpers   = Mage::getModel('betaout_amplify/config_helpers', array($models));
        $resources = Mage::getModel('betaout_amplify/config_resources', array($helpers));
        $checker   = Mage::getModel('betaout_amplify/config_checker', array($resources));

        $checker->getConflicts($config->getNode('frontend'));

        $result=$globalDataStore->getRewriteConflicts();
//        print_r($result);
        $printer = new Betaout_Amplify_Model_Config_Printer();
//          $result= Mage::getConfig()->getNode()->xpath('//global//rewrite');
//        Mage::app()->getResponse()->setBody(print_r($result));
        $this->getResponse()->setBody($printer->render($globalDataStore, 'XML configurations rewritten more than once'));
    }

}
