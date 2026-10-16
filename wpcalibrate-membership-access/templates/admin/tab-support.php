<?php
/**
 * Admin Support Tab.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

defined( 'ABSPATH' ) || exit;

$brand_icon_url = WPCALIBRATE_MEMBERSHIP_ACCESS_URL . 'assets/branding/icon-dark.png';
?>
<div class="wpcal-section-card">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'WPCalibrate Support & Resources', 'wpcalibrate-membership-access' ); ?></h2>
	</div>

	<div class="wpcal-support-grid">
		<div class="wpcal-support-info">
			<div class="wpcal-support-brand">
				<img src="<?php echo esc_url( $brand_icon_url ); ?>" alt="WPCalibrate" width="48" height="48" />
				<div>
					<h3><?php esc_html_e( 'WPCalibrate Enterprise Plugins', 'wpcalibrate-membership-access' ); ?></h3>
					<p><?php esc_html_e( 'Engineered for high-reliability, performance, and seamless WordPress extensibility.', 'wpcalibrate-membership-access' ); ?></p>
				</div>
			</div>

			<table class="wpcal-status-table widefat striped" style="margin-top: 20px;">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Official Website', 'wpcalibrate-membership-access' ); ?></strong></td>
						<td><a href="<?php echo esc_url( 'https://wpcalibrate.com' ); ?>" target="_blank" rel="noopener noreferrer">https://wpcalibrate.com</a></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Plugin Marketplace', 'wpcalibrate-membership-access' ); ?></strong></td>
						<td><a href="<?php echo esc_url( 'https://marketplace.wpcalibrate.com/' ); ?>" target="_blank" rel="noopener noreferrer">https://marketplace.wpcalibrate.com/</a></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Support Email', 'wpcalibrate-membership-access' ); ?></strong></td>
						<td><a href="<?php echo esc_url( 'mailto:support@wpcalibrate.com' ); ?>">support@wpcalibrate.com</a></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Phone / WhatsApp Support', 'wpcalibrate-membership-access' ); ?></strong></td>
						<td><a href="<?php echo esc_url( 'https://wa.me/447474795976' ); ?>" target="_blank" rel="noopener noreferrer">+447474795976</a></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Installed Version', 'wpcalibrate-membership-access' ); ?></strong></td>
						<td><code>v<?php echo esc_html( WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION ); ?></code></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="wpcal-support-help">
			<h3><?php esc_html_e( 'Need Assistance?', 'wpcalibrate-membership-access' ); ?></h3>
			<p>
				<?php esc_html_e( 'Our dedicated engineering support team is available via email and WhatsApp to assist with custom access rules, WooCommerce order synchronization, migration, and developer hooks.', 'wpcalibrate-membership-access' ); ?>
			</p>
			<a href="<?php echo esc_url( 'https://wa.me/447474795976' ); ?>" target="_blank" rel="noopener noreferrer" class="wpcal-btn wpcal-btn-primary">
				<?php esc_html_e( 'Chat on WhatsApp', 'wpcalibrate-membership-access' ); ?>
			</a>
			<a href="<?php echo esc_url( 'mailto:support@wpcalibrate.com' ); ?>" class="wpcal-btn wpcal-btn-secondary" style="margin-left: 8px;">
				<?php esc_html_e( 'Email Support Desk', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>
	</div>
</div>
