
<?php 

// This creates the cookie bridge page for redirect after setting SSO cookie
function ronikdesigns_add_sso_cookie_bridge_page() {
    if ( !ronikdesigns_get_page_by_title('sso_cookie_bridge') ) {
        // Create post object
        $bridge_post = array(
            'post_title'    => wp_strip_all_tags('sso_cookie_bridge'),
            'post_content'  => 'sso_cookie_bridge',
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'page',
            // Assign page template
            'page_template' => dirname(__FILE__, 2) . '/custom-templates/sso_cookie_bridge-template.php'
        );
        // Insert the post into the database
        wp_insert_post($bridge_post);
    }
}
ronikdesigns_add_sso_cookie_bridge_page();

function ronikdesigns_reserve_page_template_sso_cookie_bridge($page_template) {
    if (is_page('sso_cookie_bridge')) {
        $page_template = dirname(__FILE__, 2) . '/custom-templates/sso_cookie_bridge-template.php';
    }
    return $page_template;
}
add_filter('template_include', 'ronikdesigns_reserve_page_template_sso_cookie_bridge', 99);



// This creates the cookie bridge page for redirect after setting SSO cookie
function ronikdesigns_add_auth_helper_page() {
    if ( !ronikdesigns_get_page_by_title('auth_helper') ) {
        // Create post object
        $bridge_post = array(
            'post_title'    => wp_strip_all_tags('auth_helper'),
            'post_content'  => 'auth_helper',
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'page',
            // Assign page template
            'page_template' => dirname(__FILE__, 2) . '/custom-templates/auth_helper-template.php'
        );
        // Insert the post into the database
        wp_insert_post($bridge_post);
    }
}
ronikdesigns_add_auth_helper_page();

function ronikdesigns_reserve_page_template_auth_helper($page_template) {
    if (is_page('auth_helper')) {
        $page_template = dirname(__FILE__, 2) . '/custom-templates/auth_helper-template.php';
    }
    return $page_template;
}
add_filter('template_include', 'ronikdesigns_reserve_page_template_auth_helper', 99);




// This basically creates the password reset page.
function ronikdesigns_add_custom_saml_sso_page() {
    if( !ronikdesigns_get_page_by_title('saml_user_login_custom') ){
        // Create post object
        $my_post = array(
            'post_title'    => wp_strip_all_tags( 'saml_user_login_custom' ),
            'post_content'  => 'saml_user_login_custom',
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'page',
            // Assign page template
            'page_template'  => dirname( __FILE__ , 2).'/custom-templates/saml_user_login_custom-template.php'
        );
        // Insert the post into the database
        wp_insert_post( $my_post );
    }
}
ronikdesigns_add_custom_saml_sso_page();

// Lets add the password reset template.
function ronikdesigns_reserve_page_template_sso_custom( $page_template ){
    // If the page is password reset we add our custom ronik 2fa-template to the page.
    if ( is_page( 'saml_user_login_custom' ) ) {
        $page_template =  dirname( __FILE__ , 2).'/custom-templates/saml_user_login_custom-template.php';
    }
    return $page_template;
}
add_filter( 'template_include', 'ronikdesigns_reserve_page_template_sso_custom', 99 );
