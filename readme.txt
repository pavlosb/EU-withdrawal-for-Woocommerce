=== EU Withdrawal Button for WooCommerce ===
Contributors: inline
Tags: woocommerce, withdrawal, returns, eu, wpml
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.5.0
License: GPLv2 or later

EU-style online withdrawal / cancel contract flow for WooCommerce.

== Description ==
Adds a multilingual online withdrawal form for WooCommerce orders, customer confirmation email, admin notification, admin workflow records, CSV export, PDF receipt, guest order lookup, eligibility/exclusion rules, and optional WooCommerce order status update to "Withdrawal requested".

== Changelog ==
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
