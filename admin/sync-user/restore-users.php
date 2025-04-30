<?php

// Security check
if (!defined('ABSPATH')) {
    exit('Direct access denied');
}

function ronikdesigns_restore_users() {
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    $backup_dir = WP_CONTENT_DIR . '/user-backups';
    $backup_files = glob($backup_dir . '/user_backup_*.sql');
    
    if (empty($backup_files)) {
        echo '<div class="notice notice-warning"><p>No backup files found.</p></div>';
        return;
    }

    // Sort files by date (oldest first)
    usort($backup_files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });

    // Define bypass domains
    $bypass_domains = ['@ronikdesign.com', '@divisionof.com'];
    
    // Set limit for processing
    $processed_count = 0;
    $max_users = 2000; // Limit to 2000 users per restore
    $restored_count = 0;
    $errors = [];
    
    // Process each backup file
    foreach ($backup_files as $backup_file) {
        // Read the backup file
        $backup_content = file_get_contents($backup_file);
        if ($backup_content === false) {
            $errors[] = "Failed to read backup file: " . basename($backup_file);
            continue;
        }

        // Split the SQL into individual statements
        $sql_statements = array_filter(array_map('trim', explode(';', $backup_content)));
        
        // Process each SQL statement
        foreach ($sql_statements as $sql) {
            if (empty($sql)) continue;
            
            // Check if we've reached the limit
            if ($processed_count >= $max_users) {
                echo '<div class="notice notice-warning"><p>Reached maximum limit of ' . $max_users . ' users. Please run the restore again to process more users.</p></div>';
                break 2; // Break both loops
            }

            // Check if this is a user or usermeta insert
            if (strpos($sql, 'INSERT INTO wp_users_backup') !== false) {
                // Extract user data
                preg_match("/VALUES\s*\((\d+),\s*'([^']+)',\s*'([^']+)',\s*'([^']+)',\s*(\d+),\s*'([^']+)',\s*'([^']+)',\s*'([^']+)'\)/", $sql, $matches);
                
                if (count($matches) === 9) {
                    list(, $user_id, $user_login, $user_email, $user_registered, $user_status, $display_name, $backup_date, $reason) = $matches;
                    
                    // Check if user email is in bypass domains
                    $should_bypass = false;
                    foreach ($bypass_domains as $domain) {
                        if (strpos($user_email, $domain) !== false) {
                            $should_bypass = true;
                            break;
                        }
                    }
                    
                    if ($should_bypass) {
                        $errors[] = "Skipped user $user_email (bypass domain)";
                        continue;
                    }
                    
                    // Check if user already exists
                    $existing_user = get_user_by('id', $user_id);
                    if ($existing_user) {
                        $errors[] = "User ID $user_id already exists.";
                        continue;
                    }

                    // Create new user
                    $userdata = [
                        'user_login' => $user_login,
                        'user_email' => $user_email,
                        'user_registered' => $user_registered,
                        'user_status' => $user_status,
                        'display_name' => $display_name,
                        'user_pass' => wp_generate_password(12, true) // Generate a random password
                    ];

                    $new_user_id = wp_insert_user($userdata);
                    if (is_wp_error($new_user_id)) {
                        $errors[] = "Failed to restore user $user_login: " . $new_user_id->get_error_message();
                    } else {
                        // Store the original user ID in meta for reference
                        update_user_meta($new_user_id, 'original_user_id', $user_id);
                        $restored_count++;
                        $processed_count++;
                    }
                }
            } elseif (strpos($sql, 'INSERT INTO wp_usermeta_backup') !== false) {
                // Extract usermeta data
                preg_match("/VALUES\s*\((\d+),\s*'([^']+)',\s*'([^']+)'\)/", $sql, $matches);
                
                if (count($matches) === 4) {
                    list(, $user_id, $meta_key, $meta_value) = $matches;
                    
                    // Only add meta if user exists
                    if (get_user_by('id', $user_id)) {
                        update_user_meta($user_id, $meta_key, $meta_value);
                    }
                }
            }
        }
    }

    // Show results
    if ($restored_count > 0) {
        echo '<div class="notice notice-success"><p>' . sprintf(
            _n('%d user was restored.', '%d users were restored.', $restored_count, 'ronikdesigns'),
            $restored_count
        ) . '</p></div>';
    }

    if (!empty($errors)) {
        echo '<div class="notice notice-error"><p>Some errors occurred during restoration:</p><ul>';
        foreach ($errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul></div>';
    }
}

// Add menu item
add_action('admin_menu', function() {
    add_submenu_page(
        'ronikdesigns-sync-user',
        'Restore Users',
        'Restore Users',
        'manage_options',
        'ronikdesigns-restore-users',
        'ronikdesigns_restore_users_page'
    );
});

function ronikdesigns_restore_users_page() {
    ?>
    <div class="wrap">
        <h1>Restore Users from Backup</h1>
        
        <div class="notice notice-warning">
            <p><strong>Warning:</strong> This will restore users from the most recent backup file. Make sure you want to proceed.</p>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('ronikdesigns_restore_users', 'ronikdesigns_restore_nonce'); ?>
            <p>
                <input type="submit" name="restore_users" class="button button-primary" value="Restore Users" onclick="return confirm('Are you sure you want to restore users from the backup?');">
            </p>
        </form>
    </div>
    <?php
}

// Handle form submission
add_action('admin_init', function() {
    if (isset($_POST['restore_users']) && check_admin_referer('ronikdesigns_restore_users', 'ronikdesigns_restore_nonce')) {
        ronikdesigns_restore_users();
    }
}); 