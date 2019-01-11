<?php

namespace REDCapEntity;

trait EntityFormTrait {

    protected function checkPermissions($context) {
        switch ($context) {
            case 'project':
                global $user_rights;

                if (empty($user_rights['design'])) {
                    return false;
                }

                if ($this->type == 'create' || empty($this->entityTypeInfo['special_keys']['project'])) {
                    return true;
                }

                $data = $this->entity->getData();
                return $data[$this->entityTypeInfo['special_keys']['project']] == PROJECT_ID;

            case 'global':
                return SUPER_USER || ACCOUNT_MANAGER;
        }

        return true;
    }
}
