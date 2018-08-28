<?php

namespace REDCapEntity;

use ExternalModules\ExternalModules;
use REDCapEntity\EntityFactory;

class EntityDB {

    static function buildSchema($module, $reset = false) {
        $factory = new EntityFactory();
        if (!$types = $factory->loadModuleEntityTypes($module)) {
            return false;
        }

        foreach ($types as $entity_type) {
            self::buildEntityDBTable($entity_type, $factory, $reset);
        }

        return true;
    }

    static function dropSchema($module) {
        if (!method_exists($module, 'redcap_entity_types')) {
            return false;
        }

        $types = $module->redcap_entity_types();
        foreach (array_keys($types) as $entity_type) {
            db_query('DROP TABLE IF EXISTS `redcap_entity_' . $entity_type . '`');
        }
    }

    protected static function buildEntityDBTable($entity_type, EntityFactory $factory, $reset = false) {
        if (!$info = $factory->getEntityTypeInfo($entity_type)) {
            return false;
        }

        $schema = [];
        foreach (['id', 'created', 'updated'] as $key) {
            $schema[$key] = ['type' => 'integer', 'unsigned' => true, 'required' => true];
        }

        $schema += $info['properties'];

        $rows = [];
        foreach ($schema as $field => $info) {
            $row = '`' . db_real_escape_string($field) . '` ';

            switch (strtolower($info['type'])) {
                case 'user':
                case 'text':
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
}
