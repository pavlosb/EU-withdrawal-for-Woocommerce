# AGENTS.md

Guidance for Codex and other coding agents working on this repository.

## Project

This is a WordPress/WooCommerce plugin: **EU Withdrawal Button for WooCommerce**.

It implements an online withdrawal / cancel contract workflow for WooCommerce stores, with multilingual frontend labels, WooCommerce order integration, admin workflow records and WPML/Polylang compatibility.

## Important constraints

- Keep the plugin compatible with PHP 7.4+ unless explicitly changed.
- Keep WooCommerce as a required dependency.
- Do not break existing shortcode names:
  - `[eu_withdrawal_form]`
  - `[eu_withdrawal_button]`
- Do not rename the custom post type without a migration plan.
- Do not rename existing option/meta keys without migration/backward compatibility.
- Customer-facing labels must continue supporting English, Greek, Spanish and Hungarian.
- Withdrawal Requests must remain non-translatable in WPML.
- Preserve the minimal CSS approach. The plugin should inherit site/theme/WooCommerce styling by default.
- Avoid storing unnecessary personal data.
- Do not include real customer/order data in tests, issues, fixtures or screenshots.
- Compliance/legal text should be configurable and reviewed by the merchant's legal advisor.

## Coding style

- Follow WordPress coding standards where practical.
- Sanitize all input and escape all output.
- Use nonces for frontend and admin actions.
- Capability checks are required for admin actions.
- Prefer WooCommerce APIs over direct database queries.
- Keep frontend markup simple and theme-friendly.
- Avoid adding large external dependencies unless there is a clear reason.

## Testing commands

Run syntax check before committing:

```bash
php -l eu-withdrawal-button.php
```

Suggested manual test matrix is in `docs/TESTING.md`.

## Areas requiring extra care

- Guest order lookup must verify both order number and billing email.
- Direct page access must not expose order data without verification.
- Order status changes should be optional and reversible by admin workflow.
- PDF/email outputs must not contain unsanitized input.
- WPML translated page URLs should route to the correct language page.
- Frontend CSS should not override the active theme unnecessarily.

## Preferred workflow

- Create small focused branches/PRs.
- Include a short testing note in every PR.
- For new features, update `README.md`, `CHANGELOG.md` and `docs/TESTING.md` when relevant.
- For user-visible labels, update all supported languages.
