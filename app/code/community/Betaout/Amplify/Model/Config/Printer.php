<?php

class Betaout_Amplify_Model_Config_Printer
{
    public function render(
        Betaout_Amplify_Model_Config_Datastore $datastore,
        $title
    )
    {
        $block = Mage::app()->getLayout()->createBlock('Betaout_Amplify_Block_Conflictprinter');
        $block->setRewrites($datastore->getRewriteConflicts());
        $block->setTitle($title);

        return $block->toHtml();
    }
}
