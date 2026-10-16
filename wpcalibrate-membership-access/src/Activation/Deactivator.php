<?php
/**
 * Plugin Deactivation Handler.
 *
 * @package WPCalibrate\MembershipAccess\Activation
 */

namespace WPCalibrate\MembershipAccess\Activation;

use WPCalibrate\MembershipAccess\Cron\Scheduler;

defined( 'ABSPATH' ) || exit;

class Deactivator {

	public static function deactivate(): void {
		// 1. Clear scheduled cron tasks.
		Scheduler::clear_events();

		// 2. Clear any active runtime locks/transients.
		delete_transient( 'wpcal_membership_expiry_lock' );

		// Data retention rule: Memberships, plans, and options are preserved on deactivation.
	}
}
