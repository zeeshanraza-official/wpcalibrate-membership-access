<?php
/**
 * Background Expiry & Maintenance Job.
 *
 * Normalizes expired membership statuses, sends automated reminders,
 * and maintains audit integrity using idempotent batch processing and transient locking.
 *
 * @package WPCalibrate\MembershipAccess\Cron
 */

namespace WPCalibrate\MembershipAccess\Cron;

use WPCalibrate\MembershipAccess\Email\EmailManager;
use WPCalibrate\MembershipAccess\Membership\Membership;
use WPCalibrate\MembershipAccess\Membership\MembershipRepository;
use WPCalibrate\MembershipAccess\Membership\MembershipService;

defined( 'ABSPATH' ) || exit;

class ExpiryJob {

	public const LOCK_TRANSIENT = 'wpcal_membership_expiry_lock';
	public const LOCK_SECONDS   = 900; // 15 minutes max lock duration

	public function __construct(
		private MembershipRepository $membership_repo,
		private MembershipService $membership_service,
		private EmailManager $email_manager
	) {}

	/**
	 * Run the scheduled job.
	 */
	public function run(): void {
		// 1. Acquire execution lock.
		if ( get_transient( self::LOCK_TRANSIENT ) ) {
			return;
		}
		set_transient( self::LOCK_TRANSIENT, time(), self::LOCK_SECONDS );

		try {
			// 2. Normalize expired active memberships.
			$this->normalize_expired_memberships();

			// 3. Process upcoming expiry reminders.
			$this->process_expiry_reminders();
		} finally {
			// 4. Release execution lock.
			delete_transient( self::LOCK_TRANSIENT );
		}
	}

	/**
	 * Find active records whose expires_at is past and transition them to expired.
	 */
	public function normalize_expired_memberships(): int {
		$expired_items = $this->membership_repo->find_expired_active_memberships( 100 );
		$processed     = 0;

		foreach ( $expired_items as $membership ) {
			$res = $this->membership_service->update_status(
				$membership->get_id(),
				Membership::STATUS_EXPIRED,
				__( 'Automatic status normalization on expiration.', 'wpcalibrate-membership-access' ),
				0 // Actor: system / cron
			);

			if ( true === $res ) {
				$plan = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_plan_repository()->get( $membership->get_plan_id() );
				if ( $plan ) {
					$this->email_manager->send_expired_email( $membership, $plan );
				}
				$processed++;
			}
		}

		return $processed;
	}

	/**
	 * Find active records within reminder window and send notification.
	 */
	public function process_expiry_reminders(): int {
		$settings = get_option( 'wpcalibrate_membership_access_settings', [] );
		if ( ( $settings['enable_expiry_reminders'] ?? 'yes' ) !== 'yes' ) {
			return 0;
		}

		$days_threshold = max( 1, absint( $settings['expiry_reminder_days'] ?? 7 ) );
		$remind_items   = $this->membership_repo->find_memberships_for_expiry_reminder( $days_threshold, 50 );
		$processed      = 0;

		$wp_tz = wp_timezone();
		$now   = ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );

		foreach ( $remind_items as $membership ) {
			$plan = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_plan_repository()->get( $membership->get_plan_id() );
			if ( ! $plan ) {
				continue;
			}

			// Send email.
			$this->email_manager->send_expiry_reminder_email( $membership, $plan );

			// Mark metadata to prevent duplicate notifications.
			$meta                      = $membership->get_metadata();
			$meta['reminder_sent']    = true;
			$meta['reminder_sent_at'] = $now;

			$updated = new Membership(
				$membership->get_id(),
				$membership->get_user_id(),
				$membership->get_plan_id(),
				$membership->get_status(),
				$membership->get_start_date(),
				$membership->get_expires_at(),
				$membership->get_source(),
				$membership->get_order_id(),
				$membership->get_subscription_id(),
				$membership->get_created_at(),
				$membership->get_updated_at(),
				$membership->get_cancelled_at(),
				$meta
			);

			$this->membership_repo->save( $updated );
			$processed++;
		}

		return $processed;
	}
}
