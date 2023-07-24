<?php

// Customize ACF WYSIWYG toolbars
function ronikdesigns_acf_toolbars($toolbars)
{
    // Add Minimal toolbar
    $toolbars['Minimal'] = array();
    $toolbars['Minimal'][1] = array('bold', 'italic', 'link');

    return $toolbars;
}
add_filter('acf/fields/wysiwyg/toolbars', 'ronikdesigns_acf_toolbars'); // add toolbars

// Strip tags from WYSIWYG content based on toolbar settings
function ronikdesigns_acf_wysiwyg_strip_tags($value, $post_id, $field)
{
    if (isset($field['enable_strip_tags']) && $field['enable_strip_tags']) {
        if ($field['toolbar'] == 'basic') {
            $value = strip_tags($value, '<p><strong><em><span><a><br><blockquote><del><ul><ol><li>');
        } elseif ($field['toolbar'] == 'minimal') {
            $value = strip_tags($value, '<p><strong><em><a><br>');
        }
    }
    return $value;
}
add_action('acf/render_field_settings/type=wysiwyg', 'ronikdesigns_acf_wysiwyg_strip_tags_setting'); // add setting to enable/disable

// Add setting to enable/disable "Strip Tags Based on Toolbar"
function ronikdesigns_acf_wysiwyg_strip_tags_setting($field)
{
    acf_render_field_setting($field, array(
        'label'    => 'Strip Tags Based on Toolbar',
        'instructions' => 'HTML tags not supported by the selected toolbar will be stripped',
        'name' => 'enable_strip_tags',
        'type' => 'true_false',
        'ui' => 1
    ));
}
add_filter('acf/format_value/type=wysiwyg', 'ronikdesigns_acf_wysiwyg_strip_tags', 10, 3); // strip tags from WYSIWYG content based on toolbar

// Disable autoembed for ACF WYSIWYG fields (and add option to re-enable)
function ronikdesigns_acf_wysiwyg_disable_auto_embed($value, $post_id, $field)
{
    if (isset($field['enable_autoembed']) && (!empty($GLOBALS['wp_embed']) && !$field['enable_autoembed'])) {
        remove_filter('acf_the_content', array($GLOBALS['wp_embed'], 'autoembed'), 8);
    }
    return $value;
}
add_filter('acf/format_value/type=wysiwyg', 'ronikdesigns_acf_wysiwyg_disable_auto_embed', 10, 3); // disable autoembed

// Re-enable autoembed after value is formatted
function ronikdesigns_acf_wysiwyg_disable_auto_embed_after($value, $post_id, $field)
{
    if (isset($field['enable_autoembed']) && (!empty($GLOBALS['wp_embed']) && !$field['enable_autoembed'])) {
        add_filter('acf_the_content', array($GLOBALS['wp_embed'], 'autoembed'), 8);
    }
    return $value;
}
add_filter('acf/format_value/type=wysiwyg', 'ronikdesigns_acf_wysiwyg_disable_auto_embed_after', 20, 3); // re-enable autoembed after value is formatted

// Add setting to enable/disable "Enable Autoembed"
function ronikdesigns_acf_wysiwyg_disable_auto_embed_setting($field)
{
    acf_render_field_setting($field, array(
        'label'    => 'Enable Autoembed',
        'name' => 'enable_autoembed',
        'type' => 'true_false',
        'ui' => 1
    ));
}
add_action('acf/render_field_settings/type=wysiwyg', 'ronikdesigns_acf_wysiwyg_disable_auto_embed_setting'); // add setting to enable/disable

// Add class to wrapper (so JS knows to disable the wpview TinyMCE plugin)
function ronikdesigns_acf_wysiwyg_disable_auto_embed_class($field)
{
    if (isset($field['enable_autoembed']) && $field['enable_autoembed']) {
        $field['wrapper']['class'] = explode(' ', $field['wrapper']['class']);
        $field['wrapper']['class'][] = 'ks-disable-autoembed';
        $field['wrapper']['class'] = implode(' ', $field['wrapper']['class']);
    }
    return $field;
}
add_filter('acf/prepare_field/type=wysiwyg', 'ronikdesigns_acf_wysiwyg_disable_auto_embed_class'); // add class to wrapper (so JS knows to disable the wpview TinyMCE plugin)

// Add option to post object, page link, and relationship fields to allow filtering by page template
function ronikdesigns_acf_template_filter_setting($field)
{
    acf_render_field_setting($field, array(
        'label'    => 'Filter by Page Template',
        'name' => 'filter_template',
        'type' => 'select',
        'choices' => array_flip(get_page_templates()),
        'multiple' => 1,
        'ui' => 1,
        'allow_null' => 1,
        'placeholder' => 'All page templates'
    ));
}
add_action('acf/render_field_settings/type=post_object', 'ronikdesigns_acf_template_filter_setting'); // add setting to post object fields
add_action('acf/render_field_settings/type=page_link', 'ronikdesigns_acf_template_filter_setting'); // add setting to page_link fields
add_action('acf/render_field_settings/type=relationship', 'ronikdesigns_acf_template_filter_setting'); // add setting to relationship fields

// update query for post object fields to include template filter
function ronikdesigns_acf_template_filter_query($args, $field, $post_id)
{
    if (isset($field['filter_template']) && $field['filter_template']) {
        $args['meta_query'] = array(
            array(
                'key' => '_wp_page_template',
                'value' => $field['filter_template'],
                'compare' => 'IN'
            )
        );
    }
    return $args;
}
add_filter('acf/fields/post_object/query', 'ronikdesigns_acf_template_filter_query', 10, 3); // update query for post object fields to include template filter
add_filter('acf/fields/page_link/query', 'ronikdesigns_acf_template_filter_query', 10, 3); // update query for page link fields to include template filter
add_filter('acf/fields/relationship/query', 'ronikdesigns_acf_template_filter_query', 10, 3); // update query for relationship fields to include template filter

// Add maximum/minimum selection options to field types with multi-select functionality
function ronikdesigns_acf_multi_min_max_settings($field)
{
    if ($field['type'] == 'checkbox') {
        // render settings for checkbox fields (always show settings)
        acf_render_field_setting($field, array(
            'label'    => 'Minimum Selection',
            'name' => 'multi_min',
            'type' => 'number'
        ));
        acf_render_field_setting($field, array(
            'label'    => 'Maximum Selection',
            'name' => 'multi_max',
            'type' => 'number'
        ));
    } elseif ($field['type'] == 'taxonomy') {
        // render settings for taxonomy fields (hide/show settings based on whether selected appearance allows multiple values)
        acf_render_field_setting($field, array(
            'label'    => 'Minimum Selection',
            'name' => 'multi_min',
            'type' => 'number',
            'conditions' => array(
                array(
                    array(
                        'field' => 'field_type',
                        'operator' => '==',
                        'value' => 'checkbox'
                    )
                ),
                array(
                    array(
                        'field' => 'field_type',
                        'operator' => '==',
                        'value' => 'multi_select'
                    )
                ),
            )
        ));
        acf_render_field_setting($field, array(
            'label'    => 'Maximum Selection',
            'name' => 'multi_max',
            'type' => 'number',
            'conditions' => array(
                array(
                    array(
                        'field' => 'field_type',
                        'operator' => '==',
                        'value' => 'checkbox'
                    )
                ),
                array(
                    array(
                        'field' => 'field_type',
                        'operator' => '==',
                        'value' => 'multi_select'
                    )
                ),
            )
        ));
    } else {
        // render settings for other field types (hide/show settings based on whether multi-select is enabled)
        acf_render_field_setting($field, array(
            'label'    => 'Minimum Selection',
            'name' => 'multi_min',
            'type' => 'number',
            'conditions' => array(
                'field' => 'multiple',
                'operator' => '==',
                'value' => 1
            )
        ));
        acf_render_field_setting($field, array(
            'label'    => 'Maximum Selection',
            'name' => 'multi_max',
            'type' => 'number',
            'conditions' => array(
                'field' => 'multiple',
                'operator' => '==',
                'value' => 1
            )
        ));
    }
}
add_action('acf/render_field_settings/type=checkbox', 'ronikdesigns_acf_multi_min_max_settings'); // add min/max settings to checkbox fields
add_action('acf/render_field_settings/type=select', 'ronikdesigns_acf_multi_min_max_settings'); // add min/max settings to select fields
add_action('acf/render_field_settings/type=post_object', 'ronikdesigns_acf_multi_min_max_settings'); // add min/max settings to post object fields
add_action('acf/render_field_settings/type=page_link', 'ronikdesigns_acf_multi_min_max_settings'); // add min/max settings to page link fields
add_action('acf/render_field_settings/type=taxonomy', 'ronikdesigns_acf_multi_min_max_settings'); // add min/max settings to taxonomy fields
add_action('acf/render_field_settings/type=user', 'ronikdesigns_acf_multi_min_max_settings'); // add min/max settings to user fields
add_action('acf/render_field_settings/type=gf_select', 'ronikdesigns_acf_multi_min_max_settings'); // add min/max settings to Gravity Form fields

// validate min/max settings for checkbox fields
function ronikdesigns_acf_multi_min_max_validation($valid, $value, $field, $input)
{
    if ($valid) {
        if ($field['multi_min']) {
            if (!$value) $value = array(); // if value is empty, set it to an empty array so count() returns 0

            // if value doesn't meet minimum, return validation error message
            if (count($value) < $field['multi_min']) {
                $valid = 'Please select a minimum of ' . $field['multi_min'];
                if ($field['multi_min'] == 1) {
                    $valid .= ' value.';
                } else {
                    $valid .= ' values.';
                }
            }
        }
        if ($field['multi_max']) {
            if (!$value) $value = array(); // if value is empty, set it to an empty array so count() returns 0

            // if value exceeds maximum, return validation error message
            if (count($value) > $field['multi_max']) {
                $valid = 'Please select a maximum of ' . $field['multi_max'];
                if ($field['multi_max'] == 1) {
                    $valid .= ' value.';
                } else {
                    $valid .= ' values.';
                }
            }
        }
    }

    return $valid;
}
add_action('acf/validate_value/type=checkbox', 'ronikdesigns_acf_multi_min_max_validation', 10, 4); // validate min/max settings for checkbox fields
add_action('acf/validate_value/type=select', 'ronikdesigns_acf_multi_min_max_validation', 10, 4); // validate min/max settings for select fields
add_action('acf/validate_value/type=post_object', 'ronikdesigns_acf_multi_min_max_validation', 10, 4); // validate min/max settings for post object fields
add_action('acf/validate_value/type=page_link', 'ronikdesigns_acf_multi_min_max_validation', 10, 4); // validate min/max settings for page link fields
add_action('acf/validate_value/type=taxonomy', 'ronikdesigns_acf_multi_min_max_validation', 10, 4); // validate min/max settings for taxonomy fields
add_action('acf/validate_value/type=user', 'ronikdesigns_acf_multi_min_max_validation', 10, 4); // validate min/max settings for user fields
add_action('acf/validate_value/type=gf_select', 'ronikdesigns_acf_multi_min_max_validation', 10, 4); // validate min/max settings for Gravity Form fields

// Character counter for ACF text and textarea field types
function ronikdesigns_acf_character_limit_markup($field)
{
    if ($field['maxlength']) { ?>
        <p class="ronik-character-count">character Count:
            <span class="ronik-character-count__current">
                <?php
                if (function_exists('mb_strlen')) {
                    // string length showing number of characters.
                    $len = mb_strlen($field['value']);
                } else {
                    // string length showing a number of bytes
                    $len = strlen($field['value']);
                }
                echo $len; ?>
            </span>/<?php echo $field['maxlength']; ?>
        </p>
<?php }
}
add_action('acf/render_field/type=text', 'ronikdesigns_acf_character_limit_markup'); // add counter to text fields
add_action('acf/render_field/type=textarea', 'ronikdesigns_acf_character_limit_markup'); // add counter to textarea fields

// Dynamic Post Loader to select.
function ronikdesigns_acf_post_load($field)
{
    // render settings for other field types (hide/show settings based on whether multi-select is enabled)
    acf_render_field_setting($field, array(
        'label'    => 'Dynamic Post Loader to select.',
        'name' => 'dyn_post_loader',
        'type' => 'true_false',
        'ui' => 1,
    ));
}
add_action('acf/render_field_settings/type=select', 'ronikdesigns_acf_post_load' , 10); // add Dynamic Post Loader to select

// Dynamic Post Loader to select.
function ronikdesigns_acf_post_loader($field)
{
    if( !array_key_exists('dyn_post_loader', $field)  ){
        // return the field
        return $field;
    }
    if ($field['dyn_post_loader']) {
        $args = array(
            'public' => true,
            '_builtin' => false
        );
        $post_types = get_post_types($args);
        $post_types['page'] = 'page';
        $post_types['post'] = 'post';
        // array_push($post_types, 'page');
        // array_push($post_types, 'post');

        $choices = array($post_types);
        $field['choices'] = array();
        if ($choices) {
            foreach ($choices as $choice) {
                $field['choices'] = $choice;
            }
        }
    }
    // return the field
    return $field;
}
add_filter('acf/load_field/type=select', 'ronikdesigns_acf_post_loader', 11);
