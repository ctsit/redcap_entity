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

/**
 * ExternalModule class for REDCap Entity module.
 */
class ExternalModule extends AbstractExternalModule {

    function redcap_every_page_before_render($project_id) {
        define('REDCAP_ENTITY_PREFIX', $this->PREFIX);
    }

    function redcap_every_page_top($project_id) {
        $settings = [
            'projectReferenceUrl' => ExternalModules::$BASE_URL . 'manager/ajax/get-project-list.php',
            'entityReferenceUrl' => $this->getUrl('manager/ajax/entity_reference.php'),
        ];

        echo '<script>redcapEntity = ' . json_encode($settings) . ';</script>';
    }
}
