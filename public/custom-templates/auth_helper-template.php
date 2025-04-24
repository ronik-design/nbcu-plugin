<?php
get_header();
// http://together.nbcudev.local/auth_helper?ronik_debug=ronik_admin_auth&user_email=kevin@ronikdesign.com


$f_header = apply_filters( 'ronikdesign_auth_custom_header', false );
$f_mfa_settings = get_field( 'mfa_settings', 'options');

if($f_header){ 
    $f_header();
} ?>

	<div class="auth-wrapper" style="background-image: url('<?php echo $f_mfa_settings['auth_content_bgimage']['url']; ?>');">

		<div class="auth-content">
            <?php

                    $current_user = wp_get_current_user();
                    if (
                        (!in_array('administrator', (array) $current_user->roles, true)) &&
                        !is_super_admin()
                    ) {
                        echo 'Sorry, you do not have permission to access this page.';
                    } else {
                        if (class_exists('RonikAuthHelper')) {
                            $authHelper = new RonikAuthHelper;
                            $authHelper->auth_admin_messages();
                        } 
                    }
            
            ?>
		</div>
    </div>
<?php

$f_footer = apply_filters( 'ronikdesign_mfa_custom_footer', false );

if($f_footer){
        $f_footer();
}

get_footer();
