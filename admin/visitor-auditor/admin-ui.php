<?php

/**
 * Handle CSV export on admin_init to avoid headers-already-sent issues.
 */
add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;

    if (!empty($_POST['nbcu_export_log_csv']) && check_admin_referer('nbcu_audit_log_actions')) {
        error_log('NBCU IP Blocker: Export triggered');

        $logs = get_option('nbcu_block_audit_log', []);
        error_log('NBCU IP Blocker: Log count: ' . count($logs));
        error_log('NBCU IP Blocker: Logs: ' . print_r($logs, true));

        if (empty($logs)) {
            error_log('NBCU IP Blocker: No logs found for export');
            // Don’t exit here; let the admin page show an error
            return;
        }

        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="nbcu-block-audit-log-' . date('Y-m-d-His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        // Open output stream
        $output = fopen('php://output', 'w');
        if ($output === false) {
            error_log('NBCU IP Blocker: Failed to open php://output');
            exit('Error: Could not generate CSV');
        }

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write headers
        fputcsv($output, ['Time', 'IP', 'Type', 'Note']);

        // Write data
        foreach ($logs as $log) {
            $row = [
                $log['time'] ?? 'N/A',
                $log['ip'] ?? 'N/A',
                $log['type'] ?? 'N/A',
                $log['note'] ?? 'N/A'
            ];
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
});

/**
 * Admin menu page to manage blocked IPs.
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Blocked IPs',
        'IP Blocker',
        'manage_options',
        'nbcu-ip-blocker',
        'nbcu_ip_blocker_admin_page',
        'dashicons-shield',
        80
    );
});

/**
 * Admin page logic for viewing, blocking, and unblocking IPs.
 */
function nbcu_ip_blocker_admin_page()
{
    if (!current_user_can('manage_options')) return;

    $blocked_ips = get_option('nbcu_blocked_ips', []);

    // Unblock logic
    if (!empty($_GET['unblock'])) {
        $unblock_ip = $_GET['unblock'];
        unset($blocked_ips[$unblock_ip]);
        update_option('nbcu_blocked_ips', $blocked_ips);
        nbcu_reset_submission_data($unblock_ip);
        echo '<div class="updated"><p>IP unblocked and all counters reset.</p></div>';
    }

    // Permanent toggle
    if (!empty($_GET['perma'])) {
        $ip = $_GET['perma'];
        $blocked_ips[$ip]['permanent'] = true;
        $blocked_ips[$ip]['expires'] = 0;
        update_option('nbcu_blocked_ips', $blocked_ips);
        nbcu_sync_submission_block_data($ip, true);
        echo '<div class="updated"><p>IP permanently blocked.</p></div>';
    }

    // Temp block toggle
    if (!empty($_GET['tempblock'])) {
        $ip = $_GET['tempblock'];
        $duration = HOUR_IN_SECONDS;
        $blocked_ips[$ip] = [
            'permanent' => false,
            'expires' => time() + $duration,
            'severity' => 'form'
        ];
        update_option('nbcu_blocked_ips', $blocked_ips);
        nbcu_sync_submission_block_data($ip, false, $duration);
        echo '<div class="updated"><p>IP temporarily blocked for 1 hour.</p></div>';
    }

    // Manual block form handling
    if (!empty($_POST['nbcu_block_ip']) && check_admin_referer('nbcu_add_ip')) {
        $new_ip = trim($_POST['nbcu_block_ip']);
        $permanent = !empty($_POST['nbcu_permanent_block']);

        if (filter_var($new_ip, FILTER_VALIDATE_IP)) {
            $duration = (int) ($_POST['nbcu_block_duration'] ?? 3600);
            $severity = in_array($_POST['nbcu_block_severity'], ['site', 'form']) ? $_POST['nbcu_block_severity'] : 'form';

            $blocked_ips[$new_ip] = [
                'permanent' => $permanent,
                'expires' => $permanent ? 0 : time() + $duration,
                'severity' => $severity,
            ];
            nbcu_sync_submission_block_data($new_ip, $permanent, $duration);
            nbcu_log_block_event($new_ip, 'manual', $permanent ? 'Permanent' : "Temporary {$duration}s");
            update_option('nbcu_blocked_ips', $blocked_ips);
            echo '<div class="updated"><p>IP ' . esc_html($new_ip) . ' has been blocked.</p></div>';
        } else {
            echo '<div class="error"><p>Invalid IP address.</p></div>';
        }
    }

    // Clear audit log
    if (!empty($_POST['nbcu_clear_log']) && check_admin_referer('nbcu_audit_log_actions')) {
        update_option('nbcu_block_audit_log', []);
        echo '<div class="updated"><p>Audit log cleared.</p></div>';
    }

    // Show error if export attempted but no logs
    if (!empty($_POST['nbcu_export_log_csv']) && check_admin_referer('nbcu_audit_log_actions')) {
        $logs = get_option('nbcu_block_audit_log', []);
        if (empty($logs)) {
            echo '<div class="error"><p>No log data available to export.</p></div>';
        }
    }

    // Output begins
    echo '<div class="wrap"><h1>Blocked IPs</h1>';

    // Add Manual Block Form
    echo '<form method="post">';
    wp_nonce_field('nbcu_add_ip');
    echo '<p><strong>Add New IP to Block:</strong></p>';
    echo '<input type="text" name="nbcu_block_ip" placeholder="Enter IP" required>';
    echo ' <label><input type="checkbox" name="nbcu_permanent_block"> Permanent?</label><br><br>';
    echo '<label><strong>Block Duration:</strong></label> ';
    echo '<select name="nbcu_block_duration">
            <option value="900">15 minutes</option>
            <option value="3600" selected>1 hour</option>
            <option value="86400">1 day</option>
            <option value="604800">1 week</option>
          </select><br><br>';
    echo '<label><strong>Block Severity:</strong></label> ';
    echo '<select name="nbcu_block_severity">
            <option value="form">Form Submission Only</option>
            <option value="site">Entire Site</option>
          </select><br><br>';
    echo '<input type="submit" class="button button-primary" value="Block IP">';
    echo '</form><hr>';

    // Existing blocked IPs table
    if (empty($blocked_ips)) {
        echo '<p>No IPs currently blocked.</p>';
    } else {
        echo '<table class="widefat"><thead><tr>
            <th>IP</th>
            <th>Status</th>
            <th>Expires In</th>
            <th><strong>Severity</strong></th>
            <th>Actions</th>
        </tr></thead><tbody>';
        foreach ($blocked_ips as $ip => $block) {
            $status = $block['permanent'] ? '🔒 Permanent' : '⏳ Temporary';
            $expires = $block['permanent']
                ? 'Never (Permanent)'
                : human_time_diff(time(), $block['expires']) . ' remaining';
            $severity_raw = $block['severity'] ?? 'form';
            $severity = ucfirst($severity_raw);
            $severity_class = $severity_raw === 'site' ? 'severity-site' : 'severity-form';
            echo "<tr>
                <td>$ip</td>
                <td>$status</td>
                <td>$expires</td>
                <td><span class='$severity_class'>$severity</span></td>
                <td>
                    <a href='?page=nbcu-ip-blocker&unblock=$ip' class='button'>Unblock</a>
                    <a href='?page=nbcu-ip-blocker&perma=$ip' class='button button-danger'>Permanently Block</a>
                    <a href='?page=nbcu-ip-blocker&tempblock=$ip' class='button'>Temporary Block</a>
                </td>
            </tr>";
        }
        echo '</tbody></table>';
    }

    // Clear/export log form
    echo '<form method="post" style="margin-top:20px; margin-bottom: 10px;">';
    wp_nonce_field('nbcu_audit_log_actions');
    echo '<input type="submit" name="nbcu_clear_log" class="button" value="🧹 Clear Log">';
    echo ' ';
    echo '<input type="submit" name="nbcu_export_log_csv" class="button" value="📄 Export as CSV">';
    echo '</form>';

    // Audit Log Table
    echo '<h2>Audit Log</h2>';
    $logs = get_option('nbcu_block_audit_log', []);
    if (empty($logs)) {
        echo '<p>No recent log entries.</p>';
    } else {
        echo '<table class="widefat"><thead><tr><th>Time</th><th>IP</th><th>Type</th><th>Note</th></tr></thead><tbody>';
        foreach (array_reverse($logs) as $log) {
            echo "<tr>
                <td>{$log['time']}</td>
                <td>{$log['ip']}</td>
                <td>{$log['type']}</td>
                <td>{$log['note']}</td>
            </tr>";
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

/**
 * Styles for admin UI.
 */
add_action('admin_head', function () {
    echo "<style>
        .button-danger { color: white; background: #d63638; border-color: #a00; }
        .severity-site {
            color: white; background: #d63638; padding: 3px 8px; border-radius: 4px;
            font-weight: 600; font-size: 0.9em;
        }
        .severity-form {
            color: black; background: orange; padding: 3px 8px; border-radius: 4px;
            font-weight: 600; font-size: 0.9em;
        }
    </style>";
});