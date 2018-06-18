<?php

namespace REDCapEntity;

use ExternalModules\ExternalModules;
use RCView;
use REDCapEntity\Entity;
use REDCapEntity\StatusMessageQueue;

class EntityForm extends Page {

    protected $entity;
    protected $entityTypeInfo;
    protected $type;
    protected $fields = [];
    protected $errors = [];

    function __construct(Entity $entity) {
        $this->entity = $entity;
        $this->entityTypeInfo = $entity->getEntityTypeInfo();
        $this->type = $entity->getId() ? 'update' : 'create';
    }

    protected function buildFieldsInfo() {
        $this->fields = $this->entityTypeInfo['properties'];

        if (!empty($this->entityTypeInfo['special_keys']['uuid'])) {
            unset($this->fields[$this->entityTypeInfo['special_keys']['uuid']]);
        }
    }

    protected function getSubmitLabel() {
        return 'Save';
    }

    function render($context, $title = null, $icon = null) {
        if (!$title) {
            $title = empty($this->entityTypeInfo['name']) ? 'entity' : $this->entityTypeInfo['name'];

            if ($this->type == 'update') {
                $title = 'Edit ' . $title . ' - ' . $this->entity->getLabel();
            }
            else {
                $title = 'Create ' . $title;
            }
        }

        if (!$icon) {
            $icon = $this->type == 'update' ? 'blog_pencil' : 'blog_plus';
        }

        $this->jsFiles[] = dirname(__FILE__) . '/js/entity_form.js';
        parent::render($context, $title, $icon);
    }

    protected function renderPageBody() {
        $this->buildFieldsInfo();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = $_POST;
            $this->submit($data);
            StatusMessageQueue::clear();
        }
        else {
            $data = $this->entity->getData();
        }

        $data = array_map('htmlspecialchars', $data);
        $output = '';

        foreach ($this->fields as $key => $info) {
            if (!empty($this->entityTypeInfo['special_keys']['uuid']) && $this->entityTypeInfo['special_keys']['uuid'] == $key) {
                continue;
            }

            $row = RCView::label(['class' => 'form-label'], $info['name']);
            $attrs = ['name' => $key, 'class' => 'form-control'];

            if (!empty($info['choices_callback']) && method_exists($this->entity, $info['choices_callback'])) {
                $info['choices'] = $this->entity->{$info['choices_callback']}();
            }

            if (!empty($info['choices'])) {
                $choices_type = empty($info['choices_type']) ? 'select' : $info['choices_type'];

                switch ($choices_type) {
                    case 'select':
                        $row .= RCView::select($attrs, ['' => '-- Select --'] + $info['choices'], $data[$key]);
                        break;

                    case 'checkboxes':
                        // TODO
                        break;

                    case 'radios':
                        foreach ($info['choices'] as $value => $label) {
                            $radio = RCView::radio(['class' => 'form-check-input', 'value' => $value, 'checked' => $data[$key] == $value] + $attrs);
                            $radio .= RCView::label(['class' => 'form-check-label'], $label);

                            $row .= RCView::div(['class' => 'form-check'], $radio);
                        }

                        break;
                }
            }

            else {
                $attrs['value'] = $data[$key];

                switch ($info['type']) {
                    case 'long_text':
                    case 'json':
                        $row .= RCView::textarea($attrs);
                        break;

                    case 'price':
                        $attrs['value'] = number_format($data[$key] / 100, 2);

                        if (empty($info['prefix'])) {
                            $info['prefix'] = '$';
                        }

                    default:
                        $field = RCView::text($attrs);

                        if (!empty($info['prefix'])) {
                            $field = RCView::div(['class' => 'input-group-addon'], RCView::span(['class' => 'input-group-text'], $info['prefix'])) . ' ' . $field;
                            $field = RCView::div(['class' => 'input-group'], $field);
                        }

                        $row .= $field;
                        break;
                }
            }

            $output .= RCView::div(['class' => 'form-group'], $row);
        }

        $buttons = '';
        if (isset($_GET['__return_url'])) {
            $buttons .= RCView::a(['class' => 'btn btn-default', 'href' => htmlspecialchars($_GET['__return_url'])], 'Return to list');
        }

        $buttons .= RCView::button(['type' => 'submit', 'class' => 'btn btn-success'], $this->getSubmitLabel());

        $output .= RCView::div(['class' => 'text-right actions'], $buttons);
        echo RCView::form(['id' => 'entity-form', 'method' => 'post'], $output);
    }

    protected function submit($data) {
        $this->validate($data);

        if (!empty($this->errors)) {
            $items = '';
            foreach ($this->errors as $error) {
                $items .= RCView::li([], $error);
            }

            StatusMessageQueue::enqueue(RCView::ul([], $items), 'error');
        }
        else {
            $label = empty($this->entityTypeInfo['name']) ? 'entity' : strtolower($this->entityTypeInfo['name']);
            if (!$this->save()) {
                $msg = 'There was an error while saving the ' . $label . '.';
                StatusMessageQueue::enqueue($msg, 'error');
            }
            else {
                StatusMessageQueue::enqueue('The ' . $label . 'has been saved successfully.');

                if (isset($_GET['__return_url'])) {
                    redirect($_GET['__return_url']);
                }

                $args = [
                    'entity_type' => $this->entityTypeKey,
                    'entity_id' => $this->entity->getId(),
                    'context' => $this->context,
                ];

                redirect(REDCAP_ENTITY_FORM_URL . '&' . http_build_query($args));
            }
        }

        foreach ($data as $key => $value) {
            if (isset($this->fields[$key])) {
                $this->fields[$key]['default'] = $value;
            }
        }
    }

    protected function validate($data) {
        $filtered = [];
        foreach ($this->fields as $key => $info) {
            if (empty($info['required']) || (isset($data[$key]) && $data[$key] !== '')) {
                $filtered[$key] = $data[$key];

                if ($this->entityTypeInfo['properties'][$key]['type'] == 'price') {
                    $filtered[$key] = round($data[$key] * 100);
                }

                continue;
            }

            $label = empty($info['name']) ? $key : $info['name'];
            $this->errors[$key] = $label . ' is required.';
        }

        if ($invalid_fields = $this->entity->setData($filtered)) {
            foreach ($invalid_fields as $key) {
                $label = empty($this->fields[$key]['name']) ? $key : $this->fields[$key]['name'];
                $this->errors[$key] = $label . ' is invalid';
            }
        }
    }

    protected function save() {
        return $this->entity->save();
    }
}
