=== EU Withdrawal Button for WooCommerce ===
Contributors: inline
Tags: woocommerce, withdrawal, returns, eu, wpml
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.5.4
License: GPLv2 or later

EU-style online withdrawal / cancel contract flow for WooCommerce.

== Description ==
Adds a multilingual online withdrawal form for WooCommerce orders, customer confirmation email, admin notification, admin workflow records, CSV export, PDF receipt, guest order lookup, eligibility/exclusion rules, and optional WooCommerce order status update to "Withdrawal requested".

== Changelog ==
= 0.5.4 =
* New features: configurable multilingual customer and admin email templates; WordPress Privacy Tools Personal Data Exporter and Eraser/anonymization support; improved WPML and Polylang compatibility; improved multilingual language detection and translated Withdrawal page URL handling; multilingual compatibility testing documentation.
* Improvements: preserved configurable customer-facing reference codes, configurable email placeholders, WooCommerce sender integration, Unicode PDF fallback behavior, privacy-safe audit trail, and existing order workflow, admin workflow, and request integrity.
* Compatibility: WordPress Privacy Tools, WooCommerce, WPML, Polylang, and PHP 7.4+.

= 0.5.3 =
* Moved plugin settings to the new standard settings location implemented in the latest merged PR.
* Added GitHub Actions workflow to build distributable plugin ZIP artifacts.
* Preserved existing option keys, saved settings, frontend behavior, emails, PDF fallback, order status behavior, admin workflow, menu badge, custom classes, and action label behavior.

= 0.5.2 =
* Added pending/actionable withdrawal count badge to the WooCommerce > Withdrawals admin submenu.
* Added admin setting for customizing the customer-facing withdrawal action label/title.
* Preserved existing multilingual default labels when the custom action label setting is empty.
* Preserved existing shortcode/button behavior and custom button class settings.

= 0.5.1 =
* Updated withdrawal emails to use the WooCommerce configured sender where available.
* Added customer-friendly reference codes for customer email/PDF receipts while preserving the internal proof hash for admin audit details.
* Added a Greek/Unicode PDF receipt fallback that skips unsafe simple PDF attachments and keeps the HTML email receipt readable.
* Fixed WooCommerce order status persistence by using a storage-safe custom status slug and verifying the saved status before recording success notes.
* Aligned frontend WooCommerce buttons and wp-admin action links with default WooCommerce/WordPress button classes.
* Added admin settings for append-only custom CSS classes on plugin-generated frontend and admin buttons.
* Added centered withdrawal form presentation, a plugin heading toggle, and configurable before/after helper text.
* Improved the confirmation step customer/order/product/declaration summary layout.
* Improved the Withdrawal Requests admin list with clearer columns, practical filters, and nonce-protected workflow row actions.

= 0.5.0 =
* Refactored plugin internals into focused include classes without changing user-facing behavior.

= 0.4.0 =
* Direct Withdrawal page now starts with order number + billing email lookup, so products are fetched after verification instead of showing a manual product textarea.
* Added custom WooCommerce order status: Withdrawal requested.
* Optional automatic order status change after request submission.
* Improved WooCommerce/theming button classes and minimal CSS approach.
* Improved WPML/Polylang page URL handling.
* Added wpml-config.xml marking Withdrawal Requests as not translatable and request meta as copy.

= 0.3.0 =
* Added two-step confirmation, partial quantities, guest lookup, eligibility rules, excluded products/categories/types, PDF receipt, SHA-256 hash, CSV export, admin workflow statuses.

= 0.2.0 =
* Added English, Greek, Spanish and Hungarian customer-facing labels.

= 0.1.0 =
* Initial version.
