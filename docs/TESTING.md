# Testing checklist

Use this checklist on a staging WooCommerce site before installing the plugin on production.

## Environment

- WordPress current/stable version.
- WooCommerce active.
- Test with at least one default Storefront-like theme and one real client theme/page builder.
- Test with caching disabled first, then enabled.
- Test with WPML/Polylang if used by the site.

## Activation and settings

- Plugin activates without fatal errors.
- WooCommerce dependency notice works when WooCommerce is inactive.
- Withdrawal page is created or selectable.
- `[eu_withdrawal_form]` renders on the selected page.
- `[eu_withdrawal_button]` links to the selected page.
- CSS mode works:
  - Minimal structural CSS.
  - No frontend CSS.

## Direct page lookup

- Open the Withdrawal page directly.
- Form asks for order number and billing email.
- Wrong order/email combination does not expose products.
- Correct order/email combination loads order products.
- Ineligible order shows a clear message.
- Excluded products are not selectable or are clearly marked.

## Logged-in customer flow

- Customer sees withdrawal action in My Account > Orders.
- Button opens the withdrawal page with the correct order context.
- Products and quantities are loaded correctly.
- Partial withdrawal works.
- Two-step confirmation works.
- Submission creates exactly one request.

## Email flow

- Order confirmation email contains the withdrawal link when enabled.
- Customer confirmation email is sent after submission.
- Admin notification email is sent after submission.
- Email language matches the frontend language.
- PDF receipt is attached when enabled.

## Admin flow

- Request appears under WooCommerce > Withdrawals.
- Request details are accurate:
  - Customer name/email.
  - Order number.
  - Products/quantities.
  - Submitted timestamp.
  - Language.
  - Proof hash.
  - IP/user agent if enabled.
- Admin can change workflow status.
- Internal note can be saved.
- Related WooCommerce order link works.
- Order notes record relevant status changes.

## WooCommerce order status

- Custom order status **Withdrawal requested** appears in WooCommerce.
- Optional automatic status change works after submission.
- Previous order status is stored.
- Existing order processing/refund workflows are not broken.

## Multilingual / WPML / Polylang

### Baseline without a multilingual plugin

- Disable WPML and Polylang.
- Set plugin language to **Auto** and confirm the site locale controls frontend labels.
- Set plugin language explicitly to English, Greek, Spanish, and Hungarian, one at a time.
- Confirm `[eu_withdrawal_form]`, `[eu_withdrawal_button]`, My Account order actions, order details buttons, and WooCommerce email withdrawal links use the fixed plugin language fallback without fatal errors.
- Submit one request per fixed language and confirm `_ewb_language` stores the normalized language code used for the frontend/customer email.

### Shared page setup

- Create/translate the Withdrawal page for English, Greek, Spanish, and Hungarian.
- Add `[eu_withdrawal_form]` to each translated Withdrawal page.
- Select the default-language Withdrawal page in plugin settings.
- Confirm missing translated pages fall back safely to the configured default page instead of causing a fatal error or broken link.

### WPML-specific checks

- Activate WPML only.
- Confirm `ewb_withdrawal` / Withdrawal Requests remain non-translatable in WPML settings.
- Confirm WPML reads `wpml-config.xml` and treats request meta as copied audit data, not translated content.
- For English, Greek, Spanish, and Hungarian storefront languages, confirm the My Account > Orders withdrawal action URL points to the matching translated Withdrawal page and keeps `order_id`/`order_key` query args.
- For English, Greek, Spanish, and Hungarian storefront languages, confirm the order details withdrawal button URL points to the matching translated Withdrawal page and keeps `order_id`/`order_key` query args.
- For English, Greek, Spanish, and Hungarian order emails, confirm the WooCommerce email withdrawal link points to the matching translated Withdrawal page.
- For English, Greek, Spanish, and Hungarian direct Withdrawal pages, confirm order-number/billing-email lookup works and labels match the current language.
- For English, Greek, Spanish, and Hungarian guest lookup, confirm manual-product submission works and request language is stored.
- Submit a request from each storefront language and confirm customer confirmation email subject/body use that language, including configured multilingual templates when present.
- Confirm admin notification email uses the stored request/frontend language for translated labels and configured admin templates. This keeps the audit email tied to the customer-facing language.
- Confirm status, reference code, proof hash, order ID/number, products, submitted time, and language meta remain stable audit data across languages.

### Polylang-specific checks

- Activate Polylang only.
- Confirm Withdrawal Requests remain a non-translated/admin-only request record. If Polylang exposes the post type in settings, leave it disabled for translation.
- For English, Greek, Spanish, and Hungarian storefront languages, confirm the My Account > Orders withdrawal action URL points to the matching translated Withdrawal page and keeps `order_id`/`order_key` query args.
- For English, Greek, Spanish, and Hungarian storefront languages, confirm the order details withdrawal button URL points to the matching translated Withdrawal page and keeps `order_id`/`order_key` query args.
- For English, Greek, Spanish, and Hungarian order emails, confirm the WooCommerce email withdrawal link points to the matching translated Withdrawal page.
- For English, Greek, Spanish, and Hungarian direct Withdrawal pages, confirm order-number/billing-email lookup works and labels match the current language.
- For English, Greek, Spanish, and Hungarian guest lookup, confirm manual-product submission works and request language is stored.
- Submit a request from each storefront language and confirm customer confirmation email subject/body use that language, including configured multilingual templates when present.
- Confirm admin notification email uses the stored request/frontend language for translated labels and configured admin templates. This keeps the audit email tied to the customer-facing language.
- Confirm status, reference code, proof hash, order ID/number, products, submitted time, and language meta remain stable audit data across languages.

## CSS/theme compatibility

- Buttons inherit theme/WooCommerce styling.
- Inputs inherit theme styling.
- Layout does not break in Elementor/Divi blocks.
- No plugin CSS unexpectedly changes global site styles.
- Mobile layout is usable.

## Security/privacy

- Nonces are required for submission.
- Admin actions require proper capability.
- Guest lookup does not leak order details without correct email.
- Stored data is limited to what is necessary for the withdrawal record.
- CSV export is admin-only.
