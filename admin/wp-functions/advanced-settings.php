<?php 

function ronikdesigns_AdvancedSettings($fieldType){ 
	if($fieldType == 'flex'){
		$get_acf_type = 'get_field';
	} else{
		$get_acf_type = 'get_field';
	}
    $f_zindex = $get_acf_type( 'advanced_settings_z-index' );
    if(!$f_zindex){ 
        $f_zindex = ''; 
    } else{ 
        $f_zindex = 'z-index:'.$f_zindex.';'; 
    }
    $f_margin_top = $get_acf_type( 'advanced_settings_margin-top' );
    if($f_margin_top || $f_margin_top == '0'){
        $f_margin_top = 'margin-top:' .$f_margin_top . 'px;';
    } else{
        $f_margin_top = false; 
    }
    $f_margin_btm = $get_acf_type( 'advanced_settings_margin-bottom' );
    if($f_margin_btm || $f_margin_btm == '0'){
        $f_margin_bottom = 'margin-bottom:' . $f_margin_btm . 'px;';
    } else{
        $f_margin_bottom = false;
    }
    $f_padding_top = $get_acf_type( 'advanced_settings_padding-top' );
    if($f_padding_top || $f_padding_top == '0'){
        $f_padding_top = 'padding-top:' .$f_padding_top . 'px;';
    } else{
        $f_padding_top = false; 
    }
    $f_padding_btm = $get_acf_type( 'advanced_settings_padding-bottom' );
    if($f_padding_btm || $f_padding_btm == '0'){
        $f_padding_bottom = 'padding-bottom:' . $f_padding_btm . 'px;';
    } else{
        $f_padding_bottom = false;
    }
    $f_content_align = $get_acf_type( 'advanced_settings_content-alignment' );
    $f_alignment = 'display: flex;align-items: center;';
    switch ($f_content_align) {
        case 'left':
            $f_alignment .= 'justify-content: flex-start;';
            break;
        case 'center':
            $f_alignment .= 'justify-content: center;';
            break;
        case 'right':
            $f_alignment .= 'justify-content: flex-end;';
            break;
    }
    return $f_margin_top . $f_margin_bottom . $f_padding_bottom . $f_padding_top .  $f_zindex . $f_alignment;
}


function ronikdesigns_AdvancedSettingsInner($fieldType){
	if($fieldType == 'flex'){
		$get_acf_type = 'get_field';
	} else{
		$get_acf_type = 'get_field';
	}
    $f_max_width = $get_acf_type( 'advanced_settings_max-width' );

    if($f_max_width){
        $f_mw = 'max-width:';
        switch ($f_max_width) {
            case 'sm':
                $f_mw .= '450px;';
                break;
            case 'md':
                $f_mw .= '750px;';
                break;
            case 'lg':
                $f_mw .= '1050px;';
                break;
            case 'custom':
                $f_max_width_custom = $get_acf_type( 'advanced_settings_max-width_custom' );
                
                if($f_max_width_custom){
                    $f_mw .=  $f_max_width_custom.'px;'; 
                    
                    if($f_max_width_custom == ''){
                        $f_mw = 'max-width:100%;'; 
                    }
                    
                } else{
                    $f_mw = 'max-width:100%;'; 
                }
                break;
        }
    } else{
        $f_mw = '';
    }

    $f_min_height = $get_acf_type( 'advanced_settings_min-height' );
    if($f_min_height){

        $f_mh = 'min-height:';
        switch ($f_min_height) {
            case 'sm':
                $f_mh .= '450px; display:flex;';
                break;
            case 'md':
                $f_mh .= '750px; display:flex;';
                break;
            case 'lg':
                $f_mh .= '1050px; display:flex;';
                break;
            case 'custom':
                $f_min_height_custom = $get_acf_type( 'advanced_settings_min-height_custom' );
                if($f_min_height_custom){
                    $f_mh .=  $f_min_height_custom.'px;'; 
    
                    if($f_min_height_custom == ''){
                        $f_mh = 'min-height:100%;'; 
                    }
    
                } else{
                    $f_mh = 'min-height:100%;'; 
                }
                break;
        }
    } else{
        $f_mh = '';
    }
    return $f_mw . ' ' . $f_mh;
} 

