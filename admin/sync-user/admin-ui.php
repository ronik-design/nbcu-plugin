<?php

// Security check
if (!defined('ABSPATH')) {
    exit('Direct access denied');
}

add_action('admin_menu', 'ronikdesigns_sync_user_admin_menu');
add_action('admin_init', 'ronikdesigns_handle_csv_export');

function ronikdesigns_sync_user_admin_menu()
{
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

function ronikdesigns_handle_csv_export()
{
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

function ronikdesigns_sync_user_admin_page()
{
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

        // Check if this is the initial query for delete process
        if (isset($_POST['delete_results']) && $_POST['delete_results'] === 'false') {
            // This is the initial query to get total users for delete process
            // Remove delete_results from params to prevent actual deletion
            $query_params = $_POST;
            unset($query_params['delete_results']);

            // Just get the count without processing results
            $args = [
                'number' => -1,
                'fields' => 'all_with_meta',
            ];

            // Add type-specific query args
            switch ($query_params['type']) {
                case 'option1':
                    // Inactive + Registered Before
                    if (!empty($query_params['user_registered'])) {
                        $args['date_query'] = [
                            [
                                'before' => $query_params['user_registered'],
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
                                'value' => $query_params['last_login'],
                                'compare' => '<',
                                'type' => 'DATE',
                            ],
                            [
                                'key' => 'last_login',
                                'compare' => 'NOT EXISTS',
                            ]
                        ]
                    ];
                    break;

                case 'option2':
                    // Active + No WP3 Access
                    if (!empty($query_params['user_registered'])) {
                        $args['date_query'] = [
                            [
                                'before' => $query_params['user_registered'],
                                'inclusive' => true,
                                'column' => 'user_registered'
                            ]
                        ];
                    }

                    $args['meta_query'] = [
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

                    if (!empty($query_params['last_login'])) {
                        $args['meta_query'][] = [
                            'relation' => 'OR',
                            [
                                'key' => 'last_login',
                                'value' => $query_params['last_login'],
                                'compare' => '<',
                                'type' => 'DATE',
                            ],
                            [
                                'key' => 'last_login',
                                'compare' => 'NOT EXISTS',
                            ]
                        ];
                    }
                    break;

                case 'option3':
                    // Unconfirmed + Registered Before
                    if (!empty($query_params['user_registered'])) {
                        $args['date_query'] = [
                            [
                                'before' => $query_params['user_registered'],
                                'inclusive' => true,
                                'column' => 'user_registered'
                            ]
                        ];
                    }

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
                                'value' => $query_params['last_login'],
                                'compare' => '<',
                                'type' => 'DATE',
                            ],
                            [
                                'key' => 'last_login',
                                'compare' => 'NOT EXISTS',
                            ]
                        ]
                    ];
                    break;

                case 'option4':
                    // Abnormal Email Patterns - no date filtering needed
                    $args['number'] = -1;
                    break;

                case 'option5':
                    // Target only Archived users
                    if (!empty($query_params['last_login'])) {
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
                                    'value' => $query_params['last_login'],
                                    'compare' => '<',
                                    'type' => 'DATE',
                                ],
                                [
                                    'key' => 'last_login',
                                    'compare' => 'NOT EXISTS',
                                ]
                            ]
                        ];
                    } else {
                        $args['meta_query'] = [
                            [
                                'key' => 'account_status',
                                'value' => 'archived',
                                'compare' => '='
                            ]
                        ];
                    }
                    break;


                case 'option6':
                    // Target Incomplete Users
                    if (!empty($query_params['user_registered'])) {
                        $args['date_query'] = [
                            [
                                'before' => $query_params['user_registered'],
                                'inclusive' => true,
                                'column' => 'user_registered'
                            ]
                        ];
                    }


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
                                'key' => 'account_status',
                                'value' => 'active',
                                'compare' => '='
                            ],
                            [
                                'key' => 'account_status',
                                'value' => 'archived',
                                'compare' => '='
                            ],
                            [
                                'key' => 'account_status',
                                'compare' => 'NOT EXISTS'
                            ]
                        ],
                        [
                            'relation' => 'AND',
                            [
                                'relation' => 'OR',
                                [
                                    'key' => 'first_name',
                                    'compare' => 'NOT EXISTS'
                                ],
                                [
                                    'key' => 'first_name',
                                    'value' => '',
                                    'compare' => '='
                                ]
                            ],
                            [
                                'relation' => 'OR',
                                [
                                    'key' => 'last_name',
                                    'compare' => 'NOT EXISTS'
                                ],
                                [
                                    'key' => 'last_name',
                                    'value' => '',
                                    'compare' => '='
                                ]
                            ],
                            [
                                'relation' => 'OR',
                                [
                                    'key' => 'user_title',
                                    'compare' => 'NOT EXISTS'
                                ],
                                [
                                    'key' => 'user_title',
                                    'value' => '',
                                    'compare' => '='
                                ]
                            ]
                        ]
                    ];

                    if (!empty($query_params['last_login'])) {
                        $args['meta_query'][] = [
                            'relation' => 'OR',
                            [
                                'key' => 'last_login',
                                'value' => $query_params['last_login'],
                                'compare' => '<',
                                'type' => 'DATE',
                            ],
                            [
                                'key' => 'last_login',
                                'compare' => 'NOT EXISTS',
                            ]
                        ];
                    }
                    break;








                case 'option7':
                    // Target Incomplete Users
                    if (!empty($query_params['user_registered'])) {
                        $args['date_query'] = [
                            [
                                'before' => $query_params['user_registered'],
                                'inclusive' => true,
                                'column' => 'user_registered'
                            ]
                        ];
                    }

                    $args['meta_query'] = [

                        'relation' => 'AND',
                        [
                            'relation' => 'OR',
                            [
                                'key' => 'first_name',
                                'compare' => 'NOT EXISTS'
                            ],
                            [
                                'key' => 'first_name',
                                'value' => '',
                                'compare' => '='
                            ]
                        ],
                        [
                            'relation' => 'OR',
                            [
                                'key' => 'last_name',
                                'compare' => 'NOT EXISTS'
                            ],
                            [
                                'key' => 'last_name',
                                'value' => '',
                                'compare' => '='
                            ]
                        ],
                        [
                            'relation' => 'OR',
                            [
                                'key' => 'user_title',
                                'compare' => 'NOT EXISTS'
                            ],
                            [
                                'key' => 'user_title',
                                'value' => '',
                                'compare' => '='
                            ]
                        ]

                    ];

                    if (!empty($query_params['last_login'])) {
                        $args['meta_query'][] = [
                            'relation' => 'OR',
                            [
                                'key' => 'last_login',
                                'value' => $query_params['last_login'],
                                'compare' => '<',
                                'type' => 'DATE',
                            ],
                            [
                                'key' => 'last_login',
                                'compare' => 'NOT EXISTS',
                            ]
                        ];
                    }
                    break;

                case 'option8':
                    // Active + Registered Before
                    if (!empty($query_params['user_registered'])) {
                        $args['date_query'] = [
                            [
                                'before' => $query_params['user_registered'],
                                'inclusive' => true,
                                'column' => 'user_registered'
                            ]
                        ];
                    }

                    $args['meta_query'] = [
                        'relation' => 'AND',
                        [
                            'relation' => 'OR',
                            [
                                'key' => 'user_confirmed',
                                'value' => 'Y',
                                'compare' => '='
                            ],
                            [
                                'key' => 'user_confirmed',
                                'compare' => 'NOT EXISTS'
                            ]
                        ],
                        [
                            'relation' => 'OR',
                            [
                                'key' => 'wp_3_access',
                                'value' => 'Y',
                                'compare' => '='
                            ],
                            [
                                'key' => 'wp_3_access',
                                'compare' => 'NOT EXISTS'
                            ]
                        ],
                        [
                            'key' => 'account_status',
                            'value' => 'active',
                            'compare' => '='
                        ],
                        [
                            'relation' => 'OR',
                            [
                                'key' => 'last_login',
                                'value' => $query_params['last_login'],
                                'compare' => '<',
                                'type' => 'DATE',
                            ],
                            [
                                'key' => 'last_login',
                                'compare' => 'NOT EXISTS',
                            ]
                        ]
                    ];
                    break;
            }

            $user_query = new WP_User_Query($args);
            $total_users = $user_query->get_total();

            // Display the results in the expected format for JavaScript to parse
            if ($total_users > 0) {
                echo '<div class="notice notice-info" style="padding:20px; border-left: 4px solid #2271b1; background: #f0f8ff;" data-total-users="' . esc_attr($total_users) . '">';
                echo '<strong>Found ' . $total_users . ' users for deletion</strong>';
                echo '</div>';
            } else {
                echo '<div class="notice notice-warning" style="padding:20px; border-left: 4px solid #ffb900; background: #fff8e5;">';
                echo '<strong>No users found matching the criteria</strong>';
                echo '</div>';
            }
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
                    <option value="option5" <?php selected($type, 'option5'); ?>>Target Archived Users</option>
                    <option value="option6" <?php selected($type, 'option6'); ?>>Target Incomplete Users | Unconfirmed + Registered Before</option>
                    <option value="option7" <?php selected($type, 'option7'); ?>>Target Incomplete Users</option>
                    <option value="option8" <?php selected($type, 'option8'); ?>>Active + Confirmed + WP3 Access + Registered Before</option>

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
                <?php var_dump($type); ?>
                <?php if ($type === 'option2') : ?>
                    <p>
                        <label for="last_login">Last Login Before:</label><br>
                        <input type="date" name="last_login" id="last_login" value="<?php echo esc_attr($last_login); ?>">
                    </p>
                    <p>
                        <label for="user_registered">User Registered Before:</label><br>
                        <input type="date" name="user_registered" id="user_registered" value="<?php echo esc_attr($user_registered); ?>">
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
                        Example Query: <code>last_login < '2018-03-01' </code> and <code>user_registered < '2017-03-01' </code>
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
            echo '<div class="notice notice-info" style="padding:20px; border-left: 4px solid #2271b1; background: #f0f8ff;" data-total-users="' . esc_attr($result['total']) . '">';
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
        (function() {
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
                    if (selected === 'option5') {
                        // For option2 and option5, only show last_login field
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

            form.addEventListener('submit', function(e) {
                // Handle type field visibility
                if (typeField.value === 'option4') {
                    lastLoginInput.removeAttribute('name');
                    registeredInput.removeAttribute('name');
                }

                // Handle delete process
                if (deleteResultsCheckbox.checked) {
                    e.preventDefault();

                    // First run the query to get results
                    const formData = new FormData(form);
                    formData.append('delete_results', 'false'); // Ensure we don't delete yet

                    console.log('Initial query form data:', Object.fromEntries(formData.entries()));
                    console.log('About to make fetch request to:', window.location.href);

                    // Show progress bar immediately
                    progressDiv.style.display = 'block';
                    progressStatus.textContent = 'Running query to get total users...';

                    try {
                        fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                console.log('Initial query response status:', response.status);
                                console.log('Initial query response ok:', response.ok);
                                console.log('Initial query response headers:', Object.fromEntries(response.headers.entries()));
                                return response.text();
                            })
                            .then(html => {
                                console.log('Initial query response HTML length:', html.length);
                                console.log('Initial query response HTML:', html.substring(0, 1000)); // First 1000 chars

                                // Create a temporary div to parse the response
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = html;

                                // Get the total users from the response
                                const noticeElement = tempDiv.querySelector('.notice-info');
                                console.log('Found notice element:', noticeElement);

                                if (noticeElement) {
                                    const totalUsers = parseInt(noticeElement.getAttribute('data-total-users')) || 0;
                                    console.log('Total users from data attribute:', totalUsers);

                                    if (totalUsers > 0) {
                                        totalUsersInput.value = totalUsers;
                                        console.log('Found total users:', totalUsers);
                                        processBatch(0, 0);
                                    } else {
                                        console.error('No users found to delete');
                                        progressStatus.textContent = 'Error: No users found to delete';
                                    }
                                } else {
                                    console.error('Could not find results in response');
                                    console.log('All elements in response:', tempDiv.querySelectorAll('*'));
                                    progressStatus.textContent = 'Error: Could not find results in response';
                                }
                            })
                            .catch(error => {
                                console.error('Error running query:', error);
                                console.error('Error details:', error);
                                progressStatus.textContent = 'Error: ' + error.message;
                            });
                    } catch (syncError) {
                        console.error('Synchronous error before fetch:', syncError);
                        progressStatus.textContent = 'Error: ' + syncError.message;
                    }
                }
            });

            function processBatch(offset, processed) {
                const totalUsers = parseInt(totalUsersInput.value);
                const batchSize = 100;

                // Only check if we've processed all users if we have a valid total
                if (totalUsers > 0 && processed >= totalUsers) {
                    progressStatus.textContent = 'Deletion completed!';
                    return;
                }

                const formData = new FormData(form);
                formData.append('delete_results', 'true');
                formData.append('offset', offset);
                formData.append('batch_size', batchSize);
                formData.append('action', 'process_user_batch');
                formData.append('nonce', document.querySelector('input[name="nonce"]').value);
                formData.append('type', typeField.value);
                formData.append('last_login', lastLoginInput.value || '');
                formData.append('user_registered', registeredInput.value || '');
                formData.append('whitelist_domains', document.querySelector('[name="whitelist_domains"]')?.value || '');
                formData.append('create_backup', document.querySelector('[name="create_backup"]')?.checked ? 'true' : 'false');

                console.log('Processing batch:', {
                    offset,
                    processed,
                    totalUsers,
                    batchSize,
                    formData: Object.fromEntries(formData.entries())
                });

                fetch(ajaxurl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
                        return response.text().then(text => {
                            console.log('Raw response:', text);
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('Failed to parse JSON:', e);
                                throw new Error('Invalid JSON response: ' + text);
                            }
                        });
                    })
                    .then(data => {
                        console.log('Parsed response:', data);
                        console.log('Response success:', data.success);
                        console.log('Response data:', data.data);
                        if (data.success) {
                            // Get the total deleted from the response
                            const totalDeleted = parseInt(data.data?.total_deleted || 0);
                            const newProcessed = processed + totalDeleted;


                            console.log('test');
                            console.log(totalDeleted);
                            console.log(newProcessed);


                            // Calculate progress based on the total users that will be deleted
                            const totalToDelete = totalUsers; // Use the actual total users, not batch_size
                            const progress = Math.min(100, Math.round((newProcessed / totalUsers) * 100));



                            console.log(totalToDelete);




                            console.log('Batch result:', {
                                totalDeleted,
                                newProcessed,
                                progress,
                                totalToDelete,
                                response: data
                            });

                            // Update UI
                            progressFill.style.width = `${progress}%`;
                            progressStatus.textContent = `Processing... ${newProcessed} of ${totalToDelete} users deleted (${progress}%)`;
                            deletedCount.textContent = newProcessed;

                            if (data.data?.continue) {
                                // Add a small delay between batches
                                setTimeout(() => {
                                    processBatch(data.data.next_offset, newProcessed);
                                }, 1000);
                            } else {
                                progressStatus.textContent = 'Deletion completed!';
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            }
                        } else {
                            console.error('Error in batch:', data);
                            progressStatus.textContent = 'Error: ' + (data.data?.message || data.message || 'Unknown error occurred');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        progressStatus.textContent = 'Error: ' + error.message;
                        // Retry after 5 seconds on error
                        setTimeout(() => {
                            processBatch(offset, processed);
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
