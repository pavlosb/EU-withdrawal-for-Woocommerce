# EU Withdrawal Button for WooCommerce

Public WordPress/WooCommerce plugin for implementing an online EU-style withdrawal / cancel contract flow.

The plugin adds a customer-facing withdrawal form, WooCommerce order-aware product selection, guest order lookup, admin request records, multilingual labels, email acknowledgements, proof hash, optional PDF receipt, CSV export, and optional WooCommerce order status update to **Withdrawal requested**.

> Legal note: this plugin helps implement an online withdrawal process. It is not legal advice. Final wording, exclusions and refund/return rules should be reviewed by the shop owner's legal advisor for each jurisdiction and product category.

## Current version

`0.5.3` testing build.

## Main features

- WooCommerce order-aware withdrawal form.
- Direct page flow with order number + billing email lookup.
- Partial withdrawal by product and quantity.
- Guest customer flow.
- Two-step confirmation before submission.
- Eligibility window setting, default 14 days.
- Allowed order statuses setting.
- Product/category/product type exclusions.
- Customer confirmation email.
- Admin notification email.
- Admin request records under WooCommerce > Withdrawals.
- Workflow statuses: Submitted, In Review, Approved, Rejected, Completed, Refunded.
- Optional WooCommerce order status change to **Withdrawal requested**.
- SHA-256 proof hash for each request.
- Basic PDF receipt attachment.
- CSV export.
- Multilingual frontend labels: English, Greek, Spanish, Hungarian.
- WPML/Polylang language detection and WPML config.
- Minimal frontend CSS with option to inherit theme/WooCommerce styling.

## Installation

1. Copy this repository folder to `wp-content/plugins/eu-withdrawal-button/`.
2. Activate **EU Withdrawal Button for WooCommerce** from WordPress admin.
3. Go to **WooCommerce > Withdrawal Settings**.
4. Confirm or create the Withdrawal page containing:

   ```text
   [eu_withdrawal_form]
   ```

5. Add a visible link to the page in the footer/menu/policy area.
6. Test order email links, My Account links, direct lookup flow and admin request handling on staging.

## Shortcodes

```text
[eu_withdrawal_form]
```

Displays the withdrawal flow.

```text
[eu_withdrawal_button]
```

Displays a theme/WooCommerce-style button linking to the configured withdrawal page.

## Styling approach

The plugin should not impose a strong visual design. It should follow the site's WooCommerce/theme CSS as much as possible.

Relevant setting:

- **Minimal structural CSS, inherit site/theme styles**
- **No frontend CSS**

Buttons should use standard WooCommerce/button classes where possible:

```text
woocommerce-button button
woocommerce-button button alt
```

## WPML / multilingual notes

- Customer-facing languages currently supported: `en`, `el`, `es`, `hu`.
- Withdrawal Requests should not be translated.
- Request meta should be copied.
- `wpml-config.xml` is included.
- WPML/Polylang translated page URL handling should be tested per site.

## Development

Basic syntax check:

```bash
php -l eu-withdrawal-button.php
```

Build plugin zip from repository root:

```bash
mkdir -p build/eu-withdrawal-button
cp eu-withdrawal-button.php readme.txt wpml-config.xml build/eu-withdrawal-button/
cp -R includes build/eu-withdrawal-button/
cd build
zip -r eu-withdrawal-button.zip eu-withdrawal-button
```

## Testing priorities

See [`docs/TESTING.md`](docs/TESTING.md).

## Roadmap

See [`docs/ROADMAP.md`](docs/ROADMAP.md).
