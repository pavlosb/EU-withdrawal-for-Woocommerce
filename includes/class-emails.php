<?php
if (!defined('ABSPATH')) { exit; }

abstract class EU_Withdrawal_Button_Emails extends EU_Withdrawal_Button_PDF {
    protected function email_body(int $post_id,array $m): string { $products='<ul><li>'.implode('</li><li>',array_map('esc_html',(array)$m['products'])).'</li></ul>'; $body='<p>'.esc_html($this->t('received_body')).'</p><p><strong>'.esc_html($this->t('request_id')).':</strong> #'.esc_html($post_id).'<br><strong>'.esc_html($this->t('reference_code')).':</strong> '.esc_html($m['reference_code'] ?? $post_id).'<br><strong>'.esc_html($this->t('name')).':</strong> '.esc_html($m['customer_name']).'<br><strong>'.esc_html($this->t('email')).':</strong> '.esc_html($m['customer_email']).'<br><strong>'.esc_html($this->t('order')).':</strong> '.esc_html($m['order_number']).'<br><strong>'.esc_html($this->t('submitted_at')).':</strong> '.esc_html($m['submitted_at']).'</p><p><strong>'.esc_html($this->t('products')).':</strong></p>'.$products.'<p><strong>'.esc_html($this->t('declaration_label')).':</strong><br>'.esc_html($this->t('declaration')).'</p>'; if(!empty($m['comments'])){ $body.='<p><strong>'.esc_html($this->t('comments')).':</strong><br>'.nl2br(esc_html($m['comments'])).'</p>'; } return $body.'<p>'.esc_html($this->t('next_steps')).'</p>'; }

    protected function email_headers(): array {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $from_name = str_replace(["\r","\n"], '', wp_specialchars_decode((string)apply_filters('woocommerce_email_from_name', get_option('woocommerce_email_from_name')), ENT_QUOTES));
        $from_address = sanitize_email((string)apply_filters('woocommerce_email_from_address', get_option('woocommerce_email_from_address')));
        if($from_name && $from_address && is_email($from_address)){
            $headers[] = 'From: '.$from_name.' <'.$from_address.'>';
        }
        return $headers;
    }

    protected function get_admin_email_recipient(): string {
        $s = $this->get_settings();
        $admin = sanitize_email($s['admin_email'] ?? '');
        if(!$admin || !is_email($admin)){ $admin = sanitize_email(get_option('admin_email')); }
        return is_email($admin) ? $admin : '';
    }

    protected function email_template_language(array $meta): string {
        if(!empty($meta['language'])){ return $this->normalize_lang($meta['language']); }
        return $this->current_lang();
    }

    protected function configured_email_template(string $field,array $meta): string {
        $settings = $this->get_settings();
        $lang = $this->email_template_language($meta);
        return trim((string)($settings['email_templates'][$field][$lang] ?? ''));
    }

    protected function email_products_placeholder(array $products): string {
        $items = array_map('esc_html', $products);
        return $items ? '<ul><li>'.implode('</li><li>', $items).'</li></ul>' : '';
    }

    protected function email_template_placeholders(int $post_id,array $meta,bool $allow_proof_hash): array {
        $order = !empty($meta['order_id']) ? wc_get_order((int)$meta['order_id']) : false;
        $withdrawal_url = method_exists($this, 'page_url') ? $this->page_url($order instanceof WC_Order ? $order : null) : home_url('/withdrawal-cancel-contract/');
        $status = sanitize_key($meta['status'] ?? 'submitted');
        $status_label_key = 'status_'.str_replace('-', '_', $status);
        $status_label = $this->t($status_label_key);
        if($status_label === $status_label_key){ $status_label = $status; }
        $placeholders = [
            '{request_id}' => '#'.esc_html((string)$post_id),
            '{order_number}' => esc_html((string)($meta['order_number'] ?? '')),
            '{customer_name}' => esc_html((string)($meta['customer_name'] ?? '')),
            '{customer_email}' => esc_html((string)($meta['customer_email'] ?? '')),
            '{products}' => $this->email_products_placeholder((array)($meta['products'] ?? [])),
            '{submitted_at}' => esc_html((string)($meta['submitted_at'] ?? '')),
            '{withdrawal_status}' => esc_html($status_label),
            '{reference_code}' => esc_html((string)($meta['reference_code'] ?? $post_id)),
            '{withdrawal_url}' => esc_url($withdrawal_url),
            '{site_name}' => esc_html(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)),
            '{proof_hash}' => $allow_proof_hash ? esc_html((string)($meta['proof_hash'] ?? '')) : '',
        ];
        return $placeholders;
    }

    protected function render_email_subject_template(string $field,int $post_id,array $meta,string $default,bool $allow_proof_hash=false): string {
        $template = $this->configured_email_template($field, $meta);
        if($template === ''){ return $default; }
        $subject = strtr($template, $this->email_template_placeholders($post_id, $meta, $allow_proof_hash));
        $subject = wp_strip_all_tags($subject);
        return trim($subject) !== '' ? $subject : $default;
    }

    protected function render_email_body_template(string $field,int $post_id,array $meta,string $default,bool $allow_proof_hash=false): string {
        $template = $this->configured_email_template($field, $meta);
        if($template === ''){ return $default; }
        $body = strtr($template, $this->email_template_placeholders($post_id, $meta, $allow_proof_hash));
        $body = wp_kses_post(wpautop($body));
        return trim($body) !== '' ? $body : $default;
    }

    protected function add_email_failure_note(int $post_id,string $recipient_type,string $recipient,string $message): void {
        $message = sprintf('Withdrawal request #%d %s email failed for %s: %s', $post_id, $recipient_type, $recipient ?: 'no valid recipient', $message);
        add_post_meta($post_id, '_ewb_email_error', $message);
        $order_id = (int)get_post_meta($post_id, '_ewb_order_id', true);
        $order = $order_id ? wc_get_order($order_id) : false;
        if($order instanceof WC_Order){ $order->add_order_note($message); }
        if(defined('WP_DEBUG') && WP_DEBUG){ error_log('[EU Withdrawal Button] '.$message); }
    }

    protected function send_emails(int $post_id,array $meta): void {
        update_post_meta($post_id, '_ewb_email_attempted_at', current_time('mysql'));
        $previous_lang = $this->active_lang;
        if(!empty($meta['language'])){ $this->active_lang = $this->normalize_lang($meta['language']); }
        try {
            $headers = $this->email_headers();
            $body = $this->email_body($post_id,$meta);
            $attachments = [];

            if($this->get_settings()['attach_pdf_receipt']==='yes'){
                try {
                    $pdf = $this->create_pdf_receipt($post_id,$meta);
                    if($pdf && file_exists($pdf)){ $attachments[] = $pdf; }
                } catch (Throwable $e) {
                    $this->add_email_failure_note($post_id, 'PDF receipt', '', $e->getMessage());
                }
            }

            $this->send_customer_email($post_id, $meta, $body, $headers, $attachments);
            $this->send_admin_email($post_id, $meta, $body, $headers);
        } finally {
            $this->active_lang = $previous_lang;
        }
    }

    protected function send_customer_email(int $post_id,array $meta,string $body,array $headers,array $attachments): void {
        $customer_email = sanitize_email($meta['customer_email'] ?? '');
        if($customer_email && is_email($customer_email)){
            $subject = $this->render_email_subject_template('customer_subject', $post_id, $meta, $this->t('email_subject'));
            $customer_body = $this->render_email_body_template('customer_body', $post_id, $meta, $body);
            $customer_sent = wp_mail($customer_email, $subject, $customer_body, $headers, $attachments);
            update_post_meta($post_id, '_ewb_customer_email_sent', $customer_sent ? 'yes' : 'no');
            if(!$customer_sent){ $this->add_email_failure_note($post_id, 'customer', $customer_email, 'wp_mail returned false'); }
        } else {
            update_post_meta($post_id, '_ewb_customer_email_sent', 'no');
            $this->add_email_failure_note($post_id, 'customer', $customer_email, 'invalid recipient');
        }
    }

    protected function send_admin_email(int $post_id,array $meta,string $body,array $headers): void {
        $admin_email = $this->get_admin_email_recipient();
        $admin = $body.'<p><a href="'.esc_url(admin_url('post.php?post='.$post_id.'&action=edit')).'">'.esc_html($this->t('view_admin')).'</a></p>';
        if($admin_email){
            $subject = $this->render_email_subject_template('admin_subject', $post_id, $meta, $this->t('admin_new_subject').' '.$meta['order_number'], true);
            $admin_body = $this->render_email_body_template('admin_body', $post_id, $meta, $admin, true);
            $admin_sent = wp_mail($admin_email, $subject, $admin_body, $headers);
            update_post_meta($post_id, '_ewb_admin_email_sent', $admin_sent ? 'yes' : 'no');
            if(!$admin_sent){ $this->add_email_failure_note($post_id, 'admin', $admin_email, 'wp_mail returned false'); }
        } else {
            update_post_meta($post_id, '_ewb_admin_email_sent', 'no');
            $this->add_email_failure_note($post_id, 'admin', '', 'invalid recipient');
        }
    }

    public function send_test_email(): void {
        if(!current_user_can('manage_woocommerce') || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce']??'')),'ewb_send_test_email')){ wp_die('Forbidden'); }
        $recipient = $this->get_admin_email_recipient();
        $sent = false;
        if($recipient){
            $sent = wp_mail($recipient, 'EU Withdrawal Button test email', '<p>This is a test email from EU Withdrawal Button for WooCommerce.</p>', $this->email_headers());
        }
        if(!$sent && defined('WP_DEBUG') && WP_DEBUG){ error_log('[EU Withdrawal Button] Test email failed for '.($recipient ?: 'no valid recipient')); }
        wp_safe_redirect(add_query_arg('ewb_test_email', $sent ? 'sent' : 'failed', $this->settings_url()));
        exit;
    }
}

