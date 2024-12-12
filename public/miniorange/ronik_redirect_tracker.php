
<?php 

function ronik_sso_init() { 
    $mo_helper_cookie_processor = new RonikMoHelperCookieProcessor();

    $mo_helper = new RonikMoHelper();
    $mo_helper_redirect = new RonikMoHelperRedirect();
    [
        $site_production_request, 
        $site_staging_request, 
        $site_local_request, 
        $site_production_talentroom, 
        $site_staging_talentroom, 
        $site_local_talentroom, 
        $site_production_together, 
        $site_staging_together, 
        $site_local_together, 
        $blog_id_together, 
        $blog_id_talent, 
        $blog_id_request,
        $site_production_route_domain, 
        $site_staging_route_domain, 
        $site_local_route_domain
    ] = $mo_helper->siteAssigner();

    $site_mapping = [
        'production' => ['route' => $site_production_route_domain, 'request' => $site_production_request, 'talentroom' => $site_production_talentroom, 'together' => $site_production_together],
        'stage' => ['route' => $site_staging_route_domain, 'request' => $site_staging_request, 'talentroom' => $site_staging_talentroom, 'together' => $site_staging_together],
        'local' => ['route' => $site_local_route_domain, 'request' => $site_local_request, 'talentroom' => $site_local_talentroom, 'together' => $site_local_together]
    ];

    // Get the environment (local, staging, production) based on server name
    $environment = $mo_helper->getEnvironment($_SERVER['SERVER_NAME']);

    // Get the route domain
    $route_domain = $site_mapping[$environment]['route'];
    



    if (!headers_sent()) {
        error_log("Setting cookie...");
        $url_set = setcookie('testetstestest', 'aaaaaaa', time() + 3600, '/', $route_domain);
    } else {
        error_log("Headers already sent!");
    }




    // List of ignored URLs or URL patterns
    $ignoreUrls = [
        '/wp-admin/admin-ajax.php',    // Example ignored URL
        '/wp-admin/',
        '/wp-cron.php',
        'aj.php',
        '/robots.txt',
        '/nbcuni-sso/',
        '/wp-json/',
        '/wp-signup.php',
        'wp-login.php',
        '.txt',
        '/sitemap.xml',
        'saml_user_login_custom'       // Substring to check in the current path
    ];
    // Get the full request URI (including the query string)
    $currentUri = $_SERVER['REQUEST_URI'];

    // Flag to check if we should ignore redirection
    $shouldIgnoreRedirect = false;

    // Loop through the ignore list and check if any of the items are part of the full URI (path + query)
    foreach ($ignoreUrls as $ignoreUrl) {
        if (strpos($currentUri, $ignoreUrl) !== false) {
            // If the current URI contains an ignored URL, skip redirection
            $shouldIgnoreRedirect = true;
            break;  // No need to continue checking if we already found a match
        }
    }

    // If we should ignore the redirect, log and reset necessary variables
    if ($shouldIgnoreRedirect) {
        if (strpos($currentUri, 'saml_user_login_custom') !== false) {


            if (!isset($_COOKIE['sso_post_login_redirect_data']) && !$_COOKIE['sso_post_login_redirect_data']) {
                error_log(print_r('COOKIE DOESNT EXIST', true));

                $sso_post_login_redirect_data = [];
                $sso_post_login_redirect_data['site_origin'] = get_home_url();
                $url_set = setcookie('sso_post_login_redirect_data', urlencode(json_encode($sso_post_login_redirect_data)), time() + 3600, '/', $route_domain, true, true);

            }

            error_log(print_r('COOKIE'  , true ));
            error_log(print_r($url_set   , true ));



            // Have to throttle the redirect
            // sleep(10);
            error_log('saml_user_login_custom Redirect' . $currentUri);
            // Construct the base redirect URL
            $redirect_url = 'home?option=saml_user_login';
            // Perform the redirect with the query parameters
            // wp_redirect( esc_url(home_url($redirect_url)) );
            // exit; // Always call exit after a redirect to prevent further execution
        }
        // error_log('Ignoring redirect due to match in ignoreUrls: ' . $currentUri);
        $sso_post_login_redirect_cookie = null; // No redirect
        $sso_post_login_redirect_site_origin = null; // No Site Origin
    } else {
        // Try to get the site URL from WordPress or server settings
        $sso_post_login_redirect_site_origin = get_home_url() 
            ?? $_SERVER['SERVER_NAME'] 
            ?? $_SERVER['HTTP_HOST'] 
            ?? 'https://stage.together.nbcuni.com/';  // Default fallback value

        // Check if the redirect is stored in the cookie
        $sso_post_login_redirect_cookie = $_COOKIE['sso_post_login_redirect'] ?? null;
        if ($sso_post_login_redirect_cookie) {
            $sso_post_login_redirect_cookie = urldecode($sso_post_login_redirect_cookie);
        } else {
            // Check for 'r' or 'wl-register' in the query parameters
            $redirectParam = $_GET['r'] ?? $_GET['wl-register'] ?? null;
            if ($redirectParam) {
                // Clean the redirect value: ensure it's just the value of 'r' (not the entire query string)
                if (strpos($redirectParam, '/') === 0) {
                    // If the 'r' param value is a relative path (starts with /), keep it
                    $sso_post_login_redirect_cookie = $redirectParam;
                } else {
                    // If the 'r' param has a full URL (like http:// or https://), strip the domain and keep only the path
                    $sso_post_login_redirect_cookie = parse_url($redirectParam, PHP_URL_PATH); // Extract only the path
                }
            } else {
                // Otherwise, use the current URI and strip out other query parameters
                $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
                parse_str($parsedUrl['query'] ?? '', $queryParams);
                // Only keep 'r' or 'wl-register' if they exist in the URL query
                $sso_post_login_redirect_cookie = $queryParams['r'] ?? $queryParams['wl-register'] ?? $parsedUrl['path'];
            }
        }


        $mo_helper_cookie_processor->cookieSsoGenerator( $sso_post_login_redirect_site_origin , $sso_post_login_redirect_cookie, $route_domain, true);
    }


    $mo_helper_cookie_processor->cookieSsoFetcher('sso_post_login_redirect_data');


}
// add_action('template_redirect', 'ronik_sso_init');









// function ronik_sso_init_new(){
//     // if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['option']) && $_GET['option'] === 'saml_user_login_custom') {

//     // }

//     $mo_helper_cookie_processor = new RonikMoHelperCookieProcessor();
//     $test = $mo_helper_cookie_processor->cookieSsoFetcher('sso_post_login_redirect_data');

//     error_log( print_r( 'ronik_sso_init_new', true) );
//     error_log( print_r( $test, true) );
// }
// add_action('template_redirect', 'ronik_sso_init_new');







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
