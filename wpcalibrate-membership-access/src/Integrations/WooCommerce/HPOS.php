<?php
/**
 * WooCommerce High-Performance Order Storage (HPOS) Compatibility.
 *
 * @package WPCalibrate\MembershipAccess\Integrations\WooCommerce
 */

namespace WPCalibrate\MembershipAccess\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

class HPOS {

	/**
	 * Check if HPOS is active in the current WooCommerce installation.
	 */
	public static function is_hpos_enabled(): bool {
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
			return false;
		}

		return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}
}
