<?php

function ronikdesigns_dynamic_get_icon($icon){
    if(!$icon){
        return;
    }
    if($icon){
        
        $directory = get_stylesheet_directory_uri().'/roniksvg/migration/';
        $f_svg_path = $directory.$icon['icon_select'] . '.svg';
    ?>
        <img src="<?= $f_svg_path; ?>" data-svg-color="<?= $icon['icon_color']; ?>" class="adv-svg-swap">
    <?php
    }
}