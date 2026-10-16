<?php
/**
 * Cron & Action Scheduler Dispatcher.
 *
 * @package WPCalibrate\MembershipAccess\Cron
 */

namespace WPCalibrate\MembershipAccess\Cron;

defined( 'ABSPATH' ) || exit;

class Scheduler {

	public const CRON_HOOK = 'wpcalibrate_membership_access_hourly_maintenance';

	public function __construct( private ExpiryJob $expiry_job ) {}

	public function init(): void {
		add_action( self::CRON_HOOK, [ $this->expiry_job, 'run' ] );
	}

	/**
	 * Schedule the recurring hourly maintenance event.
	 */
	public static function schedule_events(): void {
		if ( function_exists( 'as_schedule_recurring_action' ) ) {
			if ( false === as_next_scheduled_action( self::CRON_HOOK ) ) {
				as_schedule_recurring_action( time() + 300, 3600, self::CRON_HOOK, [], 'wpcalibrate-membership-access' );
			}
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 300, 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule recurring events on deactivation.
	 */
	public static function clear_events(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::CRON_HOOK, [], 'wpcalibrate-membership-access' );
		}

		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}
