<?php
/**
 * @file
 * Provides ExternalModule class for REDCap Entity module.
 */

namespace REDCapEntity\ExternalModule;

require_once 'classes/EntityDB.php';
require_once 'classes/EntityFactory.php';
require_once 'classes/Page.php';
require_once 'classes/EntityDeleteForm.php';
require_once 'classes/EntityForm.php';
require_once 'classes/EntityList.php';
require_once 'classes/StatusMessageQueue.php';

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCapEntity\EntityDB;
use REDCapEntity\EntityFactory;
use RCView;

/**
 * ExternalModule class for REDCap Entity module.
 */
class ExternalModule extends AbstractExternalModule {

    function redcap_every_page_before_render($project_id) {
        define('REDCAP_ENTITY_PREFIX', $this->PREFIX);

        if (strpos(PAGE, 'ExternalModules/manager/ajax/disable-module.php') === false) {
            return;
        }

        if ($project_id || !SUPER_USER || empty($_POST['module'])) {
            return;
        }

        $factory = new EntityFactory();
        $modules = $factory->getModules();

        if (!isset($modules[$_POST['module']])) {
            return;
        }

        $module = ExternalModules::getModuleInstance($_POST['module'], $modules[$_POST['module']]);
        EntityDB::dropSchema($module);

    }

    function redcap_every_page_top($project_id) {
        if (strpos(PAGE, 'ExternalModules/manager/control_center.php') !== false) {
            $factory = new EntityFactory();
            $modules = $factory->getModules();

            foreach (ExternalModules::getEnabledModules() as $prefix => $version) {
                if (isset($modules[$prefix])) {
                    $module = ExternalModules::getModuleInstance($prefix, $version);
                    EntityDB::buildSchema($module);
                }
            }

            $checkbox = RCView::checkbox(['class' => 'form-check-input', 'id' => 'redcap-entity-drop-schema']) .
                        RCView::label(['class' => 'form-check-label', 'for' => 'redcap-entity-drop-schema'], 'Drop entity DB tables');

            $settings = [
                'modules' => $factory->getModules(),
                'disableCheckbox' => RCView::div([
                    'class' => 'form-check form-group redcap-entity-disable',
                    'style' => 'margin-top: 15px;'
                ], $checkbox),
            ];

            echo '<script src="' . $this->getUrl('manager/js/global_config.js') . '"></script>';
        }
        else {
            $settings = [
                'projectReferenceUrl' => ExternalModules::$BASE_URL . 'manager/ajax/get-project-list.php',
                'entityReferenceUrl' => $this->getUrl('manager/ajax/entity_reference.php'),
            ];
        }

        echo '<script>redcapEntity = ' . json_encode($settings) . ';</script>';
    }
}
