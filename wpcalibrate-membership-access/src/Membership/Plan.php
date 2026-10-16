<?php
/**
 * Membership Plan Domain Model.
 *
 * @package WPCalibrate\MembershipAccess\Membership
 */

namespace WPCalibrate\MembershipAccess\Membership;

defined( 'ABSPATH' ) || exit;

class Plan {

	public const DURATION_LIFETIME     = 'lifetime';
	public const DURATION_FIXED        = 'fixed';
	public const DURATION_ORDER        = 'order';
	public const DURATION_SUBSCRIPTION = 'subscription';

	public const UNIT_DAYS   = 'days';
	public const UNIT_WEEKS  = 'weeks';
	public const UNIT_MONTHS = 'months';
	public const UNIT_YEARS  = 'years';

	public function __construct(
		private int $id = 0,
		private string $name = '',
		private string $slug = '',
		private string $description = '',
		private string $status = 'active', // 'active' or 'inactive'
		private string $duration_type = self::DURATION_LIFETIME,
		private int $duration_quantity = 1,
		private string $duration_unit = self::UNIT_MONTHS,
		private array $product_ids = [],
		private bool $is_registration_allowed = false,
		private bool $is_default_registration = false,
		private int $priority = 10,
		private string $admin_notes = '',
		private string $created_at = '',
		private string $updated_at = ''
	) {}

	public function get_id(): int {
		return $this->id;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_slug(): string {
		return $this->slug;
	}

	public function get_description(): string {
		return $this->description;
	}

	public function is_active(): bool {
		return 'active' === $this->status;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function get_duration_type(): string {
		return $this->duration_type;
	}

	public function get_duration_quantity(): int {
		return $this->duration_quantity;
	}

	public function get_duration_unit(): string {
		return $this->duration_unit;
	}

	public function is_lifetime(): bool {
		return self::DURATION_LIFETIME === $this->duration_type;
	}

	public function get_product_ids(): array {
		return array_map( 'absint', $this->product_ids );
	}

	public function is_registration_allowed(): bool {
		return $this->is_registration_allowed;
	}

	public function is_default_registration(): bool {
		return $this->is_default_registration;
	}

	public function get_priority(): int {
		return $this->priority;
	}

	public function get_admin_notes(): string {
		return $this->admin_notes;
	}

	public function get_created_at(): string {
		return $this->created_at;
	}

	public function get_updated_at(): string {
		return $this->updated_at;
	}

	/**
	 * Calculate expiration datetime string (Y-m-d H:i:s in WP site timezone)
	 * given a start date timestamp or string.
	 *
	 * Returns null if duration is lifetime.
	 *
	 * @param string|\DateTimeImmutable|null $start_date
	 * @return string|null Expiry in Y-m-d H:i:s or null for lifetime.
	 */
	public function calculate_expiry( string|\DateTimeImmutable|null $start_date = null ): ?string {
		if ( $this->is_lifetime() ) {
			return null;
		}

		$wp_tz = wp_timezone();

		if ( $start_date instanceof \DateTimeImmutable ) {
			$dt = $start_date->setTimezone( $wp_tz );
		} elseif ( is_string( $start_date ) && ! empty( $start_date ) && '0000-00-00 00:00:00' !== $start_date ) {
			try {
				$dt = new \DateTimeImmutable( $start_date, $wp_tz );
			} catch ( \Exception ) {
				$dt = new \DateTimeImmutable( 'now', $wp_tz );
			}
		} else {
			$dt = new \DateTimeImmutable( 'now', $wp_tz );
		}

		if ( self::DURATION_FIXED === $this->duration_type ) {
			$qty  = max( 1, $this->duration_quantity );
			$unit = match ( $this->duration_unit ) {
				self::UNIT_DAYS   => 'days',
				self::UNIT_WEEKS  => 'weeks',
				self::UNIT_MONTHS => 'months',
				self::UNIT_YEARS  => 'years',
				default           => 'months',
			};

			$dt = $dt->modify( "+{$qty} {$unit}" );
			return $dt ? $dt->format( 'Y-m-d H:i:s' ) : null;
		}

		// Order-tied or subscription-tied return null by default unless specified by the order/subscription
		return null;
	}

	/**
	 * Convert model to associative array.
	 */
	public function to_array(): array {
		return [
			'id'                      => $this->id,
			'name'                    => $this->name,
			'slug'                    => $this->slug,
			'description'             => $this->description,
			'status'                  => $this->status,
			'duration_type'           => $this->duration_type,
			'duration_quantity'       => $this->duration_quantity,
			'duration_unit'           => $this->duration_unit,
			'product_ids'             => $this->product_ids,
			'is_registration_allowed' => $this->is_registration_allowed,
			'is_default_registration' => $this->is_default_registration,
			'priority'                => $this->priority,
			'admin_notes'             => $this->admin_notes,
			'created_at'              => $this->created_at,
			'updated_at'              => $this->updated_at,
		];
	}
}
