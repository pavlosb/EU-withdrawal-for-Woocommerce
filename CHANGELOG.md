# Changelog

## 0.5.0 - Testing build

- Improved the confirmation step summary layout with a readable inner summary block.
- Improved the Withdrawal Requests admin list with clearer columns, filters, and nonce-protected workflow row actions.
- Added centered withdrawal form presentation with configurable safe before/after helper text and an optional plugin form heading.
- Added append-only admin settings for custom CSS classes on plugin-generated frontend and admin buttons.
- Aligned frontend and wp-admin action button classes with default WooCommerce and WordPress button styling.
- Fixed WooCommerce order status persistence for withdrawal submissions by using a storage-safe custom status slug and verifying the saved order status before recording success notes.
- Updated withdrawal emails to use the WooCommerce configured sender where available.
- Added customer-friendly reference codes for email/PDF receipts while preserving the internal proof hash for admin audit details.
- Skipped the simple PDF receipt attachment for Unicode text that the built-in PDF generator cannot safely render, leaving the HTML email receipt as the customer-facing fallback.
- Refactored the monolithic plugin file into focused include classes under `includes/`.
- Kept the main plugin file as a bootstrap that loads the class files and registers activation/deactivation hooks.
- Preserved existing shortcodes, settings, CPT/meta keys, WooCommerce hooks, multilingual labels, PDF receipts, and email behavior.

## 0.4.0 - Testing build

- Fixed withdrawal request emails so customer/admin notifications validate recipients, fall back to the site admin email, continue when PDF receipt generation fails, and log send failures.
- Added a safe settings-page test email action for checking the configured admin notification recipient.
- Direct Withdrawal page now starts with order number + billing email lookup.
- Products are fetched after verified order lookup.
- Added custom WooCommerce order status: **Withdrawal requested**.
- Added optional automatic order status change after withdrawal submission.
- Added request workflow status handling and internal note support.
- Improved standard WooCommerce/theme button classes.
- Reduced plugin CSS so frontend can inherit site styles.
- Added WPML config and non-translatable Withdrawal Requests setup.

## 0.3.0 - Testing build

- Added two-step confirmation.
- Added partial withdrawal quantities.
- Added guest order lookup.
- Added eligibility window and allowed order statuses.
- Added product, category and product type exclusions.
- Added admin workflow statuses.
- Added SHA-256 proof hash.
- Added basic PDF receipt.
- Added CSV export.
- Added `[eu_withdrawal_button]` shortcode.

## 0.2.0 - Testing build

- Added multilingual frontend labels and emails.
- Added English, Greek, Spanish and Hungarian support.
- Added WPML/Polylang language detection.
- Stored request language in admin records.

## 0.1.0 - Initial draft

- Added dedicated withdrawal page/shortcode.
- Added My Account/order action links.
- Added customer confirmation email.
- Added admin notification.
- Added basic admin request records.
