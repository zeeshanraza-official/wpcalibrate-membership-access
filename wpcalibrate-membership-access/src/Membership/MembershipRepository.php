<?php
/**
 * User Membership Repository.
 *
 * Provides high-performance database interactions for the custom
 * `wpcalibrate_memberships` table, complete with prepared queries and indexed lookups.
 *
 * @package WPCalibrate\MembershipAccess\Membership
 */

namespace WPCalibrate\MembershipAccess\Membership;

use WPCalibrate\MembershipAccess\Database\Schema;

defined( 'ABSPATH' ) || exit;
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );

class MembershipRepository {

	/**
	 * In-memory cache for user active memberships within the current request.
	 *
	 * @var array<int, Membership[]>
	 */
	private array $user_active_cache = [];

	/**
	 * Find a membership by primary ID.
	 */
	public function get( int $id ): ?Membership {
		if ( $id <= 0 ) {
			return null;
		}

		global $wpdb;
		$table = Schema::get_memberships_table_name();

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$id
		);

		$row = $wpdb->get_row( $sql, ARRAY_A );
		if ( ! $row || ! is_array( $row ) ) {
			return null;
		}

		return $this->hydrate( $row );
	}

	/**
	 * Find all memberships for a specific user.
	 *
	 * @param int  $user_id
	 * @param bool $only_active If true, returns only currently active, non-expired records.
	 * @return Membership[]
	 */
	public function get_for_user( int $user_id, bool $only_active = false ): array {
		if ( $user_id <= 0 ) {
			return [];
		}

		if ( $only_active && isset( $this->user_active_cache[ $user_id ] ) ) {
			return $this->user_active_cache[ $user_id ];
		}

		global $wpdb;
		$table = Schema::get_memberships_table_name();

		if ( $only_active ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND status = %s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				Membership::STATUS_ACTIVE
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			);
		}

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$memberships = array_map( [ $this, 'hydrate' ], $rows );

		if ( $only_active ) {
			// Filter out any where is_active() is false due to immediate past-due expiry.
			$memberships = array_values(
				array_filter(
					$memberships,
					static fn( Membership $m ) => $m->is_active()
				)
			);
			$this->user_active_cache[ $user_id ] = $memberships;
		}

		return $memberships;
	}

	/**
	 * Check if a user currently has an active assignment for a given plan.
	 */
	public function user_has_plan( int $user_id, int $plan_id ): bool {
		$active = $this->get_for_user( $user_id, true );
		foreach ( $active as $membership ) {
			if ( $membership->get_plan_id() === $plan_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Find membership by WooCommerce order ID.
	 */
	public function get_by_order( int $order_id, ?int $plan_id = null ): ?Membership {
		if ( $order_id <= 0 ) {
			return null;
		}

		global $wpdb;
		$table = Schema::get_memberships_table_name();

		if ( $plan_id && $plan_id > 0 ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE order_id = %d AND plan_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$order_id,
				$plan_id
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE order_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$order_id
			);
		}

		$row = $wpdb->get_row( $sql, ARRAY_A );
		return $row ? $this->hydrate( $row ) : null;
	}

	/**
	 * Find membership by WooCommerce subscription ID.
	 */
	public function get_by_subscription( int $subscription_id ): ?Membership {
		if ( $subscription_id <= 0 ) {
			return null;
		}

		global $wpdb;
		$table = Schema::get_memberships_table_name();

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE subscription_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$subscription_id
		);

		$row = $wpdb->get_row( $sql, ARRAY_A );
		return $row ? $this->hydrate( $row ) : null;
	}

	/**
	 * Save a membership (Insert or Update).
	 *
	 * @param Membership $membership
	 * @return int Inserted/Updated ID, or 0 on failure.
	 */
	public function save( Membership $membership ): int {
		global $wpdb;
		$table = Schema::get_memberships_table_name();
		$wp_tz = wp_timezone();
		$now   = ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );

		$data = [
			'user_id'         => $membership->get_user_id(),
			'plan_id'         => $membership->get_plan_id(),
			'status'          => $membership->get_status(),
			'start_date'      => $membership->get_start_date() ?: $now,
			'expires_at'      => $membership->get_expires_at(),
			'source'          => $membership->get_source(),
			'order_id'        => $membership->get_order_id(),
			'subscription_id' => $membership->get_subscription_id(),
			'updated_at'      => $now,
			'cancelled_at'    => $membership->get_cancelled_at(),
			'metadata'        => ! empty( $membership->get_metadata() ) ? wp_json_encode( $membership->get_metadata() ) : null,
		];

		$formats = [
			'%d', // user_id
			'%d', // plan_id
			'%s', // status
			'%s', // start_date
			null === $membership->get_expires_at() ? null : '%s',
			'%s', // source
			null === $membership->get_order_id() ? null : '%d',
			null === $membership->get_subscription_id() ? null : '%d',
			'%s', // updated_at
			null === $membership->get_cancelled_at() ? null : '%s',
			'%s', // metadata
		];

		// Clear user active cache.
		unset( $this->user_active_cache[ $membership->get_user_id() ] );

		if ( $membership->get_id() > 0 ) {
			$wpdb->update(
				$table,
				$data,
				[ 'id' => $membership->get_id() ],
				$formats,
				[ '%d' ]
			);
			return $membership->get_id();
		}

		$data['created_at'] = $now;
		$formats[]          = '%s';

		$res = $wpdb->insert( $table, $data, $formats );
		return $res ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Delete a single membership assignment by ID.
	 */
	public function delete( int $id ): bool {
		if ( $id <= 0 ) {
			return false;
		}

		$m = $this->get( $id );
		if ( $m ) {
			unset( $this->user_active_cache[ $m->get_user_id() ] );
		}

		global $wpdb;
		$table = Schema::get_memberships_table_name();
		$res   = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
		return false !== $res;
	}

	/**
	 * Delete all memberships for a user.
	 */
	public function delete_for_user( int $user_id ): int {
		if ( $user_id <= 0 ) {
			return 0;
		}

		unset( $this->user_active_cache[ $user_id ] );
		global $wpdb;
		$table = Schema::get_memberships_table_name();
		return (int) $wpdb->delete( $table, [ 'user_id' => $user_id ], [ '%d' ] );
	}

	/**
	 * Query memberships for the admin table with filtering, search, and pagination.
	 *
	 * @param array $args
	 * @return array{ items: Membership[], total: int }
	 */
	public function query_admin( array $args = [] ): array {
		global $wpdb;
		$table = Schema::get_memberships_table_name();

		$defaults = [
			'search'       => '',
			'plan_id'      => 0,
			'status'       => '',
			'source'       => '',
			'expiry_state' => '', // 'active', 'expiring_soon', 'expired', 'lifetime'
			'orderby'      => 'id',
			'order'        => 'DESC',
			'limit'        => 20,
			'offset'       => 0,
		];
		$params   = array_merge( $defaults, $args );

		$where  = [ '1=1' ];
		$values = [];

		if ( ! empty( $params['plan_id'] ) ) {
			$where[]  = 'm.plan_id = %d';
			$values[] = (int) $params['plan_id'];
		}

		if ( ! empty( $params['status'] ) ) {
			$where[]  = 'm.status = %s';
			$values[] = sanitize_key( $params['status'] );
		}

		if ( ! empty( $params['source'] ) ) {
			$where[]  = 'm.source = %s';
			$values[] = sanitize_key( $params['source'] );
		}

		$wp_tz = wp_timezone();
		$now   = ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );

		if ( ! empty( $params['expiry_state'] ) ) {
			switch ( $params['expiry_state'] ) {
				case 'lifetime':
					$where[] = 'm.expires_at IS NULL';
					break;
				case 'expired':
					$where[]  = 'm.expires_at IS NOT NULL AND m.expires_at <= %s';
					$values[] = $now;
					break;
				case 'expiring_soon':
					$in_7_days = ( new \DateTimeImmutable( '+7 days', $wp_tz ) )->format( 'Y-m-d H:i:s' );
					$where[]   = 'm.expires_at IS NOT NULL AND m.expires_at > %s AND m.expires_at <= %s';
					$values[]  = $now;
					$values[]  = $in_7_days;
					break;
				case 'active_unexpired':
					$where[]  = '(m.expires_at IS NULL OR m.expires_at > %s)';
					$values[] = $now;
					break;
			}
		}

		// Search users by user_login, user_email, display_name.
		$join = '';
		if ( ! empty( $params['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( sanitize_text_field( $params['search'] ) ) . '%';
			$join        = "LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID";
			$where[]     = '(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)';
			$values[]    = $search_term;
			$values[]    = $search_term;
			$values[]    = $search_term;
		}

		$where_clause = implode( ' AND ', $where );

		// Count total matching.
		$count_sql = "SELECT COUNT(*) FROM {$table} m {$join} WHERE {$where_clause}";
		if ( ! empty( $values ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$total = (int) $wpdb->get_var( $count_sql );

		// Allowlist orderby columns.
		$allowed_cols = [
			'id'         => 'm.id',
			'user_id'    => 'm.user_id',
			'plan_id'    => 'm.plan_id',
			'status'     => 'm.status',
			'start_date' => 'm.start_date',
			'expires_at' => 'm.expires_at',
			'created_at' => 'm.created_at',
		];

		$orderby = $allowed_cols[ $params['orderby'] ] ?? 'm.id';
		$order   = 'ASC' === strtoupper( (string) $params['order'] ) ? 'ASC' : 'DESC';
		$limit   = min( 100, max( 1, (int) $params['limit'] ) );
		$offset  = max( 0, (int) $params['offset'] );

		$query_sql = "SELECT m.* FROM {$table} m {$join} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$query_values = array_merge( $values, [ $limit, $offset ] );
		$prepared_query = $wpdb->prepare( $query_sql, $query_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$rows = $wpdb->get_results( $prepared_query, ARRAY_A );
		$items = is_array( $rows ) ? array_map( [ $this, 'hydrate' ], $rows ) : [];

		return [
			'items' => $items,
			'total' => $total,
		];
	}

	/**
	 * Find active memberships whose expiration date has passed, for status normalization.
	 *
	 * @param int $limit
	 * @return Membership[]
	 */
	public function find_expired_active_memberships( int $limit = 100 ): array {
		global $wpdb;
		$table = Schema::get_memberships_table_name();
		$wp_tz = wp_timezone();
		$now   = ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE status = %s AND expires_at IS NOT NULL AND expires_at <= %s ORDER BY expires_at ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			Membership::STATUS_ACTIVE,
			$now,
			min( 500, max( 1, $limit ) )
		);

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return is_array( $rows ) ? array_map( [ $this, 'hydrate' ], $rows ) : [];
	}

	/**
	 * Find active memberships approaching expiration within N days that haven't received reminder yet.
	 *
	 * @param int $days_threshold (e.g. 7)
	 * @param int $limit
	 * @return Membership[]
	 */
	public function find_memberships_for_expiry_reminder( int $days_threshold = 7, int $limit = 50 ): array {
		global $wpdb;
		$table = Schema::get_memberships_table_name();
		$wp_tz = wp_timezone();
		$now   = ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );
		$until = ( new \DateTimeImmutable( "+{$days_threshold} days", $wp_tz ) )->format( 'Y-m-d H:i:s' );

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table} 
			 WHERE status = %s 
			   AND expires_at IS NOT NULL 
			   AND expires_at > %s 
			   AND expires_at <= %s 
			   AND (metadata NOT LIKE %s OR metadata IS NULL)
			 ORDER BY expires_at ASC 
			 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			Membership::STATUS_ACTIVE,
			$now,
			$until,
			'%"reminder_sent":true%',
			min( 200, max( 1, $limit ) )
		);

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return is_array( $rows ) ? array_map( [ $this, 'hydrate' ], $rows ) : [];
	}

	/**
	 * Summary count statistics for Overview admin widget.
	 */
	public function get_counts_summary(): array {
		global $wpdb;
		$table = Schema::get_memberships_table_name();
		$wp_tz = wp_timezone();
		$now   = ( new \DateTimeImmutable( 'now', $wp_tz ) )->format( 'Y-m-d H:i:s' );
		$in_7  = ( new \DateTimeImmutable( '+7 days', $wp_tz ) )->format( 'Y-m-d H:i:s' );

		$sql = "SELECT 
			COUNT(CASE WHEN status = 'active' AND (expires_at IS NULL OR expires_at > '{$now}') THEN 1 END) as active_count,
			COUNT(CASE WHEN status = 'active' AND expires_at IS NOT NULL AND expires_at > '{$now}' AND expires_at <= '{$in_7}' THEN 1 END) as expiring_soon_count,
			COUNT(CASE WHEN status = 'expired' OR (status = 'active' AND expires_at IS NOT NULL AND expires_at <= '{$now}') THEN 1 END) as expired_count,
			COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_count,
			COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
			COUNT(*) as total_count
		FROM {$table}";

		$row = $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! is_array( $row ) ) {
			return [
				'active_count'        => 0,
				'expiring_soon_count' => 0,
				'expired_count'       => 0,
				'suspended_count'     => 0,
				'cancelled_count'     => 0,
				'total_count'         => 0,
			];
		}

		return array_map( 'absint', $row );
	}

	/**
	 * Convert database row into Membership model.
	 */
	private function hydrate( array $row ): Membership {
		$metadata = [];
		if ( ! empty( $row['metadata'] ) ) {
			$decoded = json_decode( (string) $row['metadata'], true );
			if ( is_array( $decoded ) ) {
				$metadata = $decoded;
			}
		}

		return new Membership(
			(int) $row['id'],
			(int) $row['user_id'],
			(int) $row['plan_id'],
			(string) $row['status'],
			(string) $row['start_date'],
			isset( $row['expires_at'] ) && ! empty( $row['expires_at'] ) && '0000-00-00 00:00:00' !== $row['expires_at']
				? (string) $row['expires_at']
				: null,
			(string) $row['source'],
			! empty( $row['order_id'] ) ? (int) $row['order_id'] : null,
			! empty( $row['subscription_id'] ) ? (int) $row['subscription_id'] : null,
			(string) ( $row['created_at'] ?? '' ),
			(string) ( $row['updated_at'] ?? '' ),
			isset( $row['cancelled_at'] ) && ! empty( $row['cancelled_at'] ) ? (string) $row['cancelled_at'] : null,
			$metadata
		);
	}
}
