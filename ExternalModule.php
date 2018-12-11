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

    /**
     * @inheritdoc.
     */
    function redcap_every_page_before_render($project_id) {
        define('REDCAP_ENTITY_PREFIX', $this->PREFIX);
    }
}
