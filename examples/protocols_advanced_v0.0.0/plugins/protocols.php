<?php

use REDCapEntity\EntityList;

$list = new EntityList('protocol', $module);
$list->setOperations(['create', 'update'])
    ->setCols(['number', 'title', 'status']) // Set fields to display.
    ->setSortableCols(['number', 'title']) // Set columns to be sortable.
    ->setExposedFilters(['created_by', 'study_site']) // Set exposed filters.
    ->setBulkDelete() // Enable delete bulk operation.
    ->setBulkOperation('approve', 'Approve Protocols', 'The protocols have been approved', 'green') // Set custom bulk operation.
    ->setPager(5, 5) // Set page and pager sizes.
    ->render('project'); // Context: project.
