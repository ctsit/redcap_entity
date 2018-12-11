<?php

namespace REDCapEntity;

use ExternalModules\ExternalModules;
use REDCapEntity\EntityFactory;

class EntityDB {

    static function buildSchema($module_prefix, $reset = false) {
        if (!ExternalModules::getEnabledVersion($module_prefix)) {
            return;
        }

        foreach ($factory->getEntityTypes(ENTITY_TYPE_PENDING, $module_prefix, true) as $entity_type) {
            self::buildEntityDBTable($entity_type, $reset);
        }
    }

    static function dropSchema($module_prefix) {
        if (!ExternalModules::getEnabledVersion($module_prefix)) {
            return;
        }

        $factory = new EntityFactory();

        foreach ($factory->getEntityTypes([ENTITY_TYPE_ENABLED, ENTITY_TYPE_INVALID], $module_prefix, true) as $entity_type) {
            self::deleteEntityDBTable($entity_type);
        }
    }

    static function checkEntityDBTable($entity_type) {
        $q = db_query('SHOW TABLES LIKE "redcap_entity_' . db_escape($entity_type) . '"');
        return $q && db_num_rows($q);
    }

    static function buildEntityDBTable($entity_type, $reset = false) {
        $factory = new EntityFactory();

        if (!$info = $factory->getEntityTypeInfo($entity_type, ENTITY_TYPE_PENDING)) {
            return false;
        }

        $schema = [];
        foreach (['id', 'created', 'updated'] as $key) {
            $schema[$key] = ['type' => 'integer', 'unsigned' => true, 'required' => true];
        }

        $schema += $info['properties'];

        $rows = [];
        foreach ($schema as $field => $info) {
            $row = '`' . db_escape($field) . '` ';

            switch (strtolower($info['type'])) {
                case 'user':
                case 'email':
                case 'text':
                case 'record':
                    $row .= 'VARCHAR(255)';
                    break;

                case 'entity_reference':
                case 'price':
                case 'project':
                    $info['unsigned'] = true;

                case 'date':
                case 'integer':
                    $row .= 'INT';
                    if (!empty($info['unsigned'])) {
                        $row .= ' UNSIGNED';
                    }

                    break;

                case 'boolean':
                    $row .= 'TINYINT';
                    break;

                case 'json':
                case 'long_text':
                    $row .= 'TEXT';
                    break;

                default:
                    return false;
            }

            if ($info['required']) {
                $row .= ' NOT NULL';
            }

            $rows[$field] = $row;
        }

        $rows['id'] .= ' AUTO_INCREMENT';

        if ($reset) {
            db_query('DROP TABLE IF EXISTS `redcap_entity_' . $entity_type . '`');
        }

        $sql = '
            CREATE TABLE IF NOT EXISTS `redcap_entity_' . $entity_type . '`
            (' . implode(', ', $rows) . ', PRIMARY KEY (id))
            COLLATE utf8_unicode_ci';

        if (!db_query($sql)) {
            return false;
        }

        return true;
    }

    static function dropEntityDBTable($entity_type) {
        $factory = new EntityFactory();

        if (!$factory->getEntityTypeInfo($entity_type, ENTITY_TYPE_ENABLED)) {
            return false;
        }

        $q = db_query('DROP TABLE IF EXISTS `redcap_entity_' . db_escape($entity_type) . '`');
        return $q ? true : false;
    }
}
