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

    /**
     * @return \string[][]
     * @throws \Exception
     */
    public function getProjectList() {

        // get the project list that conforms to the user rights of the current user
        $sql = "SELECT p.project_id, p.app_title
                    FROM redcap_projects p, redcap_user_rights u
                    WHERE p.project_id = u.project_id
                        AND u.username = '" . db_real_escape_string( USERID ) . "'";

        if (SUPER_USER) {
            $sql = "SELECT p.project_id, p.app_title
                    FROM redcap_projects p
                    WHERE p.project_id > 15
                    AND p.date_deleted IS NULL";
        }

        $queryResults = $this->query( $sql, [] );

        // get the Array
        $resultArray = $queryResults->fetch_all( MYSQLI_ASSOC );
        // Define the array with the Blank entry to be first.
        $listArray = [ [ "id" => "", "text" => "--- None ---" ] ];
        foreach ( $resultArray as $result ) {
            // Translate the data to the standard output
            $id = $result["project_id"];
            $name = $result["app_title"];

            // Accumulate the translated data
            $listArray[] = [
                'id'   => $id,
                'text' => "($id) $name"
            ];
        };

        return $listArray;
    }
}
