# Testing checklist

Use staging first. Do not test with real customer data in public screenshots/issues.

## Environment

Record before testing:

- WordPress version
- WooCommerce version
- PHP version
- Theme/page builder
- WPML/Polylang enabled or not
- Payment/shipping plugins that affect orders
- Cache plugin/CDN status

## Basic activation

- Activate plugin without fatal errors.
- Confirm WooCommerce dependency notice if WooCommerce is inactive.
- Confirm Withdrawal page exists and contains `[eu_withdrawal_form]`.
- Confirm settings page exists under WooCommerce > Withdrawal Settings.
- Run PHP syntax check:

```bash
php -l eu-withdrawal-button.php
```

## Frontend flows

### Logged-in customer from My Account

- Create test order for a logged-in customer.
- Open My Account > Orders.
- Click Withdrawal / Cancel Contract.
- Confirm order details and eligible products appear.
- Select one product and partial quantity.
- Confirm two-step review screen.
- Submit.
- Confirm success message.
- Confirm customer email received.
- Confirm admin email received.
- Confirm Withdrawal Request is visible in admin.
- Confirm WooCommerce order status changes to Withdrawal requested if enabled.

### Direct Withdrawal page / guest lookup

- Open Withdrawal page directly.
- Confirm form starts with order number + billing email lookup.
- Try wrong email and confirm it does not expose products.
- Try correct order number + billing email.
- Confirm eligible products load.
- Submit request.

### Order confirmation / order email link

- Place test order.
- Open customer order email.
- Click withdrawal link.
- Confirm correct language/page URL if WPML/Polylang is active.
- Confirm products are fetched after verification or token/order context.

## Eligibility rules

- Test within default 14-day window.
- Test outside eligibility window.
- Test allowed order statuses.
- Test disabled statuses.
- Test excluded product IDs.
- Test excluded categories.
- Test virtual/downloadable/external products.

## Admin workflow

- Open WooCommerce > Withdrawals.
- Open a request.
- Confirm customer/order/product data is visible.
- Change workflow status to In Review, Approved, Rejected, Completed, Refunded.
- Add internal note.
- Confirm WooCommerce order note is created when workflow status changes.
- Confirm CSV export works.

## Multilingual / WPML

- Confirm Withdrawal Requests are not translated.
- Confirm request meta is copied, not translated.
- Test English labels.
- Test Greek labels.
- Test Spanish labels.
- Test Hungarian labels.
- Confirm translated Withdrawal page URLs work.
- Confirm customer email language matches frontend language.

## CSS / theme compatibility

- Test with Minimal CSS.
- Test with No frontend CSS.
- Confirm buttons inherit WooCommerce/theme styling.
- Confirm layout is acceptable in Elementor/Divi pages.
- Confirm no plugin CSS breaks product/account/checkout pages.

## Security/privacy

- Confirm nonces are required for submission.
- Confirm guest lookup requires both order number and billing email.
- Confirm direct access does not expose order data.
- Confirm admin actions require proper capability.
- Confirm no real personal data is committed to repo/issues.
