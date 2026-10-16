<?php
/**
 * Unconfigured License Provider.
 *
 * Safe fallback provider used when no remote commercial licensing provider
 * has been registered. Explicitly rejects fake activations.
 *
 * @package WPCalibrate\MembershipAccess\Licensing
 */

namespace WPCalibrate\MembershipAccess\Licensing;

defined( 'ABSPATH' ) || exit;

class UnconfiguredProvider implements LicenseProviderInterface {

	public function get_id(): string {
		return 'unconfigured';
	}

	public function get_name(): string {
		return __( 'Unconfigured / Standalone', 'wpcalibrate-membership-access' );
	}

	public function is_configured(): bool {
		return false;
	}

	public function activate( string $license_key ): array {
		return [
			'success'    => false,
			'message'    => __( 'Licensing provider not configured. No external license server is registered.', 'wpcalibrate-membership-access' ),
			'status'     => 'unconfigured',
			'expires_at' => null,
		];
	}

	public function deactivate( string $license_key ): array {
		return [
			'success' => true,
			'message' => __( 'License cleared locally.', 'wpcalibrate-membership-access' ),
		];
	}

	public function validate( string $license_key ): array {
		return [
			'valid'      => false,
			'message'    => __( 'Licensing provider not configured.', 'wpcalibrate-membership-access' ),
			'status'     => 'unconfigured',
			'expires_at' => null,
		];
	}
}
