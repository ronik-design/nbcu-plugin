<?php

/**
 * Check if an IP is currently blocked.
 */
function nbcu_is_ip_blocked($ip = null)
{
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
    $blocked_ips = get_option('nbcu_blocked_ips', []);
    if (!isset($blocked_ips[$ip])) return false;

    $block = $blocked_ips[$ip];
    if (!$block['permanent'] && time() >= $block['expires']) {
        unset($blocked_ips[$ip]);
        update_option('nbcu_blocked_ips', $blocked_ips);
    
        // ✅ Remove associated submission data on expiration
        nbcu_reset_submission_data($ip);
    
        return false;
    }

    return [
        'blocked' => true,
        'permanent' => !empty($block['permanent']),
        'expires' => $block['expires'] ?? 0,
        'severity' => $block['severity'] ?? 'form',
    ];
}


/**
 * Programmatically block an IP with options.
 */
function nbcu_block_ip_custom($ip, $severity = 'form', $duration_seconds = 3600, $permanent = false)
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    $blocked_ips = get_option('nbcu_blocked_ips', []);
    $blocked_ips[$ip] = [
        'permanent' => $permanent,
        'expires' => $permanent ? 0 : time() + $duration_seconds,
        'severity' => $severity,
    ];
    update_option('nbcu_blocked_ips', $blocked_ips);

    $note = $permanent ? 'Permanent' : "Temporary ({$duration_seconds}s)";
    nbcu_log_block_event($ip, 'custom', "$note | Severity: $severity");

    return true;
}


/**
 * Syncs form submission data with a block event (permanent or temporary).
 *
 * @param string  $ip         IP address to sync.
 * @param bool    $permanent  Whether this is a permanent block.
 * @param int     $duration   Duration in seconds for temporary blocks.
 */
function nbcu_sync_submission_block_data($ip, $permanent = false, $duration = 3600)
{
    $data = [
        'count' => 99,
        'start' => time(),
        'expires' => $permanent ? PHP_INT_MAX : time() + $duration,
        'permanent' => $permanent
    ];

    update_option(nbcu_get_submission_key($ip), $data);
}
