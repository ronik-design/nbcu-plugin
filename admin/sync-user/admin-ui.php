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
    if (is_admin() && isset($_POST['page']) && $_POST['page'] === 'ronikdesigns-sync-user' && !empty($_POST['export'])) {
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
    $last_login = $_POST['last_login'] ?? '2018-03-01';
    $user_registered = $_POST['user_registered'] ?? '2017-03-01';
    $export = $_POST['export'] ?? '';
    $whitelist_domains = $_POST['whitelist_domains'] ?? get_option('options_whitelist_domains', '');
    $paged = isset($_POST['paged']) ? max(1, (int) $_POST['paged']) : 1;
    $per_page = 100;

    $handler = new UserSyncHandler();
    $result = ['total' => 0, 'results' => [], 'output' => ''];

    // Process the form submission for display (not export)
    if (is_admin() && isset($_POST['page']) && $_POST['page'] === 'ronikdesigns-sync-user' && empty($_POST['export'])) {
        $result = $handler->process_sync($_POST);
    }
?>
    <div class="wrap">
        <h1>User Sync Tool</h1>

        <form method="post" action="" id="sync-form">
            <input type="hidden" name="page" value="ronikdesigns-sync-user">
            <input type="hidden" name="paged" value="<?php echo esc_attr($paged); ?>">

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
            </div>

            <p>
                <label><input type="checkbox" name="export" value="true" <?php checked($export, 'true'); ?>> Export as CSV</label>
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

            function toggleDateFields() {
                const selected = typeField.value;
                const hideDates = (selected === 'option2' || selected === 'option4');

                if (hideDates) {
                    dateFields.style.display = 'none';
                    lastLoginInput.value = '';
                    registeredInput.value = '';
                } else {
                    dateFields.style.display = 'block';
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
            });
        })();
    </script>
<?php
}