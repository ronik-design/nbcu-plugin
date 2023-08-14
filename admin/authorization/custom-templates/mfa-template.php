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
// We put this in the header for fast redirect..
$f_success = isset($_GET['mfa-success']) ? $_GET['mfa-success'] : false;
$f_error = isset($_GET['mfa-error']) ? $_GET['mfa-error'] : false;
?>

<?php
get_header(); 



$f_header = apply_filters( 'ronikdesign_auth_custom_header', false );
$f_content = apply_filters( 'ronikdesign_mfa_custom_content', false );
$f_instructions = apply_filters( 'ronikdesign_mfa_custom_instructions', false );
$f_post_instructions = apply_filters( 'ronikdesign_mfa_post_custom_instructions', false );
$f_footer = apply_filters( 'ronikdesign_mfa_custom_footer', false );
$f_mfa_settings = get_field( 'mfa_settings', 'options');
$mfa_validation = get_user_meta(get_current_user_id(),'mfa_validation', true);


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
		<div class="mfa-message">
			<?php if($f_success){ ?>
				<div class="mfa-message__success">Verification Success!</div>
			<?php } ?>
			<?php if($f_error == 'nomatch'){ ?>
				<div class="mfa-message__nomatch">Sorry your verification code does not match!</div>
			<?php } ?>
		</div>

		<?php if($f_content){ ?>
			<?= $f_content(); ?>
		<?php } 

		if($mfa_validation !== 'not_registered'){
			if($f_mfa_settings['mfa_post_content']){ ?>
				<?= $f_mfa_settings['mfa_post_content']; ?>
			<?php } ?>
			<br></br>
			<?php if($f_post_instructions){ ?>
				<?= $f_post_instructions(); ?>
			<?php } else { ?>
				<div class="instructions">
					<?php if($f_mfa_settings['mfa_post_instructions_content']){ ?>
						<?= $f_mfa_settings['mfa_post_instructions_content']; ?>
					<?php } ?>
				</div>
			<?php } 
		} else {
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
			<?php } 
		}
		?>
		<br><br>
		<?php do_action('mfa-registration-page'); ?>
	</div>

	<?php if($f_footer){ ?><?= $f_footer(); ?><?php } ?>

<?php get_footer(); ?>

