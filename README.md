# EU Withdrawal Button for WooCommerce

Public WordPress/WooCommerce plugin for implementing an online EU-style withdrawal / cancel contract flow.

The plugin adds a customer-facing withdrawal form, WooCommerce order-aware product selection, guest order lookup, admin request records, multilingual labels, email acknowledgements, proof hash, optional PDF receipt, CSV export, and optional WooCommerce order status update to **Withdrawal requested**.

> Legal note: this plugin helps implement an online withdrawal process. It is not legal advice. Final wording, exclusions and refund/return rules should be reviewed by the shop owner's legal advisor for each jurisdiction and product category.

## Current version

`0.5.4` testing build.

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

## Withdrawal availability by role

Merchants can control logged-in customer access under **WooCommerce > Settings > EU Withdrawal** using **Withdrawal availability by role**.

- **All roles can withdraw** preserves the default/current behavior.
- **Only selected roles can withdraw** allows logged-in users when at least one of their roles is selected.
- **Selected roles cannot withdraw** blocks logged-in users when any of their roles is selected.
- Guest lookup is still controlled separately by the existing guest lookup setting.
- Custom roles registered by WordPress, WooCommerce, membership, wholesale, B2B, or partner plugins appear automatically in the role checklist.

The role rule hides plugin-generated customer-facing withdrawal links/buttons and is also enforced during direct form access, review, and submission.

## WPML / multilingual notes

- Customer-facing languages currently supported: `en`, `el`, `es`, `hu`.
- Withdrawal Requests should not be translated.
- Request meta should be copied/stored as audit data, not translated content.
- `wpml-config.xml` is included.
- My Account order actions, order details buttons, shortcode buttons, and email withdrawal links resolve the translated Withdrawal page when WPML/Polylang provides one.
- If a translated Withdrawal page is missing, the plugin falls back to the configured default Withdrawal page.
- Customer confirmation emails use the frontend/request language, including configurable multilingual templates when present.
- Admin notification emails also use the stored request/frontend language for translated labels and configured admin templates, so the admin audit email matches the customer-facing request language.
- If no multilingual plugin is active, the fixed plugin language setting is used when configured; otherwise the site locale is used.
- WPML/Polylang translated page URL handling should be tested per site.

## Privacy

The plugin registers WordPress personal data exporter and eraser callbacks for EU Withdrawal Requests. Requests are matched by customer email address.

The exporter includes customer-facing withdrawal request details: request ID, reference code, related WooCommerce order ID/number, customer name, customer email, submitted date/time, request status, selected products/items, customer message/declaration, and stored language/locale. Internal-only proof hash values are not exposed in the customer export.

The eraser anonymizes direct personal fields on matching withdrawal requests: customer name, customer email, customer comments/message, IP address, user agent, and request title text. It does not delete withdrawal request posts by default.

Order-linked audit data is intentionally retained after anonymization: WooCommerce order ID/number, selected products/items, submitted date/time, workflow status, language/locale, reference code, and proof hash. This keeps the admin workflow, order notes, and legal/integrity proof usable after the personal fields are anonymized.

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
