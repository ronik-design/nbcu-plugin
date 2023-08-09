<?php 

function ronikdesigns_apikey_data( $data ) {
    // Api Key loader
    if($data['slug'] == 'apikey'){
        $target_plugin_slug = $data->get_param( 'pluginSlug' );
        $target_plugin_key = $data->get_param( 'key' );
        $target_website_id = json_decode($data->get_param( 'websiteID' ));
        // error_log(print_r(  $target_plugin_slug , true));
        // error_log(print_r(  $target_plugin_key , true));
        // error_log(print_r(  $target_website_id , true));
        $compare_response = '';
        // Lets check to make sure the key is present in the Parameters.
        if(empty($target_plugin_key) || !$target_plugin_key){
            return 'API Key Not Found!';
        }
        // Lets check to make sure the website id is present in the Parameters.
        if(empty($target_website_id) || !$target_website_id){
            return 'Website ID Not Found!';
        }
        // Lets make sure the pluginSlug is a match that was provided.
        switch ($target_plugin_slug) {
            case "ronik_media_cleaner":
                $f_apikey_field_retrieval = 'ronik_media_cleaner';
                break;
            case "ronik_optimization":
                $f_apikey_field_retrieval = 'ronik_optimization';
                break;
            case "ronik_third_addon":
                $f_apikey_field_retrieval = 'ronik_third_addon';
                break;
            default:
                return 'Invalid Plugin Slug!';
        }
        // We loop through all the potential plugins 0-3 incrementing the key value for the acf metadata.
        foreach (range(0, 3) as $number) {
            $args = array(
                'meta_query' => array(
                    'relation' => 'AND', 
                    array(
                        'key' => 'plugin-data_'.$number.'_api_key',
                        'value' => $target_plugin_key,
                        'compare' => '=='
                    ),
                    array(
                        'key' => 'plugin-data_'.$number.'_plugin_name',
                        'value' => $target_plugin_slug,
                        'compare' => '=='
                    ),
                    array(
                        'key' => 'plugin-data_'.$number.'_website_id',
                        'value' => $target_website_id,
                        'compare' => '=='
                    )
                )
            );
            $user_query = new WP_User_Query( $args );
            // We get the results and concat the results.
            if ( ! empty( $user_query->get_results() ) ) {
                foreach ( $user_query->get_results() as $user ) {
                    $compare_response .= ' valid ';
                }
            } else{
                $compare_response .= ' invalid ';
            }
        }

        // Error response catching...
        // If the string contains valid. We proceed!
        if (str_contains($compare_response, ' valid ')) {
            $response = 'Success';            
        } else {
            $response = 'Invalid Data Submitted!';
        }
        
        return $response;
    }
}
register_rest_route( 'apikey/v1', '/data/(?P<slug>\w+)', array(
    'methods' => 'GET',
    'callback' => 'ronikdesigns_apikey_data',
));
