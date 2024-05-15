<?php
use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twilio\Rest\Client;

add_action('2fa-registration-page', function () {
    // Helper Guide
    $helper = new RonikHelper;
    $authProcessor = new RonikAuthProcessor;

    // We put this in the header for fast redirect..
    $f_success = isset($_GET['sms-success']) ? $_GET['sms-success'] : false;
    $f_error = isset($_GET['sms-error']) ? $_GET['sms-error'] : false;
    $f_expired = '';
    if($f_error == 'nomatch'){
        $f_error = 'Sorry, the verification code entered is invalid.';
    }
    $get_registration_status = get_user_meta(get_current_user_id(),'sms_2fa_status', true);
    $sms_code_timestamp = get_user_meta(get_current_user_id(),'sms_code_timestamp', true);
    $f_mfa_settings = get_field('mfa_settings', 'options');

    $sms_2fa_status = get_user_meta(get_current_user_id(),'sms_2fa_status', true);
    $sms_2fa_secret = get_user_meta(get_current_user_id(),'sms_2fa_secret', true);
    $get_auth_lockout_counter = get_user_meta(get_current_user_id(), 'auth_lockout_counter', true);
    $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);


    if( isset($f_mfa_settings['sms_expiration_time']) || $f_mfa_settings['sms_expiration_time'] ){
        $f_sms_expiration_time = $f_mfa_settings['sms_expiration_time'];
    } else {
        $f_sms_expiration_time = 30;
    }
    // Check if sms_2fa_status is not equal to verified.
    if (($get_registration_status !== 'sms_2fa_unverified')) {
        $past_date = strtotime((new DateTime())->modify('-'.$f_sms_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
        // If past date is greater than current date. We reset to unverified & start the process all over again.
        if($past_date > $sms_code_timestamp ){
            $valid = false;
        } else {
            $valid = true;
        }
    } else {
        $valid = false;
    }
    // If Valid we redirect
    if ($valid) {
        $authProcessor->ronik_authorize_success_redirect_path();
    ?>
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
    } else { ?>
        <?php
        $sms_2fa_secret = get_user_meta(get_current_user_id(),'sms_2fa_secret', true);

        if (class_exists('RonikAuthHelper')) {
            $authHelper = new RonikAuthHelper;
            $authHelper->auth_admin_messages();
        }

        // Based on the session conditions we check if valid if not we default back to the send SMS button.
        if(  isset($sms_2fa_secret) && $sms_2fa_secret  && ($sms_2fa_secret !== 'invalid')  ){
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
                if (!$sms_code_timestamp) {
                    // add_user_meta(get_current_user_id(), 'sms_code_timestamp', $current_date);
                    update_user_meta(get_current_user_id(), 'sms_code_timestamp', $current_date);
                }
                if( $past_date > $sms_code_timestamp ){
                    $helper->ronikdesigns_write_log_devmode( 'SMS Expired' , 'low', 'auth_2fa');
                }
            ?>
            <div class="auth-content-bottom auth-content-bottom--sms">
                <form class="auth-content-bottom__submit <?php if($f_error){ echo 'auth-content-bottom__submit_error'; } ?>" action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>" method="post">
                    <div class="auth-content-bottom__submit-contents">
                    <div id="sms-expiration"></div>
                        <script>
                            console.log('Init Timevalidation');
                            smsExpiredChecker();
                            document.addEventListener("visibilitychange", (event) => {
                            if (document.visibilityState == "visible") {
                                console.log("tab is active");
                                // window.location.reload(true);
                            } else {
                                console.log("tab is inactive")
                            }
                            });
                            var timeleft = <?= ($f_sms_expiration_time*60); ?>;                            
                            var downloadTimer = setInterval(function(){
                                if(timeleft <= 0){
                                    clearInterval(downloadTimer);
                                    // document.getElementById("sms-expiration").innerHTML = "SMS is Expired.";
                                    setTimeout(() => {
                                        smsExpiredChecker();
                                        // window.location = window.location.pathname + "?sms-success=success";
                                    }, 1000);
                                } else {
                                    // document.getElementById("sms-expiration").innerHTML = "SMS Code will Expire in: " + timeleft + " seconds";
                                }
                                timeleft -= 1;
                            }, 1000);
                            function smsExpiredChecker(){
                                jQuery.ajax({
                                    type: 'post',
                                    url: wpVars.ajaxURL,
                                    data: {
                                        action: 'ronikdesigns_admin_auth_verification',
                                        smsExpired: true,
                                        nonce: wpVars.nonce,
                                        autoChecker: 'valid',
                                        crypt: '<?= $helper->ronik_encrypt_data_meta(get_current_user_id()); ?>'
                                    },
                                    dataType: 'json',
                                    success: data => {
                                        if(data.success){
                                            console.log('SMS Code Expired.');
                                            console.log(data);
                                            if(data.data !== 'noreload'){
                                                alert('The SMS Code Expired. Page will auto reload.');
                                                setTimeout(() => {
                                                    let url = window.location.href;
                                                    if (url.indexOf('?') > -1){
                                                        url += '&sms-error=expired'
                                                    } else {
                                                        url += '?sms-error=expired'
                                                    }
                                                    window.location.href = url;
                                                    // window.location.reload(true);
                                                }, 500);
                                            }
                                        } else{
                                            console.log('error');
                                            console.log(data);
                                            alert('Whoops! Something went wrong! Please try again later!');
                                            setTimeout(() => {
                                                // window.location.reload(true);
                                                let url = window.location.href;
                                                if (url.indexOf('?') > -1){
                                                    url += '&sms-error=error'
                                                } else {
                                                    url += '?sms-error=error'
                                                }
                                                window.location.href = url;
                                            }, 500);
                                        }
                                    },
                                    error: err => {
                                        console.log(err);
                                        alert('Whoops! Something went wrong! Please try again later!');
                                        // Lets Reload.
                                        setTimeout(() => {
                                            let url = window.location.href;
                                                if (url.indexOf('?') > -1){
                                                    url += '&sms-error=error'
                                                } else {
                                                    url += '?sms-error=error'
                                                }
                                                window.location.href = url;
                                        }, 500);
                                    }
                                });
                            }
                        </script>
                        <input style="padding-left: 12px;" type="text" id="validate-sms-code" name="validate-sms-code" type="number" minlength="6" maxlength="6" placeholder="6 Digit Code" autocomplete="off" required>
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field( 'ajax-nonce', 'nonce' ); ?>
                        <?php if($f_error){ ?>
                            <span class="message"><?= $f_error; ?></span>
                        <?php } ?>
                    </div>
                    <button type="submit" value="Reset Password">Submit SMS Code</button>
                </form>
                <div class="auth-content-bottom__helper">
                    <p>If you don't receive a text message, please reach out to the  <a href="mailto:together@nbcuni.com?subject=2fa Registration Issue">together@nbcuni.com</a> for support. </p>
                    <a style="display:inline-block; padding-top: 10px;" href="<?= admin_url('admin-ajax.php').'?action=ronikdesigns_admin_logout'; ?>"> Authenticate via NBCU SSO</a>
                </div>
            </div>
        <?php } else{ ?>
            <div class="auth-content-bottom auth-content-bottom--sms">
                <form class="auth-content-bottom__submit" action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>" method="post">
                    <input type="hidden" name="send-sms" value="send-sms">
                    <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                    <?php wp_nonce_field( 'ajax-nonce', 'nonce' ); ?>
                    <span class="button-wrapper" style="margin-top: 0;">
                        <button type="submit" value="Send SMS Code">Send SMS Code</button>
                        <a href="<?= admin_url('admin-ajax.php').'?action=ronikdesigns_admin_logout'; ?>"> Authenticate via NBCU SSO</a>
                    </span>
                </form>
            </div>
        <?php }
    } ?>
    <?php
});
