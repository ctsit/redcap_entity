<?php
/**
 * @file
 * Provides factory class for entities.
 */

namespace REDCapEntity;

require_once 'Entity.php';
require_once 'EntityQuery.php';

use Exception;
use ExternalModules\ExternalModules;
use REDCapEntity\EntityQuery;

define('ENTITY_TYPE_INVALID', 'invalid');
define('ENTITY_TYPE_PENDING', 'pending');
define('ENTITY_TYPE_ENABLED', 'enabled');

/**
 * Entity factory class.
 */
class EntityFactory {
    static protected $modules;
    static protected $entityTypes;

    function __construct($reset = false) {
        if ($reset || !isset(self::$entityTypes)) {
            $this->reset();
        }
    }

    /**
     * Loads entity types of a given module.
     *
     * @param object $module
     *   The module object.
     *
     * @return array|bool
     *   The list of valid entity types of the given module. If the module does
     *   not implement redcap_entity_types hook, FALSE is returned.
     */
    function loadModuleEntityTypes($module) {
        if (isset(self::$modules[$module->PREFIX])) {
            return self::$modules[$module->PREFIX]['entity_types'];
        }

        if (!method_exists($module, 'redcap_entity_types')) {
            return false;
        }

        $valid_property_types = $this->getValidPropertyTypes();
        $base_path = ExternalModules::getModuleDirectoryPath($module->PREFIX, $module->VERSION);

        foreach ($this->getValidStatuses() as $status) {
            $types[$status] = [];
        }

        foreach ($module->redcap_entity_types() as $type => $info) {
            $info['__pendencies'] = [];

            foreach (['label' => 'Label is missing.', 'properties' => 'Properties are missing.'] as $key => $msg) {
                if (empty($info[$key])) {
                    $info['__pendencies'][] = $msg;
                }
            }

            $class = '\REDCapEntity\Entity';

            if (isset($info['class']['path'])) {
                $path = $base_path . '/' . $info['class']['path'];

                if (file_exists($path)) {
                    require_once $path;
                }
                else {
                    $info['__pendencies'][] = 'Class file "' . $path .  '" does not exist.';
                }
            }

            if (isset($info['class']['name'])) {
                if (!class_exists($info['class']['name'])) {
                    $info['__pendencies'][] = 'Class "' . $info['class']['name'] .  '" could not be found.';
                }
                elseif (!is_subclass_of($info['class']['name'], 'REDCapEntity\Entity')) {
                    $info['__pendencies'][] = 'Class "' . $info['class']['name'] .  '" does not implement EntityInterface.';
                }
                else {
                    $class = $info['class']['name'];
                }
            }

            $info['class'] = $class;

            if (!is_array($info['properties'])) {
                $info['__pendencies'][] = 'Invalid properties list.';
            }
            else {
                foreach ($info['properties'] as $key => $property_info) {
                    if (empty($property_info['type']) || !in_array(strtolower($property_info['type']), $valid_property_types)) {
                        $info['__pendencies'][] = 'Invalid property type for "' . $key . '".';
                    }
                }
            }

            // TODO: validate option callbacks.
            // TODO: validate special keys and bulk operations.

            foreach (['special_keys', 'operations'] as $key) {
                if (!isset($info[$key])) {
                    $info[$key] = [];
                }
            }

            $info['module'] = $module->PREFIX;

            if (!empty($info['__pendencies'])) {
                $info['status'] = ENTITY_TYPE_INVALID;
            }
            elseif (EntityDB::checkEntityDBTable($type)) {
                $info['status'] = ENTITY_TYPE_ENABLED;
            }
            else {
                $info['status'] = ENTITY_TYPE_PENDING;
            }

            self::$entityTypes[$info['status']][$type] = $info;
            $types[$info['status']][$type] = &self::$entityTypes[$info['status']][$type];
        }

        self::$modules[$module->PREFIX] = [
            'version' => $module->VERSION,
            'entity_types' => $types,
        ];

        return $types;
    }

    function getInstance($entity_type, $id = null) {
        if (!$info = $this->getEntityTypeInfo($entity_type)) {
            return false;
        }

        try {
            $class = $info['class'];
            $entity = new $class($this, $entity_type, $id);
        }
        catch (Exception $e) {
            return false;
        }

        return $entity;
    }

    function loadInstances($entity_type, $ids) {
        if (!$info = $this->getEntityTypeInfo($entity_type)) {
            return false;
        }

        $entities = [];

        try {
            foreach ($ids as $id) {
                $class = $info['class'];
                $entities[$id] = new $class($this, $entity_type, $id);
            }
        }
        catch (Exception $e) {
            return false;
        }

        return $entities;
    }

    function create($entity_type, $data) {
        if (!$entity = $this->getInstance($entity_type)) {
            return false;
        }

        if (!$entity->create($data)) {
            return false;
        }

        return $entity;
    }

    function getEntityTypeInfo($entity_type, $statuses = ENTITY_TYPE_ENABLED) {
        if (!is_array($statuses)) {
            $statuses = [$statuses];
        }

        foreach ($statuses as $status) {
            if (isset(self::$entityTypes[$status][$entity_type])) {
                return self::$entityTypes[$status][$entity_type];
            }
        }

        return false;
    }

    function query($entity_type) {
        return new EntityQuery($this, $entity_type);
    }

    function getEntityTypes($statuses = ENTITY_TYPE_ENABLED, $module_prefix = null, $keys_only = false, $sort = true) {
        if ($module_prefix) {
            if (!isset(self::$modules[$module_prefix])) {
                return false;
            }

            $list = self::$modules[$module_prefix]['entity_types'];
        }
        else {
            $list = self::$entityTypes;
        }

        if ($statuses == 'all') {
            $statuses = $this->getValidStatuses();
        }
        else {
            if (!is_array($statuses)) {
                $statuses = [$statuses];
            }
        }

        $types = [];

        foreach ($statuses as $status) {
            if (isset($list[$status])) {
                $types += $list[$status];
            }
        }

        if ($sort) {
            ksort($types);
        }

        return $keys_only ? array_keys($types) : $types;
    }

    function getValidStatuses($include_labels = false) {
        $keys = [
            ENTITY_TYPE_ENABLED,
            ENTITY_TYPE_PENDING,
            ENTITY_TYPE_INVALID,
        ];

        if (!$include_labels) {
            return $keys;
        }

        return array_combine($keys, ['Enabled', 'Pending', 'Invalid']);
    }

    function reset() {
        self::$entityTypes = [];
        self::$modules = [];

        foreach ($this->getValidStatuses() as $status) {
            self::$entityTypes[$status] = [];
        }

        foreach (ExternalModules::getEnabledModules() as $prefix => $version) {
            $module = ExternalModules::getModuleInstance($prefix, $version);
            $this->loadModuleEntityTypes($module);
        }
    }

    protected function getValidPropertyTypes() {
        return [
            'user',
            'email',
            'text',
            'record',
            'entity_reference',
            'price',
            'project',
            'date',
            'integer',
            'boolean',
            'json',
            'long_text',
        ];
    }
}
