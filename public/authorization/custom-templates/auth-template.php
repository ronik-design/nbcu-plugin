<?php
/**
 * Template Name: Ronik Authorization
 *
 */

// Lets check if 2fa && MFA  is enabled. If not we kill it.
$f_auth = get_field('mfa_settings', 'options');
$authProcessor = new RonikAuthProcessor;

if(!$f_auth['enable_2fa_settings'] && !$f_auth['enable_mfa_settings']){
	// Redirect Magic, custom function to prevent an infinite loop.
	$dataUrl['reUrl'] = array('');
	$dataUrl['reDest'] = '';
	$authProcessor->ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
}
// We put this in the header for fast redirect..
$f_success = isset($_GET['sms-success']) ? $_GET['sms-success'] : false;
// Success message
if($f_success){
	$authProcessor->ronik_authorize_success_redirect_path();
}

get_header();

$f_header = apply_filters( 'ronikdesign_auth_custom_header', false );
$f_content = apply_filters( 'ronikdesign_auth_custom_content', false );
$f_instructions = apply_filters( 'ronikdesign_auth_custom_instructions', false );
$f_footer = apply_filters( 'ronikdesign_auth_custom_footer', false );
$f_mfa_settings = get_field( 'mfa_settings', 'options');
$get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);
$f_error = isset($_GET['sms-error']) ? $_GET['sms-error'] : false;
?>
	<?php if($f_header){ ?><?= $f_header(); ?><?php } ?>
	<div class="auth-wrapper" style="background-image: url('<?php echo $f_mfa_settings['auth_content_bgimage']['url']; ?>');">
		<div class="auth-flagger">
			<img src="<?php echo plugin_dir_url( __DIR__ ).'/images/flagger.svg'; ?>">
		</div>
		<div class="auth-content">
			<?php if( $get_auth_status == 'auth_select_sms-missing' ){ ?>
				<?php if($f_content){ ?>
					<?= $f_content(); ?>
				<?php }
				if($f_mfa_settings['auth_missing-sms_content_title']){ ?>
					<?= $f_mfa_settings['auth_missing-sms_content_title']; ?>
				<?php } ?>
				<?php if($f_instructions){ ?>
					<?= $f_instructions(); ?>
				<?php } else { ?>
					<div class="instructions">
						<?php if($f_mfa_settings['auth_missing-sms_instructions_content']){ ?>
							<?= $f_mfa_settings['auth_missing-sms_instructions_content']; ?>
						<?php } ?>
					</div>
				<?php } ?>
			<?php } else { ?>
				<?php if($f_content){ ?>
					<?= $f_content(); ?>
				<?php }
				if($f_mfa_settings['auth_content_title']){ ?>
					<h2><?= $f_mfa_settings['auth_content_title']; ?></h2>
				<?php } ?>
				<?php if($f_instructions){ ?>
					<?= $f_instructions(); ?>
				<?php } else { ?>
					<div class="instructions">
						<?php if($f_mfa_settings['auth_instructions_content']){ ?>
							<?= $f_mfa_settings['auth_instructions_content']; ?>
						<?php } ?>
					</div>
				<?php } ?>
			<?php } ?>
			<?php do_action('auth-registration-page'); ?>
		</div>
	</div>
	<?php if($f_footer){ ?><?= $f_footer(); ?><?php } ?>
<?php get_footer(); ?>
