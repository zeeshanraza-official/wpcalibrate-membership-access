<?php
/**
 * WooCommerce Order Handler.
 *
 * Grants and revokes memberships idempotently based on order lifecycle events.
 *
 * @package WPCalibrate\MembershipAccess\Integrations\WooCommerce
 */

namespace WPCalibrate\MembershipAccess\Integrations\WooCommerce;

use WPCalibrate\MembershipAccess\Membership\Membership;
use WPCalibrate\MembershipAccess\Membership\MembershipRepository;
use WPCalibrate\MembershipAccess\Membership\MembershipService;
use WPCalibrate\MembershipAccess\Membership\PlanRepository;

defined( 'ABSPATH' ) || exit;

class OrderHandler {

	public function __construct(
		private MembershipService $membership_service,
		private MembershipRepository $membership_repo,
		private PlanRepository $plan_repo
	) {}

	public function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'woocommerce_order_status_changed', [ $this, 'on_order_status_changed' ], 10, 4 );
	}

	/**
	 * Respond to order status changes.
	 *
	 * @param int       $order_id
	 * @param string    $status_from
	 * @param string    $status_to
	 * @param \WC_Order $order
	 */
	public function on_order_status_changed( int $order_id, string $status_from, string $status_to, \WC_Order $order ): void {
		$settings       = get_option( 'wpcalibrate_membership_access_settings', [] );
		$grant_statuses = (array) ( $settings['wc_grant_order_statuses'] ?? [ 'processing', 'completed' ] );

		// 1. Grant Membership if entering eligible paid status.
		if ( in_array( $status_to, $grant_statuses, true ) ) {
			$this->process_order_grants( $order );
			return;
		}

		// 2. Revoke or Cancel on Refund.
		if ( 'refunded' === $status_to ) {
			$action = $settings['wc_revoke_on_refund'] ?? 'cancel';
			if ( 'none' !== $action ) {
				$this->process_order_revocation( $order, $action, __( 'Order refunded', 'wpcalibrate-membership-access' ) );
			}
			return;
		}

		// 3. Revoke or Cancel on Cancellation.
		if ( 'cancelled' === $status_to ) {
			$action = $settings['wc_revoke_on_cancelled'] ?? 'cancel';
			if ( 'none' !== $action ) {
				$this->process_order_revocation( $order, $action, __( 'Order cancelled', 'wpcalibrate-membership-access' ) );
			}
			return;
		}
	}

	/**
	 * Process membership grants for an order idempotently.
	 */
	public function process_order_grants( \WC_Order $order ): void {
		$user_id = $order->get_user_id();
		if ( ! $user_id || $user_id <= 0 ) {
			// Guest checkout without an account cannot be granted access.
			return;
		}

		$order_id = $order->get_id();

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			if ( ! $product_id ) {
				continue;
			}

			// Find plans mapped to this product.
			$plans = $this->plan_repo->get_for_product( $product_id );
			foreach ( $plans as $plan ) {
				// Idempotency check: verify if this order has already granted this plan.
				$existing = $this->membership_repo->get_by_order( $order_id, $plan->get_id() );
				if ( $existing ) {
					// Already processed; avoid duplicates.
					continue;
				}

				$created_id = $this->membership_service->create_membership(
					$user_id,
					$plan->get_id(),
					Membership::SOURCE_ORDER,
					[
						'order_id' => $order_id,
					]
				);

				if ( ! is_wp_error( $created_id ) && $created_id > 0 ) {
					$order->add_order_note(
						sprintf(
							/* translators: 1: Plan name, 2: Membership ID */
							__( 'Granted membership "%1$s" (Assignment #%2$d).', 'wpcalibrate-membership-access' ),
							$plan->get_name(),
							$created_id
						)
					);
				}
			}
		}
	}

	/**
	 * Revoke or expire memberships linked to an order.
	 */
	public function process_order_revocation( \WC_Order $order, string $action, string $reason ): void {
		$order_id = $order->get_id();
		global $wpdb;
		$table = \WPCalibrate\MembershipAccess\Database\Schema::get_memberships_table_name();

		$sql = $wpdb->prepare(
			"SELECT id FROM {$table} WHERE order_id = %d AND status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$order_id,
			Membership::STATUS_ACTIVE
		);

		$ids = $wpdb->get_col( $sql );
		if ( empty( $ids ) ) {
			return;
		}

		$target_status = ( 'expire' === $action ) ? Membership::STATUS_EXPIRED : Membership::STATUS_CANCELLED;

		foreach ( $ids as $membership_id ) {
			$this->membership_service->update_status( (int) $membership_id, $target_status, $reason );
		}

		$order->add_order_note(
			sprintf(
				/* translators: 1: Target status, 2: Reason */
				__( 'Membership assignments updated to %1$s (%2$s).', 'wpcalibrate-membership-access' ),
				$target_status,
				$reason
			)
		);
	}
}
