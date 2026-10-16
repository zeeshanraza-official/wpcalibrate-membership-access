<?php
/**
 * Gutenberg Protected Content Block.
 *
 * Provides server-side rendered container block ensuring unauthorized visitors
 * never receive protected inner block HTML in responses.
 *
 * @package WPCalibrate\MembershipAccess\Blocks
 */

namespace WPCalibrate\MembershipAccess\Blocks;

use WPCalibrate\MembershipAccess\Access\AccessManager;
use WPCalibrate\MembershipAccess\Access\RuleResolver;

defined( 'ABSPATH' ) || exit;

class ProtectedContentBlock {

	public const BLOCK_NAME = 'wpcalibrate/protected-content';

	public function __construct( private AccessManager $access_manager ) {}

	public function register(): void {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	public function register_block(): void {
		// Register block script if running with block editor asset.
		wp_register_script(
			'wpcalibrate-protected-content-block',
			WPCALIBRATE_MEMBERSHIP_ACCESS_URL . 'assets/js/blocks/protected-content.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
			WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION,
			true
		);

		register_block_type(
			self::BLOCK_NAME,
			[
				'editor_script'   => 'wpcalibrate-protected-content-block',
				'render_callback' => [ $this, 'render_block' ],
				'attributes'      => [
					'allowAnyMember'  => [
						'type'    => 'boolean',
						'default' => true,
					],
					'allowLoggedIn'   => [
						'type'    => 'boolean',
						'default' => false,
					],
					'allowedPlans'    => [
						'type'    => 'array',
						'default' => [],
					],
					'fallbackMessage' => [
						'type'    => 'string',
						'default' => '',
					],
				],
			]
		);
	}

	/**
	 * Server-side render callback for the block.
	 *
	 * @param array  $attributes
	 * @param string $content Inner block HTML.
	 * @return string
	 */
	public function render_block( array $attributes, string $content ): string {
		$user_id = (int) get_current_user_id();

		// Construct rule from attributes.
		$rule_type = RuleResolver::RULE_ANY_MEMBER;
		$plan_ids  = [];

		if ( ! empty( $attributes['allowedPlans'] ) && is_array( $attributes['allowedPlans'] ) ) {
			$rule_type = RuleResolver::RULE_SPECIFIC_PLANS;
			$plan_ids  = array_values( array_filter( array_map( 'absint', $attributes['allowedPlans'] ) ) );
		} elseif ( ! empty( $attributes['allowLoggedIn'] ) ) {
			$rule_type = RuleResolver::RULE_LOGGED_IN;
		} elseif ( ! empty( $attributes['allowAnyMember'] ) ) {
			$rule_type = RuleResolver::RULE_ANY_MEMBER;
		}

		$rule = [
			'type'     => $rule_type,
			'plan_ids' => $plan_ids,
		];

		// Server-side authorization check.
		if ( $this->access_manager->user_can_access( $user_id, $rule ) ) {
			return '<div class="wpcal-protected-block-wrapper">' . $content . '</div>';
		}

		// Unauthorized: Do NOT return $content under any circumstances.
		if ( ! empty( $attributes['fallbackMessage'] ) ) {
			return '<div class="wpcal-protected-block-fallback"><p>' . esc_html( $attributes['fallbackMessage'] ) . '</p></div>';
		}

		return '';
	}
}
