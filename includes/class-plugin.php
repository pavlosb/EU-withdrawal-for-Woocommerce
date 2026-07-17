<?php
if (!defined('ABSPATH')) { exit; }

final class EU_Withdrawal_Button_Plugin extends EU_Withdrawal_Button_Admin {
    private static $instance = null;

    public static function instance(): self {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }

    protected function __construct() {
        add_action('init', [$this, 'register_withdrawal_order_status'], 5);
        add_action('init', [$this, 'register_cpt']);
        add_action('init', [$this, 'register_order_endpoint']);
        add_filter('woocommerce_register_shop_order_post_statuses', [$this, 'register_withdrawal_order_status_args']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('wc_order_statuses', [$this, 'add_withdrawal_order_status']);
        add_shortcode('eu_withdrawal_form', [$this, 'shortcode_form']);
        add_shortcode('eu_withdrawal_button', [$this, 'shortcode_button']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_filter('woocommerce_my_account_my_orders_actions', [$this, 'add_order_action'], 20, 2);
        add_action('woocommerce_order_details_after_order_table', [$this, 'order_details_button']);
        add_action('woocommerce_email_after_order_table', [$this, 'email_withdrawal_link'], 20, 4);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_menu', [$this, 'add_withdrawals_menu_badge'], 99);
        add_action('admin_init', [$this, 'register_settings']);
        add_filter('woocommerce_settings_tabs_array', [$this, 'add_woocommerce_settings_tab'], 50);
        add_action('woocommerce_settings_tabs_ewb_withdrawal', [$this, 'output_woocommerce_settings_tab']);
        add_action('woocommerce_update_options_ewb_withdrawal', [$this, 'save_woocommerce_settings_tab']);
        add_action('admin_post_ewb_export_csv', [$this, 'export_csv']);
        add_action('admin_post_ewb_send_test_email', [$this, 'send_test_email']);
        add_filter('manage_' . self::CPT . '_posts_columns', [$this, 'admin_columns']);
        add_action('manage_' . self::CPT . '_posts_custom_column', [$this, 'admin_column_content'], 10, 2);
        add_filter('list_table_primary_column', [$this, 'primary_column'], 10, 2);
        add_filter('manage_edit-' . self::CPT . '_sortable_columns', [$this, 'sortable_columns']);
        add_filter('post_row_actions', [$this, 'row_actions'], 10, 2);
        add_action('restrict_manage_posts', [$this, 'admin_filters']);
        add_action('pre_get_posts', [$this, 'filter_admin_requests']);
        add_action('admin_post_ewb_workflow_action', [$this, 'handle_workflow_action']);
        add_action('add_meta_boxes', [$this, 'add_metaboxes']);
        add_action('save_post_' . self::CPT, [$this, 'save_request_admin'], 10, 2);
    }

    public static function activate(): void {
        self::instance()->register_cpt();
        self::instance()->register_order_endpoint();
        flush_rewrite_rules();
        $settings = get_option(self::OPTION_KEY, []);
        if (empty($settings['page_id']) || !get_post_status((int)$settings['page_id'])) {
            $page = get_page_by_path('withdrawal-cancel-contract');
            if (!$page) {
                $page_id = wp_insert_post([
                    'post_title' => 'Withdrawal / Cancel Contract',
                    'post_name' => 'withdrawal-cancel-contract',
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'post_content' => '[eu_withdrawal_form]',
                ]);
            } else { $page_id = $page->ID; }
            if (!is_wp_error($page_id) && $page_id) { $settings['page_id'] = (int)$page_id; }
        }
        $settings = wp_parse_args($settings, self::default_settings_static());
        update_option(self::OPTION_KEY, $settings);
    }

    public static function deactivate(): void { flush_rewrite_rules(); }
}
