<?php

// Security check
if (!defined('ABSPATH')) {
    exit('Direct access denied');
}

class UserSyncHandler {
    private $per_page = 100;
    private $leet_map = ['@'=>'a','4'=>'a','3'=>'e','1'=>'i','!'=>'i','0'=>'o','$'=>'s','5'=>'s','7'=>'t'];
    
    public function __construct() {
        error_log("UserSyncHandler constructed.");
        // Enqueue styles to fix table overflow
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        // Add AJAX handlers
        add_action('wp_ajax_process_user_batch', [$this, 'ajax_process_user_batch']);
    }
    
    public function enqueue_styles($hook) {
        // Only enqueue styles on the User Sync page and if not exporting
        if ($hook !== 'toplevel_page_ronikdesigns-sync-user' || (isset($_POST['export']) && $_POST['export'] === 'true')) {
            return;
        }
        // Add inline CSS to handle table overflow
        wp_add_inline_style('wp-admin', '
            .widefat th, .widefat td {
                word-wrap: break-word;
                overflow-wrap: break-word;
                max-width: 200px;
                white-space: normal;
            }
            .widefat {
                table-layout: auto;
                width: 100%;
            }
            .tablenav {
                margin-top: 10px;
            }
        ');
    }
    
    /**
     * Handle AJAX request for processing user batches
     */
    public function ajax_process_user_batch() {
        // Verify user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }

        // Get the offset and total deleted count from the request
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $total_deleted = isset($_POST['total_deleted']) ? intval($_POST['total_deleted']) : 0;
        $batch_size = 100;

        // Log the request data
        error_log('AJAX Processing - Offset: ' . $offset . ', Total Deleted: ' . $total_deleted);

        // Get the form data
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $last_login = isset($_POST['last_login']) ? sanitize_text_field($_POST['last_login']) : '';
        $user_registered = isset($_POST['user_registered']) ? sanitize_text_field($_POST['user_registered']) : '';
        $whitelist_domains = isset($_POST['whitelist_domains']) ? sanitize_text_field($_POST['whitelist_domains']) : '';
        $create_backup = isset($_POST['create_backup']) ? $_POST['create_backup'] === 'true' : false;

        // Process the current batch based on type
        $args = [
            'number' => $batch_size,
            'offset' => $offset,
            'fields' => 'all_with_meta',
        ];

        // Get the type-specific query arguments
        $type_args = $this->get_query_args($type, [
            'last_login' => $last_login,
            'user_registered' => $user_registered,
            'whitelist_domains' => $whitelist_domains
        ]);

        // Merge the batch-specific args with the type-specific args
        $args = array_merge($args, $type_args);

        // Get users for the current batch
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();

        if (empty($users)) {
            wp_send_json_success([
                'continue' => false,
                'total_deleted' => $total_deleted,
                'message' => 'All users processed'
            ]);
            return;
        }

        // Prepare batch for deletion
        $batch = [];
        foreach ($users as $user) {
            $batch[] = [
                'user_id' => $user->ID,
                'reason' => $this->get_user_reason($user, $type, $last_login, $user_registered)
            ];
        }

        // Process the batch
        $deleted_count = $this->delete_users($batch, $create_backup);
        if ($deleted_count === false) {
            wp_send_json_error('Error processing batch');
            return;
        }

        // Update total deleted count
        $total_deleted += $deleted_count;

        // Send response
        wp_send_json_success([
            'continue' => true,
            'next_offset' => $offset + $batch_size,
            'total_deleted' => $total_deleted,
            'batch_size' => count($batch),
            'message' => sprintf('Processed %d users in current batch', count($batch))
        ]);
    }

    /**
     * Get the reason for user deletion based on type
     */
    private function get_user_reason($user, $type, $last_login = '', $user_registered = '') {
        switch ($type) {
            case 'option1':
                $last_login_date = get_user_meta($user->ID, 'last_login', true) ?: 'Never';
                return "Inactive (Last login: $last_login_date), Registered: {$user->user_registered}";
            
            case 'option2':
                $last_login_date = get_user_meta($user->ID, 'last_login', true) ?: 'Unknown';
                return "Active (Last login: $last_login_date), No WP3 Access";
            
            case 'option3':
                return "Unconfirmed, Registered: {$user->user_registered}";
            
            case 'option4':
                return $this->analyze_user($user, $this->load_bad_words(), $this->get_whitelist_domains([]), $this->load_burner_domains(), ['asdf', 'qwerty', 'zxcvbn', 'abc123', 'password']);
            
            default:
                return 'Unknown reason';
        }
    }
    
    private function normalize_username($username) {
        $username = strtolower($username);
        $username = str_replace(['.', '_', '-', '+'], '', $username);
        $username = preg_replace('/[0-9]/', '', $username);
        return strtr($username, $this->leet_map);
    }
    
    /**
     * Get query arguments based on type and parameters
     */
    private function get_query_args($type, $params) {
        $args = [
            'number' => -1,
            'fields' => 'all_with_meta',
        ];

        switch ($type) {
            case 'option1':
                // Inactive + Registered Before
                if (!empty($params['user_registered'])) {
                    $args['date_query'] = [
                        [
                            'before' => $params['user_registered'],
                            'inclusive' => true,
                            'column' => 'user_registered'
                        ]
                    ];
                }

                $args['meta_query'] = [
                    'relation' => 'AND',
                    [
                        'key' => 'account_status',
                        'value' => 'archived',
                        'compare' => '='
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key' => 'last_login',
                            'value' => $params['last_login'],
                            'compare' => '<',
                            'type' => 'DATE',
                        ],
                        [
                            'key' => 'last_login',
                            'compare' => 'NOT EXISTS',
                        ]
                    ]
                ];

                // Debug logging
                error_log("Option1 Query Parameters:");
                error_log("user_registered: " . ($params['user_registered'] ?? 'not set'));
                error_log("last_login: " . ($params['last_login'] ?? 'not set'));
                error_log("Query Args: " . print_r($args, true));
                break;

            case 'option2':
                // Active + No WP3 Access
                $meta_query = [
                    'relation' => 'AND',
                    [
                        'key' => 'wp_3_access',
                        'value' => 'N',
                        'compare' => '='
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key' => 'account_status',
                            'value' => 'active',
                            'compare' => '='
                        ],
                        [
                            'key' => 'account_status',
                            'compare' => 'NOT EXISTS'
                        ]
                    ]
                ];

                // Add last_login date condition if provided
                if (!empty($params['last_login'])) {
                    $meta_query[] = [
                        'key' => 'last_login',
                        'value' => $params['last_login'],
                        'compare' => '<',
                        'type' => 'DATE'
                    ];
                }

                $args['meta_query'] = $meta_query;
                break;

            case 'option3':
                // Unconfirmed + Registered Before
                $args['meta_query'] = [
                    'relation' => 'AND',
                    [
                        'key' => 'user_confirmed',
                        'value' => 'N',
                        'compare' => '='
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key' => 'last_login',
                            'value' => $params['last_login'],
                            'compare' => '<',
                            'type' => 'DATE',
                        ],
                        [
                            'key' => 'last_login',
                            'compare' => 'NOT EXISTS',
                        ]
                    ]
                ];

                if (!empty($params['user_registered'])) {
                    $args['date_query'] = [
                        [
                            'before' => $params['user_registered'],
                            'inclusive' => true,
                            'column' => 'user_registered'
                        ]
                    ];
                }
                break;

            case 'option4':
                // Abnormal Email Patterns
                $args['number'] = -1;
                break;
        }

        return $args;
    }

    public function process_sync($params) {
        try {
            $type = sanitize_text_field($params['type'] ?? '');
            $paged = max(1, (int)($params['paged'] ?? 1));
            $export = filter_var($params['export'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $per_page = isset($params['per_page']) ? (int)$params['per_page'] : $this->per_page;

            error_log("process_sync called with params: " . print_r($params, true));
            error_log("Type: $type, Paged: $paged, Export: " . ($export ? 'true' : 'false') . ", Per Page: $per_page");

            // Delete any existing transient for this type
            $cache_key = 'sync_' . $type . '_' . md5(serialize($params));
            delete_transient($cache_key);
            
            error_log("Cache cleared for key: $cache_key");
            
            $args = $this->get_query_args($type, $params);
            $user_query = new WP_User_Query($args);
            $users = $user_query->get_results();
            $total_users = $user_query->get_total();
            error_log("Total users in database ($type): $total_users");
            
            $all_results = [];
            foreach ($users as $user) {
                $all_results[$user->ID] = [
                    'user_id' => $user->ID,
                    'user_email' => $user->user_email,
                    'user_login' => $user->user_login,
                    'user_registered' => $user->user_registered,
                    'reason' => $this->get_user_reason($user, $type, $params['last_login'] ?? '', $params['user_registered'] ?? '')
                ];
            }
            
            $all_results = array_values($all_results);
            error_log("Total results ($type): " . count($all_results));
            
            return $this->process_results($all_results, $paged, $export, $per_page);
        } catch (Exception $e) {
            error_log("UserSync Error: " . $e->getMessage());
            return ['total' => 0, 'results' => [], 'output' => 'An error occurred'];
        }
    }
    
    private function process_results($all_results, $paged, $export, $per_page) {
        $total = count($all_results);
        
        // For export, process all results; for display, paginate
        $offset = ($paged - 1) * $per_page;
        $paged_results = $export ? $all_results : array_slice($all_results, $offset, $per_page);
        error_log("Paged results for page $paged (offset $offset): " . count($paged_results));
        
        // Convert stored data back to user objects for rendering/exporting
        $paged_results_with_users = [];
        foreach ($paged_results as $result) {
            $user = get_user_by('id', $result['user_id']);
            if ($user) {
                $paged_results_with_users[] = [
                    'user' => $user,
                    'reason' => $result['reason']
                ];
            }
        }
        
        if ($export) {
            $this->export_csv($paged_results_with_users);
            // No return needed since export_csv will exit
        }
        
        return [
            'total' => $total,
            'results' => $paged_results_with_users,
            'output' => $this->render_html($paged_results_with_users, $total, $paged)
        ];
    }
    
    public function delete_users($results, $create_backup = false) {
        if (empty($results)) {
            return false;
        }

        $disabled_count = 0;
        $backup_sql = [];
        $timestamp = current_time('mysql');
        
        // Define bypass domains and roles
        $bypass_domains = ['@ronikdesign.com', '@divisionof.com'];
        $bypass_roles = ['administrator', 'super_admin'];
        
        // Set limit for processing - higher limit when backup is disabled
        $processed_count = 0;
        $max_users = $create_backup ? 2000 : 5000; // 2000 with backup, 5000 without
        $batch_size = 100; // Process 100 users at a time
        
        // Define all possible meta keys
        $all_meta_keys = [
            'nickname', 'rich_editing', 'syntax_highlighting', 'use_ssl', 'show_admin_bar_front',
            'wp_3_capabilities', 'wp_3_user_level', '_yoast_wpseo_profile_updated', 'primary_blog',
            'source_domain', 'user_whitelisted', 'user_hash', 'distinct_id', 'user_company',
            'user_title', 'wp_2_access', 'wp_3_access', 'wp_3_registered', 'wp_2_capabilities',
            'wp_2_user_level', 'user_account_updates', 'user_account_updated', 'pat_status',
            'account_status', 'user_email', 'is_ae', 'wp_3_last_approval_sent', 'user_confirmed',
            'user_log', 'resend-register-email-count', 'send-forgot-pass-email-count', 'last_login',
            'lockout_until', 'login_attempts', 'login_history', 'session_tokens', 'remember_key',
            'wpseo_ignore_tour', 'manageusers_page_nbcu-userscolumnshidden', 'wp_capabilities',
            'wp_user_level', 'community-events-location', 'wp_3_ac_preferences_sorted_by',
            'wp_3_ac_preferences_layout_table', 'wp_7_capabilities', 'wp_7_user_level',
            'wp_7_dashboard_quick_press_last_post_id', 'wp_5_capabilities', 'wp_5_user_level',
            'wp_6_capabilities', 'wp_6_user_level', 'user_phone', 'nbcu_sso_id', 'wp_3_ate_activated',
            'wp_3_language_pairs', 'wpml_enabled_for_translation_via_ate', 'wp_3_ac_preferences_settings',
            'ac_preferences_admin_screen_options', '_network', 'user_history', 'restricted_whitelist',
            'wp_3_approval_email_path', 'registration_sent_email', 'register_history',
            'closedpostboxes_page', 'metaboxhidden_page', 'nav_menu_recently_edited',
            'managenav-menuscolumnshidden', 'metaboxhidden_nav-menus', 'user_company_group',
            'user_twitter', 'last_modified', 'closedpostboxes_site-options_page_whitelist-option',
            'metaboxhidden_site-options_page_whitelist-options', 'closedpostboxes_videos',
            'metaboxhidden_videos', 'wp_3_user-settings', 'wp_3_user-settings-time',
            'wp_3_media_library_mode', 'edit_acf-field-group_per_page', 'wp_6_user-settings',
            'wp_6_user-settings-time', 'user_click_actions', 'wp_2_user-settings',
            'wp_7_ac_preferences_layout_table', 'wp_7_ac_preferences_sorted_by', 'edit_talent_per_page',
            'wp_7_user-settings', 'wp_7_user-settings-time', '_yoast_wpseo_introductions',
            'user_tracker_actions', 'ae_beta_features', 'wp_6_dashboard_quick_press_last_post_id',
            'last_viewed_notifications', 'user_password_tracker_actions', 'ronikdesign_initialization_vector',
            'wp_approval_hash', 'ronik_history_registration', 'wp_user-settings-time-password-reset',
            'sms_code_timestamp', 'auth_lockout_counter', 'ronik_password_history', 'pat_contact_id',
            'wp_5_user-settings', 'wp_5_user-settings-time', 'meta-box-order_page', 'screen_layout_page',
            'closedpostboxes_talent', 'metaboxhidden_talent', 'wp_7_ac_preferences_editability_state',
            'manageedit-acf-ui-options-pagecolumnshidden', 'acf_user_settings',
            'meta-box-order_site-options_page_whitelist-options',
            'screen_layout_site-options_page_whitelist-options', 'dynamic_user_login_url',
            'wp_7_ac_preferences_settings', 'wp_media_library_mode', 'ame_rui_first_login_done',
            'network', 'auth_status', 'sms_user_phone', 'sms_2fa_status', 'sms_2fa_secret',
            'wp_3_yoast_notifications'
        ];
        
        // Get the current batch of users
        $current_batch = array_slice($results, 0, $batch_size);
        
        foreach ($current_batch as $entry) {
            // Check if we've reached the limit
            if ($processed_count >= $max_users) {
                error_log("Reached maximum limit of $max_users users. Please run the delete again to process more users.");
                break;
            }

            $user = get_user_by('id', $entry['user_id']);
            if ($user) {
                // Check if user is an admin or super admin
                $user_roles = $user->roles ?? [];
                $is_admin = false;
                foreach ($user_roles as $role) {
                    if (in_array($role, $bypass_roles)) {
                        $is_admin = true;
                        error_log("Skipping user {$user->user_email} (admin/super admin)");
                        break;
                    }
                }
                
                if ($is_admin) {
                    continue;
                }

                // Check if user email is in bypass domains
                $should_bypass = false;
                foreach ($bypass_domains as $domain) {
                    if (strpos($user->user_email, $domain) !== false) {
                        $should_bypass = true;
                        error_log("Skipping user {$user->user_email} (bypass domain)");
                        break;
                    }
                }
                
                if ($should_bypass) {
                    continue;
                }

                // Check for is_ae meta key with value 'Y'
                $is_ae = get_user_meta($user->ID, 'is_ae', true);
                if ($is_ae === 'Y') {
                    error_log("Skipping user {$user->user_email} (is_ae = Y)");
                    continue;
                }

                // Check for nbcu_sso_id meta key (any value)
                $nbcu_sso_id = get_user_meta($user->ID, 'nbcu_sso_id', true);
                if (!empty($nbcu_sso_id)) {
                    error_log("Skipping user {$user->user_email} (has nbcu_sso_id)");
                    continue;
                }

                // Log the user that would be deleted
                error_log(sprintf(
                    "Would delete user: ID=%d, Email=%s, Username=%s, Reason=%s",
                    $user->ID,
                    $user->user_email,
                    $user->user_login,
                    $entry['reason']
                ));

                // Create backup SQL for this user if requested
                if ($create_backup) {
                    $backup_sql[] = sprintf(
                        "INSERT INTO wp_users_backup (user_id, user_login, user_email, user_registered, user_status, display_name, backup_date, reason) VALUES (%d, '%s', '%s', '%s', %d, '%s', '%s', '%s');",
                        $user->ID,
                        esc_sql($user->user_login),
                        esc_sql($user->user_email),
                        $user->user_registered,
                        $user->user_status,
                        esc_sql($user->display_name),
                        $timestamp,
                        esc_sql($entry['reason'])
                    );

                    // Get all user meta data
                    $user_meta = get_user_meta($user->ID);
                    
                    // Add user meta data to backup for all possible keys
                    foreach ($all_meta_keys as $meta_key) {
                        $meta_value = isset($user_meta[$meta_key]) ? $user_meta[$meta_key][0] : '';
                        $backup_sql[] = sprintf(
                            "INSERT INTO wp_usermeta_backup (user_id, meta_key, meta_value) VALUES (%d, '%s', '%s');",
                            $user->ID,
                            esc_sql($meta_key),
                            esc_sql($meta_value)
                        );
                    }
                }

                // Delete user and reassign content to user ID 79304
                if (wp_delete_user($user->ID, 79304)) {
                    $disabled_count++;
                    $processed_count++;
                    error_log("Deleted user {$user->user_email} and reassigned content to user ID 79304");
                } else {
                    error_log("Failed to delete user {$user->user_email}");
                }
            }
        }

        // Save backup SQL to a file if requested and we have SQL to save
        if ($create_backup && !empty($backup_sql)) {
            $backup_dir = WP_CONTENT_DIR . '/user-backups';
            if (!file_exists($backup_dir)) {
                wp_mkdir_p($backup_dir);
            }
            
            $backup_file = $backup_dir . '/user_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backup_content = "-- User Backup SQL\n";
            $backup_content .= "-- Generated on: " . $timestamp . "\n";
            $backup_content .= "-- Total users: " . count($results) . "\n\n";
            
            // Add table creation if not exists
            $backup_content .= "CREATE TABLE IF NOT EXISTS wp_users_backup (
                user_id bigint(20) NOT NULL,
                user_login varchar(60) NOT NULL,
                user_email varchar(100) NOT NULL,
                user_registered datetime NOT NULL,
                user_status int(11) NOT NULL,
                display_name varchar(250) NOT NULL,
                backup_date datetime NOT NULL,
                reason text NOT NULL,
                PRIMARY KEY (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
            
            $backup_content .= "CREATE TABLE IF NOT EXISTS wp_usermeta_backup (
                umeta_id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                meta_key varchar(255) NOT NULL,
                meta_value longtext NOT NULL,
                PRIMARY KEY (umeta_id),
                KEY user_id (user_id),
                KEY meta_key (meta_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
            
            $backup_content .= implode("\n", $backup_sql);
            
            file_put_contents($backup_file, $backup_content);
            error_log("User backup saved to: " . $backup_file);
        }

        return $disabled_count;
    }
    
    private function analyze_user($user, $bad_words, $whitelist_domains, $burner_domains, $spammy_patterns) {
        $reason = [];
        $email = strtolower(trim($user->user_email));
        [$username, $domain] = explode('@', $email) + ['', ''];
        
        error_log("Analyzing email: $email, Username: $username, Domain: $domain");
        
        if (empty($username) || empty($domain)) {
            error_log("Flagging: Invalid email format");
            return ['invalid email format'];
        }
        if (in_array($domain, $whitelist_domains)) {
            error_log("Skipping: Domain in whitelist");
            return [];
        }
        if (in_array($domain, $burner_domains)) {
            error_log("Flagging: Burner domain");
            $reason[] = 'burner domain';
        }
        
        // Check for plus sign in the username (e.g., david+1@ronikdesign.com)
        if (strpos($username, '+') !== false) {
            error_log("Flagging: Email contains plus aliasing");
            $reason[] = 'email contains plus aliasing';
        }
        
        $cleaned = $this->normalize_username($username);
        error_log("Normalized username: $cleaned");
        
        foreach ($bad_words as $word) {
            if (strlen($word) < 3) continue;
            similar_text($cleaned, $word, $percent);
            if ($percent > 90 || strpos($cleaned, $word) !== false) {
                error_log("Flagging: Matched bad word '$word' (Similarity: $percent%)");
                $reason[] = "matched: $word";
                break;
            }
        }
        
        if (strlen($cleaned) > 10 && preg_match_all('/[aeiou]/', $cleaned) < 3) {
            error_log("Flagging: Gibberish username");
            $reason[] = 'gibberish username';
        }
        if (preg_match('/^\d+$/', $username)) {
            error_log("Flagging: Numeric-only username");
            $reason[] = 'numeric-only username';
        }
        if (preg_match('/(.)\1{4,}/', $username)) {
            error_log("Flagging: Repeating characters");
            $reason[] = 'repeating characters';
        }
        
        foreach ($spammy_patterns as $pattern) {
            if (stripos($username, $pattern) !== false) {
                error_log("Flagging: Contains spammy pattern '$pattern'");
                $reason[] = "contains pattern: $pattern";
                break;
            }
        }
        
        if (str_ends_with($domain, '.xyz') || str_contains($email, 'noreply') || 
            str_contains($email, '@test') || str_contains($email, '@example.com') || 
            strlen($email) < 8) {
            error_log("Flagging: Known temp/suspicious pattern");
            $reason[] = 'known temp/suspicious pattern';
        }
        
        $domain_prefix = str_replace('.', '', explode('.', $domain)[0] ?? '');
        if ($username === $domain_prefix) {
            error_log("Flagging: Username equals domain");
            $reason[] = 'username equals domain';
        }
        
        return $reason;
    }
    
    private function load_bad_words() {
        $file = dirname(__FILE__) . '/bad_words.txt';
        $file_exists = file_exists($file);
        $words = $file_exists ? array_filter(array_map('trim', file($file))) : [];
        error_log("Bad words file " . ($file_exists ? "loaded" : "not found") . ": " . count($words) . " words");
        return $words;
    }
    
    private function load_burner_domains() {
        $file = dirname(__FILE__) . '/burner_domains.txt';
        $file_exists = file_exists($file);
        $domains = $file_exists ? array_filter(array_map('trim', file($file))) : [];
        error_log("Burner domains file " . ($file_exists ? "loaded" : "not found") . ": " . count($domains) . " domains");
        return $domains;
    }
    
    private function get_whitelist_domains($params) {
        $raw = $params['whitelist_domains'] ?? get_option('options_whitelist_domains', '');
        $domains = array_filter(array_map('trim', preg_split('/[\r\n,]+/', strtolower($raw))));
        error_log("Whitelist domains retrieved: " . count($domains) . ", Domains: " . implode(', ', $domains));
        return $domains;
    }
    
    private function export_csv($results) {
        // Check if headers have already been sent
        if (headers_sent($file, $line)) {
            error_log("Headers already sent in $file on line $line");
            wp_die('Error: Output already sent before CSV export.');
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="user_sync_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open output stream
        $out = fopen('php://output', 'w');
        if ($out === false) {
            error_log("Failed to open php://output for CSV export");
            wp_die('Error generating CSV file.');
        }

        // Write CSV headers
        fputcsv($out, ['ID', 'Email', 'Username', 'Registered', 'Reason', 'User Whitelisted', 'Account Status']);
        
        // Write each user row
        foreach ($results as $entry) {
            $user = $entry['user'];
            fputcsv($out, [
                $user->ID,
                $user->user_email,
                $user->user_login,
                $user->user_registered,
                $entry['reason'],
                'Whitelisted: '. $user->user_whitelisted . ' wp_3_access: '. $user->wp_3_access,
                ($user->account_status) ? ($user->account_status) : ('Active')
            ]);
        }

        // Close the output stream
        fclose($out);
        exit;
    }
    
    private function render_html($results, $total, $paged) {
        $output = "<strong>Found $total matching users</strong><br><br>";
        if (!empty($results)) {
            $output .= "<table class='widefat fixed striped'><thead><tr>";
            $output .= "<th>ID</th><th>Email</th><th>Username</th><th>Registered</th><th>Reason</th><th>User Whitelisted</th><th>Account Status </th></tr></thead><tbody>";
            foreach ($results as $entry) {
                $user = $entry['user'];
                $reason_html = "<span style='color:red;font-weight:bold;'>" . esc_html($entry['reason']) . "</span>";
                $output .= "<tr>";
                $output .= "<td>" . esc_html($user->ID) . "</td>";
                $output .= "<td>" . esc_html($user->user_email) . "</td>";
                $output .= "<td>" . esc_html($user->user_login) . "</td>";
                $output .= "<td>" . esc_html($user->user_registered) . "</td>";
                $output .= "<td>$reason_html</td>";
                $output .= "<td>" . esc_html('Whitelisted: '. $user->user_whitelisted . '
                    wp_3_access: '. $user->wp_3_access) . "</td>";
                $output .= "<td>" . esc_html(($user->account_status) ? ($user->account_status) : ('Active')) . "</td>";
                $output .= "</tr>";
            }
            $output .= "</tbody></table>";
        } else {
            $output .= "No matching users found.";
        }
        return $output;
    }
}