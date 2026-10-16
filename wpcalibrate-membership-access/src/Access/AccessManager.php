<?php
/**
 * Central Access Management Engine.
 *
 * Evaluates all authorization decisions for posts, categories, products,
 * shortcodes, and blocks with deterministic rule evaluation and in-memory caching.
 *
 * @package WPCalibrate\MembershipAccess\Access
 */

namespace WPCalibrate\MembershipAccess\Access;

use WPCalibrate\MembershipAccess\Membership\Membership;
use WPCalibrate\MembershipAccess\Membership\MembershipRepository;
use WPCalibrate\MembershipAccess\Security\Capabilities;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class AccessManager {

	/**
	 * In-memory decision cache: [ "user_id:resource_id" => bool ]
	 *
	 * @var array<string, bool>
	 */
	private array $decision_cache = [];

	public function __construct(
		private MembershipRepository $membership_repo,
		private RuleResolver $rule_resolver
	) {}

	/**
	 * Primary access authorization method.
	 *
	 * @param int                $user_id User ID to test (0 for guest).
	 * @param int|\WP_Post|array $resource Post ID, WP_Post object, or explicit rule array.
	 * @return bool True if authorized, false if denied.
	 */
	public function user_can_access( int $user_id, int|\WP_Post|array $resource ): bool {
		// Determine cache key.
		$cache_key = null;
		if ( is_int( $resource ) || $resource instanceof \WP_Post ) {
			$post_id   = $resource instanceof \WP_Post ? $resource->ID : $resource;
			$cache_key = "{$user_id}:post_{$post_id}";
			if ( isset( $this->decision_cache[ $cache_key ] ) ) {
				return $this->decision_cache[ $cache_key ];
			}
		}

		// 1. Privileged users (Admins) can preview protected content.
		if ( $user_id > 0 && user_can( $user_id, Capabilities::CAP_MANAGE_MEMBERSHIPS ) ) {
			/**
			 * Filter whether privileged admin can preview restricted content.
			 *
			 * @param bool $allow_preview Default true.
			 * @param int $user_id
			 * @param mixed $resource
			 */
			$admin_preview = apply_filters(
				'wpcalibrate_membership_access_admin_preview',
				true,
				$user_id,
				$resource
			);
			if ( $admin_preview ) {
				if ( $cache_key ) {
					$this->decision_cache[ $cache_key ] = true;
				}
				return true;
			}
		}

		// 2. Resolve effective rule.
		$rule = is_array( $resource ) ? $resource : $this->rule_resolver->resolve_for_post( $resource );

		$decision = $this->evaluate_rule( $user_id, $rule );

		/**
		 * Filter the final access authorization decision.
		 *
		 * @param bool $decision
		 * @param int $user_id
		 * @param array $rule
		 * @param mixed $resource
		 */
		$filtered_decision = (bool) apply_filters(
			'wpcalibrate_membership_access_user_can_access',
			$decision,
			$user_id,
			$rule,
			$resource
		);

		if ( $cache_key ) {
			$this->decision_cache[ $cache_key ] = $filtered_decision;
		}

		return $filtered_decision;
	}

	/**
	 * Evaluate rule array for a given user.
	 *
	 * @param int   $user_id
	 * @param array $rule
	 * @return bool
	 */
	public function evaluate_rule( int $user_id, array $rule ): bool {
		$type = $rule['type'] ?? RuleResolver::RULE_EVERYONE;

		switch ( $type ) {
			case RuleResolver::RULE_EVERYONE:
			case RuleResolver::RULE_DEFAULT:
				return true;

			case RuleResolver::RULE_LOGGED_IN:
				return $user_id > 0;

			case RuleResolver::RULE_NON_MEMBERS:
				if ( 0 === $user_id ) {
					return true;
				}
				$active = $this->membership_repo->get_for_user( $user_id, true );
				return empty( $active );

			case RuleResolver::RULE_ANY_MEMBER:
				if ( 0 === $user_id ) {
					return false;
				}
				$active = $this->membership_repo->get_for_user( $user_id, true );
				return ! empty( $active );

			case RuleResolver::RULE_SPECIFIC_PLANS:
				if ( 0 === $user_id ) {
					return false;
				}
				$required_plans = $rule['plan_ids'] ?? [];
				if ( empty( $required_plans ) ) {
					// Fallback to any member if no specific plan selected.
					$active = $this->membership_repo->get_for_user( $user_id, true );
					return ! empty( $active );
				}

				foreach ( $required_plans as $plan_id ) {
					if ( $this->membership_repo->user_has_plan( $user_id, (int) $plan_id ) ) {
						return true;
					}
				}
				return false;

			default:
				return true;
		}
	}

	/**
	 * Helper: Check if a user holds any active membership.
	 */
	public function user_has_active_membership( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		$active = $this->membership_repo->get_for_user( $user_id, true );
		return ! empty( $active );
	}

	/**
	 * Helper: Check if a user holds an active membership for a specific plan.
	 */
	public function user_has_plan( int $user_id, int $plan_id ): bool {
		if ( $user_id <= 0 || $plan_id <= 0 ) {
			return false;
		}
		return $this->membership_repo->user_has_plan( $user_id, $plan_id );
	}

	/**
	 * Get all active memberships for a user.
	 *
	 * @param int $user_id
	 * @return Membership[]
	 */
	public function get_user_active_memberships( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return [];
		}
		return $this->membership_repo->get_for_user( $user_id, true );
	}

	/**
	 * Clear decision cache (useful in tests or when membership state alters).
	 */
	public function clear_cache(): void {
		$this->decision_cache = [];
	}

	public function get_rule_resolver(): RuleResolver {
		return $this->rule_resolver;
	}
}
