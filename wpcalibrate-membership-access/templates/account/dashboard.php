<?php
/**
 * Member Account Dashboard Template.
 *
 * @package WPCalibrate\MembershipAccess\Frontend
 */

defined( 'ABSPATH' ) || exit;

use WPCalibrate\MembershipAccess\Membership\Membership;
use WPCalibrate\MembershipAccess\Membership\Plan;
use WPCalibrate\MembershipAccess\Membership\StatusManager;

/**
 * Variables available:
 *
 * @var int          $user_id Current logged in user ID.
 * @var Membership[] $memberships Array of user memberships.
 * @var array<int, Plan> $plans_by_id Plan cache.
 */

$user_id      = isset( $user_id ) ? (int) $user_id : 0;
$memberships  = isset( $memberships ) && is_array( $memberships ) ? $memberships : [];
$plans_by_id  = isset( $plans_by_id ) && is_array( $plans_by_id ) ? $plans_by_id : [];
$is_logged_in = $user_id > 0;
?>
<div class="wpcal-member-dashboard">
	<?php if ( ! $is_logged_in ) : ?>
		<div class="wpcal-member-login-notice">
			<h3><?php esc_html_e( 'Member Login Required', 'wpcalibrate-membership-access' ); ?></h3>
			<p><?php esc_html_e( 'Please log in to your account to view your active memberships and access privileges.', 'wpcalibrate-membership-access' ); ?></p>
			<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="wpcal-btn wpcal-btn-primary">
				<?php esc_html_e( 'Log In to My Account', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>
	<?php elseif ( empty( $memberships ) ) : ?>
		<div class="wpcal-member-empty">
			<div class="wpcal-member-empty__icon" aria-hidden="true">🛡️</div>
			<h3><?php esc_html_e( 'No Memberships Found', 'wpcalibrate-membership-access' ); ?></h3>
			<p><?php esc_html_e( 'You do not have any active or previous memberships associated with your account.', 'wpcalibrate-membership-access' ); ?></p>
		</div>
	<?php else : ?>
		<div class="wpcal-member-table-wrap">
			<table class="wpcal-member-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Membership Plan', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Started On', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Expires / Renews', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Reference', 'wpcalibrate-membership-access' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $memberships as $m ) : ?>
						<?php
						$plan      = $plans_by_id[ $m->get_plan_id() ] ?? null;
						$plan_name = $plan ? $plan->get_name() : __( 'Plan #', 'wpcalibrate-membership-access' ) . $m->get_plan_id();
						$st_badge  = StatusManager::get_badge_class( $m->get_status() );
						$st_label  = StatusManager::get_label( $m->get_status() );
						?>
						<tr>
							<td data-label="<?php esc_attr_e( 'Plan', 'wpcalibrate-membership-access' ); ?>">
								<strong><?php echo esc_html( $plan_name ); ?></strong>
							</td>
							<td data-label="<?php esc_attr_e( 'Status', 'wpcalibrate-membership-access' ); ?>">
								<span class="wpcal-badge <?php echo esc_attr( $st_badge ); ?>">
									<?php echo esc_html( $st_label ); ?>
								</span>
							</td>
							<td data-label="<?php esc_attr_e( 'Started On', 'wpcalibrate-membership-access' ); ?>">
								<?php echo esc_html( gmdate( get_option( 'date_format', 'Y-m-d' ), strtotime( $m->get_start_date() ) ) ); ?>
							</td>
							<td data-label="<?php esc_attr_e( 'Expires', 'wpcalibrate-membership-access' ); ?>">
								<?php if ( $m->is_lifetime() ) : ?>
									<span class="wpcal-badge wpcal-badge--info"><?php esc_html_e( 'Lifetime Access', 'wpcalibrate-membership-access' ); ?></span>
								<?php else : ?>
									<?php echo esc_html( gmdate( get_option( 'date_format', 'Y-m-d' ), strtotime( (string) $m->get_expires_at() ) ) ); ?>
									<?php if ( $m->is_past_expiry() ) : ?>
										<span class="wpcal-desc-danger"><?php esc_html_e( '(Expired)', 'wpcalibrate-membership-access' ); ?></span>
									<?php endif; ?>
								<?php endif; ?>
							</td>
							<td data-label="<?php esc_attr_e( 'Reference', 'wpcalibrate-membership-access' ); ?>">
								<?php
								if ( $m->get_order_id() && function_exists( 'wc_get_order' ) ) {
									echo esc_html__( 'Order #', 'wpcalibrate-membership-access' ) . esc_html( $m->get_order_id() );
								} elseif ( $m->get_subscription_id() ) {
									echo esc_html__( 'Subscription #', 'wpcalibrate-membership-access' ) . esc_html( $m->get_subscription_id() );
								} else {
									echo esc_html( ucfirst( $m->get_source() ) );
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
