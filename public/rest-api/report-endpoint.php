<?php

function handle_csp_report(WP_REST_Request $request) {
    // Retrieve GET query parameters
    $data = $request->get_params();
    
    error_log('CSP Report URI: ' . print_r($data, true));

    $response = [
        'status' => 'success',
        'message' => 'Report processed successfully.',
    ];

    // Sanitize 'allow_fonts' if present
    if (isset($data['allow_fonts']) && is_array($data['allow_fonts'])) {
        $sanitized_fonts = array_map('sanitize_text_field', $data['allow_fonts']);
        // Process $sanitized_fonts as needed
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Invalid or missing allow_fonts data.';
    }

    // Sanitize 'allow_scripts' if present
    if (isset($data['allow_scripts']) && is_array($data['allow_scripts'])) {
        $sanitized_scripts = array_map('sanitize_text_field', $data['allow_scripts']);
        // Process $sanitized_scripts as needed
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Invalid or missing allow_scripts data.';
    }

    error_log('CSP Report Response: ' . print_r($response, true));

    return new WP_REST_Response($response, 200);
}

// Register REST API endpoint for CSP reports using GET
register_rest_route('csp/v1', '/report', array(
    'methods' => 'GET',
    'callback' => 'handle_csp_report',
    'permission_callback' => '__return_true', // Adjust permissions as needed
));
