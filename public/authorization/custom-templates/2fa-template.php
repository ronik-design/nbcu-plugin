<?php
/**
 * Template Name: Ronik 2fa
 *
 */

// Lets check if 2fa is enabled. If not we kill it.
$f_auth = get_field('mfa_settings', 'options');
if(!$f_auth['enable_2fa_settings']){
	// Redirect Magic, custom function to prevent an infinite loop.
	$dataUrl['reUrl'] = array('');
	$dataUrl['reDest'] = '';
	ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
}

// We put this in the header for fast redirect..
$f_success = isset($_GET['sms-success']) ? $_GET['sms-success'] : false;
// sms-success
// sms-error
$f_expired = '';

if(isset($_GET["sms-error"])){
    if($_GET["sms-error"] == 'expired'){
		$f_expired = 'Your 2fa has expired. Please re-authenticate!';
    }
	if($_GET["sms-error"] == 'saved'){
		$f_message = 'Your MFA has saved successfully.';
    }
}


// Success message
if($f_success){
	ronik_authorize_success_redirect_path();
}




get_header();

$f_header = apply_filters( 'ronikdesign_2fa_custom_header', false );
$f_content = apply_filters( 'ronikdesign_2fa_custom_content', false );
$f_instructions = apply_filters( 'ronikdesign_2fa_custom_instructions', false );
$f_footer = apply_filters( 'ronikdesign_2fa_custom_footer', false );
$f_mfa_settings = get_field( 'mfa_settings', 'options');
$f_post_instructions = apply_filters( 'ronikdesign_2fa_post_custom_instructions', false );
$f_error = isset($_GET['sms-error']) ? $_GET['sms-error'] : false;

$sms_2fa_status = get_user_meta(get_current_user_id(),'sms_2fa_status', true);
$sms_2fa_secret = get_user_meta(get_current_user_id(),'sms_2fa_secret', true);
$get_auth_lockout_counter = get_user_meta(get_current_user_id(), 'auth_lockout_counter', true);

?>
	<?php if($f_header){ ?><?= $f_header(); ?><?php } ?>

	<div class="auth-wrapper" style="background-image: url('<?php echo $f_mfa_settings['auth_content_bgimage']['url']; ?>');">
		<div class="mfa-message">
			<?php if($f_success){ ?>
				<div class="mfa-message__success">Verification Success!</div>
			<?php } ?>
			<?php if($f_error == 'nomatch'){ ?>
				<div class="mfa-message__nomatch">Your code does not match, please re-authenticate.</div>
			<?php } ?>
			<?php if($f_expired){ ?>
				<div class="mfa-message__nomatch"><?= $f_expired; ?></div>
			<?php } ?>
		</div>

		<div class="auth-flagger">
			<img src="<?php echo plugin_dir_url( __DIR__ ).'/images/flagger.svg'; ?>">
		</div>

		<div class="auth-content">
			<?php if( $get_auth_lockout_counter > 6){ ?>
				<div class="mfa-content">
					<h2>Authentication failed too many times.</h2>
					<div class="instructions">
						<!-- <h4>Account is locked out for 3 minutes. Please try again later.</h4> -->
						<h4>Your account is locked.</h4>
					</div>
					<div class="auth-content-bottom">
						<div class="auth-content-bottom__helper" style="padding: 0;">
							<p>Please reach out to <a href="mailto:together@nbcuni.com?subject=Account Locked Out">together@nbcuni.com</a> for support. </p>
						</div>
					</div>
				</div>
			<?php } else { ?>
				<?php if($f_content){ ?>
					<?= $f_content(); ?>
				<?php } ?>
				<?php
				if( ($sms_2fa_secret !== 'invalid') && $sms_2fa_secret ){
					$get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);
					$get_phone_number = substr($get_phone_number, -4);	

					if($f_mfa_settings['2fa_post_content_title']){ ?>
						<?= $f_mfa_settings['2fa_post_content_title']; ?>
					<?php } ?>
					<?php if($f_post_instructions){ ?>
						<?= $f_post_instructions(); ?>
					<?php } else { ?>
						<div class="instructions">
							<?php if($f_mfa_settings['2fa_post_instructions_content']){ ?>
								<?= $f_mfa_settings['2fa_post_instructions_content']; ?>
							<?php } ?>
							<p style="padding-top: 38px;">Please enter the 6-digit code received by text message: <?= 'xxx-xxx-'.$get_phone_number; ?></p>
						</div>
					<?php } ?>
				<?php } else {
					if($f_mfa_settings['2fa_content_title']){ ?>
						<?= $f_mfa_settings['2fa_content_title']; ?>
					<?php } ?>
					<?php if($f_instructions){ ?>
						<?= $f_instructions(); ?>
					<?php } else { ?>
						<div class="instructions">
							<?php if($f_mfa_settings['2fa_instructions_content']){ ?>
								<?= $f_mfa_settings['2fa_instructions_content']; ?>
							<?php } ?>
						</div>
					<?php }
				}
				do_action('2fa-registration-page');
			?>
			<?php } ?>
		</div>
	</div>

	<?php if($f_footer){ ?><?= $f_footer(); ?><?php } ?>

<?php get_footer(); ?>

