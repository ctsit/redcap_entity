<?php
/**
 * @file
 * Provides abstract class for a generic entity.
 */

namespace REDCapEntity;

use REDCapEntity\EntityFactory;
use Exception;
use RedCapDB;
use UserRights;

/**
 * Entity class.
 */
class Entity {
    protected $id;
    protected $created;
    protected $updated;
    protected $__factory;
    protected $__entityTypeKey;
    protected $__entityTypeInfo;
    protected $__oldData;

    function __construct(EntityFactory $factory, $entity_type, $id = null) {
        if (!$info = $factory->getEntityTypeInfo($entity_type)) {
            throw new Exception('Invalid entity type.');
        }

        $this->__entityTypeKey = $entity_type;
        $this->__entityTypeInfo = $info;

        if ($id && !$this->load($id)) {
            throw new Exception('The entity does not exist.');
        }

        $this->__factory = $factory;
    }

    function create($data) {
        if ($this->id) {
            return false;
        }

        if ($this->setData($data)) {
            return false;
        }

        return $this->save();
    }

    function setData($data) {
        $errors = [];

        if (isset($this->__entityTypeInfo['special_keys']['uuid'])) {
            unset($data[$this->__entityTypeInfo['special_keys']['uuid']]);
        }

        $data = $this->_removeBasicProperties($data);
        foreach ($data as $key => $value) {
            if (!$this->validateProperty($key, $value)) {
                $errors[] = $key;
            }
        }

        if (empty($errors)) {
            foreach ($data as $key => $value) {
                if ($value === '') {
                    $value = null;
                }
                elseif ($this->__entityTypeInfo['properties'][$key]['type'] == 'json' && $value !== null && (!is_string($value) || json_decode($value) === null)) {
                    $value = json_encode($value);
                }

                $this->{$key} = $value;
            }
        }

        return $errors;
    }

    function delete() {
        if (!$this->id) {
            return false;
        }
        
        $entity_type = db_real_escape_string($this->__entityTypeKey);
        if (!db_query('DELETE FROM `redcap_entity_' . $entity_type . '` WHERE id = "' . intval($this->id) . '"')) {
            return false;
        }

        $this->id = null;
        return true;
    }

    protected function validateProperty($key, $value) {
        if (!property_exists($this, $key) || !isset($this->__entityTypeInfo['properties'][$key])) {
            return false;
        }

        $info = $this->__entityTypeInfo['properties'][$key];
        if ($value === null || $value === '') {
            return empty($info['required']);
        }

        switch ($info['type']) {
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

            case 'price':
                return intval($value) == $value && $value >= 0;

            case 'user':
                $db = new RedCapDB();
                return $db->usernameExists($value);

            case 'project':
                $db = new RedCapDB();
                if (!$db->getProject($value)) {
                    return false;
                }

                return !defined('USERID') || UserRights::getPrivileges($value, USERID);

            case 'long_text':
                return is_string($value);

            case 'boolean':
                return is_bool($value) || $value == 1 || $value == 0;

            case 'entity_reference':
                return !empty($info['entity_type']) && $this->__factory->getInstance($info['entity_type'], $value);

            case 'json':
                return true;

            default:
                return false;
        }

        if (isset($info['choices'])) {
            return isset($info['choices'][$value]);
        }

        if (isset($info['choices_callback'])) {
            if (!method_exists($this, $info['choices_callback'])) {
                return false;
            }

            $choices = $this->{$info['choices_callback']}();
            if (!is_array($choices) || !isset($choices[$value])) {
                return false;
            }
        }

        return true;
    }

    function load($id) {
        $entity_type = db_real_escape_string($this->__entityTypeKey);
        if (!($q = db_query('SELECT * FROM `redcap_entity_' . $entity_type . '` WHERE id = "' . intval($id) . '"')) || !db_num_rows($q)) {
            return false;
        }

        $result = db_fetch_assoc($q);
        foreach ($result as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        $this->__oldData = $this->getData();
        return true;
    }

    function getId() {
        return $this->id;
    }

    function getLabel() {
        if (!$this->id) {
            return false;
        }

        if (!isset($this->__entityTypeInfo['special_keys']['label'])) {
            return '#' . $this->id;
        }

        return $this->{$this->__entityTypeInfo['special_keys']['label']};
    }

    function getData() {
        $data = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (!in_array($key, ['id', 'created', 'updated'])) {
                if (!isset($this->__entityTypeInfo['properties'][$key])) {
                    continue;
                }

                if ($this->__entityTypeInfo['properties'][$key]['type'] == 'json' && is_string($value)) {
                    $value = json_decode($value);
                }
            }

            $data[$key] = $value;
        }

        return $data;
    }

    function getFactory() {
        return $this->__factory;
    }

    function getEntityTypeInfo() {
        return $this->__entityTypeInfo;
    }

    function save($message = '') {
        $data = $this->getData();
        $data['updated'] = strtotime(NOW);

        $entity_type = db_real_escape_string($this->__entityTypeKey);
        if ($id = $data['id']) {
            $diff = [];
            foreach ($data as $key => $value) {
                if ($value !== $this->__oldData[$key]) {
                    $diff[$key] = $value;
                }
            }

            if (empty($diff)) {
                return true;
            }

            $data = $diff;
            $diff = [];
            foreach ($this->_formatQueryValues($data) as $key => $value) {
                $diff[] = $key . ' = ' . $value;
            }

            if (!db_query('UPDATE `redcap_entity_' . $entity_type . '` SET ' . implode(', ', $diff) . ' WHERE id = "' . intval($id) . '"')) {
                return false;
            }

            $event_type = 'update';
        }
        else {
            unset($data['id']);
            $data['created'] = $data['updated'];

            if (isset($this->__entityTypeInfo['special_keys']['uuid'])) {
                $data[$this->__entityTypeInfo['special_keys']['uuid']] = generateRandomHash(16);
            }

            foreach (['author' => 'USERID', 'project' => 'PROJECT_ID'] as $key => $const) {
                if (defined($const) && isset($this->__entityTypeInfo['special_keys'][$key])) {
                    $key = $this->__entityTypeInfo['special_keys'][$key];

                    if (empty($data[$key])) {
                        $data[$key] = constant($const);
                    }
                }
            }

            $keys = implode(', ', array_keys($data));
            $values = implode(', ', $this->_formatQueryValues($data));

            if (!db_query('INSERT INTO `redcap_entity_' . $entity_type . '` (' . $keys . ') VALUES (' . $values . ')')) {
                return false;
            }

            $this->id = db_insert_id();
            $this->created = $data['created'];

            $event_type = 'create';
        }

        $this->updated = $data['updated'];

        if (in_array($event_type, $this->loggableEvents())) {
            $this->logEvent($event_type, $data, $message);
        }

        return $this->id;
    }

    protected function logEvent($event_type, $data, $message = '') {
        $data = array(
            'entity_id' => $data['id'],
            'entity_type' => $this->__entityTypeKey,
            'event_type' => $event_type,
            'user_id' => USERID,
            'time' => $data['updated'],
            'data' => json_encode($this->_removeBasicProperties($data)),
            'message' => $message,
        );

        $keys = implode(', ', array_keys($data));
        $values = implode(', ', $this->_formatQueryValues($data));

        if (!db_query('INSERT INTO `redcap_entity_log` (' . $keys . ') VALUES ("' . $values . '")')) {
            return false;
        }

        return true;
    }

    protected function loggableEvents() {
        return [];
    }

    protected function _removeBasicProperties($data) {
        // Removing elementary properties, to make sure only this base class
        // can manipulate them.
        foreach (array_keys(get_class_vars(__CLASS__)) as $key) {
            unset($data[$key]);
        }

        return $data;
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

                $value = '"' . db_real_escape_string($value) . '"';
            }

            $formatted[$key] = $value;
        }

        return $formatted;
    }
}
