<?php
/**
 * Admin Access Rules Tab.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

defined( 'ABSPATH' ) || exit;

use WPCalibrate\MembershipAccess\Membership\Plan;

/**
 * Variables available:
 *
 * @var Plan[] $plans Active plans.
 */

$plans = isset( $plans ) && is_array( $plans ) ? $plans : [];
?>
<div class="wpcal-section-card">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'Content Restriction Engine & Precedence Rules', 'wpcalibrate-membership-access' ); ?></h2>
	</div>
	<p class="description" style="font-size: 14px; margin-bottom: 20px;">
		<?php esc_html_e( 'WPCalibrate Membership Access enforces a deterministic 3-tier precedence hierarchy to decide whether a visitor or user can view any piece of content.', 'wpcalibrate-membership-access' ); ?>
	</p>

	<div class="wpcal-precedence-flow">
		<div class="wpcal-flow-step">
			<span class="wpcal-flow-step__badge">1</span>
			<div class="wpcal-flow-step__content">
				<h3><?php esc_html_e( '1. Explicit Post/Page/CPT Restriction (Highest Priority)', 'wpcalibrate-membership-access' ); ?></h3>
				<p><?php esc_html_e( 'Set directly in the WordPress block or classic editor via the "WPCalibrate Membership Access" meta box. Directly dictates access regardless of categories.', 'wpcalibrate-membership-access' ); ?></p>
			</div>
		</div>

		<div class="wpcal-flow-step">
			<span class="wpcal-flow-step__badge">2</span>
			<div class="wpcal-flow-step__content">
				<h3><?php esc_html_e( '2. Taxonomy Category / Term Restriction (Medium Priority)', 'wpcalibrate-membership-access' ); ?></h3>
				<p><?php esc_html_e( 'If the individual post has no explicit restriction ("Default"), any membership rule applied to its Categories or Tags is evaluated.', 'wpcalibrate-membership-access' ); ?></p>
			</div>
		</div>

		<div class="wpcal-flow-step">
			<span class="wpcal-flow-step__badge">3</span>
			<div class="wpcal-flow-step__content">
				<h3><?php esc_html_e( '3. Global Default Settings (Fallback)', 'wpcalibrate-membership-access' ); ?></h3>
				<p><?php esc_html_e( 'If neither the post nor its categories require membership, global access settings govern unauthorized access handling and redirects.', 'wpcalibrate-membership-access' ); ?></p>
			</div>
		</div>
	</div>
</div>

<div class="wpcal-section-card">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'Gutenberg Block & Shortcode Protection Methods', 'wpcalibrate-membership-access' ); ?></h2>
	</div>
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Method', 'wpcalibrate-membership-access' ); ?></th>
				<th><?php esc_html_e( 'Syntax / Usage', 'wpcalibrate-membership-access' ); ?></th>
				<th><?php esc_html_e( 'Behavior', 'wpcalibrate-membership-access' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'Gutenberg Block', 'wpcalibrate-membership-access' ); ?></strong></td>
				<td><code>wpcalibrate/protected-content</code></td>
				<td><?php esc_html_e( 'Insert via Block inserter. Select required plans in sidebar. Protected markup is stripped completely on the server for unauthorized visitors.', 'wpcalibrate-membership-access' ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Shortcode (Any Member)', 'wpcalibrate-membership-access' ); ?></strong></td>
				<td><code>[wpcalibrate_members_only]Your secret content[/wpcalibrate_members_only]</code></td>
				<td><?php esc_html_e( 'Displays nested content only to visitors holding at least one active membership.', 'wpcalibrate-membership-access' ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Shortcode (Specific Plans)', 'wpcalibrate-membership-access' ); ?></strong></td>
				<td><code>[wpcalibrate_members_only plan_ids="1,2" fallback="Upgrade to VIP to view."]...[/wpcalibrate_members_only]</code></td>
				<td><?php esc_html_e( 'Restricts content to members belonging to specific plan IDs. Supports custom fallback message.', 'wpcalibrate-membership-access' ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Member Account Shortcode', 'wpcalibrate-membership-access' ); ?></strong></td>
				<td><code>[wpcalibrate_member_account]</code></td>
				<td><?php esc_html_e( 'Renders a front-end member dashboard listing current memberships, start/expiry dates, and status.', 'wpcalibrate-membership-access' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>
