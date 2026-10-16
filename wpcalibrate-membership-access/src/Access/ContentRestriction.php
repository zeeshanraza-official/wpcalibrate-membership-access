<?php
/**
 * Frontend Content Restriction Enforcer.
 *
 * Hooks into content rendering, feeds, REST API, archives, and searches to guarantee
 * restricted content is never leaked server-side to unauthorized visitors.
 *
 * @package WPCalibrate\MembershipAccess\Access
 */

namespace WPCalibrate\MembershipAccess\Access;

use WPCalibrate\MembershipAccess\Frontend\RedirectHandler;

defined( 'ABSPATH' ) || exit;

class ContentRestriction {

	public function __construct(
		private AccessManager $access_manager,
		private RedirectHandler $redirect_handler
	) {}

	public function register_hooks(): void {
		// Single post content and excerpt filtering.
		add_filter( 'the_content', [ $this, 'filter_content' ], 20 );
		add_filter( 'the_excerpt', [ $this, 'filter_excerpt' ], 20 );

		// RSS & Atom Feed protection.
		add_filter( 'the_content_feed', [ $this, 'filter_feed_content' ], 20 );
		add_filter( 'the_excerpt_rss', [ $this, 'filter_feed_excerpt' ], 20 );

		// REST API protection across posts, pages, and custom post types.
		add_action( 'rest_api_init', [ $this, 'register_rest_filters' ] );

		// Query manipulation for search and archive hiding where configured.
		add_action( 'pre_get_posts', [ $this, 'filter_queries' ] );
	}

	/**
	 * Intercept full post content for display.
	 */
	public function filter_content( string $content ): string {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $content;
		}

		$user_id = (int) get_current_user_id();
		if ( $this->access_manager->user_can_access( $user_id, $post_id ) ) {
			return $content;
		}

		// Unauthorized visitor: render access restricted notice.
		return $this->render_restricted_notice( $post_id, $user_id );
	}

	/**
	 * Intercept post excerpt.
	 */
	public function filter_excerpt( string $excerpt ): string {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $excerpt;
		}

		$user_id = (int) get_current_user_id();
		if ( $this->access_manager->user_can_access( $user_id, $post_id ) ) {
			return $excerpt;
		}

		$settings   = get_option( 'wpcalibrate_membership_access_settings', [] );
		$visibility = $settings['search_archive_visibility'] ?? 'normal_restrict_content';

		if ( 'hide_completely' === $visibility ) {
			return '';
		}

		if ( 'show_excerpt' === $visibility ) {
			return $excerpt;
		}

		return __( 'Restricted Content: You must be a member to view this item.', 'wpcalibrate-membership-access' );
	}

	/**
	 * Protect RSS feed content.
	 */
	public function filter_feed_content( string $content ): string {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $content;
		}

		// Feed requests are inherently guest / unauthorized.
		if ( ! $this->access_manager->user_can_access( 0, $post_id ) ) {
			return __( 'This content is reserved for members only.', 'wpcalibrate-membership-access' );
		}

		return $content;
	}

	/**
	 * Protect RSS feed excerpt.
	 */
	public function filter_feed_excerpt( string $excerpt ): string {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $excerpt;
		}

		if ( ! $this->access_manager->user_can_access( 0, $post_id ) ) {
			return __( 'Members-only content.', 'wpcalibrate-membership-access' );
		}

		return $excerpt;
	}

	/**
	 * Register REST response filters for all public post types.
	 */
	public function register_rest_filters(): void {
		$post_types = get_post_types( [ 'show_in_rest' => true ] );

		foreach ( $post_types as $post_type ) {
			add_filter(
				"rest_prepare_{$post_type}",
				[ $this, 'filter_rest_response' ],
				10,
				3
			);
		}
	}

	/**
	 * Sanitize REST output so unauthorized callers cannot see protected content.
	 *
	 * @param \WP_REST_Response $response
	 * @param \WP_Post          $post
	 * @param \WP_REST_Request  $request
	 * @return \WP_REST_Response
	 */
	public function filter_rest_response( \WP_REST_Response $response, \WP_Post $post, \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = (int) get_current_user_id();

		if ( ! $this->access_manager->user_can_access( $user_id, $post ) ) {
			$data = $response->get_data();

			if ( isset( $data['content'] ) ) {
				$data['content']['rendered'] = '<p>' . esc_html__( 'Restricted content. Active membership required.', 'wpcalibrate-membership-access' ) . '</p>';
				if ( isset( $data['content']['raw'] ) ) {
					$data['content']['raw'] = '';
				}
			}

			$settings   = get_option( 'wpcalibrate_membership_access_settings', [] );
			$visibility = $settings['search_archive_visibility'] ?? 'normal_restrict_content';

			if ( 'hide_completely' === $visibility && isset( $data['excerpt'] ) ) {
				$data['excerpt']['rendered'] = '';
				if ( isset( $data['excerpt']['raw'] ) ) {
					$data['excerpt']['raw'] = '';
				}
			}

			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * Filter search and archive queries if 'hide_completely' is configured.
	 */
	public function filter_queries( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_search() && ! $query->is_archive() && ! $query->is_home() ) {
			return;
		}

		$settings   = get_option( 'wpcalibrate_membership_access_settings', [] );
		$visibility = $settings['search_archive_visibility'] ?? 'normal_restrict_content';

		if ( 'hide_completely' !== $visibility ) {
			return;
		}

		$user_id = (int) get_current_user_id();

		// Query posts with explicit restrictions and exclude those the user cannot access.
		$restricted_posts = get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => '_wpcal_access_rule_type',
						'value'   => [ 'any_member', 'specific_plans', 'logged_in' ],
						'compare' => 'IN',
					],
				],
				'fields'         => 'ids',
			]
		);

		if ( empty( $restricted_posts ) ) {
			return;
		}

		$excluded_ids = [];
		foreach ( $restricted_posts as $p_id ) {
			if ( ! $this->access_manager->user_can_access( $user_id, (int) $p_id ) ) {
				$excluded_ids[] = (int) $p_id;
			}
		}

		if ( ! empty( $excluded_ids ) ) {
			$current_post__not_in = $query->get( 'post__not_in', [] );
			if ( ! is_array( $current_post__not_in ) ) {
				$current_post__not_in = [];
			}
			$query->set( 'post__not_in', array_merge( $current_post__not_in, $excluded_ids ) );
		}
	}

	/**
	 * Render the restricted content notice template.
	 */
	public function render_restricted_notice( int $post_id, int $user_id ): string {
		$rule     = $this->access_manager->get_rule_resolver()->resolve_for_post( $post_id );
		$settings = get_option( 'wpcalibrate_membership_access_settings', [] );

		$message = ! empty( $rule['custom_message'] )
			? $rule['custom_message']
			: ( $settings['custom_denied_message'] ?? __( 'You must have an active membership to view this content.', 'wpcalibrate-membership-access' ) );

		ob_start();
		$template_path = WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/restricted-content.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="wpcal-restricted-notice"><p>' . wp_kses_post( $message ) . '</p></div>';
		}
		return (string) ob_get_clean();
	}
}
