<?php
/**
 * Membership Audit Logger.
 *
 * Persists transactional history records for all key membership events.
 *
 * @package WPCalibrate\MembershipAccess\Audit
 */

namespace WPCalibrate\MembershipAccess\Audit;

use WPCalibrate\MembershipAccess\Database\Schema;

defined( 'ABSPATH' ) || exit;
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );

class AuditLogger {

	public const EVENT_CREATED           = 'created';
	public const EVENT_ACTIVATED         = 'activated';
	public const EVENT_MANUAL_UPDATE     = 'manual_update';
	public const EVENT_SUSPENDED         = 'suspended';
	public const EVENT_REACTIVATED       = 'reactivated';
	public const EVENT_CANCELLED         = 'cancelled';
	public const EVENT_EXPIRED           = 'expired';
	public const EVENT_RENEWED           = 'renewed';
	public const EVENT_WC_ORDER_GRANT    = 'wc_order_grant';
	public const EVENT_WC_REVOKED        = 'wc_revoked';
	public const EVENT_WC_SUBSCRIPTION   = 'wc_subscription';

	/**
	 * Log an audit trail entry.
	 *
	 * @param int         $membership_id
	 * @param int         $user_id
	 * @param string      $event_type
	 * @param string|null $old_status
	 * @param string|null $new_status
	 * @param array       $context
	 * @param int         $actor_id (0 defaults to current user ID)
	 * @return int Inserted row ID
	 */
	public function log(
		int $membership_id,
		int $user_id,
		string $event_type,
		?string $old_status = null,
		?string $new_status = null,
		array $context = [],
		int $actor_id = 0
	): int {
		global $wpdb;

		if ( 0 === $actor_id && function_exists( 'get_current_user_id' ) ) {
			$actor_id = (int) get_current_user_id();
		}

		$table = Schema::get_audit_table_name();
		$wp_tz = wp_timezone();
		$now   = ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );

		// Sanitize context array: strip secrets and passwords.
		$sanitized_context = $this->sanitize_context( $context );

		$result = $wpdb->insert(
			$table,
			[
				'membership_id' => $membership_id,
				'user_id'       => $user_id,
				'actor_id'      => $actor_id,
				'event_type'    => sanitize_key( $event_type ),
				'old_status'    => $old_status ? sanitize_key( $old_status ) : null,
				'new_status'    => $new_status ? sanitize_key( $new_status ) : null,
				'context'       => wp_json_encode( $sanitized_context ),
				'created_at'    => $now,
			],
			[ '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Get audit records for a given membership assignment.
	 *
	 * @param int $membership_id
	 * @param int $limit
	 * @return array
	 */
	public function get_for_membership( int $membership_id, int $limit = 50 ): array {
		global $wpdb;
		$table = Schema::get_audit_table_name();

		$limit = min( 200, max( 1, $limit ) );
		$sql   = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE membership_id = %d ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$membership_id,
			$limit
		);

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		return array_map( [ $this, 'format_row' ], $rows );
	}

	/**
	 * Get audit records for a given user ID.
	 */
	public function get_for_user( int $user_id, int $limit = 50 ): array {
		global $wpdb;
		$table = Schema::get_audit_table_name();

		$limit = min( 200, max( 1, $limit ) );
		$sql   = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id,
			$limit
		);

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		return array_map( [ $this, 'format_row' ], $rows );
	}

	/**
	 * Delete all audit records for a user (Privacy / Right to be Forgotten).
	 */
	public function delete_for_user( int $user_id ): int {
		global $wpdb;
		$table = Schema::get_audit_table_name();
		return (int) $wpdb->delete( $table, [ 'user_id' => $user_id ], [ '%d' ] );
	}

	/**
	 * Format row with decoded context.
	 */
	private function format_row( array $row ): array {
		$context = [];
		if ( ! empty( $row['context'] ) ) {
			$decoded = json_decode( (string) $row['context'], true );
			if ( is_array( $decoded ) ) {
				$context = $decoded;
			}
		}
		$row['context'] = $context;
		return $row;
	}

	/**
	 * Strip any sensitive keys from context.
	 */
	private function sanitize_context( array $data ): array {
		$disallowed = [ 'password', 'secret', 'token', 'license_key', 'api_key', 'card_number', 'cvv' ];
		$clean      = [];

		foreach ( $data as $k => $v ) {
			$k_lower = strtolower( (string) $k );
			foreach ( $disallowed as $forbidden ) {
				if ( str_contains( $k_lower, $forbidden ) ) {
					continue 2;
				}
			}
			if ( is_array( $v ) ) {
				$clean[ $k ] = $this->sanitize_context( $v );
			} else {
				$clean[ $k ] = sanitize_text_field( (string) $v );
			}
		}

		return $clean;
	}
}
