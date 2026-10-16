<?php
/**
 * Plugin Name:       WPCalibrate Membership Access
 * Plugin URI:        https://marketplace.wpcalibrate.com/
 * Description:       Create membership plans, assign memberships, restrict content, sell access via WooCommerce, and manage members.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Tested up to:      7.1.3
 * WC requires at least: 8.0
 * WC tested up to:   11.2.1
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-membership-access
 * Domain Path:       /languages
 *
 * @package           WPCalibrate\MembershipAccess
 */

defined( 'ABSPATH' ) || exit;

// Define Plugin Constants.
define( 'WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION', '1.0.0' );
define( 'WPCALIBRATE_MEMBERSHIP_ACCESS_DB_VERSION', '1.0.0' );
define( 'WPCALIBRATE_MEMBERSHIP_ACCESS_FILE', __FILE__ );
define( 'WPCALIBRATE_MEMBERSHIP_ACCESS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCALIBRATE_MEMBERSHIP_ACCESS_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCALIBRATE_MEMBERSHIP_ACCESS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * PHP Version Requirement Check (Minimum PHP 8.2).
 */
if ( version_compare( PHP_VERSION, '8.2.0', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: PHP version */
							__( 'WPCalibrate Membership Access requires PHP 8.2 or higher. Your server is running PHP %s. Please upgrade your PHP version.', 'wpcalibrate-membership-access' ),
							PHP_VERSION
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

/**
 * PSR-4 Autoloader for WPCalibrate\MembershipAccess.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix   = 'WPCalibrate\\MembershipAccess\\';
		$base_dir = WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'src/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Register Activation and Deactivation Hooks.
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		\WPCalibrate\MembershipAccess\Activation\Activator::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		\WPCalibrate\MembershipAccess\Activation\Deactivator::deactivate();
	}
);

/**
 * Declare WooCommerce HPOS and Blocks Compatibility safely.
 */
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
		}
	}
);

/**
 * Initialize Plugin Singleton on plugins_loaded.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		\WPCalibrate\MembershipAccess\Plugin::get_instance()->init();
	},
	10
);
