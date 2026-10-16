<?php
/**
 * WooCommerce Subscriptions Adapter.
 *
 * Listens to official WooCommerce Subscriptions lifecycle events to synchronize
 * membership access, renewals, suspensions, and cancellations without imitating billing engines.
 *
 * @package WPCalibrate\MembershipAccess\Integrations\WooSubscriptions
 */

namespace WPCalibrate\MembershipAccess\Integrations\WooSubscriptions;

use WPCalibrate\MembershipAccess\Membership\Membership;
use WPCalibrate\MembershipAccess\Membership\MembershipService;
use WPCalibrate\MembershipAccess\Membership\PlanRepository;

defined( 'ABSPATH' ) || exit;

class SubscriptionIntegration {

	public function __construct(
		private MembershipService $membership_service,
		private PlanRepository $plan_repo
	) {}

	public static function is_active(): bool {
		return class_exists( 'WC_Subscriptions' );
	}

	public function init(): void {
		if ( ! self::is_active() ) {
			return;
		}

		// Official WooCommerce Subscriptions status hooks.
		add_action( 'woocommerce_subscription_status_active', [ $this, 'on_subscription_activated' ], 10, 1 );
		add_action( 'woocommerce_subscription_status_on-hold', [ $this, 'on_subscription_on_hold' ], 10, 1 );
		add_action( 'woocommerce_subscription_status_cancelled', [ $this, 'on_subscription_cancelled' ], 10, 1 );
		add_action( 'woocommerce_subscription_status_expired', [ $this, 'on_subscription_expired' ], 10, 1 );
		add_action( 'woocommerce_subscription_renewal_payment_complete', [ $this, 'on_renewal_complete' ], 10, 2 );
	}

	/**
	 * When a subscription is activated or reactivated.
	 *
	 * @param \WC_Subscription $subscription
	 */
	public function on_subscription_activated( \WC_Subscription $subscription ): void {
		$sub_id  = $subscription->get_id();
		$user_id = $subscription->get_user_id();
		if ( ! $user_id || $user_id <= 0 ) {
			return;
		}

		$repo     = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_membership_repository();
		$existing = $repo->get_by_subscription( $sub_id );

		if ( $existing ) {
			// Reactivate existing if suspended or expired.
			if ( Membership::STATUS_ACTIVE !== $existing->get_status() ) {
				$this->membership_service->update_status(
					$existing->get_id(),
					Membership::STATUS_ACTIVE,
					__( 'WooCommerce Subscription reactivated', 'wpcalibrate-membership-access' )
				);
			}
			return;
		}

		// First time grant for subscription:
		foreach ( $subscription->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$plans      = $this->plan_repo->get_for_product( $product_id );

			foreach ( $plans as $plan ) {
				$this->membership_service->create_membership(
					$user_id,
					$plan->get_id(),
					Membership::SOURCE_SUBSCRIPTION,
					[
						'subscription_id' => $sub_id,
					]
				);
			}
		}
	}

	/**
	 * When a subscription is put on-hold (e.g. renewal payment failed).
	 */
	public function on_subscription_on_hold( \WC_Subscription $subscription ): void {
		$sub_id   = $subscription->get_id();
		$repo     = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_membership_repository();
		$existing = $repo->get_by_subscription( $sub_id );

		if ( $existing && Membership::STATUS_ACTIVE === $existing->get_status() ) {
			$this->membership_service->update_status(
				$existing->get_id(),
				Membership::STATUS_SUSPENDED,
				__( 'WooCommerce Subscription placed on-hold', 'wpcalibrate-membership-access' )
			);
		}
	}

	/**
	 * When a subscription is cancelled.
	 */
	public function on_subscription_cancelled( \WC_Subscription $subscription ): void {
		$sub_id   = $subscription->get_id();
		$repo     = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_membership_repository();
		$existing = $repo->get_by_subscription( $sub_id );

		if ( $existing ) {
			$this->membership_service->update_status(
				$existing->get_id(),
				Membership::STATUS_CANCELLED,
				__( 'WooCommerce Subscription cancelled', 'wpcalibrate-membership-access' )
			);
		}
	}

	/**
	 * When a subscription reaches end of duration / expires.
	 */
	public function on_subscription_expired( \WC_Subscription $subscription ): void {
		$sub_id   = $subscription->get_id();
		$repo     = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_membership_repository();
		$existing = $repo->get_by_subscription( $sub_id );

		if ( $existing ) {
			$this->membership_service->update_status(
				$existing->get_id(),
				Membership::STATUS_EXPIRED,
				__( 'WooCommerce Subscription expired', 'wpcalibrate-membership-access' )
			);
		}
	}

	/**
	 * When a subscription renewal payment successfully completes.
	 *
	 * @param \WC_Subscription $subscription
	 * @param \WC_Order        $last_order
	 */
	public function on_renewal_complete( \WC_Subscription $subscription, \WC_Order $last_order ): void {
		$sub_id   = $subscription->get_id();
		$repo     = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_membership_repository();
		$existing = $repo->get_by_subscription( $sub_id );

		if ( $existing ) {
			$this->membership_service->renew_membership(
				$existing->get_id(),
				null,
				sprintf(
					/* translators: %d: Order ID */
					__( 'WooCommerce Subscription renewed via order #%d', 'wpcalibrate-membership-access' ),
					$last_order->get_id()
				)
			);
		}
	}
}
