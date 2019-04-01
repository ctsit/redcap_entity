<?php

use REDCapEntity\EntityList;

$list = new EntityList('protocol', $module);
$list->setOperations(['create', 'update', 'delete'])
    ->render('project'); // Context: project.
