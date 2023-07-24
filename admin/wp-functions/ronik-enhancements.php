<?php

function ronikdesigns_header_manipulation() {
    $baseDir = get_bloginfo('stylesheet_directory');

    // Lets get the contents of the generic header.php file
    // get_theme_file_path we have to get the absolute path due to file_get_contents flaws
    $get_header_content = file_get_contents(get_theme_file_path().'/'.'header.php');
    
    // First lets check that the manifest color is set...
    $f_manifest_theme_color = get_field('manifest_theme_color', 'options');
    if($f_manifest_theme_color){
        $f_theme_color = str_contains($get_header_content, 'theme-color');
        // If the theme color is not set we set the theme color...
        // This will reduce the event of double meta names...
        if(!$f_theme_color){
            echo '<meta name="theme-color" content='.$f_manifest_theme_color.' />';
        }
    }

    // Add in the manifest.json file..
    echo '<link rel="manifest" href="'.$baseDir.'/manifest.json">';
}
add_action( 'wp_head', 'ronikdesigns_header_manipulation' );




// Enable rest api route for service workers.
$f_custom_js_settings = get_field('custom_js_settings', 'option');

if(isset($f_custom_js_settings['enable_serviceworker']) && $f_custom_js_settings['enable_serviceworker']){
    //* delete transient
    function delete_custom_transient(){
        delete_transient('frontend-script-loader');
    }
    add_action('update option', 'delete_custom_transient');
    add_action('save_post', 'delete_custom_transient');
    add_action('delete_post', 'delete_custom_transient');
    
    // Lets store all the styles and scripts inside an array.
    function ronikdesigns_scripts_styles() {
        $recient_transient = get_transient( 'frontend-script-loader' );
        // First check if the recient_transient is empty..
        if(empty( $recient_transient )){
            $result = [];
            global $wp_scripts;
            if($wp_scripts->queue){
                foreach( $wp_scripts->queue as $script ){
                    if($wp_scripts->registered[$script]->src){
                        $result[] =  $wp_scripts->registered[$script]->src . ";";
                    }
                }
            }
            global $wp_styles;
            if($wp_scripts->queue){
                foreach( $wp_styles->queue as $style ){
                    if($wp_styles->registered[$style]->src){
                        $result[] =  $wp_styles->registered[$style]->src . ";";
                    }
                }
            }
            // Expire the transient after a day or so..
            set_transient( 'frontend-script-loader', $result, DAY_IN_SECONDS );
            return $result;
        } else {
            return;
        }
    }
    add_action( 'wp_head', 'ronikdesigns_scripts_styles');
    function ronikdesigns_service_worker_data( $data ) {
        global $wp_version;
        // script loader
        if($data['slug'] == 'url'){
            $transient = get_transient( 'frontend-script-loader' );
            // First lets change http:// to secure https://
            $santize = str_replace( "http:", "https:", $transient );
            // Want to remove the semicolon since this is going right into a js script loader..
            $santize2 = str_replace( ";", "", $santize );
            if($santize2){
                $f_array = array();
                foreach( $santize2 as $string){
                    // Next we check if the script matches the server
                    // This is is critical due to cors and reliability of script not returning a 404 or 500 error. 
                    if (str_contains($string, $_SERVER['SERVER_NAME'])) {
                        $f_array[] = $string;
                    }       
                }
            }
            return $f_array;
        }

        // Image
        if($data['slug'] == 'image'){
            $select_attachment_type = array(
                "jpg" => "image/jpg",
				"jpeg" => "image/jpeg",
				"jpe" => "image/jpe",
				// "gif" => "image/gif",
				// "png" => "image/png",
			);
            $args = array(
                // 'post_status' => 'publish',
                'numberposts' => 1, // Throttle the number of posts...
                'post_type' => 'attachment',
				'post_mime_type' => $select_attachment_type,
                'orderby' => 'date', 
                'order'  => 'DESC',
            );
            $f_pages = get_posts( $args );
            if($f_pages){
                $f_url_array = [];
                foreach($f_pages as $posts){
                    $f_url_array[] = wp_get_attachment_image_url($posts->ID);
                }
                return $f_url_array;
            }
        }

        // version
        if($data['slug'] == 'version'){
            $theme_version = wp_get_theme()->get( 'Version' );
            // This is critical for caching urls...
            return [$wp_version, RONIKDESIGN_VERSION, $theme_version];
        }
        // sitemap
        if($data['slug'] == 'sitemap'){
            $args = array(
                'post_status' => 'publish',
                'numberposts' => 5, // Throttle the number of posts...
                'post_type'   => array('post','page'),
            );
            $f_pages = get_posts( $args );
            if($f_pages){
                $f_url_array = [];
                foreach($f_pages as $posts){
                    $f_url_array[] = get_permalink($posts->ID);
                }
                return $f_url_array;
            }
        }
    }
    add_action( 'rest_api_init', function () {
        register_rest_route( 'serviceworker/v1', '/data/(?P<slug>\w+)', array(
            'methods' => 'GET',
            'callback' => 'ronikdesigns_service_worker_data',
        ));
    });
}

// Disable Gutenberg editor for specific post type
function ronikdesigns_prefix_disable_gutenberg($current_status, $post_type)
{
    $f_disable_gutenberg = get_field('disable_gutenberg_posttype', 'option');
    if ($f_disable_gutenberg) {
        foreach ($f_disable_gutenberg as $key => $disable_gutenberg) {
            if ($post_type === $disable_gutenberg) return false;
        }
    }
    return $current_status;
}
add_filter('use_block_editor_for_post_type', 'ronikdesigns_prefix_disable_gutenberg', 10, 2);


// Auto Add parameters for vimeo iframe
function ronikdesigns_auto_add_vimeo_args($provider, $url, $args)
{
    if (strpos($provider, '//vimeo.com/') !== false) {
        $args = array(
            'title' => 0,
            'byline' => 0,
            'portrait' => 0,
            'badge' => 0,
            'sidedock' => 0,
            'controls' => 0,
            'allow' => 'autoplay',
            'muted' => 0,
            'loop' => 1,
        );
        $provider = add_query_arg($args, $provider);
    }
    return $provider;
}
add_filter('oembed_fetch_url', 'ronikdesigns_auto_add_vimeo_args', 10, 3);


// remove heartbeat monitor error
add_filter('ronikdesigns_wpe_heartbeat_allowed_pages', function ($pages) {
    global $pagenow;
    $pages[] =  $pagenow;
    return $pages;
});


// Add class to menu items.
function ronikdesigns_add_menu_link_class($atts, $item, $args)
{
    if (property_exists($args, 'link_class')) {
        $atts['class'] = $args->link_class;
    }
    return $atts;
}
add_filter('nav_menu_link_attributes', 'ronikdesigns_add_menu_link_class', 1, 3);


// Modify Header for page
function ronikdesigns_last_modified_header($headers)
{
    //Check if we are in a single post of any type (archive pages has not modified date)
    if (is_singular() && !is_admin()) {
        $post_id = get_queried_object_id();
        if ($post_id) {
            header("Last-Modified: " . get_the_modified_time("D, d M Y H:i:s", $post_id));
        }
    }
}
add_action('template_redirect', 'ronikdesigns_last_modified_header');


// Enable WP Login Style
function ronikdesigns_wpb_login_logo()
{
    $theme      = wp_get_theme();
    $version    = $theme->get('version');
    $assets_dir = get_stylesheet_directory_uri();
    wp_enqueue_style('ronik-login', $assets_dir . '/admin-scripts/css/wp-admin.css', array(), $version);

?>
    <style type="text/css">
         #login .video, .login .video{
            position: absolute;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
        }
        #login .video #background-video, .login .video #background-video{
            position: relative;
            width: 150%;
            height: 150%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        #login h1 a,
        .login h1 a {
            /* background-image: url(http://path/to/your/custom-logo.png); */
            height: 100px;
            width: 300px;
            background-size: 300px 100px;
            background-repeat: no-repeat;
            padding-bottom: 10px;
        }

        /* Lets fix the weird input issue */
        #user_login, #user_pass {
            padding: 0 10px;
            width: calc(100% - 20px);
        }

        .login .button.wp-hide-pw{
            right: -20px !important;
        }

        #wp-submit{
            padding: 10px;
            border: none;
            background-color: blue;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            opacity: 1;
            transition: all .4s;
        }
        #wp-submit:hover{
            opacity: .5;
        }


    </style>
    <div class="video">
        <iframe id="background-video" src="https://player.vimeo.com/video/391604277?background=1"></iframe>
    </div>
<?php }
add_action('login_enqueue_scripts', 'ronikdesigns_wpb_login_logo');
