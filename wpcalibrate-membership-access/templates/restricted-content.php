<?php
/**
 * Restricted Content Notice Template.
 *
 * @package WPCalibrate\MembershipAccess
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables available:
 *
 * @var string $message Custom or default restricted content message.
 * @var array  $rule Resolved access rule.
 * @var int    $user_id Current user ID (0 for guest).
 */

$message      = isset( $message ) ? (string) $message : '';
$rule         = isset( $rule ) && is_array( $rule ) ? $rule : [];
$user_id      = isset( $user_id ) ? (int) $user_id : 0;
$is_logged_in = $user_id > 0;
$login_url    = wp_login_url( get_permalink() );
?>
<div class="wpcal-restricted-notice">
	<div class="wpcal-restricted-notice__icon" aria-hidden="true">
		<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
			<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
			<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
		</svg>
	</div>
	<div class="wpcal-restricted-notice__content">
		<h3 class="wpcal-restricted-notice__title"><?php esc_html_e( 'Members-Only Content', 'wpcalibrate-membership-access' ); ?></h3>
		<p class="wpcal-restricted-notice__message"><?php echo wp_kses_post( $message ); ?></p>

		<div class="wpcal-restricted-notice__actions">
			<?php if ( ! $is_logged_in ) : ?>
				<a href="<?php echo esc_url( $login_url ); ?>" class="wpcal-btn wpcal-btn-primary">
					<?php esc_html_e( 'Log In to Access', 'wpcalibrate-membership-access' ); ?>
				</a>
			<?php else : ?>
				<span class="wpcal-restricted-notice__status">
					<?php esc_html_e( 'Your current account does not have access to this resource.', 'wpcalibrate-membership-access' ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>
</div>
