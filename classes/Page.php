<?php

namespace REDCapEntity;

use RCView;

abstract class Page {
    protected $jsFiles = [];
    protected $cssFiles = [];

    abstract protected function renderPageBody();

    function render($context, $title, $icon) {
        if (!in_array($context, $this->getValidContexts())) {
            redirect(APP_PATH_WEBROOT_PARENT);
        }

        switch ($context) {
            case 'control_center':
                $title = RCView::h4([], RCView::img(['src' => APP_PATH_IMAGES . $icon . '.png']) . ' ' . htmlspecialchars($title));
                $header_path = APP_PATH_DOCROOT . 'ControlCenter/header.php';
                $footer_path = APP_PATH_DOCROOT . 'ControlCenter/footer.php';

                break;

            case 'project':
                // TODO
                break;

            case 'global':
                // TODO
                break;

            default:
                return;
        }

        extract($GLOBALS);

        include_once $header_path;
        echo $title;

        $this->loadPageScripts();
        $this->loadPageStyles();
        $this->renderPageBody();

        include_once $footer_path;
    }

    protected function getValidContexts() {
        return ['project', 'global', 'control_center'];
    }

    /**
     * Includes JS files.
     */
    protected function loadPageScripts() {
        foreach ($this->jsFiles as $path) {
            echo '<script src="' . $path . '"></script>';
        }

        $this->jsFiles = [];
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
