<?php
/**
 * Admin Screens Controller.
 *
 * Handles admin routing, tabs, form processing, nonces, capabilities,
 * editor meta boxes, and view rendering.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

namespace WPCalibrate\MembershipAccess\Admin;

use WPCalibrate\MembershipAccess\Access\RuleResolver;
use WPCalibrate\MembershipAccess\Audit\AuditLogger;
use WPCalibrate\MembershipAccess\Email\EmailManager;
use WPCalibrate\MembershipAccess\Licensing\LicenseManager;
use WPCalibrate\MembershipAccess\Membership\Membership;
use WPCalibrate\MembershipAccess\Membership\MembershipRepository;
use WPCalibrate\MembershipAccess\Membership\MembershipService;
use WPCalibrate\MembershipAccess\Membership\Plan;
use WPCalibrate\MembershipAccess\Membership\PlanRepository;
use WPCalibrate\MembershipAccess\Membership\StatusManager;
use WPCalibrate\MembershipAccess\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

class AdminScreens {

	private string $hook_suffix = '';
	private array $notices      = [];

	public function __construct(
		private PlanRepository $plan_repo,
		private MembershipRepository $membership_repo,
		private MembershipService $membership_service,
		private AuditLogger $audit_logger,
		private LicenseManager $license_manager,
		private EmailManager $email_manager
	) {}

	public function set_hook_suffix( string $suffix ): void {
		$this->hook_suffix = $suffix;
	}

	public function get_hook_suffix(): string {
		return $this->hook_suffix;
	}

	public function get_capability(): string {
		return apply_filters(
			'wpcalibrate_membership_access_capability',
			Capabilities::CAP_MANAGE_MEMBERSHIPS
		);
	}

	public function init(): void {
		add_action( 'admin_init', [ $this, 'handle_actions' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Content editor meta boxes for posts, pages, and custom post types.
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta_box_data' ], 10, 2 );

		// Taxonomy term fields for categories / terms.
		add_action( 'init', [ $this, 'register_taxonomy_hooks' ], 20 );
	}

	public function enqueue_assets( string $hook ): void {
		$screen = get_current_screen();

		// Support both parent WPCalibrate screen and Membership Access submenu.
		$is_plugin_screen = str_contains( $hook, AdminMenu::SUBMENU_SLUG )
			|| str_contains( $hook, AdminMenu::PARENT_SLUG )
			|| ( $screen && ( 'toplevel_page_' . AdminMenu::PARENT_SLUG === $screen->id || str_contains( $screen->id, 'wpcalibrate' ) ) );
		$is_post_screen   = $screen && 'post' === $screen->base;

		if ( ! $is_plugin_screen && ! $is_post_screen ) {
			return;
		}

		$css_path    = WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'assets/css/admin.css';
		$css_version = file_exists( $css_path )
			? WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION . '.' . filemtime( $css_path )
			: WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION;

		wp_enqueue_style(
			'wpcalibrate-membership-access-admin',
			WPCALIBRATE_MEMBERSHIP_ACCESS_URL . 'assets/css/admin.css',
			[],
			$css_version
		);

		if ( $is_plugin_screen ) {
			$js_path    = WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'assets/js/admin.js';
			$js_version = file_exists( $js_path )
				? WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION . '.' . filemtime( $js_path )
				: WPCALIBRATE_MEMBERSHIP_ACCESS_VERSION;

			wp_enqueue_script(
				'wpcalibrate-membership-access-admin',
				WPCALIBRATE_MEMBERSHIP_ACCESS_URL . 'assets/js/admin.js',
				[ 'jquery' ],
				$js_version,
				true
			);

			wp_localize_script(
				'wpcalibrate-membership-access-admin',
				'wpcalMembershipAdmin',
				[
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'confirmDelete' => __( 'Are you sure you want to delete this record? This action cannot be undone.', 'wpcalibrate-membership-access' ),
				]
			);
		}
	}

	/**
	 * Handle state-changing actions.
	 */
	public function handle_actions(): void {
		$page = sanitize_key( $_GET['page'] ?? '' );
		if ( AdminMenu::SUBMENU_SLUG !== $page && AdminMenu::PARENT_SLUG !== $page ) {
			return;
		}

		$capability = $this->get_capability();
		if ( ! current_user_can( $capability ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$action = sanitize_key( $_POST['wpcal_action'] ?? '' );

			if ( 'save_plan' === $action ) {
				$this->handle_save_plan();
			} elseif ( 'save_settings' === $action ) {
				$this->handle_save_settings();
			} elseif ( 'save_email_settings' === $action ) {
				$this->handle_save_email_settings();
			} elseif ( 'save_wc_settings' === $action ) {
				$this->handle_save_wc_settings();
			} elseif ( 'save_license' === $action ) {
				$this->handle_save_license();
			} elseif ( 'assign_membership' === $action ) {
				$this->handle_assign_membership();
			} elseif ( 'update_membership' === $action ) {
				$this->handle_update_membership();
			}
		} elseif ( isset( $_GET['wpcal_action'] ) ) {
			$action = sanitize_key( $_GET['wpcal_action'] );

			if ( 'delete_plan' === $action ) {
				$this->handle_delete_plan();
			} elseif ( 'change_status' === $action ) {
				$this->handle_change_status();
			} elseif ( 'delete_membership' === $action ) {
				$this->handle_delete_membership();
			}
		}
	}

	private function handle_save_plan(): void {
		check_admin_referer( 'wpcal_save_plan_nonce', '_wpcal_nonce' );

		$id                = absint( $_POST['plan_id'] ?? 0 );
		$name              = sanitize_text_field( $_POST['plan_name'] ?? '' );
		$slug              = sanitize_title( $_POST['plan_slug'] ?? '' );
		$description       = wp_kses_post( $_POST['plan_description'] ?? '' );
		$status            = sanitize_key( $_POST['plan_status'] ?? 'active' );
		$duration_type     = sanitize_key( $_POST['duration_type'] ?? Plan::DURATION_LIFETIME );
		$duration_quantity = absint( $_POST['duration_quantity'] ?? 1 );
		$duration_unit     = sanitize_key( $_POST['duration_unit'] ?? Plan::UNIT_MONTHS );
		$priority          = absint( $_POST['priority'] ?? 10 );
		$admin_notes       = sanitize_textarea_field( $_POST['admin_notes'] ?? '' );
		$reg_allowed       = ! empty( $_POST['is_registration_allowed'] );
		$default_reg       = ! empty( $_POST['is_default_registration'] );

		// Product associations.
		$product_ids = [];
		if ( ! empty( $_POST['product_ids'] ) ) {
			if ( is_array( $_POST['product_ids'] ) ) {
				$product_ids = array_map( 'absint', $_POST['product_ids'] );
			} else {
				$product_ids = array_map( 'absint', explode( ',', (string) $_POST['product_ids'] ) );
			}
		}

		if ( empty( $name ) ) {
			$this->redirect_with_notice( 'plans', 'error_plan_name' );
			return;
		}

		$plan = new Plan(
			$id,
			$name,
			$slug,
			$description,
			$status,
			$duration_type,
			$duration_quantity,
			$duration_unit,
			$product_ids,
			$reg_allowed,
			$default_reg,
			$priority,
			$admin_notes
		);

		$saved_id = $this->plan_repo->save( $plan );
		if ( $saved_id > 0 ) {
			$this->redirect_with_notice( 'plans', 'plan_saved' );
		} else {
			$this->redirect_with_notice( 'plans', 'error_saving_plan' );
		}
	}

	private function handle_delete_plan(): void {
		check_admin_referer( 'wpcal_delete_plan_nonce', '_wpcal_nonce' );

		$id = absint( $_GET['plan_id'] ?? 0 );
		if ( $id > 0 && $this->plan_repo->delete( $id ) ) {
			$this->redirect_with_notice( 'plans', 'plan_deleted' );
		} else {
			$this->redirect_with_notice( 'plans', 'error_deleting_plan' );
		}
	}

	private function handle_assign_membership(): void {
		check_admin_referer( 'wpcal_assign_membership_nonce', '_wpcal_nonce' );

		$user_id    = absint( $_POST['user_id'] ?? 0 );
		$plan_id    = absint( $_POST['plan_id'] ?? 0 );
		$is_lt      = ! empty( $_POST['is_lifetime'] );
		$start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
		$expires_at = $is_lt ? null : sanitize_text_field( $_POST['expires_at'] ?? '' );

		if ( empty( $expires_at ) && ! $is_lt ) {
			$plan = $this->plan_repo->get( $plan_id );
			if ( $plan ) {
				$expires_at = $plan->calculate_expiry( $start_date );
			}
		}

		$result = $this->membership_service->create_membership(
			$user_id,
			$plan_id,
			Membership::SOURCE_ADMIN,
			[
				'start_date' => $start_date,
				'expires_at' => $expires_at,
			]
		);

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( 'members', 'error_' . $result->get_error_code() );
		} else {
			$this->redirect_with_notice( 'members', 'membership_assigned' );
		}
	}

	private function handle_update_membership(): void {
		check_admin_referer( 'wpcal_update_membership_nonce', '_wpcal_nonce' );

		$membership_id = absint( $_POST['membership_id'] ?? 0 );
		$plan_id       = absint( $_POST['plan_id'] ?? 0 );
		$status        = sanitize_key( $_POST['status'] ?? 'active' );
		$start_date    = sanitize_text_field( $_POST['start_date'] ?? '' );
		$is_lt         = ! empty( $_POST['is_lifetime'] );
		$expires_at    = $is_lt ? null : sanitize_text_field( $_POST['expires_at'] ?? '' );
		$reason        = sanitize_text_field( $_POST['reason'] ?? '' );

		$result = $this->membership_service->update_assignment(
			$membership_id,
			$plan_id,
			$start_date,
			$expires_at,
			$status,
			$reason,
			get_current_user_id()
		);

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( 'members', 'error_' . $result->get_error_code() );
		} else {
			$this->redirect_with_notice( 'members', 'membership_updated' );
		}
	}

	private function handle_change_status(): void {
		check_admin_referer( 'wpcal_status_nonce', '_wpcal_nonce' );

		$membership_id = absint( $_GET['membership_id'] ?? 0 );
		$new_status    = sanitize_key( $_GET['new_status'] ?? '' );

		$result = $this->membership_service->update_status(
			$membership_id,
			$new_status,
			__( 'Changed via admin dashboard.', 'wpcalibrate-membership-access' ),
			get_current_user_id()
		);

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( 'members', 'error_' . $result->get_error_code() );
		} else {
			$this->redirect_with_notice( 'members', 'status_updated' );
		}
	}

	private function handle_delete_membership(): void {
		check_admin_referer( 'wpcal_delete_membership_nonce', '_wpcal_nonce' );

		$membership_id = absint( $_GET['membership_id'] ?? 0 );
		if ( $membership_id > 0 && $this->membership_repo->delete( $membership_id ) ) {
			$this->redirect_with_notice( 'members', 'membership_deleted' );
		} else {
			$this->redirect_with_notice( 'members', 'error_deleting' );
		}
	}

	private function handle_save_settings(): void {
		check_admin_referer( 'wpcal_save_settings_nonce', '_wpcal_nonce' );

		$settings = get_option( 'wpcalibrate_membership_access_settings', [] );

		$settings['access_denied_behavior']     = sanitize_key( $_POST['access_denied_behavior'] ?? 'message' );
		$settings['custom_denied_message']      = wp_kses_post( $_POST['custom_denied_message'] ?? '' );
		$settings['redirect_url']               = esc_url_raw( $_POST['redirect_url'] ?? '' );
		$settings['plans_page_url']             = esc_url_raw( $_POST['plans_page_url'] ?? '' );
		$settings['search_archive_visibility']  = sanitize_key( $_POST['search_archive_visibility'] ?? 'normal_restrict_content' );
		$settings['allow_multiple_memberships'] = ! empty( $_POST['allow_multiple_memberships'] ) ? 'yes' : 'no';
		$settings['enable_expiry_reminders']    = ! empty( $_POST['enable_expiry_reminders'] ) ? 'yes' : 'no';
		$settings['expiry_reminder_days']       = max( 1, absint( $_POST['expiry_reminder_days'] ?? 7 ) );
		$settings['allow_registration_plans']   = ! empty( $_POST['allow_registration_plans'] ) ? 'yes' : 'no';
		$settings['registration_default_plan']  = absint( $_POST['registration_default_plan'] ?? 0 );
		$settings['delete_data_on_uninstall']   = ! empty( $_POST['delete_data_on_uninstall'] ) ? 'yes' : 'no';
		$settings['enable_debug_logging']       = ! empty( $_POST['enable_debug_logging'] ) ? 'yes' : 'no';

		update_option( 'wpcalibrate_membership_access_settings', $settings );
		$this->redirect_with_notice( 'settings', 'settings_saved' );
	}

	private function handle_save_email_settings(): void {
		check_admin_referer( 'wpcal_save_email_settings_nonce', '_wpcal_nonce' );

		$email_options = [
			'activation_enabled'  => ! empty( $_POST['activation_enabled'] ),
			'activation_subject'  => sanitize_text_field( $_POST['activation_subject'] ?? '' ),
			'activation_body'     => wp_kses_post( $_POST['activation_body'] ?? '' ),
			'reminder_enabled'    => ! empty( $_POST['reminder_enabled'] ),
			'reminder_subject'    => sanitize_text_field( $_POST['reminder_subject'] ?? '' ),
			'reminder_body'       => wp_kses_post( $_POST['reminder_body'] ?? '' ),
			'expired_enabled'     => ! empty( $_POST['expired_enabled'] ),
			'expired_subject'     => sanitize_text_field( $_POST['expired_subject'] ?? '' ),
			'expired_body'        => wp_kses_post( $_POST['expired_body'] ?? '' ),
			'cancelled_enabled'   => ! empty( $_POST['cancelled_enabled'] ),
			'cancelled_subject'   => sanitize_text_field( $_POST['cancelled_subject'] ?? '' ),
			'cancelled_body'      => wp_kses_post( $_POST['cancelled_body'] ?? '' ),
		];

		update_option( 'wpcalibrate_membership_access_email_settings', $email_options );
		$this->redirect_with_notice( 'emails', 'settings_saved' );
	}

	private function handle_save_wc_settings(): void {
		check_admin_referer( 'wpcal_save_wc_settings_nonce', '_wpcal_nonce' );

		$settings = get_option( 'wpcalibrate_membership_access_settings', [] );

		$statuses = $_POST['wc_grant_order_statuses'] ?? [ 'processing', 'completed' ];
		if ( is_array( $statuses ) ) {
			$settings['wc_grant_order_statuses'] = array_values( array_map( 'sanitize_key', $statuses ) );
		}

		$settings['wc_revoke_on_refund']         = sanitize_key( $_POST['wc_revoke_on_refund'] ?? 'cancel' );
		$settings['wc_revoke_on_cancelled']      = sanitize_key( $_POST['wc_revoke_on_cancelled'] ?? 'cancel' );
		$settings['wc_product_restriction_mode'] = sanitize_key( $_POST['wc_product_restriction_mode'] ?? 'disabled_purchase' );
		$settings['wc_product_restricted_msg']   = wp_kses_post( $_POST['wc_product_restricted_msg'] ?? '' );

		update_option( 'wpcalibrate_membership_access_settings', $settings );
		$this->redirect_with_notice( 'woocommerce', 'settings_saved' );
	}

	private function handle_save_license(): void {
		check_admin_referer( 'wpcal_save_license_nonce', '_wpcal_nonce' );

		$key = sanitize_text_field( $_POST['license_key'] ?? '' );
		$this->license_manager->save_key( $key );

		$this->redirect_with_notice( 'license', 'license_saved' );
	}

	private function redirect_with_notice( string $tab, string $message_code ): void {
		$url = add_query_arg(
			[
				'page'    => AdminMenu::SUBMENU_SLUG,
				'tab'     => $tab,
				'message' => $message_code,
			],
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Register meta box on post edit screens.
	 */
	public function register_meta_boxes(): void {
		$post_types = get_post_types( [ 'public' => true ] );

		foreach ( $post_types as $pt ) {
			if ( 'attachment' === $pt || PlanRepository::POST_TYPE === $pt ) {
				continue;
			}

			add_meta_box(
				'wpcalibrate_membership_access_box',
				__( 'WPCalibrate Membership Access', 'wpcalibrate-membership-access' ),
				[ $this, 'render_post_meta_box' ],
				$pt,
				'side',
				'high'
			);
		}
	}

	public function render_post_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'wpcal_post_access_nonce', '_wpcal_post_nonce' );

		$rule_type      = get_post_meta( $post->ID, '_wpcal_access_rule_type', true ) ?: 'default';
		$selected_plans = (array) get_post_meta( $post->ID, '_wpcal_access_plans', true );
		$plans          = $this->plan_repo->get_active();
		?>
		<div class="wpcal-meta-box">
			<p>
				<label for="wpcal_access_rule_type"><strong><?php esc_html_e( 'Access Restriction', 'wpcalibrate-membership-access' ); ?>:</strong></label><br>
				<select name="wpcal_access_rule_type" id="wpcal_access_rule_type" style="width:100%;">
					<option value="default" <?php selected( $rule_type, 'default' ); ?>><?php esc_html_e( 'Default (Inherit Taxonomy / Global)', 'wpcalibrate-membership-access' ); ?></option>
					<option value="everyone" <?php selected( $rule_type, 'everyone' ); ?>><?php esc_html_e( 'Public (Everyone)', 'wpcalibrate-membership-access' ); ?></option>
					<option value="logged_in" <?php selected( $rule_type, 'logged_in' ); ?>><?php esc_html_e( 'Logged-In Users', 'wpcalibrate-membership-access' ); ?></option>
					<option value="any_member" <?php selected( $rule_type, 'any_member' ); ?>><?php esc_html_e( 'Any Active Membership', 'wpcalibrate-membership-access' ); ?></option>
					<option value="specific_plans" <?php selected( $rule_type, 'specific_plans' ); ?>><?php esc_html_e( 'Specific Membership Plans', 'wpcalibrate-membership-access' ); ?></option>
					<option value="non_members" <?php selected( $rule_type, 'non_members' ); ?>><?php esc_html_e( 'Non-Members Only', 'wpcalibrate-membership-access' ); ?></option>
				</select>
			</p>

			<div id="wpcal_specific_plans_wrap" style="<?php echo ( 'specific_plans' === $rule_type ) ? '' : 'display:none;'; ?>">
				<p><strong><?php esc_html_e( 'Allowed Plans', 'wpcalibrate-membership-access' ); ?>:</strong></p>
				<?php if ( empty( $plans ) ) : ?>
					<p class="description"><?php esc_html_e( 'No membership plans exist yet.', 'wpcalibrate-membership-access' ); ?></p>
				<?php else : ?>
					<?php foreach ( $plans as $p ) : ?>
						<label style="display:block;margin-bottom:4px;">
							<input type="checkbox" name="wpcal_access_plans[]" value="<?php echo esc_attr( $p->get_id() ); ?>" <?php checked( in_array( $p->get_id(), $selected_plans, true ) ); ?>>
							<?php echo esc_html( $p->get_name() ); ?>
						</label>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<script>
		jQuery(function($){
			$('#wpcal_access_rule_type').on('change', function(){
				if ($(this).val() === 'specific_plans') {
					$('#wpcal_specific_plans_wrap').slideDown(150);
				} else {
					$('#wpcal_specific_plans_wrap').slideUp(150);
				}
			});
		});
		</script>
		<?php
	}

	public function save_meta_box_data( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['_wpcal_post_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpcal_post_nonce'] ), 'wpcal_post_access_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$rule_type = sanitize_key( $_POST['wpcal_access_rule_type'] ?? 'default' );
		update_post_meta( $post_id, '_wpcal_access_rule_type', $rule_type );

		if ( isset( $_POST['wpcal_access_plans'] ) && is_array( $_POST['wpcal_access_plans'] ) ) {
			$plans = array_values( array_map( 'absint', $_POST['wpcal_access_plans'] ) );
			update_post_meta( $post_id, '_wpcal_access_plans', $plans );
		} else {
			update_post_meta( $post_id, '_wpcal_access_plans', [] );
		}
	}

	/**
	 * Register term taxonomy fields.
	 */
	public function register_taxonomy_hooks(): void {
		$taxonomies = [ 'category', 'post_tag', 'product_cat' ];

		foreach ( $taxonomies as $tax ) {
			add_action( "{$tax}_edit_form_fields", [ $this, 'render_term_fields' ], 10, 2 );
			add_action( "edited_{$tax}", [ $this, 'save_term_fields' ], 10, 1 );
		}
	}

	public function render_term_fields( \WP_Term $term, string $taxonomy ): void {
		$rule_type      = get_term_meta( $term->term_id, '_wpcal_access_rule_type', true ) ?: 'default';
		$selected_plans = (array) get_term_meta( $term->term_id, '_wpcal_access_plans', true );
		$plans          = $this->plan_repo->get_active();
		?>
		<tr class="form-field">
			<th scope="row"><label for="wpcal_term_access_rule"><?php esc_html_e( 'Membership Access', 'wpcalibrate-membership-access' ); ?></label></th>
			<td>
				<select name="wpcal_access_rule_type" id="wpcal_term_access_rule">
					<option value="default" <?php selected( $rule_type, 'default' ); ?>><?php esc_html_e( 'Default (Open / Global)', 'wpcalibrate-membership-access' ); ?></option>
					<option value="logged_in" <?php selected( $rule_type, 'logged_in' ); ?>><?php esc_html_e( 'Logged-In Users', 'wpcalibrate-membership-access' ); ?></option>
					<option value="any_member" <?php selected( $rule_type, 'any_member' ); ?>><?php esc_html_e( 'Any Active Membership', 'wpcalibrate-membership-access' ); ?></option>
					<option value="specific_plans" <?php selected( $rule_type, 'specific_plans' ); ?>><?php esc_html_e( 'Specific Membership Plans', 'wpcalibrate-membership-access' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Restricts all posts filed under this category.', 'wpcalibrate-membership-access' ); ?></p>

				<div style="margin-top:10px;">
					<strong><?php esc_html_e( 'Allowed Plans (if Specific Membership Plans chosen):', 'wpcalibrate-membership-access' ); ?></strong><br>
					<?php foreach ( $plans as $p ) : ?>
						<label style="margin-right:15px;display:inline-block;">
							<input type="checkbox" name="wpcal_access_plans[]" value="<?php echo esc_attr( $p->get_id() ); ?>" <?php checked( in_array( $p->get_id(), $selected_plans, true ) ); ?>>
							<?php echo esc_html( $p->get_name() ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</td>
		</tr>
		<?php
	}

	public function save_term_fields( int $term_id ): void {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		if ( isset( $_POST['wpcal_access_rule_type'] ) ) {
			update_term_meta( $term_id, '_wpcal_access_rule_type', sanitize_key( $_POST['wpcal_access_rule_type'] ) );
		}

		if ( isset( $_POST['wpcal_access_plans'] ) && is_array( $_POST['wpcal_access_plans'] ) ) {
			$plans = array_values( array_map( 'absint', $_POST['wpcal_access_plans'] ) );
			update_term_meta( $term_id, '_wpcal_access_plans', $plans );
		} else {
			update_term_meta( $term_id, '_wpcal_access_plans', [] );
		}
	}

	/**
	 * Main Render Entry Point for the admin screen.
	 */
	public function render(): void {
		$tab = sanitize_key( $_GET['tab'] ?? 'overview' );
		$msg = sanitize_key( $_GET['message'] ?? '' );

		$brand_icon_url = WPCALIBRATE_MEMBERSHIP_ACCESS_URL . 'assets/branding/icon-dark.png';

		$tabs = [
			'overview'    => __( 'Overview', 'wpcalibrate-membership-access' ),
			'plans'       => __( 'Plans', 'wpcalibrate-membership-access' ),
			'members'     => __( 'Members', 'wpcalibrate-membership-access' ),
			'rules'       => __( 'Access Rules', 'wpcalibrate-membership-access' ),
			'emails'      => __( 'Emails', 'wpcalibrate-membership-access' ),
			'woocommerce' => __( 'WooCommerce', 'wpcalibrate-membership-access' ),
			'settings'    => __( 'Settings', 'wpcalibrate-membership-access' ),
			'license'     => __( 'License', 'wpcalibrate-membership-access' ),
			'support'     => __( 'Support', 'wpcalibrate-membership-access' ),
		];

		// Check WooCommerce status.
		$is_wc_active = class_exists( 'WooCommerce' );

		include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/header.php';

		switch ( $tab ) {
			case 'plans':
				$plans = $this->plan_repo->get_all();
				$edit_plan = null;
				if ( isset( $_GET['edit'] ) ) {
					$edit_plan = $this->plan_repo->get( absint( $_GET['edit'] ) );
				}
				include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/tab-plans.php';
				break;

			case 'members':
				$page_num = max( 1, absint( $_GET['paged'] ?? 1 ) );
				$per_page = 20;
				$filters  = [
					'search'       => sanitize_text_field( $_GET['s'] ?? '' ),
					'plan_id'      => absint( $_GET['filter_plan'] ?? 0 ),
					'status'       => sanitize_key( $_GET['filter_status'] ?? '' ),
					'source'       => sanitize_key( $_GET['filter_source'] ?? '' ),
					'expiry_state' => sanitize_key( $_GET['filter_expiry'] ?? '' ),
					'limit'        => $per_page,
					'offset'       => ( $page_num - 1 ) * $per_page,
				];

				$query_result = $this->membership_repo->query_admin( $filters );
				$memberships  = $query_result['items'];
				$total_items  = $query_result['total'];
				$total_pages  = (int) ceil( $total_items / $per_page );
				$plans        = $this->plan_repo->get_all();

				$audit_records = [];
				if ( ! empty( $_GET['view_audit'] ) ) {
					$audit_records = $this->audit_logger->get_for_membership( absint( $_GET['view_audit'] ) );
				}

				include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/tab-members.php';
				break;

			case 'rules':
				$plans = $this->plan_repo->get_active();
				include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/tab-rules.php';
				break;

			case 'emails':
				$email_settings = $this->email_manager->get_settings();
				include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/tab-emails.php';
				break;

			case 'woocommerce':
				$settings = get_option( 'wpcalibrate_membership_access_settings', [] );
				$plans    = $this->plan_repo->get_all();
				include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/tab-woocommerce.php';
				break;

			case 'settings':
				$settings = get_option( 'wpcalibrate_membership_access_settings', [] );
				$plans    = $this->plan_repo->get_all();
				include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/tab-settings.php';
				break;

			case 'license':
				$provider = $this->license_manager->get_provider();
				$license_info = $this->license_manager->get_license_info();
				include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/tab-license.php';
				break;

			case 'support':
				include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/tab-support.php';
				break;

			case 'overview':
			default:
				$counts = $this->membership_repo->get_counts_summary();
				$plans  = $this->plan_repo->get_all();
				include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/tab-overview.php';
				break;
		}

		include WPCALIBRATE_MEMBERSHIP_ACCESS_PATH . 'templates/admin/footer.php';
	}
}
