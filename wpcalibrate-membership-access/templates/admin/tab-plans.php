<?php
/**
 * Admin Plans Tab.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

defined( 'ABSPATH' ) || exit;

use WPCalibrate\MembershipAccess\Membership\Plan;

/**
 * Variables available:
 *
 * @var \WPCalibrate\MembershipAccess\Admin\AdminScreens $this
 * @var Plan[]    $plans
 * @var Plan|null $edit_plan
 */

$plans     = isset( $plans ) && is_array( $plans ) ? $plans : [];
$edit_plan = isset( $edit_plan ) ? $edit_plan : null;
$is_editing = ( null !== $edit_plan ) || ( isset( $_GET['action'] ) && 'new' === $_GET['action'] );
?>

<?php if ( $is_editing ) : ?>
	<?php
	$plan_id       = $edit_plan ? $edit_plan->get_id() : 0;
	$name          = $edit_plan ? $edit_plan->get_name() : '';
	$slug          = $edit_plan ? $edit_plan->get_slug() : '';
	$desc          = $edit_plan ? $edit_plan->get_description() : '';
	$status        = $edit_plan ? $edit_plan->get_status() : 'active';
	$dur_type      = $edit_plan ? $edit_plan->get_duration_type() : Plan::DURATION_LIFETIME;
	$dur_qty       = $edit_plan ? $edit_plan->get_duration_quantity() : 1;
	$dur_unit      = $edit_plan ? $edit_plan->get_duration_unit() : Plan::UNIT_MONTHS;
	$product_ids   = $edit_plan ? implode( ', ', $edit_plan->get_product_ids() ) : '';
	$reg_allowed   = $edit_plan ? $edit_plan->is_registration_allowed() : false;
	$default_reg   = $edit_plan ? $edit_plan->is_default_registration() : false;
	$priority      = $edit_plan ? $edit_plan->get_priority() : 10;
	$admin_notes   = $edit_plan ? $edit_plan->get_admin_notes() : '';
	?>
	<div class="wpcal-section-card">
		<div class="wpcal-card-header">
			<h2><?php echo $plan_id ? esc_html__( 'Edit Membership Plan', 'wpcalibrate-membership-access' ) : esc_html__( 'Create New Membership Plan', 'wpcalibrate-membership-access' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
				&larr; <?php esc_html_e( 'Back to Plans List', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans' ) ); ?>">
			<?php wp_nonce_field( 'wpcal_save_plan_nonce', '_wpcal_nonce' ); ?>
			<input type="hidden" name="wpcal_action" value="save_plan" />
			<input type="hidden" name="plan_id" value="<?php echo esc_attr( $plan_id ); ?>" />

			<table class="form-table">
				<tr>
					<th scope="row"><label for="plan_name"><?php esc_html_e( 'Plan Name', 'wpcalibrate-membership-access' ); ?> <span class="required">*</span></label></th>
					<td>
						<input type="text" name="plan_name" id="plan_name" class="regular-text" value="<?php echo esc_attr( $name ); ?>" required />
						<p class="description"><?php esc_html_e( 'e.g. VIP Member, Monthly Pass, Pro Access', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="plan_slug"><?php esc_html_e( 'Slug', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="text" name="plan_slug" id="plan_slug" class="regular-text" value="<?php echo esc_attr( $slug ); ?>" />
						<p class="description"><?php esc_html_e( 'Unique identifier used in shortcodes. Leave blank to auto-generate.', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="plan_description"><?php esc_html_e( 'Description', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<textarea name="plan_description" id="plan_description" class="large-text" rows="3"><?php echo esc_textarea( $desc ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Internal or public summary of what this plan unlocks.', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="plan_status"><?php esc_html_e( 'Plan Status', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<select name="plan_status" id="plan_status">
							<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active (Can be granted or purchased)', 'wpcalibrate-membership-access' ); ?></option>
							<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php esc_html_e( 'Inactive (Disabled)', 'wpcalibrate-membership-access' ); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="duration_type"><?php esc_html_e( 'Access Duration', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<select name="duration_type" id="duration_type">
							<option value="lifetime" <?php selected( $dur_type, Plan::DURATION_LIFETIME ); ?>><?php esc_html_e( 'Unlimited / Lifetime (Never expires)', 'wpcalibrate-membership-access' ); ?></option>
							<option value="fixed" <?php selected( $dur_type, Plan::DURATION_FIXED ); ?>><?php esc_html_e( 'Fixed Duration (Expires after set period)', 'wpcalibrate-membership-access' ); ?></option>
							<option value="order" <?php selected( $dur_type, Plan::DURATION_ORDER ); ?>><?php esc_html_e( 'Tied to WooCommerce Order', 'wpcalibrate-membership-access' ); ?></option>
							<option value="subscription" <?php selected( $dur_type, Plan::DURATION_SUBSCRIPTION ); ?>><?php esc_html_e( 'Tied to WooCommerce Subscription', 'wpcalibrate-membership-access' ); ?></option>
						</select>

						<div id="wpcal_fixed_duration_fields" style="margin-top:10px;<?php echo ( Plan::DURATION_FIXED === $dur_type ) ? '' : 'display:none;'; ?>">
							<input type="number" name="duration_quantity" min="1" step="1" value="<?php echo esc_attr( $dur_qty ); ?>" style="width: 70px;" />
							<select name="duration_unit">
								<option value="days" <?php selected( $dur_unit, Plan::UNIT_DAYS ); ?>><?php esc_html_e( 'Days', 'wpcalibrate-membership-access' ); ?></option>
								<option value="weeks" <?php selected( $dur_unit, Plan::UNIT_WEEKS ); ?>><?php esc_html_e( 'Weeks', 'wpcalibrate-membership-access' ); ?></option>
								<option value="months" <?php selected( $dur_unit, Plan::UNIT_MONTHS ); ?>><?php esc_html_e( 'Months', 'wpcalibrate-membership-access' ); ?></option>
								<option value="years" <?php selected( $dur_unit, Plan::UNIT_YEARS ); ?>><?php esc_html_e( 'Years', 'wpcalibrate-membership-access' ); ?></option>
							</select>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="product_ids"><?php esc_html_e( 'Associated WooCommerce Products', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="text" name="product_ids" id="product_ids" class="regular-text" value="<?php echo esc_attr( $product_ids ); ?>" />
						<p class="description"><?php esc_html_e( 'Comma-separated Product IDs. When purchased, grants this membership automatically.', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'User Registration Options', 'wpcalibrate-membership-access' ); ?></th>
					<td>
						<fieldset>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="is_registration_allowed" value="1" <?php checked( $reg_allowed ); ?> />
								<?php esc_html_e( 'Offer this free plan as selectable during WordPress user registration', 'wpcalibrate-membership-access' ); ?>
							</label>
							<label style="display:block;">
								<input type="checkbox" name="is_default_registration" value="1" <?php checked( $default_reg ); ?> />
								<?php esc_html_e( 'Automatically grant this plan to any newly registered WordPress user', 'wpcalibrate-membership-access' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="priority"><?php esc_html_e( 'Display Priority', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="number" name="priority" id="priority" value="<?php echo esc_attr( $priority ); ?>" style="width:80px;" />
						<p class="description"><?php esc_html_e( 'Lower numbers appear higher in lists (e.g. 10).', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="admin_notes"><?php esc_html_e( 'Admin Notes', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<textarea name="admin_notes" id="admin_notes" class="large-text" rows="2"><?php echo esc_textarea( $admin_notes ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Internal notes for administrators (never shown publicly).', 'wpcalibrate-membership-access' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" class="wpcal-btn wpcal-btn-primary" value="<?php esc_attr_e( 'Save Membership Plan', 'wpcalibrate-membership-access' ); ?>" />
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans' ) ); ?>" class="wpcal-btn wpcal-btn-secondary" style="margin-left:10px;">
					<?php esc_html_e( 'Cancel', 'wpcalibrate-membership-access' ); ?>
				</a>
			</p>
		</form>
	</div>

	<script>
	jQuery(function($){
		$('#duration_type').on('change', function(){
			if ($(this).val() === 'fixed') {
				$('#wpcal_fixed_duration_fields').slideDown(150);
			} else {
				$('#wpcal_fixed_duration_fields').slideUp(150);
			}
		});
	});
	</script>
<?php else : ?>
	<div class="wpcal-section-card">
		<div class="wpcal-card-header">
			<h2><?php esc_html_e( 'Configured Membership Plans', 'wpcalibrate-membership-access' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans&action=new' ) ); ?>" class="wpcal-btn wpcal-btn-primary">
				+ <?php esc_html_e( 'Add New Plan', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>

		<?php if ( empty( $plans ) ) : ?>
			<div class="wpcal-empty-state">
				<div class="wpcal-empty-state__icon">🛡️</div>
				<h3><?php esc_html_e( 'No membership plans created yet', 'wpcalibrate-membership-access' ); ?></h3>
				<p><?php esc_html_e( 'Get started by creating your first free, lifetime, or fixed-duration membership plan.', 'wpcalibrate-membership-access' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans&action=new' ) ); ?>" class="wpcal-btn wpcal-btn-primary wpcal-btn-hero">
					<?php esc_html_e( 'Create Membership Plan', 'wpcalibrate-membership-access' ); ?>
				</a>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped wpcal-table">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Plan Name', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 140px;"><?php esc_html_e( 'Slug', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 160px;"><?php esc_html_e( 'Duration', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 120px;"><?php esc_html_e( 'Products', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e( 'Status', 'wpcalibrate-membership-access' ); ?></th>
						<th scope="col" style="width: 150px; text-align: right;"><?php esc_html_e( 'Actions', 'wpcalibrate-membership-access' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $plans as $plan ) : ?>
						<?php
						$edit_url   = admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans&edit=' . $plan->get_id() );
						$delete_url = wp_nonce_url(
							admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans&wpcal_action=delete_plan&plan_id=' . $plan->get_id() ),
							'wpcal_delete_plan_nonce',
							'_wpcal_nonce'
						);
						?>
						<tr>
							<td class="column-primary">
								<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $plan->get_name() ); ?></a></strong>
								<?php if ( $plan->is_default_registration() ) : ?>
									<span class="wpcal-badge wpcal-badge--info"><?php esc_html_e( 'Auto-Assigned on Register', 'wpcalibrate-membership-access' ); ?></span>
								<?php endif; ?>
								<?php if ( $plan->is_registration_allowed() ) : ?>
									<span class="wpcal-badge wpcal-badge--default"><?php esc_html_e( 'Registration Selectable', 'wpcalibrate-membership-access' ); ?></span>
								<?php endif; ?>
							</td>
							<td><code><?php echo esc_html( $plan->get_slug() ); ?></code></td>
							<td>
								<?php
								if ( $plan->is_lifetime() ) {
									esc_html_e( 'Lifetime / Unlimited', 'wpcalibrate-membership-access' );
								} elseif ( Plan::DURATION_FIXED === $plan->get_duration_type() ) {
									echo esc_html( $plan->get_duration_quantity() . ' ' . ucfirst( $plan->get_duration_unit() ) );
								} else {
									echo esc_html( ucfirst( $plan->get_duration_type() ) );
								}
								?>
							</td>
							<td>
								<?php
								$p_ids = $plan->get_product_ids();
								echo empty( $p_ids ) ? '&mdash;' : esc_html( implode( ', ', $p_ids ) );
								?>
							</td>
							<td>
								<span class="wpcal-badge <?php echo $plan->is_active() ? 'wpcal-badge--active' : 'wpcal-badge--suspended'; ?>">
									<?php echo esc_html( ucfirst( $plan->get_status() ) ); ?>
								</span>
							</td>
							<td style="text-align: right;">
								<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'wpcalibrate-membership-access' ); ?>
								</a>
								<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small wpcal-btn-danger" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this membership plan?', 'wpcalibrate-membership-access' ) ); ?>');">
									<?php esc_html_e( 'Delete', 'wpcalibrate-membership-access' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
<?php endif; ?>
