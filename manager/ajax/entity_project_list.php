<?php


    $results = [];
    if ( $module ) {

        $results = $module->getProjectList();
    }
    echo json_encode(['results' => $results, 'more' => false]);
