<?php

use ExternalModules\ExternalModules;
use REDCapEntity\EntityFactory;

if (!isset($_GET['entity_type'])) {
    redirect(APP_PATH_WEBROOT_PARENT);
}

$entity_type = $_GET['entity_type'];
$factory = new EntityFactory();

if (!$info = $factory->getEntityTypeInfo($entity_type)) {
    redirect(APP_PATH_WEBROOT_PARENT);
}

$entity_id = isset($_GET['entity_id']) ? $_GET['entity_id'] : null;

if (!empty($_GET['__delete'])) {
    if (!$entity_id) {
        redirect(APP_PATH_WEBROOT_PARENT);
    }

    $class = 'REDCapEntity\EntityDeleteForm';
}

try {
    $entity = $factory->getInstance($entity_type, $entity_id);
}
catch (Exception $e) {
    redirect(APP_PATH_WEBROOT_PARENT);
}

if (!isset($class)) {
    $class = 'REDCapEntity\EntityForm';
    if (!empty($info['form_class']['name'])) {
        $class = $info['form_class']['name'];

        if (!empty($info['form_class']['path'])) {
            $base_path = ExternalModules::getModuleDirectoryPath($info['module']); 
            include_once $base_path . '/' . $info['form_class']['path'];
        }

        if (!is_subclass_of($class, 'REDCapEntity\EntityForm')) {
            redirect(APP_PATH_WEBROOT_PARENT);
        }
    }
}

$context = isset($_GET['context']) ? $_GET['context'] : 'control_center';

$form = new $class($entity);
$form->render($context);
