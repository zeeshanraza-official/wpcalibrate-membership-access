<?php
/**
 * Database Migrator for WPCalibrate Membership Access.
 *
 * @package WPCalibrate\MembershipAccess\Database
 */

namespace WPCalibrate\MembershipAccess\Database;

defined( 'ABSPATH' ) || exit;

class Migrator {

	/**
	 * Run migrations idempotently.
	 */
	public static function migrate(): void {
		$installed_version = get_option( Schema::OPTION_DB_VERSION, '' );

		if ( version_compare( (string) $installed_version, WPCALIBRATE_MEMBERSHIP_ACCESS_DB_VERSION, '<' ) ) {
			self::run_migrations( (string) $installed_version );
			update_option( Schema::OPTION_DB_VERSION, WPCALIBRATE_MEMBERSHIP_ACCESS_DB_VERSION );
		}
	}

	/**
	 * Execute schema creation and version-specific update steps.
	 *
	 * @param string $from_version Previous installed DB version.
	 */
	public static function run_migrations( string $from_version = '' ): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql_statements = Schema::get_schema_sql();
		foreach ( $sql_statements as $sql ) {
			dbDelta( $sql );
		}

		/**
		 * Action triggered after database migration completes.
		 *
		 * @param string $from_version Previous version.
		 * @param string $to_version Target version.
		 */
		do_action(
			'wpcalibrate_membership_access_db_migrated',
			$from_version,
			WPCALIBRATE_MEMBERSHIP_ACCESS_DB_VERSION
		);
	}
}
