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

## Multilingual / WPML

- English frontend labels work.
- Greek frontend labels work.
- Spanish frontend labels work.
- Hungarian frontend labels work.
- WPML language URL resolves to the translated/current language page.
- Withdrawal Requests are not translatable.
- Request meta is copied, not translated.

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
