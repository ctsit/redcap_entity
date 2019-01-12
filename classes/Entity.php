<?php
/**
 * @file
 * Provides a class for a generic entity.
 */

namespace REDCapEntity;

use REDCapEntity\EntityFactory;
use Exception;
use Records;
use RedCapDB;
use UserRights;

/**
 * Entity class.
 */
class Entity {
    protected $id;
    protected $created;
    protected $updated;
    protected $data = [];
    protected $factory;
    protected $entityTypeKey;
    protected $entityTypeInfo;
    protected $oldData;
    protected $errors;

    function __construct(EntityFactory $factory, $entity_type, $id = null) {
        if (!$info = $factory->getEntityTypeInfo($entity_type)) {
            throw new Exception('Invalid entity type.');
        }

        foreach (array_keys($info['properties']) as $key) {
            $this->data[$key] = null;
        }

        $this->entityTypeKey = $entity_type;
        $this->entityTypeInfo = $info;

        if ($id && !$this->load($id)) {
            throw new Exception('The entity does not exist.');
        }

        $this->factory = $factory;
    }

    function create($data) {
        if ($this->id) {
            return false;
        }

        if (!$this->setData($data)) {
            return false;
        }

        return $this->save();
    }

    function setData($data) {
        $this->errors = [];

        foreach ($data as $key => $value) {
            if (!$this->validateProperty($key, $value)) {
                $this->errors[] = $key;
            }
        }

        if (!empty($this->errors)) {
            return false;
        }

        foreach ($data as $key => $value) {
            if ($value === '') {
                $value = null;
            }
            elseif (
                $this->entityTypeInfo['properties'][$key]['type'] == 'json' &&
                $value !== null &&
                (!is_string($value) || json_decode($value) === null)
            ) {
                $value = json_encode($value);
            }

            $this->data[$key] = $value;
        }

        return true;
    }

    function delete() {
        if (!$this->id) {
            return false;
        }
        
        $entity_type = db_escape($this->entityTypeKey);
        if (!db_query('DELETE FROM `redcap_entity_' . $entity_type . '` WHERE id = "' . intval($this->id) . '"')) {
            return false;
        }

        // Resetting object.
        $this->id = null;
        $this->created = null;
        $this->updated = null;
        $this->errors = null;
        $this->oldData = null;

        foreach (array_keys($this->data) as $key) {
            $this->data[$key] = null;
        }

        return true;
    }

    protected function validateProperty($key, $value) {
        if (!array_key_exists($key, $this->data) || !isset($this->entityTypeInfo['properties'][$key])) {
            return false;
        }

        $info = $this->entityTypeInfo['properties'][$key];
        if ($value === null || $value === '') {
            return empty($info['required']);
        }

        switch ($info['type']) {
            case 'email':
                if (!isEmail($value)) {
                    return false;
                }

                break;

            case 'text':
                if (!is_string($value)) {
                    return false;
                }

                break;

            case 'date':
            case 'integer':
                if (!is_numeric($value) || intval($value) != $value) {
                    return false;
                }

                break;

            case 'record':
                return defined('PROJECT_ID') && Records::recordExists(PROJECT_ID, $value);

            case 'user':
                $db = new RedCapDB();
                return $db->usernameExists($value);

            case 'project':
                $db = new RedCapDB();
                if (!$db->getProject($value)) {
                    return false;
                }

                return !defined('USERID') || SUPER_USER || ACCOUNT_MANAGER || UserRights::getPrivileges($value, USERID);

            case 'long_text':
                return is_string($value);

            case 'boolean':
                return is_bool($value) || $value == 1 || $value == 0;

            case 'entity_reference':
                return !empty($info['entity_type']) && $this->factory->getInstance($info['entity_type'], $value);

            case 'json':
                return true;

            default:
                return false;
        }

        if (isset($info['choices'])) {
            return isset($info['choices'][$value]);
        }

        if (isset($info['choices_callback'])) {
            if (!is_callable($info['choices_callback'])) {
                return false;
            }

            $choices = $info['choices_callback']();
            if (!is_array($choices) || !isset($choices[$value])) {
                return false;
            }
        }

        return true;
    }

    function load($id) {
        $entity_type = db_escape($this->entityTypeKey);
        if (!($q = db_query('SELECT * FROM `redcap_entity_' . $entity_type . '` WHERE id = "' . intval($id) . '"')) || !db_num_rows($q)) {
            return false;
        }

        $result = db_fetch_assoc($q);

        $this->id = $id;
        $this->created = $result['created'];
        $this->updated = $result['updated'];

        foreach ($result as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                $this->data[$key] = $value;
            }
        }

        $this->oldData = $this->data;
        return true;
    }

    function getId() {
        return $this->id;
    }

    function getLabel() {
        if (!$this->id) {
            return false;
        }

        if (!isset($this->entityTypeInfo['special_keys']['label'])) {
            return '#' . $this->id;
        }

        return $this->data[$this->entityTypeInfo['special_keys']['label']];
    }

    function getCreationTimestamp() {
        return $this->created;
    }

    function getLastUpdateTimestamp() {
        return $this->updated;
    }

    function getData() {
        $data = [];
        foreach ($this->data as $key => $value) {
            if ($this->entityTypeInfo['properties'][$key]['type'] == 'json' && is_string($value)) {
                $value = json_decode($value);
            }

            $data[$key] = $value;
        }

        return $data;
    }

    function getFactory() {
        return $this->factory;
    }

    function getEntityTypeInfo() {
        return $this->entityTypeInfo;
    }

    function getErrors() {
        return $this->errors;
    }

    function save() {
        // The entity requires a successfull setData() call before saving.
        if (!isset($this->errors) || !empty($this->errors)) {
            return false;
        }

        $row = ['updated' => strtotime(NOW)];
        $entity_type = db_escape($this->entityTypeKey);

        if ($this->id) {
            foreach ($this->data as $key => $value) {
                if ($value !== $this->oldData[$key]) {
                    $row[$key] = $value;
                }
            }

            $diff = [];
            foreach ($this->_formatQueryValues($row) as $key => $value) {
                $diff[] = $key . ' = ' . $value;
            }

            if (!db_query('UPDATE `redcap_entity_' . $entity_type . '` SET ' . implode(', ', $diff) . ' WHERE id = "' . intval($this->id) . '"')) {
                return false;
            }
        }
        else {
            $row['created'] = $row['updated'];
            $row += $this->data;

            foreach (['author' => 'USERID', 'project' => 'PROJECT_ID'] as $key => $const) {
                if (defined($const) && isset($this->entityTypeInfo['special_keys'][$key])) {
                    $key = $this->entityTypeInfo['special_keys'][$key];

                    if (empty($row[$key])) {
                        $row[$key] = constant($const);
                    }
                }
            }

            $keys = implode(', ', array_keys($row));
            $values = implode(', ', $this->_formatQueryValues($row));

            if (!db_query('INSERT INTO `redcap_entity_' . $entity_type . '` (' . $keys . ') VALUES (' . $values . ')')) {
                return false;
            }

            $this->id = db_insert_id();
            $this->created = $data['created'];
        }

        $this->updated = $data['updated'];
        $this->oldData = $this->data;

        return $this->id;
    }

    protected function _formatQueryValues($data) {
        $formatted = [];
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '"1"' : '"0"';
            }
            elseif ($value === null) {
                $value = 'NULL';
            }
            else {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }

                $value = '"' . db_escape($value) . '"';
            }

            $formatted[$key] = $value;
        }

        return $formatted;
    }
}
