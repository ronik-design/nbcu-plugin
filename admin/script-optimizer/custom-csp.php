<?php

// Define constants for paths and flags
define('ENV_PATH', get_site_url()); // Base URL of the site
define('DISALLOW_SCRIPTS_DEFER', get_field('csp_disallow-script-defer', 'option')); // Flag to control script deferment
$f_csp_disallow_url = get_field('csp_disallow-url', 'option');
$f_csp_disallow_query = get_field('csp_disallow-query', 'option');
$f_csp_enable = get_field('csp_enable', 'option');

$csp_allow_fonts_scripts_santized = get_field('csp_allow-fonts', 'option');
$csp_allow_scripts_santized = get_field('csp_allow-scripts', 'option');

// Define CSP and security headers
define('ALLOWABLE_FONTS', '');
define('ALLOWABLE_SCRIPTS', '');

if($csp_allow_fonts_scripts_santized){
    define('ALLOWABLE_FONTS', $csp_allow_fonts_scripts_santized);
}
if($csp_allow_scripts_santized){
    define('ALLOWABLE_SCRIPTS', $csp_allow_scripts_santized);
}


if(!$f_csp_enable){
    return false; // Exit if bypass conditions are met
}

// Check if the current request URI should bypass the script
if (
    str_contains($_SERVER['REQUEST_URI'], '/wp-apxupx.php') ||
    str_contains($_SERVER['REQUEST_URI'], '/wp-cron.php') ||
    str_contains($_SERVER['REQUEST_URI'], '/wp-admin/') ||
    str_contains($_SERVER['REQUEST_URI'], '/wp-content/') ||
    is_user_logged_in()
) {
    return false; // Exit if bypass conditions are met
}

// Add query parameters as body classes for debugging or styling
function ronik_query_body_class($classes) {
    $query_classes = array_map(fn($k) => "ronik-query|$k", array_keys($_GET));
    $classes[] = implode(' ', $query_classes); // Append classes to existing body classes
    return $classes;
}
add_filter('body_class', 'ronik_query_body_class');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate if a URL is accessible and not blocked
function isUrlValid($url) {
    $context = stream_context_create(['http' => ['ignore_errors' => true]]);
    $content = file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];

    if (str_contains($content, 'Access Denied')) {
        return false;
    }

    foreach ($headers as $header) {
        if (str_contains($header, 'HTTP/1.1 40') || str_contains($header, 'HTTP/1.1 50')) {
            return false;
        }
    }

    return true;
}

// Trigger bypass settings based on certain conditions
function bypasser_trigger($bypasserType, $timeStamp, $bypasserHandle) {
    $currentDateTime = new DateTime();
    $datetime = isset($_SESSION['f_bypasser_enable']) ? new DateTime(date('Y-m-d h:i:s', $_SESSION['f_bypasser_enable']['time'])) : $currentDateTime;
    
    $datetime->modify('+4 seconds'); // Adjust time by 4 seconds

    if ($currentDateTime > $datetime) {
        $_SESSION['f_bypasser_enable'] = [
            "bypasserType" => $bypasserType,
            "time" => time(),
            "location" => $bypasserHandle
        ];
    }
}

// Helper function to sanitize URLs
function ronik_sanitize_url($url) {
    return str_replace([home_url('', 'https'), home_url('', 'http')], '', $url);
}

// Process disallowed URLs and queries
function process_disallowed_items($items, $type) {
    foreach ($items as $item) {
        $handle = $item['handle'];
        $sanitizedHandle = ronik_sanitize_url($handle);
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $httpReferer = $_SERVER['HTTP_REFERER'] ?? '';
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $pointOrigin = $_POST['point_origin'] ?? '';

        if (str_contains($requestUri, $sanitizedHandle) || str_contains($httpReferer, $sanitizedHandle) || str_contains($queryString, $sanitizedHandle) || str_contains($pointOrigin, $sanitizedHandle)) {
            $GLOBALS['f_bypasser_enable'] .= ',valid';
            bypasser_trigger("valid", time(), $handle);
        }
    }
}

// Process disallowed URLs and queries
if ($f_csp_disallow_url) {
    process_disallowed_items($f_csp_disallow_url, 'url');
}

if ($f_csp_disallow_query) {
    process_disallowed_items($f_csp_disallow_query, 'query');
}

error_log('Post f_bypasser_enable: ' . print_r($GLOBALS['f_bypasser_enable'], true));

$bypasserStatus = end(explode(',', $GLOBALS['f_bypasser_enable']));
bypasser_trigger($bypasserStatus === 'invalid' ? "invalid" : "valid", time(), 'fake');

// Define and sanitize CSP settings
function get_sanitized_csp_sources($sources) {
    $sanitized_sources = '';
    foreach (array_unique(array_filter(explode(" ", $sources))) as $source) {
        if (isUrlValid($source)) {
            $sanitized_sources .= $source . ' ';
        } else {
            error_log("URL is invalid or returns a 404 error: $source");
        }
    }
    return $sanitized_sources . site_url() . " blob: data: ";
}

$csp_allow_fonts = "https://fonts.googleapis.com/ https://fonts.gstatic.com/ ";
$f_csp_allow_fonts = get_field('csp_allow-fonts', 'option');
if ($f_csp_allow_fonts) {
    foreach ($f_csp_allow_fonts as $allow_fonts) {
        $csp_allow_fonts .= $allow_fonts['link'] . ' ';
    }
}

$csp_allow_scripts = "https://secure.gravatar.com/ https://0.gravatar.com/ https://google.com/ https://www.google.com/ https://www.google-analytics.com/ https://www.googletagmanager.com/ https://tagmanager.google.com https://ajax.googleapis.com/ https://googleads.g.doubleclick.net/ https://ssl.gstatic.com https://www.gstatic.com https://www.facebook.com/ https://connect.facebook.net/ https://twitter.com/ https://analytics.twitter.com/ https://t.co/ https://static.ads-twitter.com/ https://linkedin.com/ https://px.ads.linkedin.com/ https://px4.ads.linkedin.com/ https://player.vimeo.com/ https://www.youtube.com/ https://youtu.be/ ";
$f_csp_allow_scripts = get_field('csp_allow-scripts', 'option');
if ($f_csp_allow_scripts) {
    foreach ($f_csp_allow_scripts as $allow_scripts) {
        $csp_allow_scripts .= $allow_scripts['link'] . ' ';
    }
}

$csp_allow_fonts_scripts_santized = get_transient('csp_allow_fonts_scripts_santized');
if (empty($csp_allow_fonts_scripts_santized)) {
    $csp_allow_fonts_scripts_santized = get_sanitized_csp_sources($csp_allow_fonts);
    set_transient('csp_allow_fonts_scripts_santized', $csp_allow_fonts_scripts_santized, DAY_IN_SECONDS);
}

$csp_allow_scripts_santized = get_transient('csp_allow_scripts_santized');
if (empty($csp_allow_scripts_santized)) {
    $csp_allow_scripts_santized = get_sanitized_csp_sources($csp_allow_scripts);
    set_transient('csp_allow_scripts_santized', $csp_allow_scripts_santized, DAY_IN_SECONDS);
}


if (false === ($csp_time = get_transient('csp_time_dilation'))) {
    $csp_time = time();
    $csp_expire_time = rand(10, 100);
    set_transient('csp_time_dilation', $csp_time, $csp_expire_time);
}
define('CSP_NONCE', wp_create_nonce('csp_nonce_' . $csp_time));

// Add nonce to the head for inline scripts
add_action('wp_head', function() {
    ?>
    <span data-csp="<?php echo CSP_NONCE; ?>" style="opacity:0;position:absolute;left:-3000px;top:-3000px;height:0;overflow:hidden;"></span>
    <?php
});

// Add Content-Security-Policy headers
add_filter('wp_headers', function ($headers) {
    // Generate a nonce value for inline scripts
    $nonce = 'nonce-' . CSP_NONCE;

    // Default security headers
    $headers['Referrer-Policy']             = 'no-referrer-when-downgrade'; 
    $headers['X-Content-Type-Options']      = 'nosniff'; // Prevents MIME type sniffing
    $headers['X-XSS-Protection']            = '1; mode=block'; // Enables XSS filter in browsers

    // Permissions Policy to include encrypted-media
    $headers['Permissions-Policy']          = 'browsing-topics=(), fullscreen=(self), geolocation=*, camera=(), encrypted-media=*';

    // Content Security Policy
    $headers['Content-Security-Policy']     = "default-src 'self' https: data: blob:; ";
    $headers['Content-Security-Policy']    .= "script-src '" . $nonce . "' 'strict-dynamic' 'unsafe-inline' 'unsafe-eval' https: http: 'self' https://pix.cadent.tv; ";
    $headers['Content-Security-Policy']    .= "script-src-elem 'self' https: http: https://pix.cadent.tv 'unsafe-inline' " . ALLOWABLE_SCRIPTS . "; ";
    $headers['Content-Security-Policy']    .= "style-src 'self' 'unsafe-inline' https: " . ALLOWABLE_FONTS . "; ";
    $headers['Content-Security-Policy']    .= "img-src * data: blob: https://pix.cadent.tv; ";
    $headers['Content-Security-Policy']    .= "font-src 'self' https: data:; ";
    $headers['Content-Security-Policy']    .= "media-src 'self' https: data: blob:; ";
    $headers['Content-Security-Policy']    .= "connect-src 'self' https: data: blob:; ";
    $headers['Content-Security-Policy']    .= "object-src 'none'; ";
    $headers['Content-Security-Policy']    .= "frame-src * data: blob: 'unsafe-inline'; ";
    $headers['Content-Security-Policy']    .= "frame-ancestors 'self'; ";
    $headers['Content-Security-Policy']    .= "base-uri 'none'; ";

    $headers['Content-Security-Policy']    .= "report-uri " . esc_url(ENV_PATH . '/wp-json/csp/v1/report') . "; "; // Add report URI
    $headers['Content-Security-Policy']    .= "report-to 'csp-endpoint'; "; // Add report-to

    $headers['X-Frame-Options']             = 'SAMEORIGIN';


    // Report-To header configuration
    $headers['Report-To'] = json_encode([
        "group" => "csp-endpoint",
        "max_age" => 10886400, // 3 months in seconds
        "endpoints" => [
            ["url" => esc_url(ENV_PATH . '/wp-json/csp/v1/report')]
        ],
        "include_subdomains" => true
    ]);

    return $headers;
});

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

// Add preload to all enqueue styles
function ronikdesigns_add_preload_attribute($link, $handle) {
    $all_styles = handle_retrieval(true, false); // Assume this function retrieves all styles with handles
    $styles_to_preload = $all_styles;
    if ($styles_to_preload) {
        foreach ($styles_to_preload as $i => $current_style) {
            if (strpos($link, $current_style) !== false) {
                $org_link = $link;
                $mod_link = str_replace(["rel='stylesheet'", "id='"], ["rel='preload' as='style'", "id='pre-"], $link);
                $link = $mod_link . $org_link;
                return $link;
            }
        }
    }
    return $link;
}
add_filter('style_loader_tag', 'ronikdesigns_add_preload_attribute', 10, 2);

// Nonce external scripts
add_filter('nonce_scripts', function ($scripts) {
    $all_scripts = handle_retrieval(false, true); // Assume this function retrieves all scripts with handles
    return $all_scripts;
});

// // Add nonce and defer attributes to scripts
// add_filter('script_loader_tag', function ($html, $handle) {
//     $deferHandles = apply_filters('nonce_scripts', []);
//     $nonce = CSP_NONCE;
//     if (in_array($handle, $deferHandles)) {
//         $html = trim(str_replace("<script", '<script type="text/javascript" defer nonce="' . $nonce . '"', $html));
//     } else {
//         $html = trim(str_replace("<script", '<script type="text/javascript" nonce="' . $nonce . '"', $html));
//     }

//     // Remove defer attribute if it is not allowed
//     if (DISALLOW_SCRIPTS_DEFER) {
//         foreach (DISALLOW_SCRIPTS_DEFER as $key => $reject_script_defer) {
//             if ($reject_script_defer['handle'] === $handle) {
//                 $html = trim(str_replace("defer", "", $html));
//             }
//         }
//     }

//     return $html;
// }, 1, 2);
add_filter('script_loader_tag', function ($html, $handle) {
    // Get the nonce value for CSP
    $nonce = CSP_NONCE;

    // Check if the handle should have the `defer` attribute
    $deferHandles = apply_filters('nonce_scripts', []);
    if (in_array($handle, $deferHandles)) {
        // Add `defer` and `nonce` attributes
        $html = str_replace('<script ', '<script defer nonce="' . esc_attr($nonce) . '" ', $html);
    } else {
        // Add `nonce` attribute only
        $html = str_replace('<script ', '<script nonce="' . esc_attr($nonce) . '" ', $html);
    }

    // Remove `defer` attribute if it is not allowed
    if (DISALLOW_SCRIPTS_DEFER) {
        foreach (DISALLOW_SCRIPTS_DEFER as $key => $reject_script_defer) {
            if ($reject_script_defer['handle'] === $handle) {
                $html = str_replace(' defer', '', $html);
            }
        }
    }

    return $html;
}, 10, 2);



// Add resource hints
add_filter('wp_resource_hints', function($urls, $relation_type) {
    if ($relation_type === 'dns-prefetch') {
        return array_merge($urls, array(
            'https://fonts.googleapis.com/',
            'https://fonts.gstatic.com/'
        ));
    }
    return $urls;
}, 10, 2);
?>
