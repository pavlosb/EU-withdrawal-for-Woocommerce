# EU Withdrawal Button for WooCommerce

Public WordPress/WooCommerce plugin for implementing an online EU-style withdrawal / cancel contract flow.

The plugin adds a customer-facing withdrawal form, WooCommerce order-aware product selection, guest order lookup, admin request records, multilingual labels, email acknowledgements, proof hash, optional PDF receipt, CSV export, and optional WooCommerce order status update to **Withdrawal requested**.

> Legal note: this plugin helps implement an online withdrawal flow. It is not legal advice. Final wording, eligibility rules, exceptions, and store policies should be reviewed by a qualified legal advisor in the relevant jurisdiction.

## Current version

`0.4.0` testing build.

## Main features

- Dedicated withdrawal form shortcode: `[eu_withdrawal_form]`
- Button/link shortcode: `[eu_withdrawal_button]`
- Guest lookup by order number and billing email
- Logged-in customer order prefill from My Account / order email links
- Product and quantity selection for partial withdrawal
- Two-step confirmation before submission
- Admin request records under WooCommerce
- Workflow statuses: Submitted, In Review, Approved, Rejected, Completed, Refunded
- Optional WooCommerce order status change to `Withdrawal requested`
- Email confirmation to the customer
- Admin notification email
- Simple PDF receipt attachment
- SHA-256 proof hash
- CSV export
- Multilingual customer-facing labels: English, Greek, Spanish, Hungarian
- WPML/Polylang-aware language detection and translated page URL handling
- Minimal/no frontend CSS modes so the plugin can inherit each site's theme styling

## Installation

1. Download or clone the repository.
2. Copy the plugin folder into `wp-content/plugins/`.
3. Activate **EU Withdrawal Button for WooCommerce** in WordPress admin.
4. Go to **WooCommerce > Withdrawal Settings**.
5. Confirm or create the Withdrawal page containing:

```text
[eu_withdrawal_form]
```

6. Add a footer/menu link to the Withdrawal page.
7. Test logged-in, guest, email-link, and direct-page flows on staging before production.

## Recommended page placement

To keep the function clearly visible and easily accessible, add links/buttons in multiple places:

- Footer/menu link to the Withdrawal page
- My Account > Orders action button
- Order details page button
- WooCommerce order emails link
- Terms / Returns / Withdrawal Policy page

## Styling / CSS

The plugin is designed to avoid imposing its own visual style.

Settings include:

- **Minimal structural CSS, inherit site/theme styles**
- **No frontend CSS**

Buttons use WooCommerce/theme-friendly classes where possible:

```text
woocommerce-button button
woocommerce-button button alt
```

For page builders such as Elementor or Divi, place the shortcode inside a normal page section and style the surrounding page with the theme/builder.

## Multilingual support

Supported customer-facing languages:

- English
- Greek
- Spanish
- Hungarian

The plugin can use:

- WordPress locale
- WPML current language
- Polylang current language
- Fixed language from plugin settings

Withdrawal Requests are intended to remain **not translatable** in WPML. Request metadata should be copied, not translated.

## Development notes

This repository includes `AGENTS.md` for Codex and other AI coding agents.

Core rules:

- Preserve WooCommerce compatibility.
- Keep frontend markup theme-friendly.
- Do not add heavy CSS unless optional.
- Keep customer-facing strings translatable/multilingual.
- Do not remove audit/proof records.
- Treat legal/compliance wording as configurable and subject to legal review.

## Testing

See [`docs/TESTING.md`](docs/TESTING.md).

## Roadmap

See [`docs/ROADMAP.md`](docs/ROADMAP.md).

## Security

See [`SECURITY.md`](SECURITY.md).
