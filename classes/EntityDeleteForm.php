<?php

namespace REDCapEntity;

use ExternalModules\ExternalModules;
use RCView;
use REDCapEntity\Entity;
use REDCapEntity\StatusMessageQueue;

class EntityDeleteForm extends Page {
    use EntityFormTrait;

    protected $entity;
    protected $entityTypeInfo;

    function __construct(Entity $entity) {
        $this->entity = $entity;
        $this->entityTypeInfo = $entity->getEntityTypeInfo();
    }

    protected function renderPageBody() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $label = empty($this->entityTypeInfo['label']) ? 'entity' : strtolower($this->entityTypeInfo['label']);

            if ($this->entity->delete()) {
                StatusMessageQueue::enqueue('The ' . $label . ' has been deleted successfully.');

                $url = isset($_GET['__return_url']) ? $_GET['__return_url'] : APP_PATH_WEBROOT_PARENT;
                redirect($url);
            }

            StatusMessageQueue::enqueue('There was an error while deleting the ' . $label . '.', 'error');
        }

        $output = RCView::button(['type' => 'submit', 'class' => 'btn btn-danger'], 'Delete');
        if (isset($_GET['__return_url'])) {
            $output = RCView::a(['class' => 'btn btn-default', 'href' => htmlspecialchars($_GET['__return_url'])], 'Return to list') . $output;
        }

        $output = RCView::div([], 'Are you sure do you want to proceed? This action cannot be undone.') .
                  RCView::div(['class' => 'text-right actions'], $output);

        echo RCView::form(['id' => 'entity-delete-form', 'method' => 'post'], $output);
    }

    function render($context, $title = null, $icon = 'blog_minus') {
        if (!$title) {
            $title = empty($this->entityTypeInfo['label']) ? 'entity' : $this->entityTypeInfo['label'];
            $title = 'Delete ' . $title . ' - ' . $this->entity->getLabel();
        }

        parent::render($context, $title, $icon);
    }
}
