<?php

namespace REDCapProtocols;

use REDCapEntity\Entity;

class Protocol extends Entity {
    function approve() {
        $this->setData(['status' => 'in_study']);
        $this->save();
    }
}
