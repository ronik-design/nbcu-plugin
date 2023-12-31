<?php
use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twilio\Rest\Client;

// do_action('2fa-registration-page');
add_action('mfa-registration-page', function () {
    $options = new QROptions([
        'eccLevel' => QRCode::ECC_L,
        'outputType' => QRCode::OUTPUT_MARKUP_SVG,
        'version' => 7,
    ]);
    $google2fa = new Google2FA();
    // Lets generate the google2fa_secret key.
    $google2fa_secret = $google2fa->generateSecretKey();
    // Lets store important data inside the usermeta.
    $get_current_secret = get_user_meta(get_current_user_id(), 'google2fa_secret', true);
    $mfa_status = get_user_meta(get_current_user_id(),'mfa_status', true);
    $mfa_validation = get_user_meta(get_current_user_id(),'mfa_validation', true);
    $get_auth_lockout_counter = get_user_meta(get_current_user_id(), 'auth_lockout_counter', true);

    // Check if user has secret if not add secret.
    if (!$get_current_secret) {
        // add_user_meta(get_current_user_id(), 'google2fa_secret', $google2fa_secret);
        update_user_meta(get_current_user_id(), 'google2fa_secret', $google2fa_secret);
    }
    // Check if user has mfa_status if not add secret.
    if (!$mfa_status) {
        // add_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
        update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
    }
    // Check if user has mfa_validation if not add secret.
    if (!$mfa_validation) {
        // add_user_meta(get_current_user_id(), 'mfa_validation', 'not_registered');
        update_user_meta(get_current_user_id(), 'mfa_validation', 'not_registered');
    }

    // We put this in the header for fast redirect..
    $f_success = isset($_GET['mfa-success']) ? $_GET['mfa-success'] : false;
    $f_error = isset($_GET['mfa-error']) ? $_GET['mfa-error'] : false;
    $f_expired = '';
    if($f_error == 'nomatch'){
        $f_error = 'Sorry, the verification code entered is invalid.';
    }

    ?>
        <div class="dev-notice">
            <h4>Dev Message:</h4>
            <p>Current UserID: <?php echo get_current_user_id(); ?></p>
            <p>MFA Current Secret: <?php echo $get_current_secret; ?></p>
            <p>MFA Status: <?php echo $mfa_status; ?></p>
            <p>MFA Validation: <?php echo $mfa_validation; ?></p>
            <p>Auth Lockout: <?php echo  $get_auth_lockout_counter; ?></p>
            <form  action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                <input type="hidden" type="text" name="re-auth" value="RESET">
                <button type="submit" name="submit" aria-label="Change Authentication Selection." value="Change Authentication Selection.">Change Authentication Selection.</button>
            </form>
        </div>

    <?php

    // Check if mfa_status is not equal to verified.
    if ($mfa_status == 'mfa_unverified' && is_user_logged_in()) {
        // Get the User Object.
        $author_obj = get_user_by('id', get_current_user_id());
        // Lets create the QR as well.
        $g2faUrl = $google2fa->getQRCodeUrl(
            'NBCU', // Set to a default value
            $author_obj->user_email, // Set for specific email
            $get_current_secret // Lets use the $google2fa_secret we created earlier.
        );
        $qrcode = (new QRCode($options))->render($g2faUrl);

        if( isset($mfa_validation) && $mfa_validation == 'valid'){
            update_user_meta(get_current_user_id(), 'mfa_validation', 'invalid');
            // This is mostly for messaging purposes..
            wp_redirect( esc_url(home_url('/mfa?mfaredirect=expired')) );
            exit;
        ?>
            <p>Authorization has expired.</p>
            <div id="countdown"></div>
            <script>
                var timeleft = 5;
                var downloadTimer = setInterval(function(){
                    if(timeleft <= 0){
                        clearInterval(downloadTimer);
                        document.getElementById("countdown").innerHTML = "Reloading";
                        setTimeout(() => {
                            window.location = window.location.pathname + "?mfaredirect=expired";
                        }, 1000);
                    } else {
                    document.getElementById("countdown").innerHTML = "Page will auto reload in: " + timeleft + " seconds";
                    }
                        timeleft -= 1;
                }, 1000);
            </script>

        <?php } else { ?>

            <div class="auth-content-bottom">
                <?php if( !$mfa_validation || ($mfa_validation == 'not_registered' )  ){ ?>
                    <!-- We check if the get_current_secret is empty or false if so we reload the page.  -->
                    <?php if(!$get_current_secret){ ?>
                        <script>
                            location.reload();
                        </script>
                    <?php } ?>
                    <span class="qr-code-wrapper">
                        <img src='<?= $qrcode ?>' alt='QR Code' width='100' height='100'>
                        <p><?= $get_current_secret; ?></p>
                    </span>
                <?php } ?>
                <form class="auth-content-bottom__submit <?php if($f_error){ echo 'auth-content-bottom__submit_error'; } ?>" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                    <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                    <input required autocomplete="off" type="text" name="google2fa_code" placeholder="6 Digit Code" value="">
                    <input type="submit" name="submit" value="Submit">
                    <?php if($f_error){ ?>
                        <span class="message"><?= $f_error; ?></span>
                    <?php } ?>
                </form>
                <div class="auth-content-bottom__helper">
                    <p>If you encounter any issue, please reach out to the <a href="mailto:together@nbcuni.com?subject=MFA Registration Issue">together@nbcuni.com</a> for support. </p>
                </div>
            </div>

        <?php }?>
    <?php } else{
        $cookie_name = "ronik-auth-reset-redirect";
        if(isset($_COOKIE[$cookie_name])) {
            wp_redirect( esc_url(home_url(urldecode($_COOKIE[$cookie_name]))) );
            exit;
        } else {
            wp_redirect( esc_url(home_url()) );
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
                        window.location = window.location.pathname + "?mfaredirect=saved";
                    }, 1000);
                } else {
                    document.getElementById("countdown").innerHTML = "Page will reload in: " + timeleft + " seconds";
                }
                    timeleft -= 1;
            }, 1000);
        </script>
    <?php }
});
