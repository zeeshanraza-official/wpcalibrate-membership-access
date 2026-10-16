<?php
/**
 * Plugin Activation Handler.
 *
 * @package WPCalibrate\MembershipAccess\Activation
 */

namespace WPCalibrate\MembershipAccess\Activation;

use WPCalibrate\MembershipAccess\Cron\Scheduler;
use WPCalibrate\MembershipAccess\Database\Migrator;
use WPCalibrate\MembershipAccess\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

class Activator {

	public static function activate(): void {
		// 1. Verify PHP requirement.
		if ( version_compare( PHP_VERSION, '8.2.0', '<' ) ) {
			deactivate_plugins( WPCALIBRATE_MEMBERSHIP_ACCESS_BASENAME );
			wp_die(
				esc_html__( 'WPCalibrate Membership Access requires PHP 8.2 or higher.', 'wpcalibrate-membership-access' ),
				'Plugin Activation Error',
				[ 'back_link' => true ]
			);
		}

		// 2. Run Database Migrations.
		Migrator::migrate();

		// 3. Register Plugin Capabilities for Administrator.
		Capabilities::register_capabilities();

		// 4. Schedule Cron Tasks.
		Scheduler::schedule_events();

		// 5. Set default settings if not already present.
		self::set_default_options();

		// 6. Record activation version.
		update_option( 'wpcalibrate_membership_access_version', WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION );
	}

	private static function set_default_options(): void {
		$defaults = [
			'access_denied_behavior'     => 'message', // 'message', 'login_redirect', 'registration_redirect', 'custom_page', 'plans_page'
			'custom_denied_message'      => __( 'You must have an active membership to view this content.', 'wpcalibrate-membership-access' ),
			'redirect_url'               => '',
			'search_archive_visibility'  => 'normal_restrict_content', // 'hide_completely', 'show_excerpt', 'normal_restrict_content'
			'allow_multiple_memberships' => 'yes',
			'enable_expiry_reminders'    => 'yes',
			'expiry_reminder_days'       => 7,
			'wc_grant_order_statuses'    => [ 'processing', 'completed' ],
			'wc_revoke_on_refund'        => 'cancel', // 'cancel', 'expire', 'none'
			'wc_revoke_on_cancelled'     => 'cancel',
			'wc_product_restriction_mode'=> 'disabled_purchase', // 'hide', 'disabled_purchase'
			'wc_product_restricted_msg'  => __( 'This item requires an active membership to purchase.', 'wpcalibrate-membership-access' ),
			'allow_registration_plans'   => 'no',
			'registration_default_plan'  => 0,
			'registration_allowed_plans' => [],
			'delete_data_on_uninstall'   => 'no',
			'enable_debug_logging'       => 'no',
		];

		$existing = get_option( 'wpcalibrate_membership_access_settings', [] );
		if ( ! is_array( $existing ) || empty( $existing ) ) {
			update_option( 'wpcalibrate_membership_access_settings', $defaults );
		}
	}
}
