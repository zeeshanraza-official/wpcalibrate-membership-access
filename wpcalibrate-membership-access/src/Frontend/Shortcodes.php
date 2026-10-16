<?php
/**
 * Frontend Shortcodes.
 *
 * Implements [wpcalibrate_members_only] and helper shortcodes using the central AccessManager.
 *
 * @package WPCalibrate\MembershipAccess\Frontend
 */

namespace WPCalibrate\MembershipAccess\Frontend;

use WPCalibrate\MembershipAccess\Access\AccessManager;
use WPCalibrate\MembershipAccess\Access\RuleResolver;
use WPCalibrate\MembershipAccess\Membership\MembershipRepository;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

	public function __construct(
		private AccessManager $access_manager,
		private MembershipRepository $membership_repo
	) {}

	public function register(): void {
		add_shortcode( 'wpcalibrate_members_only', [ $this, 'render_members_only' ] );
		// Alias for convenience
		add_shortcode( 'wpcal_members_only', [ $this, 'render_members_only' ] );
	}

	/**
	 * Shortcode callback for [wpcalibrate_members_only].
	 *
	 * Attributes:
	 * - plan_ids: Comma-separated plan IDs (e.g. "1,2,5")
	 * - plans: Comma-separated plan slugs (e.g. "vip,gold")
	 * - any: "true" to allow any active membership
	 * - logged_in: "true" to allow any logged-in user
	 * - fallback: Text/HTML to display if user is unauthorized
	 *
	 * @param array|string $atts
	 * @param string|null  $content
	 * @return string
	 */
	public function render_members_only( array|string $atts = [], ?string $content = null ): string {
		$atts = shortcode_atts(
			[
				'plan_ids'  => '',
				'plans'     => '',
				'any'       => 'false',
				'logged_in' => 'false',
				'fallback'  => '',
			],
			$atts,
			'wpcalibrate_members_only'
		);

		$user_id = (int) get_current_user_id();

		// Determine rule.
		$rule_type = RuleResolver::RULE_ANY_MEMBER;
		$plan_ids  = [];

		if ( ! empty( $atts['plan_ids'] ) ) {
			$rule_type = RuleResolver::RULE_SPECIFIC_PLANS;
			$plan_ids  = array_values( array_filter( array_map( 'absint', explode( ',', (string) $atts['plan_ids'] ) ) ) );
		} elseif ( 'true' === strtolower( (string) $atts['logged_in'] ) ) {
			$rule_type = RuleResolver::RULE_LOGGED_IN;
		} elseif ( 'true' === strtolower( (string) $atts['any'] ) ) {
			$rule_type = RuleResolver::RULE_ANY_MEMBER;
		}

		$rule = [
			'type'     => $rule_type,
			'plan_ids' => $plan_ids,
		];

		if ( $this->access_manager->user_can_access( $user_id, $rule ) ) {
			return do_shortcode( (string) $content );
		}

		if ( ! empty( $atts['fallback'] ) ) {
			return '<div class="wpcal-shortcode-fallback">' . wp_kses_post( $atts['fallback'] ) . '</div>';
		}

		return '';
	}
}
