<?php
/**
 * Administrator CSV Member Exporter.
 *
 * Implements low-memory streaming and strict formula injection sanitization.
 *
 * @package WPCalibrate\MembershipAccess\Export
 */

namespace WPCalibrate\MembershipAccess\Export;

use WPCalibrate\MembershipAccess\Membership\MembershipRepository;
use WPCalibrate\MembershipAccess\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

class CsvExporter {

	public function __construct( private MembershipRepository $membership_repo ) {}

	public function init(): void {
		add_action( 'admin_post_wpcal_export_members', [ $this, 'export_csv' ] );
	}

	/**
	 * Stream CSV export directly to browser.
	 */
	public function export_csv(): void {
		check_admin_referer( 'wpcal_export_members_nonce' );

		$capability = apply_filters(
			'wpcalibrate_membership_access_export_capability',
			Capabilities::CAP_EXPORT_MEMBERSHIPS
		);

		if ( ! current_user_can( $capability ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'wpcalibrate-membership-access' ), 403 );
		}

		$filename = sprintf( 'wpcalibrate-memberships-%s.csv', gmdate( 'Y-m-d-His' ) );

		// Send CSV headers.
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			exit;
		}

		// UTF-8 BOM for Excel compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Header Row.
		fputcsv(
			$output,
			[
				'Assignment ID',
				'User ID',
				'User Login',
				'User Email',
				'Display Name',
				'Plan ID',
				'Status',
				'Source',
				'Start Date',
				'Expires At',
				'Order ID',
				'Subscription ID',
				'Created At',
			]
		);

		$batch_size = 200;
		$offset     = 0;

		$plan_repo = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_plan_repository();

		while ( true ) {
			$results = $this->membership_repo->query_admin(
				[
					'limit'   => $batch_size,
					'offset'  => $offset,
					'orderby' => 'id',
					'order'   => 'ASC',
				]
			);

			$items = $results['items'];
			if ( empty( $items ) ) {
				break;
			}

			foreach ( $items as $membership ) {
				$user = get_userdata( $membership->get_user_id() );

				fputcsv(
					$output,
					[
						$this->sanitize_csv_cell( (string) $membership->get_id() ),
						$this->sanitize_csv_cell( (string) $membership->get_user_id() ),
						$this->sanitize_csv_cell( $user ? $user->user_login : '' ),
						$this->sanitize_csv_cell( $user ? $user->user_email : '' ),
						$this->sanitize_csv_cell( $user ? $user->display_name : '' ),
						$this->sanitize_csv_cell( (string) $membership->get_plan_id() ),
						$this->sanitize_csv_cell( $membership->get_status() ),
						$this->sanitize_csv_cell( $membership->get_source() ),
						$this->sanitize_csv_cell( $membership->get_start_date() ),
						$this->sanitize_csv_cell( $membership->is_lifetime() ? 'Lifetime' : (string) $membership->get_expires_at() ),
						$this->sanitize_csv_cell( $membership->get_order_id() ? (string) $membership->get_order_id() : '' ),
						$this->sanitize_csv_cell( $membership->get_subscription_id() ? (string) $membership->get_subscription_id() : '' ),
						$this->sanitize_csv_cell( $membership->get_created_at() ),
					]
				);
			}

			$offset += $batch_size;
			if ( $offset >= $results['total'] ) {
				break;
			}
		}

		fclose( $output );
		exit;
	}

	/**
	 * Sanitize CSV value against spreadsheet formula injection (CSV Injection / DDE).
	 *
	 * Prepends single quote if value begins with =, +, -, @, \t, or \r.
	 */
	public function sanitize_csv_cell( string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$first_char = $value[0];
		if ( in_array( $first_char, [ '=', '+', '-', '@', "\t", "\r" ], true ) ) {
			return "'" . $value;
		}

		return $value;
	}
}
