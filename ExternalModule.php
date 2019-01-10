<?php
/**
 * @file
 * Provides ExternalModule class for REDCap Entity module.
 */

namespace REDCapEntity\ExternalModule;

require_once 'classes/EntityDB.php';
require_once 'classes/EntityFactory.php';
require_once 'classes/Page.php';
require_once 'classes/EntityFormTrait.php';
require_once 'classes/EntityForm.php';
require_once 'classes/EntityDeleteForm.php';
require_once 'classes/EntityList.php';
require_once 'classes/StatusMessageQueue.php';

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

/**
 * ExternalModule class for REDCap Entity module.
 */
class ExternalModule extends AbstractExternalModule {

    /**
     * @inheritdoc.
     */
    function redcap_every_page_before_render($project_id = null) {
        define('REDCAP_ENTITY_PREFIX', $this->PREFIX);
    }

    /**
     * @inheritdoc
     */
    function redcap_every_page_top($project_id) {
        if (strpos(PAGE, 'ExternalModules/manager/control_center.php') === false) {
            return;
        }

        $this->includeJs('manager/js/global_config.js');
        $this->setJsSettings(['modulePrefix' => $this->PREFIX]);
    }

    /**
     * @inheritdoc
     */
    function redcap_module_system_enable($version) {
        // Making sure the module is enabled on all projects.
        $this->setSystemSetting(ExternalModules::KEY_ENABLED, true);
    }

    /**
     * Includes a local JS file.
     *
     * @param string $path
     *   The relative path to the js file.
     */
    function includeJs($path) {
        echo '<script src="' . $this->getUrl($path) . '"></script>';
    }

    /**
     * Sets JS settings.
     *
     * @param mixed $settings
     *   The setting settings.
     */
    protected function setJsSettings($settings) {
        echo '<script>redcapEntity = ' . json_encode($settings) . ';</script>';
    }
}
