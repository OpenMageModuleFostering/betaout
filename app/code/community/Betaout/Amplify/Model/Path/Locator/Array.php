<?php

class Betaout_Amplify_Model_Path_Locator_Array
    extends Betaout_Amplify_Model_Path_Locator_IteratorAbstract
    implements Betaout_Amplify_Model_Path_Locator_LocatorInterface
{
    public function getPath(SimpleXmlElement $element)
    {
        $this->_iterator[] = $element->getName() . '/';
        if (!$element->getSafeParent()) {
            return array_pop($this->_iterator);
        }

        return $this->getPath($element->getParent()) . array_pop($this->_iterator);
    }
}
