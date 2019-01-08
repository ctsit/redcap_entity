<?php

use REDCapEntity\EntityList;

$list = new EntityList('department', $module);
$list->setOperations(['create', 'delete', 'update']);
$list->render('control_center');
