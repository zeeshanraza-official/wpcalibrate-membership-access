<?php
/**
 * Admin Menu Management.
 *
 * Implements defensive, shared WPCalibrate parent dashboard registration
 * and isolated Membership Access submenu.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

namespace WPCalibrate\MembershipAccess\Admin;

use WPCalibrate\MembershipAccess\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

class AdminMenu {

	public const PARENT_SLUG  = 'wpcalibrate';
	public const SUBMENU_SLUG = 'wpcalibrate-membership-access';

	public function __construct( private AdminScreens $admin_screens ) {}

	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 9 );
		add_action( 'admin_menu', [ $this, 'ensure_parent_menu_icon' ], 99 );
		add_action( 'admin_head', [ $this, 'render_admin_menu_styles' ] );
	}

	public function get_capability(): string {
		/**
		 * Filter required administrative capability for Membership Access.
		 *
		 * @param string $capability
		 */
		return apply_filters(
			'wpcalibrate_membership_access_capability',
			Capabilities::CAP_MANAGE_MEMBERSHIPS
		);
	}

	public function register_menu(): void {
		$capability = $this->get_capability();
		if ( ! current_user_can( $capability ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $menu, $admin_page_hooks;

		$icon_svg = $this->get_menu_icon();

		// 1. Check if the shared WPCalibrate parent menu is already registered.
		$parent_exists = isset( $admin_page_hooks[ self::PARENT_SLUG ] );

		if ( ! $parent_exists ) {
			add_menu_page(
				__( 'WPCalibrate', 'wpcalibrate-membership-access' ),
				__( 'WPCalibrate', 'wpcalibrate-membership-access' ),
				$capability,
				self::PARENT_SLUG,
				[ $this->admin_screens, 'render' ],
				$icon_svg,
				59
			);
		} else {
			// Ensure icon is updated to official transparent brand icon even if parent was registered by another plugin.
			$this->ensure_parent_menu_icon();
		}

		// 2. Add Membership Access submenu under WPCalibrate parent.
		$hook_suffix = add_submenu_page(
			self::PARENT_SLUG,
			__( 'Membership Access', 'wpcalibrate-membership-access' ),
			__( 'Membership Access', 'wpcalibrate-membership-access' ),
			$capability,
			self::SUBMENU_SLUG,
			[ $this->admin_screens, 'render' ]
		);

		// Store hook suffix for asset enqueuing.
		$this->admin_screens->set_hook_suffix( (string) $hook_suffix );
	}

	/**
	 * Ensure the parent WPCalibrate menu item holds the official brand icon,
	 * regardless of registration order across multiple WPCalibrate plugins.
	 */
	public function ensure_parent_menu_icon(): void {
		global $menu;
		if ( ! is_array( $menu ) ) {
			return;
		}

		$icon_svg = $this->get_menu_icon();
		foreach ( $menu as $k => $item ) {
			if ( isset( $item[2] ) && self::PARENT_SLUG === $item[2] ) {
				$menu[ $k ][6] = $icon_svg;
				break;
			}
		}
	}

	/**
	 * Get scalable SVG data URI for official WPCalibrate dashboard menu icon.
	 *
	 * Uses crisp vector paths for the rounded C frame, teal calibration notch,
	 * and internal WP monogram. Has zero font dependencies and renders reliably
	 * across all screen pixel densities.
	 */
	public function get_menu_icon(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20" fill="none">'
			. '<path d="M 13.6 2.4 L 6.4 2.4 C 4.19 2.4 2.4 4.19 2.4 6.4 L 2.4 13.6 C 2.4 15.81 4.19 17.6 6.4 17.6 L 13.6 17.6 C 15.81 17.6 17.6 15.81 17.6 13.6 L 17.6 12.4 C 17.6 11.96 17.24 11.6 16.8 11.6 L 15.2 11.6 C 14.76 11.6 14.4 11.96 14.4 12.4 L 14.4 13.2 C 14.4 14.08 13.68 14.8 12.8 14.8 L 7.2 14.8 C 6.32 14.8 5.6 14.08 5.6 13.2 L 5.6 6.8 C 5.6 5.92 6.32 5.2 7.2 5.2 L 12.8 5.2 C 13.68 5.2 14.4 5.92 14.4 6.8 L 14.4 7.6 C 14.4 8.04 14.76 8.4 15.2 8.4 L 16.8 8.4 C 17.24 8.4 17.6 8.04 17.6 7.6 L 17.6 6.4 C 17.6 4.19 15.81 2.4 13.6 2.4 Z" fill="#ffffff"/>'
			. '<rect x="15.2" y="9" width="2.4" height="2" rx="0.6" fill="#00a896"/>'
			. '<path d="M 5.8 7.2 L 7.2 7.2 C 7.56 7.2 7.88 7.44 7.98 7.78 L 8.8 10.9 L 9.62 7.78 C 9.72 7.44 10.04 7.2 10.4 7.2 C 10.76 7.2 11.08 7.44 11.18 7.78 L 11.7 9.7 C 11.84 9.1 12.36 8.6 13.1 8.6 L 14.6 8.6 C 15.26 8.6 15.8 9.14 15.8 9.8 L 15.8 10.6 C 15.8 11.26 15.26 11.8 14.6 11.8 L 13.2 11.8 L 13.2 13.6 C 13.2 13.82 13.02 14 12.8 14 L 12 14 C 11.78 14 11.6 13.82 11.6 13.6 L 11.6 10.9 L 10.7 13.5 C 10.6 13.8 10.32 14 10 14 C 9.68 14 9.4 13.8 9.3 13.5 L 8.4 10.9 L 7.5 13.6 C 7.42 13.84 7.2 14 6.94 14 L 6 14 C 5.7 14 5.44 13.78 5.38 13.48 L 4.6 7.7 C 4.56 7.42 4.78 7.2 5.06 7.2 L 5.8 7.2 Z M 13.1 10 L 14.2 10 C 14.32 10 14.4 10.08 14.4 10.2 L 14.4 10.2 C 14.4 10.32 14.32 10.4 14.2 10.4 L 13.1 10.4 C 12.94 10.4 12.8 10.26 12.8 10.1 L 12.8 10.1 C 12.8 10.04 12.84 10 12.9 10 L 13.1 10 Z" fill="#ffffff"/>'
			. '</svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Output global admin styles ensuring WPCalibrate menu icon renders cleanly on all pages.
	 *
	 * Preserves WordPress core inline background-image style without destruction,
	 * while ensuring consistent 20x20 sizing, crisp positioning and standard opacity states.
	 */
	public function render_admin_menu_styles(): void {
		?>
		<style id="wpcal-admin-menu-icon-styles">
			#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image {
				background-size: 20px 20px !important;
				background-position: center center !important;
				background-repeat: no-repeat !important;
				opacity: 0.75;
				transition: opacity 0.15s ease-in-out;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover div.wp-menu-image,
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu div.wp-menu-image,
			#adminmenu .toplevel_page_wpcalibrate.current div.wp-menu-image {
				opacity: 1 !important;
			}
		</style>
		<?php
	}
}
