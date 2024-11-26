<?php 
if( $rk_bypasser_e_demo_mode == 'valid' && $rk_bypasser_which_environment !== 'live' ){
    return false;
}
// Fail safe checking for local 
if (!str_contains($_SERVER['HTTP_HOST'], '.local')) {
    // Activate SSO DEMO support
        // http://together.nbcudev.local/home/?sso-miniorange=true
    // deactivate SSO DEMO support
        // http://together.nbcudev.local/home/?sso-miniorange=false

    // Cookie name
    $cookie_name = "sso_miniorange";
    // Check if the 'sso-miniorange' parameter exists in the URL
    if (isset($_GET['sso-miniorange'])) {
        // Get the value of the parameter
        $param_value = $_GET['sso-miniorange'];
        if ($param_value === 'true') {
            // Set the cookie to expire in 20 minutes (1200 seconds)
            $cookie_expiration = time() + 1200; // 1200 seconds = 20 minutes
            // Set the cookie
            setcookie($cookie_name, "true", $cookie_expiration, "/");
            // Optionally uncomment for debugging
            // echo "Cookie '$cookie_name' has been set for 20 minutes.";
        } elseif ($param_value === 'false') {
            // Deactivate and remove the cookie by setting the expiration date in the past
            setcookie($cookie_name, "", time() - 3600, "/");
            // Optionally uncomment for debugging
            // echo "Cookie '$cookie_name' has been removed.";
        }
    }
    // To check if the cookie is set
    if (isset($_COOKIE[$cookie_name])) {
        // Optionally uncomment for debugging
        // echo "Cookie '$cookie_name' is set with value: " . $_COOKIE[$cookie_name];
    } else {
        // Cookie is not set or has been removed
        // Optionally uncomment for debugging
        // echo "Cookie '$cookie_name' is not set.";
        return false;
    }
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