<?php
add_action('init', 'nbcu_register_block_check');

function nbcu_register_block_check() {
    if (is_admin()) return;

    $ip = $_SERVER['REMOTE_ADDR'];
    $blocked_ips = get_option('nbcu_blocked_ips', []);

    if (isset($blocked_ips[$ip])) {
        $block = $blocked_ips[$ip];
        if ($block['permanent']) {
            wp_die('Your IP is permanently blocked from registering.');
        }
        if (time() < $block['expires']) {
            $remaining = human_time_diff(time(), $block['expires']);
            wp_die("Too many registration attempts. Try again in $remaining.");
        }

        unset($blocked_ips[$ip]);
        update_option('nbcu_blocked_ips', $blocked_ips);
    }

    $key = 'nbcu_register_count_' . md5($ip);
    $attempts = (int) get_transient($key);
    $attempts++;

    if ($attempts >= 2) {
        $blocked_ips[$ip] = [
            'permanent' => false,
            'expires' => time() + HOUR_IN_SECONDS,
        ];
        nbcu_log_block_event($ip, 'auto', 'Rate limit exceeded');
        update_option('nbcu_blocked_ips', $blocked_ips);
    } else {
        set_transient($key, $attempts, HOUR_IN_SECONDS);
    }
}
