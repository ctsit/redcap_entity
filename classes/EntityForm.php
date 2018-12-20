<?php

namespace REDCapEntity;

use ExternalModules\ExternalModules;
use RCView;
use REDCap;
use REDCapEntity\Entity;
use REDCapEntity\StatusMessageQueue;
use ToDoList;
use User;

class EntityForm extends Page {

    protected $entity;
    protected $entityTypeInfo;
    protected $type;
    protected $errors = [];
    protected $formUrl;

    function __construct(Entity $entity) {
        $this->entity = $entity;
        $this->entityFactory = $entity->getFactory();
        $this->entityTypeInfo = $entity->getEntityTypeInfo();
        $this->type = $entity->getId() ? 'update' : 'create';
        $this->formUrl = ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/entity.php');
    }

    protected function buildFieldsInfo() {
        $this->fields = $this->entityTypeInfo['properties'];
    }

    protected function getSubmitLabel() {
        return 'Save';
    }

    function render($context, $title = null, $icon = null) {
        if (!$title) {
            $title = empty($this->entityTypeInfo['label']) ? 'entity' : $this->entityTypeInfo['label'];

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

        $this->cssFiles[] = ExternalModules::$BASE_URL . 'manager/css/select2.css';
        $this->cssFiles[] = ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/css/entity_form.css');

        $this->jsFiles[] = ExternalModules::$BASE_URL . 'manager/js/select2.js';
        $this->jsFiles[] = ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/js/entity_fields.js');

        $this->jsSettings['redcapEntity'] = [
            'entityReferenceUrl' => ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/ajax/entity_reference.php'),
            'projectReferenceUrl' => ExternalModules::$BASE_URL . 'manager/ajax/get-project-list.php',
        ];

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

        $output = $this->buildFormElements($this->entity, $this->entityTypeInfo, $data);

        $buttons = '';
        if (isset($_GET['__return_url'])) {
            $buttons .= RCView::a(['class' => 'btn btn-default', 'href' => REDCap::escapeHtml($_GET['__return_url'])], 'Return to list');
        }

        $buttons .= RCView::button(['type' => 'submit', 'class' => 'btn btn-success'], REDCap::escapeHtml($this->getSubmitLabel()));

        $output .= RCView::div(['class' => 'text-right actions'], $buttons);
        echo RCView::form(['id' => 'entity-form', 'method' => 'post'], $output);
    }

    protected function buildFormElements($entity, $entity_type_info, $data) {
        $rows = '';

        foreach ($entity_type_info['properties'] as $key => $info) {
            $label = REDCap::escapeHtml($info['name']);

            if ($info['type'] == 'entity_reference' && !empty($info['entity_type'])) {
                if (!$entity_type_info = $this->entityFactory->getEntityTypeInfo($info['entity_type'])) {
                    continue;
                }
            }

            $data[$key] = REDCap::escapeHtml($data[$key]);

            if (!empty($entity_type_info['special_keys']['uuid']) && $entity_type_info['special_keys']['uuid'] == $key) {
                continue;
            }

            $attrs = ['name' => $key, 'class' => 'form-control', 'id' => 'redcap-entity-prop-' . $key];

            if (!empty($info['readonly'])) {
                $attrs['readonly'] = '';
            }

            if (!empty($info['disabled'])) {
                $attrs['disabled'] = '';
            }

            if ($info['type'] == 'boolean') {
                $attrs['value'] = '1';

                if (!empty($data[$key])) {
                    $attrs['checked'] = true;
                }

                $row = RCView::checkbox(['class' => 'form-check-input'] + $attrs);
                $row .= RCView::label(['class' => 'form-check-label'], $label);

                $rows .= RCView::div(['class' => 'form-check form-group'], $row);
                continue;
            }

            $row = RCView::label(['class' => 'form-label', 'for' => $attrs['id']], $label);

            if (!empty($info['choices'])) {
                $choices = $info['choices'];
                $attrs['class'] .= ' redcap-entity-select';
            }
            elseif (!empty($info['choices_callback'])) {
                if (method_exists($this->entity, $info['choices_callback'])) {
                    $info['choices'] = $this->entity->{$info['choices_callback']}();
                    $attrs['class'] .= ' redcap-entity-select';
                }
            }
            elseif ($info['type'] == 'record') {
                if (defined('PROJECT_ID')) {
                    $info['choices'] = Records::getRecordsAsArray(PROJECT_ID);
                }
            }
            elseif ($info['type'] == 'project') {
                $info['choices'] = [];
                $attrs['class'] .= ' redcap-entity-select-project';

                if (!empty($data[$key]) && ($title = ToDoList::getProjectTitle($data[$key]))) {
                    $info['choices'][$data[$key]] = '(' . $data[$key] . ') ' . REDCap::escapeHtml($title);
                }
            }
            elseif ($info['type'] == 'user') {
                $info['choices'] = [];
                $attrs['class'] .= ' redcap-entity-select-user';

                if (!empty($data[$key]) && ($user_info = User::getUserInfo($data[$key]))) {
                    $full_name = $user_info['user_firstname'] . ' ' . $user_info['user_lastname'];
                    $info['choices'][$data[$key]] = $data[$key] . ' (' . REDCap::escapeHtml($full_name . ') - ' . $user_info['user_email']);
                }
            }
            elseif ($info['type'] == 'entity_reference' && !empty($info['entity_type'])) {
                $info['choices'] = [];
                $attrs['class'] .= ' redcap-entity-select-entity-reference';
                $attrs['data-entity_type'] = $info['entity_type'];

                if (!empty($data[$key]) && ($entity = $this->entityFactory->getInstance($info['entity_type'], $data[$key]))) {
                    $info['choices'][$data[$key]] = REDCap::escapeHtml($entity->getLabel());
                }
            }

            if (isset($info['choices']) && is_array($info['choices'])) {
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
                        if ($info['type'] == 'date') {
                            if (is_numeric($attrs['value'])) {
                                $attrs['value'] = date('m/d/Y', $attrs['value']);
                            }

                            if (empty($info['readonly'])) {
                                $this->jsSettings['redcapEntity']['dateFields'][] = $key;
                            }
                        }

                        $field = RCView::text($attrs);

                        if (!empty($info['prefix'])) {
                            $field = RCView::div(['class' => 'input-group-addon'], RCView::span(['class' => 'input-group-text'], $info['prefix'])) . ' ' . $field;
                            $field = RCView::div(['class' => 'input-group'], $field);
                        }

                        $row .= $field;
                        break;
                }
            }

            $rows .= RCView::div(['class' => 'form-group'], $row);
        }

        return $rows;
    }

    protected function submit($data) {
        $this->validate($data, $this->entity, $this->entityTypeInfo);

        if (!empty($this->errors)) {
            $items = '';
            foreach ($this->errors as $error) {
                $items .= RCView::li([], $error);
            }

            StatusMessageQueue::enqueue(RCView::ul([], $items), 'error');
        }
        else {
            $label = empty($this->entityTypeInfo['label']) ? 'entity' : $this->entityTypeInfo['label'];
            if (!$this->save()) {
                $msg = 'There was an error while saving the ' . $label . '.';
                StatusMessageQueue::enqueue($msg, 'error');
            }
            else {
                StatusMessageQueue::enqueue('The ' . $label . ' has been saved successfully.');

                if (isset($_GET['__return_url'])) {
                    redirect($_GET['__return_url']);
                }

                $args = [
                    'entity_type' => $this->entityTypeKey,
                    'entity_id' => $this->entity->getId(),
                    'context' => $this->context,
                ];

                redirect($this->formUrl . '&' . http_build_query($args));
            }
        }

        foreach ($data as $key => $value) {
            if (isset($this->entityTypeInfo['properties'][$key])) {
                $this->entityTypeInfo['properties'][$key]['default'] = $value;
            }
        }
    }

    protected function validate($data, $entity, $entity_type_info) {
        $elements = $entity_type_info['properties'];
        $filtered = [];

        foreach ($elements as $key => $info) {
            if (empty($info['required']) || (isset($data[$key]) && $data[$key] !== '')) {
                $filtered[$key] = $data[$key];

                switch ($info['type']) {
                    case 'price':
                        $filtered[$key] = round($data[$key] * 100);
                        break;

                    case 'date':
                        $filtered[$key] = strtotime($data[$key]);
                        break;

                    case 'boolean':
                        $filtered[$key] = !empty($data[$key]);
                        break;
                }

                continue;
            }

            $label = empty($info['name']) ? $key : $info['name'];
            $this->errors[$key] = $label . ' is required.';
        }

        if ($invalid_fields = $entity->setData($filtered)) {
            foreach ($invalid_ields as $key) {
                $label = empty($elements[$key]['name']) ? $key : $elements[$key]['name'];
                $this->errors[$key] = $label . ' is invalid';
            }
        }
    }

    protected function save($entity) {
        return $this->entity->save();
    }
}
