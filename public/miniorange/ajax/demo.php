<?php 
if( MO_DEMO !== 'valid' ) {
    return false;
}

// if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
//     wp_send_json_error('Security check failed');
//     wp_die();
// }

$attributes = [
    "email" => ["john2.doe@example.com"],
    "Email" => ["john2.doe@example.com"], // Alternative casing
    "firstname" => ["John2"],
    "FirstName" => ["John2"], // Alternative casing
    "lastname" => ["Doe2"],
    "LastName" => ["Doe2"], // Alternative casing
    "accountstatus" => ["active"], // Could be "active" or "inactive"
    "uid" => ["123456"],
    "UID" => ["123456"], // Alternative casing
    "jobtitle" => ["Software Engineer"],
    "telephonenumber" => ["123-456-7890"],
]; 


$mo_helper = new RonikMoHelper();

if( isset($_POST['auth-sso-login']) && $_POST['auth-sso-login'] == 'valid' ){
    $post_login_redirect = $mo_helper->userFlowProcessor($attributes);
} elseif( isset($_POST['auth-sso-logout']) && $_POST['auth-sso-logout'] == 'valid' ){
    $mo_helper->userFlowLogout('/');
}

if ($post_login_redirect !== 'invalid-redirect') {
    wp_send_json_success(['redirect' => $post_login_redirect]);
} else {
    wp_send_json_error("Invalid redirect URL. Please contact an administrator.");
}

wp_die(); // Important to end the AJAX request

