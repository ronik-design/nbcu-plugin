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

if(isset($_GET["2faredirect"])){
    if($_GET["2faredirect"] == 'expired'){
		$f_expired = 'Your 2fa has expired. Please re-authenticate!';
    }
	if($_GET["2faredirect"] == 'saved'){
		$f_message = 'Your MFA has saved successfully.';
    }
}


// Success message
if($f_success){
    // Lets Check for the password reset url cookie.
    $cookie_name = "ronik-auth-reset-redirect";
    if(isset($_COOKIE[$cookie_name])) {
        wp_redirect( esc_url(home_url(urldecode($_COOKIE[$cookie_name]))) );
        exit;
    } else {
        // We run our backup plan for redirecting back to previous page.
        // The downside this wont account for pages that were clicked during the redirect. So it will get the page that was previously visited.

    }
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

	<div class="twofa-wrapper">
		<div class="twofa-message">
			<?php if($f_success){ ?>
				<div class="twofa-message__success">Verification Success!</div>
			<?php } ?>
			<?php if($f_message){ ?>
				<div class="twofa-message__success">Authentication Saved!</div>
			<?php } ?>
			<?php if($f_error == 'nomatch'){ ?>
				<div class="twofa-message__nomatch">Sorry, the verification code entered is invalid.</div>
			<?php } ?>
			<?php if($f_expired){ ?>
				<div class="twofa-message__nomatch"><?= $f_expired; ?></div>
			<?php } ?>
		</div>
		<div class="twofa-content">
			<?php if($f_content){ ?>
				<?= $f_content(); ?>
			<?php } ?>
		    <?php
			if( ($sms_2fa_secret !== 'invalid') && $sms_2fa_secret ){
				if($f_mfa_settings['2fa_post_content']){ ?>
					<?= $f_mfa_settings['2fa_post_content']; ?>
				<?php } ?>
				<br></br>
				<?php if($f_post_instructions){ ?>
					<?= $f_post_instructions(); ?>
				<?php } else { ?>
					<div class="instructions">
						<?php if($f_mfa_settings['2fa_post_instructions_content']){ ?>
							<?= $f_mfa_settings['2fa_post_instructions_content']; ?>
						<?php } ?>
					</div>
				<?php }
			} else {
				if($f_mfa_settings['2fa_content']){ ?>
					<?= $f_mfa_settings['2fa_content']; ?>
				<?php } ?>
				<br></br>
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
			?>
		</div>
		<br><br>
		<?php
			if( strlen($get_auth_lockout_counter) > 6){
				$f_expiration_time = 3;
				$past_date = strtotime((new DateTime())->modify('-'.$f_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
				if( $past_date > $get_auth_lockout_counter ){
					delete_user_meta(get_current_user_id(), 'auth_lockout_counter');
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
								url: "<?php echo esc_url( admin_url('admin-post.php') ); ?>",
								data: {
									action: 'ronikdesigns_admin_auth_verification',
									killValidation: killValidation,
									timeChecker: timeChecker,
									timeLockoutChecker: timeLockoutChecker,
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
					Account is locked out for 3 minutes. Please try again later!

				<?php }
			?>
			<?php
			} else {
				do_action('2fa-registration-page');
			}
		?>
	</div>

	<?php if($f_footer){ ?><?= $f_footer(); ?><?php } ?>

<?php get_footer(); ?>

