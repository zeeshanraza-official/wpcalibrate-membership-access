<?php
/**
 * Admin Screen Header Template.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables available:
 *
 * @var string $tab Current active tab slug.
 * @var array  $tabs Available tabs map.
 * @var string $msg Flash message key.
 * @var string $brand_icon_url WPCalibrate brand icon URL.
 */

$tab            = isset( $tab ) ? (string) $tab : 'overview';
$tabs           = isset( $tabs ) && is_array( $tabs ) ? $tabs : [];
$msg            = isset( $msg ) ? (string) $msg : '';
$brand_icon_url = ! empty( $brand_icon_url ) ? (string) $brand_icon_url : WPCALIBRATE_MEMBERSHIP_ACCESS_URL . 'assets/branding/wpcalibrate-icon.svg';
?>
<div class="wrap wpcal-admin-wrap">
	<!-- Top Brand Gradient Accent Bar -->
	<div class="wpcal-brand-accent-bar" aria-hidden="true"></div>

	<header class="wpcal-admin-header">
		<div class="wpcal-admin-header__brand">
			<div class="wpcal-brand-icon-wrapper" title="WPCalibrate">
				<img src="<?php echo esc_url( $brand_icon_url ); ?>" alt="WPCalibrate" class="wpcal-brand-logo" width="32" height="32" />
			</div>
			<div class="wpcal-admin-header__title-wrap">
				<div class="wpcal-brand-subtitle">
					<span class="wpcal-brand-pill">WPCALIBRATE</span>
					<span class="wpcal-brand-suite-label"><?php esc_html_e( 'Official Suite', 'wpcalibrate-membership-access' ); ?></span>
				</div>
				<h1 class="wpcal-admin-header__title"><?php esc_html_e( 'Membership Access', 'wpcalibrate-membership-access' ); ?></h1>
			</div>
			<span class="wpcal-version-badge">v<?php echo esc_html( WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION ); ?></span>
		</div>
		<div class="wpcal-admin-header__actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=plans&action=new' ) ); ?>" class="wpcal-btn wpcal-btn-primary">
				<span class="dashicons dashicons-plus-alt2" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px; vertical-align: middle;"></span>
				<?php esc_html_e( 'New Plan', 'wpcalibrate-membership-access' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=members&action=assign' ) ); ?>" class="wpcal-btn wpcal-btn-secondary">
				<span class="dashicons dashicons-admin-users" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px; vertical-align: middle;"></span>
				<?php esc_html_e( 'Assign Member', 'wpcalibrate-membership-access' ); ?>
			</a>
		</div>
	</header>

	<?php if ( ! empty( $msg ) ) : ?>
		<div class="notice notice-<?php echo str_starts_with( $msg, 'error' ) ? 'error' : 'success'; ?> is-dismissible wpcal-notice">
			<p>
				<?php
				switch ( $msg ) {
					case 'plan_saved':
						esc_html_e( 'Membership plan saved successfully.', 'wpcalibrate-membership-access' );
						break;
					case 'plan_deleted':
						esc_html_e( 'Membership plan deleted.', 'wpcalibrate-membership-access' );
						break;
					case 'membership_assigned':
						esc_html_e( 'Membership successfully assigned to user.', 'wpcalibrate-membership-access' );
						break;
					case 'membership_updated':
						esc_html_e( 'Membership assignment updated.', 'wpcalibrate-membership-access' );
						break;
					case 'status_updated':
						esc_html_e( 'Membership status updated successfully.', 'wpcalibrate-membership-access' );
						break;
					case 'membership_deleted':
						esc_html_e( 'Membership record deleted.', 'wpcalibrate-membership-access' );
						break;
					case 'settings_saved':
						esc_html_e( 'Settings updated successfully.', 'wpcalibrate-membership-access' );
						break;
					case 'license_saved':
						esc_html_e( 'License key saved.', 'wpcalibrate-membership-access' );
						break;
					case 'error_plan_name':
						esc_html_e( 'Error: Plan name is required.', 'wpcalibrate-membership-access' );
						break;
					case 'error_invalid_user':
						esc_html_e( 'Error: The selected WordPress user is invalid.', 'wpcalibrate-membership-access' );
						break;
					case 'error_invalid_plan':
						esc_html_e( 'Error: The selected membership plan is invalid.', 'wpcalibrate-membership-access' );
						break;
					case 'error_invalid_transition':
						esc_html_e( 'Error: Invalid membership status transition.', 'wpcalibrate-membership-access' );
						break;
					default:
						echo esc_html( ucfirst( str_replace( '_', ' ', $msg ) ) );
						break;
				}
				?>
			</p>
		</div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper wpcal-nav-tabs" aria-label="<?php esc_attr_e( 'Membership Access Settings Tabs', 'wpcalibrate-membership-access' ); ?>">
		<?php foreach ( $tabs as $tab_key => $tab_title ) : ?>
			<?php
			$tab_url = add_query_arg(
				[
					'page' => \WPCalibrate\MembershipAccess\Admin\AdminMenu::SUBMENU_SLUG,
					'tab'  => $tab_key,
				],
				admin_url( 'admin.php' )
			);
			$active_class = ( $tab === $tab_key ) ? 'nav-tab-active wpcal-tab-active' : '';
			?>
			<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo esc_attr( $active_class ); ?>">
				<?php echo esc_html( $tab_title ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="wpcal-tab-content">
