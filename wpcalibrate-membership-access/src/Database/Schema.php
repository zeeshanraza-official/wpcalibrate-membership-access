<?php
/**
 * Database Schema definitions for WPCalibrate Membership Access.
 *
 * @package WPCalibrate\MembershipAccess\Database
 */

namespace WPCalibrate\MembershipAccess\Database;

defined( 'ABSPATH' ) || exit;

class Schema {

	public const OPTION_DB_VERSION = 'wpcalibrate_membership_access_db_version';

	/**
	 * Get the table name for user membership assignments.
	 */
	public static function get_memberships_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcalibrate_memberships';
	}

	/**
	 * Get the table name for membership audit trail.
	 */
	public static function get_audit_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcalibrate_membership_audit';
	}

	/**
	 * Get SQL creation statements suitable for dbDelta().
	 *
	 * Note: dbDelta requires two spaces after PRIMARY KEY, specific index formatting,
	 * and lowercase SQL keywords for types.
	 *
	 * @return string[] SQL statements.
	 */
	public static function get_schema_sql(): array {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$memberships_table = self::get_memberships_table_name();
		$audit_table       = self::get_audit_table_name();

		$sql_memberships = "CREATE TABLE {$memberships_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			plan_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(30) NOT NULL DEFAULT 'pending',
			start_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			expires_at datetime DEFAULT NULL,
			source varchar(40) NOT NULL DEFAULT 'admin',
			order_id bigint(20) unsigned DEFAULT NULL,
			subscription_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			cancelled_at datetime DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY plan_id (plan_id),
			KEY status (status),
			KEY expires_at (expires_at),
			KEY order_id (order_id),
			KEY subscription_id (subscription_id),
			KEY user_plan_status (user_id, plan_id, status)
		) {$charset_collate};";

		$sql_audit = "CREATE TABLE {$audit_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			membership_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			event_type varchar(50) NOT NULL DEFAULT '',
			old_status varchar(30) DEFAULT NULL,
			new_status varchar(30) DEFAULT NULL,
			context longtext DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY membership_id (membership_id),
			KEY user_id (user_id),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) {$charset_collate};";

		return [ $sql_memberships, $sql_audit ];
	}
}
