<?php
/**
 * Template Name: Ronik mfa
 *
 */

// Lets check if 2fa is enabled. If not we kill it. 
$f_auth = get_field('mfa_settings', 'options');
if(!$f_auth['enable_mfa_settings']){
	// Redirect Magic, custom function to prevent an infinite loop.
	$dataUrl['reUrl'] = array('/wp-admin/admin-post.php');
	$dataUrl['reDest'] = '';
	ronikRedirectLoopApproval($dataUrl, "ronik-2fa-reset-redirect");
}
// unset($_SESSION["auth-select"]);

?>

<?php
get_header(); 



$f_header = apply_filters( 'ronikdesign_mfa_custom_header', false );
$f_content = apply_filters( 'ronikdesign_mfa_custom_content', false );
$f_instructions = apply_filters( 'ronikdesign_mfa_custom_instructions', false );
$f_footer = apply_filters( 'ronikdesign_mfa_custom_footer', false );
$f_mfa_settings = get_field( 'mfa_settings', 'options');


if( isset($_GET["mfaredirect"]) ){
	if($_GET["mfaredirect"] == 'home'){
		$f_instructions_verfied_complete = true;
	} else {
		$f_instructions_verfied_complete = false;
	}
} else{
	$f_instructions_verfied_complete = false;
}
?>
	<?php if($f_header){ ?><?= $f_header(); ?><?php } ?>

	<div class="mfa-wrapper">

		<?php if($f_content){ ?>
			<?= $f_content(); ?>
		<?php } 
		if($f_mfa_settings['mfa_content']){ ?>
			<?= $f_mfa_settings['mfa_content']; ?>
		<?php } ?>
		<br></br>
		<?php if($f_instructions){ ?>
			<?= $f_instructions(); ?>
		<?php } else { ?>
			<div class="instructions">
				<?php if($f_mfa_settings['mfa_instructions_content']){ ?>
					<?= $f_mfa_settings['mfa_instructions_content']; ?>
				<?php } ?>
			</div>
		<?php } ?>


		<br><br>
		<?php do_action('mfa-registration-page'); ?>
	</div>

	<?php if($f_footer){ ?><?= $f_footer(); ?><?php } ?>

<?php get_footer(); ?>

