<?php
/**
 * Member Account Dashboard Component.
 *
 * Implements the front-end member account dashboard shortcode [wpcalibrate_member_account].
 *
 * @package WPCalibrate\MembershipAccess\Frontend
 */

namespace WPCalibrate\MembershipAccess\Frontend;

use WPCalibrate\MembershipAccess\Membership\MembershipRepository;
use WPCalibrate\MembershipAccess\Membership\Plan;
use WPCalibrate\MembershipAccess\Membership\PlanRepository;

defined( 'ABSPATH' ) || exit;

class MemberDashboard {

	public function __construct(
		private MembershipRepository $membership_repo,
		private PlanRepository $plan_repo
	) {}

	public function register(): void {
		add_shortcode( 'wpcalibrate_member_account', [ $this, 'render_shortcode' ] );
		add_shortcode( 'wpcal_member_dashboard', [ $this, 'render_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
	}

	public function enqueue_frontend_assets(): void {
		wp_register_style(
			'wpcalibrate-membership-access-frontend',
			WPCALIBRATE_MEMBERSHIP_ACCESS_URL . 'assets/css/frontend.css',
			[],
			WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION
		);
	}

	/**
	 * Shortcode callback for member account dashboard.
	 */
	public function render_shortcode(): string {
		wp_enqueue_style( 'wpcalibrate-membership-access-frontend' );

		$user_id = (int) get_current_user_id();
		return $this->render_dashboard_html( $user_id );
	}

	/**
	 * Render member dashboard markup for a specified user ID.
	 *
	 * @param int $user_id
	 * @return string
	 */
	public function render_dashboard_html( int $user_id ): string {
		$memberships = [];
		$plans_by_id = [];

		if ( $user_id > 0 ) {
			$memberships = $this->membership_repo->get_for_user( $user_id );
			$plans       = $this->plan_repo->get_all();
			foreach ( $plans as $p ) {
				$plans_by_id[ $p->get_id() ] = $p;
			}
		}

		ob_start();
		$template_path = WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/account/dashboard.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
		return (string) ob_get_clean();
	}
}
