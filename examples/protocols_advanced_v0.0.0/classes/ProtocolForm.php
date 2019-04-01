<?php

namespace REDCapProtocols;

use ExternalModules\ExternalModules;
use REDCapEntity\EntityForm;

class ProtocolForm extends EntityForm {
    protected function getSubmitLabel() {
        return 'Save Protocol';
    }

    protected function renderPageBody() {
        // Adds helper text.
        echo '<div class="yellow helper-info">Helper information about this form.</div>';

        // Adds assets.
        $this->cssFiles[] = ExternalModules::getUrl('protocols', 'css/protocol_form.css');
        $this->jsFiles[] = ExternalModules::getUrl('protocols', 'js/protocol_form.js');

        parent::renderPageBody();
    }

    protected function buildFieldsInfo() {
        parent::buildFieldsInfo();

        // Sets "Title" as the 1st field.
        $title = $this->fields['title'];
        unset($this->fields['title']);
        $this->fields = ['title' => $title] + $this->fields;
    }
}
