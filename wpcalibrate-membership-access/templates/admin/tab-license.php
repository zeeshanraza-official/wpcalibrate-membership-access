<?php
/**
 * Admin License Tab.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

defined( 'ABSPATH' ) || exit;

use WPCalibrate\MembershipAccess\Licensing\LicenseProviderInterface;

/**
 * Variables available:
 *
 * @var LicenseProviderInterface $provider
 * @var array                    $license_info
 */

$provider     = isset( $provider ) && $provider instanceof LicenseProviderInterface ? $provider : new \WPCalibrate\MembershipAccess\Licensing\UnconfiguredProvider();
$license_info = isset( $license_info ) && is_array( $license_info ) ? $license_info : [];
?>
<div class="wpcal-section-card">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'License Management', 'wpcalibrate-membership-access' ); ?></h2>
	</div>

	<?php if ( ! $provider->is_configured() ) : ?>
		<div class="wpcal-banner wpcal-banner--info">
			<h3><?php esc_html_e( 'Licensing Provider Not Configured', 'wpcalibrate-membership-access' ); ?></h3>
			<p>
				<?php esc_html_e( 'No external commercial licensing provider has been registered for this installation. Core membership creation, user assignments, access restriction, and WooCommerce selling remain fully operational.', 'wpcalibrate-membership-access' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=license' ) ); ?>">
		<?php wp_nonce_field( 'wpcal_save_license_nonce', '_wpcal_nonce' ); ?>
		<input type="hidden" name="wpcal_action" value="save_license" />

		<table class="form-table">
			<tr>
				<th scope="row"><label for="license_key"><?php esc_html_e( 'License Key', 'wpcalibrate-membership-access' ); ?></label></th>
				<td>
					<input type="text" name="license_key" id="license_key" class="regular-text" value="<?php echo esc_attr( $license_info['masked_key'] ); ?>" placeholder="XXXX-XXXX-XXXX-XXXX" />
					<p class="description"><?php esc_html_e( 'Enter your license key obtained from the WPCalibrate Marketplace.', 'wpcalibrate-membership-access' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'License Status', 'wpcalibrate-membership-access' ); ?></th>
				<td>
					<span class="wpcal-badge wpcal-badge--default">
						<?php echo esc_html( ucfirst( $license_info['status'] ) ); ?>
					</span>
					<p class="description"><?php echo esc_html( $license_info['message'] ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Licensing Provider Engine', 'wpcalibrate-membership-access' ); ?></th>
				<td>
					<code><?php echo esc_html( $provider->get_name() ); ?></code>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" class="wpcal-btn wpcal-btn-primary" value="<?php esc_attr_e( 'Save License Key', 'wpcalibrate-membership-access' ); ?>" />
		</p>
	</form>
</div>
