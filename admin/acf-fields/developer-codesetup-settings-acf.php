<?php 

if( function_exists('acf_add_local_field_group') ):

    acf_add_local_field_group(array(
        'key' => 'group_63e6a449c38dd_ronikdesign',
        'title' => 'Code Template',
        'fields' => array(
            array(
                'key' => 'field_63e6a4491d1d4',
                'label' => 'Code template',
                'name' => '',
                'aria-label' => '',
                'type' => 'message',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'message' => '
                    <strong>Please follow the screenshots below as a reference point to setting up the acf fields correctly.</strong>
                    </br></br>


                    <div class="acf-setup-instructions">
                        <div class="acf-setup-instruction__content">
                            <strong>Enable Dynamic Icon Dropdown </strong></br>
                            Create a new field. Then assign the Field Name as "icon". 

                            Then select "Clone" Field Type. You will see a list select the "All fields from CLONE: ACF Select Icon field group." 

                            Then Display select "Seamless (replaced this field with selected fields) Select "Prefix Field Names."

                            Function listed below. "icon_dynamic-icon" is the acf field name
                            </br>
                            
                            <code>$f_icon = get_sub_field("icon_dynamic-icon");</code></br>
                            <code>ronikdesigns_dynamic_get_icon($f_icon);</code>
                        </div>
                        <div class="acf-setup-instruction__img"><img src="'.plugin_dir_url( __DIR__ ) . 'screenshots/dynamic-icon-dropdown.jpg'.'"></div>
                    </div>





                    <div class="acf-setup-instructions">
                        <div class="acf-setup-instruction__content">
                            <strong>Enable Standard Text </strong></br>
                            Create a new field. Then assign the Field Name as "standard-text". 

                            Then select "Clone" Field Type. You will see a list select the "All fields from CLONE: Text Block field group." 

                            Then Display select "Seamless (replaced this field with selected fields) Select "Prefix Field Names."

                            Function listed below. "standard-text" is the acf field name
                            </br>
                            <code>ronikdesigns_standard_text("standard-text");</code>
                        </div>
                        <div class="acf-setup-instruction__img"><img src="'.plugin_dir_url( __DIR__ ) . 'screenshots/standard-text.jpg'.'"></div>
                    </div>





                    <div class="acf-setup-instructions">
                        <div class="acf-setup-instruction__content">
                            <strong>Enable Advanced Settings & Flex Establishment </strong></br>
                            Create a new field. Then assign the Field Name as "advanced_settings". 

                            Then select "Clone" Field Type. You will see a list select the "All fields from CLONE: Advanced Settings field group." 

                            Then Display select "Seamless (replaced this field with selected fields) Select "Prefix Field Names."

                            To use ronikdesigns_FlexEstablishment please reference code below.

                            

                            <code>$flexname = "flex-hero";</br>$f_flexname_outer = "flex-hero-block";</br>$f_flexname_inner = "flex-hero-block__inner";</br>$f_inlineStyle = "";</br>
                                $f_content = function(){ 
                                ronikdesigns_standard_text("standard-text");
                                };</br>
                                echo ronikdesigns_FlexEstablishment($block=false, $flexname, $f_flexname_outer, $f_flexname_inner, $f_inlineStyle, $f_content, $f_mod=false, $f_pageID=false );</code>

                        </div>
                        <div class="acf-setup-instruction__img"><img src="'.plugin_dir_url( __DIR__ ) . 'screenshots/advanced-settings.jpg'.'"></div>
                    </div>
                    


                    <div class="acf-setup-instructions">
                        <div class="acf-setup-instruction__content">
                            <strong>Enable Lazy Loader </strong></br>
                            Create a standard image field.  

                            Make sure return format is set to "Image Array"

                            Function listed below. Please copy code example below:
                            </br>

                            The 
                            <code> $helper = new RonikHelper; </code>
                            
                            "image" = First parameter can be either a acf set array or a image url.
                            "adv" = Second parameter Will create all the necessary code and will output the image. If set to false function will return just a barebone svg.
                            "customClass" = Third parameter will create the custom class for the created code.
                            
                            <code> $helper->ronikdesigns_svgplaceholder("image", true, "customClass"); </code>



                            Function creates a blank svg placeholder with the provided ACF image size.

                            <code>$helper->ronikdesigns_svgplaceholder($f_image, false, "customClass");</code>

                            

                        </div>
                        <div class="acf-setup-instruction__img"><img src="'.plugin_dir_url( __DIR__ ) . 'screenshots/standard-image.jpg'.'"></div>
                    </div>
                
                ',
                'new_lines' => 'wpautop',
                'esc_html' => 0,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'code-template',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ));
    
endif;		