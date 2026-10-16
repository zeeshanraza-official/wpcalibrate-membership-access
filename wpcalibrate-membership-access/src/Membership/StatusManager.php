<?php
/**
 * Membership Status Lifecycle Manager.
 *
 * Enforces centralized, validated status transitions across all subsystems.
 *
 * @package WPCalibrate\MembershipAccess\Membership
 */

namespace WPCalibrate\MembershipAccess\Membership;

defined( 'ABSPATH' ) || exit;

class StatusManager {

	/**
	 * Map of allowed state transitions.
	 */
	private const TRANSITIONS = [
		Membership::STATUS_PENDING   => [
			Membership::STATUS_ACTIVE,
			Membership::STATUS_CANCELLED,
		],
		Membership::STATUS_ACTIVE    => [
			Membership::STATUS_EXPIRED,
			Membership::STATUS_CANCELLED,
			Membership::STATUS_SUSPENDED,
		],
		Membership::STATUS_SUSPENDED => [
			Membership::STATUS_ACTIVE,
			Membership::STATUS_CANCELLED,
			Membership::STATUS_EXPIRED,
		],
		Membership::STATUS_EXPIRED   => [
			Membership::STATUS_ACTIVE,
			Membership::STATUS_CANCELLED,
		],
		Membership::STATUS_CANCELLED => [
			Membership::STATUS_ACTIVE,
			Membership::STATUS_PENDING,
		],
	];

	/**
	 * Verify if a transition from one status to another is permissible.
	 */
	public function can_transition( string $from_status, string $to_status ): bool {
		if ( $from_status === $to_status ) {
			return true;
		}

		$allowed = self::TRANSITIONS[ $from_status ] ?? [];
		return in_array( $to_status, $allowed, true );
	}

	/**
	 * Get human-readable labels for all supported statuses.
	 *
	 * @return array<string, string>
	 */
	public static function get_status_labels(): array {
		return [
			Membership::STATUS_PENDING   => __( 'Pending', 'wpcalibrate-membership-access' ),
			Membership::STATUS_ACTIVE    => __( 'Active', 'wpcalibrate-membership-access' ),
			Membership::STATUS_EXPIRED   => __( 'Expired', 'wpcalibrate-membership-access' ),
			Membership::STATUS_CANCELLED => __( 'Cancelled', 'wpcalibrate-membership-access' ),
			Membership::STATUS_SUSPENDED => __( 'Suspended', 'wpcalibrate-membership-access' ),
		];
	}

	/**
	 * Get label for a given status.
	 */
	public static function get_label( string $status ): string {
		$labels = self::get_status_labels();
		return $labels[ $status ] ?? ucfirst( $status );
	}

	/**
	 * Get CSS badge class for a status in UI.
	 */
	public static function get_badge_class( string $status ): string {
		return match ( $status ) {
			Membership::STATUS_ACTIVE    => 'wpcal-badge--active',
			Membership::STATUS_PENDING   => 'wpcal-badge--pending',
			Membership::STATUS_EXPIRED   => 'wpcal-badge--expired',
			Membership::STATUS_CANCELLED => 'wpcal-badge--cancelled',
			Membership::STATUS_SUSPENDED => 'wpcal-badge--suspended',
			default                      => 'wpcal-badge--default',
		};
	}
}
