<?php

// Function To create the barebone needs of a acf flexible content structure
function ronikdesigns_FlexEstablishment($block = false, $flexname, $f_flexname_outer, $f_flexname_inner, $f_inlineStyle, $f_content, $f_mod = false, $f_pageID = false)
{
    ob_start();
    $flexname = urlencode(strtolower($f_flexname_outer));
    // Create custom id attribute.
    $id = $flexname;
    // Create class attribute allowing for custom "className" and "align" values.
    $class_name = $flexname;
    $f_bg = get_field('advanced_settings_outer-bg', $f_pageID);
    if ($f_bg) {
        $f_bg = 'background-color:' . $f_bg . ';';
    } else {
        $f_bg = '';
    }
    $f_img = get_field('advanced_settings_outer-img', $f_pageID);
    if ($f_img) {
        $f_img = 'background-image:url(' . $f_img['url'] . ');';
        $f_img_pos = get_field('advanced_settings_outer-img_pos', $f_pageID);
        $f_img .= 'background-position: ' . $f_img_pos . ';';
        $f_img_size = get_field('advanced_settings_outer-img_size', $f_pageID);
        $f_img .= 'background-size: ' . $f_img_size . ';';
    } else {
        $f_img = '';
    }
    $f_color_override = get_field('advanced_settings_content_color_override', $f_pageID);
    if ($f_color_override) {
        if ($f_color_override == 'light') {
            $f_color_override = 'content-override-light';
        } else {
            $f_color_override = 'content-override-dark';
        }
    } else {
        $f_color_override = '';
    }
    $f_gradient_overlay = get_field('advanced_settings_gradient_overlay', $f_pageID);
    if ($f_gradient_overlay) {
        if ($f_gradient_overlay['overall_opacity']) {
            $opacity = 'opacity:' . $f_gradient_overlay['overall_opacity'] . ';';
        } else {
            $opacity = '';
        }
        $gradient_overlay =  '<div class="flex-gradient" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; ' . $opacity . ' ';
        if ($f_gradient_overlay['color_one']) {
            $f_color_one = $f_gradient_overlay['color_one'];
        } else {
            $f_color_one = '';
        }
        if ($f_gradient_overlay['color_two']) {
            $f_color_two = $f_gradient_overlay['color_two'];
        } else {
            $f_color_two = '';
        }
        $gradient_overlay .=  'background: linear-gradient(90deg, ' . $f_color_one . ', ' . $f_color_two . ' );';
        $gradient_overlay .=  ' "> </div> ';
    } else {
        $gradient_overlay = '';
    }
    $flexname_inner = urlencode(strtolower($f_flexname_inner));
    $f_cl_outer = get_field('advanced_settings_class_assigning_outer', $f_pageID);
    $f_cl_inner = get_field('advanced_settings_class_assigning_inner', $f_pageID);
    $f_id_outer = get_field('advanced_settings_id_assigning_outer', $f_pageID);
    $f_id_inner = get_field('advanced_settings_id_assigning_inner', $f_pageID);

    if ($block) {
        /**
         * $blockname Block Template.
         *
         * @param   array $block The block settings and attributes.
         * @param   string $content The block inner HTML (empty).
         * @param   bool $is_preview True during AJAX preview.
         * @param   (int|string) $post_id The post ID this block is saved to.
         */
        $id .= ' ' . $block['id'];
        if (!empty($block['anchor'])) {
            $id .= ' ' . $block['anchor'];
        }
        if (!empty($block['className'])) {
            $class_name .= ' ' . $block['className'];
        }
        if (!empty($block['align'])) {
            // We disable the align attribute class since it causes container breakage.
            // $class_name .= ' align' . $block['align'];
        }
    }
?>
    <div id="<?php echo esc_attr($id . ' ' . $f_id_outer); ?>" class="<?php echo esc_attr($class_name . ' ' . $f_cl_outer . ' ' . $f_color_override); ?>" style="<?= ' position:relative; ' . $f_bg . $f_img . ' ' . $f_inlineStyle . ' ' . ronikdesigns_AdvancedSettings('flex'); ?>">
        <?php if ($f_gradient_overlay['enable_gradient']) {
            echo $gradient_overlay;
        } ?>
        <div style="z-index:1; width:100%; <?= ronikdesigns_AdvancedSettingsInner('flex'); ?>" class="<?php echo esc_attr($flexname_inner . ' ' . $f_cl_inner); ?>" id="<?php echo esc_attr($f_id_inner); ?>">
            <?php
            if ($f_mod) {
                $f_content('mod');
            } else {
                $f_content('');
            }
            ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}
