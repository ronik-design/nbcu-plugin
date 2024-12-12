<?php

class RonikMoHelperDemoProcessor {

    public function dummyAttributes() {
        $attributes = [
            "email" => ["john2.doe@ronikdesign.com"],
            "Email" => ["john2.doe@ronikdesign.com"], // Alternative casing
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
        return $attributes;
    }
    public function dummyUserFlow($attributes=false) {
        error_log(print_r('dummyUserFlow', true));

        if(!$attributes){
            $attributes = $this->dummyAttributes();
        }
        $mo_helper = new RonikMoHelper();
        // Assume $mo_helper is an object and userFlowProcessor() is a method that processes the user flow
        $post_login_redirect = $mo_helper->userFlowProcessor($attributes);

        error_log(print_r('dummyUserFlow: '. $post_login_redirect, true));

        sleep(1);
        // Check if the result is valid (non-empty, non-false) and redirect
        wp_redirect( !empty($post_login_redirect) ? $post_login_redirect : home_url() );
        exit(); // Always call exit() after wp_redirect() to stop further execution
    }

            
}
