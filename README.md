# WPCalibrate Membership Access

[![WordPress](https://img.shields.io/badge/WordPress-6.5%20to%207.1.3-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%20to%2011.2.1-96588a.svg)](https://woocommerce.com/)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777bb4.svg)](https://php.net/)
[![HPOS Ready](https://img.shields.io/badge/WooCommerce-HPOS%20Compatible-green.svg)](https://woocommerce.com/document/high-performance-order-storage/)
[![Blocks Ready](https://img.shields.io/badge/WooCommerce-Blocks%20Ready-purple.svg)](https://woocommerce.com/document/cart-checkout-blocks-status/)
[![License](https://img.shields.io/badge/License-GPLv2%20or%20later-orange.svg)](LICENSE.txt)

A modular, enterprise-grade membership management and content restriction plugin for **WordPress** and **WooCommerce**. Designed for high-volume sites, digital publications, educational academies, and e-commerce stores, **WPCalibrate Membership Access** delivers complete access governance, real-time lifecycle enforcement, and frictionless dashboard updates.

---

## Table of Contents
- [About](#about)
- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Docs & Operational Guide](#docs--operational-guide)
  - [1. Creating Membership Plans](#1-creating-membership-plans)
  - [2. Restricting Content & Access Precedence](#2-restricting-content--access-precedence)
  - [3. Shortcodes & Gutenberg Block Reference](#3-shortcodes--gutenberg-block-reference)
  - [4. WooCommerce Monetization & HPOS](#4-woocommerce-monetization--hpos)
  - [5. Lifecycle Management & Real-Time Expiration](#5-lifecycle-management--real-time-expiration)
  - [6. Audit Trail & GDPR Compliance](#6-audit-trail--gdpr-compliance)
  - [7. Developer Hooks & API](#7-developer-hooks--api)
  - [8. Automatic Dashboard Updates](#8-automatic-dashboard-updates)
- [Changelog](#changelog)
- [Support & Community](#support--community)
- [License](#license)

---

## About

**WPCalibrate Membership Access** is engineered to eliminate the performance bottlenecks, race conditions, and unauthorized caching leaks commonly found in legacy membership plugins. Built from the ground up on modern PHP 8.2+ object-oriented principles, it provides authoritative access decisions with zero external Composer runtime dependencies.

### Core Architecture Highlights:
* **CPT-Backed Plan Modeling**: Plans are stored natively in non-public Custom Post Types (`wpcal_member_plan`) with strongly-typed domain model wrappers.
* **Dedicated High-Performance Tables**: Member assignments and immutable audit histories reside in custom indexed MySQL tables (`wpcalibrate_memberships` and `wpcalibrate_membership_audit`), keeping WordPress's core options and postmeta tables clean.
* **Deterministic Rule Precedence**: Authorization decisions resolve through a strict 3-tier precedence pipeline (`Global Precedence` &rarr; `Content Restrictions` &rarr; `Plan Entitlements`), preventing conflicting permissions or unauthorized access.
* **Real-Time Expiry Engine**: Access is verified at the moment of request. If an account has expired, access is revoked immediately, even if Action Scheduler or WP-Cron hasn't fired yet.
* **Native WooCommerce & HPOS Integration**: Seamlessly monetize tiers with standard WooCommerce products, automatic order fulfillment, and full compatibility with High-Performance Order Storage (HPOS).

---

## Features

### 🛡️ Deterministic Access Control
* **Flexible Visibility Modes**: Restrict entire posts, pages, or custom post types by guest status, logged-in status, non-members, or specific active membership plans.
* **Granular Protection Options**: Show a customizable restricted-content excerpt, hide content entirely, or redirect unauthorized visitors to a login or sales page.
* **Loop & Feed Protection**: Automatically filters protected content from RSS/Atom feeds, REST API endpoints, search archives, and related post loops.
* **Gutenberg Block & Shortcodes**: Protect partial page content with the `[wpcalibrate_members_only]` shortcode or the visual Gutenberg block.

### 💳 Monetization & WooCommerce Integration
* **Product-Linked Plans**: Link one or multiple membership plans to standard simple or variable WooCommerce products.
* **Instant Order Fulfillment**: Automatically grant membership access as soon as an order status transitions to *Processing* or *Completed*.
* **Idempotent Order Handling**: Prevents duplicate plan assignments for repeated webhooks or order status modifications.
* **WooCommerce Subscriptions**: Full lifecycle synchronization — activates on subscription payment, suspends on on-hold, and cancels upon expiration.

### 👥 Member Account Dashboard & Self-Service
* **Front-End Account Hub**: Embed a responsive member portal using `[wpcalibrate_member_account]` where users view plan status, renewal dates, and accessible content.
* **Custom Registration Routing**: Option to assign default introductory tiers upon registration or redirect members to targeted landing pages.

### ⚡ Operational Administration
* **Centralized WPCalibrate Suite Menu**: Shared parent admin dashboard with dedicated submenus for Plans, Members, Access Rules, Email Alerts, WooCommerce, Settings, License, and Support.
* **Overview Tab Architecture**: Live metrics overview (Active, Expiring Soon, Expired, Total Plans) accompanied by operational workflow diagrams and shortcode guides.
* **Secure CSV Member Export**: Stream high-volume member data with built-in CSV formula injection protection (neutralizing `=`, `+`, `-`, `@`).
* **GDPR Personal Data Compliance**: Native hooks for WordPress Personal Data Export and Personal Data Erasure tools.
* **Immutable Audit Trail**: Chronological, encrypted logging of all membership status changes, renewals, and administrative actions.

---

## System Requirements

| Component | Minimum Version | Recommended / Tested |
| :--- | :--- | :--- |
| **WordPress** | 6.5 | **7.1.3** |
| **PHP** | 8.2 | **8.3+** |
| **MySQL / MariaDB** | MySQL 5.7 / MariaDB 10.4 | MySQL 8.0+ / MariaDB 10.6+ |
| **WooCommerce** *(Optional)* | 8.0 | **11.2.1** (HPOS Enabled) |

---

## Installation

### Method 1: WordPress Dashboard (Recommended)
1. Download the latest release package `wpcalibrate-membership-access-1.0.0.zip` from [GitHub Releases](https://github.com/zeeshanraza-official/wpcalibrate-membership-access/releases).
2. In your WordPress admin dashboard, navigate to **Plugins &rarr; Add New &rarr; Upload Plugin**.
3. Choose the downloaded ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

### Method 2: Manual SFTP / FTP Installation
1. Extract `wpcalibrate-membership-access-1.0.0.zip` locally.
2. Upload the `wpcalibrate-membership-access` folder to your server's `/wp-content/plugins/` directory.
3. Navigate to **Plugins &rarr; Installed Plugins** in WordPress and activate **WPCalibrate Membership Access**.

---

## Docs & Operational Guide

### 1. Creating Membership Plans
1. Navigate to **WPCalibrate &rarr; Membership Access &rarr; Plans**.
2. Click **+ New Plan**.
3. Configure the plan parameters:
   * **Plan Name & Slug**: Internal title and identifier.
   * **Duration Type**: Choose between *Lifetime* (never expires) or *Fixed Duration* (specify days, weeks, months, or years).
   * **Registration Eligibility**: Enable if visitors should automatically receive this plan upon free site registration.
   * **Priority Weight**: Used when a member holds multiple tiers to resolve highest entitlement precedence.
   * **Linked WooCommerce Products**: Select WooCommerce products that grant this tier upon purchase.
4. Click **Publish Plan**.

### 2. Restricting Content & Access Precedence
Access is evaluated deterministically in three sequential phases:

```
[ Visitor Request ]
        │
        ▼
[ Layer 1: Global Precedence ] ──► Administrator or 'manage_options'? ──► Access Granted
        │
        ▼
[ Layer 2: Content Rules ] ────► Is rule Public? ────────────────────────► Access Granted
        │                       ► Is rule Logged-In Only & guest? ───────► Access Denied
        ▼
[ Layer 3: Plan Evaluation ] ──► Does user have matching plan?
                                 AND is status == 'active'?
                                 AND is expires_at in the future? ───────► Access Granted
                                 Otherwise ──────────────────────────────► Access Denied
```

To restrict any post, page, or custom post type:
* Open the editor and locate the **WPCalibrate Access Rules** sidebar panel or metabox.
* Select the restriction rule: *Everyone*, *Logged-in Users Only*, *Non-Members Only*, or *Specific Membership Plans*.
* Choose unauthorized fallback: *Show Restricted Message*, *Hide from Loop*, or *Redirect to URL*.

### 3. Shortcodes & Gutenberg Block Reference

#### `[wpcalibrate_member_account]`
Renders the self-service member account portal. Displays active subscriptions, renewal dates, expiry notifications, and quick links to restricted member content.
```html
[wpcalibrate_member_account]
```

#### `[wpcalibrate_members_only]`
Wraps and restricts specific portions of content within any post or page:
```html
[wpcalibrate_members_only plan="vip-all-access,premium-monthly" fallback="Please upgrade your account to view this download."]
    <p>Exclusive downloads and premium resources go here.</p>
[/wpcalibrate_members_only]
```

### 4. WooCommerce Monetization & HPOS
1. Ensure **WooCommerce** is activated.
2. Edit any WooCommerce product (or variation) and navigate to the **Membership Access** tab in Product Data.
3. Select the target plan to grant upon successful checkout.
4. When a customer purchases the product, access is granted automatically upon order completion. If WooCommerce Subscriptions is installed, subscription expirations and cancellations automatically update the member's status.

### 5. Lifecycle Management & Real-Time Expiration
Member records transition through strict lifecycle states:
* `pending`: Awaiting payment confirmation or manual approval.
* `active`: Fully entitled to restricted content.
* `suspended`: Temporarily paused (e.g. subscription payment failed).
* `expired`: Duration exceeded; access is immediately denied.
* `cancelled`: Terminated by administrator or customer.

### 6. Audit Trail & GDPR Compliance
* **Immutable Audit Log**: Every assignment, status transition, renewal, and expiration is logged with timestamp, user ID, actor ID, and metadata context.
* **GDPR Export**: User membership and audit histories are integrated into WordPress's native **Tools &rarr; Export Personal Data**.
* **GDPR Erasure**: Integrates with **Tools &rarr; Erase Personal Data** while preserving anonymized aggregate record integrity.

### 7. Developer Hooks & API

#### Filter Required Management Capability:
```php
add_filter( 'wpcalibrate_membership_access_capability', function( $capability ) {
    return 'edit_posts'; // Allow editors to manage memberships
} );
```

#### Programmatically Grant Membership:
```php
$service = \WPCalibrate\MembershipAccess\Plugin::get_instance()->get_membership_service();
$membership = $service->create_membership( [
    'user_id' => 42,
    'plan_id' => 105,
    'status'  => 'active',
    'source'  => 'custom_integration',
] );
```

### 8. Automatic Dashboard Updates

**WPCalibrate Membership Access** includes an integrated update engine ([`GitHubUpdater`](src/Updates/GitHubUpdater.php)) that connects directly to this public repository's GitHub Releases:

1. When a new release tag (e.g. `v1.0.1`) is published on GitHub, WordPress automatically detects the update within 6 hours (or immediately upon visiting **Dashboard &rarr; Updates**).
2. An update banner will appear under **Plugins** in your WordPress admin:
   > *There is a new version of WPCalibrate Membership Access available. View version 1.0.1 details or update now.*
3. Clicking **Update Now** automatically downloads the verified release ZIP package and installs it in place with zero manual file transfers.

---

## Changelog

### [1.0.0] - 2026-10-16
#### Initial Public Release
* **CPT-Backed Plan Modeling**: Stored natively in `wpcal_member_plan` custom post type.
* **Dedicated Custom Tables**: `wpcalibrate_memberships` and `wpcalibrate_membership_audit`.
* **Deterministic Access Engine**: Strict 3-tier precedence pipeline for all WordPress content queries, feeds, and REST routes.
* **WooCommerce Monetization**: HPOS-compatible automated order fulfillment and WooCommerce Subscriptions synchronization.
* **Frontend Components**: `[wpcalibrate_member_account]` dashboard shortcode, `[wpcalibrate_members_only]` wrapper, and visual Gutenberg block.
* **Real-Time Expiry**: Immediate access revocation upon expiration date with Action Scheduler automated cleanup.
* **Official Branding**: Crisp monochrome SVG vector sidebar menu icon and high-resolution header badge.
* **Integrated GitHub Updater**: Seamless one-click updates directly from the WordPress dashboard via GitHub Releases.
* **Quality Assurance**: 33 automated tests passing with 0 errors; 52 PHP files verified with syntax linting.

---

## Support & Community

* **Website**: [https://wpcalibrate.com](https://wpcalibrate.com)
* **Marketplace**: [https://marketplace.wpcalibrate.com](https://marketplace.wpcalibrate.com)
* **Email Support**: [support@wpcalibrate.com](mailto:support@wpcalibrate.com)
* **Phone / WhatsApp**: [+447474795976](https://wa.me/447474795976)

---

## License

This plugin is free software, licensed under the terms of the **GNU General Public License Version 2** (or later). See [`LICENSE.txt`](LICENSE.txt) for the complete license text.
