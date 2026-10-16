<?php
/**
 * Frontend Redirect Handler.
 *
 * Implements safe, loop-protected redirection for unauthorized access requests.
 *
 * @package WPCalibrate\MembershipAccess\Frontend
 */

namespace WPCalibrate\MembershipAccess\Frontend;

use WPCalibrate\MembershipAccess\Access\AccessManager;

defined( 'ABSPATH' ) || exit;

class RedirectHandler {

	public function __construct( private AccessManager $access_manager ) {}

	public function register_hooks(): void {
		add_action( 'template_redirect', [ $this, 'maybe_redirect' ] );
	}

	/**
	 * Perform safe redirect on template_redirect if the visitor lacks access
	 * and redirect behavior is configured.
	 */
	public function maybe_redirect(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$user_id = (int) get_current_user_id();

		// Check if user is authorized.
		if ( $this->access_manager->user_can_access( $user_id, $post ) ) {
			return;
		}

		$rule     = $this->access_manager->get_rule_resolver()->resolve_for_post( $post );
		$behavior = $rule['behavior'] ?? 'default';

		// If post rule says default, check global setting.
		if ( 'default' === $behavior ) {
			$settings = get_option( 'wpcalibrate_membership_access_settings', [] );
			$behavior = $settings['access_denied_behavior'] ?? 'message';
		}

		// If behavior is to display message, do not redirect.
		if ( 'message' === $behavior ) {
			return;
		}

		$current_url = $this->get_current_url();
		$target_url  = $this->determine_redirect_url( $behavior, $rule, $current_url );

		if ( empty( $target_url ) ) {
			return;
		}

		// Prevent redirect loops!
		if ( $this->is_same_url( $current_url, $target_url ) ) {
			return;
		}

		/**
		 * Filter redirect URL before execution.
		 *
		 * @param string $target_url
		 * @param \WP_Post $post
		 * @param int $user_id
		 */
		$target_url = apply_filters( 'wpcalibrate_membership_access_redirect_url', $target_url, $post, $user_id );

		wp_safe_redirect( $target_url );
		exit;
	}

	/**
	 * Determine the destination URL for redirection.
	 */
	public function determine_redirect_url( string $behavior, array $rule, string $current_url ): string {
		$settings = get_option( 'wpcalibrate_membership_access_settings', [] );

		switch ( $behavior ) {
			case 'login_redirect':
			case 'login':
				return wp_login_url( $current_url );

			case 'registration_redirect':
			case 'registration':
				return wp_registration_url();

			case 'custom_page':
			case 'custom_url':
				$url = ! empty( $rule['redirect_url'] ) ? $rule['redirect_url'] : ( $settings['redirect_url'] ?? '' );
				return ! empty( $url ) ? esc_url_raw( $url ) : wp_login_url( $current_url );

			case 'plans_page':
				$plans_url = $settings['plans_page_url'] ?? '';
				return ! empty( $plans_url ) ? esc_url_raw( $plans_url ) : home_url();

			default:
				return '';
		}
	}

	/**
	 * Get current requested URL.
	 */
	private function get_current_url(): string {
		global $wp;
		return home_url( add_query_arg( [], $wp->request ?? '' ) );
	}

	/**
	 * Compare URLs safely ignoring scheme/trailing slash differences.
	 */
	private function is_same_url( string $url1, string $url2 ): bool {
		$p1 = wp_parse_url( rtrim( $url1, '/' ) );
		$p2 = wp_parse_url( rtrim( $url2, '/' ) );

		if ( ! $p1 || ! $p2 ) {
			return false;
		}

		return ( ( $p1['host'] ?? '' ) === ( $p2['host'] ?? '' ) ) &&
			   ( ( $p1['path'] ?? '' ) === ( $p2['path'] ?? '' ) );
	}
}
