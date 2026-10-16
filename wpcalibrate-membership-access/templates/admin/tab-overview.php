<?php
/**
 * Admin Overview Tab - How to Use, Purpose & Method.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables available:
 *
 * @var array $counts Membership counts summary.
 * @var array $plans All membership plans.
 * @var bool  $is_wc_active Whether WooCommerce is installed and active.
 */

$counts        = isset( $counts ) && is_array( $counts ) ? $counts : [];
$plans         = isset( $plans ) && is_array( $plans ) ? $plans : [];
$is_wc_active  = isset( $is_wc_active ) ? (bool) $is_wc_active : class_exists( 'WooCommerce' );
$is_wcs_active = class_exists( 'WC_Subscriptions' );
?>

<!-- 1. Real-Time Metrics & Statistics Grid -->
<div class="wpcal-overview-grid">
	<!-- Stat Card: Active Memberships -->
	<div class="wpcal-stat-card wpcal-stat-card--primary">
		<div class="wpcal-stat-card__number"><?php echo esc_html( number_format_i18n( $counts['active_count'] ?? 0 ) ); ?></div>
		<div class="wpcal-stat-card__label"><?php esc_html_e( 'Active Memberships', 'wpcalibrate-membership-access' ); ?></div>
		<div class="wpcal-stat-card__footer">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members&filter_status=active' ) ); ?>">
				<?php esc_html_e( 'View active members &rarr;', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>
	</div>

	<!-- Stat Card: Expiring Soon -->
	<div class="wpcal-stat-card wpcal-stat-card--warning">
		<div class="wpcal-stat-card__number"><?php echo esc_html( number_format_i18n( $counts['expiring_soon_count'] ?? 0 ) ); ?></div>
		<div class="wpcal-stat-card__label"><?php esc_html_e( 'Expiring Soon (7 Days)', 'wpcalibrate-membership-access' ); ?></div>
		<div class="wpcal-stat-card__footer">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members&filter_expiry=expiring_soon' ) ); ?>">
				<?php esc_html_e( 'View expiring soon &rarr;', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>
	</div>

	<!-- Stat Card: Expired Memberships -->
	<div class="wpcal-stat-card wpcal-stat-card--danger">
		<div class="wpcal-stat-card__number"><?php echo esc_html( number_format_i18n( $counts['expired_count'] ?? 0 ) ); ?></div>
		<div class="wpcal-stat-card__label"><?php esc_html_e( 'Expired Memberships', 'wpcalibrate-membership-access' ); ?></div>
		<div class="wpcal-stat-card__footer">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members&filter_status=expired' ) ); ?>">
				<?php esc_html_e( 'View expired records &rarr;', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>
	</div>

	<!-- Stat Card: Total Plans -->
	<div class="wpcal-stat-card wpcal-stat-card--info">
		<div class="wpcal-stat-card__number"><?php echo esc_html( number_format_i18n( count( $plans ) ) ); ?></div>
		<div class="wpcal-stat-card__label"><?php esc_html_e( 'Membership Plans', 'wpcalibrate-membership-access' ); ?></div>
		<div class="wpcal-stat-card__footer">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans' ) ); ?>">
				<?php esc_html_e( 'Manage plans &rarr;', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>
	</div>
</div>

<!-- 2. Hero Section: Purpose & Capabilities -->
<div class="wpcal-overview-hero">
	<div class="wpcal-overview-hero__badge">
		<span class="dashicons dashicons-admin-settings" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
		<?php esc_html_e( 'Plugin Purpose & Architecture', 'wpcalibrate-membership-access' ); ?>
	</div>
	<h2 class="wpcal-overview-hero__title"><?php esc_html_e( 'Enterprise Membership Management & Content Access Control', 'wpcalibrate-membership-access' ); ?></h2>
	<p class="wpcal-overview-hero__desc">
		<?php esc_html_e( 'WPCalibrate Membership Access is a modular, high-performance membership solution for WordPress. It provides complete control over content visibility, custom membership tiers, user assignment lifecycles, and optional WooCommerce e-commerce monetization — built with zero external dependencies and native HPOS support.', 'wpcalibrate-membership-access' ); ?>
	</p>

	<div class="wpcal-overview-hero__pills">
		<span class="wpcal-hero-pill">✓ <?php esc_html_e( 'CPT-Backed Plan Architecture', 'wpcalibrate-membership-access' ); ?></span>
		<span class="wpcal-hero-pill">✓ <?php esc_html_e( '3-Tier Deterministic Precedence', 'wpcalibrate-membership-access' ); ?></span>
		<span class="wpcal-hero-pill">✓ <?php esc_html_e( 'Real-Time Expiry Enforcement', 'wpcalibrate-membership-access' ); ?></span>
		<span class="wpcal-hero-pill">✓ <?php esc_html_e( 'WooCommerce HPOS Monetization', 'wpcalibrate-membership-access' ); ?></span>
		<span class="wpcal-hero-pill">✓ <?php esc_html_e( 'Immutable Audit Trail & GDPR', 'wpcalibrate-membership-access' ); ?></span>
	</div>

	<div class="wpcal-overview-hero__actions">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans&action=new' ) ); ?>" class="wpcal-btn-hero-primary">
			+ <?php esc_html_e( 'Create Your First Plan', 'wpcalibrate-membership-access' ); ?>
		</a>
		<a href="#wpcal-how-to-use" class="wpcal-btn-hero-secondary">
			<?php esc_html_e( 'Read How-To-Use Guide &darr;', 'wpcalibrate-membership-access' ); ?>
		</a>
	</div>
</div>

<!-- 3. The Core Method: Architectural Flow -->
<div class="wpcal-section-card">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'The Method: How Membership Access Operates', 'wpcalibrate-membership-access' ); ?></h2>
	</div>
	<p class="description" style="font-size: 14px; margin-bottom: 24px;">
		<?php esc_html_e( 'The plugin is engineered around three deterministic layers designed to eliminate race conditions, unauthorized caching leaks, and stale access states.', 'wpcalibrate-membership-access' ); ?>
	</p>

	<div class="wpcal-method-grid">
		<!-- Method 1: Plan Definition -->
		<div class="wpcal-method-card">
			<span class="wpcal-method-card__step"><?php esc_html_e( 'Layer 1: Definition', 'wpcalibrate-membership-access' ); ?></span>
			<div class="wpcal-method-card__icon">🏷️</div>
			<h3><?php esc_html_e( 'CPT-Backed Plan Modeling', 'wpcalibrate-membership-access' ); ?></h3>
			<p>
				<?php esc_html_e( 'Plans are stored natively in non-public custom post types (wpcal_member_plan). Each plan defines fixed or lifetime duration, user registration eligibility, priority weight, and linked WooCommerce products.', 'wpcalibrate-membership-access' ); ?>
			</p>
		</div>

		<!-- Method 2: Rule Precedence -->
		<div class="wpcal-method-card">
			<span class="wpcal-method-card__step"><?php esc_html_e( 'Layer 2: Evaluation', 'wpcalibrate-membership-access' ); ?></span>
			<div class="wpcal-method-card__icon">⚖️</div>
			<h3><?php esc_html_e( 'Deterministic Precedence', 'wpcalibrate-membership-access' ); ?></h3>
			<p>
				<?php esc_html_e( 'Authorization follows a strict 3-tier hierarchy: Explicit Post Rules override Category/Term Rules, which override Global Defaults. In-memory request caching guarantees sub-millisecond decisions.', 'wpcalibrate-membership-access' ); ?>
			</p>
		</div>

		<!-- Method 3: Enforcement & Expiry -->
		<div class="wpcal-method-card">
			<span class="wpcal-method-card__step"><?php esc_html_e( 'Layer 3: Enforcement', 'wpcalibrate-membership-access' ); ?></span>
			<div class="wpcal-method-card__icon">⚡</div>
			<h3><?php esc_html_e( 'Real-Time Immediate Expiry', 'wpcalibrate-membership-access' ); ?></h3>
			<p>
				<?php esc_html_e( 'If expires_at is past, access is denied immediately on the very next HTTP request — without waiting for scheduled cron tasks. Background jobs periodically sanitize statuses and notify members.', 'wpcalibrate-membership-access' ); ?>
			</p>
		</div>
	</div>
</div>

<!-- 4. Step-by-Step How To Use Guide -->
<div class="wpcal-section-card" id="wpcal-how-to-use">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'How to Use: 5-Step Operational Guide', 'wpcalibrate-membership-access' ); ?></h2>
	</div>
	<p class="description" style="font-size: 14px; margin-bottom: 20px;">
		<?php esc_html_e( 'Follow this complete workflow to configure your membership plans, protect content, and provide members with a self-service dashboard.', 'wpcalibrate-membership-access' ); ?>
	</p>

	<div class="wpcal-guide-list">
		<!-- Step 1 -->
		<div class="wpcal-guide-step-card">
			<div class="wpcal-guide-step-number">1</div>
			<div class="wpcal-guide-step-body">
				<h3><?php esc_html_e( 'Create Membership Plans', 'wpcalibrate-membership-access' ); ?></h3>
				<p>
					<?php esc_html_e( 'Navigate to the Plans tab and click "Add Membership Plan". Configure the name, slug, description, and duration (Lifetime or fixed intervals like 30 Days, 1 Year). If offering a free tier upon WordPress user registration, check "Allow user registration".', 'wpcalibrate-membership-access' ); ?>
				</p>
				<div class="wpcal-guide-step-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
						<?php esc_html_e( 'Go to Plans Tab &rarr;', 'wpcalibrate-membership-access' ); ?>
					</a>
				</div>
			</div>
		</div>

		<!-- Step 2 -->
		<div class="wpcal-guide-step-card">
			<div class="wpcal-guide-step-number">2</div>
			<div class="wpcal-guide-step-body">
				<h3><?php esc_html_e( 'Protect Your Content (Posts, Pages & Categories)', 'wpcalibrate-membership-access' ); ?></h3>
				<p>
					<?php esc_html_e( 'You can restrict entire pages or individual content blocks:', 'wpcalibrate-membership-access' ); ?>
					<br>
					&bull; <strong><?php esc_html_e( 'Whole Page/Post Protection:', 'wpcalibrate-membership-access' ); ?></strong> <?php esc_html_e( 'Open any post in the WordPress editor and find the "WPCalibrate Membership Access" meta box in the sidebar. Select "Members of specific plans" and choose the required plan.', 'wpcalibrate-membership-access' ); ?>
					<br>
					&bull; <strong><?php esc_html_e( 'Category-wide Protection:', 'wpcalibrate-membership-access' ); ?></strong> <?php esc_html_e( 'Go to Posts &rarr; Categories, edit any category, and assign required membership plans to protect all posts in that category.', 'wpcalibrate-membership-access' ); ?>
					<br>
					&bull; <strong><?php esc_html_e( 'Partial Content (Shortcode/Block):', 'wpcalibrate-membership-access' ); ?></strong> <?php esc_html_e( 'Wrap members-only snippets using the shortcode below or insert the "Protected Content" Gutenberg block.', 'wpcalibrate-membership-access' ); ?>
				</p>
				<div class="wpcal-guide-step-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=rules' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
						<?php esc_html_e( 'View Content Rules &rarr;', 'wpcalibrate-membership-access' ); ?>
					</a>
				</div>
			</div>
		</div>

		<!-- Step 3 -->
		<div class="wpcal-guide-step-card">
			<div class="wpcal-guide-step-number">3</div>
			<div class="wpcal-guide-step-body">
				<h3><?php esc_html_e( 'Sell Memberships via WooCommerce (Optional)', 'wpcalibrate-membership-access' ); ?></h3>
				<p>
					<?php esc_html_e( 'If WooCommerce is active, you can monetize your membership tiers directly through your online store:', 'wpcalibrate-membership-access' ); ?>
					<br>
					&bull; <?php esc_html_e( 'Edit your Membership Plan and enter the WooCommerce Product IDs that grant access upon purchase.', 'wpcalibrate-membership-access' ); ?>
					<br>
					&bull; <?php esc_html_e( 'When a customer completes checkout, the membership is granted automatically and idempotently linked to the order ID.', 'wpcalibrate-membership-access' ); ?>
					<br>
					&bull; <?php esc_html_e( 'If an order is refunded or cancelled, the membership status automatically transitions to Cancelled.', 'wpcalibrate-membership-access' ); ?>
				</p>
				<div class="wpcal-guide-step-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=woocommerce' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
						<?php esc_html_e( 'WooCommerce Settings &rarr;', 'wpcalibrate-membership-access' ); ?>
					</a>
				</div>
			</div>
		</div>

		<!-- Step 4 -->
		<div class="wpcal-guide-step-card">
			<div class="wpcal-guide-step-number">4</div>
			<div class="wpcal-guide-step-body">
				<h3><?php esc_html_e( 'Setup the Member Self-Service Dashboard', 'wpcalibrate-membership-access' ); ?></h3>
				<p>
					<?php esc_html_e( 'Create a new page in WordPress (e.g., "My Membership" or "Member Dashboard") and insert the following shortcode into the page content:', 'wpcalibrate-membership-access' ); ?>
					<br>
					<code class="wpcal-code-inline">[wpcalibrate_member_account]</code>
					<br>
					<?php esc_html_e( 'Logged-in members will see their current active plans, validity period, expiration dates, renewal links, and access privileges.', 'wpcalibrate-membership-access' ); ?>
				</p>
			</div>
		</div>

		<!-- Step 5 -->
		<div class="wpcal-guide-step-card">
			<div class="wpcal-guide-step-number">5</div>
			<div class="wpcal-guide-step-body">
				<h3><?php esc_html_e( 'Manage Members, View Audit Logs & Export Data', 'wpcalibrate-membership-access' ); ?></h3>
				<p>
					<?php esc_html_e( 'Administrators have full operational control under the Members tab:', 'wpcalibrate-membership-access' ); ?>
					<br>
					&bull; <strong><?php esc_html_e( 'Manual Assignment & Editing:', 'wpcalibrate-membership-access' ); ?></strong> <?php esc_html_e( 'Assign plans directly to any registered WordPress user with custom expiration dates.', 'wpcalibrate-membership-access' ); ?>
					<br>
					&bull; <strong><?php esc_html_e( 'Status Lifecycle:', 'wpcalibrate-membership-access' ); ?></strong> <?php esc_html_e( 'Update statuses (Active, Suspended, Cancelled, Expired) with enforced state-machine validation.', 'wpcalibrate-membership-access' ); ?>
					<br>
					&bull; <strong><?php esc_html_e( 'Audit History:', 'wpcalibrate-membership-access' ); ?></strong> <?php esc_html_e( 'Click "View History" on any member to view a complete, timestamped audit log of every change, actor ID, and metadata.', 'wpcalibrate-membership-access' ); ?>
					<br>
					&bull; <strong><?php esc_html_e( 'Secure CSV Export:', 'wpcalibrate-membership-access' ); ?></strong> <?php esc_html_e( 'Export member records with streaming output and automatic formula injection neutralization.', 'wpcalibrate-membership-access' ); ?>
				</p>
				<div class="wpcal-guide-step-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
						<?php esc_html_e( 'Manage Members &rarr;', 'wpcalibrate-membership-access' ); ?>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpcal_export_members' ), 'wpcal_export_members_nonce' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
						<?php esc_html_e( 'Export CSV &rarr;', 'wpcalibrate-membership-access' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- 5. Shortcodes & Blocks Reference Card -->
<div class="wpcal-section-card">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'Shortcodes & Block Reference Cheatsheet', 'wpcalibrate-membership-access' ); ?></h2>
	</div>
	<p class="description" style="font-size: 14px;">
		<?php esc_html_e( 'Copy and paste these shortcodes anywhere on your website or use the native Gutenberg block.', 'wpcalibrate-membership-access' ); ?>
	</p>

	<div class="wpcal-cheatsheet-grid">
		<!-- Shortcode 1 -->
		<div class="wpcal-snippet-card">
			<div class="wpcal-snippet-card__title"><?php esc_html_e( '1. Content Restriction Shortcode', 'wpcalibrate-membership-access' ); ?></div>
			<code>[wpcalibrate_members_only plan_id="1,2"]
Your protected content, download links, or videos go here.
[/wpcalibrate_members_only]</code>
		</div>

		<!-- Shortcode 2 -->
		<div class="wpcal-snippet-card">
			<div class="wpcal-snippet-card__title"><?php esc_html_e( '2. Member Dashboard Shortcode', 'wpcalibrate-membership-access' ); ?></div>
			<code>[wpcalibrate_member_account]</code>
		</div>
	</div>
</div>

<!-- 6. Integration Health & System Status -->
<div class="wpcal-dashboard-sections">
	<div class="wpcal-section-card">
		<div class="wpcal-card-header">
			<h2><?php esc_html_e( 'Integration Health & System Status', 'wpcalibrate-membership-access' ); ?></h2>
		</div>
		<table class="wpcal-status-table widefat striped">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'WooCommerce Core', 'wpcalibrate-membership-access' ); ?></strong></td>
					<td>
						<?php if ( $is_wc_active ) : ?>
							<span class="wpcal-status-tag wpcal-status-tag--ok"><?php esc_html_e( 'Active & Connected', 'wpcalibrate-membership-access' ); ?></span>
						<?php else : ?>
							<span class="wpcal-status-tag wpcal-status-tag--optional"><?php esc_html_e( 'Inactive (Plugin running in Standalone Mode)', 'wpcalibrate-membership-access' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php esc_html_e( 'Optional: Used for selling memberships via products and checkout.', 'wpcalibrate-membership-access' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WooCommerce HPOS Compatibility', 'wpcalibrate-membership-access' ); ?></strong></td>
					<td>
						<span class="wpcal-status-tag wpcal-status-tag--ok"><?php esc_html_e( 'Declared & Supported', 'wpcalibrate-membership-access' ); ?></span>
					</td>
					<td><?php esc_html_e( 'High-Performance Order Storage compatible without legacy post-meta assumptions.', 'wpcalibrate-membership-access' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WooCommerce Subscriptions', 'wpcalibrate-membership-access' ); ?></strong></td>
					<td>
						<?php if ( $is_wcs_active ) : ?>
							<span class="wpcal-status-tag wpcal-status-tag--ok"><?php esc_html_e( 'Active & Synchronized', 'wpcalibrate-membership-access' ); ?></span>
						<?php else : ?>
							<span class="wpcal-status-tag wpcal-status-tag--optional"><?php esc_html_e( 'Not Detected (Adapter Idle)', 'wpcalibrate-membership-access' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php esc_html_e( 'Optional: Maps recurring subscriptions to membership renewals and lifecycle events.', 'wpcalibrate-membership-access' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Expiry Cleanup & Maintenance Job', 'wpcalibrate-membership-access' ); ?></strong></td>
					<td>
						<span class="wpcal-status-tag wpcal-status-tag--ok"><?php esc_html_e( 'Scheduled (Hourly)', 'wpcalibrate-membership-access' ); ?></span>
					</td>
					<td><?php esc_html_e( 'Immediate real-time expiry checking is active, reinforced by hourly normalization.', 'wpcalibrate-membership-access' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="wpcal-section-card">
		<div class="wpcal-card-header">
			<h2><?php esc_html_e( 'Quick Operations', 'wpcalibrate-membership-access' ); ?></h2>
		</div>
		<div class="wpcal-quick-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members&action=assign' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
				<span class="dashicons dashicons-plus" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
				<?php esc_html_e( 'Assign Membership', 'wpcalibrate-membership-access' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=rules' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
				<span class="dashicons dashicons-lock" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
				<?php esc_html_e( 'View Access Rules', 'wpcalibrate-membership-access' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpcal_export_members' ), 'wpcal_export_members_nonce' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
				<span class="dashicons dashicons-download" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
				<?php esc_html_e( 'Export Members (CSV)', 'wpcalibrate-membership-access' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=support' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
				<span class="dashicons dashicons-sos" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
				<?php esc_html_e( 'Official Support', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>
	</div>
</div>
