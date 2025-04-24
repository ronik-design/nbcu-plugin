<?php
class RonikAuthHelper
{

    /**
     * Shows MFA/2FA debug panel for super admins using GET params only
     */
    public function auth_admin_messages()
    {

        $current_user = wp_get_current_user();
        if (
            (!in_array('administrator', (array) $current_user->roles, true)) &&
            !is_super_admin()
        ) {
            return false;
        }

        // Check if debug mode is active via GET
        if (!isset($_GET['ronik_debug']) || $_GET['ronik_debug'] !== 'ronik_admin_auth') {
            return false;
        }

        $current_user = wp_get_current_user();
        $f_user_id = $current_user->ID;

        // Support ?user_id=### or ?user_email=example@example.com
        if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
            $f_user_id = (int) $_GET['user_id'];
        } elseif (isset($_GET['user_email'])) {
            $user = get_user_by('email', sanitize_email($_GET['user_email']));
            if ($user) {
                $f_user_id = $user->ID;
            }
        }

        $get_auth_status = get_user_meta($f_user_id, 'auth_status', true) ?: 'none';
        $get_current_secret = get_user_meta($f_user_id, 'google2fa_secret', true);
        $sms_2fa_status = get_user_meta($f_user_id, 'sms_2fa_status', true);
        $sms_2fa_secret = get_user_meta($f_user_id, 'sms_2fa_secret', true);
        $get_auth_lockout_counter = get_user_meta($f_user_id, 'auth_lockout_counter', true) ?: 0;
        $get_phone_number = get_user_meta($f_user_id, 'sms_user_phone', true);
        $mfa_status = get_user_meta($f_user_id, 'mfa_status', true);
        $mfa_validation = get_user_meta($f_user_id, 'mfa_validation', true);

        $user = get_user_by('id', $f_user_id);
        $f_user_override = get_option('options_mfa_settings_user_override');
        $f_auth = get_field('mfa_settings', 'options');

        $f_redirect = $_SERVER['REQUEST_URI'] ?: '/auth_helper?ronik_debug=ronik_admin_auth';


?>
        <div class="dev-notice">
            <h4>Dev Message:</h4>
            <p>Welcome to the Frontend Dev Reset Center. This is a powerful tool that can reset and change all AUTH information.</p>


            <div class="dev-notice__wrapper">
                <div class="dev-notice__full dev-notice__center dev-notice__hr"></div>

                <div class="dev-notice__full dev-notice__center">
                    <h6>User Profile: Information</h6>
                    <br>
                </div>
                <div class="dev-notice__half">
                    <p>Current UserID: <?= esc_html($f_user_id); ?></p>
                </div>
                <div class="dev-notice__half">
                    <p>Current User Email: <?= esc_html($user->user_email); ?></p>
                </div>
                <br>

                <div class="dev-notice__full dev-notice__center dev-notice__hr"></div>

                <div class="dev-notice__full dev-notice__center">
                    <h6>Auth Status: <?= esc_html($get_auth_status); ?></h6>
                    <br>
                </div>

                <div class="dev-notice__half">
                    <p>MFA Current Secret: <?= esc_html($get_current_secret); ?></p>
                    <p>MFA Status: <?= esc_html($mfa_status); ?></p>
                    <p>MFA Validation: <?= esc_html($mfa_validation); ?></p>
                </div>

                <div class="dev-notice__half">
                    <p>SMS Phone Number: <?= esc_html($get_phone_number); ?></p>
                    <p>SMS Secret: <?= esc_html($sms_2fa_secret); ?></p>
                    <p>SMS Status: <?= esc_html($sms_2fa_status); ?></p>
                </div>

                <div class="dev-notice__full">
                    <p>Auth Lockout: <?= esc_html($get_auth_lockout_counter); ?></p>
                </div>

                <?php if (!empty($f_auth['enable_2fa_settings']) && !empty($f_auth['enable_mfa_settings'])) : ?>

                    <form action="<?= esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <input type="hidden" name="user-id" value="<?= esc_attr($f_user_id); ?>">
                        <?php
                        $checkbox_status = (strpos($f_user_override, $user->user_email) !== false) ? 'checked' : 'notchecked';
                        ?>

                        <div class="dev-notice__full dev-notice__center dev-notice__hr"></div>

                        <div class="dev-notice__full dev-notice__center">
                            <h6><?= $checkbox_status === 'checked' ? 'Current user is on Bypass Auth Verification' : 'Current user is not on Bypass Auth Verification'; ?></h6>
                            <br>
                        </div>

                        <div class="dev-notice__half">
                            <input style="opacity: .5; pointer-events: none; width:100%; font-size: 1.5em;" type="text" name="bypass-auth-list" value="<?= esc_attr($user->user_email); ?>">
                        </div>

                        <div class="dev-notice__half">
                            <button type="submit"><?= $checkbox_status === 'checked' ? 'Remove from Bypass List' : 'Add to Bypass List'; ?></button>
                        </div>

                        <div class="dev-notice__full dev-notice__center">
                            <br>
                            <p>Warning: this will auto bypass the user from future MFA/2FA authorization.</p>
                        </div>


                        <input type="hidden" name="bypass-auth-redirect" value="<?= esc_attr($f_redirect); ?>">
                        <input type="hidden" name="bypass-auth-verification" value="HELPER_RESET">
                        <input type="hidden" name="bypass-auth-verification-status" value="<?= esc_attr($checkbox_status); ?>">
                    </form>

                    <form action="<?= esc_url(admin_url('admin-ajax.php')); ?>" method="post">

                        <div class="dev-notice__full dev-notice__center dev-notice__hr"></div>

                        <div class="dev-notice__full dev-notice__center">
                            <h6>Current User Phone Number: <?= esc_attr($get_phone_number); ?></h6>
                            <br>
                        </div>

                        <div class="dev-notice__full dev-notice__center">
                            <br>
                        </div>
                        <div class="dev-notice__half">
                            <input style="height: 33px; font-size: 1.5em;" type="text" name="change-phone-number" value="<?= esc_attr($get_phone_number); ?>">
                        </div>
                        <div class="dev-notice__half">
                            <button type="submit">Change Phone Number</button>
                        </div>
                        <div class="dev-notice__full dev-notice__center">
                            <p>Note: +1 is necessary and please no dashes.</p>
                        </div>

                        <input type="hidden" name="bypass-auth-redirect" value="<?= esc_attr($f_redirect); ?>">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <input type="hidden" name="user-id" value="<?= esc_attr($f_user_id); ?>">
                        <input type="hidden" name="re-phone-number" value="HELPER_RESET">
                    </form>



                    <form class="dev-notice__full" action="<?= esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <input type="hidden" name="user-id" value="<?= esc_attr($f_user_id); ?>">
                        <input type="hidden" name="re-auth-options" value="HELPER_RESET">
                        <input type="hidden" name="bypass-auth-redirect" value="<?= esc_attr($f_redirect); ?>">

                        <label for="auth-option">Select Option:</label>
                        <select name="auth-option" id="auth-option">
                            <?php if ($get_phone_number) { ?>
                                <option value="auth_select_sms" <?= ($get_auth_status === 'auth_select_sms') ? 'selected' : ''; ?>>SMS</option>
                            <?php } else { ?>
                                <option value="auth_select_sms-missing" <?= ($get_auth_status === 'auth_select_sms-missing') ? 'selected' : ''; ?>>SMS Missing</option>
                            <?php } ?>
                            <option value="auth_select_mfa" <?= ($get_auth_status === 'auth_select_mfa') ? 'selected' : ''; ?>>MFA</option>
                            <option value="none" <?= ($get_auth_status === 'none') ? 'selected' : ''; ?>>None</option>
                        </select>

                        <br>
                        <button type="submit">Change Auth Selection</button>
                    </form>

                    <form class="dev-notice__third" action="<?= esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <input type="hidden" name="user-id" value="<?= esc_attr($f_user_id); ?>">
                        <input type="hidden" name="reset-lockout" value="HELPER_RESET">
                        <input type="hidden" name="bypass-auth-redirect" value="<?= esc_attr($f_redirect); ?>">

                        <button type="submit">Reset Auth Lockout</button>
                    </form>

                    <form class="dev-notice__third" action="<?= esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                        <input type="hidden" name="action" value="ronikdesigns_admin_auth_verification">
                        <?php wp_nonce_field('ajax-nonce', 'nonce'); ?>
                        <input type="hidden" name="user-id" value="<?= esc_attr($f_user_id); ?>">
                        <input type="hidden" name="reset-entire-auth" value="HELPER_RESET">
                        <input type="hidden" name="bypass-auth-redirect" value="<?= esc_attr($f_redirect); ?>">

                        <button type="submit">Reset Entire Auth</button>
                    </form>

                <?php endif; ?>
            </div>

        </div>
<?php
    }
}
