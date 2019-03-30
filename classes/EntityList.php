<?php

namespace REDCapEntity;

use Exception;
use ExternalModules\ExternalModules;
use Records;
use RedCapDB;
use RCView;
use REDCap;
use REDCapEntity\EntityFactory;
use REDCapEntity\Page;
use REDCapEntity\StatusMessageQueue;
use ToDoList;
use User;

class EntityList extends Page {

    protected $entityFactory;
    protected $entityTypeKey;
    protected $entityTypeInfo;
    protected $module;
    protected $operations = [];
    protected $pageSize = 25;
    protected $pagerSize = 10;
    protected $currPage = 1;
    protected $cols;
    protected $sortableCols;
    protected $exposedFilters;
    protected $header = [];
    protected $rows = [];
    protected $rowsAttributes = [];
    protected $totalRows = 0;
    protected $context;
    protected $bulkDeleteEnabled = false;
    protected $formUrl;
    protected $validOperations = ['create', 'delete', 'update'];
    protected $bulkOperations = [];

    function __construct($entity_type, $module) {
        $this->entityFactory = new EntityFactory();

        if (!$info = $this->entityFactory->getEntityTypeInfo($entity_type)) {
            throw new Exception('Invalid entity type.');
        }

        $this->module = $module;
        $this->entityTypeKey = $entity_type;
        $this->entityTypeInfo = $info;

        if (!empty($_GET['pager']) && $_GET['pager'] == intval($_GET['pager'])) {
            $this->currPage = $_GET['pager'];
        }

        $fields = $this->getFields();

        $this->cols = array_keys($fields);
        $this->formUrl = ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/entity.php');
    }

    function setPager($page_size, $pager_size) {
        $this->pageSize = $page_size;
        $this->pagerSize = $pager_size;

        return $this;
    }

    function setOperations($operations) {
        if (empty($operations) || !is_array($operations)) {
            return;
        }

        $this->operations = [];
        foreach ($operations as $operation) {
            if (in_array($operation, $this->validOperations)) {
                $this->operations[] = $operation;
            }
        }

        return $this;
    }

    function setCols($cols) {
        $this->cols = $cols;
        return $this;
    }

    function setSortableCols($cols) {
        $this->sortableCols = $cols;
        return $this;
    }

    function setExposedFilters($filters) {
        $this->exposedFilters = $filters;
        return $this;
    }

    function setBulkOperation($method, $label, $message, $btn_color = null) {
        $this->bulkOperations[$method] = [
            'label' => $label,
            'message' => $message,
            'btn_color' => $btn_color,
        ];

        return $this;
    }

    function setBulkDelete() {
        return $this->setBulkOperation('delete', 'Delete', 'The ' . $this->entityTypeInfo['label_plural'] . ' have been deleted.', 'red');
    }

    function render($context, $title = null, $icon = null) {
        if (!$title) {
            $title = isset($this->entityTypeInfo['label_plural']) ? $this->entityTypeInfo['label_plural'] : 'Entities';
        }

        if (!$icon) {
            $icon = isset($this->entityTypeInfo['icon']) ? $this->entityTypeInfo['icon'] : 'application_view_columns';
        }

        $this->context = $context;
        parent::render($context, $title, $icon);
    }

    protected function renderPageBody() {
        $this->processBulkOperations();

        StatusMessageQueue::clear();

        $this->renderAddButton();
        $this->renderExposedFilters();
        $this->renderTable();
        $this->renderPager();
        $this->renderBulkOperations();
    }

    protected function renderAddButton() {
        if (!in_array('create', $this->operations)) {
            return;
        }

        $args = [
            'entity_type' => $this->entityTypeKey,
            'context' => $this->context,
            '__return_url' => $_SERVER['REQUEST_URI'],
        ];

        if (defined('PROJECT_ID')) {
            $args['pid'] = PROJECT_ID;
        }

        $title = RCView::i(['class' => 'fa fa-plus-circle']) . ' ';
        $title .= isset($this->entityTypeInfo['label']) ? $this->entityTypeInfo['label'] : 'Entity';

        echo RCView::button([
            'class' => 'btn btn-success btn-sm redcap-entity-add-btn',
            'onclick' => 'location.href = "' . $this->formUrl . '&' . htmlentities(http_build_query($args)) . '";',
        ], $title);
    }

    protected function getFields() {
        return ['id' => ['name' => '#', 'type' => 'integer']] + $this->entityTypeInfo['properties'] + [
            'created' => ['name' => 'Created', 'type' => 'date'],
            'updated' => ['name' => 'Updated', 'type' => 'date'],
        ];
    }

    protected function getExposedFilters() {
        $filters = [];
        $fields = $this->getFields();

        if (!isset($this->exposedFilters)) {
            $this->exposedFilters = $this->cols;
        }

        $project_key = '';
        if (isset($this->entityTypeInfo['special_keys']['project']) && defined('PROJECT_ID')) {
            $project_key = $this->entityTypeInfo['special_keys']['project'];
        }

        foreach ($this->exposedFilters as $key) {
            if (isset($fields[$key]) && !in_array($fields[$key]['type'], ['json', 'date', 'data']) && $key != $project_key) {
                $filters[$key] = $fields[$key];
            }
        }

        return $filters;
    }

    protected function renderExposedFilters() {
        if (!$filters = $this->getExposedFilters()) {
            return;
        }

        $output = '';

        foreach (['prefix', 'page', 'pid'] as $key) {
            if (isset($_GET[$key])) {
               $output .= RCView::hidden(['name' => $key, 'value' => REDCap::escapeHtml($_GET[$key])]);
            }
        }

        if (PAGE == 'api/index.php' && isset($_GET['prefix']) && isset($_GET['page'])) {
            $output .= RCView::hidden(['name' => 'type', 'value' => 'module']);
        }

        foreach ($filters as $key => $info) {
            $label = REDCap::escapeHtml($info['name']);
            $value = isset($_GET['_' . $key]) ? trim($_GET['_' . $key]) : '';

            $attrs = [
                'name' => '_' . $key,
                'id' => 'redcap-entity-filter-' . $key,
                'class' => 'form-control form-control-sm',
            ];

            $choices = false;

            if ($info['type'] == 'boolean') {
                $choices = ['0' => 'No', '1' => 'Yes'];
            }
            elseif (!empty($info['choices'])) {
                $choices = $info['choices'];
            }
            elseif (!empty($info['choices_callback'])) {
                $entity = $this->entityFactory->getInstance($this->entityTypeKey);
                $choices = $info['choices_callback']();
            }
            elseif ($info['type'] == 'record') {
                $choices = Records::getRecordsAsArray(PROJECT_ID);
                $attrs['class'] .= ' redcap-entity-select';
            }
            elseif ($info['type'] == 'project') {
                $choices = [];
                $attrs['class'] .= ' redcap-entity-select-project';

                if (!empty($value) && ($title = ToDoList::getProjectTitle($value))) {
                    $choices[$value] = '(' . $value . ') ' . REDCap::escapeHtml($title);
                }
            }
            elseif ($info['type'] == 'user') {
                $choices = [];
                $attrs['class'] .= ' redcap-entity-select-user';

                if (!empty($value) && ($user_info = User::getUserInfo($value))) {
                    $choices[$value] = $value . ' (' . REDCap::escapeHtml($user_info['user_firstname'] . ' ' . $user_info['user_lastname'] . ') - ' . $user_info['user_email']);
                }
            }
            elseif ($info['type'] == 'entity_reference' && !empty($info['entity_type'])) {
                $choices = [];

                $attrs['class'] .= ' redcap-entity-select-entity-reference';
                $attrs['data-entity_type'] = $info['entity_type'];

                if (!empty($value) && ($entity = $this->entityFactory->getInstance($info['entity_type'], $value))) {
                    $choices[$value] = REDCap::escapeHtml($entity->getLabel());
                }
            }

            if ($choices === false) {
                $element = RCView::text($attrs + ['value' => $value, 'placeholder' => $label]);
            }
            else {
                $element = RCView::select($attrs, ['' => '-- ' . $label . ' --'] + $choices, $value);
            }

            $element = RCView::label(['for' => $attrs['id'], 'class' => 'sr-only'], $label) . $element;
            $output .= RCView::div(['class' => 'form-group'], $element);
        }

        $output .= RCView::button(['type' => 'submit', 'class' => 'btn btn-sm btn-primary'], 'Submit');
        echo RCView::form(['id' => 'redcap-entity-exp-filters-form', 'class' => 'form-inline'], $output);
    }

    protected function renderTable() {
        $this->buildTableHeader();
        $this->buildTableRows();

        if (empty($this->rows)) {
            echo RCView::div([], $this->getEmptyResultsMessage());
            return;
        }

        $first_item = ($this->currPage - 1) * $this->pageSize + 1;
        $last_item = min($this->totalRows, $this->currPage * $this->pageSize);
        $label = $this->totalRows == 1 ? 'result' : 'results';

        echo RCView::p([], 'Showing ' . $first_item . ' - ' . $last_item . ' of ' . $this->totalRows . ' ' . $label);

        $this->loadTemplate('list', [
            'class' => 'redcap-entity-list',
            'id' => 'redcap_entity_list-' . $this->entityTypeKey,
            'header' => $this->header,
            'rows' => $this->rows,
            'rows_attributes' => $this->rowsAttributes,
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

    protected function renderBulkOperations() {
        if (!$this->totalRows || !$this->bulkOperations) {
            return;
        }

        $btns = '';
        foreach ($this->bulkOperations as $key => $op) {
            $btn_class = 'primary';

            if (!empty($op['btn_color'])) {
                $btn_classes = [
                    'blue' => 'primary',
                    'green' => 'success',
                    'yellow' => 'warning',
                    'red' => 'danger',
                    'black' => 'dark',
                    'gray' => 'secondary',
                    'white' => 'light',
                    'light_blue' => 'info',
                ];

                if (isset($btn_classes[$op['btn_color']])) {
                    $btn_class = $btn_classes[$op['btn_color']];
                }
            }

            $name = REDCap::escapeHtml($key);
            $btns .= RCView::button([
                'name' => $name,
                'data-toggle' => 'modal',
                'data-target' => '#redcap-entity-bulk-operation-modal-' . $name,
                'class' => 'btn btn-' . $btn_class . ' bulk-operation',
                'disabled' => true,
            ], REDCap::escapeHtml($op['label']));

            $this->loadTemplate('modal', [
                'id' => 'redcap-entity-bulk-operation-modal-' . $name,
                'confirm_btn' => [
                    'title' => $op['label'],
                    'attrs' => [
                        'form' => 'redcap-entity-bulk-form',
                        'class' => 'btn btn-' . $btn_class,
                    ],
                ],
            ]);

            $form = RCView::hidden(['name' => '__operation']);
            echo RCView::form(['id' => 'redcap-entity-bulk-form', 'method' => 'post'], $form);
        }

        echo RCView::div(['class' => 'redcap-entity-bulk-btns'], $btns);
    }

    protected function buildTableRows() {
        $query = $this->getQuery();
        $fields = $this->getFields();

        if ($this->pageSize) {
            $count_query = clone $query;
            $this->totalRows = $count_query->countQuery()->execute();

            $query->limit($this->pageSize, ($this->currPage - 1) * $this->pageSize);
        }

        if (!empty($_GET['__order_by']) && in_array($_GET['__order_by'], $this->getSortableCols())) {
            $key = $_GET['__order_by'];
            $sorting_field = empty($fields[$key]['sql_field']) ? 'e.' . $key : 'alias__' . $key;
            $query->orderBy($sorting_field, !empty($_GET['__desc']));
        }
        else {
            $query->orderBy('e.updated', true)->orderBy('e.id', true);
        }

        if (!$entities = $query->execute()) {
            return;
        }

        $custom_fields = [];

        foreach ($fields as $key => $info) {
            if (!empty($info['sql_field'])) {
                $custom_fields[] = $key;
            }
        }

        $raw_results = $query->getRawResults();

        foreach ($entities as $id => $entity) {
            $data = [
                'id' => $id,
                'created' => $entity->getCreationTimestamp(),
                'updated' => $entity->getLastUpdateTimestamp(),
            ] + $entity->getData();

            foreach ($custom_fields as $key) {
                $data[$key] = $raw_results[$id]['alias__' . $key];
            }

            $this->rows[$id] = $this->buildTableRow($data, $entity);
        }
    }

    protected function buildTableRow($data, $entity) {
        $row = [];

        $fields = $this->getFields();
        $data = array_map('REDCap::escapeHtml', $data);

        foreach (array_keys($this->header) as $key) {
            if ($key == '__bulk_op') {
                $row['__bulk_op'] = RCView::checkbox(['name' => 'entities[]', 'value' => $data['id'], 'form' => 'redcap-entity-bulk-form']);
                continue;
            }

            if (in_array($key, ['__update', '__delete'])) {
                $args = [
                    'entity_id' => $data['id'],
                    'entity_type' => $this->entityTypeKey,
                    'context' => $this->context,
                    '__return_url' => $_SERVER['REQUEST_URI'],
                ];

                if (defined('PROJECT_ID')) {
                    $args['pid'] = PROJECT_ID;
                }

                $path = $this->formUrl;

                if ($key == '__update') {
                    $title = 'edit';
                }
                else {
                    $args['__delete'] = true;
                    $title = 'delete';
                }

                $row[$key] = RCView::a(['href' => $path . '&' . htmlentities(http_build_query($args))], $title);
                continue;
            }

            if (in_array($key, ['created', 'updated'])) {
                $row[$key] = date('m/d/Y - h:ia', $data[$key]);
                continue;
            }

            if (!isset($data[$key]) || trim($data[$key]) === '') {
                $row[$key] = '-';
                continue;
            }

            $info = $fields[$key];

            if (!empty($info['choices']) && isset($info['choices'][$data[$key]])) {
                $row[$key] = $info['choices'][$data[$key]];
                continue;
            }

            if (!empty($info['choices_callback']) && is_callable($info['choices_callback'])) {
                $choices = $info['choices_callback']();

                if (isset($choices[$data[$key]])) {
                    $row[$key] = $choices[$data[$key]];
                    continue;
                }
            }

            $row[$key] = $data[$key];

            switch ($info['type']) {
                case 'boolean':
                    $row[$key] = empty($row[$key]) ? 'No' : 'Yes';
                    break;

                case 'record':
                    if (defined('PROJECT_ID')) {
                        $row[$key] = RCView::a([
                            'href' => APP_PATH_WEBROOT . 'DataEntry/record_home.php?pid=' . PROJECT_ID . '&id=' . $row[$key] . '&arm=' . getArm(),
                            'target' => '_blank',
                        ], $row[$key]);
                    }

                    break;

                case 'date':
                    $row[$key] = date('m/d/Y', $data[$key]);
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
                    $row[$key] = RCView::a(['href' => APP_PATH_WEBROOT . 'index.php?pid=' . $row[$key], 'target' => '_blank'], '(' . $data[$key] . ') ' . $project->app_title);
                    break;

                case 'user':
                    if (!$user_info = User::getUserInfo($data[$key])) {
                        break;
                    }

                    $url = SUPER_USER || ACCOUNT_MANAGER ? APP_PATH_WEBROOT . 'ControlCenter/view_users.php?username=' . $data[$key] : 'mailto:' . $user_info['user_email'];
                    $row[$key] = '(' . $data[$key] . ') ' . $user_info['user_firstname'] . ' ' . $user_info['user_lastname'];
                    $row[$key] = RCView::a(['href' => $url, 'target' => '_blank'], REDCap::escapeHtml($row[$key]));
                    break;

                case 'email':
                    if ($row[$key]) {
                        $row[$key] = RCView::a(['href' => 'mailto:' . $user_info['user_email']], $row[$key]);
                    }

                    break;
            }
        }

        $this->rowsAttributes[$data['id']] = $this->getRowAttributes($data, $entity);
        return $row;
    }

    protected function getRowAttributes($data, $entity) {
        return [];
    }

    protected function getColsLabels() {
        $labels = [];
        $fields = $this->getFields();

        foreach ($this->cols as $key) {
            if (!isset($fields[$key])) {
                continue;
            }

            if (!in_array($fields[$key]['type'], ['json', 'long_text', 'data'])) {
                $labels[$key] = $fields[$key]['name'];
            }
        }

        foreach (['update', 'delete'] as $op) {
            if (in_array($op, $this->operations)) {
                $labels['__' . $op] = '';
            }
        }

        if (isset($this->entityTypeInfo['special_keys']['project']) && defined('PROJECT_ID')) {
            unset($labels[$this->entityTypeInfo['special_keys']['project']]);
        }

        return $labels;
    }

    protected function getSortableCols() {
        $fields = $this->getFields();

        if (!isset($this->sortableCols)) {
            $this->sortableCols = $this->cols;
        }

        foreach ($this->sortableCols as $key) {
            if (!isset($fields[$key])) {
                continue;
            }

            if (in_array($fields[$key]['type'], ['text', 'integer', 'user', 'project', 'date', 'email'])) {
                $sortable[] = $key;
            }
        }

        return $sortable;
    }

    protected function buildTableHeader() {
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

        $header = $this->getColsLabels();
        if (!empty($this->bulkOperations)) {
            $header = ['__bulk_op' => RCView::checkbox(['name' => 'all_entities', 'form' => 'redcap-entity-bulk-form'])] + $header;
        }

        foreach ($this->getSortableCols() as $key) {
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

        $this->header = $header;
    }

    protected function getQuery() {
        $query = $this->entityFactory->query($this->entityTypeKey);

        foreach ($this->getExposedFilters() as $key => $info) {
            if (!isset($_GET['_' . $key]) || $_GET['_' . $key] === '') {
                continue;
            }

            $op = '=';
            $field = empty($info['sql_field']) ? $key : $info['sql_field'];
            $value = trim($_GET['_' . $key]);

            if ($info['type'] = 'text' && empty($info['choices']) && empty($info['choices_callback'])) {
                $op = 'LIKE';
                $value = '%' . $value . '%';
            }

            $query->condition($field, $value, $op);
        }

        foreach ($this->getFields() as $key => $info) {
            if (!empty($info['sql_field'])) {
                $query->addExpression($info['sql_field'], 'alias__' . $key);
            }
        }

        if (isset($this->entityTypeInfo['special_keys']['project']) && defined('PROJECT_ID')) {
            $query->condition($this->entityTypeInfo['special_keys']['project'], PROJECT_ID);
        }

        return $query;
    }

    protected function getEmptyResultsMessage() {
        $label = isset($this->entityTypeInfo['label_plural']) ? $this->entityTypeInfo['label_plural'] : 'results';
        return 'There are no ' . $label . '.';
    }

    protected function processBulkOperations() {
        if (empty($this->bulkOperations)) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST['__operation']) || empty($_POST['entities'])) {
            return;
        }

        $op = $_POST['__operation'];

        if (!isset($this->bulkOperations[$op])) {
            return;
        }

        if (!$entities = $this->entityFactory->loadInstances($this->entityTypeKey, $_POST['entities'])) {
            return;
        }

        $this->executeBulkOperation($op, $this->bulkOperations[$op], $entities);
    }

    protected function executeBulkOperation($op, $op_info, $entities) {
        foreach ($entities as $entity) {
            // TODO: check if method exists.
            $entity->$op();
        }

        // TODO: detect errors.

        if (!empty($op_info['message'])) {
            StatusMessageQueue::enqueue($op_info['message']);
        }
    }

    protected function loadPageScripts() {
        $this->jsFiles[] = APP_URL_EXTMOD . 'manager/js/select2.js';
        $this->jsFiles[] = ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/js/entity_list.js');
        $this->jsFiles[] = ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/js/entity_fields.js');

        $this->jsSettings['redcapEntity'] = [
            'entityReferenceUrl' => ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/ajax/entity_reference.php'),
            'projectReferenceUrl' => ExternalModules::$BASE_URL . 'manager/ajax/get-project-list.php',
        ];

        parent::loadPageScripts();
    }

    protected function loadPageStyles() {
        $this->cssFiles[] = APP_URL_EXTMOD . 'manager/css/select2.css';
        $this->cssFiles[] = ExternalModules::getUrl(REDCAP_ENTITY_PREFIX, 'manager/css/entity_list.css');

        parent::loadPageStyles();
    }

    // TODO: move it to a Trait class.
    protected function loadTemplate($template, $vars = []) {
        extract($vars);
        include dirname(__DIR__) . '/templates/' . $template . '.php';
    }
}
