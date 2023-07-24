<?php

// This action is used to save custom fields that have been added to the WordPress profile page.
function ronikdesigns_save_extra_user_profile_fields_auth($user_id){
    if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)) {
        return;
    }
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    update_user_meta($user_id, 'auth_status', $_POST['auth_select']);
    update_user_meta($user_id, 'mfa_status', $_POST['google2fa_code']);
    update_user_meta($user_id, 'sms_2fa_status', $_POST['sms_code']);
    update_user_meta($user_id, 'sms_user_phone', $_POST['sms_phonenumber']);

}
add_action('personal_options_update', 'ronikdesigns_save_extra_user_profile_fields_auth');
add_action('edit_user_profile_update', 'ronikdesigns_save_extra_user_profile_fields_auth');


// Per each user
function ronikdesigns_extra_user_profile_fields_auth($user){
    if(isset($_GET["user_id"])){
        $get_auth_status = get_user_meta($_GET["user_id"], 'auth_status', true);
        if(!$get_auth_status){
            $get_auth_status = 'none';
        }

        $get_registration_status = get_user_meta($_GET["user_id"], 'mfa_status', true);
        if(!$get_registration_status){
            $get_registration_status = 'mfa_unverified';
        }
        // SMS Status
        $get_sms_status = get_user_meta($_GET["user_id"], 'sms_2fa_status', true);
        if(!$get_sms_status){
            $get_sms_status = 'sms_unverified';
        }
        // Get user phone number.
        $get_phone_number = get_user_meta($_GET["user_id"], 'sms_user_phone', true);
        if(!$get_phone_number){
            $get_phone_number = false;
        }
    } else {
        // Last chance to get registration status
        if(isset($_GET["user"])){
            $get_registration_status = get_user_meta($_GET["user"], 'mfa_status', true);
            if(!$get_registration_status){
                $get_registration_status = 'mfa_unverified';
            }
            // SMS Status
            $get_sms_status = get_user_meta($_GET["user"], 'sms_2fa_status', true);
            if(!$get_sms_status){
                $get_sms_status = 'sms_unverified';
            }
            // Get user phone number.
            $get_phone_number = get_user_meta($_GET["user_id"], 'sms_user_phone', true);
            if(!$get_phone_number){
                $get_phone_number = false;
            }
        } else {
            // User is matching with there own account.
            if( $_SERVER['REQUEST_URI'] == '/wp-admin/profile.php' ){
                $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);
                if(!$get_auth_status){
                    $get_auth_status = 'none';
                }
                $get_registration_status =  get_user_meta(get_current_user_id(), 'mfa_status', true);
                if(!$get_registration_status){
                    $get_registration_status = 'mfa_unverified';
                }
                $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);
                if(!$get_phone_number){
                    $get_phone_number = false;
                }
                $get_sms_status = get_user_meta(get_current_user_id(), 'sms_2fa_status', true);
                if(!$get_sms_status){
                    $get_sms_status = 'sms_unverified';
                }
            // All else fails set everything to false or invalid.
            } else {
                $get_auth_status = 'none';
                $get_registration_status = 'mfa_unverified';
                $get_phone_number = false;
                $get_sms_status = 'sms_unverified';
            }
        }
    }

?>
    <h3><?php _e("Extra profile information", "blank"); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="auth_select"><?php _e("User Auth Selection"); ?></label></th>
            <td>
                <select name="auth_select" id="auth_select">
                    <option value="auth_select_mfa" <?php if ($get_auth_status == 'auth_select_mfa') { ?>selected="selected" <?php } ?>>auth_select_mfa</option>
                    <option value="auth_select_sms" <?php if ($get_auth_status == 'auth_select_sms') { ?>selected="selected" <?php } ?>>auth_select_sms</option>
                    <option value="none" <?php if ($get_auth_status == 'none') { ?>selected="selected" <?php } ?>>none</option>
                </select>
            </td>
        </tr>

        <tr>
            <th><label for="google2fa_code"><?php _e("Google 2fa"); ?></label></th>
            <td>
                <select name="google2fa_code" id="google2fa_code">
                    <option value="mfa_verified" <?php if ($get_registration_status == 'mfa_verified') { ?>selected="selected" <?php } ?>>mfa_verified</option>
                    <option value="mfa_unverified" <?php if ($get_registration_status == 'mfa_unverified') { ?>selected="selected" <?php } ?>>mfa_unverified</option>
                </select>
            </td>
        </tr>

        <tr>
            <th><label for="sms_code"><?php _e("SMS 2fa"); ?></label></th>
            <td>
                <select name="sms_code" id="sms_code">
                    <option value="sms_verified" <?php if ($get_sms_status == 'sms_verified') { ?>selected="selected" <?php } ?>>sms2fa_verified</option>
                    <option value="sms_unverified" <?php if ($get_sms_status == 'sms_unverified') { ?>selected="selected" <?php } ?>>sms2fa_unverified</option>
                </select>
            </td>
        </tr>

        <?php if($get_phone_number){ ?>
            <tr>
                <th><label for="sms_phonenumber"><?php _e("SMS 2fa Phone Number"); ?></label></th>
                <td>
                    <input type="text" id="sms_phonenumber" name="sms_phonenumber" value="<?= $get_phone_number; ?>"><br><br>
                </td>
            </tr>
        <?php } else { ?>
            <tr>
                <th><label for="sms_phonenumber"><?php _e("SMS 2fa Phone Number"); ?></label></th>
                <td>
                    <input type="text" id="sms_phonenumber" name="sms_phonenumber" value=""><br><br>
                </td>
            </tr>
        <?php } ?>
    </table>
<?php }
add_action('show_user_profile', 'ronikdesigns_extra_user_profile_fields_auth');
add_action('edit_user_profile', 'ronikdesigns_extra_user_profile_fields_auth');