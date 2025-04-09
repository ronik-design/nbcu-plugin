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
    
    private function normalize_username($username) {
        $username = strtolower($username);
        $username = str_replace(['.', '_', '-', '+'], '', $username);
        $username = preg_replace('/[0-9]/', '', $username);
        return strtr($username, $this->leet_map);
    }
    
    public function process_sync($params) {
        try {
            $type = sanitize_text_field($params['type'] ?? '');
            $paged = max(1, (int)($params['paged'] ?? 1));
            $export = filter_var($params['export'] ?? false, FILTER_VALIDATE_BOOLEAN);

            error_log("process_sync called with params: " . print_r($params, true));
            error_log("Type: $type, Paged: $paged, Export: " . ($export ? 'true' : 'false'));

            switch ($type) {
                case 'option1':
                    return $this->handle_option1($paged, $export, $params);
                case 'option2':
                    return $this->handle_option2($paged, $export, $params);
                case 'option3':
                    return $this->handle_option3($paged, $export, $params);
                case 'option4':
                    return $this->handle_abnormal_emails($paged, $export, $params);
                default:
                    error_log("Invalid query type: $type");
                    return ['total' => 0, 'results' => [], 'output' => 'Invalid query type'];
            }
        } catch (Exception $e) {
            error_log("UserSync Error: " . $e->getMessage());
            return ['total' => 0, 'results' => [], 'output' => 'An error occurred'];
        }
    }
    
    private function handle_option1($paged, $export, $params) {
        // Option 1: Inactive + Registered Before
        $last_login = sanitize_text_field($params['last_login'] ?? '');
        $user_registered = sanitize_text_field($params['user_registered'] ?? '');
        
        $cache_key = 'sync_option1_' . md5(serialize($params));
        $cached = get_transient($cache_key);
        
        error_log("Checking cache with key: $cache_key");
        if ($cached !== false) {
            error_log("Cache hit. Cached results count: " . count($cached));
            $all_results = $cached;
        } else {
            error_log("Cache miss or bypassed. Processing fresh data.");
            
            $args = [
                'number' => -1,
                'fields' => 'all_with_meta',
            ];
            
            // Add date query for user registration
            if (!empty($user_registered)) {
                $args['date_query'] = [
                    [
                        'before' => $user_registered,
                        'inclusive' => true,
                    ],
                ];
            }
            
            // Add meta query for last login
            if (!empty($last_login)) {
                $args['meta_query'] = [
                    [
                        'key' => 'last_login',
                        'value' => $last_login,
                        'compare' => '<',
                        'type' => 'DATE',
                    ],
                ];
            } else {
                // If no last_login date, assume inactive if last_login doesn't exist
                $args['meta_query'] = [
                    [
                        'key' => 'last_login',
                        'compare' => 'NOT EXISTS',
                    ],
                ];
            }
            
            $user_query = new WP_User_Query($args);
            $users = $user_query->get_results();
            $total_users = $user_query->get_total();
            error_log("Total users in database (option1): $total_users");
            
            $all_results = [];
            foreach ($users as $user) {
                $last_login_date = get_user_meta($user->ID, 'last_login', true) ?: 'Never';
                $all_results[$user->ID] = [
                    'user_id' => $user->ID,
                    'user_email' => $user->user_email,
                    'user_login' => $user->user_login,
                    'user_registered' => $user->user_registered,
                    'reason' => "Inactive (Last login: $last_login_date), Registered: {$user->user_registered}"
                ];
            }
            
            $all_results = array_values($all_results);
            error_log("Total results (option1): " . count($all_results));
            set_transient($cache_key, $all_results, HOUR_IN_SECONDS * 6);
        }
        
        return $this->process_results($all_results, $paged, $export);
    }
    
    private function handle_option2($paged, $export, $params) {
        // Option 2: Active + No WP3 Access
        $cache_key = 'sync_option2_' . md5(serialize($params));
        $cached = get_transient($cache_key);
        
        error_log("Checking cache with key: $cache_key");
        if ($cached !== false) {
            error_log("Cache hit. Cached results count: " . count($cached));
            $all_results = $cached;
        } else {
            error_log("Cache miss or bypassed. Processing fresh data.");
            
            // Assuming 'last_login' exists for active users, and 'wp3_access' meta indicates WP3 access
            $args = [
                'number' => -1,
                'fields' => 'all_with_meta',
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'last_login',
                        'compare' => 'EXISTS',
                    ],
                    [
                        'key' => 'wp3_access',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ];
            
            $user_query = new WP_User_Query($args);
            $users = $user_query->get_results();
            $total_users = $user_query->get_total();
            error_log("Total users in database (option2): $total_users");
            
            $all_results = [];
            foreach ($users as $user) {
                $last_login_date = get_user_meta($user->ID, 'last_login', true) ?: 'Unknown';
                $all_results[$user->ID] = [
                    'user_id' => $user->ID,
                    'user_email' => $user->user_email,
                    'user_login' => $user->user_login,
                    'user_registered' => $user->user_registered,
                    'reason' => "Active (Last login: $last_login_date), No WP3 Access"
                ];
            }
            
            $all_results = array_values($all_results);
            error_log("Total results (option2): " . count($all_results));
            set_transient($cache_key, $all_results, HOUR_IN_SECONDS * 6);
        }
        
        return $this->process_results($all_results, $paged, $export);
    }
    
    private function handle_option3($paged, $export, $params) {
        // Option 3: Unconfirmed + Registered Before
        $user_registered = sanitize_text_field($params['user_registered'] ?? '');
        
        $cache_key = 'sync_option3_' . md5(serialize($params));
        $cached = get_transient($cache_key);
        
        error_log("Checking cache with key: $cache_key");
        if ($cached !== false) {
            error_log("Cache hit. Cached results count: " . count($cached));
            $all_results = $cached;
        } else {
            error_log("Cache miss or bypassed. Processing fresh data.");
            
            $args = [
                'number' => -1,
                'fields' => 'all_with_meta',
                'meta_query' => [
                    [
                        'key' => 'email_verified',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ];
            
            // Add date query for user registration
            if (!empty($user_registered)) {
                $args['date_query'] = [
                    [
                        'before' => $user_registered,
                        'inclusive' => true,
                    ],
                ];
            }
            
            $user_query = new WP_User_Query($args);
            $users = $user_query->get_results();
            $total_users = $user_query->get_total();
            error_log("Total users in database (option3): $total_users");
            
            $all_results = [];
            foreach ($users as $user) {
                $all_results[$user->ID] = [
                    'user_id' => $user->ID,
                    'user_email' => $user->user_email,
                    'user_login' => $user->user_login,
                    'user_registered' => $user->user_registered,
                    'reason' => "Unconfirmed, Registered: {$user->user_registered}"
                ];
            }
            
            $all_results = array_values($all_results);
            error_log("Total results (option3): " . count($all_results));
            set_transient($cache_key, $all_results, HOUR_IN_SECONDS * 6);
        }
        
        return $this->process_results($all_results, $paged, $export);
    }
    
    private function handle_abnormal_emails($paged, $export, $params) {
        $cache_key = 'sync_flagged_users_' . md5(serialize($params));
        $cached = get_transient($cache_key);
        
        error_log("Checking cache with key: $cache_key");
        if ($cached !== false) {
            error_log("Cache hit. Cached results count: " . count($cached));
            $all_flagged = $cached;
        } else {
            error_log("Cache miss or bypassed. Processing fresh data.");
            $bad_words = $this->load_bad_words();
            $whitelist_domains = $this->get_whitelist_domains($params);
            $burner_domains = $this->load_burner_domains();
            $spammy_patterns = ['asdf', 'qwerty', 'zxcvbn', 'abc123', 'password'];
            
            error_log("Bad words count: " . count($bad_words));
            error_log("Whitelist domains: " . implode(', ', $whitelist_domains));
            error_log("Burner domains count: " . count($burner_domains));
            
            $all_flagged = [];
            
            $user_query = new WP_User_Query([
                'number' => -1,
                'fields' => 'all_with_meta',
            ]);
            
            $users = $user_query->get_results();
            $total_users = $user_query->get_total();
            error_log("Total users in database: $total_users");
            
            if ($total_users == 0) {
                error_log("No users found in wp_users table.");
            }
            
            foreach ($users as $user) {
                error_log("Analyzing user ID {$user->ID}: Email: {$user->user_email}");
                $flags = $this->analyze_user($user, $bad_words, $whitelist_domains, $burner_domains, $spammy_patterns);
                if (!empty($flags)) {
                    $all_flagged[$user->ID] = [
                        'user_id' => $user->ID,
                        'user_email' => $user->user_email,
                        'user_login' => $user->user_login,
                        'user_registered' => $user->user_registered,
                        'reason' => implode(', ', $flags)
                    ];
                    error_log("Flagged user ID {$user->ID}: " . $user->user_email . " - Reasons: " . implode(', ', $flags));
                } else {
                    error_log("User ID {$user->ID} not flagged.");
                }
            }
            
            $all_flagged = array_values($all_flagged);
            error_log("Total flagged users: " . count($all_flagged));
            set_transient($cache_key, $all_flagged, HOUR_IN_SECONDS * 6);
        }
        
        return $this->process_results($all_flagged, $paged, $export);
    }
    
    private function process_results($all_results, $paged, $export) {
        $total = count($all_results);
        
        // For export, process all results; for display, paginate
        $offset = ($paged - 1) * $this->per_page;
        $paged_results = $export ? $all_results : array_slice($all_results, $offset, $this->per_page);
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