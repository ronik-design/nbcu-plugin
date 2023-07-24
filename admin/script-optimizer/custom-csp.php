<?php
$f_csp_enable = get_field('csp_enable', 'option');
if ($f_csp_enable) {
    /**
     * ENV_PATH
     * This is critcal for csp to work correctly.
     * We need to set the paths to all external links that are needed for the site to work properly.
     */
    define('ENV_PATH', get_site_url());
    // ALLOWABLE_FONTS
    $f_csp_allow_fonts = get_field('csp_allow-fonts', 'option');
    $csp_allow_fonts = " data: https://fonts.googleapis.com/ https://fonts.gstatic.com/  ";
    $csp_allow_fonts .= " " . ENV_PATH . " ";
    if ($f_csp_allow_fonts) {
        foreach ($f_csp_allow_fonts as $allow_fonts) {
            $csp_allow_fonts .= $allow_fonts['link'] . ' ';
        }
    }
    // ALLOWABLE_SCRIPTS
    $f_csp_allow_scripts = get_field('csp_allow-scripts', 'option');
    // We automatically include the site url and blob data & some of the big companies urls...
    $csp_allow_scripts = "https://secure.gravatar.com/ https://0.gravatar.com/ https://google.com/ https://www.google.com/ https://www.google-analytics.com/ https://www.googletagmanager.com/ https://tagmanager.google.com https://ajax.googleapis.com/ https://googleads.g.doubleclick.net/ https://ssl.gstatic.com https://www.gstatic.com https://www.facebook.com/ https://connect.facebook.net/ https://twitter.com/ https://analytics.twitter.com/ https://t.co/ https://static.ads-twitter.com/ https://linkedin.com/ https://px.ads.linkedin.com/ https://px4.ads.linkedin.com/ https://player.vimeo.com/ https://www.youtube.com/ https://youtu.be/" . site_url() . " blob: data: " . $csp_allow_fonts . " ";
    if ($f_csp_allow_scripts) {
        foreach ($f_csp_allow_scripts as $allow_scripts) {
            $csp_allow_scripts .= $allow_scripts['link'] . ' ';
        }
    }
    // Disallow scripts Defer.
    $f_csp_disallow_scripts_defer = get_field('csp_disallow-script-defer', 'option');
    define('DISALLOW_SCRIPTS_DEFER', $f_csp_disallow_scripts_defer);
    define('ALLOWABLE_FONTS', $csp_allow_fonts);
    define('ALLOWABLE_SCRIPTS', $csp_allow_scripts);
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
            // Need to redeclare csp with nonce-.
            $nonce = 'nonce-' . CSP_NONCE;
            $headers['Referrer-Policy']             = 'no-referrer-when-downgrade'; //This is the default value, the same as if it were not set.
            $headers['X-Content-Type-Options']      = 'nosniff';
            $headers['X-XSS-Protection']            = '1; mode=block';
            $headers['Permissions-Policy']          = 'fullscreen=(self "' . ENV_PATH . '"), geolocation=*, camera=()';
            //Note: In the presence of a CSP nonce the unsafe-inline directive will be ignored by modern browsers. Older browsers, which don't support nonces, will see unsafe-inline and allow inline scripts to execute. For site to work properly.
            $headers['Content-Security-Policy']     = " script-src '" . $nonce . "' 'strict-dynamic' 'unsafe-inline' 'unsafe-eval' https: 'self'; ";
            // $headers['Content-Security-Policy']      = " script-src 'strict-dynamic' 'unsafe-inline' 'unsafe-eval' https: 'self'; ";
            $headers['Content-Security-Policy']     .= " default-src 'strict-dynamic' 'unsafe-inline' 'unsafe-eval' https: 'self'; ";
            $headers['Content-Security-Policy']     .= " script-src-elem 'unsafe-inline' " . ALLOWABLE_SCRIPTS . " https; ";

            $headers['Content-Security-Policy']     .= " object-src 'none'; ";
            $headers['Content-Security-Policy']     .= " base-uri 'none'; ";

            $headers['Content-Security-Policy']     .= " child-src  'unsafe-inline' " . ALLOWABLE_SCRIPTS . " https; ";
            $headers['Content-Security-Policy']     .= " style-src  'unsafe-inline' " . ALLOWABLE_SCRIPTS . " https; ";

            $headers['Content-Security-Policy']     .= " font-src 'self'  " . ALLOWABLE_FONTS . ";  ";
            $headers['Content-Security-Policy']     .= " img-src 'self'  " . ALLOWABLE_SCRIPTS . ";  ";

            $headers['Content-Security-Policy']     .= " frame-src 'self'  " . ALLOWABLE_SCRIPTS . ";  ";

            $headers['Content-Security-Policy']     .= " report-uri " . ENV_PATH . "; ";

            $headers['X-Frame-Options']             = 'SAMEORIGIN';
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
