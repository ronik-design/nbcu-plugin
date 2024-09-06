<?php

if ( str_contains($_SERVER['REQUEST_URI'], '/wp-apxupx.php') || str_contains($_SERVER['REQUEST_URI'], '/wp-cron.php') || str_contains($_SERVER['REQUEST_URI'], '/wp-admin/') || str_contains($_SERVER['REQUEST_URI'], '/wp-content/')) {
    return false;
}

if( is_user_logged_in() ){
    return false;
}


function ronik_query_body_class($classes) {
    $out = "";
    $i = 0; /* for illustrative purposes only */

    foreach($_GET as $k => $v) {
        if($i == 0) {
            $out .= 'ronik-query|'.$k;
        } else {
            $out .= ' ronik-query|'.$k;
        }
        $i++;
    }
    $classes[] = $out;
    return $classes;
}
add_filter('body_class', 'ronik_query_body_class');

if ( str_contains(implode(",", get_body_class()), 'ronik-query')){
    return false;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$f_csp_enable = get_field('csp_enable', 'option');
$f_csp_disallow_url = get_field('csp_disallow-url', 'option');
$f_csp_disallow_query = get_field('csp_disallow-query', 'option');
$f_bypasser_enable = 'invalid';
$f_bypasser_enabled = true;
// $_POST['f_bypasser_enable'] = 'invalid';
// $_SESSION['f_bypasser_enable'] = 'invalid';



function bypasser_trigger( $bypasserType, $timeStamp, $bypasserHandle ){


    $currentDateTime = new DateTime(date('Y-m-d h:i:s', $timeStamp ));
    $datetime = new DateTime(date('Y-m-d h:i:s', $timeStamp));
    if( isset($_SESSION['f_bypasser_enable']) ){
        $datetime = new DateTime(date('Y-m-d h:i:s', $_SESSION['f_bypasser_enable']['time']));
    }
    $datetime->modify('+4 second');



    if( $currentDateTime->format('Y-m-d h:i:s') > $datetime->format('Y-m-d h:i:s') ){
        error_log(print_r( 'Time difference Expired' , true));

        // if($bypasserHandle !== $_SESSION['f_bypasser_enable']['location'] ){
            $_SESSION['f_bypasser_enable'] = array(
                "bypasserType" => $bypasserType,
                "time" => time(),
                "location" => $bypasserHandle
            );

            error_log(print_r( $_SESSION['f_bypasser_enable'] , true));
            error_log(print_r('post' . $_SESSION['f_bypasser_enable']['location']   , true));


        // }
    } else {
        error_log(print_r( 'Time difference Not Expired' , true));

    }


}

if($f_csp_disallow_url){
    foreach($f_csp_disallow_url as $disallow_url ){
        $santizeDisallowUrlSecure = str_replace(home_url('', 'https' ), "", $disallow_url['handle']);
        $santizeDisallowUrl = str_replace(home_url('', 'http' ), "", $disallow_url['handle']);

        if( isset($_SERVER['REQUEST_URI']) ){
            if( $santizeDisallowUrlSecure == $_SERVER['REQUEST_URI'] || $santizeDisallowUrl == $_SERVER['REQUEST_URI'] ){
                $f_bypasser_enable .= ',valid';
                error_log(print_r( 'a' , true));
                bypasser_trigger( "valid", time(), $disallow_url['handle']);
            }
        }
        if( isset($_SERVER['HTTP_REFERER']) ){
            $santizeRefererUrlSecure = str_replace(home_url('', 'https' ), "", $_SERVER['HTTP_REFERER']);
            $santizeRefererUrl = str_replace(home_url('', 'http' ), "", $_SERVER['HTTP_REFERER']);
            if( $santizeRefererUrlSecure == $santizeDisallowUrlSecure || $santizeRefererUrl == $santizeDisallowUrl ){
                $f_bypasser_enable .= ',valid';
                error_log(print_r( 'c' , true));
                bypasser_trigger( "valid", time(), $disallow_url['handle']);
            }
        }
    }
}


if($f_csp_disallow_query){
    foreach($f_csp_disallow_query as $disallow_query ){
        if( isset($_SERVER['REQUEST_URI']) ){
            if (str_contains($_SERVER['REQUEST_URI'], $disallow_query['handle'])) {
                $f_bypasser_enable .= ',valid';
                error_log(print_r( 'b' , true));
                bypasser_trigger( "valid", time(), $disallow_query['handle']);
            }
        }
        if( isset($_SERVER['HTTP_REFERER']) ){
            if (str_contains($_SERVER['HTTP_REFERER'], $disallow_query['handle'])) {
                $f_bypasser_enable .= ',valid';
                error_log(print_r( 'd' , true));
                bypasser_trigger( "valid", time(), $disallow_query['handle']);
            }
        }
        if( isset($_SERVER['QUERY_STRING']) ){
            if (str_contains($_SERVER['QUERY_STRING'], $disallow_query['handle'])) {
                $f_bypasser_enable .= ',valid';
                error_log(print_r( 'e' , true));
                bypasser_trigger( "valid", time(), $disallow_query['handle']);
            }
        }
        if( isset($_POST['point_origin']) ){
            if (str_contains($_POST['point_origin'], $disallow_query['handle'])) {
                $f_bypasser_enable .= ',valid';
                error_log(print_r( 'f' , true));
                bypasser_trigger( "valid", time(), $disallow_query['handle']);
            }
        }
    }
}





error_log(print_r('post f_bypasser_enable', true));
error_log(print_r($_SESSION['f_bypasser_enable']['bypasserType'], true));



$f_bypasser_enable_array = explode(",", $f_bypasser_enable);
if ( end($f_bypasser_enable_array) == 'invalid' ) {
    bypasser_trigger( "invalid", time(), 'fake');
} else {
    bypasser_trigger( "valid", time(), 'fake');
}



if($f_csp_enable){
    // Check post value
    if( $_SESSION['f_bypasser_enable']['bypasserType'] !== 'valid' ){
        // If post fails we check the session
            error_log(print_r('CSP Activated', true));
            /**
             * ENV_PATH
             * This is critcal for csp to work correctly.
             * We need to set the paths to all external links that are needed for the site to work properly.
             */
            define('ENV_PATH', get_site_url());
            // ALLOWABLE_FONTS
            $f_csp_allow_fonts = get_field('csp_allow-fonts', 'option');
            $csp_allow_fonts = " https://fonts.googleapis.com/ https://fonts.gstatic.com/  ";
            if ($f_csp_allow_fonts) {
                foreach ($f_csp_allow_fonts as $allow_fonts) {
                    $csp_allow_fonts .= $allow_fonts['link'] . ' ';
                }
            }



            function isUrlValid($url) {
                // Disable error reporting for file_get_contents
                $context = stream_context_create(['http' => ['ignore_errors' => true]]);
                // Fetch the URL content
                $content = file_get_contents($url, false, $context);
                // Get the response headers
                $headers = $http_response_header;
                // Check if the response code contains "404"
                foreach ($headers as $header) {

                    // if($url == 'https://static.ads-twitter.com/'){                        
                    //     error_log(print_r($url, true));
                    //     error_log(print_r($header, true));
                    //     error_log(print_r($content, true));
                    // }

                    if (!str_contains($content, 'Access Denied')) {                        
                        // Check to make sure response type is not application
                        if (!str_contains($header, 'Content-Type: application/')) {
                            // Check to make sure
                            if (!str_contains($header, 'HTTP/1.1 400')) {

                                if (str_contains($header, 'HTTP/1.1 40')) {
                                    return false; // URL is invalid or returns a 400 error
                                }
                                if (str_contains($header, 'HTTP/1.1 50')) {
                                    // error_log(print_r($url, true));
                                    // error_log(print_r($header, true));
                                    return false; // URL is invalid or returns a 500 error
                                }
                            }
                        }
                    }
                }
                return true; // URL is valid
             }




            // ALLOWABLE_SCRIPTS
            $f_csp_allow_scripts = get_field('csp_allow-scripts', 'option');
            // We automatically include the site url and blob data & some of the big companies urls...
            $csp_allow_scripts = "https://secure.gravatar.com/ https://0.gravatar.com/ https://google.com/ https://www.google.com/ https://www.google-analytics.com/ https://www.googletagmanager.com/ https://tagmanager.google.com https://ajax.googleapis.com/ https://googleads.g.doubleclick.net/ https://ssl.gstatic.com https://www.gstatic.com https://www.facebook.com/ https://connect.facebook.net/ https://twitter.com/ https://analytics.twitter.com/ https://t.co/ https://static.ads-twitter.com/ https://linkedin.com/ https://px.ads.linkedin.com/ https://px4.ads.linkedin.com/ https://player.vimeo.com/ https://www.youtube.com/ https://youtu.be/ ";
            if ($f_csp_allow_scripts) {
                foreach ($f_csp_allow_scripts as $allow_scripts) {
                    $csp_allow_scripts .= $allow_scripts['link'] . ' ';
                }
            }




            $csp_allow_fonts_scripts_santized = get_transient( 'csp_allow_fonts_scripts_santized' );
            // First check if the csp_allow_scripts_santized is empty..
            if(empty( $csp_allow_fonts_scripts_santized )){
                $csp_allow_fonts_scripts_reformatted = array_values(array_filter(explode(" ", $csp_allow_fonts)));
                $csp_allow_fonts_scripts_santized_r1 = '';
                if ($csp_allow_fonts) {
                    foreach ($csp_allow_fonts_scripts_reformatted as $allow_fonts_scripts) {
                        // Usage
                        $url = $allow_fonts_scripts;
                        if (isUrlValid($url)) {
                            // error_log(print_r("URL is valid.", true));
                            // error_log(print_r($url, true));
                            $csp_allow_fonts_scripts_santized_r1 .= $url . ' ';
                        } else {
                            error_log(print_r("URL is invalid or returns a 404 error.", true));
                            error_log(print_r($url, true));
                        }
                    }
                }
                $csp_allow_fonts_scripts_santized_r1 .=  site_url() . " blob: data: ";
                $csp_allow_fonts_scripts_santized_r2 = implode(' ',array_unique(explode(' ', $csp_allow_fonts_scripts_santized_r1)));
                // Expire the transient after a day or so..
                set_transient( 'csp_allow_fonts_scripts_santized', $csp_allow_fonts_scripts_santized_r2, DAY_IN_SECONDS );
                $csp_allow_fonts_scripts_santized = $csp_allow_fonts_scripts_santized_r2;
            }








            $csp_allow_scripts_santized = get_transient( 'csp_allow_scripts_santized' );
            // First check if the csp_allow_scripts_santized is empty..
            if(empty( $csp_allow_scripts_santized )){
                if ($csp_allow_scripts) {
                    $csp_allow_scripts_santized_r1 = '';
                    $csp_allow_scripts_reformatted = array_values(array_filter(explode(" ", $csp_allow_scripts)));
                    foreach ($csp_allow_scripts_reformatted as $allow_scripts) {
                        // Usage
                        $url = $allow_scripts;
                        if (isUrlValid($url)) {
                            // error_log(print_r("URL is valid.", true));
                            // error_log(print_r($url, true));
                            $csp_allow_scripts_santized_r1 .= $url . ' ';
                        } else {
                            error_log(print_r("URL is invalid or returns a 400 - 500 error.", true));
                            error_log(print_r($url, true));
                        }
                    }
                }
                $csp_allow_scripts_santized_r1 .= $csp_allow_fonts_scripts_santized;
                $csp_allow_scripts_santized_r2 = implode(' ', array_unique( explode(' ', $csp_allow_scripts_santized_r1 ) ) );
                // Expire the transient after a day or so..
                set_transient( 'csp_allow_scripts_santized', $csp_allow_scripts_santized_r2, DAY_IN_SECONDS );
                $csp_allow_scripts_santized = $csp_allow_scripts_santized_r2;
            }













            
            error_log(print_r('$csp_allow_fonts_scripts_santized ' . $csp_allow_fonts_scripts_santized, true));
            error_log(print_r('$csp_allow_scripts_santized ' . $csp_allow_scripts_santized, true));











            
            // Disallow scripts Defer.
            $f_csp_disallow_scripts_defer = get_field('csp_disallow-script-defer', 'option');
            define('DISALLOW_SCRIPTS_DEFER', $f_csp_disallow_scripts_defer);
            define('ALLOWABLE_FONTS', $csp_allow_fonts_scripts_santized);
            define('ALLOWABLE_SCRIPTS', $csp_allow_scripts_santized);
            /**
             * Custom Nonce
             * This is critcal for csp to work correctly.
             */
            if (false === ($csp_time = get_transient('csp_time_dilation'))) {
                $csp_time = time(); // Current timestamp.
                $csp_expire_time = rand(10, 100); // We add a random function between 10-100 seconds to the function. This will make it harder to predict the expiration of the nonce.
                set_transient('csp_time_dilation', $csp_time, $csp_expire_time);
            }
            // Based on wp not having a true nonce function... we add a time stamp to the nonce name to auto create a new one after certain amout of time has passed. Not ideal but better than 24 hours or 12 hours.
            define('CSP_NONCE', wp_create_nonce('csp_nonce_' . $csp_time));

            /**
             * Add a class to the body class.
             * Primary purpose is to let js know that csp is enabled
             */
            function ronikdesigns_body_class($classes)
            {
                $classes[] = 'csp-enabled';

                return $classes;
            }
            // add_filter('body_class', 'ronikdesigns_body_class');

            function hook_csp() {
                ?>
                <span data-csp="<?php echo CSP_NONCE; ?>" style="opacity:0;position:absolute;left:-3000px;top:-3000px;height:0;overflow:hidden;"></span>
                <?php
            }
            add_action('wp_head', 'hook_csp');


            /**
             * We only want to trigger when user is not logged in.
             * Due to the complexity of the wp admin interface.
             */
            if (!is_admin() && !is_user_logged_in()) {
                //Remove Gutenberg Block Library CSS from loading on the frontend
                function ronikdesigns_remove_wp_block_library_css()
                {
                    wp_dequeue_style('wp-block-library');
                    wp_dequeue_style('wp-block-library-theme');
                    wp_dequeue_style('wc-block-style'); // Remove WooCommerce block CSS
                }
                add_action('wp_enqueue_scripts', 'ronikdesigns_remove_wp_block_library_css', 100);
                // This retrieves all scripts and style handles
                function handle_retrieval($styles, $scripts)
                {
                    // all loaded Scripts
                    if ($scripts) {
                        global $wp_scripts;
                        return $wp_scripts->queue;
                    }
                    // all loaded Styles (CSS)
                    if ($styles) {
                        global $wp_styles;
                        return $wp_styles->queue;
                    }
                }
                // Move jQuery script to the footer instead of header.
                function ronikdesigns_jquery_to_footer()
                {
                    // wp_scripts()->add_data( 'jquery', 'group', 1 );
                    wp_scripts()->add_data('jquery-core', 'group', 1);
                    wp_scripts()->add_data('jquery-migrate', 'group', 1);
                }
                add_action('wp_enqueue_scripts', 'ronikdesigns_jquery_to_footer');
                //Remove JQuery migrate,
                function ronikdesigns_remove_jquery_migrate($scripts)
                {
                    if (!is_admin() && isset($scripts->registered['jquery'])) {
                        $script = $scripts->registered['jquery'];
                        if ($script->deps) { // Check whether the script has any dependencies
                            $script->deps = array_diff($script->deps, array(
                                'jquery-migrate'
                            ));
                        }
                    }
                }
                add_action('wp_default_scripts', 'ronikdesigns_remove_jquery_migrate');
                //Add preload to all enqueue styles.
                function ronikdesigns_add_preload_attribute($link, $handle)
                {
                    $all_styles = handle_retrieval(true, false); // A list of all the styles with handles.
                    $styles_to_preload = $all_styles;
                    # add the preload attribute to the css array and keep the original.
                    if ($styles_to_preload) {
                        foreach ($styles_to_preload as $i => $current_style) {
                            if (true == strpos($link, $current_style)) {
                                $org_link = $link;
                                // $mod_link = str_replace("rel='stylesheet'", "rel='preload' as='style'", $link);
                                $mod_link = str_replace(array("rel='stylesheet'", "id='"), array("rel='preload' rel='preconnect' as='style'", "id='pre-"), $link);
                                $link = $mod_link . $org_link;
                                return $link;
                            }
                        }
                    }
                }
                add_filter('style_loader_tag', 'ronikdesigns_add_preload_attribute', 10, 2);
                // Nonce external scripts
                add_filter('nonce_scripts', function ($scripts) {
                    $all_scripts = handle_retrieval(false, true);
                    return $all_scripts;
                });
                add_filter('script_loader_tag', function ($html, $handle) {
                    // CSP fix
                    // $nonce = wp_create_nonce( 'my-nonce' );
                    // $nonce = 'random123';
                    $deferHandles = apply_filters('nonce_scripts', []);
                    if (in_array($handle, $deferHandles)) {
                        $html = trim(str_replace("<script", '<script type="text/javascript" defer nonce="' . CSP_NONCE . '"', $html));
                    } else {
                        // Internal
                        $html = trim(str_replace("<script", '<script type="text/javascript" defer nonce="' . CSP_NONCE . '"', $html));
                    }

                    // Basically
                    if(DISALLOW_SCRIPTS_DEFER){
                        foreach(DISALLOW_SCRIPTS_DEFER as $key => $reject_script_defer){
                            if($reject_script_defer['handle'] == $handle){
                                $html = trim(str_replace("defer", "", $html));
                            }
                        }
                    }

                    return $html;
                }, 1, 2);

                // CSP fix.
                function additional_securityheaders($headers)
                {
                    $nonce = 'nonce-' . CSP_NONCE; // Assume CSP_NONCE is defined somewhere

                    // Default security headers
                    $headers['Referrer-Policy']             = 'no-referrer-when-downgrade'; 
                    $headers['X-Content-Type-Options']      = 'nosniff';
                    $headers['X-XSS-Protection']            = '1; mode=block';
                
                    // Permissions Policy to include encrypted-media
                    $headers['Permissions-Policy']          = 'browsing-topics=(), fullscreen=(self), geolocation=*, camera=(), encrypted-media=*';
                
                    // Content Security Policy
                    $headers['Content-Security-Policy']     = "default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'; ";
                    // $headers['Content-Security-Policy']    .= "script-src 'self' 'nonce-" . CSP_NONCE . "' 'strict-dynamic' https: http: script.crazyegg.com; ";
                    $headers['Content-Security-Policy'] .= "script-src '" . $nonce . "' 'strict-dynamic' 'unsafe-inline' 'unsafe-eval' https: 'self'; ";
                    // $headers['Content-Security-Policy']    .= "script-src-elem 'self' https: http: script.crazyegg.com; ";
                    $headers['Content-Security-Policy']     .= " script-src-elem 'unsafe-inline' " . ALLOWABLE_SCRIPTS . " https; ";

                    $headers['Content-Security-Policy']    .= "style-src 'self' 'unsafe-inline' https: " . ALLOWABLE_SCRIPTS . "; ";
                    $headers['Content-Security-Policy']    .= "img-src 'self' https: data: blob:; ";
                    $headers['Content-Security-Policy']    .= "font-src 'self' https: data:; ";
                    $headers['Content-Security-Policy']    .= "media-src 'self' https: data: blob:; ";
                    $headers['Content-Security-Policy']    .= "connect-src 'self' https: data: blob:; ";
                    $headers['Content-Security-Policy']    .= "object-src 'none'; ";
                    $headers['Content-Security-Policy']    .= "frame-src 'self' https: data: blob:; ";
                    $headers['Content-Security-Policy']    .= "frame-ancestors 'self'; ";
                    $headers['Content-Security-Policy']    .= "base-uri 'none'; ";
                
                    $headers['X-Frame-Options']             = 'SAMEORIGIN';



                    // $nonce = 'nonce-' . CSP_NONCE;

                    // // Default security headers
                    // $headers['Referrer-Policy']             = 'no-referrer-when-downgrade';
                    // $headers['X-Content-Type-Options']      = 'nosniff';
                    // $headers['X-XSS-Protection']            = '1; mode=block';
                    // $headers['Permissions-Policy']          = 'browsing-topics=(), fullscreen=(self "' . ENV_PATH . '"), geolocation=*, camera=()';
                
                    // // CSP header with backward compatibility
                    // $headers['Content-Security-Policy']     = "default-src 'self' data: blob:; ";
                    // $headers['Content-Security-Policy']    .= "script-src 'self' 'nonce-" . CSP_NONCE . "' 'unsafe-inline' 'strict-dynamic' https: http:; ";
                    // $headers['Content-Security-Policy']    .= "style-src 'self' 'unsafe-inline'; ";
                    // $headers['Content-Security-Policy']    .= "img-src 'self' data: blob:; ";
                    // $headers['Content-Security-Policy']    .= "font-src 'self' data:; ";
                    // $headers['Content-Security-Policy']    .= "media-src 'self' data: blob:; ";
                    // $headers['Content-Security-Policy']    .= "connect-src 'self' data: blob:; ";
                    // $headers['Content-Security-Policy']    .= "object-src 'none'; ";
                    // $headers['Content-Security-Policy']    .= "frame-src 'self' data: blob:; ";
                    // $headers['Content-Security-Policy']    .= "frame-ancestors 'self'; ";
                    // $headers['Content-Security-Policy']    .= "base-uri 'none'; "; // Added base-uri directive
                
                    // $headers['X-Frame-Options']             = 'SAMEORIGIN';


                    return $headers;
                }
                add_filter('wp_headers', 'additional_securityheaders', 1);

                function wporg_my_wp_script_attributes($attr)
                {
                    if (!isset($attr['nonce'])) {
                        $attr['nonce'] = CSP_NONCE; // Random custom function
                    }
                    return $attr;
                }
                add_filter('wp_script_attributes', 'wporg_my_wp_script_attributes');
                function mxd_wp_inline_script_attributes($attr)
                {
                    if (!isset($attr['nonce'])) {
                        $attr['nonce'] = CSP_NONCE;
                    }
                    return $attr;
                };
                add_filter('wp_inline_script_attributes', 'mxd_wp_inline_script_attributes');
            }
    }
}




