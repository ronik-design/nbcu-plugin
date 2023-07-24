<?php 

if( function_exists('acf_add_local_field_group') ):
    acf_add_local_field_group(array(
        'key' => 'group_6247192d3bf80_ronikdesign',
        'title' => 'CLONE: Buttons',
        'fields' => array(
            array(
                'key' => 'field_6247193114538',
                'label' => 'Buttons',
                'name' => 'buttons',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'collapsed' => '',
                'min' => 0,
                'max' => 0,
                'layout' => 'table',
                'button_label' => 'Add Row',
                'sub_fields' => array(
                    array(
                        'key' => 'field_6247194814539',
                        'label' => 'Button',
                        'name' => 'button',
                        'aria-label' => '',
                        'type' => 'link',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'parent_repeater' => 'field_6247193114538',
                    ),
                ),
                'rows_per_page' => 20,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
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
        'show_in_rest' => 0,
    ));
    
endif;