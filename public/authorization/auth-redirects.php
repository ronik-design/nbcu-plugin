<?php
// This file plays a critical role. It loads in the MFA & SMS file.
// Also handles all the redirects.

// This function block is responsible for redirecting users to the correct AUTH page.
function ronikdesigns_redirect_registered_auth() {
    // Helper Guide
    $helper = new RonikHelper;
    $authChecker = new RonikAuthChecker;


    $f_auth = get_field('mfa_settings', 'options');
    $f_auth_mfa = get_option('options_mfa_settings_enable_mfa_settings');
    $f_auth_2fa = get_option('options_mfa_settings_enable_2fa_settings');

    $f_admin_auth_select['mfa'] = false;
    $f_admin_auth_select['2fa'] = false;

    // Kill the entire AUTH if both are not enabled!
    if((!$f_auth_mfa) && (!$f_auth_2fa)){
        $helper->ronikdesigns_write_log_devmode('Auth is Killed', 'low');
        return;
    }
    // Restricted Access only login users can proceed.
    if(!is_user_logged_in()){
        $helper->ronikdesigns_write_log_devmode('Auth is due to not logged in user.', 'low');
        // Redirect Magic, custom function to prevent an infinite loop.
        $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/auth/', '/2fa/', '/mfa/');
        $dataUrl['reDest'] = '';
        ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
    }
    // If both AUTH are not enabled we auto bypass the auth-template. By including the auth files below.
        // Lets check if MFA is not enabled!
        if(!$f_auth_mfa){
            // Lets check if 2fa is enabled!
            if($f_auth_2fa){
                // Store the values in an array for later.
                $f_admin_auth_select['2fa'] = true;
            }
        }
        // Lets check if 2fa is not enabled!
        if(!$f_auth_2fa){
            // Lets check if MFA is enabled!
            if($f_auth_mfa){
                // Store the values in an array for later.
                $f_admin_auth_select['mfa'] = true;
            }
        }
        // Lets check if 2fa is not enabled!
        if($f_auth_2fa && $f_auth_mfa){
            // Store the values in an array for later.
            $f_admin_auth_select['mfa'] = true;
            $f_admin_auth_select['2fa'] = true;
        }
        // We check the current user auth status and compare it to the admin auth selection
        $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);

        if( $authChecker->urlCheckNoWpPage($_SERVER['REQUEST_URI']) ){
            if( $authChecker->urlCheckNoAuthPage($_SERVER['REQUEST_URI']) ){
                $authChecker->userTrackerActions($_SERVER['REQUEST_URI']);
            }
        }

        // Okay this is critical: If site admin disables one auth but not the other auth.
        // Basically user selection is thrown away and is automatically set.
        if($f_admin_auth_select['mfa'] && !$f_admin_auth_select['2fa'] ){
            $helper->ronikdesigns_write_log_devmode('ADMIN Overwrite to MFA', 'low');
            // update usermeta data
            update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_mfa');
            ronikdesigns_redirect_non_registered_mfa();
            return;
        } else if(!$f_admin_auth_select['mfa'] && $f_admin_auth_select['2fa']) {
            $helper->ronikdesigns_write_log_devmode('ADMIN Overwrite to 2fa', 'low');
            // update usermeta data
            update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_sms');
            ronikdesigns_redirect_registered_2fa();
            return;
        }


        if(($get_auth_status == 'auth_select_sms')){
            if($f_admin_auth_select['2fa']){
                // Include the 2fa auth.
                $helper->ronikdesigns_write_log_devmode('ronikdesigns_redirect_registered_2fa', 'low');
                ronikdesigns_redirect_registered_2fa();
                return;
            }
        }
        if(($get_auth_status == 'auth_select_mfa')){
            if($f_admin_auth_select['mfa']){
                // Include the mfa auth.
                $helper->ronikdesigns_write_log_devmode('roniknbcu_ronikdesign_non_registered_mfa', 'low');
                ronikdesigns_redirect_non_registered_mfa();
                return;
            }
        }

        // This is critical
        if($get_auth_status == 'auth_select_sms-missing'){
            $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/auth/');
            $dataUrl['reDest'] = '/auth/';
            ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
            return;
        }

        // Lets check if the current user status is none or not yet set.
        if(($get_auth_status == 'none') || !isset($get_auth_status) || !$get_auth_status){
            $helper->ronikdesigns_write_log_devmode('roniknbcu_ronikdesign_none', 'low');
            // If the MFA && 2fa are enabled we auto redirect to the AUTH template for user selection.
            if(($f_admin_auth_select['mfa']) && ($f_admin_auth_select['2fa'])){
                $helper->ronikdesigns_write_log_devmode('AUTH Route', 'low');

                // Redirect Magic, custom function to prevent an infinite loop.
                $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/auth/');
                $dataUrl['reDest'] = '/auth/';
                ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                return;

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
        } else {
            $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/auth/');
            $dataUrl['reDest'] = '/auth/';
            ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
            return;
        }
}
add_action( 'admin_init', 'ronikdesigns_redirect_registered_auth' );
add_action( 'template_redirect', 'ronikdesigns_redirect_registered_auth' );


// This function block is responsible for detecting the time expiration of the MFA on page specific pages.
function ronikdesigns_redirect_non_registered_mfa() {
    $helper = new RonikHelper;

    $mfa_status = get_user_meta(get_current_user_id(), $key = 'mfa_status', true);
    $mfa_validation = get_user_meta(get_current_user_id(),'mfa_validation', true);

    $f_mfa_settings = get_field('mfa_settings', 'options');
    if( isset($f_mfa_settings['auth_expiration_time']) || $f_mfa_settings['auth_expiration_time'] ){
        $f_auth_expiration_time = $f_mfa_settings['auth_expiration_time'];
    } else {
        $f_auth_expiration_time = 30;
    }
    // Redirect Magic, custom function to prevent an infinite loop.
    $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/2fa/', '/mfa/');
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
                // if (!str_contains($_SERVER['REQUEST_URI'], '/wp-admin/')) {
                //     error_log(print_r( 'RONIK NEXT FIX 0! KM might be fixed by ignoring the wp-admin request uri', true));
                //     // Takes care of the redirection logic
                //     // ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                // }
            } else {
                if (str_contains($_SERVER['REQUEST_URI'], '/mfa/')) {
                // if($_SERVER['REQUEST_URI'] == '/mfa/'){
                    // Lets block the user from accessing the 2fa if already authenticated.
                    $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/2fa/', '/mfa/');
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
                $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/2fa/');
                $dataUrl['reDest'] = '/mfa/';
            ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
        }
    }
}


// This function block is responsible for detecting the time expiration of the 2fa on page specific pages.
function ronikdesigns_redirect_registered_2fa() {
    // Helper Guide
    $helper = new RonikHelper;

    $get_registration_status = get_user_meta(get_current_user_id(),'sms_2fa_status', true);
    $sms_code_timestamp = get_user_meta(get_current_user_id(),'sms_code_timestamp', true);
    $f_mfa_settings = get_field('mfa_settings', 'options');
    if( isset($f_mfa_settings['auth_expiration_time']) || $f_mfa_settings['auth_expiration_time'] ){
        $f_auth_expiration_time = $f_mfa_settings['auth_expiration_time'];
    } else {
        $f_auth_expiration_time = 30;
    }
    // Redirect Magic, custom function to prevent an infinite loop.
    $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/2fa/');
    $dataUrl['reDest'] = '/2fa/';

    // We check the current page id and also the page title of the 2fa.
    if (!str_contains($_SERVER['REQUEST_URI'], '2fa') && !str_contains($_SERVER['REQUEST_URI'], 'mfa')) {
        // Check if user has sms_2fa_status if not add secret.
        if (!$get_registration_status) {
            update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
        }
        // Check if sms_2fa_status is not equal to unverified.
        if (($get_registration_status !== 'sms_2fa_unverified')) {
            $past_date = strtotime((new DateTime())->modify('-'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
            // If past date is greater than current date. We reset to unverified & start the process all over again.
            if($past_date > $sms_code_timestamp ){
                if (!str_contains($_SERVER['REQUEST_URI'], '/wp-admin/')) {
                    error_log(print_r( 'RONIK NEXT FIX 1! KM might be fixed by ignoring the wp-admin request uri', true));
                    // update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                    // Takes care of the redirection logic
                    ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                }
            } else {
                if (str_contains($_SERVER['REQUEST_URI'], '/2fa/')) {
                    // Lets block the user from accessing the 2fa if already authenticated.
                    $dataUrl['reUrl'] = array('/');
                    $dataUrl['reDest'] = '/';
                    error_log(Print_r( 'RONIK NEXT FIX!', true));
                    ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                }
            }
        } else {
            error_log(print_r( 'RONIK NEXT FIX 2!', true));
            // update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
            // Takes care of the redirection logic
            ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
        }
    }else {
        // Lets block the user from accessing the 2fa if already authenticated.
        $dataUrl['reUrl'] = array('/');
        $dataUrl['reDest'] = '/';
        ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
    }
}


function timeValidationExcution(){
    // Check if user is logged in.
    if (!is_user_logged_in()) {
        return;
    } ?>
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> -->
    <script>
        // This guy triggers a reload when user leaves page.
        document.addEventListener("visibilitychange", (event) => {
        if (document.visibilityState == "visible") {
            console.log("tab is active");
            // window.location.reload(true);
        } else {
            console.log("tab is inactive")
        }
        });
        jQuery(document).ready(function(){
            timeValidationAjax('invalid', 'invalid', 'invalid');
            // Throttle the execution.
            setTimeout(() => {
                function videoIframeValidation($target){
                    var iframe = $target;
                    var videoPlayer = new Vimeo.Player(iframe);
                    videoPlayer.on('play', function() {
                        console.log('Video is playing');
                        timeValidationAjax('invalid', 'invalid', 'valid');
                    });
                    videoPlayer.on('pause', function() {
                        console.log('Video is paused');
                        timeValidationAjax('invalid', 'invalid', 'invalid');
                    });
                }
                // Pretty much we detect if the iframe is for vimeo if so we add the wrapper
                $( "iframe.optanon-category-4-7" ).each(function( index ) {
                   if($( this )){
                        if (typeof $( this ).attr('src') !== 'undefined') {
                            if( $( this ).attr('src').length > 0 && $( this ).attr('src').indexOf("vimeo") > -1 ){
                                videoIframeValidation($( this ));
                            }
                        }
                        if (typeof $( this ).attr('data-src') !== 'undefined') {
                            if( $( this ).attr('data-src').length > 0 && $( this ).attr('data-src').indexOf("vimeo") > -1 ){
                                videoIframeValidation($( this ));
                            }
                        }
                    }
                });

                // observer that detects changes to the dom...
                // This is primarily used for the modal video
                // observer that detects changes to the dom...
                class RonikObserverModal {
                    constructor(elemToObserve, activeClassTarget) {
                        this.elemToObserve = elemToObserve;
                        this.activeClassTarget = activeClassTarget;
                    }
                    ronikObserve() {
                        let elemToObserve = this.elemToObserve;
                        let activeClassTarget = this.activeClassTarget;
                        if(elemToObserve){
                            let prevClassState = elemToObserve.classList.contains(activeClassTarget);
                            let observer = new MutationObserver(function(mutations) {
                                mutations.forEach(function(mutation) {
                                    if(mutation.attributeName == "class"){
                                        let currentClassState = mutation.target.classList.contains(activeClassTarget);
                                        if(prevClassState !== currentClassState)    {
                                            prevClassState = currentClassState;
                                            if(currentClassState){
                                                console.log("Video is playing");
                                                timeValidationAjax('invalid', 'invalid', 'valid');
                                            } else{
                                                console.log("Video is not playing");
                                                timeValidationAjax('invalid', 'invalid', 'invalid');
                                            }
                                        }
                                    }
                                });
                            });
                            observer.observe(elemToObserve, {attributes: true});
                        }
                    }
                }

                const ronikObserverModalTarget1 = new RonikObserverModal(document.querySelector('.jqmWindow'), 'active');
                ronikObserverModalTarget1.ronikObserve();

                const ronikObserverModalTarget2 = new RonikObserverModal(document.querySelector('.event-video'), 'is-open');
                ronikObserverModalTarget2.ronikObserve();

            }, 250);


            console.log('Lets check the inital timeout');
            // Lets trigger the validation on page load.
            timeValidationAjax('invalid', 'valid', false);
            // This is critical we basically re-run the timeValidationAjax function every 30 seconds
            function timeValidationChecker() {
                console.log('Every 30 seconds We trigger the validation checker');
                console.log('Lets check the timeout');
                // Lets trigger the validation on page load.
                timeValidationAjax('invalid', 'valid', false);
            }
            setInterval(timeValidationChecker, (60000/2));
            <?php
                $f_auth = get_field('mfa_settings', 'options');
                $auth_idle_time = $f_auth['auth_idle_time'];
                if($auth_idle_time){
                    $auth_idle_time = $auth_idle_time * 60000; // milliseconds to minutes conversion.
                } else{
                    $auth_idle_time = 15000;
                }
                $auth_max_time = $f_auth['auth_expiration_time'];
                if($auth_max_time){
                    $auth_max_time = $auth_max_time * 60000; // milliseconds to minutes conversion.
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
                timeValidationAjax('valid', 'invalid', false);
            }, timeoutTimeMax);

            function idleTimeValidation(){
                console.log('idle Time Validation');
                console.log('Detect if user does not interact with site.');
                // Basically this will kill the mfa and reset everything.
                timeValidationAjax('valid', 'invalid', false);
            }
            function timeValidationAjax( killValidation, timeChecker, videoPlayed ){
                jQuery.ajax({
                    type: 'POST',
                    url: wpVars.ajaxURL,
                    data: {
                        action: 'ronikdesigns_admin_auth_verification',
                        killValidation: killValidation,
                        timeChecker: timeChecker,
                        videoPlayed: videoPlayed,
                        nonce: wpVars.nonce
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
