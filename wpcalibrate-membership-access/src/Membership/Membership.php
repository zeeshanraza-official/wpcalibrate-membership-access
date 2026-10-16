<?php
/**
 * User Membership Domain Model.
 *
 * @package WPCalibrate\MembershipAccess\Membership
 */

namespace WPCalibrate\MembershipAccess\Membership;

defined( 'ABSPATH' ) || exit;

class Membership {

	public const STATUS_PENDING   = 'pending';
	public const STATUS_ACTIVE    = 'active';
	public const STATUS_EXPIRED   = 'expired';
	public const STATUS_CANCELLED = 'cancelled';
	public const STATUS_SUSPENDED = 'suspended';

	public const SOURCE_ADMIN        = 'admin';
	public const SOURCE_REGISTRATION = 'registration';
	public const SOURCE_ORDER        = 'order';
	public const SOURCE_SUBSCRIPTION = 'subscription';
	public const SOURCE_API          = 'api';

	public function __construct(
		private int $id = 0,
		private int $user_id = 0,
		private int $plan_id = 0,
		private string $status = self::STATUS_PENDING,
		private string $start_date = '',
		private ?string $expires_at = null,
		private string $source = self::SOURCE_ADMIN,
		private ?int $order_id = null,
		private ?int $subscription_id = null,
		private string $created_at = '',
		private string $updated_at = '',
		private ?string $cancelled_at = null,
		private array $metadata = []
	) {}

	public function get_id(): int {
		return $this->id;
	}

	public function get_user_id(): int {
		return $this->user_id;
	}

	public function get_plan_id(): int {
		return $this->plan_id;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function get_start_date(): string {
		return $this->start_date;
	}

	public function get_expires_at(): ?string {
		return $this->expires_at;
	}

	public function is_lifetime(): bool {
		return null === $this->expires_at || '' === trim( (string) $this->expires_at );
	}

	/**
	 * Checks if this membership is currently valid and granting access.
	 *
	 * Note: Enforces immediate past-due expiry check even if cron normalization has not yet executed.
	 */
	public function is_active(): bool {
		if ( self::STATUS_ACTIVE !== $this->status ) {
			return false;
		}

		if ( $this->is_lifetime() ) {
			return true;
		}

		return ! $this->is_past_expiry();
	}

	/**
	 * Determine if expires_at is in the past according to WordPress timezone.
	 */
	public function is_past_expiry(): bool {
		if ( $this->is_lifetime() ) {
			return false;
		}

		try {
			$wp_tz   = wp_timezone();
			$now     = new \DateTimeImmutable( 'now', $wp_tz );
			$expires = new \DateTimeImmutable( (string) $this->expires_at, $wp_tz );
			return $now >= $expires;
		} catch ( \Exception ) {
			return false;
		}
	}

	/**
	 * Calculate remaining days until expiration.
	 * Returns null for lifetime, negative if expired, integer days otherwise.
	 */
	public function get_days_remaining(): ?int {
		if ( $this->is_lifetime() ) {
			return null;
		}

		try {
			$wp_tz   = wp_timezone();
			$now     = new \DateTimeImmutable( 'now', $wp_tz );
			$expires = new \DateTimeImmutable( (string) $this->expires_at, $wp_tz );

			$diff = $now->diff( $expires );
			$days = (int) $diff->format( '%r%a' );
			return $days;
		} catch ( \Exception ) {
			return null;
		}
	}

	public function get_source(): string {
		return $this->source;
	}

	public function get_order_id(): ?int {
		return $this->order_id;
	}

	public function get_subscription_id(): ?int {
		return $this->subscription_id;
	}

	public function get_created_at(): string {
		return $this->created_at;
	}

	public function get_updated_at(): string {
		return $this->updated_at;
	}

	public function get_cancelled_at(): ?string {
		return $this->cancelled_at;
	}

	public function get_metadata(): array {
		return $this->metadata;
	}

	public function get_meta( string $key, mixed $default = null ): mixed {
		return $this->metadata[ $key ] ?? $default;
	}

	public function with_status( string $status ): self {
		$clone = clone $this;
		$clone->status = $status;
		return $clone;
	}

	public function with_expiry( ?string $expires_at ): self {
		$clone = clone $this;
		$clone->expires_at = $expires_at;
		return $clone;
	}

	public function to_array(): array {
		return [
			'id'              => $this->id,
			'user_id'         => $this->user_id,
			'plan_id'         => $this->plan_id,
			'status'          => $this->status,
			'start_date'      => $this->start_date,
			'expires_at'      => $this->expires_at,
			'source'          => $this->source,
			'order_id'        => $this->order_id,
			'subscription_id' => $this->subscription_id,
			'created_at'      => $this->created_at,
			'updated_at'      => $this->updated_at,
			'cancelled_at'    => $this->cancelled_at,
			'metadata'        => $this->metadata,
		];
	}
}
