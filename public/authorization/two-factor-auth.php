<?php
add_action('2fa-registration-page', function () {
    // ✅ Initialize helper and auth processor classes
    $helper = new RonikHelper;
    $authProcessor = new RonikAuthProcessor;

    // ✅ Pull GET params to show errors/success messages
    $f_success = isset($_GET['sms-success']) ? $_GET['sms-success'] : false;
    $f_error = isset($_GET['sms-error']) ? $_GET['sms-error'] : false;
    $f_expired = '';

    // ✅ Display human-readable error message
    if ($f_error == 'nomatch') {
        $f_error = 'Sorry, the verification code entered is invalid.';
    }

    // ✅ Grab user 2FA data from user_meta
    $get_registration_status = get_user_meta(get_current_user_id(), 'sms_2fa_status', true);
    $sms_code_timestamp = get_user_meta(get_current_user_id(), 'sms_code_timestamp', true);
    $f_mfa_settings = get_field('mfa_settings', 'options');
    $sms_2fa_status = get_user_meta(get_current_user_id(), 'sms_2fa_status', true);
    $sms_2fa_secret = get_user_meta(get_current_user_id(), 'sms_2fa_secret', true);
    $get_auth_lockout_counter = get_user_meta(get_current_user_id(), 'auth_lockout_counter', true);
    $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);

    // ✅ Use custom expiration time from settings or default to 30 minutes
    $f_sms_expiration_time = isset($f_mfa_settings['sms_expiration_time']) ? $f_mfa_settings['sms_expiration_time'] : 30;

    if( isset($f_mfa_settings['sms_expiration_time']) || $f_mfa_settings['sms_expiration_time'] ){
        $f_sms_expiration_time = $f_mfa_settings['sms_expiration_time'];
    } else {
        $f_sms_expiration_time = 30;
    }
    // Check if sms_2fa_status is not equal to verified.
    if (($get_registration_status !== 'sms_2fa_unverified')) {
        $past_date = strtotime((new DateTime())->modify('-' . $f_sms_expiration_time . ' minutes')->format('d-m-Y H:i:s'));

        // 🧠 Debug logs
        error_log(print_r($sms_2fa_status, true));
        error_log(print_r($sms_code_timestamp, true));
        error_log(print_r($past_date, true));

        // ✅ Expired? Reset 2FA values and restart the flow
        if ($sms_code_timestamp < $past_date) {
            $valid = false;
            update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
            update_user_meta(get_current_user_id(), 'sms_2fa_secret', 'invalid');
            update_user_meta(get_current_user_id(), 'sms_code_timestamp', 0);
        } else {
            $valid = true;
        }
    } else {
        $valid = false;
    }

    // ✅ If validation passed, show success + reload page to complete auth
    if ($valid) {
?>
        <div class="">Authorization Saved!</div>
        <div id="countdown"></div>
        <script>
            // ✅ Countdown before redirecting back to 2FA complete route
            var timeleft = 5;
            var downloadTimer = setInterval(function() {
                if (timeleft <= 0) {
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
        // ✅ If success param is set, complete redirect now
        if ($f_success) {
            $authProcessor->ronik_authorize_success_redirect_path();
            exit;
        }
        // ❌ Not valid — either SMS secret is invalid or expired, or never sent
    } else { ?>
        <?php
        // ✅ Load the current SMS secret (may be 'invalid' or active)
        $sms_2fa_secret = get_user_meta(get_current_user_id(), 'sms_2fa_secret', true);

        // ✅ Optional helper class for showing admin messages (e.g., lockout info)
        if (class_exists('RonikAuthHelper')) {
            $authHelper = new RonikAuthHelper;
            $authHelper->auth_admin_messages();
        }

        // ✅ Show form only if secret exists and isn't marked as invalid
        if ($sms_2fa_secret && $sms_2fa_secret !== 'invalid') {
            $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);
            $get_phone_number = substr($get_phone_number, -4); // Last 4 digits only

            // ✅ Get current time and expiration config
            $current_date = strtotime((new DateTime())->format('d-m-Y H:i:s'));
            $f_mfa_settings = get_field('mfa_settings', 'options');
            $f_expiration_time = $f_mfa_settings['sms_expiration_time'] ?? 10;
            $f_sms_expiration_time = $f_expiration_time;

            // ✅ Calculate expiration window
            $past_date = strtotime((new DateTime())->modify('-' . $f_sms_expiration_time . ' minutes')->format('d-m-Y H:i:s'));
            $sms_code_timestamp = get_user_meta(get_current_user_id(), 'sms_code_timestamp', true);

            // 🪵 Log SMS expiration event if needed
            if ($past_date > $sms_code_timestamp) {
                $helper->ronikdesigns_write_log_devmode('SMS Expired', 'low', 'auth_2fa');
            }
        ?>
            <!-- ✅ 2FA Code Entry UI -->
            <div class="auth-content-bottom auth-content-bottom--sms">
                <form class="auth-content-bottom__submit <?php if ($f_error) {
                                                                echo 'auth-content-bottom__submit_error';
                                                            } ?>" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                    <div class="auth-content-bottom__submit-contents">
                        <div id="sms-expiration"></div>
                        <script>
                            // 🔁 Real-time countdown and expiration checker
                            console.log('Init Timevalidation');
                            smsExpiredChecker(); // Initial call

                            // ✅ Recheck when tab is focused again
                            document.addEventListener("visibilitychange", () => {
                                if (document.visibilityState == "visible") {
                                    console.log("Tab reactivated");
                                }
                            });

                            // 🕒 Countdown timer before SMS code expiration
                            var timeleft = <?= ($f_sms_expiration_time * 60); ?>;
                            var downloadTimer = setInterval(() => {
                                if (timeleft <= 0) {
                                    clearInterval(downloadTimer);
                                    setTimeout(() => {
                                        smsExpiredChecker(); // Revalidate via AJAX
                                    }, 1000);
                                }
                                timeleft -= 1;
                            }, 1000);

                            // ✅ SMS Expiration AJAX Check
                            function smsExpiredChecker() {
                                jQuery.ajax({
                                    type: 'post',
                                    url: wpVars.ajaxURL,
                                    data: {
                                        action: 'ronikdesigns_admin_auth_verification',
                                        smsExpired: true,
                                        nonce: wpVars.nonce,
                                        autoChecker: 'valid',
                                        crypt: '<?= $helper->ronik_encrypt_data_meta(get_current_user_id()); ?>'
                                    },
                                    dataType: 'json',
                                    success: data => {
                                        if (data.success) {
                                            if (data.data !== 'noreload') {
                                                alert('The SMS Code Expired. Page will auto reload.');
                                                setTimeout(() => {
                                                    let url = window.location.href;
                                                    url += (url.indexOf('?') > -1) ? '&sms-error=expired' : '?sms-error=expired';
                                                    window.location.href = url;
                                                }, 500);
                                            }
                                        } else {
                                            alert('Something went wrong! Please try again later!');
                                            setTimeout(() => {
                                                let url = window.location.href;
                                                url += (url.indexOf('?') > -1) ? '&sms-error=error' : '?sms-error=error';
                                                window.location.href = url;
                                            }, 500);
                                        }
                                    },
                                    error: err => {
                                        console.log(err);
                                        alert('Something went wrong! Please try again later!');
                                        setTimeout(() => {
                                            let url = window.location.href;
                                            url += (url.indexOf('?') > -1) ? '&sms-error=error' : '?sms-error=error';
                                            window.location.href = url;
                                        }, 500);
                                    }
                                });
                            }
                        </script>

                        <!-- 🔢 6-digit SMS input field -->
                        <input style="padding-left: 12px;" type="text" id="validate-sms-code" name="validate-sms-code" minlength="6" maxlength="6" placeholder="6 Digit Code" autocomplete="off" required>
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <?php if ($f_error) { ?>
                            <span class="message"><?= $f_error; ?></span>
                        <?php } ?>
                    </div>
                    <button type="submit" value="Reset Password">Submit SMS Code</button>
                </form>

                <!-- 🆘 Fallback instructions and SSO link -->
                <div class="auth-content-bottom__helper">
                    <p>If you don't receive a text message, please reach out to <a href="mailto:together@nbcuni.com?subject=2fa Registration Issue">together@nbcuni.com</a> for support.</p>
                    <a style="display:inline-block; padding-top: 10px;" href="<?= admin_url('admin-ajax.php') . '?action=ronikdesigns_admin_logout'; ?>"> Authenticate via NBCU SSO</a>
                </div>
            </div>
        <?php } else { ?>
            <!-- 📨 User has not yet triggered 2FA. Display "Send SMS Code" form. -->
            <div class="auth-content-bottom auth-content-bottom--sms">
                <form class="auth-content-bottom__submit" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                    <!-- Hidden fields to initiate the SMS send logic -->
                    <input type="hidden" name="send-sms" value="send-sms">
                    <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                    <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                    <span class="button-wrapper" style="margin-top: 0;">
                        <!-- Trigger sending the SMS -->
                        <button type="submit" value="Send SMS Code">Send SMS Code</button>

                        <!-- 🆘 Allow alternate login via NBCU SSO -->
                        <a href="<?= admin_url('admin-ajax.php') . '?action=ronikdesigns_admin_logout'; ?>">
                            Authenticate via NBCU SSO
                        </a>
                    </span>
                </form>
            </div>
    <?php }
    } ?>
<?php
});
