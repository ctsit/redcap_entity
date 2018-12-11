<?php

require_once dirname(__DIR__) . '/classes/SchemaManagerPage.php';

$page = new SchemaManagerPage();
$page->render('control_center', 'Entity Schema Manager', 'database_table');
