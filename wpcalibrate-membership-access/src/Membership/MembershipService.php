<?php
/**
 * Membership Management Service.
 *
 * Coordinates business logic, state transitions, validation, audit logs,
 * and extensibility hooks.
 *
 * @package WPCalibrate\MembershipAccess\Membership
 */

namespace WPCalibrate\MembershipAccess\Membership;

use WPCalibrate\MembershipAccess\Audit\AuditLogger;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class MembershipService {

	public function __construct(
		private MembershipRepository $membership_repo,
		private PlanRepository $plan_repo,
		private StatusManager $status_manager,
		private AuditLogger $audit_logger
	) {}

	/**
	 * Create and activate a membership for a user.
	 *
	 * @param int    $user_id
	 * @param int    $plan_id
	 * @param string $source (admin, registration, order, subscription, api)
	 * @param array  $args   Optional overrides: start_date, expires_at, order_id, subscription_id, metadata, status
	 * @return int|\WP_Error Created Membership ID or WP_Error on failure.
	 */
	public function create_membership( int $user_id, int $plan_id, string $source, array $args = [] ): int|\WP_Error {
		// 1. Validate user.
		if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
			return new \WP_Error(
				'invalid_user',
				__( 'The specified WordPress user does not exist.', 'wpcalibrate-membership-access' )
			);
		}

		// 2. Validate plan.
		$plan = $this->plan_repo->get( $plan_id );
		if ( ! $plan ) {
			return new \WP_Error(
				'invalid_plan',
				__( 'The specified membership plan does not exist.', 'wpcalibrate-membership-access' )
			);
		}

		// 3. Check multiple memberships setting if applicable.
		$settings = get_option( 'wpcalibrate_membership_access_settings', [] );
		$allow_multiple = ( $settings['allow_multiple_memberships'] ?? 'yes' ) === 'yes';

		if ( ! $allow_multiple ) {
			$active_memberships = $this->membership_repo->get_for_user( $user_id, true );
			if ( ! empty( $active_memberships ) ) {
				// If user already has an active assignment for this plan, renew or return existing ID.
				foreach ( $active_memberships as $existing ) {
					if ( $existing->get_plan_id() === $plan_id ) {
						// Extend or keep existing.
						return $existing->get_id();
					}
				}
			}
		}

		// 4. Calculate dates.
		$wp_tz      = wp_timezone();
		$start_date = ! empty( $args['start_date'] )
			? sanitize_text_field( $args['start_date'] )
			: ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );

		if ( array_key_exists( 'expires_at', $args ) ) {
			$expires_at = $args['expires_at'];
		} else {
			$expires_at = $plan->calculate_expiry( $start_date );
		}

		$status = ! empty( $args['status'] )
			? sanitize_key( $args['status'] )
			: Membership::STATUS_ACTIVE;

		$order_id        = ! empty( $args['order_id'] ) ? absint( $args['order_id'] ) : null;
		$subscription_id = ! empty( $args['subscription_id'] ) ? absint( $args['subscription_id'] ) : null;
		$metadata        = is_array( $args['metadata'] ?? null ) ? $args['metadata'] : [];

		$membership = new Membership(
			0,
			$user_id,
			$plan_id,
			$status,
			$start_date,
			$expires_at,
			sanitize_key( $source ),
			$order_id,
			$subscription_id,
			'',
			'',
			null,
			$metadata
		);

		$membership_id = $this->membership_repo->save( $membership );
		if ( ! $membership_id ) {
			return new \WP_Error(
				'db_insert_failed',
				__( 'Failed to record membership in the database.', 'wpcalibrate-membership-access' )
			);
		}

		// 5. Log audit trail.
		$this->audit_logger->log(
			$membership_id,
			$user_id,
			AuditLogger::EVENT_CREATED,
			null,
			$status,
			[
				'plan_id'         => $plan_id,
				'plan_name'       => $plan->get_name(),
				'source'          => $source,
				'order_id'        => $order_id,
				'subscription_id' => $subscription_id,
				'expires_at'      => $expires_at,
			]
		);

		/**
		 * Action triggered when a membership is created.
		 *
		 * @param int        $membership_id
		 * @param Membership $membership
		 * @param Plan       $plan
		 */
		do_action( 'wpcalibrate_membership_access_membership_created', $membership_id, $membership, $plan );

		if ( Membership::STATUS_ACTIVE === $status ) {
			do_action( 'wpcalibrate_membership_access_membership_activated', $membership_id, $membership, $plan );
		}

		return $membership_id;
	}

	/**
	 * Transition membership status with validation, history logging, and actions.
	 *
	 * @param int    $membership_id
	 * @param string $new_status
	 * @param string $reason
	 * @param int    $actor_id
	 * @return bool|\WP_Error
	 */
	public function update_status( int $membership_id, string $new_status, string $reason = '', int $actor_id = 0 ): bool|\WP_Error {
		$membership = $this->membership_repo->get( $membership_id );
		if ( ! $membership ) {
			return new \WP_Error( 'not_found', __( 'Membership assignment not found.', 'wpcalibrate-membership-access' ) );
		}

		$old_status = $membership->get_status();
		$new_status = sanitize_key( $new_status );

		if ( ! $this->status_manager->can_transition( $old_status, $new_status ) ) {
			return new \WP_Error(
				'invalid_transition',
				sprintf(
					/* translators: 1: Old status, 2: New status */
					__( 'Cannot transition membership status from %1$s to %2$s.', 'wpcalibrate-membership-access' ),
					StatusManager::get_label( $old_status ),
					StatusManager::get_label( $new_status )
				)
			);
		}

		$wp_tz        = wp_timezone();
		$now          = ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );
		$cancelled_at = ( Membership::STATUS_CANCELLED === $new_status ) ? $now : $membership->get_cancelled_at();

		$updated_membership = new Membership(
			$membership->get_id(),
			$membership->get_user_id(),
			$membership->get_plan_id(),
			$new_status,
			$membership->get_start_date(),
			$membership->get_expires_at(),
			$membership->get_source(),
			$membership->get_order_id(),
			$membership->get_subscription_id(),
			$membership->get_created_at(),
			$now,
			$cancelled_at,
			$membership->get_metadata()
		);

		$saved_id = $this->membership_repo->save( $updated_membership );
		if ( ! $saved_id ) {
			return new \WP_Error( 'db_save_failed', __( 'Failed to update membership status in database.', 'wpcalibrate-membership-access' ) );
		}

		// Log audit.
		$event_type = match ( $new_status ) {
			Membership::STATUS_ACTIVE    => ( Membership::STATUS_SUSPENDED === $old_status ? AuditLogger::EVENT_REACTIVATED : AuditLogger::EVENT_ACTIVATED ),
			Membership::STATUS_SUSPENDED => AuditLogger::EVENT_SUSPENDED,
			Membership::STATUS_CANCELLED => AuditLogger::EVENT_CANCELLED,
			Membership::STATUS_EXPIRED   => AuditLogger::EVENT_EXPIRED,
			default                      => 'status_change',
		};

		$this->audit_logger->log(
			$membership_id,
			$membership->get_user_id(),
			$event_type,
			$old_status,
			$new_status,
			[ 'reason' => $reason ],
			$actor_id
		);

		// Trigger general and status-specific actions.
		do_action(
			'wpcalibrate_membership_access_membership_status_changed',
			$membership_id,
			$new_status,
			$old_status,
			$updated_membership
		);

		$plan = $this->plan_repo->get( $membership->get_plan_id() );
		if ( Membership::STATUS_ACTIVE === $new_status ) {
			do_action( 'wpcalibrate_membership_access_membership_activated', $membership_id, $updated_membership, $plan );
		} elseif ( Membership::STATUS_SUSPENDED === $new_status ) {
			do_action( 'wpcalibrate_membership_access_membership_suspended', $membership_id, $updated_membership, $plan );
		} elseif ( Membership::STATUS_CANCELLED === $new_status ) {
			do_action( 'wpcalibrate_membership_access_membership_cancelled', $membership_id, $updated_membership, $plan );
		} elseif ( Membership::STATUS_EXPIRED === $new_status ) {
			do_action( 'wpcalibrate_membership_access_membership_expired', $membership_id, $updated_membership, $plan );
		}

		return true;
	}

	/**
	 * Renew or extend a membership.
	 *
	 * @param int         $membership_id
	 * @param string|null $custom_expiry Custom expiry date, or null to compute from plan duration.
	 * @param string      $reason
	 * @return bool|\WP_Error
	 */
	public function renew_membership( int $membership_id, ?string $custom_expiry = null, string $reason = '' ): bool|\WP_Error {
		$membership = $this->membership_repo->get( $membership_id );
		if ( ! $membership ) {
			return new \WP_Error( 'not_found', __( 'Membership not found.', 'wpcalibrate-membership-access' ) );
		}

		$plan = $this->plan_repo->get( $membership->get_plan_id() );
		if ( ! $plan ) {
			return new \WP_Error( 'invalid_plan', __( 'Plan not found.', 'wpcalibrate-membership-access' ) );
		}

		$wp_tz = wp_timezone();
		$now   = ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );

		if ( null !== $custom_expiry ) {
			$new_expiry = $custom_expiry;
		} elseif ( $plan->is_lifetime() ) {
			$new_expiry = null;
		} else {
			// If membership is active and unexpired, extend from current expiry date.
			// If already expired, extend from now.
			$base_date = ( ! $membership->is_past_expiry() && ! empty( $membership->get_expires_at() ) )
				? $membership->get_expires_at()
				: $now;

			$new_expiry = $plan->calculate_expiry( $base_date );
		}

		$old_status = $membership->get_status();
		$old_expiry = $membership->get_expires_at();

		// Reset reminder flag in metadata so renewal allows future reminders.
		$meta = $membership->get_metadata();
		unset( $meta['reminder_sent'], $meta['reminder_sent_at'] );

		$updated = new Membership(
			$membership->get_id(),
			$membership->get_user_id(),
			$membership->get_plan_id(),
			Membership::STATUS_ACTIVE,
			$membership->get_start_date(),
			$new_expiry,
			$membership->get_source(),
			$membership->get_order_id(),
			$membership->get_subscription_id(),
			$membership->get_created_at(),
			$now,
			null,
			$meta
		);

		$saved_id = $this->membership_repo->save( $updated );
		if ( ! $saved_id ) {
			return new \WP_Error( 'db_save_failed', __( 'Could not save renewed membership.', 'wpcalibrate-membership-access' ) );
		}

		$this->audit_logger->log(
			$membership_id,
			$membership->get_user_id(),
			AuditLogger::EVENT_RENEWED,
			$old_status,
			Membership::STATUS_ACTIVE,
			[
				'old_expires_at' => $old_expiry,
				'new_expires_at' => $new_expiry,
				'reason'         => $reason,
			]
		);

		do_action( 'wpcalibrate_membership_access_membership_renewed', $membership_id, $updated, $plan );

		return true;
	}

	/**
	 * Manually edit membership dates or lifetime toggle from administration.
	 */
	public function update_assignment(
		int $membership_id,
		int $new_plan_id,
		string $start_date,
		?string $expires_at,
		string $status,
		string $reason = '',
		int $actor_id = 0
	): bool|\WP_Error {
		$membership = $this->membership_repo->get( $membership_id );
		if ( ! $membership ) {
			return new \WP_Error( 'not_found', __( 'Membership not found.', 'wpcalibrate-membership-access' ) );
		}

		$plan = $this->plan_repo->get( $new_plan_id );
		if ( ! $plan ) {
			return new \WP_Error( 'invalid_plan', __( 'Invalid membership plan.', 'wpcalibrate-membership-access' ) );
		}

		$wp_tz = wp_timezone();
		$now   = ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );

		$updated = new Membership(
			$membership->get_id(),
			$membership->get_user_id(),
			$new_plan_id,
			sanitize_key( $status ),
			sanitize_text_field( $start_date ),
			$expires_at,
			$membership->get_source(),
			$membership->get_order_id(),
			$membership->get_subscription_id(),
			$membership->get_created_at(),
			$now,
			( Membership::STATUS_CANCELLED === $status ? $now : null ),
			$membership->get_metadata()
		);

		$saved = $this->membership_repo->save( $updated );
		if ( ! $saved ) {
			return new \WP_Error( 'db_save_failed', __( 'Could not update membership assignment.', 'wpcalibrate-membership-access' ) );
		}

		$this->audit_logger->log(
			$membership_id,
			$membership->get_user_id(),
			AuditLogger::EVENT_MANUAL_UPDATE,
			$membership->get_status(),
			$status,
			[
				'old_plan_id' => $membership->get_plan_id(),
				'new_plan_id' => $new_plan_id,
				'start_date'  => $start_date,
				'expires_at'  => $expires_at,
				'reason'      => $reason,
			],
			$actor_id
		);

		return true;
	}
}
