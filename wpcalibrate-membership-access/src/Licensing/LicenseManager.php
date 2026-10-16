<?php
/**
 * License Manager.
 *
 * Coordinates license provider integration, key masking, secure storage,
 * and status queries without fake activation simulations.
 *
 * @package WPCalibrate\MembershipAccess\Licensing
 */

namespace WPCalibrate\MembershipAccess\Licensing;

defined( 'ABSPATH' ) || exit;

class LicenseManager {

	public const OPTION_KEY   = 'wpcalibrate_membership_access_license_key';
	public const OPTION_INFO  = 'wpcalibrate_membership_access_license_info';

	private LicenseProviderInterface $provider;

	public function __construct( ?LicenseProviderInterface $provider = null ) {
		$this->provider = $provider ?? new UnconfiguredProvider();
	}

	public function set_provider( LicenseProviderInterface $provider ): void {
		$this->provider = $provider;
	}

	public function get_provider(): LicenseProviderInterface {
		return $this->provider;
	}

	/**
	 * Retrieve saved license key.
	 */
	public function get_key(): string {
		return (string) get_option( self::OPTION_KEY, '' );
	}

	/**
	 * Retrieve masked key for safe UI display (e.g. ****-****-****-1234).
	 */
	public function get_masked_key(): string {
		$key = trim( $this->get_key() );
		if ( empty( $key ) ) {
			return '';
		}

		$len = strlen( $key );
		if ( $len <= 4 ) {
			return str_repeat( '*', $len );
		}

		$last_four = substr( $key, -4 );
		return str_repeat( '*', max( 0, $len - 4 ) ) . $last_four;
	}

	/**
	 * Save license key and query provider.
	 */
	public function save_key( string $key ): void {
		$sanitized_key = sanitize_text_field( $key );
		update_option( self::OPTION_KEY, $sanitized_key );

		if ( ! empty( $sanitized_key ) && $this->provider->is_configured() ) {
			$result = $this->provider->activate( $sanitized_key );
			update_option( self::OPTION_INFO, $result );
		} else {
			delete_option( self::OPTION_INFO );
		}
	}

	/**
	 * Get current license information.
	 *
	 * @return array{ masked_key: string, status: string, message: string }
	 */
	public function get_license_info(): array {
		$masked = $this->get_masked_key();
		$info   = get_option( self::OPTION_INFO, [] );

		if ( ! $this->provider->is_configured() ) {
			return [
				'masked_key' => $masked,
				'status'     => 'unconfigured',
				'message'    => __( 'Licensing provider not configured. Plugin operates normally in standalone mode.', 'wpcalibrate-membership-access' ),
			];
		}

		return [
			'masked_key' => $masked,
			'status'     => $info['status'] ?? 'inactive',
			'message'    => $info['message'] ?? '',
		];
	}
}
