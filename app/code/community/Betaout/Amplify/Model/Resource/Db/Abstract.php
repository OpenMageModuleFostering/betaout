<?php
abstract class Betaout_Amplify_Model_Resource_Db_Abstract extends Mage_Core_Model_Resource_Abstract
{
    const CHECKSUM_KEY_NAME = 'Checksum';

    protected $_resources;

    protected $_resourcePrefix;
    protected $_connections = array();

    protected $_resourceModel;

    protected $_tables = array();

    /**
     * Main table name
     *
     * @var string
     */
    protected $_mainTable;

    /**
     * Main table primary key field name
     *
     * @var string
     */
    protected $_idFieldName;

    /**
     * Primery key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = true;

    /**
     * Use is object new method for save of object
     *
     * @var boolean
     */
    protected $_useIsObjectNew = false;

    /**
     * Fields List for update in forsedSave
     *
     * @var array
     */
    protected $_fieldsForUpdate = array();

    /**
     * Fields of main table
     *
     * @var array
     */
    protected $_mainTableFields;

    protected $_uniqueFields = null;

    protected $_serializableFields = array();

    protected function _init($mainTable, $idFieldName)
    {
        $this->_setMainTable($mainTable, $idFieldName);
    }

    protected function _setResource($connections, $tables = null)
    {
        $this->_resources = Mage::getSingleton('core/resource');

        if (is_array($connections)) {
            foreach ($connections as $k => $v) {
                $this->_connections[$k] = $this->_resources->getConnection($v);
            }
        } else if (is_string($connections)) {
            $this->_resourcePrefix = $connections;
        }

        if (is_null($tables) && is_string($connections)) {
            $this->_resourceModel = $this->_resourcePrefix;
        } else if (is_array($tables)) {
            foreach ($tables as $k => $v) {
                $this->_tables[$k] = $this->_resources->getTableName($v);
            }
        } else if (is_string($tables)) {
            $this->_resourceModel = $tables;
        }

        return $this;
    }

    protected function _setMainTable($mainTable, $idFieldName = null)
    {
        $mainTableArr = explode('/', $mainTable);

        if (!empty($mainTableArr[1])) {
            if (empty($this->_resourceModel)) {
                $this->_setResource($mainTableArr[0]);
            }
            $this->_setMainTable($mainTableArr[1], $idFieldName);
        } else {
            $this->_mainTable = $mainTable;
            if (is_null($idFieldName)) {
                $idFieldName = $mainTable . '_id';
            }
            $this->_idFieldName = $idFieldName;
        }

        return $this;
    }

    /**
     * Get primary key field name
     *
     * @return string
     */
    public function getIdFieldName()
    {
        if (empty($this->_idFieldName)) {
            Mage::throwException(Mage::helper('core')->__('Empty identifier field name'));
        }

        return $this->_idFieldName;
    }

    /**
     * Returns main table name - extracted from "module/table" style and
     * validated by db adapter
     *
     * @return string
     */
    public function getMainTable()
    {
        if (empty($this->_mainTable)) {
            Mage::throwException(Mage::helper('core')->__('Empty main table name'));
        }

        return $this->getTable($this->_mainTable);
    }

    /**
     * Get table name for the entity, validated by db adapter
     *
     * @param string $entityName
     *
     * @return string
     */
    public function getTable($entityName)
    {
        if (is_array($entityName)) {
            $cacheName = join('@', $entityName);
            list($entityName, $entitySuffix) = $entityName;
        } else {
            $cacheName    = $entityName;
            $entitySuffix = null;
        }

        if (isset($this->_tables[$cacheName])) {
            return $this->_tables[$cacheName];
        }

        if (strpos($entityName, '/')) {
            if (!is_null($entitySuffix)) {
                $modelEntity = array($entityName, $entitySuffix);
            } else {
                $modelEntity = $entityName;
            }
            $this->_tables[$cacheName] = $this->_resources->getTableName($modelEntity);
        } else if (!empty($this->_resourceModel)) {
            $entityName = sprintf('%s/%s', $this->_resourceModel, $entityName);
            if (!is_null($entitySuffix)) {
                $modelEntity = array($entityName, $entitySuffix);
            } else {
                $modelEntity = $entityName;
            }
            $this->_tables[$cacheName] = $this->_resources->getTableName($modelEntity);
        } else {
            if (!is_null($entitySuffix)) {
                $entityName .= '_' . $entitySuffix;
            }
            $this->_tables[$cacheName] = $entityName;
        }

        return $this->_tables[$cacheName];
    }


    public function getValueTable($entityName, $valueType)
    {
        return $this->getTable(array($entityName, $valueType));
    }

    protected function _getConnection($connectionName)
    {
        if (isset($this->_connections[$connectionName])) {
            return $this->_connections[$connectionName];
        }
        if (!empty($this->_resourcePrefix)) {
            $this->_connections[$connectionName] = $this->_resources->getConnection(
                $this->_resourcePrefix . '_' . $connectionName);
        } else {
            $this->_connections[$connectionName] = $this->_resources->getConnection($connectionName);
        }

        return $this->_connections[$connectionName];
    }

    protected function _getReadAdapter()
    {
        $writeAdapter = $this->_getWriteAdapter();
        if ($writeAdapter && $writeAdapter->getTransactionLevel() > 0) {
            // if transaction is started we should use write connection for reading
            return $writeAdapter;
        }

        return $this->_getConnection('read');
    }

    /**
     * Retrieve connection for write data
     *
     * @return Varien_Db_Adapter_Interface
     */
    protected function _getWriteAdapter()
    {
        return $this->_getConnection('write');
    }

    /**
     * Temporary resolving collection compatibility
     *
     * @return Varien_Db_Adapter_Interface
     */
    public function getReadConnection()
    {
        return $this->_getReadAdapter();
    }

    public function load(Mage_Core_Model_Abstract $object, $value, $field = null)
    {
        if (is_null($field)) {
            $field = $this->getIdFieldName();
        }

        $read = $this->_getReadAdapter();
        if ($read && !is_null($value)) {
            $select = $this->_getLoadSelect($field, $value, $object);
            $data   = $read->fetchRow($select);

            if ($data) {
                $object->setData($data);
            }
        }

        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }

    protected function _getLoadSelect($field, $value, $object)
    {
        $field  = $this->_getReadAdapter()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), $field));
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
            ->where($field . '=?', $value);

        return $select;
    }

    /**
     * Save object object data
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    public function save(Mage_Core_Model_Abstract $object)
    {
        if ($object->isDeleted()) {
            return $this->delete($object);
        }

        $this->_serializeFields($object);
        $this->_beforeSave($object);
        $this->_checkUnique($object);
        if (!is_null($object->getId()) && (!$this->_useIsObjectNew || !$object->isObjectNew())) {
            $condition = $this->_getWriteAdapter()->quoteInto($this->getIdFieldName() . '=?', $object->getId());
            /**
             * Not auto increment primary key support
             */
            if ($this->_isPkAutoIncrement) {
                $data = $this->_prepareDataForSave($object);
                unset($data[$this->getIdFieldName()]);
                $this->_getWriteAdapter()->update($this->getMainTable(), $data, $condition);
            } else {
                $select = $this->_getWriteAdapter()->select()
                    ->from($this->getMainTable(), array($this->getIdFieldName()))
                    ->where($condition);
                if ($this->_getWriteAdapter()->fetchOne($select) !== false) {
                    $data = $this->_prepareDataForSave($object);
                    unset($data[$this->getIdFieldName()]);
                    if (!empty($data)) {
                        $this->_getWriteAdapter()->update($this->getMainTable(), $data, $condition);
                    }
                } else {
                    $this->_getWriteAdapter()->insert($this->getMainTable(), $this->_prepareDataForSave($object));
                }
            }
        } else {
            $bind = $this->_prepareDataForSave($object);
            if ($this->_isPkAutoIncrement) {
                unset($bind[$this->getIdFieldName()]);
            }
            $this->_getWriteAdapter()->insert($this->getMainTable(), $bind);

            $object->setId($this->_getWriteAdapter()->lastInsertId($this->getMainTable()));

            if ($this->_useIsObjectNew) {
                $object->isObjectNew(false);
            }
        }

        $this->unserializeFields($object);
        $this->_afterSave($object);

        return $this;
    }

    public function forsedSave(Mage_Core_Model_Abstract $object)
    {
        $this->_beforeSave($object);
        $bind    = $this->_prepareDataForSave($object);
        $adapter = $this->_getWriteAdapter();
        // update
        if (!is_null($object->getId()) && $this->_isPkAutoIncrement) {
            unset($bind[$this->getIdFieldName()]);
            $condition = $adapter->quoteInto($this->getIdFieldName() . '=?', $object->getId());
            $adapter->update($this->getMainTable(), $bind, $condition);
        } else {
            $adapter->insertOnDuplicate($this->getMainTable(), $bind, $this->_fieldsForUpdate);
            $object->setId($adapter->lastInsertId($this->getMainTable()));
        }

        $this->_afterSave($object);

        return $this;
    }

    public function delete(Mage_Core_Model_Abstract $object)
    {
        $this->_beforeDelete($object);
        $this->_getWriteAdapter()->delete(
            $this->getMainTable(),
            $this->_getWriteAdapter()->quoteInto($this->getIdFieldName() . '=?', $object->getId())
        );
        $this->_afterDelete($object);

        return $this;
    }

    public function addUniqueField($field)
    {
        if (is_null($this->_uniqueFields)) {
            $this->_initUniqueFields();
        }
        if (is_array($this->_uniqueFields)) {
            $this->_uniqueFields[] = $field;
        }

        return $this;
    }

    /**
     * Reset unique fields restrictions
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    public function resetUniqueField()
    {
        $this->_uniqueFields = array();

        return $this;
    }

    /**
     * Unserialize serializeable object fields
     *
     * @param Mage_Core_Model_Abstract $object
     */
    public function unserializeFields(Mage_Core_Model_Abstract $object)
    {
        foreach ($this->_serializableFields as $field => $parameters) {
            list($serializeDefault, $unserializeDefault) = $parameters;
            $this->_unserializeField($object, $field, $unserializeDefault);
        }
    }

    /**
     * Initialize unique fields
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = array();

        return $this;
    }

    /**
     * Get configuration of all unique fields
     *
     * @return array
     */
    public function getUniqueFields()
    {
        if (is_null($this->_uniqueFields)) {
            $this->_initUniqueFields();
        }

        return $this->_uniqueFields;
    }

    protected function _prepareDataForSave(Mage_Core_Model_Abstract $object)
    {
        return $this->_prepareDataForTable($object, $this->getMainTable());
    }

    public function hasDataChanged($object)
    {
        if (!$object->getOrigData()) {
            return true;
        }

        $fields = $this->_getWriteAdapter()->describeTable($this->getMainTable());
        foreach (array_keys($fields) as $field) {
            if ($object->getOrigData($field) != $object->getData($field)) {
                return true;
            }
        }

        return false;
    }

    protected function _prepareValueForSave($value, $type)
    {
        return $this->_prepareTableValueForSave($value, $type);
    }

    protected function _checkUnique(Mage_Core_Model_Abstract $object)
    {
        $existent = array();
        $fields   = $this->getUniqueFields();
        if (!empty($fields)) {
            if (!is_array($fields)) {
                $this->_uniqueFields = array(
                    array(
                        'field' => $fields,
                        'title' => $fields
                    ));
            }

            $data   = new Varien_Object($this->_prepareDataForSave($object));
            $select = $this->_getWriteAdapter()->select()
                ->from($this->getMainTable());

            foreach ($fields as $unique) {
                $select->reset(Zend_Db_Select::WHERE);

                if (is_array($unique['field'])) {
                    foreach ($unique['field'] as $field) {
                        $select->where($field . '=?', trim($data->getData($field)));
                    }
                } else {
                    $select->where($unique['field'] . '=?', trim($data->getData($unique['field'])));
                }

                if ($object->getId() || $object->getId() === '0') {
                    $select->where($this->getIdFieldName() . '!=?', $object->getId());
                }

                $test = $this->_getWriteAdapter()->fetchRow($select);
                if ($test) {
                    $existent[] = $unique['title'];
                }
            }
        }

        if (!empty($existent)) {
            if (count($existent) == 1) {
                $error = Mage::helper('core')->__('%s already exists.', $existent[0]);
            } else {
                $error = Mage::helper('core')->__('%s already exist.', implode(', ', $existent));
            }
            Mage::throwException($error);
        }

        return $this;
    }

    /**
     * After load
     *
     * @param Mage_Core_Model_Abstract $object
     */
    public function afterLoad(Mage_Core_Model_Abstract $object)
    {
        $this->_afterLoad($object);
    }

    /**
     * Perform actions after object load
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return $this
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions before object save
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return $this
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions after object save
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return $this
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions before object delete
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return $this
     */
    protected function _beforeDelete(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    protected function _afterDelete(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Serialize serializeable fields of the object
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _serializeFields(Mage_Core_Model_Abstract $object)
    {
        foreach ($this->_serializableFields as $field => $parameters) {
            list($serializeDefault, $unserializeDefault) = $parameters;
            $this->_serializeField($object, $field, $serializeDefault, isset($parameters[2]));
        }
    }

    public function getChecksum($table)
    {
        if (!$this->_getReadAdapter()) {
            return false;
        }
        $checksum = $this->_getReadAdapter()->getTablesChecksum($table);
        if (count($checksum) == 1) {
            return $checksum[$table];
        }

        return $checksum;
    }
}
