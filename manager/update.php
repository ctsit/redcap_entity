<?php

use ExternalModules\ExternalModules;
use REDCapEntity\EntityFactory;

if (!isset($_GET['entity_type'])) {
    // TODO: display error.
    exit;
}

$entity_type = $_GET['entity_type'];
$factory = new EntityFactory();

if (!$info = $factory->getEntityTypeInfo($entity_type)) {
    // TODO: display error.
    exit;
}

$entity_id = null;
if (isset($_GET['entity_id'])) {
    if (empty($info['operations']['update'])) {
        // TODO: display error.
        exit;
    }

    $entity_id = $_GET['entity_id'];
}
elseif (empty($info['operations']['create'])) {
    // TODO: display error.
    exit;
}

try {
    $entity = $factory->getInstance($entity_type, $entity_id);
}
catch (Exception $e) {
    // TODO: display error.
    exit;
}

$class = 'REDCapEntity\EntityForm';
if (!empty($info['form_class']['name'])) {
    $class = $info['form_class']['name'];

    if (!empty($info['form_class']['path'])) {
        $base_path = ExternalModules::getModuleDirectoryPath($info['module']); 
        include_once $base_path . '/' . $info['form_class']['path'];
    }

    if (!is_subclass_of($class, 'REDCapEntity\EntityForm')) {
        // TODO: display error.
        exit;
    }
}

$context = isset($_GET['context']) ? $_GET['context'] : 'control_center';

$form = new $class($entity);
$form->render($context);
