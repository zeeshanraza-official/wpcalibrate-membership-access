<?php
/**
 * Membership Access Rule Resolver.
 *
 * Implements deterministic rule resolution with documented precedence:
 * 1. Explicit resource restriction (post/page/CPT meta)
 * 2. Taxonomy term restriction (term meta)
 * 3. Global settings defaults
 *
 * @package WPCalibrate\MembershipAccess\Access
 */

namespace WPCalibrate\MembershipAccess\Access;

use WPCalibrate\MembershipAccess\Membership\PlanRepository;

defined( 'ABSPATH' ) || exit;

class RuleResolver {

	public const RULE_EVERYONE       = 'everyone';
	public const RULE_LOGGED_IN      = 'logged_in';
	public const RULE_ANY_MEMBER     = 'any_member';
	public const RULE_SPECIFIC_PLANS = 'specific_plans';
	public const RULE_NON_MEMBERS    = 'non_members';
	public const RULE_DEFAULT        = 'default';

	public function __construct( private PlanRepository $plan_repo ) {}

	/**
	 * Resolve the effective rule for a given post or post ID.
	 *
	 * @param int|\WP_Post $resource Post ID or WP_Post object.
	 * @return array{
	 *     type: string,
	 *     plan_ids: int[],
	 *     behavior: string,
	 *     custom_message: string,
	 *     redirect_url: string,
	 *     source: string
	 * }
	 */
	public function resolve_for_post( int|\WP_Post $resource ): array {
		$post_id = $resource instanceof \WP_Post ? $resource->ID : (int) $resource;
		if ( $post_id <= 0 ) {
			return $this->get_default_rule();
		}

		// 1. Check explicit post-level restriction.
		$post_rule = $this->get_post_rule( $post_id );
		if ( self::RULE_DEFAULT !== $post_rule['type'] ) {
			$post_rule['source'] = 'post';
			return $post_rule;
		}

		// 2. Check taxonomy-level restrictions on post terms.
		$term_rule = $this->get_terms_rule_for_post( $post_id );
		if ( null !== $term_rule && self::RULE_DEFAULT !== $term_rule['type'] ) {
			$term_rule['source'] = 'taxonomy';
			// Fallback behavior options to post if set, else global.
			if ( empty( $term_rule['behavior'] ) || 'default' === $term_rule['behavior'] ) {
				$term_rule['behavior']       = $post_rule['behavior'] ?? 'default';
				$term_rule['custom_message'] = $post_rule['custom_message'] ?? '';
				$term_rule['redirect_url']   = $post_rule['redirect_url'] ?? '';
			}
			return $term_rule;
		}

		// 3. Fallback to global defaults.
		$default_rule           = $this->get_default_rule();
		$default_rule['source'] = 'global';
		return $default_rule;
	}

	/**
	 * Get post-level access rule meta.
	 */
	public function get_post_rule( int $post_id ): array {
		$type           = get_post_meta( $post_id, '_wpcal_access_rule_type', true ) ?: self::RULE_DEFAULT;
		$plan_ids       = get_post_meta( $post_id, '_wpcal_access_plans', true );
		$behavior       = get_post_meta( $post_id, '_wpcal_denied_behavior', true ) ?: 'default';
		$custom_message = (string) get_post_meta( $post_id, '_wpcal_custom_message', true );
		$redirect_url   = (string) get_post_meta( $post_id, '_wpcal_redirect_url', true );

		if ( ! is_array( $plan_ids ) ) {
			$plan_ids = ! empty( $plan_ids ) ? [ (int) $plan_ids ] : [];
		}

		return [
			'type'           => sanitize_key( $type ),
			'plan_ids'       => array_values( array_filter( array_map( 'absint', $plan_ids ) ) ),
			'behavior'       => sanitize_key( $behavior ),
			'custom_message' => $custom_message,
			'redirect_url'   => esc_url_raw( $redirect_url ),
			'source'         => 'post',
		];
	}

	/**
	 * Get term-level access rule for a taxonomy term.
	 */
	public function get_term_rule( int $term_id ): array {
		$type     = get_term_meta( $term_id, '_wpcal_access_rule_type', true ) ?: self::RULE_DEFAULT;
		$plan_ids = get_term_meta( $term_id, '_wpcal_access_plans', true );

		if ( ! is_array( $plan_ids ) ) {
			$plan_ids = ! empty( $plan_ids ) ? [ (int) $plan_ids ] : [];
		}

		return [
			'type'           => sanitize_key( $type ),
			'plan_ids'       => array_values( array_filter( array_map( 'absint', $plan_ids ) ) ),
			'behavior'       => 'default',
			'custom_message' => '',
			'redirect_url'   => '',
			'source'         => 'term',
		];
	}

	/**
	 * Inspect terms associated with post across all public taxonomies.
	 */
	private function get_terms_rule_for_post( int $post_id ): ?array {
		$taxonomies = get_object_taxonomies( get_post_type( $post_id ) );
		if ( empty( $taxonomies ) ) {
			return null;
		}

		$terms = wp_get_object_terms( $post_id, $taxonomies, [ 'fields' => 'ids' ] );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		// If multiple terms have rules, collect and determine the most restrictive.
		foreach ( $terms as $term_id ) {
			$rule = $this->get_term_rule( (int) $term_id );
			if ( self::RULE_DEFAULT !== $rule['type'] ) {
				// Return the first matching configured term rule.
				return $rule;
			}
		}

		return null;
	}

	/**
	 * Default rule when no restriction applies (accessible by everyone).
	 */
	public function get_default_rule(): array {
		return [
			'type'           => self::RULE_EVERYONE,
			'plan_ids'       => [],
			'behavior'       => 'default',
			'custom_message' => '',
			'redirect_url'   => '',
			'source'         => 'default',
		];
	}
}
