<?php

class RonikMoHelperCipher {
    public function decryptLoginRequest($data) {
        // Helper Guide
        $helper = new RonikHelper;
        $user_id = $helper->ronik_decrypt_data($data , 300);

        error_log(print_r('decryptLoginRequest', true));
        error_log(print_r($user_id, true));

        if($user_id){
            $author_obj = get_user_by('id', $user_id);
            if ($author_obj) {
                wp_set_current_user($user_id, $author_obj->user_login);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $author_obj->user_login, $author_obj);
                // return $site_mapping[$environment]['talentroom'].'/talent';
            }
        }
    }
}
