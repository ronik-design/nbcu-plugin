<?php

// Security check
if (!defined('ABSPATH')) {
    exit('Direct access denied');
}

add_action('admin_menu', 'ronikdesigns_sync_user_admin_menu');
add_action('admin_init', 'ronikdesigns_handle_csv_export');

function ronikdesigns_sync_user_admin_menu() {
    add_menu_page(
        'User Sync Tool',
        'User Sync',
        'manage_options',
        'ronikdesigns-sync-user',
        'ronikdesigns_sync_user_admin_page',
        'dashicons-filter',
        80
    );
}

function ronikdesigns_handle_csv_export() {
    // Check for export condition early in the WordPress lifecycle
    if (is_admin() && isset($_POST['page']) && $_POST['page'] === 'ronikdesigns-sync-user' && isset($_POST['export']) && $_POST['export'] === 'true') {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access.');
        }

        // Include the UserSyncHandler class if not already loaded
        require_once dirname(__FILE__) . '/query-handler.php';
        $handler = new UserSyncHandler();
        $handler->process_sync($_POST);
        // The export_csv method will handle the output and exit
        exit; // Ensure no further processing happens
    }
}

function ronikdesigns_sync_user_admin_page() {
    // Normal page rendering (non-export case)
    $type = $_POST['type'] ?? 'option1';
    $last_login = $_POST['last_login'] ?? '2022-03-27';
    $user_registered = $_POST['user_registered'] ?? '2022-03-27';
    $export = $_POST['export'] ?? '';
    $whitelist_domains = $_POST['whitelist_domains'] ?? get_option('options_whitelist_domains', '');
    $paged = isset($_POST['paged']) ? max(1, (int) $_POST['paged']) : 1;
    $per_page = 100;

    $handler = new UserSyncHandler();
    $result = ['total' => 0, 'results' => [], 'output' => ''];

    // Process the form submission for display (not export)
    if (is_admin() && isset($_POST['page']) && $_POST['page'] === 'ronikdesigns-sync-user' && empty($_POST['export'])) {
        // Handle deletion if checkbox was checked
        if (isset($_POST['delete_results']) && $_POST['delete_results'] === 'true') {
            // Get all results without pagination
            $all_results = $handler->process_sync(array_merge($_POST, ['paged' => 1, 'per_page' => -1]));
            $deleted_count = $handler->delete_users($all_results['results']);
            if ($deleted_count !== false) {
                echo '<div class="notice notice-success"><p>' . sprintf(
                    _n('%d user was deleted.', '%d users were deleted.', $deleted_count, 'ronikdesigns'),
                    $deleted_count
                ) . '</p></div>';
            }
            // Don't run the query again after deletion
            return;
        }
        
        // Only run the query if we're not deleting
        $result = $handler->process_sync($_POST);
    }
?>
    <div class="wrap">
        <h1>User Sync Tool</h1>

        <script>
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        </script>

        <div id="delete-progress" style="display: none; margin: 20px 0; padding: 20px; background: #f8f9fa; border: 1px solid #ddd;">
            <h3>Deletion Progress</h3>
            <div class="progress-bar" style="height: 20px; background: #eee; border-radius: 3px; margin: 10px 0;">
                <div class="progress-fill" style="width: 0%; height: 100%; background: #2271b1; border-radius: 3px; transition: width 0.3s;"></div>
            </div>
            <p class="progress-status">Processing batch 0 of 0...</p>
            <p class="progress-detail">Users deleted: <span class="deleted-count">0</span></p>
        </div>

        <form method="post" action="" id="sync-form">
            <input type="hidden" name="page" value="ronikdesigns-sync-user">
            <input type="hidden" name="paged" value="<?php echo esc_attr($paged); ?>">
            <input type="hidden" name="total_users" id="total-users" value="0">
            <?php wp_nonce_field('process_user_batch', 'nonce'); ?>

            <p>
                <label for="type">Query Type:</label>
                <select name="type" id="type">
                    <option value="option1" <?php selected($type, 'option1'); ?>>Inactive + Registered Before</option>
                    <option value="option2" <?php selected($type, 'option2'); ?>>Active + No WP3 Access</option>
                    <option value="option3" <?php selected($type, 'option3'); ?>>Unconfirmed + Registered Before</option>
                    <option value="option4" <?php selected($type, 'option4'); ?>>Abnormal Email Patterns</option>
                </select>
            </p>

            <?php if ($type === 'option4') : ?>
                <p style="color: #666;"><em>Scanning for fake-looking emails like <code>12345@</code>, <code>@example.com</code>, <code>noreply@</code>, gibberish usernames, and burner domains.</em></p>
                <p>
                    <label for="whitelist_domains">Whitelist Domains:</label><br>
                    <textarea name="whitelist_domains" id="whitelist_domains" rows="5" cols="60" placeholder="example.com, test.com"><?php echo esc_textarea($whitelist_domains); ?></textarea>
                </p>
            <?php endif; ?>

            <div id="date-fields">
                <?php if ($type === 'option2') : ?>
                    <p>
                        <label for="last_login">Last Login Before:</label><br>
                        <input type="date" name="last_login" id="last_login" value="<?php echo esc_attr($last_login); ?>">
                    </p>
                <?php else : ?>
                    <p>
                        <label for="last_login">Last Login Before:</label><br>
                        <input type="date" name="last_login" id="last_login" value="<?php echo esc_attr($last_login); ?>">
                    </p>

                    <p>
                        <label for="user_registered">User Registered Before:</label><br>
                        <input type="date" name="user_registered" id="user_registered" value="<?php echo esc_attr($user_registered); ?>">
                    </p>

                    <small style="color: #666;">
                        Example Query: <code>last_login < '2018-03-01'</code> and <code>user_registered < '2017-03-01'</code>
                    </small>
                <?php endif; ?>
            </div>

            <p>
                <label><input type="checkbox" name="export" value="true" <?php checked($export, 'true'); ?>> Export as CSV</label>
            </p>
  
            <p>
                <label><input type="checkbox" name="delete_results" value="true" id="delete-results"> Delete results after display</label>
            </p>
            
            <p id="backup-checkbox-container" style="display: none;">
                <label><input type="checkbox" name="create_backup" value="true" <?php checked(isset($_POST['create_backup']) && $_POST['create_backup'] === 'true', true); ?>> Create backup SQL before deletion</label>
            </p>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Run Query">
            </p>
        </form>

        <?php
        if (!empty($_POST['type']) && empty($_POST['export'])) {
            ob_start();
            $output = $result['output'];
            $output = ob_get_clean() . $output;
            echo '<div class="notice notice-info" style="padding:20px; border-left: 4px solid #2271b1; background: #f0f8ff;">';
            echo $output;
            echo '</div>';

            // Pagination
            $total_flagged = $result['total'] ?? 0;
            $total_pages = ceil($total_flagged / $per_page);

            if ($total_pages > 1) {
                echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                if ($paged > 1) {
                    echo '<button class="prev-page button" type="submit" name="paged" value="' . ($paged - 1) . '" form="sync-form">« Previous</button> ';
                }
                if ($paged < $total_pages) {
                    echo '<button class="next-page button" type="submit" name="paged" value="' . ($paged + 1) . '" form="sync-form">Next »</button>';
                }
                echo '</div></div>';
            }
        }
        ?>
    </div>

    <script>
        (function () {
            const typeField = document.getElementById('type');
            const form = document.getElementById('sync-form');
            const dateFields = document.getElementById('date-fields');
            const lastLoginInput = document.getElementById('last_login');
            const registeredInput = document.getElementById('user_registered');
            const deleteResultsCheckbox = document.getElementById('delete-results');
            const backupCheckboxContainer = document.getElementById('backup-checkbox-container');
            const progressDiv = document.getElementById('delete-progress');
            const progressFill = document.querySelector('.progress-fill');
            const progressStatus = document.querySelector('.progress-status');
            const deletedCount = document.querySelector('.deleted-count');
            const totalUsersInput = document.getElementById('total-users');
            const nonceField = document.querySelector('input[name="nonce"]');

            function toggleDateFields() {
                const selected = typeField.value;
                const hideDates = (selected === 'option4');

                if (hideDates) {
                    dateFields.style.display = 'none';
                    lastLoginInput.value = '';
                    registeredInput.value = '';
                } else {
                    dateFields.style.display = 'block';
                    if (selected === 'option2') {
                        // For option2, only show last_login field
                        lastLoginInput.parentElement.style.display = 'block';
                        registeredInput.parentElement.style.display = 'none';
                    } else {
                        // For other options, show both fields
                        lastLoginInput.parentElement.style.display = 'block';
                        registeredInput.parentElement.style.display = 'block';
                    }
                    lastLoginInput.value = '<?= $last_login; ?>';
                    registeredInput.value = '<?= $user_registered; ?>';
                }
            }

            typeField.addEventListener('change', toggleDateFields);
            toggleDateFields();

            form.addEventListener('submit', function (e) {
                if (typeField.value === 'option2' || typeField.value === 'option4') {
                    lastLoginInput.removeAttribute('name');
                    registeredInput.removeAttribute('name');
                }

                if (deleteResultsCheckbox.checked) {
                    e.preventDefault();
                    startDeleteProcess();
                }
            });

            function startDeleteProcess() {
                // Show progress bar
                progressDiv.style.display = 'block';
                
                // Get total users from the table
                const totalUsers = document.querySelectorAll('.widefat tbody tr').length;
                totalUsersInput.value = totalUsers;
                
                // Start the delete process
                processBatch(0, 0);
            }

            function processBatch(offset, totalDeleted) {
                const batchSize = 100;
                const totalUsers = parseInt(totalUsersInput.value);
                const progress = Math.min(100, (offset / totalUsers) * 100);
                
                // Update progress bar
                progressFill.style.width = progress + '%';
                progressStatus.textContent = `Processing batch ${Math.floor(offset / batchSize) + 1} of ${Math.ceil(totalUsers / batchSize)}...`;
                deletedCount.textContent = totalDeleted;

                // Prepare form data
                const formData = new FormData();
                formData.append('action', 'process_user_batch');
                formData.append('offset', offset);
                formData.append('total_deleted', totalDeleted);
                formData.append('type', form.querySelector('[name="type"]').value);
                formData.append('last_login', form.querySelector('[name="last_login"]')?.value || '');
                formData.append('user_registered', form.querySelector('[name="user_registered"]')?.value || '');
                formData.append('whitelist_domains', form.querySelector('[name="whitelist_domains"]')?.value || '');
                formData.append('create_backup', form.querySelector('[name="create_backup"]')?.checked ? 'true' : 'false');

                // Log the form data for debugging
                console.log('=== AJAX Request Debug ===');
                console.log('URL:', ajaxurl);
                console.log('Form Data:', {
                    type: formData.get('type'),
                    last_login: formData.get('last_login'),
                    user_registered: formData.get('user_registered'),
                    whitelist_domains: formData.get('whitelist_domains'),
                    offset: offset,
                    total_deleted: totalDeleted,
                    create_backup: formData.get('create_backup')
                });

                // Send AJAX request
                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', Object.fromEntries(response.headers.entries()));
                    
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('Error response body:', text);
                            throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received response:', data);
                    if (data.success) {
                        const newTotalDeleted = data.data?.total_deleted || totalDeleted;
                        
                        if (data.data?.continue) {
                            // Add a delay before processing the next batch
                            const delay = 2000; // 2 seconds delay
                            progressStatus.textContent = `Batch ${Math.floor(offset / batchSize) + 1} complete. Waiting ${delay/1000} seconds before next batch...`;
                            
                            setTimeout(() => {
                                processBatch(data.data.next_offset, newTotalDeleted);
                            }, delay);
                        } else {
                            // Process complete
                            progressStatus.textContent = `Deletion complete! Total users deleted: ${newTotalDeleted}`;
                            progressFill.style.width = '100%';
                            deletedCount.textContent = newTotalDeleted;
                            
                            // Show success message and reload after 5 seconds
                            const successMessage = document.createElement('div');
                            successMessage.className = 'notice notice-success';
                            successMessage.style.padding = '10px';
                            successMessage.style.margin = '10px 0';
                            successMessage.innerHTML = `<p>Successfully deleted ${newTotalDeleted} users. Page will reload in 5 seconds...</p>`;
                            progressDiv.appendChild(successMessage);
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 5000);
                        }
                    } else {
                        progressStatus.textContent = 'Error: ' + (data.message || 'Unknown error occurred');
                        console.error('Error response:', data);
                    }
                })
                .catch(error => {
                    progressStatus.textContent = 'Error: ' + error.message;
                    console.error('Fetch error:', error);
                    // Retry after 5 seconds on error
                    setTimeout(() => {
                        processBatch(offset, totalDeleted);
                    }, 5000);
                });
            }

            // Add event listener for delete results checkbox
            deleteResultsCheckbox.addEventListener('change', function() {
                backupCheckboxContainer.style.display = this.checked ? 'block' : 'none';
            });

            // Initial state
            backupCheckboxContainer.style.display = deleteResultsCheckbox.checked ? 'block' : 'none';
        })();
    </script>
<?php
}