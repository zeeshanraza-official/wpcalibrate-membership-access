<?php
/**
 * License Provider Interface.
 *
 * @package WPCalibrate\MembershipAccess\Licensing
 */

namespace WPCalibrate\MembershipAccess\Licensing;

defined( 'ABSPATH' ) || exit;

interface LicenseProviderInterface {

	/**
	 * Provider unique identifier.
	 */
	public function get_id(): string;

	/**
	 * Provider display name.
	 */
	public function get_name(): string;

	/**
	 * Whether this provider is configured with necessary API endpoints/secrets.
	 */
	public function is_configured(): bool;

	/**
	 * Activate a license key.
	 *
	 * @param string $license_key
	 * @return array{ success: bool, message: string, status: string, expires_at: ?string }
	 */
	public function activate( string $license_key ): array;

	/**
	 * Deactivate a license key.
	 *
	 * @param string $license_key
	 * @return array{ success: bool, message: string }
	 */
	public function deactivate( string $license_key ): array;

	/**
	 * Validate a license key with remote authority.
	 *
	 * @param string $license_key
	 * @return array{ valid: bool, message: string, status: string, expires_at: ?string }
	 */
	public function validate( string $license_key ): array;
}
