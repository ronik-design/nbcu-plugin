<?php
use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twilio\Rest\Client;
// Cleaning up the session variables.
session_start();


add_action('auth-registration-page', function () {    
    $get_auth_status = get_user_meta(get_current_user_id(),'auth_status', true);
    // Check if auth_status is not equal to none or empty.
    if (($get_auth_status !== 'none') && !empty($get_auth_status) ) {
        $valid = true;
    } else {
        $valid = false;
    }
        // If Valid we redirect
        if ($valid) { ?>
            <div class="">Authorization Saved!</div>
            <div id="countdown"></div>
            <script>
                var timeleft = 5;
                var downloadTimer = setInterval(function(){
                    if(timeleft <= 0){
                        clearInterval(downloadTimer);
                        document.getElementById("countdown").innerHTML = "Reloading";
                        setTimeout(() => {
                            window.location = window.location.pathname + "?sms-success=success";
                        }, 1000);
                    } else {
                        document.getElementById("countdown").innerHTML = "Page will reload in: " + timeleft + " seconds";
                    }
                    timeleft -= 1;
                }, 1000);
            </script>
            <?php
            $f_success = isset($_GET['sms-success']) ? $_GET['sms-success'] : false;
            // Success message
            if($f_success){
                // Lets Check for the password reset url cookie.
                $cookie_name = "ronik-2fa-reset-redirect";
                if(isset($_COOKIE[$cookie_name])) {
                    wp_redirect( esc_url(home_url(urldecode($_COOKIE[$cookie_name]))) );
                    exit;
                } else {
                    // We run our backup plan for redirecting back to previous page.
                    // The downside this wont account for pages that were clicked during the redirect. So it will get the page that was previously visited.
                    add_action('wp_footer', 'ronikdesigns_redirect_js');
                    function ronikdesigns_redirect_js(){ ?>
                        <script type="text/javascript">
                            var x = JSON.parse(window.localStorage.getItem("ronik-url-reset"));
                            window.location.replace(x.redirect);
                        </script>
                    <?php };
                }
            }
        } else {
            // Based on the session conditions we check if valid if not we default back to the send SMS button.
            if( isset($_SESSION["auth-select"]) && $_SESSION["auth-select"] == '2fa'){
                // Get user phone number.
                $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);
                if($get_phone_number ){
                    update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_sms');
                } else { ?>
                    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                        <p>Please enter a phone number capable of receiving text messages:</p>
                        <label for="auth-phone_number">Phone Number:</label>
                        <input type="text" id="auth-phone_number" name="auth-phone_number" required><br><br>
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <button type="submit" value="Send SMS Code">Submit phone number.</button>
                    </form>
                <?php } 
            } else { ?>
                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">      
                    <p>Please Select the type of authentication:</p>
                    <input type="radio" id="mfa" name="auth-select" value="mfa" checked="checked">
                    <label for="mfa">MFA</label><br><br>
                    <input type="radio" id="2fa" name="auth-select" value="2fa">
                    <label for="2fa">2fa</label><br>  
                    <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                    <input type="submit" value="Submit">
                </form>
            <?php
            }
        }
    });