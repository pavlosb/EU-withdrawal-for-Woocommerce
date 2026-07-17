<?php
/**
 * Plugin Name: EU Withdrawal Button for WooCommerce
 * Description: Online withdrawal/cancel contract flow for WooCommerce with multilingual labels, order-aware forms, eligibility rules, proof hash, PDF receipt and admin workflow.
 * Version: 0.5.3
 * Author: INLINE Technology Consultants
 * Text Domain: eu-withdrawal-button
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

if (!defined('ABSPATH')) { exit; }

require_once __DIR__ . '/includes/class-i18n.php';
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-order-status.php';
require_once __DIR__ . '/includes/class-pdf.php';
require_once __DIR__ . '/includes/class-emails.php';
require_once __DIR__ . '/includes/class-frontend.php';
require_once __DIR__ . '/includes/class-admin.php';
require_once __DIR__ . '/includes/class-plugin.php';

register_activation_hook(__FILE__, ['EU_Withdrawal_Button_Plugin','activate']);
register_deactivation_hook(__FILE__, ['EU_Withdrawal_Button_Plugin','deactivate']);
add_action('plugins_loaded', ['EU_Withdrawal_Button_Plugin','instance']);
