<?php
/**
 * User Registration Integration.
 *
 * Handles offering free membership plans during WordPress user registration
 * and automatically granting default plans while strictly preventing unauthorized plan self-selection.
 *
 * @package WPCalibrate\MembershipAccess\Frontend
 */

namespace WPCalibrate\MembershipAccess\Frontend;

use WPCalibrate\MembershipAccess\Membership\Membership;
use WPCalibrate\MembershipAccess\Membership\MembershipService;
use WPCalibrate\MembershipAccess\Membership\PlanRepository;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class UserRegistration {

	public function __construct(
		private MembershipService $membership_service,
		private PlanRepository $plan_repo
	) {}

	public function register(): void {
		$settings = get_option( 'wpcalibrate_membership_access_settings', [] );

		// If plan selection on register is enabled, hook into registration form.
		if ( ( $settings['allow_registration_plans'] ?? 'no' ) === 'yes' ) {
			add_action( 'register_form', [ $this, 'render_registration_field' ] );
			add_filter( 'registration_errors', [ $this, 'validate_registration_plan' ], 10, 3 );
		}

		// Always hook user_register to process either user choice or default plan auto-assignment.
		add_action( 'user_register', [ $this, 'process_registration_membership' ], 20 );
	}

	public function render_registration_field(): void {
		$plans = $this->plan_repo->get_registration_plans();
		if ( empty( $plans ) ) {
			return;
		}

		$selected_id = isset( $_POST['wpcal_reg_plan'] ) ? absint( $_POST['wpcal_reg_plan'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		?>
		<p class="wpcal-reg-field">
			<label for="wpcal_reg_plan">
				<?php esc_html_e( 'Select Membership Plan', 'wpcalibrate-membership-access' ); ?><br />
				<select name="wpcal_reg_plan" id="wpcal_reg_plan" style="width:100%;margin-top:4px;">
					<option value="0"><?php esc_html_e( '-- Free Access (Default) --', 'wpcalibrate-membership-access' ); ?></option>
					<?php foreach ( $plans as $plan ) : ?>
						<option value="<?php echo esc_attr( $plan->get_id() ); ?>" <?php selected( $selected_id, $plan->get_id() ); ?>>
							<?php echo esc_html( $plan->get_name() ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>
		<?php
	}

	/**
	 * Validate that user-submitted plan is actually authorized for registration self-selection.
	 */
	public function validate_registration_plan( \WP_Error $errors, string $sanitized_user_login, string $user_email ): \WP_Error {
		if ( empty( $_POST['wpcal_reg_plan'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $errors;
		}

		$plan_id = absint( $_POST['wpcal_reg_plan'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 0 === $plan_id ) {
			return $errors;
		}

		$plan = $this->plan_repo->get( $plan_id );
		if ( ! $plan || ! $plan->is_registration_allowed() ) {
			$errors->add(
				'invalid_membership_plan',
				__( '<strong>ERROR</strong>: The selected membership plan is not available for registration.', 'wpcalibrate-membership-access' )
			);
		}

		return $errors;
	}

	/**
	 * Grant membership upon user registration.
	 */
	public function process_registration_membership( int $user_id ): void {
		$plan_id = 0;

		// 1. Check if user selected an eligible plan.
		if ( ! empty( $_POST['wpcal_reg_plan'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$candidate_id = absint( $_POST['wpcal_reg_plan'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$plan         = $this->plan_repo->get( $candidate_id );
			if ( $plan && $plan->is_registration_allowed() ) {
				$plan_id = $candidate_id;
			}
		}

		// 2. If no valid selection was made, check for default auto-assigned plan.
		if ( 0 === $plan_id ) {
			$settings   = get_option( 'wpcalibrate_membership_access_settings', [] );
			$default_id = absint( $settings['registration_default_plan'] ?? 0 );
			if ( $default_id > 0 ) {
				$plan_id = $default_id;
			} else {
				$default_plan = $this->plan_repo->get_default_registration_plan();
				if ( $default_plan ) {
					$plan_id = $default_plan->get_id();
				}
			}
		}

		if ( $plan_id > 0 ) {
			$this->membership_service->create_membership(
				$user_id,
				$plan_id,
				Membership::SOURCE_REGISTRATION
			);
		}
	}
}
