<?php
use PragmaRX\Google2FA\Google2FA;








return;









// Add our custom logic to the wp-admin login page.
// Add additional login field
function ronikdesigns_login_field_auth(){ 
    $f_auth = get_field('mfa_settings', 'options');
    // Check for 2fa Enabled.
    if(isset($f_auth['enable_2fa_settings']) || $f_auth['enable_2fa_settings']){ ?>
        <br>
        <p>
            <label for="sms_code">SMS Code 
                <br>
                <sup>**If you have not registered yet. Please leave empty!</sup>
                <br>
                <input type="text" tabindex="20" size="20" value="" class="input" id="sms_code" name="sms_code">
            </label>
        </p>
        <?php
    }

    // Check for MFA Enabled.
    if(isset($f_auth['enable_mfa_settings']) || $f_auth['enable_mfa_settings']){ ?>
        <br>
        <p>
            <label for="google2fa_code">Google 2fa Code 
                <br>
                <sup>**If you have not registered yet. Please leave empty!</sup>
                <br>
                <input type="text" tabindex="20" size="20" value="" class="input" id="google2fa_code" name="google2fa_code">
            </label>
        </p>
    <?php
    }
}
add_action('login_form', 'ronikdesigns_login_field_auth');

// Add logic for auth field
function ronikdesigns_custom_authenticate_auth($user, $username, $password){
    $google2fa = new Google2FA();
    //Get POSTED value
    if(isset($_POST['google2fa_code'])){
        $login_google2fa = $_POST['google2fa_code'];
        //Get user object
        $user = get_user_by('login', $username);
        //Get stored value
        $get_current_secret = get_user_meta($user->ID, 'google2fa_secret', true);
        $mfa_status = get_user_meta($user->ID, 'mfa_status', true);
    } else{
        $login_google2fa = false;
        $get_current_secret = false;
    }
    // If stored value is empty, we proceed with standard login.
    if (!$get_current_secret || $mfa_status == 'mfa_unverified') {
        return null;
    }
    // Lets check for the following: If user comes back false
    if (!$user) {
        //User note found, or no value entered or doesn't match stored value - don't proceed.
        remove_action('authenticate', 'wp_authenticate_username_password', 20);
        remove_action('authenticate', 'wp_authenticate_email_password', 20);
        //Create an error to return to user
        return new WP_Error('denied', __("<strong>ERROR</strong>: Sorry incorrect credentials."));
    }
    // Check if verifed.
    $valid = $google2fa->verifyKey($get_current_secret, $login_google2fa);
    if ($valid) {
        //Make sure you return null
        return null;
    } else {
        //User note found, or no value entered or doesn't match stored value - don't proceed.
        remove_action('authenticate', 'wp_authenticate_username_password', 20);
        remove_action('authenticate', 'wp_authenticate_email_password', 20);
        //Create an error to return to user
        return new WP_Error('denied', __("<strong>ERROR</strong>: You're unique identifier was invalid."));
    }
}
add_filter('authenticate', 'ronikdesigns_custom_authenticate_auth', 10, 3);