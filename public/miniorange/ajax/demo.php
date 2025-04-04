<?php 
if( $rk_bypasser_e_demo_mode == 'valid' && $rk_bypasser_which_environment !== 'live' ){
    return false;
}

$mo_helper = new RonikMoHelper();
$mo_helper_demo_processor = new RonikMoHelperDemoProcessor();
// error_log('Available cookies at dummyUserFlow: ' . print_r($_COOKIE, true));
$mo_helper_demo_processor->dummyUserFlow( false, true);
