<?php 
class RonikAuthHelper{
    /**
	 * This helps developers to debug on the fly and to make sure auth is working correctly!
	*/
    public function auth_admin_messages(){
        // https://together.nbcudev.local/auth?ronik_debug=ronik_admin_auth
        if( isset($_GET['ronik_debug']) && $_GET['ronik_debug'] == 'ronik_admin_auth' ){
            if(is_user_admin() || is_super_admin()){
                setcookie("RonikDebug", 'ronik_admin_auth_717', time()+1500);  /* expire in 25 min */
                // https://together.nbcudev.local/auth?ronik_debug=ronik_admin_auth&user_id=33
                if( isset($_GET['user_id']) ){
                    setcookie("RonikDebugUserID", $_GET['user_id'], time()+1500);  /* expire in 25 min */
                } else {
                    setcookie("RonikDebugUserID", get_current_user_id(), time()+1500);  /* expire in 25 min */
                }
            }
        }

        // CRITICAL this prevents non super_admin from seeing the auth admin settings
        if(!is_super_admin()){
            return false;
        }

        if((isset($_COOKIE['RonikDebug']) && array_key_exists( 'RonikDebug', $_COOKIE) && $_COOKIE['RonikDebug'] == 'ronik_admin_auth_717') || str_contains($_SERVER['SERVER_NAME'], 'together.nbcudev.local-de')){
            if(isset($_COOKIE['RonikDebugUserID']) && array_key_exists( 'RonikDebugUserID', $_COOKIE) ){
                $f_user_id = is_numeric($_COOKIE['RonikDebugUserID']) ? $_COOKIE['RonikDebugUserID'] : get_current_user_id();
            } else {
                $f_user_id = get_current_user_id();
            }

            // All auth data for person.
            $get_auth_status = get_user_meta($f_user_id,'auth_status', true);
            $get_current_secret = get_user_meta($f_user_id, 'google2fa_secret', true);
            $sms_2fa_status = get_user_meta($f_user_id,'sms_2fa_status', true);
            $sms_2fa_secret = get_user_meta($f_user_id,'sms_2fa_secret', true);
            $get_auth_lockout_counter = get_user_meta($f_user_id, 'auth_lockout_counter', true) ? get_user_meta($f_user_id, 'auth_lockout_counter', true) : (int)0;
            $get_phone_number = get_user_meta($f_user_id, 'sms_user_phone', true);
            $mfa_status = get_user_meta($f_user_id,'mfa_status', true);
            $mfa_validation = get_user_meta($f_user_id,'mfa_validation', true);

           
            error_log(print_r( 'DEBUG ACTIVATED', true));
            $f_auth = get_field('mfa_settings', 'options');
        ?>
            <div class="dev-notice">
                <h4>Dev Message:</h4>
                <p>
                    Welcome to the Frontend Dev Reset Center. This is a powerful tool that can reset and change all AUTH information.
                </p>
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
                <?php if( ( isset($f_auth['enable_2fa_settings']) && $f_auth['enable_2fa_settings'] ) && ( isset($f_auth['enable_mfa_settings']) && $f_auth['enable_mfa_settings'] ) ){ ?>
                    <form action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field( 'ajax-nonce', 'nonce' ); ?>
                        <input type="hidden" type="text" name="user-id" value="<?= $f_user_id; ?>">
                        <input style=" height: 33px; padding: 0; font-size: 1.5em; " type="text" type="text" name="change-phone-number" value="<?= $get_phone_number; ?>">
                        <input type="hidden" type="text" name="re-phone-number" value="RESET">
                        <button type="submit" name="submit" aria-label="Change Phone Number." value="Change Phone Number.">Change Phone Number.</button>
                        <p style="padding-bottom: 0;">+1 is necessary and please no dashes!</p>
                    </form>

                    <form action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field( 'ajax-nonce', 'nonce' ); ?>
                        <input type="hidden" type="text" name="user-id" value="<?= $f_user_id; ?>">
                        <input type="hidden" type="text" name="re-auth" value="RESET">
                        <button type="submit" name="submit" aria-label="Change Authentication Selection." value="Change Authentication Selection.">Change Authentication Selection.</button>
                    </form>

                    <form action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field( 'ajax-nonce', 'nonce' ); ?>
                        <input type="hidden" type="text" name="user-id" value="<?= $f_user_id; ?>">
                        <input type="hidden" type="text" name="reset-lockout" value="RESET">
                        <button type="submit" name="submit" aria-label="Reset Auth Lockout" value="Reset Auth Lockout">Reset Auth Lockout.</button>
                    </form>

                    <form action="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field( 'ajax-nonce', 'nonce' ); ?>
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