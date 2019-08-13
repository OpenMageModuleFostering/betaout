<?php

class Betaout_Amplify_Model_Path_Locator_Stack extends Betaout_Amplify_Model_Path_Locator_IteratorAbstract implements Betaout_Amplify_Model_Path_Locator_LocatorInterface {

    public function getPath(SimpleXmlElement $element) {
        $this->_iterator->push($element->getName() . '/');
        if (!$element->getSafeParent()) {
            return $this->_iterator->pop();
        }

        return $this->getPath($element->getParent()) . $this->_iterator->pop();
    }

}
