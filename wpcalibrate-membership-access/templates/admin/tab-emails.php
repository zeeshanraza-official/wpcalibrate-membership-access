<?php
/**
 * Admin Emails Tab.
 *
 * @package WPCalibrate\MembershipAccess\Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables available:
 *
 * @var array $email_settings
 */

$email_settings = isset( $email_settings ) && is_array( $email_settings ) ? $email_settings : [];
?>
<div class="wpcal-section-card">
	<div class="wpcal-card-header">
		<h2><?php esc_html_e( 'Transactional Membership Emails', 'wpcalibrate-membership-access' ); ?></h2>
	</div>
	<p class="description" style="font-size: 14px; margin-bottom: 20px;">
		<?php esc_html_e( 'Customize automated email notifications sent to members. Use placeholders to dynamically insert membership details.', 'wpcalibrate-membership-access' ); ?>
		<br>
		<strong><?php esc_html_e( 'Supported Placeholders:', 'wpcalibrate-membership-access' ); ?></strong>
		<code>{user_name}</code>, <code>{plan_name}</code>, <code>{expiry_date}</code>, <code>{site_title}</code>, <code>{login_url}</code>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-membership-access&tab=emails' ) ); ?>">
		<?php wp_nonce_field( 'wpcal_save_email_settings_nonce', '_wpcal_nonce' ); ?>
		<input type="hidden" name="wpcal_action" value="save_email_settings" />

		<!-- 1. Membership Activated Email -->
		<div class="wpcal-email-box">
			<h3>
				<label>
					<input type="checkbox" name="activation_enabled" value="1" <?php checked( ! empty( $email_settings['activation_enabled'] ) ); ?> />
					<?php esc_html_e( 'Membership Activated Email', 'wpcalibrate-membership-access' ); ?>
				</label>
			</h3>
			<table class="form-table">
				<tr>
					<th scope="row"><label><?php esc_html_e( 'Email Subject', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="text" name="activation_subject" class="large-text" value="<?php echo esc_attr( $email_settings['activation_subject'] ?? '' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'Message Body', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<textarea name="activation_body" class="large-text" rows="4"><?php echo esc_textarea( $email_settings['activation_body'] ?? '' ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

		<!-- 2. Expiry Reminder Email -->
		<div class="wpcal-email-box">
			<h3>
				<label>
					<input type="checkbox" name="reminder_enabled" value="1" <?php checked( ! empty( $email_settings['reminder_enabled'] ) ); ?> />
					<?php esc_html_e( 'Membership Expiring Soon Reminder Email', 'wpcalibrate-membership-access' ); ?>
				</label>
			</h3>
			<table class="form-table">
				<tr>
					<th scope="row"><label><?php esc_html_e( 'Email Subject', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="text" name="reminder_subject" class="large-text" value="<?php echo esc_attr( $email_settings['reminder_subject'] ?? '' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'Message Body', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<textarea name="reminder_body" class="large-text" rows="4"><?php echo esc_textarea( $email_settings['reminder_body'] ?? '' ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

		<!-- 3. Expired Email -->
		<div class="wpcal-email-box">
			<h3>
				<label>
					<input type="checkbox" name="expired_enabled" value="1" <?php checked( ! empty( $email_settings['expired_enabled'] ) ); ?> />
					<?php esc_html_e( 'Membership Expired Notice Email', 'wpcalibrate-membership-access' ); ?>
				</label>
			</h3>
			<table class="form-table">
				<tr>
					<th scope="row"><label><?php esc_html_e( 'Email Subject', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="text" name="expired_subject" class="large-text" value="<?php echo esc_attr( $email_settings['expired_subject'] ?? '' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'Message Body', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<textarea name="expired_body" class="large-text" rows="4"><?php echo esc_textarea( $email_settings['expired_body'] ?? '' ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

		<!-- 4. Cancelled Email -->
		<div class="wpcal-email-box">
			<h3>
				<label>
					<input type="checkbox" name="cancelled_enabled" value="1" <?php checked( ! empty( $email_settings['cancelled_enabled'] ) ); ?> />
					<?php esc_html_e( 'Membership Cancelled Notice Email', 'wpcalibrate-membership-access' ); ?>
				</label>
			</h3>
			<table class="form-table">
				<tr>
					<th scope="row"><label><?php esc_html_e( 'Email Subject', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<input type="text" name="cancelled_subject" class="large-text" value="<?php echo esc_attr( $email_settings['cancelled_subject'] ?? '' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'Message Body', 'wpcalibrate-membership-access' ); ?></label></th>
					<td>
						<textarea name="cancelled_body" class="large-text" rows="4"><?php echo esc_textarea( $email_settings['cancelled_body'] ?? '' ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<input type="submit" class="wpcal-btn wpcal-btn-primary" value="<?php esc_attr_e( 'Save Email Settings', 'wpcalibrate-membership-access' ); ?>" />
		</p>
	</form>
</div>
