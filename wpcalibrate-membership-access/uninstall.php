<?php
/**
 * Plugin Uninstall Handler.
 *
 * Cleans up plugin data ONLY if the administrator has explicitly enabled
 * "Delete plugin data when uninstalled" in settings.
 *
 * @package WPCalibrate\MembershipAccess
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// 1. Check data retention setting.
$settings = get_option( 'wpcalibrate_membership_access_settings', [] );
$delete_data = ( $settings['delete_data_on_uninstall'] ?? 'no' ) === 'yes';

if ( ! $delete_data ) {
	// Preserve all data safely for potential future reactivation.
	return;
}

global $wpdb;

// 2. Drop custom tables.
$table_memberships = $wpdb->prefix . 'wpcalibrate_memberships';
$table_audit       = $wpdb->prefix . 'wpcalibrate_membership_audit';

$wpdb->query( "DROP TABLE IF EXISTS {$table_memberships}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$table_audit}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange

// 3. Delete all membership plan posts & meta.
$plan_ids = get_posts(
	[
		'post_type'      => 'wpcal_member_plan',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	]
);

foreach ( $plan_ids as $p_id ) {
	wp_delete_post( $p_id, true );
}

// 4. Delete plugin options.
$options_to_delete = [
	'wpcalibrate_membership_access_settings',
	'wpcalibrate_membership_access_email_settings',
	'wpcalibrate_membership_access_version',
	'wpcalibrate_membership_access_db_version',
	'wpcalibrate_membership_access_license_key',
	'wpcalibrate_membership_access_license_info',
];

foreach ( $options_to_delete as $opt ) {
	delete_option( $opt );
}

// 5. Clear scheduled events.
wp_clear_scheduled_hook( 'wpcalibrate_membership_access_hourly_maintenance' );

// 6. Remove plugin-specific capabilities.
global $wp_roles;
if ( ! isset( $wp_roles ) ) {
	$wp_roles = new \WP_Roles();
}

$caps = [
	'wpcalibrate_manage_memberships',
	'wpcalibrate_manage_membership_settings',
	'wpcalibrate_export_memberships',
];

foreach ( $wp_roles->roles as $role_name => $role_info ) {
	$role = get_role( $role_name );
	if ( $role ) {
		foreach ( $caps as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}

// 7. Delete transients.
delete_transient( 'wpcal_membership_expiry_lock' );
