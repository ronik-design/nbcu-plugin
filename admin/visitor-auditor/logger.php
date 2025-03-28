<?php
function nbcu_log_block_event($ip, $type = 'manual', $extra = '') {
    $logs = get_option('nbcu_block_audit_log', []);
    $logs[] = [
        'ip' => $ip,
        'time' => current_time('mysql'),
        'type' => $type,
        'note' => $extra,
    ];
    update_option('nbcu_block_audit_log', array_slice($logs, -100));
}
