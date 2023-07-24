<?php 
function ronikdesigns_standard_text($ACF, $isOption = null){
    if($isOption){
        $f_align = get_field($ACF.'_content-alignment', 'option');
        $f_title = get_field($ACF.'_title', 'option');
        $f_text = get_field($ACF.'_text', 'option');
        $f_buttons = get_field($ACF.'_buttons', 'option');
    } else{
        $f_align = get_field($ACF.'_content-alignment');
        $f_title = get_field($ACF.'_title');
        $f_text = get_field($ACF.'_text');
        $f_buttons = get_field($ACF.'_buttons');
    }
    ?>
    <div class="standard-text">
        <div class="standard-text__content">
            <div class="text-content <?php if($f_align){ echo 'text-content_'.$f_align; } ?>">
                <?php if($f_title){ ?><h2 class="text-content__title"><?= $f_title; ?></h2><?php } ?>
                <?php if($f_text){ ?><div class="text-content__text"><?= $f_text; ?></div><?php } ?>
                <?php if($f_buttons){ ?><div class="text-content__buttons"><?php ronikdesigns_buttons($ACF); ?></div><?php } ?>
            </div>
        </div>
    </div>
<?php
}
