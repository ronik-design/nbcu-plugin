<?php
/*
	** Add Filters
*/
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

$ronikdesigns_spam_buster = get_field( 'gform_spam_buster', 'options' );
if( !empty($ronikdesigns_spam_buster['enable_spam_buster']) ){
    if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
        //plugin is activated, do something
        add_filter( 'gform_entry_is_spam', 'filter_gform_entry_is_spam_ip_rate_limit', 11, 3 );
        add_filter( 'gform_entry_is_spam', 'filter_gform_entry_is_spam_iplocate_country', 11, 3 );
        add_filter( 'gform_entry_is_spam', 'filter_gform_entry_is_spam_purgomalum', 11, 3 );
    }
}
/*
	** IP Rate Limit
	** Mark a submission as spam if the IP address is the source of multiple submissions.
*/
function filter_gform_entry_is_spam_ip_rate_limit( $is_spam, $form, $entry ) {
    if ( $is_spam ) {
        return $is_spam;
    }
    $ip_address = empty( $entry['ip'] ) ? GFFormsModel::get_ip() : $entry['ip'];
    if ( ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
        return true;
    }
    $key   = wp_hash( __FUNCTION__ . $ip_address );
    $count = (int) get_transient( $key );
    if ( $count >= 2 ) {
        return true;
    }
    $count ++;
	// Expires in one hour.
    set_transient( $key, $count, HOUR_IN_SECONDS );
    return false;
}

/*
	** Integrate with iplocate.io
	** iplocate.io service to be used to get the country code for the IP address of the
	** form submitter enabling you to mark submissions as spam if they originate from specified countries.
*/
function filter_gform_entry_is_spam_iplocate_country( $is_spam, $form, $entry ) {
	$ronikdesigns_spam_buster = get_field( 'spam_buster', 'options' );
	if($ronikdesigns_spam_buster['country_code']){
		// Empty Array
		$stack = array();
		// Push items to Array
		foreach( $ronikdesigns_spam_buster['country_code'] as $country_code ){
			array_push($stack, $country_code['item']);
		} 
		if ( $is_spam ) {
			return $is_spam;
		}
		$ip_address = empty( $entry['ip'] ) ? GFFormsModel::get_ip() : $entry['ip'];
		if ( ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
			return true;
		}
		$response = wp_remote_get( 'https://www.iplocate.io/api/lookup/' . $ip_address );
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $response_body['country_code'] ) ) {
			return false;
		}
		// The two letter ISO 3166-1 alpha-2 country codes.
		$country_codes = $stack;
		return in_array( $response_body['country_code'], $country_codes );
	}
}

/*
	** Integrate with PurgoMalum profanity filter
	** Use the PurgoMalum profanity filter to mark submissions as spam if Text or 
	** Paragraph field values contain words found on the PurgoMalum profanity list.
*/
function filter_gform_entry_is_spam_purgomalum( $is_spam, $form, $entry ) {
    if ( $is_spam ) {
        return $is_spam;
    }
    $field_types_to_check = array(
        'text',
        'textarea',
    );
    $text_to_check = array();
    foreach ( $form['fields'] as $field ) {
        // Skipping fields which are administrative or the wrong type.
        if ( $field->is_administrative() || ! in_array( $field->get_input_type(), $field_types_to_check ) ) {
            continue;
        }
        // Skipping fields which don't have a value.
        $value = $field->get_value_export( $entry );
        if ( empty( $value ) ) {
            continue;
        }
        $text_to_check[] = $value;
    }
    if ( empty( $text_to_check ) ) {
        return false;
    }
    $args = array(
        'text' => urlencode( implode( "\r\n", $text_to_check ) ),
    );
    $response = wp_remote_get( add_query_arg( $args, 'https://www.purgomalum.com/service/containsprofanity' ) );
    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        GFCommon::log_debug( __METHOD__ . '(): $response => ' . print_r( $response, true ) );
        return false;
    }
    return wp_remote_retrieve_body( $response ) === 'true';
}

