<?php
if (!defined('ABSPATH')) { exit; }

abstract class EU_Withdrawal_Button_Settings extends EU_Withdrawal_Button_I18n {
    protected static function default_settings_static(): array {
        return [
            'page_id' => 0,
            'admin_email' => get_option('admin_email'),
            'language' => 'auto',
            'show_in_order_emails' => 'yes',
            'eligibility_days' => 14,
            'allowed_statuses' => ['processing','completed','on-hold'],
            'css_mode' => 'theme',
            'require_two_step' => 'yes',
            'allow_guest_lookup' => 'yes',
            'attach_pdf_receipt' => 'yes',
            'excluded_product_ids' => '',
            'excluded_category_ids' => [],
            'exclude_virtual' => 'no',
            'exclude_downloadable' => 'no',
            'exclude_external' => 'yes',
            'button_label_override' => '',
            'change_order_status_on_submit' => 'yes',
        ];
    }

    protected function get_settings(): array {
        return wp_parse_args(get_option(self::OPTION_KEY, []), self::default_settings_static());
    }

    protected static function get_setting_static(string $key, $default='') {
        $settings = get_option(self::OPTION_KEY, []);
        $defaults = self::default_settings_static();
        return $settings[$key] ?? ($defaults[$key] ?? $default);
    }

    public function register_settings(): void { register_setting('ewb_settings_group', self::OPTION_KEY, [$this,'sanitize_settings']); }

    public function sanitize_settings($input): array {
        $d = self::default_settings_static();
        $lang = sanitize_text_field($input['language'] ?? 'auto'); if (!in_array($lang, ['auto','en','el','es','hu'], true)) { $lang='auto'; }
        $css = sanitize_text_field($input['css_mode'] ?? 'theme'); if (!in_array($css, ['theme','none'], true)) { $css='theme'; }
        $statuses = array_map('sanitize_key', (array)($input['allowed_statuses'] ?? $d['allowed_statuses']));
        $valid_statuses = array_map(static function($key){ return str_replace('wc-', '', $key); }, array_keys(wc_get_order_statuses()));
        $statuses = array_values(array_intersect($statuses, $valid_statuses));
        if (!$statuses) { $statuses = $d['allowed_statuses']; }
        return [
            'page_id'=>absint($input['page_id'] ?? 0),
            'admin_email'=>sanitize_email($input['admin_email'] ?? get_option('admin_email')),
            'language'=>$lang,
            'show_in_order_emails'=>!empty($input['show_in_order_emails'])?'yes':'no',
            'eligibility_days'=>max(0, absint($input['eligibility_days'] ?? 14)),
            'allowed_statuses'=>$statuses,
            'css_mode'=>$css,
            'require_two_step'=>!empty($input['require_two_step'])?'yes':'no',
            'allow_guest_lookup'=>!empty($input['allow_guest_lookup'])?'yes':'no',
            'attach_pdf_receipt'=>!empty($input['attach_pdf_receipt'])?'yes':'no',
            'excluded_product_ids'=>sanitize_text_field($input['excluded_product_ids'] ?? ''),
            'excluded_category_ids'=>array_map('absint', (array)($input['excluded_category_ids'] ?? [])),
            'exclude_virtual'=>!empty($input['exclude_virtual'])?'yes':'no',
            'exclude_downloadable'=>!empty($input['exclude_downloadable'])?'yes':'no',
            'exclude_external'=>!empty($input['exclude_external'])?'yes':'no',
            'button_label_override'=>sanitize_text_field($input['button_label_override'] ?? ''),
            'change_order_status_on_submit'=>!empty($input['change_order_status_on_submit'])?'yes':'no',
        ];
    }

    public function settings_page(): void {
        if (!current_user_can('manage_woocommerce')) { return; }
        $s=$this->get_settings(); $pages=get_pages(['sort_column'=>'post_title']); $trans=$this->translations(); $cats=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
        ?>
        <div class="wrap"><h1>EU Withdrawal Button Settings</h1>
        <?php if(isset($_GET['ewb_test_email'])){ $sent = sanitize_key(wp_unslash($_GET['ewb_test_email'])) === 'sent'; echo '<div class="notice notice-'.($sent ? 'success' : 'error').'"><p>'.esc_html($sent ? 'Test email sent.' : 'Test email could not be sent. Check the WooCommerce order notes/debug log or mail server configuration.').'</p></div>'; } ?>
        <p>Shortcodes: <code>[eu_withdrawal_form]</code> and <code>[eu_withdrawal_button]</code>. The frontend CSS is deliberately minimal so buttons/inputs inherit the active theme styling.</p>
        <p><a class="button" href="<?php echo esc_url(admin_url('admin-post.php?action=ewb_export_csv&_wpnonce='.wp_create_nonce('ewb_export_csv'))); ?>">Export withdrawal requests CSV</a></p>
        <p><a class="button" href="<?php echo esc_url(admin_url('admin-post.php?action=ewb_send_test_email&_wpnonce='.wp_create_nonce('ewb_send_test_email'))); ?>">Send test email</a></p>
        <form method="post" action="options.php"><?php settings_fields('ewb_settings_group'); ?>
        <table class="form-table" role="presentation">
        <tr><th>Withdrawal page</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[page_id]"><option value="0">— Select page —</option><?php foreach($pages as $p){ echo '<option value="'.esc_attr($p->ID).'" '.selected((int)$s['page_id'],(int)$p->ID,false).'>'.esc_html($p->post_title).'</option>'; } ?></select></td></tr>
        <tr><th>Admin notification email</th><td><input type="email" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[admin_email]" value="<?php echo esc_attr($s['admin_email']); ?>"></td></tr>
        <tr><th>Frontend language</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[language]"><option value="auto" <?php selected($s['language'],'auto'); ?>>Auto-detect WPML/Polylang/site locale</option><?php foreach(['en','el','es','hu'] as $l){ echo '<option value="'.esc_attr($l).'" '.selected($s['language'],$l,false).'>'.esc_html($trans[$l]['language_name']).' ('.esc_html($l).')</option>'; } ?></select></td></tr>
        <tr><th>Button label override</th><td><input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[button_label_override]" value="<?php echo esc_attr($s['button_label_override']); ?>"><p class="description">Leave empty to use translated label.</p></td></tr>
        <tr><th>Eligibility window</th><td><input type="number" min="0" step="1" name="<?php echo esc_attr(self::OPTION_KEY); ?>[eligibility_days]" value="<?php echo esc_attr($s['eligibility_days']); ?>"> days <p class="description">0 disables date limit. Default EU withdrawal period is commonly 14 days; confirm legal wording with counsel.</p></td></tr>
        <tr><th>Allowed order statuses</th><td><?php foreach(wc_get_order_statuses() as $key=>$label){ $key=str_replace('wc-','',$key); echo '<label style="display:block"><input type="checkbox" name="'.esc_attr(self::OPTION_KEY).'[allowed_statuses][]" value="'.esc_attr($key).'" '.checked(in_array($key,(array)$s['allowed_statuses'],true),true,false).'> '.esc_html($label).'</label>'; } ?></td></tr>
        <tr><th>Display / CSS</th><td><label><input type="radio" name="<?php echo esc_attr(self::OPTION_KEY); ?>[css_mode]" value="theme" <?php checked($s['css_mode'],'theme'); ?>> Minimal structural CSS, inherit site/theme styles</label><br><label><input type="radio" name="<?php echo esc_attr(self::OPTION_KEY); ?>[css_mode]" value="none" <?php checked($s['css_mode'],'none'); ?>> No frontend CSS</label></td></tr>
        <tr><th>Flow</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[require_two_step]" value="yes" <?php checked($s['require_two_step'],'yes'); ?>> Require review/confirmation step</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[allow_guest_lookup]" value="yes" <?php checked($s['allow_guest_lookup'],'yes'); ?>> Allow guest lookup by order number + billing email</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[show_in_order_emails]" value="yes" <?php checked($s['show_in_order_emails'],'yes'); ?>> Add link to customer order emails</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[attach_pdf_receipt]" value="yes" <?php checked($s['attach_pdf_receipt'],'yes'); ?>> Attach simple PDF receipt to customer email</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[change_order_status_on_submit]" value="yes" <?php checked($s['change_order_status_on_submit'],'yes'); ?>> Change WooCommerce order status to <strong>Withdrawal requested</strong> after submission</label></td></tr>
        <tr><th>Excluded products</th><td><input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[excluded_product_ids]" value="<?php echo esc_attr($s['excluded_product_ids']); ?>"><p class="description">Comma-separated product or variation IDs.</p></td></tr>
        <tr><th>Excluded categories</th><td><?php if(!is_wp_error($cats)){ foreach($cats as $cat){ echo '<label style="display:block"><input type="checkbox" name="'.esc_attr(self::OPTION_KEY).'[excluded_category_ids][]" value="'.esc_attr($cat->term_id).'" '.checked(in_array((int)$cat->term_id,(array)$s['excluded_category_ids'],true),true,false).'> '.esc_html($cat->name).'</label>'; } } ?></td></tr>
        <tr><th>Excluded product types</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[exclude_virtual]" value="yes" <?php checked($s['exclude_virtual'],'yes'); ?>> Virtual</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[exclude_downloadable]" value="yes" <?php checked($s['exclude_downloadable'],'yes'); ?>> Downloadable</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[exclude_external]" value="yes" <?php checked($s['exclude_external'],'yes'); ?>> External/Affiliate</label></td></tr>
        </table><?php submit_button(); ?></form></div>
        <?php
    }
}

