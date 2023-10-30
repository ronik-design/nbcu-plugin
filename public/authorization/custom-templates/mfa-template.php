<?php
/**
 * Template Name: Ronik mfa
 *
 */

// Lets check if 2fa is enabled. If not we kill it.
$f_auth = get_field('mfa_settings', 'options');
if(!$f_auth['enable_mfa_settings']){
	// Redirect Magic, custom function to prevent an infinite loop.
	$dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php');
	$dataUrl['reDest'] = '';
	ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
}
// We put this in the header for fast redirect..
$f_success = isset($_GET['mfa-success']) ? $_GET['mfa-success'] : false;
$f_error = isset($_GET['mfa-error']) ? $_GET['mfa-error'] : false;
$f_expired = '';
$f_message = '';
if(isset($_GET["mfaredirect"])){
    if($_GET["mfaredirect"] == 'expired'){
		$f_expired = 'Your MFA has expired. Please re-authenticate!';
    }
	if($_GET["mfaredirect"] == 'saved'){
		$f_message = 'Your MFA has saved successfully.';
    }
}
// Success message
if($f_success){
	ronik_authorize_success_redirect_path();
    // // Lets Check for the password reset url cookie.
    // $cookie_name = "ronik-auth-reset-redirect";
    // if(isset($_COOKIE[$cookie_name])) {
    //     wp_redirect( esc_url(home_url(urldecode($_COOKIE[$cookie_name]))) );
    //     exit;
    // } else {
    //     // We run our backup plan for redirecting back to previous page.
    //     // The downside this wont account for pages that were clicked during the redirect. So it will get the page that was previously visited.
    // }
}
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
			<?php if( strlen($get_auth_lockout_counter) > 6){ ?>
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
				<div class="mfa-content">
					<?php if($f_content){ ?>
						<?= $f_content(); ?>
					<?php }

					if($mfa_validation !== 'not_registered'){
						if($f_mfa_settings['mfa_post_content_title']){ ?>
							<?= $f_mfa_settings['mfa_post_content_title']; ?>
						<?php } ?>
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
						if($f_mfa_settings['mfa_content_title']){ ?>
							<?= $f_mfa_settings['mfa_content_title']; ?>
						<?php } ?>
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
				</div>
			<?php } ?>


			<?php
				if( strlen($get_auth_lockout_counter) > 6){
					$f_expiration_time = 3;
					$past_date = strtotime((new DateTime())->modify('-'.$f_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
					if( $past_date > $get_auth_lockout_counter ){
						// delete_user_meta(get_current_user_id(), 'auth_lockout_counter');
					} else { ?>
						<script>
							timeLockoutValidationChecker();
							// This is critical we basically re-run the timeValidationAjax function every 30 seconds
							function timeLockoutValidationChecker() {
								console.log('Lets check the timeout');
								// Lets trigger the validation on page load.
								timeValidationAjax('invalid', 'invalid', 'valid');
							}
							setInterval(timeLockoutValidationChecker, (60000/2));

							function timeValidationAjax( killValidation, timeChecker, timeLockoutChecker ){
								jQuery.ajax({
									type: 'POST',
									url: wpVars.ajaxURL,
									data: {
										action: 'ronikdesigns_admin_auth_verification',
										killValidation: killValidation,
										timeChecker: timeChecker,
										timeLockoutChecker: timeLockoutChecker,
										nonce: wpVars.nonce
									},
									success: data => {
										if(data.success){
											console.log(data);
											if(data.data == 'reload'){
												setTimeout(() => {
													window.location.reload(true);
												}, 50);
											}
										} else{
											console.log('error');
											console.log(data);
											console.log(data.data);
											// window.location.reload(true);
										}
										console.log(data);
									},
									error: err => {
										console.log(err);
										// window.location.reload(true);
									}
								});
							}
						</script>
						<!-- <div id="countdown"></div> -->
						<script>
							var timeleft = 60*3;
							var downloadTimer = setInterval(function(){
								if(timeleft <= 0){
									clearInterval(downloadTimer);
									// document.getElementById("countdown").innerHTML = "Reloading";
									setTimeout(() => {
										window.location = window.location.pathname + "?sms-success=success";
									}, 1000);
								} else {
									// document.getElementById("countdown").innerHTML = "Page will reload in: " + timeleft + " seconds";
								}
								timeleft -= 1;
							}, 1000);
						</script>
					<?php }
				?>
				<?php
				} else {
					do_action('mfa-registration-page');
				}
			?>
		</div>
	</div>

	<?php if($f_footer){ ?><?= $f_footer(); ?><?php } ?>

<?php get_footer(); ?>
