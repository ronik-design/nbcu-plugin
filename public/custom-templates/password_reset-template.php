<?php
/**
 * Template Name: Ronik Reset Password
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

$authProcessor = new RonikAuthProcessor();
$currentUser   = wp_get_current_user();
$settings      = get_field('password_reset_settings', 'options');
$pastDate      = strtotime((new DateTime())->modify('-' . $settings['pr_days'] . ' day')->format('Y-m-d'));
$lastReset     = get_user_meta($currentUser->ID, 'wp_user-settings-time-password-reset', true);

if ($lastReset > $pastDate) {
    $authProcessor->ronik_authorize_success_redirect_path();
    wp_redirect(home_url());
    exit;
}

$success = isset($_GET['pr-success']);
$error   = $_GET['pr-error'] ?? false;

if ($success) {
    error_log('Password reset success');
    if (isset($_COOKIE['ronik-password-reset-redirect'])) {
        wp_redirect(home_url(urldecode($_COOKIE['ronik-password-reset-redirect'])));
        exit;
    }
}

get_header();

$header       = apply_filters('ronikdesign_passwordreset_custom_header', false);
$content      = apply_filters('ronikdesign_passwordreset_custom_content', false);
$instructions = apply_filters('ronikdesign_passwordreset_custom_instructions', false);
$footer       = apply_filters('ronikdesign_passwordreset_custom_footer', false);
?>

<?php if ($header) echo $header(); ?>

<div class="pass-reset-wrapper">
    <div class="pass-reset-message">
        <?php if ($success): ?>
            <div class="pass-reset-message__success">Password Successfully Reset</div>
        <?php endif; ?>

        <?php
        $messages = [
            'alreadyexists'         => 'Sorry your password is already used! Please choose a different password!',
            'nomatch'               => 'Sorry your password does not match!',
            'weak'                  => 'Sorry you did not input a strong enough password!',
            'missing'               => 'Sorry you did not input a password!',
            'no-uppercase'          => 'Sorry your input does not contain an uppercase letter!',
            'no-lowercase'          => 'Sorry your input does not contain a lowercase letter!',
            'pastused'              => 'This password has already been used! Please choose a different password!',
            'no-special-characters' => 'Sorry your input does not contain a special character!',
        ];

        if ($error && isset($messages[$error])) {
            echo '<div class="pass-reset-message__nomatch">' . esc_html($messages[$error]) . '</div>';
        }
        ?>
    </div>

    <br>

    <?php if ($content) echo $content(); ?>
    <br>
    <?php if ($instructions) echo $instructions(); ?>
    <br>

    <?php if ($currentUser): ?>
        <form action="<?= esc_url(admin_url('admin-ajax.php')); ?>" method="post">
            <!-- Hidden input to prevent autofill warnings -->
            <input type="text" name="email" value="..." autocomplete="username email" style="display: none;" >

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" class="adv-passwordchecker" autocomplete="new-password" required>

            <label for="retype_password">Retype Password:</label>
            <input type="password" name="retype_password" id="retype_password" class="adv-passwordchecker" autocomplete="new-password" required>

            <input type="hidden" name="action" value="ronikdesigns_admin_password_reset">
            <input type="hidden" name="nonce" value="<?= esc_attr(wp_create_nonce('ajax-nonce-password')); ?>">

            <button type="submit" class="ronik-password-disabled">Reset Password</button>
        </form>
    <?php else: ?>
        <p>Whoops, something went wrong!</p>
    <?php endif; ?>

    <br><br>
</div>

<?php if ($footer) echo $footer(); ?>

<?php get_footer(); ?>
