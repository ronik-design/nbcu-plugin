<?php 

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

register_rest_route( 'serviceworker/v1', '/data/(?P<slug>\w+)', array(
    'methods' => 'GET',
    'callback' => 'ronikdesigns_service_worker_data',
));
