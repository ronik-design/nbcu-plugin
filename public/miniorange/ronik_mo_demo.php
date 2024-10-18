<?php 
if( MO_DEMO !== 'valid' ) {
    return false;
}
// Fail safe checking for local 
if (!str_contains($_SERVER['HTTP_HOST'], '.local')) {
    return false;
}


// Function to sanitize all GET parameters dynamically
function sanitize_all_get_params($filter = FILTER_SANITIZE_STRING) {
    $sanitized_params = [];
    foreach ($_GET as $key => $value) {
        $sanitized_params[$key] = filter_var($value, $filter);
    }
    return $sanitized_params;
}


add_action('wp_head','my_added_login_field');
function my_added_login_field(){ 
// Get sanitized GET parameters
$sanitized_get_params = sanitize_all_get_params();
// Encode the sanitized parameters to a JSON string without escaping
$json_get_params = json_encode($sanitized_get_params, JSON_UNESCAPED_UNICODE);
?>
    <div class=""  style="position: absolute;z-index: 11111111;">
        <form id="ajax-sso-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
            <input type="hidden" value="valid" id="auth-sso" name="auth-sso-login">
            <input type="hidden" value="<?= htmlspecialchars($json_get_params, ENT_QUOTES, 'UTF-8'); ?>" id="auth-sso-get" name="auth-sso-get">

            <input type="hidden" name="action" value="ronikdesign_miniorange_ajax">
            <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
            <button type="submit" value="Send SSO Login">Login</button>
        </form>


        <form id="ajax-sso-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
            <input type="hidden" value="valid" id="auth-sso-logout" name="auth-sso-logout">
            <input type="hidden" name="action" value="ronikdesign_miniorange_ajax">
            <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
            <button type="submit" value="Send SSO Logout">Logout</button>
        </form>
    </div>


    <script>
    jQuery(document).ready(function($) {
        
        $('#ajax-sso-form').on('submit', function(e) {
            e.preventDefault();

            var formData = $(this).serialize();

            $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', formData, function(response) {                
                if (response.success && response.data.redirect) {
                    window.location.href = response.data.redirect; // Redirect in the browser
                } else {

                    console.log(response.data);


                    alert(response.data); // Handle error message
                }
            });
        });
    });
    </script>
    <?php
}