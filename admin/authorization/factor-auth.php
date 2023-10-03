<?php
use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twilio\Rest\Client;
// Cleaning up the session variables.
		// temp session_start();
        // session_start();

add_action('auth-rest', function ($args) { ?>
    <form class="registeration-mfa-reset <?= $args['class']; ?>" style="<?= $args['style']; ?>" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
        <h2>MFA Registeration Reset</h2>
        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
        <input type="hidden" type="text" name="re-auth" value="RESET">
        <button type="submit" name="submit" aria-label="Change Authentication Selection." value="Change Authentication Selection.">Change Authentication Selection.</button>
    </form>
<?php });

add_action('auth-registration-page', function () {
    $get_auth_status = get_user_meta(get_current_user_id(),'auth_status', true);
    $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);

        $mfa_status = get_user_meta(get_current_user_id(),'mfa_status', true);
        $mfa_validation = get_user_meta(get_current_user_id(),'mfa_validation', true);
        $get_auth_lockout_counter = get_user_meta(get_current_user_id(), 'auth_lockout_counter', true);

    ?>

        <div class="dev-notice">
            <h4>Dev Message:</h4>
            <p>Current UserID: <?php echo get_current_user_id(); ?></p>
            <p>Auth Status: <?php echo $get_auth_status; ?></p>
            <p>MFA Status: <?php echo $mfa_status; ?></p>
            <p>MFA Validation: <?php echo $mfa_validation; ?></p>
            <p>Auth Lockout: <?php echo  $get_auth_lockout_counter; ?></p>
            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                <input type="hidden" type="text" name="re-auth" value="RESET">
                <button type="submit" name="submit" aria-label="Change Authentication Selection." value="Change Authentication Selection.">Change Authentication Selection.</button>
            </form>
        </div>
    <?php 
    // Check if auth_status is not equal to none or empty.
    if (($get_auth_status !== 'none') && !empty($get_auth_status)) {
        $valid = true;
    } else {
        $valid = false;
    }
    // Check if auth_status is auth_select_sms-missing.
    if( $get_auth_status == 'auth_select_sms-missing' ){
        $valid = false;
    }
    // If Valid we redirect
    if ($valid) {
        $f_success = isset($_GET['sms-success']) ? $_GET['sms-success'] : false;
        $f_success = isset($_GET['auth-phone_number']) ? $_GET['auth-phone_number'] : false;
        // Authorization Saved!
            // Success message
            if($f_success){
                // This is mostly for messaging purposes..
                wp_redirect( esc_url(home_url('/2fa?sms-valid=saved')) );
                exit;
            }
            if($get_auth_status == 'auth_select_sms'){
                // This is mostly for messaging purposes..
                wp_redirect( esc_url(home_url('/2fa?sms-valid=saved')) );
                exit;
            }
            if($get_auth_status == 'auth_select_mfa'){
                // This is mostly for messaging purposes..
                wp_redirect( esc_url(home_url('/mfa?mfaredirect=saved')) );
                exit;
            }
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
    } else {
        // Check the $get_auth_status if sms-missing is set.
        if( $get_auth_status == 'auth_select_sms-missing' ){ ?>
            <script>
                $(document).ready(function() {
                    $("#auth-phone_number").on('keyup', function() {
                        var phone = $(this).val().replace(/\D/g, '');
                        phone = phone.replace(/^1/, '');
                        phone = phone.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
                        $(this).val(phone);
                    });
                });
            </script>
            <div class="auth-content-bottom auth-content-bottom--sms">
                <form class="auth-content-bottom__submit" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                    <div class="auth-content-bottom__submit-contents">
                        <input type="tel" id="auth-phone_number" name="auth-phone_number" placeholder="234-567-8901" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required>
                        <small>Format: 234-567-8901</small>
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                    </div>
                    <button type="submit" value="Send SMS Code">Submit</button>
                </form>

            </div>
        <?php } else { ?>
            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                <p>Please select the type of authentication:</p>
                <span>
                    <input type="radio" id="mfa" name="auth-select" value="mfa" checked="checked">
                    <label for="mfa">Authenticate with authenticator app (recommended)</label>
                </span>
                <span>
                    <input type="radio" id="2fa" name="auth-select" value="2fa">
                    <label for="2fa">Authenticate with a code received to SMS</label>
                </span>
                <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                <button type="submit" value="Submit">Submit</button>
            </form>
        <?php }
    }
});
