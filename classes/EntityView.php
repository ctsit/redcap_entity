<?php

namespace REDCapEntity;

use Exception;
use ExternalModules\ExternalModules;
use RedCapDB;
use RCView;
use REDCap;
use REDCapEntity\EntityFactory;
use REDCapEntity\Page;
use User;

class EntityView extends Page {

    protected $entityFactory;
    protected $entityTypeKey;
    protected $entityTypeInfo;
    protected $pageSize;
    protected $pagerSize;
    protected $currPage;
    protected $rows = [];
    protected $totalRows = 0;
    protected $context;

    function __construct($entity_type, $page_size = 25, $pager_size = 10) {
        $this->entityFactory = new EntityFactory();

        if (!$info = $this->entityFactory->getEntityTypeInfo($entity_type)) {
            throw new Exception('Invalid entity type.');
        }

        $this->entityTypeKey = $entity_type;
        $this->entityTypeInfo = $info;

        $this->pageSize = $page_size;
        $this->pagerSize = $pager_size;
        $this->currPage = empty($_GET['pager']) || $_GET['pager'] != intval($_GET['pager']) ? 1 : $_GET['pager'];
    }

    function render($context, $title = null, $icon = 'application_view_columns') {
        if (!$title) {
            $title = isset($this->entityTypeInfo['label_plural']) ? $this->entityTypeInfo['label_plural'] : 'Entities';
        }

        $this->context = $context;
        parent::render($context, $title, $icon);
    }

    protected function renderPageBody() {
        $this->loadStyles();
        $this->renderAddButton();
        $this->renderExposedFilters();
        $this->renderTable();
        $this->renderPager();
    }

    protected function renderAddButton() {
        $operations = $this->getOperations();
        if (empty($operations['create'])) {
            return;
        }

        $args = [
            'entity_type' => $this->entityTypeKey,
            'context' => $this->context,
            '__return_url' => REDCap::escapeHtml($_SERVER['REQUEST_URI']),
        ];

        $title = RCView::img(['src' => APP_PATH_IMAGES . 'add.png']) . ' ';
        $title .= isset($this->entityTypeInfo['label']) ? $this->entityTypeInfo['label'] : 'Entity';

        echo RCView::a([
            'href' => REDCAP_ENTITY_FORM_URL . '&' . http_build_query($args),
            'class' => 'btn btn-default',
        ], $title);
    }

    protected function renderExposedFilters() {
        // TODO.
    }

    protected function renderTable() {
        $this->buildTableRows();

        if (empty($this->rows)) {
            echo RCView::div([], $this->getEmptyResultsMessage());
            return;
        }

        $this->loadTemplate('entity_list', [
            'header' => $this->getTableHeader(),
            'rows' => $this->rows,
        ]);
    }

    protected function renderPager() {
        if ($this->totalRows <= $this->pageSize) {
            return;
        }

        $this->loadTemplate('pager', [
            'list_max_size' => $this->pageSize,
            'pager_max_size' => $this->pagerSize,
            'total_rows' => $this->totalRows,
        ]);
    }

    protected function buildTableRows() {
        $query = $this->getQuery();

        if ($this->pageSize) {
            $count_query = clone $query;
            $this->totalRows = $count_query->countQuery()->execute();

            $query->limit($this->pageSize, ($this->currPage - 1) * $this->pageSize);
        }

        if (!empty($_GET['__order_by']) && in_array($_GET['__order_by'], $this->getSortableColumns())) {
            $query->orderBy($_GET['__order_by'], !empty($_GET['__desc']));
        }
        else {
            $query->orderBy('updated', true);
        }

        if (!$entities = $query->execute()) {
            return;
        }

        foreach ($entities as $id => $entity) {
            $this->rows[$id] = $this->buildTableRow($entity);
        }
    }

    protected function buildTableRow($entity) {
        $data = array_map('REDCap::escapeHtml', $entity->getData());
        $properties = $this->entityTypeInfo['properties'] += [
            'id' => ['name' => '#', 'type' => 'integer'],
            'created' => ['name' => 'Created', 'type' => 'date'],
            'updated' => ['name' => 'Updated', 'type' => 'date'],
        ];

        $row = [];

        foreach (array_keys($this->getTableHeaderLabels()) as $key) {
            if (in_array($key, ['__update', '__delete'])) {
                $args = [
                    'entity_id' => $entity->getId(),
                    'entity_type' => $this->entityTypeKey,
                    'context' => $this->context,
                    '__return_url' => $_SERVER['REQUEST_URI'],
                ];

                $path = REDCAP_ENTITY_FORM_URL;

                if ($key == '__update') {
                    $title = 'edit';
                }
                else {
                    $args['__delete'] = true;
                    $title = 'delete';
                }

                $row[$key] = RCView::a(['href' => $path . '&' . http_build_query($args)], $title);
                continue;
            }

            if (!isset($data[$key]) || $data[$key] === '') {
                $row[$key] = '-';
                continue;
            }

            $info = $properties[$key];

            if (!empty($info['choices']) && isset($info['choices'][$data[$key]])) {
                $row[$key] = $choices[$data[$key]];
                continue;
            }

            if (!empty($info['choices_callback']) && method_exists($entity, $info['choices_callback'])) {
                $choices = $entity->{$info['choices_callback']}();

                if (isset($choices[$data[$key]])) {
                    $row[$key] = $choices[$data[$key]];
                    continue;
                }
            }

            $row[$key] = $data[$key];

            switch ($info['type']) {
                case 'date':
                    $format = empty($info['format']) ? 'm/d/Y - h:ia' : $info['format'];
                    $row[$key] = date($format, $data[$key]);
                    break;

                case 'price':
                    $row[$key] = '$' . number_format($data[$key] / 100, 2);
                    break;

                case 'entity_reference':
                    if (empty($info['entity_type'])) {
                        break;
                    }

                    if (!$target_info = $this->entityFactory->getEntityTypeInfo($info['entity_type'])) {
                        break;
                    }

                    if (!isset($target_info['special_keys']['label'])) {
                        break;
                    }

                    if (!$referenced_entity = $this->entityFactory->getInstance($info['entity_type'], $data[$key])) {
                        break;
                    }

                    // TODO: add link to the entity page, if exists and if
                    // user has access.

                    $row[$key] = REDCap::escapeHtml($referenced_entity->getLabel());
                    break;

                case 'project':
                    $db = new RedCapDB();
                    if (!$project = $db->getProject($data[$key])) {
                        break;
                    }

                    // TODO: check access to add link.
                    $row[$key] = RCView::a(['href' => APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $row[$key], 'target' => '_blank'], $project->app_title);
                    break;

                case 'user':
                    if (!$user_info = User::getUserInfo($data[$key])) {
                        break;
                    }

                    $url = SUPER_USER || ACCOUNT_MANAGER ? APP_PATH_WEBROOT . 'ControlCenter/view_users.php?username=' . $data[$key] : 'mailto:' . $user_info['user_email'];
                    $row[$key] = '(' . $data[$key] . ') ' . $user_info['user_firstname'] . ' ' . $user_info['user_lastname'];
                    $row[$key] = RCView::a(['href' => $url, 'target' => '_blank'], REDCap::escapeHtml($row[$key]));
            }
        }

        return $row;
    }

    protected function getFilters() {
        return [];
    }

    protected function getTableHeaderLabels() {
        $labels = ['id' => '#'];

        foreach ($this->entityTypeInfo['properties'] as $key => $info) {
            if (!in_array($info['type'], ['json', 'long_text'])) {
                $labels[$key] = $info['name'];
            }
        }

        $labels += [
            'created' => 'Created',
            'updated' => 'Updated',
        ];

        $operations = $this->getOperations();
        foreach (['update', 'delete'] as $op) {
            if (!empty($operations[$op])) {
                $labels['__' . $op] = '';
            }
        }

        return $labels;
    }

    protected function getTableHeader() {
        $args = [];
        $url = parse_url($_SERVER['REQUEST_URI']);
        $curr_key = '';

        if (!empty($url['query'])) {
            parse_str($url['query'], $args);

            if (isset($args['__order_by'])) {
                $curr_key = $args['__order_by'];
                $direction = empty($args['__desc']) ? 'up' : 'down';
                $icon = RCView::img(['src' => APP_PATH_IMAGES . 'bullet_arrow_' . $direction . '.png']);
            }

            unset($args['__order_by'], $args['__desc']);
        }

        $header = $this->getTableHeaderLabels();
        foreach ($this->getSortableColumns() as $key) {
            if (!isset($header[$key])) {
                continue;
            }

            $args['__order_by'] = $key;

            if ($key == $curr_key) {
                $header[$key] = $icon . ' ' .  $header[$key];

                if ($direction == 'up') {
                    $args['__desc'] = '1';
                }
            }

            $header[$key] = RCView::a(['href' => $url['path'] . '?' . http_build_query($args)], $header[$key]);
        }

        return $header;
    }

    protected function getSortableColumns() {
        $sortable = ['id', 'created', 'updated'];
        if (isset($this->entityTypeInfo['special_keys']['name'])) {
            $sortable[] = $this->entityTypeInfo['special_keys']['name'];
        }

        return $sortable;
    }

    protected function getQuery() {
        $query = $this->entityFactory->query($this->entityTypeKey);

        foreach ($this->getFilters() as $filter) {
            if (isset($_GET[$filter]) && $_GET[$filter] !== '') {
                $query->condition($filter, $_GET[$filter]);
            }
        }

        if (isset($this->entityTypeInfo['special_keys']['project']) && defined('PROJECT_ID')) {
            $query->condition($this->entityTypeInfo['special_keys']['project'], PROJECT_ID);
        }

        return $query;
    }

    protected function getEmptyResultsMessage() {
        $label = isset($this->entityTypeInfo['label_plural']) ? strtolower($this->entityTypeInfo['label_plural']) : 'results';
        return 'There are no ' . $label . '.';
    }

    protected function getOperations() {
        return isset($this->entityTypeInfo['operations']) ? $this->entityTypeInfo['operations'] : [];
    }

    function loadStyles() {
        echo '<style>#pagecontainer { max-width: 1500px; }</style>';
    }

    protected function loadTemplate($template, $vars = []) {
        extract($vars);
        include dirname(__DIR__) . '/templates/' . $template . '.php';
    }
}
