<?php

class Betaout_Amplify_Model_Path_Locator_Factory
{
    public function getLocator()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $model = new Betaout_Amplify_Model_Path_Locator_Stack(new SplStack());
        } else {
            $model = new Betaout_Amplify_Model_Path_Locator_Array(array());
        }

        return $model;
    }
}
