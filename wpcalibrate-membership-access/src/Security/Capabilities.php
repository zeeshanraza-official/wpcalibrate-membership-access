<?php
/**
 * Plugin Capabilities Management.
 *
 * @package WPCalibrate\MembershipAccess\Security
 */

namespace WPCalibrate\MembershipAccess\Security;

defined( 'ABSPATH' ) || exit;

class Capabilities {

	public const CAP_MANAGE_MEMBERSHIPS = 'wpcalibrate_manage_memberships';
	public const CAP_MANAGE_SETTINGS    = 'wpcalibrate_manage_membership_settings';
	public const CAP_EXPORT_MEMBERSHIPS = 'wpcalibrate_export_memberships';

	/**
	 * List of custom capabilities owned by this plugin.
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return [
			self::CAP_MANAGE_MEMBERSHIPS,
			self::CAP_MANAGE_SETTINGS,
			self::CAP_EXPORT_MEMBERSHIPS,
		];
	}

	/**
	 * Assign plugin capabilities to administrator role on activation or init.
	 */
	public static function register_capabilities(): void {
		$admin_role = get_role( 'administrator' );
		if ( ! $admin_role ) {
			return;
		}

		foreach ( self::get_all() as $cap ) {
			if ( ! $admin_role->has_cap( $cap ) ) {
				$admin_role->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove plugin capabilities from all roles (used on uninstall if retention is disabled).
	 */
	public static function remove_capabilities(): void {
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}

		$caps = self::get_all();
		foreach ( $wp_roles->roles as $role_name => $role_info ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $caps as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}
}
