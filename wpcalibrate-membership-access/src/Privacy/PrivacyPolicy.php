<?php
/**
 * WordPress Privacy Exporter & Eraser Integration.
 *
 * @package WPCalibrate\MembershipAccess\Privacy
 */

namespace WPCalibrate\MembershipAccess\Privacy;

use WPCalibrate\MembershipAccess\Audit\AuditLogger;
use WPCalibrate\MembershipAccess\Membership\MembershipRepository;

defined( 'ABSPATH' ) || exit;

class PrivacyPolicy {

	public function __construct(
		private MembershipRepository $membership_repo,
		private AuditLogger $audit_logger
	) {}

	public function init(): void {
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'register_exporter' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_eraser' ] );
	}

	public function register_exporter( array $exporters ): array {
		$exporters['wpcalibrate-membership-access'] = [
			'exporter_friendly_name' => __( 'WPCalibrate Membership Records', 'wpcalibrate-membership-access' ),
			'callback'               => [ $this, 'export_user_data' ],
		];
		return $exporters;
	}

	public function register_eraser( array $erasers ): array {
		$erasers['wpcalibrate-membership-access'] = [
			'eraser_friendly_name' => __( 'WPCalibrate Membership Records', 'wpcalibrate-membership-access' ),
			'callback'             => [ $this, 'erase_user_data' ],
		];
		return $erasers;
	}

	/**
	 * Export user personal data for WordPress privacy tool.
	 *
	 * @param string $email_address
	 * @param int    $page
	 * @return array
	 */
	public function export_user_data( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return [
				'data' => [],
				'done' => true,
			];
		}

		$memberships = $this->membership_repo->get_for_user( $user->ID );
		$data_to_export = [];

		foreach ( $memberships as $m ) {
			$plan = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_plan_repository()->get( $m->get_plan_id() );
			$plan_name = $plan ? $plan->get_name() : '#' . $m->get_plan_id();

			$data_to_export[] = [
				'group_id'    => 'wpcalibrate_memberships',
				'group_label' => __( 'WPCalibrate Memberships', 'wpcalibrate-membership-access' ),
				'item_id'     => 'membership-' . $m->get_id(),
				'data'        => [
					[
						'name'  => __( 'Membership ID', 'wpcalibrate-membership-access' ),
						'value' => $m->get_id(),
					],
					[
						'name'  => __( 'Plan Name', 'wpcalibrate-membership-access' ),
						'value' => $plan_name,
					],
					[
						'name'  => __( 'Status', 'wpcalibrate-membership-access' ),
						'value' => $m->get_status(),
					],
					[
						'name'  => __( 'Start Date', 'wpcalibrate-membership-access' ),
						'value' => $m->get_start_date(),
					],
					[
						'name'  => __( 'Expires At', 'wpcalibrate-membership-access' ),
						'value' => $m->is_lifetime() ? __( 'Lifetime', 'wpcalibrate-membership-access' ) : $m->get_expires_at(),
					],
					[
						'name'  => __( 'Source', 'wpcalibrate-membership-access' ),
						'value' => $m->get_source(),
					],
				],
			];
		}

		return [
			'data' => $data_to_export,
			'done' => true,
		];
	}

	/**
	 * Erase user personal data for WordPress privacy tool.
	 *
	 * @param string $email_address
	 * @param int    $page
	 * @return array
	 */
	public function erase_user_data( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return [
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => [],
				'done'           => true,
			];
		}

		$deleted_memberships = $this->membership_repo->delete_for_user( $user->ID );
		$deleted_audit       = $this->audit_logger->delete_for_user( $user->ID );

		return [
			'items_removed'  => ( $deleted_memberships > 0 || $deleted_audit > 0 ),
			'items_retained' => false,
			'messages'       => [
				sprintf(
					/* translators: 1: Count memberships, 2: Count audit logs */
					__( 'Removed %1$d membership records and %2$d audit history logs.', 'wpcalibrate-membership-access' ),
					$deleted_memberships,
					$deleted_audit
				),
			],
			'done'           => true,
		];
	}
}
