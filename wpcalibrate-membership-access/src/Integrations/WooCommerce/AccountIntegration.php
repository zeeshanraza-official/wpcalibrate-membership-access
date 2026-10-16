<?php
/**
 * WooCommerce My Account Endpoint Integration.
 *
 * Adds a dedicated "Memberships" tab to WooCommerce My Account.
 *
 * @package WPCalibrate\MembershipAccess\Integrations\WooCommerce
 */

namespace WPCalibrate\MembershipAccess\Integrations\WooCommerce;

use WPCalibrate\MembershipAccess\Frontend\MemberDashboard;

defined( 'ABSPATH' ) || exit;

class AccountIntegration {

	public const ENDPOINT = 'memberships';

	public function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'add_endpoint' ] );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_item' ] );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', [ $this, 'render_content' ] );
	}

	public function add_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	public function add_menu_item( array $items ): array {
		$new_items = [];
		foreach ( $items as $key => $title ) {
			$new_items[ $key ] = $title;
			if ( 'orders' === $key ) {
				$new_items[ self::ENDPOINT ] = __( 'Memberships', 'wpcalibrate-membership-access' );
			}
		}

		if ( ! isset( $new_items[ self::ENDPOINT ] ) ) {
			$new_items[ self::ENDPOINT ] = __( 'Memberships', 'wpcalibrate-membership-access' );
		}

		return $new_items;
	}

	public function render_content(): void {
		$user_id   = (int) get_current_user_id();
		$dashboard = new MemberDashboard(
			\WPCalibrate\MembershipAccess\Plugin::get_instance()->get_membership_repository(),
			\WPCalibrate\MembershipAccess\Plugin::get_instance()->get_plan_repository()
		);

		echo $dashboard->render_dashboard_html( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
