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
        add_user_meta(get_current_user_id(), 'mfa_validation', 'not_registered');
    }

    var_dump(get_current_user_id());
    var_dump($get_current_secret);
    var_dump($mfa_status);
    var_dump($mfa_validation);

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
            // Lets Check for the password reset url cookie.
            $cookie_name = "ronik-2fa-reset-redirect";
            if(isset($_COOKIE[$cookie_name])) {
                wp_redirect( esc_url(home_url(urldecode($_COOKIE[$cookie_name]))) );
                exit;
            }
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
                            window.location = window.location.pathname + "?mfaredirect=home";
                        }, 1000);
                    } else {
                    document.getElementById("countdown").innerHTML = "Page will auto reload in: " + timeleft + " seconds";
                    }
                        timeleft -= 1;
                }, 1000);
            </script>

        <?php } else {
            if( !$mfa_validation || ($mfa_validation == 'not_registered' )  ){ ?>
                <!-- We check if the get_current_secret is empty or false if so we reload the page.  -->
                <?php if(!$get_current_secret){ ?>
                    <script>
                        location.reload();
                    </script>
                <?php } ?>
                <p><?= $get_current_secret; ?></p>
                <img src='<?= $qrcode ?>' alt='QR Code' width='100' height='100'>
            <?php } ?>
            <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                <input required autocomplete="off" type="text" name="google2fa_code" value="">
                <input type="submit" name="submit" value="Submit">
            </form>
            
        <?php }?>
    <?php } else{ ?>
        <div class="">Authorization Saved!</div>
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
        </script>




        <?php 
        // Lets Check for the password reset url cookie.
            $cookie_name = "ronik-2fa-reset-redirect";
            if(isset($_COOKIE[$cookie_name])) {
                wp_redirect( esc_url(home_url(urldecode($_COOKIE[$cookie_name]))) );
                exit;
            } else {
                // We run our backup plan for redirecting back to previous page.
                // The downside this wont account for pages that were clicked during the redirect. So it will get the page that was previously visited.
                add_action('wp_footer', 'ronikdesigns_redirect_js');
                function ronikdesigns_redirect_js(){ ?>
                    <script type="text/javascript">
                        var x = JSON.parse(window.localStorage.getItem("ronik-url-reset"));
                        window.location.replace(x.redirect);
                    </script>
                <?php };
            }

            ?>


    <?php }
});