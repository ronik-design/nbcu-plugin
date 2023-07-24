<?php 
// This file plays a critical role. It loads in the MFA & SMS file.

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
            if(($get_auth_status == 'auth_select_sms')){
                if($f_admin_auth_select['2fa']){
                    // Include the 2fa auth.
                    error_log(print_r('ronikdesigns_redirect_registered_2fa', true));
                    ronikdesigns_redirect_registered_2fa();
                }
            }
            if(($get_auth_status == 'auth_select_mfa')){
                if($f_admin_auth_select['mfa']){
                    // Include the mfa auth.
                    error_log(print_r('ronikdesigns_redirect_non_registered_mfa', true));
                    ronikdesigns_redirect_non_registered_mfa();
                }
            }

            // Lets check if the current user status is none or not yet set.
            if(($get_auth_status == 'none') || !isset($get_auth_status) || !$get_auth_status){
                // If the MFA && 2fa are enabled we auto redirect to the AUTH template for user selection.
                if(($f_admin_auth_select['mfa']) && ($f_admin_auth_select['2fa'])){   
                    // Redirect Magic, custom function to prevent an infinite loop.
                    $dataUrl['reUrl'] = array('/wp-admin/admin-post.php', '/auth/');
                    $dataUrl['reDest'] = '/auth/';
                    ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                // Next we check if the MFA is set but not the 2fa. If so we include just the mfa.
                } else if(($f_admin_auth_select['mfa']) && (!$f_admin_auth_select['2fa'])){
                    // Include the mfa auth.
                    ronikdesigns_redirect_non_registered_mfa();
                // Next we check if the 2fa is set but not the mfa. If so we include just the 2fa.
                } else if((!$f_admin_auth_select['mfa']) && ($f_admin_auth_select['2fa'])){
                    // Include the 2fa auth.
                    ronikdesigns_redirect_registered_2fa();
                }
            }
    }
    add_action( 'admin_init', 'ronikdesigns_redirect_registered_auth' );
    add_action( 'template_redirect', 'ronikdesigns_redirect_registered_auth' );





    function your_function_name(){ ?>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script>
            jQuery(document).ready(function(){
                <?php 
                	$f_auth = get_field('mfa_settings', 'options');
                    $auth_idle_time = $f_auth['auth_idle_time'];
                	// $auth_idle_time = get_user_meta(get_current_user_id(), 'auth_idle_time', true);
                    if($auth_idle_time){
                        $auth_idle_time = $auth_idle_time * 60000; // milliseconds to minutes conversion.
                        // $auth_idle_time = $auth_idle_time * 10;

                    } else{
                        $auth_idle_time = 15000;
                    }
                ?>
                var timeoutTime = <?= $auth_idle_time; ?>;
                var timeoutTimer = setTimeout(idleTimeValidation, timeoutTime);
                jQuery(document).ready(function() {
                    // Okay lets check for mousemove, mousedown, keydown
                    $('body').bind('mousemove mousedown keydown', function(event) {
                        clearTimeout(timeoutTimer);
                        timeoutTimer = setTimeout(idleTimeValidation, timeoutTime);
                    });
                    setTimeout(function() {
                        console.log('ExpirationTimerExpiration');
                        timeValidationAjax();
                    }, timeoutTime);

                });
                function idleTimeValidation(){
                    console.log('idleTimeValidation');
                    timeValidationAjax();
                }

                function timeValidationAjax(){
                    jQuery.ajax({
                        type: 'POST',
                        url: "<?php echo esc_url( admin_url('admin-post.php') ); ?>",
                        data: {
                            action: 'ronikdesigns_admin_auth_verification',
                            timeChecker: true
                        },
                        success: data => {
                            if(data.success){
                                if(data.data == 'reload'){
                                    setTimeout(() => {
                                        window.location.reload(true);
                                    }, 500);
                                }
                            } else{
                                console.log('error');
                                console.log(data);
                                window.location.reload(true);
                            }
                        },
                        error: err => {
                            console.log(err);
                            window.location.reload(true);
                        }
                    });
                }

            });
        </script>
    <?php };
    add_action('wp_footer', 'your_function_name');


?>