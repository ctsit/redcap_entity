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

/**
 * Entity factory class.
 */
class EntityFactory {
    static protected $entityTypes;
    static protected $modules;

    function __construct($reset = false) {
        if ($reset || !isset(self::$entityTypes)) {
            $this->reset();
        }
    }

    function loadModuleEntityTypes($module) {
        if (!method_exists($module, 'redcap_entity_types')) {
            return false;
        }

        $path = ExternalModules::getModuleDirectoryPath($module->PREFIX, $module->VERSION); 

        $types = [];
        foreach ($module->redcap_entity_types() as $type => $info) {
            if (!isset(self::$entityTypes[$type])) {
                if (!isset($info['class']['name']) || !isset($info['class']['path']) || !isset($info['properties'])) {
                    continue;
                }

                include_once $path . '/' . $info['class']['path'];
                if (!is_subclass_of($info['class']['name'], 'REDCapEntity\Entity')) {
                    continue;
                }

                foreach (['special_keys', 'operations'] as $key) {
                    if (!isset($info[$key])) {
                        $info[$key] = [];
                    }
                }

                $info['module'] = $module->PREFIX;
                self::$entityTypes[$type] = $info;
            }

            $types[] = $type;
        }

        if (!empty($types)) {
            self::$modules[$module->PREFIX] = $module->VERSION;
        }

        return $types;
    }

    function getInstance($entity_type, $id = null) {
        if (!isset(self::$entityTypes[$entity_type])) {
            return false;
        }

        try {
            $class = self::$entityTypes[$entity_type]['class']['name'];
            $entity = new $class($this, $entity_type, $id);
        }
        catch (Exception $e) {
            return false;
        }

        return $entity;
    }

    function loadInstances($entity_type, $ids) {
        if (!isset(self::$entityTypes[$entity_type])) {
            return false;
        }

        $entities = [];

        try {
            foreach ($ids as $id) {
                $class = self::$entityTypes[$entity_type]['class']['name'];
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

    function getEntityTypeInfo($entity_type) {
        if (!isset(self::$entityTypes[$entity_type])) {
            return false;
        }

        return self::$entityTypes[$entity_type];
    }

    function query($entity_type) {
        return new EntityQuery($this, $entity_type);
    }

    function entityTypeExists($entity_type) {
        return isset(self::$entityTypes[$entity_type]);
    }

    function getEntityTypes($keys_only = false) {
        return $keys_only ? array_keys(self::$entityTypes) : self::$entityTypes;
    }

    function getModules() {
        return self::$modules;
    }

    protected function reset() {
        self::$entityTypes = [];
        self::$modules = [];

        foreach (ExternalModules::getEnabledModules() as $prefix => $version) {
            $module = ExternalModules::getModuleInstance($prefix, $version);
            $this->loadModuleEntityTypes($module);
        }
    }
}
