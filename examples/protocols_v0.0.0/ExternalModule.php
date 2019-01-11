<?php
/**
 * @file
 * Provides ExternalModule class for Protocols module.
 */

namespace Protocols\ExternalModule;

use ExternalModules\AbstractExternalModule;
use REDCapEntity\EntityDB;

/**
 * ExternalModule class for Protocols module.
 */
class ExternalModule extends AbstractExternalModule {

    function redcap_entity_types() {
        $types = [];
    
        $types['study_site'] = [
            'label' => 'Study site',
            'label_plural' => 'Study sites',
            'icon' => 'home_pencil',
            'properties' => [
                'name' => [
                    'name' => 'Name',
                    'type' => 'text',
                    'required' => true,
                ],
                'project_id' => [
                    'name' => 'Project ID',
                    'type' => 'project',
                    'required' => true,
                ],
            ],
            'special_keys' => [
                'label' => 'name',
                'project' => 'project_id',
            ],
        ];
    
        $types['protocol'] = [
            'label' => 'Protocol',
            'label_plural' => 'Protocols',
            'icon' => 'codebook',
            'properties' => [
                'number' => [
                    'name' => 'Number',
                    'type' => 'text',
                    'required' => true,
                ],
                'title' => [
                    'name' => 'Title',
                    'type' => 'text',
                    'required' => true,
                ],
                'status' => [
                    'name' => 'Status',
                    'type' => 'text',
                    'choices' => [
                        'in_study' => 'In Study',
                        'pending' => 'Pending',
                        'expired' => 'Expired',
                    ],
                    'required' => true,
                ],
                'created_by' => [
                    'name' => 'Created by',
                    'type' => 'user',
                    'required' => true,
                ],
                'project_id' => [
                    'name' => 'Project ID',
                    'type' => 'project',
                    'required' => true,
                ],
                'study_site' => [
                    'name' => 'Study site',
                    'type' => 'entity_reference',
                    'entity_type' => 'study_site',
                ],
                'pi' => [
                    'name' => 'PI',
                    'type' => 'user',
                ],
            ],
            'special_keys' => [
                'label' => 'number',
                'project' => 'project_id',
                'author' => 'created_by',
            ],
        ];

        return $types;
    }

	function redcap_module_system_enable($version) {
		EntityDB::buildSchema($this->PREFIX);
	}
}
