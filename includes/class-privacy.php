<?php
if (!defined('ABSPATH')) { exit; }

abstract class EU_Withdrawal_Button_Privacy extends EU_Withdrawal_Button_Settings {
    const PRIVACY_EXPORTER_SLUG = 'eu-withdrawal-requests';
    const PRIVACY_ITEMS_PER_PAGE = 20;

    public function register_privacy_exporter(array $exporters): array {
        $exporters[self::PRIVACY_EXPORTER_SLUG] = [
            'exporter_friendly_name' => __('EU Withdrawal Requests', 'eu-withdrawal-button'),
            'callback' => [$this, 'privacy_exporter'],
        ];
        return $exporters;
    }

    public function register_privacy_eraser(array $erasers): array {
        $erasers[self::PRIVACY_EXPORTER_SLUG] = [
            'eraser_friendly_name' => __('EU Withdrawal Requests', 'eu-withdrawal-button'),
            'callback' => [$this, 'privacy_eraser'],
        ];
        return $erasers;
    }

    protected function privacy_query_args(string $email_address, int $page): array {
        return [
            'post_type' => self::CPT,
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => self::PRIVACY_ITEMS_PER_PAGE,
            'paged' => max(1, $page),
            'orderby' => 'ID',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_ewb_customer_email',
                    'value' => $email_address,
                    'compare' => '=',
                ],
            ],
        ];
    }

    public function privacy_exporter(string $email_address, int $page = 1): array {
        $email_address = sanitize_email($email_address);
        if (!is_email($email_address)) {
            return ['data' => [], 'done' => true];
        }

        $query = new WP_Query($this->privacy_query_args($email_address, $page));
        $data = [];
        foreach ((array)$query->posts as $post_id) {
            $post_id = (int)$post_id;
            $data[] = [
                'group_id' => self::PRIVACY_EXPORTER_SLUG,
                'group_label' => __('EU Withdrawal Requests', 'eu-withdrawal-button'),
                'item_id' => 'eu-withdrawal-request-' . $post_id,
                'data' => $this->privacy_export_item_data($post_id),
            ];
        }

        return [
            'data' => $data,
            'done' => (int)$query->max_num_pages <= max(1, $page),
        ];
    }

    protected function privacy_export_item_data(int $post_id): array {
        $fields = [
            __('Request ID', 'eu-withdrawal-button') => '#' . $post_id,
            __('Reference code', 'eu-withdrawal-button') => $this->privacy_meta($post_id, 'reference_code'),
            __('WooCommerce order ID', 'eu-withdrawal-button') => $this->privacy_meta($post_id, 'order_id'),
            __('WooCommerce order number', 'eu-withdrawal-button') => $this->privacy_meta($post_id, 'order_number'),
            __('Customer name', 'eu-withdrawal-button') => $this->privacy_meta($post_id, 'customer_name'),
            __('Customer email', 'eu-withdrawal-button') => $this->privacy_meta($post_id, 'customer_email'),
            __('Submitted at', 'eu-withdrawal-button') => $this->privacy_meta($post_id, 'submitted_at'),
            __('Request status', 'eu-withdrawal-button') => $this->privacy_meta($post_id, 'status'),
            __('Selected products/items', 'eu-withdrawal-button') => $this->privacy_products_value($post_id),
            __('Customer message/declaration', 'eu-withdrawal-button') => $this->privacy_meta($post_id, 'comments'),
            __('Language/locale', 'eu-withdrawal-button') => $this->privacy_meta($post_id, 'language'),
        ];

        $data = [];
        foreach ($fields as $name => $value) {
            if ($value === '') {
                continue;
            }
            $data[] = [
                'name' => $name,
                'value' => $value,
            ];
        }
        return $data;
    }

    protected function privacy_meta(int $post_id, string $key): string {
        $value = get_post_meta($post_id, '_ewb_' . $key, true);
        if (is_array($value)) {
            $value = implode(', ', array_map('sanitize_text_field', $value));
        }
        return sanitize_text_field((string)$value);
    }

    protected function privacy_products_value(int $post_id): string {
        $products = get_post_meta($post_id, '_ewb_products', true);
        if (is_array($products)) {
            $products = array_filter(array_map('sanitize_text_field', $products));
            return implode('; ', $products);
        }
        return sanitize_textarea_field((string)$products);
    }

    public function privacy_eraser(string $email_address, int $page = 1): array {
        $email_address = sanitize_email($email_address);
        if (!is_email($email_address)) {
            return ['items_removed' => false, 'items_retained' => false, 'messages' => [], 'done' => true];
        }

        // Always erase the first page of currently matching records so anonymizing email meta cannot shift later pages past the query window.
        $query = new WP_Query($this->privacy_query_args($email_address, 1));
        $messages = [];
        foreach ((array)$query->posts as $post_id) {
            $post_id = (int)$post_id;
            $this->anonymize_withdrawal_request($post_id);
            $messages[] = sprintf(
                __('Anonymized personal fields for withdrawal request #%d; retained order-linked audit and proof data.', 'eu-withdrawal-button'),
                $post_id
            );
        }

        $processed = count((array)$query->posts);
        return [
            'items_removed' => $processed > 0,
            'items_retained' => $processed > 0,
            'messages' => $messages,
            'done' => $processed < self::PRIVACY_ITEMS_PER_PAGE,
        ];
    }

    protected function anonymize_withdrawal_request(int $post_id): void {
        // Direct identifiers, contact details, free-text comments, IP address, and user agent are anonymized.
        update_post_meta($post_id, '_ewb_customer_name', __('Anonymized customer', 'eu-withdrawal-button'));
        update_post_meta($post_id, '_ewb_customer_email', $this->anonymous_email_for_request($post_id));
        update_post_meta($post_id, '_ewb_comments', '');
        update_post_meta($post_id, '_ewb_ip_address', '');
        update_post_meta($post_id, '_ewb_user_agent', '');
        update_post_meta($post_id, '_ewb_privacy_anonymized_at', current_time('mysql'));

        // The request post, order ID/number, selected products, submitted date, status, language, reference code, and proof hash are intentionally retained.
        // They are order-linked audit/proof data needed to keep withdrawal workflow and legal integrity usable after anonymization.
    }

    protected function anonymous_email_for_request(int $post_id): string {
        return 'anonymous-withdrawal-' . absint($post_id) . '@example.invalid';
    }
}
