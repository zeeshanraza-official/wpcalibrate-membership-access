<?php
/**
 * WooCommerce Integration Coordinator.
 *
 * @package WPCalibrate\MembershipAccess\Integrations\WooCommerce
 */

namespace WPCalibrate\MembershipAccess\Integrations\WooCommerce;

use WPCalibrate\MembershipAccess\Access\AccessManager;
use WPCalibrate\MembershipAccess\Membership\MembershipService;
use WPCalibrate\MembershipAccess\Membership\PlanRepository;

defined( 'ABSPATH' ) || exit;

class WooCommerceIntegration {

	private ?ProductMembership $product_membership = null;
	private ?OrderHandler $order_handler           = null;
	private ?AccountIntegration $account_integration = null;

	public function __construct(
		private MembershipService $membership_service,
		private PlanRepository $plan_repo,
		private AccessManager $access_manager
	) {}

	public static function is_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function init(): void {
		if ( ! self::is_active() ) {
			return;
		}

		$membership_repo           = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_membership_repository();
		$this->product_membership  = new ProductMembership( $this->access_manager, $this->plan_repo );
		$this->order_handler       = new OrderHandler( $this->membership_service, $membership_repo, $this->plan_repo );
		$this->account_integration = new AccountIntegration();

		$this->product_membership->init();
		$this->order_handler->init();
		$this->account_integration->init();
	}
}
