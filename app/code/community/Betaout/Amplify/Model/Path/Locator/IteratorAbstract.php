<?php

abstract class Betaout_Amplify_Model_Path_Locator_IteratorAbstract
{
    /**
     * Locator implementation
     *
     * @var Betaout_Amplify_Model_Path_Locator_LocatorInterface
     * @access protected
     */
    protected $_iterator = null;

    /**
     * Constructor
     *
     * @param Betaout_Amplify_Model_Path_Locator_LocatorInterface $iterator
     */
    public function __construct($iterator)
    {
        $this->_iterator = $iterator;
    }
}
