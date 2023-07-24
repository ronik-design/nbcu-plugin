<?php
use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twilio\Rest\Client;
// Cleaning up the session variables.
session_start();



add_action('2fa-registration-page', function () {    
        $get_registration_status = get_user_meta(get_current_user_id(),'sms_2fa_status', true);
        $f_mfa_settings = get_field('mfa_settings', 'options');
        if( isset($f_mfa_settings['sms_expiration_time']) || $f_mfa_settings['sms_expiration_time'] ){
            $f_sms_expiration_time = $f_mfa_settings['sms_expiration_time'];
        } else {
            $f_sms_expiration_time = 30;
        }
        // Check if sms_2fa_status is not equal to verified.
        if (($get_registration_status !== 'sms_2fa_unverified')) {
            $past_date = strtotime((new DateTime())->modify('-'.$f_sms_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
            // If past date is greater than current date. We reset to unverified & start the process all over again.
            if($past_date > $get_registration_status ){
                $valid = false;
            } else {
                $valid = true;
            }
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
        } else { ?>
            <p><?= $get_registration_status; ?></p>
            <?php
            // Based on the session conditions we check if valid if not we default back to the send SMS button.
            if( isset($_SESSION["send-sms"]) && $_SESSION["send-sms"] == 'valid'){ 
                $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);
                $get_phone_number = substr($get_phone_number, -4);
                // Update the status with timestamp.
                // Keep in mind all timestamp are within the UTC timezone. For constant all around.
                // https://www.timestamp-converter.com/
                // Get the current time.
                $current_date = strtotime((new DateTime())->format( 'd-m-Y H:i:s' ));
                $f_mfa_settings = get_field('mfa_settings', 'options');

                $f_expiration_time = $f_mfa_settings['sms_expiration_time'];
                if($f_expiration_time){
                    $f_sms_expiration_time = $f_expiration_time;
                } else{
                    $f_sms_expiration_time = 10;
                }
                $past_date = strtotime((new DateTime())->modify('-'.$f_sms_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                // Lets store the sms code timestamp in user meta.
                $sms_code_timestamp = get_user_meta(get_current_user_id(),'sms_code_timestamp', true);
                // Lets also store within the session.
                $_SESSION["sms-code-timestamp"] = $current_date;
                if (!$sms_code_timestamp) {
                    add_user_meta(get_current_user_id(), 'sms_code_timestamp', $current_date);
                }
                if( $past_date > $sms_code_timestamp ){
                    error_log(print_r( 'Expired', true));
                    update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                    //Lets wipe out the session for sms_2fa_status
                    unset($_SESSION['send-sms']);
                    unset($_SESSION['sms-valid']);
                    unset($_SESSION["sms-code-timestamp"]);
                    // update_user_meta(get_current_user_id(), 'sms_code_timestamp', time());
                }            
            ?>

                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                    <p>Last 4 digits of phone number associated with the account:</br> <?= 'xxx-xxx-'.$get_phone_number; ?></p>

                    <div id="sms-expiration"></div>
                    <script>

                        document.addEventListener("visibilitychange", (event) => {
                        if (document.visibilityState == "visible") {
                            console.log("tab is active");
                            window.location.reload(true);
                        } else {
                            console.log("tab is inactive")
                        }
                        });




                        var timeleft = <?= ($f_sms_expiration_time*60); ?>;
                        var downloadTimer = setInterval(function(){
                            if(timeleft <= 0){
                                clearInterval(downloadTimer);
                                document.getElementById("sms-expiration").innerHTML = "SMS is Expired.";

                                setTimeout(() => {
                                    jQuery.ajax({
                                        type: 'post',
                                        url: '/wp-admin/admin-post.php',
                                        data: {
                                            action: 'ronikdesigns_admin_auth_verification',
                                            smsExpired: true,
                                            // nonce: wpVars.nonce,
                                        },
                                        dataType: 'json',
                                        success: data => {
                                            if(data.success){
                                                console.log('SMS Code Expired.');
                                                console.log(data);
                                                alert('The SMS Code Expired. Page will auto reload.');
                                                setTimeout(() => {
                                                    window.location.reload(true);
                                                }, 500);
                                            } else{
                                                console.log('error');
                                                console.log(data);
                                                alert('Whoops! Something went wrong! Please try again later!');
                                                setTimeout(() => {
                                                    window.location.reload(true);
                                                }, 500);
                                            }
                                        },
                                        error: err => {
                                            console.log(err);
                                            alert('Whoops! Something went wrong! Please try again later!');
                                            // Lets Reload.
                                            setTimeout(() => {
                                                window.location.reload(true);
                                            }, 500);
                                        }
                                    });
                                    // window.location = window.location.pathname + "?sms-success=success";
                                }, 1000);
                            } else {
                                document.getElementById("sms-expiration").innerHTML = "SMS Code will Expire in: " + Math.floor(timeleft/60 ) + " minutes";
                            }
                            timeleft -= 1;
                        }, 1000);
                    </script>


                    <input type="text" name="send-sms-code" value="" required>
                    <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                    <p>If you don't receive a text message, please reach out to the <a href="mailto:together@nbcuni.com?subject=2fa SMS Issue">together@nbcuni.com</a> for support. </p>
                    <button type="submit" value="Reset Password">Submit SMS Code</button>
                </form>
            <?php } else{ ?>
                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                    <input type="hidden" name="send-sms" value="send-sms">
                    <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                    <button type="submit" value="Send SMS Code">Send SMS Code</button>
                </form>
            <?php }
        }
    });



// This function block is responsible for detecting the time expiration of the 2fa on page specific pages.
function ronikdesigns_redirect_registered_2fa() {
    $get_registration_status = get_user_meta(get_current_user_id(),'sms_2fa_status', true);
    $f_mfa_settings = get_field('mfa_settings', 'options');
    // if( isset($f_mfa_settings['sms_expiration_time']) || $f_mfa_settings['sms_expiration_time'] ){
    //     $f_sms_expiration_time = $f_mfa_settings['sms_expiration_time'];
    // } else {
    //     $f_sms_expiration_time = 30;
    // }
    if( isset($f_mfa_settings['auth_expiration_time']) || $f_mfa_settings['auth_expiration_time'] ){
        $f_auth_expiration_time = $f_mfa_settings['auth_expiration_time'];
    } else {
        $f_auth_expiration_time = 30;
    }

    $f_auth = get_field('mfa_settings', 'options');
    // Redirect Magic, custom function to prevent an infinite loop.
    $dataUrl['reUrl'] = array('/wp-admin/admin-post.php', '/2fa/');
    $dataUrl['reDest'] = '/2fa/';
    if($f_auth['auth_page_enabled']){
        foreach($f_auth['auth_page_enabled'] as $auth_page_enabled){
            // We check the current page id and also the page title of the 2fa.
            if(($auth_page_enabled['page_selection'][0] == get_the_ID()) || ronikdesigns_get_page_by_title('2fa')){
                // Check if user has sms_2fa_status if not add secret.
                if (!$get_registration_status) {
                    add_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                }
                // Check if sms_2fa_status is not equal to unverified.
                if (($get_registration_status !== 'sms_2fa_unverified')) {
                    $past_date = strtotime((new DateTime())->modify('-'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                    // If past date is greater than current date. We reset to unverified & start the process all over again.
                    if($past_date > $get_registration_status ){
                        unset($_SESSION['send-sms']);
                        unset($_SESSION['sms-valid']);
                        session_destroy();
                        update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                        // Takes care of the redirection logic
                        ronikRedirectLoopApproval($dataUrl, "ronik-2fa-reset-redirect");
                    } else {
                        if (str_contains($_SERVER['REQUEST_URI'], '/2fa/')) {
                        // if($_SERVER['REQUEST_URI'] == '/2fa/'){
                            // Lets block the user from accessing the 2fa if already authenticated.
                            $dataUrl['reUrl'] = array('/');
                            $dataUrl['reDest'] = '/';
                            ronikRedirectLoopApproval($dataUrl, "ronik-2fa-reset-redirect");
                        }
                    }
                } else {
                    error_log(print_r( $get_registration_status, true));
                    update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                    // Takes care of the redirection logic
                    ronikRedirectLoopApproval($dataUrl, "ronik-2fa-reset-redirect");
                }
            }
        }
    }
}