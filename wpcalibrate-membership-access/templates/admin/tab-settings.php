<?php
/**
 * Admin General Settings Tab.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

defined( 'ABSPATH' ) || exit;

use WPCalibrate\MembershipAccess\Membership\Plan;

/**
 * Variables available:
 *
 * @var array  $settings Plugin settings array.
 * @var Plan[] $plans All plans.
 */

$settings = isset( $settings ) && is_array( $settings ) ? $settings : [];
$plans    = isset( $plans ) && is_array( $plans ) ? $plans : [];
?>
<div class="wpcal-section-card">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'General Membership & Restriction Settings', 'wpcalibrate-membership-access' ); ?></h2>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=settings' ) ); ?>">
		<?php wp_nonce_field( 'wpcal_save_settings_nonce', '_wpcal_nonce' ); ?>
		<input type="hidden" name="wpcal_action" value="save_settings" />

		<table class="form-table">
			<tr>
				<th scope="row"><label for="access_denied_behavior"><?php esc_html_e( 'Default Access Denied Action', 'wpcalibrate-membership-access' ); ?></label></th>
				<td>
					<select name="access_denied_behavior" id="access_denied_behavior">
						<option value="message" <?php selected( $settings['access_denied_behavior'] ?? 'message', 'message' ); ?>><?php esc_html_e( 'Display Restricted Content Message on Page', 'wpcalibrate-membership-access' ); ?></option>
						<option value="login_redirect" <?php selected( $settings['access_denied_behavior'] ?? '', 'login_redirect' ); ?>><?php esc_html_e( 'Redirect to Login Page (with safe return destination)', 'wpcalibrate-membership-access' ); ?></option>
						<option value="registration_redirect" <?php selected( $settings['access_denied_behavior'] ?? '', 'registration_redirect' ); ?>><?php esc_html_e( 'Redirect to User Registration Page', 'wpcalibrate-membership-access' ); ?></option>
						<option value="custom_page" <?php selected( $settings['access_denied_behavior'] ?? '', 'custom_page' ); ?>><?php esc_html_e( 'Redirect to Custom URL / Page', 'wpcalibrate-membership-access' ); ?></option>
						<option value="plans_page" <?php selected( $settings['access_denied_behavior'] ?? '', 'plans_page' ); ?>><?php esc_html_e( 'Redirect to Membership Plans Page', 'wpcalibrate-membership-access' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Can be overridden per individual post or category.', 'wpcalibrate-membership-access' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="custom_denied_message"><?php esc_html_e( 'Default Restricted Notice Text', 'wpcalibrate-membership-access' ); ?></label></th>
				<td>
					<textarea name="custom_denied_message" id="custom_denied_message" class="large-text" rows="3"><?php echo esc_textarea( $settings['custom_denied_message'] ?? __( 'You must have an active membership to view this content.', 'wpcalibrate-membership-access' ) ); ?></textarea>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="redirect_url"><?php esc_html_e( 'Custom Redirect URL', 'wpcalibrate-membership-access' ); ?></label></th>
				<td>
					<input type="url" name="redirect_url" id="redirect_url" class="large-text" value="<?php echo esc_attr( $settings['redirect_url'] ?? '' ); ?>" placeholder="https://example.com/join" />
					<p class="description"><?php esc_html_e( 'Used when "Redirect to Custom URL" is selected.', 'wpcalibrate-membership-access' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="plans_page_url"><?php esc_html_e( 'Membership Plans Page URL', 'wpcalibrate-membership-access' ); ?></label></th>
				<td>
					<input type="url" name="plans_page_url" id="plans_page_url" class="large-text" value="<?php echo esc_attr( $settings['plans_page_url'] ?? '' ); ?>" placeholder="https://example.com/memberships" />
					<p class="description"><?php esc_html_e( 'Target destination when redirecting unauthorized visitors to plans pricing table.', 'wpcalibrate-membership-access' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="search_archive_visibility"><?php esc_html_e( 'Search & Archive Visibility', 'wpcalibrate-membership-access' ); ?></label></th>
				<td>
					<select name="search_archive_visibility" id="search_archive_visibility">
						<option value="normal_restrict_content" <?php selected( $settings['search_archive_visibility'] ?? 'normal_restrict_content', 'normal_restrict_content' ); ?>>
							<?php esc_html_e( 'Show normally in search & archives; restrict content when opened', 'wpcalibrate-membership-access' ); ?>
						</option>
						<option value="show_excerpt" <?php selected( $settings['search_archive_visibility'] ?? '', 'show_excerpt' ); ?>>
							<?php esc_html_e( 'Show title and excerpt in archives; restrict full content when opened', 'wpcalibrate-membership-access' ); ?>
						</option>
						<option value="hide_completely" <?php selected( $settings['search_archive_visibility'] ?? '', 'hide_completely' ); ?>>
							<?php esc_html_e( 'Completely hide restricted posts from search results and archive feeds', 'wpcalibrate-membership-access' ); ?>
						</option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Multiple Memberships', 'wpcalibrate-membership-access' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="allow_multiple_memberships" value="1" <?php checked( ( $settings['allow_multiple_memberships'] ?? 'yes' ) === 'yes' ); ?> />
						<?php esc_html_e( 'Allow a single user to hold multiple concurrent membership plans', 'wpcalibrate-membership-access' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Expiry Notifications', 'wpcalibrate-membership-access' ); ?></th>
				<td>
					<label style="display:block;margin-bottom:6px;">
						<input type="checkbox" name="enable_expiry_reminders" value="1" <?php checked( ( $settings['enable_expiry_reminders'] ?? 'yes' ) === 'yes' ); ?> />
						<?php esc_html_e( 'Send automated reminder email before a membership expires', 'wpcalibrate-membership-access' ); ?>
					</label>
					<div style="margin-top:6px;">
						<?php esc_html_e( 'Send reminder', 'wpcalibrate-membership-access' ); ?>
						<input type="number" name="expiry_reminder_days" min="1" max="90" value="<?php echo esc_attr( $settings['expiry_reminder_days'] ?? 7 ); ?>" style="width:70px;" />
						<?php esc_html_e( 'days before expiration.', 'wpcalibrate-membership-access' ); ?>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'WordPress User Registration', 'wpcalibrate-membership-access' ); ?></th>
				<td>
					<label style="display:block;margin-bottom:6px;">
						<input type="checkbox" name="allow_registration_plans" value="1" <?php checked( ( $settings['allow_registration_plans'] ?? 'no' ) === 'yes' ); ?> />
						<?php esc_html_e( 'Offer eligible free membership plans on standard WordPress register form', 'wpcalibrate-membership-access' ); ?>
					</label>

					<div style="margin-top:8px;">
						<label for="registration_default_plan"><?php esc_html_e( 'Default Auto-Granted Plan:', 'wpcalibrate-membership-access' ); ?></label>
						<select name="registration_default_plan" id="registration_default_plan">
							<option value="0"><?php esc_html_e( '-- None --', 'wpcalibrate-membership-access' ); ?></option>
							<?php foreach ( $plans as $p ) : ?>
								<option value="<?php echo esc_attr( $p->get_id() ); ?>" <?php selected( $settings['registration_default_plan'] ?? 0, $p->get_id() ); ?>>
									<?php echo esc_html( $p->get_name() ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Diagnostic Logging', 'wpcalibrate-membership-access' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_debug_logging" value="1" <?php checked( ( $settings['enable_debug_logging'] ?? 'no' ) === 'yes' ); ?> />
						<?php esc_html_e( 'Enable internal debugging logs for troubleshooting background operations and access decisions', 'wpcalibrate-membership-access' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Data Retention on Uninstall', 'wpcalibrate-membership-access' ); ?></th>
				<td>
					<label style="color:#b91c1c;">
						<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( ( $settings['delete_data_on_uninstall'] ?? 'no' ) === 'yes' ); ?> />
						<strong><?php esc_html_e( 'Delete all plugin data (memberships, audit history, plans, options) when plugin is deleted/uninstalled.', 'wpcalibrate-membership-access' ); ?></strong>
					</label>
					<p class="description"><?php esc_html_e( 'Default is unchecked (safe). Leaving unchecked preserves all your data if you deactivate or reinstall.', 'wpcalibrate-membership-access' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" class="wpcal-btn wpcal-btn-primary" value="<?php esc_attr_e( 'Save General Settings', 'wpcalibrate-membership-access' ); ?>" />
		</p>
	</form>
</div>
