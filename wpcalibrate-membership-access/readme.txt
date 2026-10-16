=== WPCalibrate Membership Access ===
Contributors: wpcalibrate
Tags: membership, access control, restrict content, woocommerce, members
Requires at least: 6.2
Tested up to: 7.1.3
Requires PHP: 8.2
WC requires at least: 8.0
WC tested up to: 11.2.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise WordPress plugin for creating membership plans, assigning memberships, restricting content, and selling memberships with WooCommerce.

== Description ==

WPCalibrate Membership Access provides a fast, robust, and extensible membership infrastructure for WordPress sites. Build unlimited membership plans, grant access manually or upon user registration, restrict posts, pages, custom post types, categories, and WooCommerce products, and provide clean account management interfaces for your members.

= Key Features =

* **Unlimited Membership Plans**: Configure lifetime access or fixed durations (days, weeks, months, years) with intuitive admin controls.
* **Granular Content Restriction**: Protect entire posts, pages, custom post types, or taxonomy categories.
* **Gutenberg Protected Content Block**: Insert a container block that renders protected content server-side, guaranteeing zero client-side leakage to unauthorized visitors.
* **Shortcode Protection**: Use `[wpcalibrate_members_only]` with plan ID, any member, or logged-in options and custom fallback messaging.
* **Central Access Engine**: Deterministic rule precedence (Post Meta > Taxonomy Category > Global Settings) prevents privilege escalation and ensures fast in-memory execution.
* **Real-time Expiry Enforcement**: Memberships past their expiration date deny access immediately, independent of when background cron tasks fire.
* **WooCommerce Integration (Optional)**: Connect products to membership plans. Eligible paid orders grant access automatically. Full High-Performance Order Storage (HPOS) and Cart/Checkout Blocks compatibility.
* **WooCommerce Subscriptions Ready**: Automatically synchronizes recurring renewals, suspensions, cancellations, and reactivations when WooCommerce Subscriptions is active.
* **Member Account Dashboard**: Front-end account interface via `[wpcalibrate_member_account]` and seamless WooCommerce My Account endpoint.
* **Transactional Email Notifications**: Automated emails for activation, upcoming expiry reminders (default 7 days), expiration, and cancellation with safe template placeholders.
* **Administrator Management & Audit Trail**: Search, filter, paginate, and edit memberships with complete transactional history logging.
* **Safe CSV Export**: Stream members to CSV with formula injection sanitization protection.
* **Privacy & Data Retention**: Full integration with WordPress personal data exporter and eraser. Configurable data retention upon uninstallation.

== Installation ==

1. Upload the `wpcalibrate-membership-access` folder to the `/wp-content/plugins/` directory, or install the ZIP through the WordPress admin plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **WPCalibrate > Membership Access** to configure your plans and access settings.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce to work? =
No. WooCommerce is completely optional. The plugin functions as a full-featured standalone membership and content-protection system with free or administrator-assigned memberships.

= What happens when a fixed-duration membership reaches its expiry date? =
The central AccessManager enforces expiry in real time. If `expires_at` is in the past, access to protected resources is denied immediately, even before the background normalization job runs.

= Are custom tables used? =
Yes. User memberships and audit history are stored in dedicated custom tables (`wpcalibrate_memberships` and `wpcalibrate_membership_audit`) with optimal database indexing for high performance. Membership plans are stored natively in a non-public custom post type (`wpcal_member_plan`).

= Is WooCommerce HPOS supported? =
Yes. High-Performance Order Storage (HPOS) compatibility is verified and officially declared.

= What hooks and filters are available? =
* `wpcalibrate_membership_access_user_can_access` - Filter access decisions.
* `wpcalibrate_membership_access_membership_created` - Action fired when a membership is created.
* `wpcalibrate_membership_access_membership_activated` - Action fired upon activation.
* `wpcalibrate_membership_access_membership_status_changed` - Action fired upon any status change.
* `wpcalibrate_membership_access_membership_renewed` - Action fired upon renewal.
* `wpcalibrate_membership_access_redirect_url` - Filter unauthorized redirect URL.

== Changelog ==

= 1.0.0 =
* Initial production release.
