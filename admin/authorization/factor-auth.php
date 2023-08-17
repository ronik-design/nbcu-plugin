<?php
use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twilio\Rest\Client;
// Cleaning up the session variables.
		// temp session_start();
        // session_start();


add_action('auth-registration-page', function () {
    $get_auth_status = get_user_meta(get_current_user_id(),'auth_status', true);
    $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);

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
        // Success message
        if($f_success){
            // This is mostly for messaging purposes..
            wp_redirect( esc_url(home_url('/2fa?2faredirect=saved')) );
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
            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                <p>Please enter a valid phone number to receive authentication codes by text message:</p><br>
                <label for="auth-phone_number">Phone Number:</label>
                <input type="text" id="auth-phone_number" name="auth-phone_number" required><br><br>
                <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                <button type="submit" value="Send SMS Code">Submit phone number.</button>
            </form>
            <br><br>
            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                <input type="hidden" type="text" name="re-auth" value="RESET">
                <input type="submit" name="submit" value="Change Authentication Selection.">
            </form>
        <?php } else { ?>
            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                <p>Please select the type of authentication:</p><br>
                <input type="radio" id="mfa" name="auth-select" value="mfa" checked="checked">
                <label for="mfa">Authenticate with authenticator app (recommended)</label><br>
                <input type="radio" id="2fa" name="auth-select" value="2fa">
                <label for="2fa">Authenticate with a code received to SMS</label><br>
                <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                <button type="submit" value="Submit">Submit</button>
            </form>
        <?php }
    }
});
