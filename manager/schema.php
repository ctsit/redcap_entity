<?php

require_once dirname(__DIR__) . '/classes/SchemaManagerPage.php';

use REDCapEntity\SchemaManagerPage;

$page = new SchemaManagerPage();
$page->render('control_center', 'Entity DB Manager', 'database_table');
