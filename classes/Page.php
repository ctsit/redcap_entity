<?php

namespace REDCapEntity;

use REDCap;
use RCView;

abstract class Page {
    protected $jsFiles = [];
    protected $jsSettings = [];
    protected $cssFiles = [];

    abstract protected function renderPageBody();

    function render($context, $title, $icon) {
        if (!in_array($context, $this->getValidContexts())) {
            redirect(APP_PATH_WEBROOT_PARENT);
        }

        $title = RCView::img(['src' => APP_PATH_IMAGES . $icon . '.png']) . ' ' . REDCap::escapeHtml($title);

        switch ($context) {
            case 'control_center':
                $title = RCView::h4([], $title);
                $header_path = 'ControlCenter/header.php';
                $footer_path = 'ControlCenter/footer.php';

                break;

            case 'project':
                $title = RCView::div(['class' => 'projhdr'], $title);
                $header_path = 'ProjectGeneral/header.php';
                $footer_path = 'ProjectGeneral/footer.php';

                break;

            case 'global':
                // TODO
                break;

            default:
                return;
        }

        extract($GLOBALS);

        include_once APP_PATH_DOCROOT . $header_path;
        echo $title;

        $this->renderPageBody();
        $this->loadPageScripts();
        $this->loadPageStyles();

        include_once APP_PATH_DOCROOT . $footer_path;
    }

    protected function getValidContexts() {
        return ['project', 'global', 'control_center'];
    }

    /**
     * Includes JS files and settings.
     */
    protected function loadPageScripts() {
        foreach ($this->jsSettings as $key => $setting) {
            echo '<script>' . $key . ' = ' . json_encode($setting, JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS) . ';</script>';
        }

        foreach ($this->jsFiles as $path) {
            echo '<script src="' . $path . '"></script>';
        }

        $this->jsFiles = [];
        $this->jsSettings = [];
    }

    /**
     * Includes CSS files.
     */
    protected function loadPageStyles() {
        foreach ($this->cssFiles as $path) {
            echo '<link rel="stylesheet" href="' . $path . '">';
        }

        $this->cssFiles = [];
    }
}
