<?php
/**
 * WooCommerce Product Access & Purchase Restriction.
 *
 * Enforces server-side restrictions on WooCommerce product viewing, purchasing,
 * cart addition, cart validation, and checkout entry.
 *
 * @package WPCalibrate\MembershipAccess\Integrations\WooCommerce
 */

namespace WPCalibrate\MembershipAccess\Integrations\WooCommerce;

use WPCalibrate\MembershipAccess\Access\AccessManager;
use WPCalibrate\MembershipAccess\Membership\PlanRepository;
use WC_Product;

defined( 'ABSPATH' ) || exit;

class ProductMembership {

	public function __construct(
		private AccessManager $access_manager,
		private PlanRepository $plan_repo
	) {}

	public function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Prevent unauthorized purchasing on product single & loop.
		add_filter( 'woocommerce_is_purchasable', [ $this, 'filter_is_purchasable' ], 10, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'filter_loop_add_to_cart' ], 10, 2 );

		// Validate cart addition server-side.
		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_add_to_cart' ], 10, 3 );

		// Re-validate cart items on cart & checkout entry.
		add_action( 'woocommerce_check_cart_items', [ $this, 'validate_cart_items' ] );
	}

	/**
	 * Check if current user can purchase the specified product.
	 */
	public function can_user_purchase( int $product_id, int $user_id = 0 ): bool {
		if ( 0 === $user_id ) {
			$user_id = (int) get_current_user_id();
		}

		return $this->access_manager->user_can_access( $user_id, $product_id );
	}

	/**
	 * Disable product purchasing if the user does not hold access.
	 *
	 * @param bool        $is_purchasable
	 * @param \WC_Product $product
	 * @return bool
	 */
	public function filter_is_purchasable( bool $is_purchasable, \WC_Product $product ): bool {
		if ( ! $is_purchasable ) {
			return false;
		}

		$product_id = $product->get_id();
		if ( ! $this->can_user_purchase( $product_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Replace add to cart button in product loops when restricted.
	 */
	public function filter_loop_add_to_cart( string $html, \WC_Product $product ): string {
		$product_id = $product->get_id();
		if ( ! $this->can_user_purchase( $product_id ) ) {
			$settings = get_option( 'wpcalibrate_membership_access_settings', [] );
			$msg      = $settings['wc_product_restricted_msg'] ?? __( 'Membership Required', 'wpcalibrate-membership-access' );
			return '<a href="' . esc_url( get_permalink( $product_id ) ) . '" class="button wpcal-restricted-btn">' . esc_html( $msg ) . '</a>';
		}

		return $html;
	}

	/**
	 * Server-side validation when attempting to add to cart.
	 */
	public function validate_add_to_cart( bool $passed, int $product_id, int $quantity ): bool {
		if ( ! $this->can_user_purchase( $product_id ) ) {
			$settings = get_option( 'wpcalibrate_membership_access_settings', [] );
			$msg      = $settings['wc_product_restricted_msg'] ?? __( 'This item requires an active membership to purchase.', 'wpcalibrate-membership-access' );
			wc_add_notice( esc_html( $msg ), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Validate cart items during cart viewing and checkout entry.
	 */
	public function validate_cart_items(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$user_id = (int) get_current_user_id();

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product_id = $cart_item['product_id'] ?? 0;
			if ( $product_id > 0 && ! $this->can_user_purchase( $product_id, $user_id ) ) {
				WC()->cart->remove_cart_item( $cart_item_key );
				$settings = get_option( 'wpcalibrate_membership_access_settings', [] );
				$msg      = $settings['wc_product_restricted_msg'] ?? __( 'An item in your cart requires an active membership and was removed.', 'wpcalibrate-membership-access' );
				wc_add_notice( esc_html( $msg ), 'error' );
			}
		}
	}
}
