<?php 

// Create a like compare function.
function nbcu_compare_like($a_value , $b_value){
	if(stripos($a_value, $b_value) !== FALSE){
		return true;
	} else {
		return false;
	}
}


function ronikdesigns_service_worker_data( $data ) {
    $f_custom_scripts_loader = get_field('custom_js_settings_serviceworker_script_loader', 'options');
    $f_cache = apply_filters( 'ronik_cache_busting_proto', false );

    $f_custom_scripts = '';
    if($f_custom_scripts_loader){
        $f_custom_scripts_count = count($f_custom_scripts_loader);
        foreach($f_custom_scripts_loader as $key => $script){
            if( substr($script['script'], -1) == '/' ){
                $f_url = 'https://'.$_SERVER['SERVER_NAME'].$script['script'];
            } else {
                $f_url = 'https://'.$_SERVER['SERVER_NAME'].$script['script'].'/';
            }
            if( ($key+1) == $f_custom_scripts_count ){
                if(strpos($script['script'], "http") == false){
                    $f_custom_scripts .= "".$f_url."";
                } else {
                    $f_custom_scripts .= "".$script['script']."";
                }
            } else {
                if(strpos($script['script'], "http") == false){
                    $f_custom_scripts .= "".$f_url."" . ',';

                } else {                    
                    $f_custom_scripts .= "".$script['script'].$f_cache ."" . ',';
                }
            }
        }
    }


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
        $pageId = base64_decode($data->get_param( 'pid' ));
        $sanPageId =  str_replace( '\/', '/', $pageId);
        
        if($pageId){
            $f_get_all_posts = get_posts( array(
                'post_status' => array('publish'),
                'numberposts'       => -1,
                'fields' => 'ids',
                'order' => 'ASC',
                'post__in' => array(url_to_postid($sanPageId)),
                'post_type' => 'any'  // Any sometimes doesnt work for all, it depends if regisetred..
    
            ) );    
            if($f_get_all_posts){
                foreach($f_get_all_posts as $f_post_id){
                    // Lets update the post meta of all posts...
                    $postmetas = get_post_meta( $f_post_id );
                    // First we get all the meta values & keys from the current post.
                    if($postmetas){
                        $f_collector = array();
                        foreach($postmetas as $meta_key => $meta_value) {
                            $f_meta_val = $meta_value[0];
                            if( (is_int($f_meta_val) || is_numeric($f_meta_val)) && $f_meta_val > 1){
                                if( wp_get_attachment_image_src($f_meta_val) ){
                                    $f_collector[] = wp_get_attachment_image_src($f_meta_val)[0];
                                }
                            }
                        }
                        if($f_collector && !empty($f_collector)){
                            return $f_collector;
                        }
                    }
                }
            }
        }
    

        $select_attachment_type = array(
            "jpg" => "image/jpg",
            "jpeg" => "image/jpeg",
            "jpe" => "image/jpe",
            // "gif" => "image/gif",
            // "png" => "image/png",
        );
        $args = array(
            // 'post_status' => 'publish',
            'numberposts' => 50, // Throttle the number of posts...
            'post_type' => 'attachment',
            'post_mime_type' => $select_attachment_type,
            // 'orderby' => 'date', 
            'orderby' => 'rand', 
            'order'  => 'DESC',
            // 'post__in' => array(url_to_postid($sanPageId)),
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

        $version_ronikdesigns_increment = get_option( 'version_ronikdesigns_increment', 1 );
        // This is critical for caching urls...
        return [$wp_version, RONIKDESIGN_VERSION, $theme_version, $version_ronikdesigns_increment];
    }
    // sitemap
    if($data['slug'] == 'sitemap'){
        $pageId = base64_decode($data->get_param( 'pid' ));
        $sanPageId =  str_replace( '\/', '/', $pageId);
        $args = array(
            'post_status' => 'publish',
            'orderby' => 'rand',
            // 'meta_key' => 'wpb_post_views_count',
            // 'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'numberposts' => 50, // Throttle the number of posts...
            'post_type' => 'page'  // Any sometimes doesnt work for all, it depends if regisetred..
        );
        $f_pages = get_posts( $args );
        if($f_pages){
            $f_url_array = [];
            foreach($f_pages as $posts){
                $f_url_array[] = get_permalink($posts->ID);
            }
            $f_url_array[] = $sanPageId;
                // This is critical. We explode the string to arrat then merge the two arrays
                $f_new_array = array_merge($f_url_array,  explode(",", $f_custom_scripts));
                // Next we remove any duplicate arrays values. Then we re-index the value
                $f_new_array_re = array_values( array_unique($f_new_array) );

            return $f_new_array_re;
        }
    }  
}

register_rest_route( 'serviceworker/v1', '/data/(?P<slug>\w+)', array(
    'methods' => 'GET',
    'callback' => 'ronikdesigns_service_worker_data',
));
