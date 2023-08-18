<?php
// This file plays a critical role. It loads in the MFA & SMS file.
// Also handles all the redirects.

    // This function block is responsible for redirecting users to the correct AUTH page.
    function ronikdesigns_redirect_registered_auth() {
        $f_auth = get_field('mfa_settings', 'options');
        $f_admin_auth_select['mfa'] = false;
        $f_admin_auth_select['2fa'] = false;
        // Kill the entire AUTH if both are not enabled!
        if((!$f_auth['enable_mfa_settings']) && (!$f_auth['enable_2fa_settings'])){
            return;
        }
        // Restricted Access only login users can proceed.
        if(!is_user_logged_in()){
            // Redirect Magic, custom function to prevent an infinite loop.
            $dataUrl['reUrl'] = array('/wp-admin/admin-post.php', '/auth/', '/2fa/', '/mfa/');
            $dataUrl['reDest'] = '';
            ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
        }
        // If both AUTH are not enabled we auto bypass the auth-template. By including the auth files below.
            // Lets check if MFA is not enabled!
            if(!$f_auth['enable_mfa_settings']){
                // Lets check if 2fa is enabled!
                if($f_auth['enable_2fa_settings']){
                    // Store the values in an array for later.
                    $f_admin_auth_select['2fa'] = true;
                }
            }
            // Lets check if 2fa is not enabled!
            if(!$f_auth['enable_2fa_settings']){
                // Lets check if MFA is enabled!
                if($f_auth['enable_mfa_settings']){
                    // Store the values in an array for later.
                    $f_admin_auth_select['mfa'] = true;
                }
            }
            // Lets check if 2fa is not enabled!
            if($f_auth['enable_2fa_settings'] && $f_auth['enable_mfa_settings']){
                // Store the values in an array for later.
                $f_admin_auth_select['mfa'] = true;
                $f_admin_auth_select['2fa'] = true;
            }
            // We check the current user auth status and compare it to the admin auth selection
            $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);

            // Okay this is critical: If site admin disables one auth but not the other auth.
            // Basically user selection is thrown away and is automatically set.
            if($f_admin_auth_select['mfa'] && !$f_admin_auth_select['2fa'] ){
                error_log(print_r('ADMIN Overwrite to MFA', true));
                // update usermeta data
                update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_mfa');
                ronikdesigns_redirect_non_registered_mfa();
                return;
            } else if(!$f_admin_auth_select['mfa'] && $f_admin_auth_select['2fa']) {
                error_log(print_r('ADMIN Overwrite to 2fa', true));
                // update usermeta data
                update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_sms');
                ronikdesigns_redirect_registered_2fa();
                return;
            }


            if(($get_auth_status == 'auth_select_sms')){
                if($f_admin_auth_select['2fa']){
                    // Include the 2fa auth.
                    error_log(print_r('ronikdesigns_redirect_registered_2fa', true));
                    ronikdesigns_redirect_registered_2fa();
                    return;
                }
            }
            if(($get_auth_status == 'auth_select_mfa')){
                if($f_admin_auth_select['mfa']){
                    // Include the mfa auth.
                    error_log(print_r('roniknbcu_ronikdesign_non_registered_mfa', true));
                    ronikdesigns_redirect_non_registered_mfa();
                    return;
                }
            }
            // Lets check if the current user status is none or not yet set.
            if(($get_auth_status == 'none') || !isset($get_auth_status) || !$get_auth_status){
                error_log(print_r('roniknbcu_ronikdesign_none', true));
                // If the MFA && 2fa are enabled we auto redirect to the AUTH template for user selection.
                if(($f_admin_auth_select['mfa']) && ($f_admin_auth_select['2fa'])){
                    error_log(print_r('AUTH Route', true));

                    // Redirect Magic, custom function to prevent an infinite loop.
                    // if(empty($get_auth_status)){
                        $dataUrl['reUrl'] = array('/wp-admin/admin-post.php', '/auth/');
                        $dataUrl['reDest'] = '/auth/';
                        ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                        return;
                        // wp_redirect( esc_url(home_url('/auth/')) );
                        // exit;

                    // }
                // Next we check if the MFA is set but not the 2fa. If so we include just the mfa.
                } else if(($f_admin_auth_select['mfa']) && (!$f_admin_auth_select['2fa'])){
                    // Include the mfa auth.
                    ronikdesigns_redirect_non_registered_mfa();
                    return;
                // Next we check if the 2fa is set but not the mfa. If so we include just the 2fa.
                } else if((!$f_admin_auth_select['mfa']) && ($f_admin_auth_select['2fa'])){
                    // Include the 2fa auth.
                    ronikdesigns_redirect_registered_2fa();
                    return;
                }
            }
    }
    add_action( 'admin_init', 'ronikdesigns_redirect_registered_auth' );
    add_action( 'template_redirect', 'ronikdesigns_redirect_registered_auth' );





// This function block is responsible for detecting the time expiration of the MFA on page specific pages.
function ronikdesigns_redirect_non_registered_mfa() {
    $mfa_status = get_user_meta(get_current_user_id(), $key = 'mfa_status', true);
    $mfa_validation = get_user_meta(get_current_user_id(),'mfa_validation', true);

    $f_mfa_settings = get_field('mfa_settings', 'options');
    if( isset($f_mfa_settings['auth_expiration_time']) || $f_mfa_settings['auth_expiration_time'] ){
        $f_auth_expiration_time = $f_mfa_settings['auth_expiration_time'];
    } else {
        $f_auth_expiration_time = 30;
    }
    $f_auth = get_field('mfa_settings', 'options');
    // Redirect Magic, custom function to prevent an infinite loop.
    $dataUrl['reUrl'] = array('/wp-admin/admin-post.php', '/2fa/', '/mfa/');
    $dataUrl['reDest'] = '/mfa/';

            if(ronikdesigns_get_page_by_title('mfa')){
                // Check if user has mfa_status if not add secret.
                if (!$mfa_status) {
                    // add_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
                    update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');

                }
                // Check if mfa_status is not equal to unverified.
                if (($mfa_status !== 'mfa_unverified')) {
                    $past_date = strtotime((new DateTime())->modify('-'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                    // If past date is greater than current date. We reset to unverified & start the process all over again.
                    if($past_date > $mfa_status ){
                        // session_destroy();
                        update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
                        // Takes care of the redirection logic
                        ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                    } else {
                        if (str_contains($_SERVER['REQUEST_URI'], '/mfa/')) {
                        // if($_SERVER['REQUEST_URI'] == '/mfa/'){
                            // Lets block the user from accessing the 2fa if already authenticated.
                            $dataUrl['reUrl'] = array('/wp-admin/admin-post.php', '/2fa/', '/mfa/');
                            $dataUrl['reDest'] = '/';

                            if($mfa_validation !== 'valid' ){
                                ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                            }
                        }
                    }
                } else {
                    // update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
                    // Takes care of the redirection logic
                        // Redirect Magic, custom function to prevent an infinite loop.
                        $dataUrl['reUrl'] = array('/wp-admin/admin-post.php', '/2fa/');
                        $dataUrl['reDest'] = '/mfa/';
                    ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                }
            }
}


// This function block is responsible for detecting the time expiration of the 2fa on page specific pages.
function ronikdesigns_redirect_registered_2fa() {
    $get_registration_status = get_user_meta(get_current_user_id(),'sms_2fa_status', true);
    $sms_code_timestamp = get_user_meta(get_current_user_id(),'sms_code_timestamp', true);
    $f_mfa_settings = get_field('mfa_settings', 'options');
    if( isset($f_mfa_settings['auth_expiration_time']) || $f_mfa_settings['auth_expiration_time'] ){
        $f_auth_expiration_time = $f_mfa_settings['auth_expiration_time'];
    } else {
        $f_auth_expiration_time = 30;
    }
    $f_auth = get_field('mfa_settings', 'options');
    // Redirect Magic, custom function to prevent an infinite loop.
    $dataUrl['reUrl'] = array('/wp-admin/admin-post.php', '/2fa/');
    $dataUrl['reDest'] = '/2fa/';

    // if($f_auth['auth_page_enabled']){
    //     foreach($f_auth['auth_page_enabled'] as $auth_page_enabled){
            // We check the current page id and also the page title of the 2fa.
            if (!str_contains($_SERVER['REQUEST_URI'], '2fa') && !str_contains($_SERVER['REQUEST_URI'], 'mfa')) {
                // Check if user has sms_2fa_status if not add secret.
                if (!$get_registration_status) {
                    // add_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                    update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');

                }
                // Check if sms_2fa_status is not equal to unverified.
                if (($get_registration_status !== 'sms_2fa_unverified')) {
                    $past_date = strtotime((new DateTime())->modify('-'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                    // If past date is greater than current date. We reset to unverified & start the process all over again.
                    if($past_date > $sms_code_timestamp ){
                        update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                        // Takes care of the redirection logic
                        ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                    } else {
                        if (str_contains($_SERVER['REQUEST_URI'], '/2fa/')) {
                        // if($_SERVER['REQUEST_URI'] == '/2fa/'){
                            // Lets block the user from accessing the 2fa if already authenticated.
                            $dataUrl['reUrl'] = array('/');
                            $dataUrl['reDest'] = '/';

                            error_log(Print_r( 'RONIK NEXT FIX!', true));
                            ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                        }
                    }
                } else {
                    error_log(print_r( $get_registration_status, true));
                    update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                    // Takes care of the redirection logic
                    ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                }
            }else {
                // Lets block the user from accessing the 2fa if already authenticated.
                $dataUrl['reUrl'] = array('/');
                $dataUrl['reDest'] = '/';
                ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
            }
    //     }
    // }
}













    function timeValidationExcution(){
		// Check if user is logged in.
		if (!is_user_logged_in()) {
			return;
		}
    ?>
        <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> -->
        <script>
            // This guy triggers a reload when user leaves page.
            document.addEventListener("visibilitychange", (event) => {
            if (document.visibilityState == "visible") {
                console.log("tab is active");
                window.location.reload(true);
            } else {
                console.log("tab is inactive")
            }
            });
            jQuery(document).ready(function(){
                console.log('Lets check the timeout');
                // Lets trigger the validation on page load.
                timeValidationAjax('invalid', 'valid');
                <?php
                	$f_auth = get_field('mfa_settings', 'options');
                    $auth_idle_time = $f_auth['auth_idle_time'];
                    if($auth_idle_time){
                        $auth_idle_time = $auth_idle_time * 60000; // milliseconds to minutes conversion.
                        // $auth_idle_time = $auth_idle_time * 5000; // milliseconds to minutes conversion.
                    } else{
                        $auth_idle_time = 15000;
                    }

                    $auth_max_time = $f_auth['auth_expiration_time'];
                    if($auth_max_time){
                        $auth_max_time = $auth_max_time * 60000; // milliseconds to minutes conversion.
                        // $auth_max_time = $auth_max_time * 5000; // milliseconds to minutes conversion.
                    } else{
                        $auth_max_time = 15000;
                    }

                    $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);

                    if((!$get_auth_status) || !$get_auth_status == 'none'){
                        return;
                    }


                ?>
                var timeoutTimeMax = <?= $auth_max_time; ?>;
                var timeoutTimeIdle = <?= $auth_idle_time; ?>;
                console.log(timeoutTimeIdle);
                console.log(timeoutTimeMax);

                var timeoutTimer = setTimeout(idleTimeValidation, timeoutTimeIdle);
                  // This is a simple countdown function. That when the function time is up we trigger the kill code on mfa status..
                $('body').bind('mousemove mousedown keydown', function(event) {
                    clearTimeout(timeoutTimer);
                    timeoutTimer = setTimeout(idleTimeValidation, timeoutTimeIdle);
                });

                // This is a simple countdown function. That when the function time is up we trigger the kill code on mfa status..
                setTimeout(function() {
                    console.log('Expiration Timer Expiration');
                    // Basically this will kill the mfa and reset everything.
                    timeValidationAjax('valid', 'invalid');
                }, timeoutTimeMax);

                function idleTimeValidation(){
                    console.log('idle Time Validation');
                    // Basically this will kill the mfa and reset everything.
                    timeValidationAjax('valid', 'invalid');
                }
                function timeValidationAjax( killValidation, timeChecker ){
                    jQuery.ajax({
                        type: 'POST',
                        url: "<?php echo esc_url( admin_url('admin-post.php') ); ?>",
                        data: {
                            action: 'ronikdesigns_admin_auth_verification',
                            killValidation: killValidation,
                            timeChecker: timeChecker,
                        },
                        success: data => {
                            if(data.success){
                                console.log(data);
                                if(data.data == 'reload'){
                                    setTimeout(() => {
                                        window.location.reload(true);
                                    }, 50);
                                }
                            } else{
                                console.log('error');
                                console.log(data);
                                console.log(data.data);
                                // window.location.reload(true);
                            }
                            console.log(data);
                        },
                        error: err => {
                            console.log(err);
                            // window.location.reload(true);
                        }
                    });
                }

            });
        </script>
    <?php };
    add_action('wp_footer', 'timeValidationExcution');
    add_action('admin_footer', 'timeValidationExcution');
?>
