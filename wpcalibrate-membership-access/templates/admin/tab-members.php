<?php
/**
 * Admin Members Management Tab.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

defined( 'ABSPATH' ) || exit;

use WPCalibrate\MembershipAccess\Membership\Membership;
use WPCalibrate\MembershipAccess\Membership\Plan;
use WPCalibrate\MembershipAccess\Membership\StatusManager;

/**
 * Variables available:
 *
 * @var \WPCalibrate\MembershipAccess\Admin\AdminScreens $this
 * @var Membership[] $memberships
 * @var int          $total_items
 * @var int          $total_pages
 * @var int          $page_num
 * @var array        $filters
 * @var Plan[]       $plans
 * @var array        $audit_records
 */

$memberships   = isset( $memberships ) && is_array( $memberships ) ? $memberships : [];
$total_items   = isset( $total_items ) ? (int) $total_items : 0;
$total_pages   = isset( $total_pages ) ? (int) $total_pages : 1;
$page_num      = isset( $page_num ) ? (int) $page_num : 1;
$filters       = isset( $filters ) && is_array( $filters ) ? $filters : [];
$plans         = isset( $plans ) && is_array( $plans ) ? $plans : [];
$audit_records = isset( $audit_records ) && is_array( $audit_records ) ? $audit_records : [];

$plans_by_id = [];
foreach ( $plans as $p ) {
	$plans_by_id[ $p->get_id() ] = $p;
}

$is_assigning = isset( $_GET['action'] ) && 'assign' === $_GET['action'];
$is_editing   = isset( $_GET['action'] ) && 'edit_member' === $_GET['action'] && ! empty( $_GET['membership_id'] );
$editing_m    = null;
if ( $is_editing ) {
	$editing_m = $this->membership_repo->get( absint( $_GET['membership_id'] ) );
}
?>

<?php if ( ! empty( $audit_records ) ) : ?>
	<div class="wpcal-section-card" style="border-left: 4px solid #2563eb;">
		<div class="wpcal-card-header">
			<h2><?php esc_html_e( 'Membership Audit Trail / Change History', 'wpcalibrate-membership-access' ); ?> (ID: #<?php echo esc_html( absint( $_GET['view_audit'] ) ); ?>)</h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
				&times; <?php esc_html_e( 'Close History', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>
		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Timestamp', 'wpcalibrate-membership-access' ); ?></th>
					<th><?php esc_html_e( 'Event', 'wpcalibrate-membership-access' ); ?></th>
					<th><?php esc_html_e( 'Actor', 'wpcalibrate-membership-access' ); ?></th>
					<th><?php esc_html_e( 'Status Transition', 'wpcalibrate-membership-access' ); ?></th>
					<th><?php esc_html_e( 'Details', 'wpcalibrate-membership-access' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $audit_records as $rec ) : ?>
					<?php
					$actor = ( $rec['actor_id'] > 0 ) ? get_userdata( $rec['actor_id'] ) : null;
					$actor_name = $actor ? $actor->display_name : ( 0 === (int) $rec['actor_id'] ? __( 'System / Cron', 'wpcalibrate-membership-access' ) : '#' . $rec['actor_id'] );
					?>
					<tr>
						<td><?php echo esc_html( $rec['created_at'] ); ?></td>
						<td><strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $rec['event_type'] ) ) ); ?></strong></td>
						<td><?php echo esc_html( $actor_name ); ?></td>
						<td>
							<?php if ( ! empty( $rec['old_status'] ) || ! empty( $rec['new_status'] ) ) : ?>
								<code><?php echo esc_html( $rec['old_status'] ?: 'none' ); ?></code> &rarr; <code><?php echo esc_html( $rec['new_status'] ?: 'none' ); ?></code>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $rec['context'] ) ) : ?>
								<code><?php echo esc_html( wp_json_encode( $rec['context'] ) ); ?></code>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>

<?php if ( $is_assigning ) : ?>
	<div class="wpcal-section-card">
		<div class="wpcal-card-header">
			<h2><?php esc_html_e( 'Assign Membership to User', 'wpcalibrate-membership-access' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
				&larr; <?php esc_html_e( 'Back to Members', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members' ) ); ?>">
			<?php wp_nonce_field( 'wpcal_assign_membership_nonce', '_wpcal_nonce' ); ?>
			<input type="hidden" name="wpcal_action" value="assign_membership" />

			<table class="form-table">
				<tr>
					<th scope="row"><label for="user_id"><?php esc_html_e( 'User ID or Username', 'wpcalibrate-membership-access' ); ?> <span class="required">*</span></label></th>
					<td>
						<input type="number" name="user_id" id="user_id" class="regular-text" required placeholder="User ID" />
						<p class="description"><?php esc_html_e( 'Enter the numeric WordPress User ID.', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="plan_id"><?php esc_html_e( 'Membership Plan', 'wpcalibrate-membership-access' ); ?> <span class="required">*</span></label></th>
					<td>
						<select name="plan_id" id="plan_id" required>
							<option value=""><?php esc_html_e( '-- Select a Membership Plan --', 'wpcalibrate-membership-access' ); ?></option>
							<?php foreach ( $plans as $p ) : ?>
								<option value="<?php echo esc_attr( $p->get_id() ); ?>"><?php echo esc_html( $p->get_name() ); ?> (<?php echo esc_html( ucfirst( $p->get_duration_type() ) ); ?>)</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="start_date"><?php esc_html_e( 'Start Date', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="text" name="start_date" id="start_date" value="<?php echo esc_attr( current_time( 'Y-m-d H:i:s' ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Format: YYYY-MM-DD HH:MM:SS. Defaults to current date/time.', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Expiration', 'wpcalibrate-membership-access' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:6px;">
							<input type="checkbox" name="is_lifetime" id="assign_is_lifetime" value="1" />
							<?php esc_html_e( 'Lifetime Membership (Never expires)', 'wpcalibrate-membership-access' ); ?>
						</label>

						<div id="assign_expires_wrap">
							<input type="text" name="expires_at" id="expires_at" class="regular-text" placeholder="YYYY-MM-DD HH:MM:SS" />
							<p class="description"><?php esc_html_e( 'Leave blank to compute automatically from the selected plan duration.', 'wpcalibrate-membership-access' ); ?></p>
						</div>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" class="wpcal-btn wpcal-btn-primary" value="<?php esc_attr_e( 'Assign Membership', 'wpcalibrate-membership-access' ); ?>" />
			</p>
		</form>
	</div>
	<script>
	jQuery(function($){
		$('#assign_is_lifetime').on('change', function(){
			if ($(this).is(':checked')) {
				$('#assign_expires_wrap').slideUp(150);
			} else {
				$('#assign_expires_wrap').slideDown(150);
			}
		});
	});
	</script>
<?php elseif ( $is_editing && $editing_m ) : ?>
	<div class="wpcal-section-card">
		<div class="wpcal-card-header">
			<h2><?php esc_html_e( 'Edit Membership Assignment', 'wpcalibrate-membership-access' ); ?> #<?php echo esc_html( $editing_m->get_id() ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
				&larr; <?php esc_html_e( 'Back to Members', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>

		<?php
		$user = get_userdata( $editing_m->get_user_id() );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members' ) ); ?>">
			<?php wp_nonce_field( 'wpcal_update_membership_nonce', '_wpcal_nonce' ); ?>
			<input type="hidden" name="wpcal_action" value="update_membership" />
			<input type="hidden" name="membership_id" value="<?php echo esc_attr( $editing_m->get_id() ); ?>" />

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Member User', 'wpcalibrate-membership-access' ); ?></th>
					<td>
						<strong><?php echo esc_html( $user ? $user->display_name . ' (' . $user->user_email . ')' : '#' . $editing_m->get_user_id() ); ?></strong>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="edit_plan_id"><?php esc_html_e( 'Membership Plan', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<select name="plan_id" id="edit_plan_id" required>
							<?php foreach ( $plans as $p ) : ?>
								<option value="<?php echo esc_attr( $p->get_id() ); ?>" <?php selected( $editing_m->get_plan_id(), $p->get_id() ); ?>>
									<?php echo esc_html( $p->get_name() ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="edit_status"><?php esc_html_e( 'Status', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<select name="status" id="edit_status">
							<?php foreach ( StatusManager::get_status_labels() as $st_key => $st_label ) : ?>
								<option value="<?php echo esc_attr( $st_key ); ?>" <?php selected( $editing_m->get_status(), $st_key ); ?>>
									<?php echo esc_html( $st_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="edit_start_date"><?php esc_html_e( 'Start Date', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="text" name="start_date" id="edit_start_date" class="regular-text" value="<?php echo esc_attr( $editing_m->get_start_date() ); ?>" />
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Expiration', 'wpcalibrate-membership-access' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:6px;">
							<input type="checkbox" name="is_lifetime" id="edit_is_lifetime" value="1" <?php checked( $editing_m->is_lifetime() ); ?> />
							<?php esc_html_e( 'Lifetime Membership (Never expires)', 'wpcalibrate-membership-access' ); ?>
						</label>

						<div id="edit_expires_wrap" style="<?php echo $editing_m->is_lifetime() ? 'display:none;' : ''; ?>">
							<input type="text" name="expires_at" id="edit_expires_at" class="regular-text" value="<?php echo esc_attr( (string) $editing_m->get_expires_at() ); ?>" />
							<p class="description"><?php esc_html_e( 'Format: YYYY-MM-DD HH:MM:SS', 'wpcalibrate-membership-access' ); ?></p>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="edit_reason"><?php esc_html_e( 'Reason for Change', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="text" name="reason" id="edit_reason" class="regular-text" placeholder="e.g. Plan upgrade, courtesy extension" />
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" class="wpcal-btn wpcal-btn-primary" value="<?php esc_attr_e( 'Save Assignment Changes', 'wpcalibrate-membership-access' ); ?>" />
			</p>
		</form>
	</div>
	<script>
	jQuery(function($){
		$('#edit_is_lifetime').on('change', function(){
			if ($(this).is(':checked')) {
				$('#edit_expires_wrap').slideUp(150);
			} else {
				$('#edit_expires_wrap').slideDown(150);
			}
		});
	});
	</script>
<?php else : ?>
	<div class="wpcal-section-card">
		<div class="wpcal-card-header">
			<h2><?php esc_html_e( 'Member Assignments', 'wpcalibrate-membership-access' ); ?> (<?php echo esc_html( number_format_i18n( $total_items ) ); ?>)</h2>
			<div>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpcal_export_members' ), 'wpcal_export_members_nonce' ) ); ?>" class="wpcal-btn wpcal-btn-secondary" style="margin-right:8px;">
					<?php esc_html_e( 'Export CSV', 'wpcalibrate-membership-access' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members&action=assign' ) ); ?>" class="wpcal-btn wpcal-btn-primary">
					+ <?php esc_html_e( 'Assign Membership', 'wpcalibrate-membership-access' ); ?>
				</a>
			</div>
		</div>

		<!-- Filters & Search Form -->
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="wpcal-filter-bar">
			<input type="hidden" name="page" value="wpcalibrate-membership-access" />
			<input type="hidden" name="tab" value="members" />

			<input type="search" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search user / email...', 'wpcalibrate-membership-access' ); ?>" />

			<select name="filter_plan">
				<option value=""><?php esc_html_e( 'All Plans', 'wpcalibrate-membership-access' ); ?></option>
				<?php foreach ( $plans as $p ) : ?>
					<option value="<?php echo esc_attr( $p->get_id() ); ?>" <?php selected( $filters['plan_id'], $p->get_id() ); ?>>
						<?php echo esc_html( $p->get_name() ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="filter_status">
				<option value=""><?php esc_html_e( 'All Statuses', 'wpcalibrate-membership-access' ); ?></option>
				<?php foreach ( StatusManager::get_status_labels() as $s_k => $s_l ) : ?>
					<option value="<?php echo esc_attr( $s_k ); ?>" <?php selected( $filters['status'], $s_k ); ?>>
						<?php echo esc_html( $s_l ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="filter_expiry">
				<option value=""><?php esc_html_e( 'All Expiry States', 'wpcalibrate-membership-access' ); ?></option>
				<option value="lifetime" <?php selected( $filters['expiry_state'], 'lifetime' ); ?>><?php esc_html_e( 'Lifetime Only', 'wpcalibrate-membership-access' ); ?></option>
				<option value="active_unexpired" <?php selected( $filters['expiry_state'], 'active_unexpired' ); ?>><?php esc_html_e( 'Active & Valid', 'wpcalibrate-membership-access' ); ?></option>
				<option value="expiring_soon" <?php selected( $filters['expiry_state'], 'expiring_soon' ); ?>><?php esc_html_e( 'Expiring Soon (7 Days)', 'wpcalibrate-membership-access' ); ?></option>
				<option value="expired" <?php selected( $filters['expiry_state'], 'expired' ); ?>><?php esc_html_e( 'Expired Only', 'wpcalibrate-membership-access' ); ?></option>
			</select>

			<select name="filter_source">
				<option value=""><?php esc_html_e( 'All Sources', 'wpcalibrate-membership-access' ); ?></option>
				<option value="admin" <?php selected( $filters['source'], 'admin' ); ?>><?php esc_html_e( 'Admin Assignment', 'wpcalibrate-membership-access' ); ?></option>
				<option value="order" <?php selected( $filters['source'], 'order' ); ?>><?php esc_html_e( 'WooCommerce Order', 'wpcalibrate-membership-access' ); ?></option>
				<option value="subscription" <?php selected( $filters['source'], 'subscription' ); ?>><?php esc_html_e( 'Woo Subscriptions', 'wpcalibrate-membership-access' ); ?></option>
				<option value="registration" <?php selected( $filters['source'], 'registration' ); ?>><?php esc_html_e( 'Registration', 'wpcalibrate-membership-access' ); ?></option>
			</select>

			<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wpcalibrate-membership-access' ); ?>" />
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members' ) ); ?>" class="button">
				<?php esc_html_e( 'Reset', 'wpcalibrate-membership-access' ); ?>
			</a>
		</form>

		<?php if ( empty( $memberships ) ) : ?>
			<div class="wpcal-empty-state">
				<div class="wpcal-empty-state__icon">👥</div>
				<h3><?php esc_html_e( 'No memberships found', 'wpcalibrate-membership-access' ); ?></h3>
				<p><?php esc_html_e( 'No member assignments matched your search or filters.', 'wpcalibrate-membership-access' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped wpcal-table">
				<thead>
					<tr>
						<th scope="col" style="width: 220px;"><?php esc_html_e( 'User', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 160px;"><?php esc_html_e( 'Plan', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 110px;"><?php esc_html_e( 'Status', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 140px;"><?php esc_html_e( 'Source', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 150px;"><?php esc_html_e( 'Start Date', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 160px;"><?php esc_html_e( 'Expires At', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 180px; text-align: right;"><?php esc_html_e( 'Actions', 'wpcalibrate-membership-access' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $memberships as $m ) : ?>
						<?php
						$user      = get_userdata( $m->get_user_id() );
						$plan      = $plans_by_id[ $m->get_plan_id() ] ?? null;
						$plan_name = $plan ? $plan->get_name() : '#' . $m->get_plan_id();
						$st_badge  = StatusManager::get_badge_class( $m->get_status() );
						$st_label  = StatusManager::get_label( $m->get_status() );

						$history_url = admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members&view_audit=' . $m->get_id() );
						$edit_url    = admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members&action=edit_member&membership_id=' . $m->get_id() );

						$delete_url = wp_nonce_url(
							admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members&wpcal_action=delete_membership&membership_id=' . $m->get_id() ),
							'wpcal_delete_membership_nonce',
							'_wpcal_nonce'
						);
						?>
						<tr>
							<td>
								<?php if ( $user ) : ?>
									<strong><a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php echo esc_html( $user->display_name ); ?></a></strong><br>
									<span class="description"><?php echo esc_html( $user->user_email ); ?></span>
								<?php else : ?>
									<em><?php esc_html_e( 'User Deleted', 'wpcalibrate-membership-access' ); ?> (#<?php echo esc_html( $m->get_user_id() ); ?>)</em>
								<?php endif; ?>
							</td>
							<td>
								<strong><?php echo esc_html( $plan_name ); ?></strong>
							</td>
							<td>
								<span class="wpcal-badge <?php echo esc_attr( $st_badge ); ?>">
									<?php echo esc_html( $st_label ); ?>
								</span>
							</td>
							<td>
								<?php
								$src = $m->get_source();
								if ( 'order' === $src && $m->get_order_id() ) {
									echo esc_html__( 'Order #', 'wpcalibrate-membership-access' ) . esc_html( $m->get_order_id() );
								} elseif ( 'subscription' === $src && $m->get_subscription_id() ) {
									echo esc_html__( 'Sub #', 'wpcalibrate-membership-access' ) . esc_html( $m->get_subscription_id() );
								} else {
									echo esc_html( ucfirst( $src ) );
								}
								?>
							</td>
							<td><?php echo esc_html( $m->get_start_date() ); ?></td>
							<td>
								<?php if ( $m->is_lifetime() ) : ?>
									<span class="wpcal-badge wpcal-badge--info"><?php esc_html_e( 'Lifetime', 'wpcalibrate-membership-access' ); ?></span>
								<?php else : ?>
									<?php echo esc_html( (string) $m->get_expires_at() ); ?>
									<?php if ( $m->is_past_expiry() ) : ?>
										<br><span class="wpcal-desc-danger"><?php esc_html_e( '(Past Due)', 'wpcalibrate-membership-access' ); ?></span>
									<?php endif; ?>
								<?php endif; ?>
							</td>
							<td style="text-align: right;">
								<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small" title="<?php esc_attr_e( 'Edit Dates & Plan', 'wpcalibrate-membership-access' ); ?>">
									<?php esc_html_e( 'Edit', 'wpcalibrate-membership-access' ); ?>
								</a>
								<a href="<?php echo esc_url( $history_url ); ?>" class="button button-small" title="<?php esc_attr_e( 'View Change History', 'wpcalibrate-membership-access' ); ?>">
									<?php esc_html_e( 'History', 'wpcalibrate-membership-access' ); ?>
								</a>
								<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small wpcal-btn-danger" onclick="return confirm('<?php echo esc_js( __( 'Remove this membership assignment?', 'wpcalibrate-membership-access' ) ); ?>');">
									&times;
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<span class="displaying-num"><?php echo esc_html( sprintf( __( '%d items', 'wpcalibrate-membership-access' ), $total_items ) ); ?></span>
						<?php
						echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							[
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $total_pages,
								'current'   => $page_num,
							]
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
<?php endif; ?>
