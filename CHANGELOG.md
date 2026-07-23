# Changelog

## 0.5.6 - Testing build

### New / improved

- Added guest/email lookup enforcement for role-based withdrawal restrictions when the entered email belongs to an existing WordPress user account.
- Added a separate customizable multilingual message and CSS class for email-matched accounts that are not eligible by role.
- Added a dedicated CSS class for the non-eligible order notice.
- Added backend customization for the non-eligible order message.
- Preserved multilingual/default fallback behavior for the non-eligible message.
- Hid the normal post-submission helper text when the order is not eligible, avoiding conflicting instructions.

### Styling

- Site admins can now target the non-eligible notice with custom CSS, for example `.ewb-notice--not-eligible`.

### Compatibility

- No change to withdrawal eligibility rules.
- No change to role-based withdrawal availability.
- No change to email templates.
- No change to PDF behavior.
- No change to privacy export/erase behavior.
- No change to WPML/Polylang behavior except message fallback support.

## 0.5.5 - Testing build

### New feature

- Added role-based withdrawal availability settings.
- Merchants can now choose whether:
  - all roles can withdraw,
  - only selected roles can withdraw,
  - or selected roles are excluded from withdrawal.
- Supports custom WordPress/WooCommerce roles such as registered partner, B2B, wholesale or partner-type roles.

### Frontend behavior

- Withdrawal button can be hidden from My Account > Orders for disallowed roles.
- Withdrawal button can be hidden from order details for disallowed roles.
- Shortcode withdrawal buttons respect the same role restrictions.
- Direct form access/submission is blocked for disallowed roles.

### Improvements

- Preserves default behavior after upgrade.
- Guest behavior remains handled separately according to existing settings.
- Role settings are sanitized and validated.
- Admin/shop manager workflow remains unaffected.

## 0.5.4 - Testing build

### New features

- Added role-based withdrawal availability settings for controlling logged-in customer access by WordPress/WooCommerce role.
- Added configurable multilingual customer and admin email templates.
- Added WordPress Privacy Tools integration:
  - Personal Data Exporter.
  - Personal Data Eraser / anonymization support.
- Improved WPML and Polylang compatibility.
- Improved multilingual language detection and translated Withdrawal page URL handling.
- Added multilingual compatibility testing documentation.

### Improvements

- Preserved configurable customer-facing reference codes.
- Preserved configurable email placeholders.
- Preserved WooCommerce sender integration.
- Preserved Unicode PDF fallback behavior.
- Preserved privacy-safe audit trail.
- Preserved existing order workflow, admin workflow, and request integrity.

### Compatibility

- WordPress Privacy Tools.
- WooCommerce.
- WPML.
- Polylang.
- PHP 7.4+.

## 0.5.3 - Testing build

- Improved WPML/Polylang compatibility for translated Withdrawal page URLs in email links and documented multilingual test coverage.
- Added WordPress personal data exporter and eraser/anonymizer support for EU Withdrawal Requests.
- Added configurable multilingual customer and admin email templates with safe placeholders and sanitized basic HTML output.
- Moved plugin settings to the new standard settings location implemented in the latest merged PR.
- Added GitHub Actions workflow to build distributable plugin ZIP artifacts.
- Preserved existing option keys, saved settings, frontend behavior, emails, PDF fallback, order status behavior, admin workflow, menu badge, custom classes, and action label behavior.

## 0.5.2 - Testing build

- Moved plugin settings to WooCommerce > Settings > EU Withdrawal while keeping the old settings URL as a redirect.
- Added pending/actionable withdrawal count badge to the WooCommerce > Withdrawals admin submenu.
- Added admin setting for customizing the customer-facing withdrawal action label/title.
- Preserved existing multilingual default labels when the custom action label setting is empty.
- Preserved existing shortcode/button behavior and custom button class settings.

## 0.5.1 - Testing build

- Updated withdrawal emails to use the WooCommerce configured sender where available.
- Added customer-friendly reference codes for customer email/PDF receipts while preserving the internal proof hash for admin audit details.
- Added a Greek/Unicode PDF receipt fallback that skips unsafe simple PDF attachments and keeps the HTML email receipt readable.
- Fixed WooCommerce order status persistence by using a storage-safe custom status slug and verifying the saved status before recording success notes.
- Aligned frontend WooCommerce buttons and wp-admin action links with default WooCommerce/WordPress button classes.
- Added admin settings for append-only custom CSS classes on plugin-generated frontend and admin buttons.
- Added centered withdrawal form presentation, a plugin heading toggle, and configurable before/after helper text.
- Improved the confirmation step customer/order/product/declaration summary layout.
- Improved the Withdrawal Requests admin list with clearer columns, practical filters, and nonce-protected workflow row actions.

## 0.5.0 - Testing build

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
- Added guest lookup.
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
