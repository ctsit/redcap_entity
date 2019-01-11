<?php

use REDCapEntity\EntityList;

$list = new EntityList('protocol', $module);
$list->setOperations(['create', 'delete', 'update'])
    ->setBulkDelete()
    ->setCols(['number', 'title', 'status', 'pi'])
    ->setExposedFilters([])
    ->render('project');
