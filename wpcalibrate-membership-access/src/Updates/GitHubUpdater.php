<?php
/**
 * GitHub-based Update Checker for WPCalibrate Membership Access.
 *
 * Enables administrators to receive update notifications and perform one-click updates
 * directly from the WordPress dashboard via public GitHub releases.
 *
 * @package WPCalibrate\MembershipAccess\Updates
 */

declare(strict_types=1);

namespace WPCalibrate\MembershipAccess\Updates;

defined( 'ABSPATH' ) || exit;

class GitHubUpdater {

	private const GITHUB_REPO    = 'zeeshanraza-official/wpcalibrate-membership-access';
	private const TRANSIENT_KEY  = 'wpcal_membership_access_github_release';
	private const CACHE_HOURS    = 6;

	/**
	 * Register update hooks.
	 */
	public function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_post_install', [ $this, 'post_install' ], 10, 3 );
	}

	/**
	 * Check GitHub releases for a newer version of the plugin.
	 *
	 * @param object|bool $transient Transient object.
	 * @return object|bool
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) || ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( ! $release || empty( $release['tag_name'] ) ) {
			return $transient;
		}

		$remote_version  = ltrim( (string) $release['tag_name'], 'v' );
		$current_version = defined( 'WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION' ) ? WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION : '1.0.0';

		if ( version_compare( $remote_version, $current_version, '>' ) ) {
			$plugin_file = defined( 'WPCALIBRATE_MEMBERSHIP_ACCESS_BASENAME' )
				? WPCALIBRATE_MEMBERSHIP_ACCESS_BASENAME
				: 'wpcalibrate-membership-access/wpcalibrate-membership-access.php';

			$package_url = $this->get_asset_zip_url( $release );

			$item = (object) [
				'id'            => 'wpcalibrate-membership-access',
				'slug'          => 'wpcalibrate-membership-access',
				'plugin'        => $plugin_file,
				'new_version'   => $remote_version,
				'url'           => $release['html_url'] ?? 'https://github.com/' . self::GITHUB_REPO,
				'package'       => $package_url,
				'requires'      => '6.5',
				'requires_php'  => '8.2',
				'tested'        => '7.1.3',
				'icons'         => [
					'1x' => defined( 'WPCALIBRATE_MEMBERSHIP_ACCESS_URL' ) ? WPCALIBRATE_MEMBERSHIP_ACCESS_URL . 'assets/branding/icon-dark.png' : '',
					'2x' => defined( 'WPCALIBRATE_MEMBERSHIP_ACCESS_URL' ) ? WPCALIBRATE_MEMBERSHIP_ACCESS_URL . 'assets/branding/icon-dark.png' : '',
				],
			];

			$transient->response[ $plugin_file ] = $item;
			unset( $transient->no_update[ $plugin_file ] );
		}

		return $transient;
	}

	/**
	 * Provide plugin details for the modal view in WordPress dashboard.
	 *
	 * @param false|object|array $result Result.
	 * @param string             $action Action name.
	 * @param object             $args   Arguments.
	 * @return false|object
	 */
	public function plugin_info( $result, string $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || 'wpcalibrate-membership-access' !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		$remote_version = ltrim( (string) ( $release['tag_name'] ?? '1.0.0' ), 'v' );
		$body_markdown  = (string) ( $release['body'] ?? '' );

		return (object) [
			'name'          => 'WPCalibrate Membership Access',
			'slug'          => 'wpcalibrate-membership-access',
			'version'       => $remote_version,
			'author'        => '<a href="https://wpcalibrate.com">WPCalibrate</a>',
			'homepage'      => 'https://github.com/' . self::GITHUB_REPO,
			'requires'      => '6.5',
			'requires_php'  => '8.2',
			'tested'        => '7.1.3',
			'last_updated'  => $release['published_at'] ?? '',
			'download_link' => $this->get_asset_zip_url( $release ),
			'sections'      => [
				'description' => esc_html__( 'Enterprise membership management, deterministic content access control, user lifecycle management, and WooCommerce monetization for WordPress.', 'wpcalibrate-membership-access' ),
				'changelog'   => nl2br( esc_html( $body_markdown ) ),
			],
			'banners'       => [
				'low' => defined( 'WPCALIBRATE_MEMBERSHIP_ACCESS_URL' ) ? WPCALIBRATE_MEMBERSHIP_ACCESS_URL . 'assets/branding/icon-dark.png' : '',
			],
		];
	}

	/**
	 * Ensure the extracted plugin directory matches the standard plugin slug.
	 *
	 * @param bool  $true       True.
	 * @param array $hook_extra Extra hook parameters.
	 * @param array $result     Installation result array.
	 * @return array
	 */
	public function post_install( $true, array $hook_extra, array $result ): array {
		global $wp_filesystem;

		$plugin_slug = 'wpcalibrate-membership-access';
		$destination = WP_PLUGIN_DIR . '/' . $plugin_slug;

		if ( ! empty( $result['destination'] ) && $result['destination'] !== $destination && isset( $hook_extra['plugin'] ) && str_contains( (string) $hook_extra['plugin'], $plugin_slug ) ) {
			if ( $wp_filesystem ) {
				$wp_filesystem->move( $result['destination'], $destination );
				$result['destination'] = $destination;
			}
		}

		return $result;
	}

	/**
	 * Fetch the latest release payload from GitHub.
	 *
	 * @return array<string, mixed>|null
	 */
	private function get_latest_release(): ?array {
		$cached = get_site_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) && ! empty( $cached['tag_name'] ) ) {
			return $cached;
		}

		$url  = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';
		$args = [
			'headers' => [
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . ( $GLOBALS['wp_version'] ?? '6.5' ) . '; WPCalibrate-Membership-Access',
			],
			'timeout' => 10,
		];

		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			return null;
		}

		set_site_transient( self::TRANSIENT_KEY, $data, self::CACHE_HOURS * HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Extract ZIP package URL from release assets or fallback to zipball.
	 *
	 * @param array<string, mixed> $release Release payload.
	 * @return string
	 */
	private function get_asset_zip_url( array $release ): string {
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( ! empty( $asset['browser_download_url'] ) && str_ends_with( strtolower( (string) $asset['browser_download_url'] ), '.zip' ) ) {
					return (string) $asset['browser_download_url'];
				}
			}
		}

		return (string) ( $release['zipball_url'] ?? '' );
	}
}
