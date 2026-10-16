<?php
/**
 * Admin WooCommerce Integration Tab.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

defined( 'ABSPATH' ) || exit;

use WPCalibrate\MembershipAccess\Membership\Plan;

/**
 * Variables available:
 *
 * @var array  $settings Plugin settings array.
 * @var Plan[] $plans All plans.
 */

$settings      = isset( $settings ) && is_array( $settings ) ? $settings : [];
$plans         = isset( $plans ) && is_array( $plans ) ? $plans : [];
$is_wc_active  = class_exists( 'WooCommerce' );
$is_wcs_active = class_exists( 'WC_Subscriptions' );
?>
<div class="wpcal-section-card">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'WooCommerce Integration Configuration', 'wpcalibrate-membership-access' ); ?></h2>
	</div>

	<?php if ( ! $is_wc_active ) : ?>
		<div class="wpcal-banner wpcal-banner--info">
			<h3><?php esc_html_e( 'WooCommerce is Currently Inactive', 'wpcalibrate-membership-access' ); ?></h3>
			<p>
				<?php esc_html_e( 'WPCalibrate Membership Access is fully operational as a standalone membership system. When WooCommerce is installed and activated, you can link plans directly to products, sell memberships through checkout, and restrict products to members.', 'wpcalibrate-membership-access' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="wpcal-banner wpcal-banner--success">
			<h3><?php esc_html_e( 'WooCommerce Integration Active', 'wpcalibrate-membership-access' ); ?></h3>
			<p>
				<?php esc_html_e( 'WooCommerce Core detected. High-Performance Order Storage (HPOS) and Checkout Block compatibility are declared and enabled.', 'wpcalibrate-membership-access' ); ?>
			</p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=woocommerce' ) ); ?>">
			<?php wp_nonce_field( 'wpcal_save_wc_settings_nonce', '_wpcal_nonce' ); ?>
			<input type="hidden" name="wpcal_action" value="save_wc_settings" />

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Grant Membership on Order Status', 'wpcalibrate-membership-access' ); ?></th>
					<td>
						<?php
						$current_statuses = (array) ( $settings['wc_grant_order_statuses'] ?? [ 'processing', 'completed' ] );
						?>
						<label style="display:block;margin-bottom:6px;">
							<input type="checkbox" name="wc_grant_order_statuses[]" value="processing" <?php checked( in_array( 'processing', $current_statuses, true ) ); ?> />
							<code>processing</code> &mdash; <?php esc_html_e( 'Payment received, order in processing', 'wpcalibrate-membership-access' ); ?>
						</label>
						<label style="display:block;">
							<input type="checkbox" name="wc_grant_order_statuses[]" value="completed" <?php checked( in_array( 'completed', $current_statuses, true ) ); ?> />
							<code>completed</code> &mdash; <?php esc_html_e( 'Order completed / fulfilled', 'wpcalibrate-membership-access' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Memberships are never granted while an order is unpaid or pending payment.', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="wc_revoke_on_refund"><?php esc_html_e( 'Order Refund Action', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<select name="wc_revoke_on_refund" id="wc_revoke_on_refund">
							<option value="cancel" <?php selected( $settings['wc_revoke_on_refund'] ?? 'cancel', 'cancel' ); ?>><?php esc_html_e( 'Cancel membership immediately', 'wpcalibrate-membership-access' ); ?></option>
							<option value="expire" <?php selected( $settings['wc_revoke_on_refund'] ?? '', 'expire' ); ?>><?php esc_html_e( 'Expire membership immediately', 'wpcalibrate-membership-access' ); ?></option>
							<option value="none" <?php selected( $settings['wc_revoke_on_refund'] ?? '', 'none' ); ?>><?php esc_html_e( 'Do not change membership status', 'wpcalibrate-membership-access' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Action taken when an eligible paid order is refunded.', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="wc_revoke_on_cancelled"><?php esc_html_e( 'Order Cancellation Action', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<select name="wc_revoke_on_cancelled" id="wc_revoke_on_cancelled">
							<option value="cancel" <?php selected( $settings['wc_revoke_on_cancelled'] ?? 'cancel', 'cancel' ); ?>><?php esc_html_e( 'Cancel membership', 'wpcalibrate-membership-access' ); ?></option>
							<option value="none" <?php selected( $settings['wc_revoke_on_cancelled'] ?? '', 'none' ); ?>><?php esc_html_e( 'Do not change membership status', 'wpcalibrate-membership-access' ); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="wc_product_restriction_mode"><?php esc_html_e( 'Product Restriction Behavior', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<select name="wc_product_restriction_mode" id="wc_product_restriction_mode">
							<option value="disabled_purchase" <?php selected( $settings['wc_product_restriction_mode'] ?? 'disabled_purchase', 'disabled_purchase' ); ?>>
								<?php esc_html_e( 'Show product in catalog, but disable purchasing / Add to Cart for non-members', 'wpcalibrate-membership-access' ); ?>
							</option>
							<option value="hide" <?php selected( $settings['wc_product_restriction_mode'] ?? '', 'hide' ); ?>>
								<?php esc_html_e( 'Completely hide restricted products from catalog and search queries', 'wpcalibrate-membership-access' ); ?>
							</option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="wc_product_restricted_msg"><?php esc_html_e( 'Restricted Purchase Notice', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="text" name="wc_product_restricted_msg" id="wc_product_restricted_msg" class="large-text" value="<?php echo esc_attr( $settings['wc_product_restricted_msg'] ?? __( 'This item requires an active membership to purchase.', 'wpcalibrate-membership-access' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Displayed in place of the purchase button when non-members view a restricted product.', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" class="wpcal-btn wpcal-btn-primary" value="<?php esc_attr_e( 'Save WooCommerce Settings', 'wpcalibrate-membership-access' ); ?>" />
			</p>
		</form>
	<?php endif; ?>
</div>

<div class="wpcal-section-card">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'WooCommerce Subscriptions Extension', 'wpcalibrate-membership-access' ); ?></h2>
	</div>
	<table class="wpcal-status-table widefat striped">
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'Subscriptions Status', 'wpcalibrate-membership-access' ); ?></strong></td>
				<td>
					<?php if ( $is_wcs_active ) : ?>
						<span class="wpcal-status-tag wpcal-status-tag--ok"><?php esc_html_e( 'Enabled & Hooked', 'wpcalibrate-membership-access' ); ?></span>
					<?php else : ?>
						<span class="wpcal-status-tag wpcal-status-tag--optional"><?php esc_html_e( 'WooCommerce Subscriptions Not Active', 'wpcalibrate-membership-access' ); ?></span>
					<?php endif; ?>
				</td>
				<td>
					<?php esc_html_e( 'When WooCommerce Subscriptions is active, subscription product purchases automatically synchronise recurring renewals, suspensions, cancellations, and reactivations without imitation billing engines.', 'wpcalibrate-membership-access' ); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>
