<?php
use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twilio\Rest\Client;

add_action('auth_user-lockout', function ($args) { 
    if (class_exists('RonikAuthHelper')) {
        $authHelper = new RonikAuthHelper;
        $authHelper->auth_admin_messages();
    }    
?>
    <div class="mfa-content">
        <h2>Authentication failed too many times.</h2>
        <div class="instructions">
            <!-- <h4>Account is locked out for 3 minutes. Please try again later.</h4> -->
            <h4>Your account is locked.</h4>
        </div>
        <div class="auth-content-bottom">
            <div class="auth-content-bottom__helper" style="padding: 0;">
                <p>Please reach out to <a href="mailto:together@nbcuni.com?subject=Account Locked Out">together@nbcuni.com</a> for support. </p>
            </div>
        </div>
    </div>
<?php 
});

add_action('auth-rest', function ($args) {
    $f_auth = get_field('mfa_settings', 'options');
    if( ( isset($f_auth['enable_2fa_settings']) && $f_auth['enable_2fa_settings'] ) && ( isset($f_auth['enable_mfa_settings']) && $f_auth['enable_mfa_settings'] ) ){ ?>
        <form class="registeration-mfa-reset <?= $args['class']; ?>" style="<?= $args['style']; ?>" action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>" method="post">
            <h2>MFA Registration Reset</h2>
            <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
            <?php wp_nonce_field( 'ajax-nonce', 'nonce' ); ?>
            <input type="hidden" type="text" name="re-auth" value="RESET">
            <button type="submit" name="submit" aria-label="Change Authentication Selection." value="Change Authentication Selection.">Change Authentication Selection.</button>
        </form>
    <?php }
});

add_action('auth-registration-page', function () {
    $get_auth_status = get_user_meta(get_current_user_id(),'auth_status', true);

    if (class_exists('RonikAuthHelper')) {
        $authHelper = new RonikAuthHelper;
        $authHelper->auth_admin_messages();
    }

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
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css"/>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
            <div class="auth-content-bottom auth-content-bottom--sms">
                <form class="auth-content-bottom__submit" action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>" method="post"  onkeyup="process(event)">
                    <div class="auth-content-bottom__submit-contents">
                        <input type="tel" id="auth-phone_number" name="auth-phone_number" required>
                        <small>Format: 234-567-8901</small>
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field( 'ajax-nonce', 'nonce' ); ?>
                    </div>
                    <button disabled class="btn-disabled" id="submit-tel" type="submit" value="Send SMS Code">Submit</button>
                </form>
                <div class="alert alert-info" style="display: none;"></div>
            </div>
            <script>
                const phoneInputField = document.querySelector("#auth-phone_number");
                const phoneInput = window.intlTelInput(phoneInputField, {
                    utilsScript:
                    "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                });
                const info = document.querySelector(".alert-info");
                function process(event) {
                    const inputTarget = $(event.currentTarget).find("#auth-phone_number");
                    const countryData = phoneInput.getSelectedCountryData();
                    const isValid = phoneInput.isValidNumber();
                    if(isValid){
                        $("#submit-tel").removeClass('btn-disabled');
                        $("#submit-tel").prop('disabled', false);
                    } else {
                        $("#submit-tel").addClass('btn-disabled');
                        $("#submit-tel").prop('disabled', true);
                    }
                    if( countryData.iso2 == 'us' ){
                        var phone = inputTarget.val();
                        phone = phone.replace(/^1/, '');
                        phone = phone.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
                        inputTarget.val(phone);
                    }
                }
                $('.auth-content-bottom__submit').on('submit',function(event){
                    // block form submit event
                    event.preventDefault();
                    // Assign country code value to input field val
                    const phoneNumber = phoneInput.getNumber();
                    $("#auth-phone_number").val(phoneNumber);
                    // Micro pause just incase...
                    setTimeout(function(){
                        // Continue the form submit
                        event.currentTarget.submit();
                    }, 50);
                });
            </script>
        <?php } else { ?>
            <form action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>" method="post">
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
                <?php wp_nonce_field( 'ajax-nonce', 'nonce' ); ?>
                <span class="button-wrapper">
                    <button type="submit" value="Submit">Submit</button>
                    <a href="<?= admin_url('admin-ajax.php').'?action=ronikdesigns_admin_logout'; ?>"> Authenticate via NBCU SSO</a>
                </span>
            </form>
        <?php }
    }
});
