<?php

class Betaout_Amplify_Model_Config_Checker
    extends Betaout_Amplify_Model_Config_ConfigAbstract
{
    public function getConflicts(
        Betaout_Amplify_Model_Core_Config_Element $config
    )
    {
        $rewrites = $this->getRewrites($config);
        foreach ($rewrites as $type => $modules) {
            foreach ($modules as $module => $classes) {
                foreach ($classes as $class => $conflicts) {
                    if (count($classes[$class]) > 1) {
                        echo "$type : $module : $class is rewrite multiple times by";
                    }
                }
            }
        }

        return $this->getRewrites($config);
    }
}
