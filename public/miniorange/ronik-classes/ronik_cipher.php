<?php

class RonikMoHelperCipher {
    public function encryptLoginRequest($key, $base_url, $userid) {
        return $base_url . rawurlencode($this->myEncrypt(json_encode(['ui' => $userid, 'time' => time()]), $key));
    }

    public function myEncrypt($data, $key) {
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', base64_decode($key), 0, $iv);
        if ($encrypted === false) {
            throw new Exception('Encryption failed.');
        }
        return base64_encode($encrypted . '::' . $iv);
    }

    public function decryptLoginRequest($key) {
        $mo_helper = new RonikMoHelper();
        $site_mapping = $mo_helper->siteAssigner();
        $environment = $mo_helper->getEnvironment($_SERVER['SERVER_NAME']);
        $password_encrypted = rawurldecode($_GET["sso-rk-log"]);
        $password_decrypted = $this->myDecrypt($password_encrypted, $key);

        if ($password_decrypted === false) {
            return false;
        }
        $piecesArray = json_decode($password_decrypted, true);
        if (!isset($piecesArray['ui'], $piecesArray['time'])) {
            return false;
        }
        $user_id = $piecesArray['ui'];
        $timestamp = $piecesArray['time'];
        $dif = time() - $timestamp;
        if ($dif > 60) {
            return false;
        }
        $previous_dynamic_user_login_url = get_user_meta($user_id, 'dynamic_user_login_url', true);
        $previous_dynamic_user_login_url_array = explode(",", $previous_dynamic_user_login_url);
        if (in_array($password_encrypted, $previous_dynamic_user_login_url_array)) {
            return false;
        }
        $updated_dynamic_user_login_url = empty($previous_dynamic_user_login_url) 
            ? $password_encrypted 
            : rtrim($previous_dynamic_user_login_url, ',') . ',' . $password_encrypted;
        update_user_meta($user_id, 'dynamic_user_login_url', $updated_dynamic_user_login_url);
        $author_obj = get_user_by('id', $user_id);
        if ($author_obj) {
            wp_set_current_user($user_id, $author_obj->user_login);
            wp_set_auth_cookie($user_id);
            do_action('wp_login', $author_obj->user_login, $author_obj);
            return $site_mapping[$environment]['talentroom'].'/talent';
        }
    }

    private function myDecrypt($data, $key) {
        $encryption_key = base64_decode($key);
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
    }
}
