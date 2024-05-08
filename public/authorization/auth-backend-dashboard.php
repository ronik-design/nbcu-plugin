<?php

// This action is used to save custom fields that have been added to the WordPress profile page.
function ronikdesigns_save_extra_user_profile_fields_auth($user_id){
    if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)) {
        return;
    }
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    $helper = new RonikHelper;
    $helper->ronikdesigns_write_log_devmode('AUTH Backend Dashboard', 'low', 'auth');

    update_user_meta($user_id, 'auth_status', $_POST['auth_select']);
    update_user_meta($user_id, 'sms_user_phone', $_POST['sms_phonenumber']);

    if($_POST['auth_reset_lockdown'] == 'auth_not_locked'){
        delete_user_meta($user_id, 'auth_lockout_counter' );
    }
    if($_POST['auth_reset']){
        delete_user_meta($user_id, 'auth_status' );
        delete_user_meta($user_id, 'mfa_status' );
        delete_user_meta($user_id, 'mfa_validation' );
        delete_user_meta($user_id, 'sms_2fa_status' );
        delete_user_meta($user_id, 'sms_user_phone' );
        delete_user_meta($user_id, 'sms_2fa_secret' );
        delete_user_meta($user_id, 'google2fa_secret' );
        delete_user_meta($user_id, 'sms_code_timestamp' );
        delete_user_meta($user_id, 'auth_lockout_counter' );
    }

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
        $get_mfa_status =  get_user_meta($_GET["user_id"], 'mfa_status', true);
        if(!$get_mfa_status){
            $get_mfa_status = 'mfa_unverified';
        }
        $get_phone_number = get_user_meta($_GET["user_id"], 'sms_user_phone', true);
        if(!$get_phone_number){
            $get_phone_number = false;
        }
        $get_sms_status = get_user_meta($_GET["user_id"], 'sms_2fa_status', true);
        if(!$get_sms_status){
            $get_sms_status = 'sms_2fa_unverified';
        }
        $get_sms_secret = get_user_meta($_GET["user_id"], 'sms_2fa_secret', true);
        if(!$get_sms_secret){
            $get_sms_secret = 'invalid';
        }
        $get_sms_code_timestamp = get_user_meta($_GET["user_id"], 'sms_code_timestamp', true);
        if(!$get_sms_code_timestamp){
            $get_sms_code_timestamp = 'invalid';
        }
        $get_mfa_secret = get_user_meta($_GET["user_id"], 'google2fa_secret', true);
        if(!$get_mfa_secret){
            $get_mfa_secret = '';
        }

        $get_mfa_validation = get_user_meta($_GET["user_id"], 'mfa_validation', true);
        if(!$get_mfa_validation){
            $get_mfa_validation = '';
        }
        $get_auth_lockout_counter = get_user_meta($_GET["user_id"], 'auth_lockout_counter', true);
        if(!$get_auth_lockout_counter){
            $get_auth_lockout_counter = '';
        }
    } else {
        // Last chance to get registration status
        if(isset($_GET["user"])){
            $get_auth_status = get_user_meta($_GET["user"], 'auth_status', true);
            if(!$get_auth_status){
                $get_auth_status = 'none';
            }
            $get_mfa_status =  get_user_meta($_GET["user"], 'mfa_status', true);
            if(!$get_mfa_status){
                $get_mfa_status = 'mfa_unverified';
            }
            $get_phone_number = get_user_meta($_GET["user"], 'sms_user_phone', true);
            if(!$get_phone_number){
                $get_phone_number = false;
            }
            $get_sms_status = get_user_meta($_GET["user"], 'sms_2fa_status', true);
            if(!$get_sms_status){
                $get_sms_status = 'sms_2fa_unverified';
            }
            $get_sms_secret = get_user_meta($_GET["user"], 'sms_2fa_secret', true);
            if(!$get_sms_secret){
                $get_sms_secret = 'invalid';
            }
            $get_sms_code_timestamp = get_user_meta($_GET["user"], 'sms_code_timestamp', true);
            if(!$get_sms_code_timestamp){
                $get_sms_code_timestamp = 'invalid';
            }
            $get_mfa_secret = get_user_meta($_GET["user"], 'google2fa_secret', true);
            if(!$get_mfa_secret){
                $get_mfa_secret = '';
            }

            $get_mfa_validation = get_user_meta($_GET["user"], 'mfa_validation', true);
            if(!$get_mfa_validation){
                $get_mfa_validation = '';
            }

            $get_auth_lockout_counter = get_user_meta($_GET["user"], 'auth_lockout_counter', true);
            if(!$get_auth_lockout_counter){
                $get_auth_lockout_counter = '';
            }

        } else {
            // User is matching with there own account.
            if( str_contains($_SERVER['REQUEST_URI'] , 'profile.php') ){
                $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);
                if(!$get_auth_status){
                    $get_auth_status = 'none';
                }
                $get_mfa_status =  get_user_meta(get_current_user_id(), 'mfa_status', true);
                if(!$get_mfa_status){
                    $get_mfa_status = 'mfa_unverified';
                }
                $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);
                if(!$get_phone_number){
                    $get_phone_number = false;
                }
                $get_sms_status = get_user_meta(get_current_user_id(), 'sms_2fa_status', true);
                if(!$get_sms_status){
                    $get_sms_status = 'sms_2fa_unverified';
                }
                $get_sms_secret = get_user_meta(get_current_user_id(), 'sms_2fa_secret', true);
                if(!$get_sms_secret){
                    $get_sms_secret = 'invalid';
                }
                $get_sms_code_timestamp = get_user_meta(get_current_user_id(), 'sms_code_timestamp', true);
                if(!$get_sms_code_timestamp){
                    $get_sms_code_timestamp = 'invalid';
                }
                $get_mfa_secret = get_user_meta(get_current_user_id(), 'google2fa_secret', true);
                if(!$get_mfa_secret){
                    $get_mfa_secret = '';
                }

                $get_mfa_validation = get_user_meta(get_current_user_id(), 'mfa_validation', true);
                if(!$get_mfa_validation){
                    $get_mfa_validation = '';
                }

                $get_auth_lockout_counter = get_user_meta(get_current_user_id(), 'auth_lockout_counter', true);
                if(!$get_auth_lockout_counter){
                    $get_auth_lockout_counter = '';
                }

            } else {
                $get_auth_status = 'none';
                $get_mfa_status = 'mfa_unverified';
                $get_mfa_validation = '';
                $get_phone_number = false;
                $get_sms_status = 'sms_2fa_unverified';
                $get_mfa_secret = '';
                $get_sms_secret = 'invalid';
                $get_sms_code_timestamp = 'invalid';
                $get_auth_lockout_counter = '';
            }
        }
    }

?>
    <h3><?php _e("MFA profile information", "blank"); ?></h3>
    <table class="form-table">
        <?php
        if( ($get_auth_lockout_counter) > 6){ ?>
            <tr>
                <th><label for="auth_reset_lockdown"><?php _e("Account Locked Down"); ?></label><p>The current user account is locked down!</p></th>
                <td>
                    <select name="auth_reset_lockdown" id="auth_reset_lockdown">
                        <option value="auth_is_locked" selected="selected">User is Locked</option>
                        <option value="auth_not_locked" >User is Not Locked</option>
                    </select>
                    <p>Change the selection and click update user to reset locked down mode.</p>
                </td>
            </tr>
        <?php } else { ?>
            <?php if($get_auth_lockout_counter){ ?>
                User has <?= $get_auth_lockout_counter; ?> failed attempts.
            <?php } else { ?>
                User has 0 failed attempts.
            <?php } ?>
        <?php } ?>
        <br>
        <tr>
            <th><label for="auth_reset"><?php _e("Auth Reset"); ?></label></th>
            <td>
                <input type="checkbox"  name="auth_reset" id="auth_reset"  />
                <p>Click the checkbox above and click "update" will reset all of user authorization preferences. </p>
            </td>
        </tr>
        <tr>
            <th><label for="auth_select"><?php _e("User Auth Selection"); ?></label></th>
            <td>
                <select name="auth_select" id="auth_select">
                    <option value="auth_select_mfa" <?php if ($get_auth_status == 'auth_select_mfa') { ?>selected="selected" <?php } ?>>auth_select_mfa</option>
                    <option value="auth_select_sms" <?php if ($get_auth_status == 'auth_select_sms') { ?>selected="selected" <?php } ?>>auth_select_sms</option>
                    <option value="none" <?php if ($get_auth_status == 'none') { ?>selected="selected" <?php } ?>>none</option>
                </select>
                <?php if ($get_auth_status == 'auth_select_mfa') { ?>
                    <strong style="color: green;">User has selected authenticator app</strong>
                <?php } else if($get_auth_status == 'auth_select_sms') { ?>
                    <strong style="color: green;">User has selected SMS</strong>
                <?php } else { ?>
                    <strong style="color: red;">User has not selected AUTH Preference</strong>
                <?php } ?>
                <p>Change the selection and click "update" to change the users preference.</p>
            </td>
        </tr>
        <tr <?= ($get_auth_status !== 'auth_select_mfa') ? 'style="opacity:.2;"' : ''; ?>>
            <th><label for="mfa_status"><?php _e("MFA Status"); ?></label></th>
            <td>
                <input disabled name="mfa_status" id="mfa_status" value="<?= $get_mfa_status; ?>">

                <?php   
                if($get_mfa_status !== 'mfa_unverified'){              
                        $f_auth_expiration_time = get_option('options_mfa_settings_auth_expiration_time');
                        $past_date_vis = strtotime((new DateTime(date('m/d/Y h:i:s', $get_mfa_status)))->modify('+'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                        $past_date = strtotime((new DateTime())->modify('-'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                    ?>
                    <p>User TimeStamp UTC: <br><?php echo date('m/d/Y h:i:s', $get_mfa_status); ?></p>
                    <p>AUTH Expiration TimeStamp UTC: <br><?php echo date('m/d/Y h:i:s', $past_date_vis); ?></p>

                    <br>
                    <p>User TimeStamp EST: <br><?php echo date('m/d/Y h:i:s', $get_mfa_status - 3600*4); ?></p>
                    <p>AUTH Expiration TimeStamp EST: <br><?php echo date('m/d/Y h:i:s', $past_date_vis - 3600*4); ?></p>

                    <?php 
                    if( $past_date > $get_mfa_status ){ ?>
                        <strong style="color: red;">User timestamp has expired</strong>
                    <?php } else { ?>
                        <strong style="color: green;">User timestamp is valid</strong>
                    <?php }
                } else { ?>
                    <strong style="color: red;">User timestamp has expired</strong>
                <?php } ?>
            </td>
        </tr>
        <tr <?= ($get_auth_status !== 'auth_select_mfa') ? 'style="opacity:.2;"' : ''; ?>>
            <th><label for="google2fa_secret"><?php _e("MFA Secret"); ?></label></th>
            <td>
                <input disabled name="google2fa_secret" id="google2fa_secret" value="<?= $get_mfa_secret; ?>">

                <?php if($get_mfa_secret && $get_auth_status == 'auth_select_mfa'){ ?>
                    <p>User Authorization Key to be placed in Authorization APP only! In case of emeregency this should never be reshared only to help end user reactivate there account.</p>
                <?php } ?>
            </td>
        </tr>
        <tr <?= ($get_auth_status !== 'auth_select_mfa') ? 'style="opacity:.2;"' : ''; ?>>
            <th><label for="mfa_validation"><?php _e("MFA Validation"); ?></label></th>
            <td>
                <input disabled name="mfa_validation" id="mfa_validation" value="<?= $get_mfa_validation; ?>">
                    <?php if($get_mfa_validation == 'not_registered' && $get_auth_status == 'auth_select_mfa'){ ?>
                        <strong style="color: red;">User has not scanned or entered their secret key into their authentication app, MFA registration is incomplete</strong>
                    <?php }
                    if($get_mfa_validation == 'valid' && $get_auth_status == 'auth_select_mfa'){ ?>
                        <strong style="color: green;">User has fully registered</strong>
                    <?php }
                ?>
            </td>
        </tr>
        <tr <?= ($get_auth_status !== 'auth_select_sms') ? 'style="opacity:.2;"' : ''; ?>>
            <th><label for="sms_status"><?php _e("SMS 2fa Status"); ?></label></th>
            <td>
                <input disabled name="sms_status" id="sms_status" value="<?= $get_sms_status; ?>">
                <?php if($get_sms_status == 'sms_2fa_unverified' ){ ?>
                    <strong style="color: red;">User auth has expired</strong>
                <?php } ?>
                <?php if($get_sms_status == 'sms_2fa_verified' ){ ?>
                    <strong style="color: green;">User has successfully submitted.</strong>
                <?php } ?>
            </td>
        </tr>
        <tr <?= ($get_auth_status !== 'auth_select_sms') ? 'style="opacity:.2;"' : ''; ?>>
            <th><label for="sms_secret"><?php _e("SMS 2fa Secret"); ?></label></th>
            <td>
                <input disabled name="sms_secret" id="sms_secret" value="<?= $get_sms_secret; ?>">
                <?php if($get_sms_secret == 'invalid' ){ ?>
                    <strong style="color: red;">User auth has expired</strong>
                <?php } ?>
                <p>SMS secret is more for the Twilio API to debug Twilio Errors </p>
            </td>
        </tr>
        <tr <?= ($get_auth_status !== 'auth_select_sms') ? 'style="opacity:.2;"' : ''; ?>>
            <th><label for="sms_code_timestamp"><?php _e("SMS 2fa Code Timestamp"); ?></label></th>
            <td>
                <input disabled name="sms_code_timestamp" id="sms_code_timestamp" value="<?= $get_sms_code_timestamp; ?>">
                <?php if($get_sms_code_timestamp == 'invalid' ){ ?>
                    <strong style="color: red;">Timestamp has expired or never been set!</strong>
                <?php } else { ?>
                    <?php if(!$f_auth_expiration_time){
                        $f_auth_expiration_time = 3;        
                    } ?>
                    <?php                         
                        $f_auth_expiration_time = get_option('options_mfa_settings_auth_expiration_time');
                        $f_expiration_time = get_option('options_mfa_settings_sms_expiration_time');
                        $past_date_vis = strtotime((new DateTime(date('m/d/Y h:i:s', $get_sms_code_timestamp)))->modify('+'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                        $past_date = strtotime((new DateTime())->modify('-'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                    ?>
                    <p>User TimeStamp UTC: <br><?php echo date('m/d/Y h:i:s', $get_sms_code_timestamp); ?></p>
                    <p>SMS Expiration TimeStamp UTC: <br><?php echo date('m/d/Y h:i:s', $past_date_vis); ?></p>

                    <br>
                    <p>User TimeStamp EST: <br><?php echo date('m/d/Y h:i:s', $get_sms_code_timestamp - 3600*4); ?></p>
                    <p>SMS Expiration TimeStamp EST: <br><?php echo date('m/d/Y h:i:s', $past_date_vis - 3600*4); ?></p>

                    <?php 
                    if( $past_date > $get_sms_code_timestamp ){ ?>
                        <strong style="color: red;">User timestamp has expired</strong>
                    <?php } else { ?>
                        <strong style="color: green;">User timestamp is valid</strong>
                    <?php } ?>
                    
                <?php } ?>
            </td>
        </tr>
        <?php if($get_phone_number){ ?>
            <tr <?= ($get_auth_status !== 'auth_select_sms') ? 'style="opacity:.2;"' : ''; ?>>
                <th><label for="sms_phonenumber"><?php _e("SMS 2fa Phone Number"); ?></label></th>
                <td>
                    <input type="text" id="sms_phonenumber" name="sms_phonenumber" value="<?= $get_phone_number; ?>"><br><br>
                </td>
            </tr>
        <?php } else { ?>
            <tr <?= ($get_auth_status !== 'auth_select_sms') ? 'style="opacity:.2;"' : ''; ?>>
                <th><label for="sms_phonenumber"><?php _e("SMS 2fa Phone Number"); ?></label></th>
                <td>
                    <input type="text" id="sms_phonenumber" name="sms_phonenumber" value=""><br><br>
                </td>
            </tr>
        <?php } ?>
    </table>
<?php }
add_action('show_user_profile', 'ronikdesigns_extra_user_profile_fields_auth', 99, 1 );
add_action('edit_user_profile', 'ronikdesigns_extra_user_profile_fields_auth', 99, 1 );
