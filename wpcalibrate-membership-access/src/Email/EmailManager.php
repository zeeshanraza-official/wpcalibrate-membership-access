<?php
/**
 * Transactional Email Manager.
 *
 * Dispatches membership lifecycle emails with customizable templates and safe placeholders.
 *
 * @package WPCalibrate\MembershipAccess\Email
 */

namespace WPCalibrate\MembershipAccess\Email;

use WPCalibrate\MembershipAccess\Membership\Membership;
use WPCalibrate\MembershipAccess\Membership\Plan;

defined( 'ABSPATH' ) || exit;

class EmailManager {

	public const OPTION_NAME = 'wpcalibrate_membership_access_email_settings';

	public function get_settings(): array {
		$defaults = [
			'activation_enabled' => true,
			'activation_subject' => __( 'Your membership to {site_title} is now active!', 'wpcalibrate-membership-access' ),
			'activation_body'    => __( "Hello {user_name},\n\nYour membership for \"{plan_name}\" has been successfully activated on {site_title}.\n\nAccess Details:\n- Plan: {plan_name}\n- Expires: {expiry_date}\n\nYou can log in and view your account here:\n{login_url}\n\nThank you,\n{site_title}", 'wpcalibrate-membership-access' ),

			'reminder_enabled'   => true,
			'reminder_subject'   => __( 'Reminder: Your membership on {site_title} is expiring soon', 'wpcalibrate-membership-access' ),
			'reminder_body'      => __( "Hello {user_name},\n\nThis is a quick reminder that your \"{plan_name}\" membership on {site_title} will expire on {expiry_date}.\n\nTo ensure uninterrupted access to protected content, please renew your membership before it expires.\n\nAccount Login:\n{login_url}\n\nBest regards,\n{site_title}", 'wpcalibrate-membership-access' ),

			'expired_enabled'    => true,
			'expired_subject'    => __( 'Your membership on {site_title} has expired', 'wpcalibrate-membership-access' ),
			'expired_body'       => __( "Hello {user_name},\n\nYour membership for \"{plan_name}\" on {site_title} expired on {expiry_date}.\n\nYou can renew or upgrade your access anytime by logging into your account:\n{login_url}\n\nBest regards,\n{site_title}", 'wpcalibrate-membership-access' ),

			'cancelled_enabled'  => true,
			'cancelled_subject'  => __( 'Your membership on {site_title} has been cancelled', 'wpcalibrate-membership-access' ),
			'cancelled_body'     => __( "Hello {user_name},\n\nYour membership for \"{plan_name}\" on {site_title} has been cancelled.\n\nIf you have any questions, please contact our support team.\n\nAccount Login:\n{login_url}\n\nBest regards,\n{site_title}", 'wpcalibrate-membership-access' ),
		];

		$saved = get_option( self::OPTION_NAME, [] );
		return is_array( $saved ) ? array_merge( $defaults, $saved ) : $defaults;
	}

	public function send_activation_email( Membership $membership, Plan $plan ): bool {
		$settings = $this->get_settings();
		if ( empty( $settings['activation_enabled'] ) ) {
			return false;
		}

		return $this->dispatch_email(
			$membership,
			$plan,
			$settings['activation_subject'],
			$settings['activation_body']
		);
	}

	public function send_expiry_reminder_email( Membership $membership, Plan $plan ): bool {
		$settings = $this->get_settings();
		if ( empty( $settings['reminder_enabled'] ) ) {
			return false;
		}

		return $this->dispatch_email(
			$membership,
			$plan,
			$settings['reminder_subject'],
			$settings['reminder_body']
		);
	}

	public function send_expired_email( Membership $membership, Plan $plan ): bool {
		$settings = $this->get_settings();
		if ( empty( $settings['expired_enabled'] ) ) {
			return false;
		}

		return $this->dispatch_email(
			$membership,
			$plan,
			$settings['expired_subject'],
			$settings['expired_body']
		);
	}

	public function send_cancelled_email( Membership $membership, Plan $plan ): bool {
		$settings = $this->get_settings();
		if ( empty( $settings['cancelled_enabled'] ) ) {
			return false;
		}

		return $this->dispatch_email(
			$membership,
			$plan,
			$settings['cancelled_subject'],
			$settings['cancelled_body']
		);
	}

	private function dispatch_email( Membership $membership, Plan $plan, string $subject_tpl, string $body_tpl ): bool {
		$user = get_userdata( $membership->get_user_id() );
		if ( ! $user || empty( $user->user_email ) ) {
			return false;
		}

		$expiry_str = $membership->is_lifetime()
			? __( 'Lifetime Access', 'wpcalibrate-membership-access' )
			: ( $membership->get_expires_at() ?: __( 'N/A', 'wpcalibrate-membership-access' ) );

		$placeholders = [
			'{user_name}'   => $user->display_name ?: $user->user_login,
			'{plan_name}'   => $plan->get_name(),
			'{expiry_date}' => $expiry_str,
			'{site_title}'  => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'{login_url}'   => wp_login_url(),
		];

		$subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $subject_tpl );
		$body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $body_tpl );

		$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

		return wp_mail( $user->user_email, $subject, $body, $headers );
	}
}
