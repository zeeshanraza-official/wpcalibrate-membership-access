<?php
/**
 * Membership Plan Repository.
 *
 * Implements WordPress-native post/meta storage for membership plans using
 * dedicated non-public custom post type `wpcal_member_plan`.
 *
 * @package WPCalibrate\MembershipAccess\Membership
 */

namespace WPCalibrate\MembershipAccess\Membership;

defined( 'ABSPATH' ) || exit;

class PlanRepository {

	public const POST_TYPE = 'wpcal_member_plan';

	/**
	 * In-memory cache for resolved plans.
	 *
	 * @var array<int, Plan>
	 */
	private array $cache = [];

	/**
	 * Register the non-public custom post type for plans.
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'          => __( 'Membership Plans', 'wpcalibrate-membership-access' ),
					'singular_name' => __( 'Membership Plan', 'wpcalibrate-membership-access' ),
				],
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'exclude_from_search' => true,
				'supports'            => [ 'title', 'editor' ],
				'can_export'          => true,
				'rewrite'             => false,
				'query_var'           => false,
			]
		);
	}

	/**
	 * Get a plan by its ID.
	 */
	public function get( int $id ): ?Plan {
		if ( $id <= 0 ) {
			return null;
		}

		if ( isset( $this->cache[ $id ] ) ) {
			return $this->cache[ $id ];
		}

		$post = get_post( $id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		$plan = $this->hydrate_from_post( $post );
		$this->cache[ $id ] = $plan;
		return $plan;
	}

	/**
	 * Get a plan by its slug.
	 */
	public function get_by_slug( string $slug ): ?Plan {
		$posts = get_posts(
			[
				'post_type'      => self::POST_TYPE,
				'name'           => sanitize_title( $slug ),
				'post_status'    => 'any',
				'posts_per_page' => 1,
			]
		);

		if ( empty( $posts ) ) {
			return null;
		}

		return $this->get( $posts[0]->ID );
	}

	/**
	 * Get all plans.
	 *
	 * @param array $args Query modifications.
	 * @return Plan[]
	 */
	public function get_all( array $args = [] ): array {
		$query_args = array_merge(
			[
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			],
			$args
		);

		$posts = get_posts( $query_args );
		$plans = [];

		foreach ( $posts as $post ) {
			$plan = $this->hydrate_from_post( $post );
			$this->cache[ $plan->get_id() ] = $plan;
			$plans[]                        = $plan;
		}

		return $plans;
	}

	/**
	 * Get only active plans.
	 *
	 * @return Plan[]
	 */
	public function get_active(): array {
		$all = $this->get_all();
		return array_values(
			array_filter(
				$all,
				static fn( Plan $p ) => $p->is_active()
			)
		);
	}

	/**
	 * Get plans associated with a WooCommerce product ID.
	 *
	 * @param int $product_id
	 * @return Plan[]
	 */
	public function get_for_product( int $product_id ): array {
		if ( $product_id <= 0 ) {
			return [];
		}

		$active_plans = $this->get_active();
		$matched      = [];

		foreach ( $active_plans as $plan ) {
			if ( in_array( $product_id, $plan->get_product_ids(), true ) ) {
				$matched[] = $plan;
			}
		}

		return $matched;
	}

	/**
	 * Get plans available during user registration.
	 *
	 * @return Plan[]
	 */
	public function get_registration_plans(): array {
		$active = $this->get_active();
		return array_values(
			array_filter(
				$active,
				static fn( Plan $p ) => $p->is_registration_allowed()
			)
		);
	}

	/**
	 * Get the default plan to assign upon user registration (if configured).
	 */
	public function get_default_registration_plan(): ?Plan {
		$active = $this->get_active();
		foreach ( $active as $plan ) {
			if ( $plan->is_default_registration() ) {
				return $plan;
			}
		}
		return null;
	}

	/**
	 * Save or update a Plan.
	 *
	 * @param Plan $plan
	 * @return int Created/Updated Post ID or 0 on failure.
	 */
	public function save( Plan $plan ): int {
		$id        = $plan->get_id();
		$post_data = [
			'post_title'   => $plan->get_name(),
			'post_name'    => $plan->get_slug() ?: sanitize_title( $plan->get_name() ),
			'post_content' => $plan->get_description(),
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish',
			'menu_order'   => $plan->get_priority(),
		];

		if ( $id > 0 ) {
			$post_data['ID'] = $id;
			$saved_id        = wp_update_post( $post_data, true );
		} else {
			$saved_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $saved_id ) || ! is_int( $saved_id ) || $saved_id <= 0 ) {
			return 0;
		}

		// Update Post Meta.
		update_post_meta( $saved_id, '_wpcal_plan_status', $plan->get_status() );
		update_post_meta( $saved_id, '_wpcal_duration_type', $plan->get_duration_type() );
		update_post_meta( $saved_id, '_wpcal_duration_quantity', $plan->get_duration_quantity() );
		update_post_meta( $saved_id, '_wpcal_duration_unit', $plan->get_duration_unit() );
		update_post_meta( $saved_id, '_wpcal_product_ids', $plan->get_product_ids() );
		update_post_meta( $saved_id, '_wpcal_is_registration_allowed', $plan->is_registration_allowed() ? 'yes' : 'no' );
		update_post_meta( $saved_id, '_wpcal_is_default_registration', $plan->is_default_registration() ? 'yes' : 'no' );
		update_post_meta( $saved_id, '_wpcal_priority', $plan->get_priority() );
		update_post_meta( $saved_id, '_wpcal_admin_notes', $plan->get_admin_notes() );

		// Clear cache.
		unset( $this->cache[ $saved_id ] );

		return $saved_id;
	}

	/**
	 * Delete a plan.
	 */
	public function delete( int $id ): bool {
		if ( $id <= 0 ) {
			return false;
		}

		unset( $this->cache[ $id ] );
		$result = wp_delete_post( $id, true );
		return false !== $result;
	}

	/**
	 * Convert WP_Post into Plan domain model.
	 */
	private function hydrate_from_post( \WP_Post $post ): Plan {
		$id = $post->ID;

		$status             = get_post_meta( $id, '_wpcal_plan_status', true ) ?: 'active';
		$duration_type      = get_post_meta( $id, '_wpcal_duration_type', true ) ?: Plan::DURATION_LIFETIME;
		$duration_quantity  = (int) ( get_post_meta( $id, '_wpcal_duration_quantity', true ) ?: 1 );
		$duration_unit      = get_post_meta( $id, '_wpcal_duration_unit', true ) ?: Plan::UNIT_MONTHS;
		$product_ids        = (array) get_post_meta( $id, '_wpcal_product_ids', true );
		$reg_allowed_val    = get_post_meta( $id, '_wpcal_is_registration_allowed', true );
		$is_reg_allowed     = 'yes' === $reg_allowed_val;
		$default_reg_val    = get_post_meta( $id, '_wpcal_is_default_registration', true );
		$is_default_reg     = 'yes' === $default_reg_val;
		$priority           = (int) ( get_post_meta( $id, '_wpcal_priority', true ) ?: $post->menu_order ?: 10 );
		$admin_notes        = (string) get_post_meta( $id, '_wpcal_admin_notes', true );

		return new Plan(
			$id,
			$post->post_title,
			$post->post_name,
			$post->post_content,
			$status,
			$duration_type,
			$duration_quantity,
			$duration_unit,
			$product_ids,
			$is_reg_allowed,
			$is_default_reg,
			$priority,
			$admin_notes,
			$post->post_date,
			$post->post_modified
		);
	}
}
