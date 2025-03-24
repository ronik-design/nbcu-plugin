<?php
class RonikAuthHelper
{
    /**
     * This helps developers to debug on the fly and to make sure auth is working correctly!
     */
    public function auth_admin_messages()
    {
        // https://together.nbcudev.local/auth?ronik_debug=ronik_admin_auth
        if (isset($_GET['ronik_debug']) && $_GET['ronik_debug'] == 'ronik_admin_auth') {
            if (is_user_admin() || is_super_admin()) {
                setcookie("RonikDebug", 'ronik_admin_auth_717', time() + 1500);  /* expire in 25 min */
                // https://together.nbcudev.local/auth?ronik_debug=ronik_admin_auth&user_id=33
                if (isset($_GET['user_id'])) {
                    setcookie("RonikDebugUserID", $_GET['user_id'], time() + 1500);  /* expire in 25 min */
                    setcookie("RonikDebugUserEmail", '', time() - 3600);  // Expire the cookie by setting it in the past
                    // https://together.nbcudev.local/auth?ronik_debug=ronik_admin_auth&user_email=david@ronikdesign.com    
                } elseif (isset($_GET['user_email'])) {
                    setcookie("RonikDebugUserEmail", $_GET['user_email'], time() + 1500);  /* expire in 25 min */
                    setcookie("RonikDebugUserID", '', time() - 3600);  // Expire the cookie by setting it in the past
                } else {
                    setcookie("RonikDebugUserID", get_current_user_id(), time() + 1500);  /* expire in 25 min */
                    setcookie("RonikDebugUserEmail", '', time() - 3600);  // Expire the cookie by setting it in the past
                }
            }
        }

        // CRITICAL this prevents non super_admin from seeing the auth admin settings
        if (!is_super_admin()) {
            return false;
        }

        if ((isset($_COOKIE['RonikDebug']) && array_key_exists('RonikDebug', $_COOKIE) && $_COOKIE['RonikDebug'] == 'ronik_admin_auth_717') || str_contains($_SERVER['SERVER_NAME'], 'together.nbcudev.local-de')) {
            $current_user = wp_get_current_user();

            if (isset($_COOKIE['RonikDebugUserID']) && array_key_exists('RonikDebugUserID', $_COOKIE)) {
                $f_user_id = is_numeric($_COOKIE['RonikDebugUserID']) ? $_COOKIE['RonikDebugUserID'] : $current_user->ID;
            } elseif (isset($_COOKIE['RonikDebugUserEmail']) && array_key_exists('RonikDebugUserEmail', $_COOKIE)) {
                $user = get_user_by('email', $_COOKIE['RonikDebugUserEmail']);
                $f_user_id = ($user) ? $user->ID : $current_user->ID;
            } else {
                $f_user_id = $current_user->ID;
            }

            // All auth data for person.
            $get_auth_status = get_user_meta($f_user_id, 'auth_status', true);
            $get_current_secret = get_user_meta($f_user_id, 'google2fa_secret', true);
            $sms_2fa_status = get_user_meta($f_user_id, 'sms_2fa_status', true);
            $sms_2fa_secret = get_user_meta($f_user_id, 'sms_2fa_secret', true);
            $get_auth_lockout_counter = get_user_meta($f_user_id, 'auth_lockout_counter', true) ? get_user_meta($f_user_id, 'auth_lockout_counter', true) : (int)0;
            $get_phone_number = get_user_meta($f_user_id, 'sms_user_phone', true);
            $mfa_status = get_user_meta($f_user_id, 'mfa_status', true);
            $mfa_validation = get_user_meta($f_user_id, 'mfa_validation', true);

            $user = get_user_by('id', $f_user_id);
            $f_user_override = get_option('options_mfa_settings_user_override');

            error_log(print_r('DEBUG ACTIVATED', true));
            $f_auth = get_field('mfa_settings', 'options');
?>
            <div class="dev-notice">
                <h4>Dev Message:</h4>
                <p>
                    Welcome to the Frontend Dev Reset Center. This is a powerful tool that can reset and change all AUTH information.
                </p>
                <br>
                <hr>
                <p>Current UserID: <?php echo $f_user_id; ?></p>
                <p>Auth Status: <?php echo $get_auth_status; ?></p>
                <br>
                <p>MFA Current Secret: <?php echo $get_current_secret; ?></p>
                <p>MFA Status: <?php echo $mfa_status; ?></p>
                <p>MFA Validation: <?php echo $mfa_validation; ?></p>
                <br>
                <p>SMS Phone Number: <?php echo $get_phone_number; ?></p>
                <p>SMS Secret: <?php echo $sms_2fa_secret; ?></p>
                <p>SMS Status: <?php echo $sms_2fa_status; ?></p>
                <p>Auth Lockout: <?php echo  $get_auth_lockout_counter; ?></p>
                <?php if ((isset($f_auth['enable_2fa_settings']) && $f_auth['enable_2fa_settings']) && (isset($f_auth['enable_mfa_settings']) && $f_auth['enable_mfa_settings'])) { ?>


                    <form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <input type="hidden" type="text" name="user-id" value="<?= $f_user_id; ?>">
                        <?php
                        if (strpos($f_user_override, $user->data->user_email) !== false) {
                        ?>
                            <p style="padding-bottom: 0;">Current user is currently on Bypass Auth Verification</p>
                        <?php
                            $checkbox_status = 'checked';
                        } else {
                        ?>
                            <p style="padding-bottom: 0;">Current user is currently not on Bypass Auth Verification</p>
                        <?php
                            $checkbox_status = 'notchecked';
                        }
                        ?>
                        <input style="opacity: .5; pointer-events: none; height: 33px; width:100%; padding: 0; font-size: 1.5em; " type="text" name="bypass-auth-list" value="<?= $user->data->user_email; ?>">
                        <br>
                        <input type="hidden" type="text" name="bypass-auth-verification-status" value="<?= $checkbox_status; ?>">
                        <input type="hidden" type="text" name="bypass-auth-verification" value="RESET">
                        <button type="submit" name="submit" aria-label="Bypass Auth Verification" value="Bypass Auth Verification"><?= ($checkbox_status == 'checked') ? 'Remove from Bypass Auth Verification List' : 'Add to Bypass Auth Verification List' ?></button>
                        <p style="padding-bottom: 0;">Warning this will auto bypass the user from future authorization with MFA/ 2FA.</p>
                    </form>


                    <form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <input type="hidden" type="text" name="user-id" value="<?= $f_user_id; ?>">
                        <input style=" height: 33px; padding: 0; font-size: 1.5em; " type="text" type="text" name="change-phone-number" value="<?= $get_phone_number; ?>">
                        <input type="hidden" type="text" name="re-phone-number" value="RESET">
                        <button type="submit" name="submit" aria-label="Change Phone Number." value="Change Phone Number.">Change Phone Number.</button>
                        <p style="padding-bottom: 0;">+1 is necessary and please no dashes!</p>
                    </form>

                    <form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <input type="hidden" type="text" name="user-id" value="<?= $f_user_id; ?>">
                        <input type="hidden" type="text" name="re-auth" value="RESET">
                        <button type="submit" name="submit" aria-label="Change Authentication Selection." value="Change Authentication Selection.">Change Authentication Selection.</button>
                    </form>

                    <form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <input type="hidden" type="text" name="user-id" value="<?= $f_user_id; ?>">
                        <input type="hidden" type="text" name="reset-lockout" value="RESET">
                        <button type="submit" name="submit" aria-label="Reset Auth Lockout" value="Reset Auth Lockout">Reset Auth Lockout.</button>
                    </form>

                    <form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <input type="hidden" type="text" name="user-id" value="<?= $f_user_id; ?>">
                        <input type="hidden" type="text" name="reset-entire-auth" value="RESET">
                        <button type="submit" name="submit" aria-label="Reset Entire Auth" value="Reset Entire Auth">Reset Entire Auth.</button>
                    </form>
                <?php } ?>
            </div>
<?php
        }
    }
}
