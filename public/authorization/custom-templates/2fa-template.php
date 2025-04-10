<?php
/**
 * Template Name: Ronik 2fa
 *
 */

$authProcessor = new RonikAuthProcessor;

// ✅ Check if 2FA is enabled in settings, otherwise redirect to prevent access to this page
$f_auth = get_field('mfa_settings', 'options');
if(!$f_auth['enable_2fa_settings']){
	$dataUrl['reUrl'] = array(''); // Pages to prevent looping on
	$dataUrl['reDest'] = ''; // Default destination
	$authProcessor->ronikRedirectLoopApproval($dataUrl);

}

// ✅ Parse 2FA status from URL query strings
$f_success = isset($_GET['sms-success']) ? $_GET['sms-success'] : false;
$f_expired = '';
if(isset($_GET["sms-error"])){
    if($_GET["sms-error"] == 'expired'){
		$f_expired = 'Your 2fa has expired. Please re-authenticate!';
    }
	if($_GET["sms-error"] == 'saved'){
		$f_message = 'Your MFA has saved successfully.';
    }
}

// ✅ If 2FA verification was successful, redirect to the user's original intended path
if($f_success){
	$authProcessor->ronik_authorize_success_redirect_path();
}

get_header();

// ✅ Apply customizable filters for header, instructions, content, and footer
$f_header = apply_filters( 'ronikdesign_2fa_custom_header', false );
$f_content = apply_filters( 'ronikdesign_2fa_custom_content', false );
$f_instructions = apply_filters( 'ronikdesign_2fa_custom_instructions', false );
$f_footer = apply_filters( 'ronikdesign_2fa_custom_footer', false );
$f_post_instructions = apply_filters( 'ronikdesign_2fa_post_custom_instructions', false );

// ✅ Retrieve user-specific data from user meta
$f_mfa_settings = get_field( 'mfa_settings', 'options');
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
			<?php do_action('auth_user-lockout'); ?>
		<?php } else { ?>
			<?php if($f_content){ ?>
				<?= $f_content(); ?>
			<?php } ?>
			<?php
			// ✅ If the user's secret is valid and not marked 'invalid', show post-verification instruction
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
				// ✅ Fallback content when no valid 2FA secret is available (likely unregistered)
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