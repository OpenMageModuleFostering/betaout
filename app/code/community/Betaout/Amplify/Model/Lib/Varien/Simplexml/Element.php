<?php
class Betaout_Amplify_Model_Lib_Varien_Simplexml_Element
    extends Varien_Simplexml_Element
{
    public function extend($source, $overwrite = false)
    {
        if (!$source instanceof Varien_Simplexml_Element) {
            return $this;
        }

        foreach ($source->children() as $child) {
            $this->extendChild($child, $overwrite);
        }

        return $this;
    }

    public function extendChild($source, $overwrite = false)
    {
        // this will be our new target node
        $targetChild = null;

        // name of the source node
        $sourceName = $source->getName();

        // here we have children of our source node
        $sourceChildren = $source->children();

        if (!$source->hasChildren()) {
            // handle string node
            if (isset($this->$sourceName)) {
                // if target already has children return without regard
                if ($this->$sourceName->children()) {
                    return $this;
                }
                if ($overwrite) {
                    if (Mage::registry('conflict_datastore_enabled')) {
                        $factory   = new Betaout_Amplify_Model_Path_Locator_Factory;
                        $locator   = $factory->getLocator();
                        $dataStore = Mage::registry('conflict_datastore');
                        $dataStore->addRewrite(
                            (string)$this->$sourceName,
                            (string)$source,
                            Mage::registry('conflict_datastore_config_file'),
                            $locator->getPath($source)
                        );
                    }
                    unset($this->$sourceName);
                } else {
                    return $this;
                }
            }

            $targetChild = $this->addChild($sourceName, $source->xmlentities());
            $targetChild->setParent($this);
            foreach ($source->attributes() as $key => $value) {
                $targetChild->addAttribute($key, $this->xmlentities($value));
            }

            return $this;
        }

        if (isset($this->$sourceName)) {
            $targetChild = $this->$sourceName;
        }

        if (is_null($targetChild)) {
            // if child target is not found create new and descend
            $targetChild = $this->addChild($sourceName);
            $targetChild->setParent($this);
            foreach ($source->attributes() as $key => $value) {
                $targetChild->addAttribute($key, $this->xmlentities($value));
            }
        }

        // finally add our source node children to resulting new target node
        foreach ($sourceChildren as $childNode) {
            $targetChild->extendChild($childNode, $overwrite);
        }

        return $this;
    }

    /**
     * @param      $path
     * @param      $value
     * @param bool $overwrite
     *
     * @return $this
     */
    public function setNode($path, $value, $overwrite = true)
    {
        $arr1 = explode('/', $path);
        $arr  = array();
        foreach ($arr1 as $v) {
            if (!empty($v))
                $arr[] = $v;
        }
        $last = sizeof($arr) - 1;
        $node = $this;
        foreach ($arr as $i => $nodeName) {
            if ($last === $i) {
                if (!isset($node->$nodeName) || $overwrite) {
                    // http://bugs.php.net/bug.php?id=36795
                    // comment on [8 Feb 8:09pm UTC]
                    if (isset($node->$nodeName) && (version_compare(phpversion(), '5.2.6', '<') === true)) {
                        $node->$nodeName = $node->xmlentities($value);
                    } else {
                        $node->$nodeName = $value;
                    }
                }
            } else {
                if (!isset($node->$nodeName)) {
                    $node = $node->addChild($nodeName);
                } else {
                    $node = $node->$nodeName;
                }
            }

        }

        return $this;
    }

    /**
     * Returns parent node for the element
     *
     * Currently using xpath
     * If xpath value doesn't exist - return null
     *
     * @return Varien_Simplexml_Element|null
     */
    public function getSafeParent()
    {
        if (!empty($this->_parent)) {
            $parent = $this->_parent;
        } else {
            $arr = $this->xpath('..');
            if (is_array($arr) && isset($arr[0])) {
                $parent = $arr[0];
            } else {
                $parent = null;
            }
        }

        return $parent;
    }
}
