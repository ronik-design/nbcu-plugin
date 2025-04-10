<?php
add_action('2fa-registration-page', function () {
    $user_id = get_current_user_id();
    $helper = new RonikHelper;
    $authProcessor = new RonikAuthProcessor;

    $f_mfa_settings = get_field('mfa_settings', 'options');
    $f_sms_expiration_time = isset($f_mfa_settings['sms_expiration_time']) ? $f_mfa_settings['sms_expiration_time'] : 30;

    $f_success = $_GET['sms-success'] ?? false;
    $f_error = $_GET['sms-error'] ?? false;
    $valid = false;

    $sms_status = get_user_meta($user_id, 'sms_2fa_status', true);
    $sms_secret = get_user_meta($user_id, 'sms_2fa_secret', true);
    $sms_timestamp = get_user_meta($user_id, 'sms_code_timestamp', true);
    $phone = get_user_meta($user_id, 'sms_user_phone', true);
    $lockout_count = get_user_meta($user_id, 'auth_lockout_counter', true);

    // ✅ Check if code expired
    if ($sms_status !== 'sms_2fa_unverified') {
        $expired_time = strtotime((new DateTime())->modify("-{$f_sms_expiration_time} minutes")->format('Y-m-d H:i:s'));
        if ($sms_timestamp < $expired_time) {
            update_user_meta($user_id, 'sms_2fa_status', 'sms_2fa_unverified');
            update_user_meta($user_id, 'sms_2fa_secret', 'invalid');
            update_user_meta($user_id, 'sms_code_timestamp', 0);
        } else {
            $valid = true;
        }
    }

    // ✅ Show success and reload countdown
    if ($valid) {
        ?>
        <div class="">Authorization Saved!</div>
        <div id="countdown"></div>
        <script>
            let counter = 5;
            const countdown = setInterval(() => {
                const display = document.getElementById("countdown");
                display.innerText = counter > 0
                    ? `Page will reload in: ${counter} seconds`
                    : "Reloading";
                if (counter <= 0) {
                    clearInterval(countdown);
                    setTimeout(() => {
                        window.location = window.location.pathname + "?sms-success=success";
                    }, 1000);
                }
                counter--;
            }, 1000);
        </script>
        <?php
        if ($f_success) {
            $authProcessor->ronik_authorize_success_redirect_path();
            exit;
        }
        return;
    }

    // 🔐 Optional lockout/admin message
    if (class_exists('RonikAuthHelper')) {
        (new RonikAuthHelper)->auth_admin_messages();
    }

    // 🔢 Show code input form
    if ($sms_secret && $sms_secret !== 'invalid') {
        $last4 = substr($phone, -4); ?>
        <div class="auth-content-bottom auth-content-bottom--sms">
            <form class="auth-content-bottom__submit <?= $f_error ? 'auth-content-bottom__submit_error' : '' ?>" action="<?= esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                <div class="auth-content-bottom__submit-contents">
                    <input type="text" id="validate-sms-code" name="validate-sms-code" minlength="6" maxlength="6" placeholder="6 Digit Code" autocomplete="off" required style="padding-left: 12px;">
                    <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                    <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                    <?php if ($f_error): ?>
                        <span class="message"><?= esc_html($f_error === 'nomatch' ? 'Invalid code entered.' : $f_error); ?></span>
                    <?php endif; ?>
                </div>
                <button type="submit">Submit SMS Code</button>
            </form>

            <div class="auth-content-bottom__helper">
                <p>If you don't receive a text message, contact <a href="mailto:together@nbcuni.com?subject=2fa Registration Issue">together@nbcuni.com</a>.</p>
                <a href="<?= admin_url('admin-ajax.php?action=ronikdesigns_admin_logout'); ?>">Authenticate via NBCU SSO</a>
            </div>

            <script>
                smsExpiredChecker();
                
                let timeleft = <?= $f_sms_expiration_time * 60 ?>;
                const timer = setInterval(() => {
                    if (--timeleft <= 0) {
                        clearInterval(timer);
                        setTimeout(() => smsExpiredChecker(), 1000);
                    }
                }, 1000);

                function smsExpiredChecker() {
                    jQuery.post(wpVars.ajaxURL, {
                        action: 'ronikdesigns_admin_auth_verification',
                        smsExpired: true,
                        nonce: wpVars.nonce,
                        autoChecker: 'valid',
                        crypt: '<?= $helper->ronik_encrypt_data_meta($user_id); ?>'
                    }, function(data) {
                        if (data.success && data.data !== 'noreload') {
                            alert("SMS code expired. Reloading...");
                            window.location.href = updateQuery('sms-error', 'expired');
                        }
                    }).fail(() => {
                        alert("Something went wrong.");
                        window.location.href = updateQuery('sms-error', 'error');
                    });

                    function updateQuery(key, value) {
                        const url = new URL(window.location.href);
                        url.searchParams.set(key, value);
                        return url.toString();
                    }
                }
            </script>
        </div>
        <?php
    } else {
        // 📨 No SMS sent yet — show "Send Code" button
        ?>
        <div class="auth-content-bottom auth-content-bottom--sms">
            <form class="auth-content-bottom__submit" action="<?= esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                <input type="hidden" name="send-sms" value="send-sms">
                <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                <span class="button-wrapper" style="margin-top: 0;">
                    <button type="submit" class="btn-submit">Send SMS Code</button>
                    <a class="btn-sso" href="<?= admin_url('admin-ajax.php?action=ronikdesigns_admin_logout'); ?>">Authenticate via NBCU SSO</a>
                </span>
            </form>
        </div>
        <?php
    }
});
?>
