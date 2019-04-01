<?php

use REDCapEntity\EntityList;

$list = new EntityList('study_site', $module);
$list->setOperations(['create', 'update', 'delete']) // Enabling all operations.
    ->render('control_center'); // Context: Control Center.
