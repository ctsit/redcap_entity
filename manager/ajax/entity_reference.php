<?php

header('Content-type: application/json');

global $isAjax;

if (!$isAjax || empty($_GET['entity_type'])) {
    exit;
}

$factory = new \REDCapEntity\EntityFactory();
if (!$info = $factory->getEntityTypeInfo($_GET['entity_type'])) {
    exit;
}

$query = $factory->query($_GET['entity_type']);

if (!empty($_GET['term'])) {
    $term = '%' . $_GET['term'] . '%';
    $query = $query->condition('id', $term, 'LIKE');

    if (!empty($info['special_keys']['label'])) {
        $key = $info['special_keys']['label'];
        $query->condition($key, $term, 'LIKE')->orderBy($key);
    }
}

if (!$entities = $query->execute(true, false)) {
    exit;
}

$results = [];
foreach ($entities as $id => $entity) {
    $results[] = ['id' => $id, 'text' => $entity->getLabel()];
}

echo json_encode(['results' => $results, 'more' => false]);
