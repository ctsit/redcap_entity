<?php

namespace REDCapEntity;

use RCView;

class StatusMessageQueue {
    static function enqueue($msg, $type = 'success') {
        if (!isset($_SESSION['redcap_entity_message_queue'])) {
            $_SESSION['redcap_entity_message_queue'] = ['error' => [], 'warning' => [], 'success' =>[]];
        }

        if (!isset($_SESSION['redcap_entity_message_queue'][$type])) {
            return;
        }

        $_SESSION['redcap_entity_message_queue'][$type][] = $msg;
    }

    static function clear() {
        if (!isset($_SESSION['redcap_entity_message_queue'])) {
            return;
        }

        $styles = [
            'error' => ['icon' => 'exclamation', 'color' => 'red'],
            'success' => ['icon' => 'tick', 'color' => 'green'],
        ];

        foreach ($_SESSION['redcap_entity_message_queue'] as $type => $msgs) {
            if (empty($styles[$type])) {
                continue;
            }

            $style = $styles[$type];
            foreach ($msgs as $i => $msg) {
                displayMsg($msg, 'redcap-entity-' . $type . '-' . $i, 'center', $style['color'], $style['icon'] . '.png', 0, false);
            }
        }

        unset($_SESSION['redcap_entity_message_queue']);
    }
}
