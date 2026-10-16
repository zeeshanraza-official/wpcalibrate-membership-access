<?php
/**
 * Main Plugin Orchestrator.
 *
 * @package WPCalibrate\MembershipAccess
 */

namespace WPCalibrate\MembershipAccess;

use WPCalibrate\MembershipAccess\Access\AccessManager;
use WPCalibrate\MembershipAccess\Access\ContentRestriction;
use WPCalibrate\MembershipAccess\Access\RuleResolver;
use WPCalibrate\MembershipAccess\Admin\AdminMenu;
use WPCalibrate\MembershipAccess\Admin\AdminScreens;
use WPCalibrate\MembershipAccess\Audit\AuditLogger;
use WPCalibrate\MembershipAccess\Blocks\ProtectedContentBlock;
use WPCalibrate\MembershipAccess\Cron\ExpiryJob;
use WPCalibrate\MembershipAccess\Cron\Scheduler;
use WPCalibrate\MembershipAccess\Database\Migrator;
use WPCalibrate\MembershipAccess\Email\EmailManager;
use WPCalibrate\MembershipAccess\Export\CsvExporter;
use WPCalibrate\MembershipAccess\Frontend\MemberDashboard;
use WPCalibrate\MembershipAccess\Frontend\RedirectHandler;
use WPCalibrate\MembershipAccess\Frontend\Shortcodes;
use WPCalibrate\MembershipAccess\Frontend\UserRegistration;
use WPCalibrate\MembershipAccess\Integrations\WooCommerce\WooCommerceIntegration;
use WPCalibrate\MembershipAccess\Integrations\WooSubscriptions\SubscriptionIntegration;
use WPCalibrate\MembershipAccess\Licensing\LicenseManager;
use WPCalibrate\MembershipAccess\Membership\MembershipRepository;
use WPCalibrate\MembershipAccess\Membership\MembershipService;
use WPCalibrate\MembershipAccess\Membership\PlanRepository;
use WPCalibrate\MembershipAccess\Membership\StatusManager;
use WPCalibrate\MembershipAccess\Privacy\PrivacyPolicy;
use WPCalibrate\MembershipAccess\Security\Capabilities;
use WPCalibrate\MembershipAccess\Updates\GitHubUpdater;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?Plugin $instance = null;

	private PlanRepository $plan_repository;
	private MembershipRepository $membership_repository;
	private StatusManager $status_manager;
	private AuditLogger $audit_logger;
	private MembershipService $membership_service;
	private RuleResolver $rule_resolver;
	private AccessManager $access_manager;
	private ContentRestriction $content_restriction;
	private RedirectHandler $redirect_handler;
	private EmailManager $email_manager;
	private LicenseManager $license_manager;
	private AdminMenu $admin_menu;
	private AdminScreens $admin_screens;
	private Scheduler $scheduler;
	private ExpiryJob $expiry_job;
	private WooCommerceIntegration $woocommerce_integration;
	private SubscriptionIntegration $subscription_integration;
	private GitHubUpdater $github_updater;

	private bool $initialized = false;

	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		// 1. Run migrations if needed (idempotent, checks DB version option).
		Migrator::migrate();

		// 2. Ensure capabilities are assigned to administrator role.
		Capabilities::register_capabilities();

		// 3. Initialize Domain Repositories and Services.
		$this->audit_logger           = new AuditLogger();
		$this->status_manager         = new StatusManager();
		$this->plan_repository        = new PlanRepository();
		$this->membership_repository  = new MembershipRepository();
		$this->membership_service     = new MembershipService(
			$this->membership_repository,
			$this->plan_repository,
			$this->status_manager,
			$this->audit_logger
		);

		// 4. Access Resolution and Content Restriction.
		$this->rule_resolver       = new RuleResolver( $this->plan_repository );
		$this->access_manager      = new AccessManager(
			$this->membership_repository,
			$this->rule_resolver
		);
		$this->redirect_handler    = new RedirectHandler( $this->access_manager );
		$this->content_restriction = new ContentRestriction(
			$this->access_manager,
			$this->redirect_handler
		);

		// 5. Communications, Licensing and Background Scheduler.
		$this->email_manager   = new EmailManager();
		$this->license_manager = new LicenseManager();
		$this->expiry_job      = new ExpiryJob(
			$this->membership_repository,
			$this->membership_service,
			$this->email_manager
		);
		$this->scheduler       = new Scheduler( $this->expiry_job );

		// 6. Integrations.
		$this->woocommerce_integration  = new WooCommerceIntegration(
			$this->membership_service,
			$this->plan_repository,
			$this->access_manager
		);
		$this->subscription_integration = new SubscriptionIntegration(
			$this->membership_service,
			$this->plan_repository
		);

		// 7. Admin and Frontend Interfaces.
		$this->admin_screens = new AdminScreens(
			$this->plan_repository,
			$this->membership_repository,
			$this->membership_service,
			$this->audit_logger,
			$this->license_manager,
			$this->email_manager
		);
		$this->admin_menu    = new AdminMenu( $this->admin_screens );

		// 8. Register Hooks across subsystems.
		$this->register_subsystems();
	}

	private function register_subsystems(): void {
		// Translation.
		load_plugin_textdomain(
			'wpcalibrate-membership-access',
			false,
			dirname( WPCALIBRATE_MEMBERSHIP_ACCESS_BASENAME ) . '/languages/'
		);

		// Register post type for Plans.
		$this->plan_repository->register_post_type();

		// Content restrictions, redirect handler and feeds/REST protection.
		$this->content_restriction->register_hooks();
		$this->redirect_handler->register_hooks();

		// Shortcodes and Blocks.
		( new Shortcodes( $this->access_manager, $this->membership_repository ) )->register();
		( new MemberDashboard( $this->membership_repository, $this->plan_repository ) )->register();
		( new ProtectedContentBlock( $this->access_manager ) )->register();

		// User registration hooks (for free plan auto-assignment or selection).
		( new UserRegistration( $this->membership_service, $this->plan_repository ) )->register();

		// Admin Menu, Screens and GitHub Update Checker.
		if ( is_admin() ) {
			$this->admin_menu->init();
			$this->admin_screens->init();
			( new CsvExporter( $this->membership_repository ) )->init();
			$this->github_updater = new GitHubUpdater();
			$this->github_updater->init();
		}

		// Background Tasks.
		$this->scheduler->init();

		// Privacy data exporter & eraser.
		( new PrivacyPolicy( $this->membership_repository, $this->audit_logger ) )->init();

		// WooCommerce optional integration.
		$this->woocommerce_integration->init();
		$this->subscription_integration->init();
	}

	// Accessor methods for services.
	public function get_plan_repository(): PlanRepository {
		return $this->plan_repository;
	}

	public function get_membership_repository(): MembershipRepository {
		return $this->membership_repository;
	}

	public function get_status_manager(): StatusManager {
		return $this->status_manager;
	}

	public function get_membership_service(): MembershipService {
		return $this->membership_service;
	}

	public function get_access_manager(): AccessManager {
		return $this->access_manager;
	}

	public function get_audit_logger(): AuditLogger {
		return $this->audit_logger;
	}

	public function get_email_manager(): EmailManager {
		return $this->email_manager;
	}

	public function get_license_manager(): LicenseManager {
		return $this->license_manager;
	}
}
