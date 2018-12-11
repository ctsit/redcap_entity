<?php

require_once 'Page.php';

use REDCapEntity\Page;
use REDCapEntity\EntityDB;
use REDCapEntity\EntityFactory;
use ExternalModules\ExternalModules;

class SchemaManagerPage extends Page {
    protected function renderPageBody() {
        $factory = new EntityFactory();

        if (
            $_SERVER['REQUEST_METHOD'] == 'POST' &&
            !empty($_POST['entity_type']) &&
            !empty($_POST['operation']) &&
            in_array($_POST['operation'], ['build', 'drop']) &&
            EntityDB::{$_POST['operation'] . 'EntityDBTable'}($_POST['entity_type'])
        ) {
            $factory->reset();
            // TODO: message.
        }

        $statuses = $factory->getValidStatuses(true);

        $rows = '';
        $form = RCView::hidden(['name' => 'operation']) . RCView::hidden(['name' => 'entity_type']);
        echo RCView::form(['id' => 'entity_type_table_operation', 'method' => 'post'], $form);

        foreach ($factory->getEntityTypes('all') as $type => $info) {
            $info['module'] = ExternalModules::getConfig($info['module']);
            $info['module'] = $info['module']['name'];

            $info['id'] = $type;
            $info['count'] = '-';

            foreach (['id', 'label', 'module', 'count'] as $key) {
                $info[$key] = REDCap::escapeHtml($info[$key]);
            }

            $info['table'] = '-';

            $modal_vars = ['id' => 'entity-type-' . $info['id'] . '-modal'];
            $btn_attrs = [
                'type' => 'button',
                'class' => 'entity-schema-op-btn btn btn-',
                'data-entity_type' => $info['id'],
            ];

            switch ($info['status']) {
                case ENTITY_TYPE_INVALID:
                    $btn_type = 'warning';
                    $btn_text = 'see pendencies';

                    $modal_vars['title'] = 'Pendencies for <em>' . $info['id'] . '</em>';
                    $modal_vars['body'] = '';

                    foreach ($info['__pendencies'] as $msg) {
                        $modal_vars['body'] .= RCView::li([], $msg);
                    }

                    $modal_vars['body'] = RCView::ul([], $modal_vars['body']);

                    break;

                case ENTITY_TYPE_ENABLED:
                    $info['table'] = 'redcap_entity_' . $info['id'];
                    $info['count'] = $factory->query($type)->countQuery()->execute();

                    $btn_type = 'danger';
                    $btn_text = 'drop db table';

                    $modal_vars['title'] = 'Are you sure you want to delete the db table?';
                    $modal_vars['body'] = 'Table <em>redcap_entity_' . $info['id'] . '</em> will be removed';

                    if ($info['count']) {
                        $modal_vars['body'] .= ' - ' . $info['count'] . ' row(s) will be lost';
                    }

                    $modal_vars['body'] .= '. ' . $info['label'] . ' entities will not work anymore.';

                    $confirm_field = RCView::label(['for' => $info['id'] . '_delete_table'], 'Please type in the name of the db table to confirm');
                    $confirm_field .= RCView::input([
                        'id' => $info['id'] . '_delete_table',
                        'type' => 'text',
                        'class' => 'form-control delete-table-confirm-name',
                        'data-entity_type' => $info['id']
                    ]);

                    $modal_vars['body'] = RCView::div([], $modal_vars['body']) . RCView::br() . RCView::div(['class' => 'form-group'], $confirm_field);
                    $modal_vars['confirm_btn'] = [
                        'title' => 'I understand the consequences, drop this table',
                        'attrs' => $btn_attrs,
                    ];

                    $modal_vars['confirm_btn']['attrs']['data-operation'] = 'drop';
                    $modal_vars['confirm_btn']['attrs']['disabled'] = true;
                    $modal_vars['confirm_btn']['attrs']['class'] .= 'danger';

                    break;

                case ENTITY_TYPE_PENDING:
                    $btn_type = 'success';
                    $btn_text = 'create db table';

                    $modal_vars['title'] = 'Create db table?';
                    $modal_vars['body'] = '';

                    foreach (array_keys($info['properties']) as $property) {
                        $modal_vars['body'] .= RCView::li([], $property);
                    }

                    $modal_vars['body'] = 'Table <em>redcap_entity_' . $info['id'] . '</em> will be created, containg the following properties:' . RCView::ul([], $modal_vars['body']);
                    $modal_vars['confirm_btn'] = [
                        'title' => 'Create table',
                        'attrs' => $btn_attrs,
                    ];

                    $modal_vars['confirm_btn']['attrs']['data-operation'] = 'build';
                    $modal_vars['confirm_btn']['attrs']['class'] .= 'success';

                    break;
            }

            $this->loadTemplate('modal', $modal_vars);

            $btn_attrs = [
                'class' => 'btn btn-xs btn-' . $btn_type,
                'data-toggle' => 'modal',
                'data-target' => '#' . $modal_vars['id'],
            ];

            $info['button'] = RCView::button($btn_attrs, $btn_text);
            $info['status'] = $statuses[$info['status']];

            $row = [];
            foreach (['id', 'table', 'label', 'module', 'status', 'count', 'button'] as $key) {
                $row[] = $info[$key];
            }

            $rows_attributes[] = ['data-entity_type' => $info['id']];
            $rows[] = $row;
        }

        $this->loadTemplate('list', [
            'id' => 'entity_type_schema',
            'header' => ['ID', 'DB table', 'Label', 'Module', 'Status', 'Rows count', ''],
            'rows' => $rows,
        ]);

        $this->jsFiles[] = ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/js/entity_schema_manager.js');
        $this->cssFiles[] = ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/css/schema_manager.css');
    }

    // TODO: move it to a Trait class.
    protected function loadTemplate($template, $vars = []) {
        extract($vars);
        include dirname(__DIR__) . '/templates/' . $template . '.php';
    }
}
