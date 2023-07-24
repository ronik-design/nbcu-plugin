<?php 
function ronikdesigns_buttons($ACF){
    $f_buttons = get_field($ACF.'_buttons');

    if($f_buttons){ ?>
    <div class="buttons-wrapper">
        <?php foreach($f_buttons as $buttons){ ?>
        <div class="buttons-wrapper__content">
            <?php if($buttons['button']){ ?>
                <a class="underline underline_arrow" href="<?= $buttons['button']['url']; ?>" target="<?= $buttons['button']['target']; ?>"><?= $buttons['button']['title']; ?></a>
            <?php } ?>
        </div>
       <?php } ?>
    </div>
    <?php }   
}