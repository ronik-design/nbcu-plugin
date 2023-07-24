<?php 
// We dynamically create the manifest.php file.
    $f_manifest_app_name = get_field('manifest_app_name', 'options');
    $f_manifest_description = get_field('manifest_description', 'options');
    $f_manifest_theme_color = get_field('manifest_theme_color', 'options');
    $f_manifest_icons_512x512 = get_field('manifest_icons_512x512', 'options');
    $f_manifest_icons_144x144 = get_field('manifest_icons_144x144', 'options');
    $random_file = fopen(get_stylesheet_directory()."/manifest.json", "w");
    
    if(!$f_manifest_app_name){
        $f_manifest_app_name = get_bloginfo();
    }
    if(!$f_manifest_description){
        $f_manifest_description = get_bloginfo('description');
    }
    if(!$f_manifest_icons_512x512){
        // Store var as blanks to avoid errors...
        $f_manifest_icons_512x512['url'] = '';
        $f_manifest_icons_512x512['mime_type'] = '';
    }
    if(!$f_manifest_icons_144x144){
        // Store var as blanks to avoid errors...
        $f_manifest_icons_144x144['url'] = '';
        $f_manifest_icons_144x144['mime_type'] = '';
    }

    $f_array = array(
        "name" => $f_manifest_app_name, 
        "app_name" => $f_manifest_app_name, 
        "short_name" => $f_manifest_app_name,
        "description" => $f_manifest_description, 
        "icons" => array(
            array(
                "src" => $f_manifest_icons_512x512['url'],
                "type" => $f_manifest_icons_512x512['mime_type'], 
                "sizes" => "512x512", 
                "purpose" => "maskable any", 
            ),
            array(
                "src" => $f_manifest_icons_144x144['url'],
                "type" => $f_manifest_icons_144x144['mime_type'], 
                "sizes" => "144x144", 
                "purpose" => "any", 
            ),
        ),
        "start_url" => "/", 
        "background_color" => $f_manifest_theme_color, 
        "display" => "fullscreen", 
        "scope" => "/", 
        "theme_color" => $f_manifest_theme_color,
        "screenshots" => array(
            array(
                "src" => $f_manifest_icons_512x512['url'],
                "type" => $f_manifest_icons_512x512['mime_type'], 
                "sizes" => "512x512", 
                "purpose" => "any"
            )
        ),
    );

    fwrite($random_file, json_encode($f_array, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    fclose($random_file);
?>