<?php
use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twilio\Rest\Client;







    // do_action('2fa-registration-page');
    add_action('mfa-registration-page', function () {
        if(isset($_GET["mfaredirect"])){
            if($_GET["mfaredirect"] == 'home'){
                header("Location:".home_url());
                die();
            }
        }
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

        // Check if user has secret if not add secret.
        if (!$get_current_secret) {
            add_user_meta(get_current_user_id(), 'google2fa_secret', $google2fa_secret);
        }
        // Check if user has mfa_status if not add secret.
        if (!$mfa_status) {
            add_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
        }
        // Check if user has mfa_validation if not add secret.
        if (!$mfa_validation) {
            add_user_meta(get_current_user_id(), 'mfa_validation', 'not_valid');
        }


        var_dump(get_current_user_id());
        var_dump($mfa_status);

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


            if( isset($_SESSION["send-mfa"]) && $_SESSION["send-mfa"] == 'valid'){ ?>
                <div class="">Authorization Saved!</div>
                <div id="countdown"></div>
                <!-- <script>
                    var timeleft = 5;
                    var downloadTimer = setInterval(function(){
                        if(timeleft <= 0){
                            clearInterval(downloadTimer);
                            document.getElementById("countdown").innerHTML = "Reloading";
                            setTimeout(() => {
                                window.location = window.location.pathname + "?mfaredirect=home";
                            }, 1000);
                        } else {
                        document.getElementById("countdown").innerHTML = "Page will reload in: " + timeleft + " seconds";
                        }
                            timeleft -= 1;
                    }, 1000);
                </script> -->

            <?php } else {

                var_dump( $mfa_validation);

                if( !$mfa_validation || $mfa_validation == 'not_valid'){ ?>
                    <p><?= $get_current_secret; ?></p>
                    <img src='<?= $qrcode ?>' alt='QR Code' width='100' height='100'>
                <?php } ?>
                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                    <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                    <input autocomplete="off" type="text" name="google2fa_code" value="">
                    <input type="submit" name="submit" value="Submit">
                </form>
                
            <?php }?>
 
            


        <?php } else{ ?>
            Please contact system administrator. To reset 2fa code.
            <!-- <br>
            <div id="countdown"></div>
            <script>
                var timeleft = 5;
                var downloadTimer = setInterval(function(){
                    if(timeleft <= 0){
                        clearInterval(downloadTimer);
                        document.getElementById("countdown").innerHTML = "Reloading";
                        setTimeout(() => {
                            window.location = window.location.pathname + "?mfaredirect=home";
                        }, 1000);
                    } else {
                    document.getElementById("countdown").innerHTML = "Page will reload in: " + timeleft + " seconds";
                    }
                        timeleft -= 1;
                }, 1000);
            </script> -->
        <?php }
    });













    // This function block is responsible for detecting the time expiration of the MFA on page specific pages.
function ronikdesigns_redirect_non_registered_mfa() {
    $mfa_status = get_user_meta(get_current_user_id(), $key = 'mfa_status', true);
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
    if($f_auth['auth_page_enabled']){
        foreach($f_auth['auth_page_enabled'] as $auth_page_enabled){
            // We check the current page id and also the page title of the 2fa.
            if(($auth_page_enabled['page_selection'][0] == get_the_ID()) || ronikdesigns_get_page_by_title('mfa') || ronikdesigns_get_page_by_title('2fa')){
                // Check if user has mfa_status if not add secret.
                if (!$mfa_status) {
                    add_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
                }
                // Check if mfa_status is not equal to unverified.
                if (($mfa_status !== 'mfa_unverified')) {
                    $past_date = strtotime((new DateTime())->modify('-'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                    // If past date is greater than current date. We reset to unverified & start the process all over again.
                    if($past_date > $mfa_status ){
                        session_destroy();
                        update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
                        // Takes care of the redirection logic
                        ronikRedirectLoopApproval($dataUrl, "ronik-2fa-reset-redirect");
                    } else {
                        if (str_contains($_SERVER['REQUEST_URI'], '/mfa/')) {
                        // if($_SERVER['REQUEST_URI'] == '/mfa/'){
                            // Lets block the user from accessing the 2fa if already authenticated.
                            $dataUrl['reUrl'] = array('/wp-admin/admin-post.php', '/2fa/', '/mfa/');
                            $dataUrl['reDest'] = '/';
                            ronikRedirectLoopApproval($dataUrl, "ronik-2fa-reset-redirect");
                        }
                    }
                } else {
                    update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
                    // Takes care of the redirection logic
                        // Redirect Magic, custom function to prevent an infinite loop.
                        $dataUrl['reUrl'] = array('/wp-admin/admin-post.php', '/2fa/');
                        $dataUrl['reDest'] = '/mfa/';
                    ronikRedirectLoopApproval($dataUrl, "ronik-2fa-reset-redirect");
                }
            }
        }
    }
}