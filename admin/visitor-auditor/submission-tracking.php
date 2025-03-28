<?php

/**
 * Get a unique key for tracking form submissions by IP.
 */
function nbcu_get_submission_key($ip = null) {
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
    return 'nbcu_form_submit_count_' . md5($ip);
}

/**
 * Track form submission count and apply temporary block if exceeded.
 */
function nbcu_track_form_submission() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $max_submissions = 3;
    $time_window = 600; // 10 minutes
    $block_duration = 86400; // 1 day

    $key = nbcu_get_submission_key($ip);
    $data = get_option($key, null);

    if (!$data || time() > ($data['start'] + $time_window)) {
        $data = [
            'count' => 1,
            'start' => time(),
            'expires' => time() + $time_window
        ];
    } else {
        $data['count']++;
    }

    update_option($key, $data);

    if ($data['count'] > $max_submissions) {
        nbcu_block_ip_custom($ip, 'form', $block_duration, false);
        nbcu_log_block_event($ip, 'auto', 'Form spam detected (submitted more than 3 times in 10 minutes)');
        return false;
    }

    return true;
}

/**
 * Return the current form submission status for an IP.
 */
function nbcu_get_submission_status($ip = null) {
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
    $key = nbcu_get_submission_key($ip);
    $data = get_option($key);

    if (!$data || !is_array($data)) {
        return [
            'ip' => $ip,
            'count' => 0,
            'start' => 'N/A',
            'expires_in' => 'N/A',
            'permanent' => false
        ];
    }

    $now = time();
    $expires = $data['expires'] ?? 0;
    $permanent = $data['permanent'] ?? false;

    // ⏱ Expired? Reset and remove
    if (!$permanent && $expires > 0 && $now > $expires) {
        delete_option($key);
        return [
            'ip' => $ip,
            'count' => 0,
            'start' => 'N/A',
            'expires_in' => 'N/A',
            'permanent' => false
        ];
    }

    return [
        'ip' => $ip,
        'count' => $data['count'] ?? 0,
        'start' => date('Y-m-d H:i:s', $data['start'] ?? $now),
        'expires_in' => $permanent ? 'Never' : human_time_diff($now, $expires) . ' remaining',
        'permanent' => $permanent
    ];
}

/**
 * Delete form submission data for an IP (used on unblock or expiration).
 */
function nbcu_reset_submission_data($ip)
{
    delete_option(nbcu_get_submission_key($ip));
}
