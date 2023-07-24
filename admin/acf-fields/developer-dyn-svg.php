<?php

if( function_exists('acf_add_local_field_group') ):

    acf_add_local_field_group(array(
        'key' => 'group_6430583718cb5_ronikdesign',
        'title' => 'CLONE: ACF Select Icon',
        'fields' => array(
            array(
                'key' => 'field_643058453fdcb_ronikdesign',
                'label' => 'Dynamic Icon',
                'name' => 'dynamic-icon',
                'type' => 'group',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_6430593b38972_ronikdesign',
                        'label' => 'Icon Color',
                        'name' => 'icon_color',
                        'type' => 'color_picker',
                        'instructions' => 'Warning: Color changing will only affect the path & polygon colors. This will change all colors of the vector.
    If you want the default color please leave color blank.',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                    ),
                    array(
                        'key' => 'field_6430595238973_ronikdesign',
                        'label' => 'Icon Select',
                        'name' => 'icon_select',
                        'type' => 'icon-picker',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                        'initial_value' => '',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => false,
        'description' => '',
    ));
    
    endif;