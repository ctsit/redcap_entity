<?php
/**
 * @file
 * Provides ExternalModule class for Department Manager module.
 */

namespace DepartmentManager\ExternalModule;

use ExternalModules\AbstractExternalModule;
use REDCapEntity\EntityDB;

/**
 * ExternalModule class for Department Manager module.
 */
class ExternalModule extends AbstractExternalModule {

    function redcap_entity_types() {
        $types = [];

        $types['department'] = [
            'label' => 'Department',
            'label_plural' => 'Departments',
            'icon' => 'home_pencil',
            'properties' => [
                'name' => [
                    'name' => 'Name',
                    'type' => 'text',
                    'required' => true,
                ],
                'institution' => [
                    'name' => 'Institution',
                    'type' => 'text',
                    'choices' => [
                        'inst_1' => 'Institution 1',
                        'inst_2' => 'Institution 2',
                        'inst_3' => 'Institution 3',
                    ],
                    'choices_type' => 'radios',
                    'required' => true,
                ],
                'project_id' => [
                    'name' => 'Project ID',
                    'type' => 'project',
                    'required' => true,
                ],
                'contact_email' => [
                    'name' => 'Contact email',
                    'type' => 'email',
                ],
                'comments' => [
                    'name' => 'Comments',
                    'type' => 'long_text',
                ],
            ],
            'operations' => ['create', 'update', 'delete'],
            'special_keys' => [
                'label' => 'label',
                'project' => 'project_id',
            ],
            'bulk_operations' => [
                'delete' => [
                    'name' => 'Delete',
                    'method' => 'delete',
                    'messages' => [
                        'success' => 'The departments have been deleted.',
                    ],
                ],
            ],
        ];
       
        return $types;
    }

	function redcap_module_system_enable($version) {
		EntityDB::buildSchema($this->PREFIX);
	}
}
