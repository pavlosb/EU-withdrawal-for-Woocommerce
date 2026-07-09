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
            'hide_form_heading' => 'no',
            'before_form_text' => '',
            'after_form_text' => '',
            'change_order_status_on_submit' => 'yes',
            'button_class_order_details' => '',
            'button_class_shortcode_button' => '',
            'button_class_lookup_submit' => '',
            'button_class_form_submit' => '',
            'button_class_confirm_submit' => '',
            'button_class_confirm_back' => '',
            'button_class_email_link' => '',
            'button_class_admin_export_csv' => '',
            'button_class_admin_test_email' => '',
            'button_class_admin_workflow_action' => '',
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

    protected static function custom_button_class_fields(): array {
        return [
            'button_class_order_details' => 'Order details withdrawal button',
            'button_class_shortcode_button' => '[eu_withdrawal_button] shortcode button',
            'button_class_lookup_submit' => 'Lookup/find order button',
            'button_class_form_submit' => 'Form submit/review button',
            'button_class_confirm_submit' => 'Confirmation submit button',
            'button_class_confirm_back' => 'Confirmation back button',
            'button_class_email_link' => 'WooCommerce email withdrawal link/button',
            'button_class_admin_export_csv' => 'Admin export CSV button',
            'button_class_admin_test_email' => 'Admin send test email button',
            'button_class_admin_workflow_action' => 'Admin workflow/status action links',
        ];
    }

    protected static function sanitize_css_class_list($value): string {
        $value = wp_strip_all_tags((string)$value);
        $classes = preg_split('/\s+/', $value);
        $sanitized = [];
        foreach((array)$classes as $class){
            $class = sanitize_html_class($class);
            if($class !== ''){
                $sanitized[] = $class;
            }
        }
        return implode(' ', array_unique($sanitized));
    }

    protected function custom_button_classes(string $context, string $base_classes): string {
        $settings = $this->get_settings();
        $custom = self::sanitize_css_class_list($settings['button_class_'.$context] ?? '');
        return trim(preg_replace('/\s+/', ' ', $base_classes.' '.$custom));
    }

    protected function frontend_button_classes(string $context, string $base_classes): string {
        return $this->custom_button_classes($context, $base_classes);
    }

    protected function admin_button_classes(string $context, string $base_classes): string {
        return $this->custom_button_classes($context, $base_classes);
    }

    protected static function sanitize_helper_text($value): string {
        return wp_kses_post((string)$value);
    }

    protected function default_form_helper_text(string $position): string {
        $texts = [
            'en' => [
                'before' => 'You can submit an electronic withdrawal / contract cancellation request for eligible orders. The right of withdrawal is usually exercised within 14 days, in accordance with applicable law and the store policy.',
                'after' => 'After submitting the request, you will receive an email confirmation and further information about the next steps for product return and refund processing.',
            ],
            'el' => [
                'before' => 'Μπορείτε να υποβάλετε ηλεκτρονικά αίτημα υπαναχώρησης / ακύρωσης σύμβασης για επιλέξιμες παραγγελίες. Το δικαίωμα υπαναχώρησης ασκείται συνήθως εντός 14 ημερών, σύμφωνα με την ισχύουσα νομοθεσία και την πολιτική του καταστήματος.',
                'after' => 'Μετά την υποβολή του αιτήματος θα λάβετε επιβεβαίωση μέσω email και νεότερη ενημέρωση για τα επόμενα βήματα επιστροφής προϊόντων και επιστροφής χρημάτων.',
            ],
            'es' => [
                'before' => 'Puede enviar una solicitud electrónica de desistimiento / cancelación de contrato para pedidos elegibles. El derecho de desistimiento suele ejercerse dentro de 14 días, de acuerdo con la legislación aplicable y la política de la tienda.',
                'after' => 'Después de enviar la solicitud, recibirá una confirmación por email y más información sobre los próximos pasos para la devolución de productos y el procesamiento del reembolso.',
            ],
            'hu' => [
                'before' => 'Elektronikusan elállási / szerződésmegszüntetési kérelmet nyújthat be a jogosult rendelésekhez. Az elállási jog általában 14 napon belül gyakorolható, az alkalmazandó jog és az áruház szabályzata szerint.',
                'after' => 'A kérelem beküldése után emailes visszaigazolást és további tájékoztatást kap a termék-visszaküldés és a visszatérítés következő lépéseiről.',
            ],
        ];
        $lang = $this->current_lang();
        return $texts[$lang][$position] ?? $texts['en'][$position] ?? '';
    }

    protected function form_helper_text(string $position): string {
        $settings = $this->get_settings();
        $key = $position === 'after' ? 'after_form_text' : 'before_form_text';
        $text = trim((string)($settings[$key] ?? ''));
        if($text === ''){
            $text = $this->default_form_helper_text($position);
        }
        return self::sanitize_helper_text($text);
    }

    public function sanitize_settings($input): array {
        $d = self::default_settings_static();
        $lang = sanitize_text_field($input['language'] ?? 'auto'); if (!in_array($lang, ['auto','en','el','es','hu'], true)) { $lang='auto'; }
        $css = sanitize_text_field($input['css_mode'] ?? 'theme'); if (!in_array($css, ['theme','none'], true)) { $css='theme'; }
        $statuses = array_map('sanitize_key', (array)($input['allowed_statuses'] ?? $d['allowed_statuses']));
        $valid_statuses = array_map(static function($key){ return str_replace('wc-', '', $key); }, array_keys(wc_get_order_statuses()));
        $statuses = array_values(array_intersect($statuses, $valid_statuses));
        if (!$statuses) { $statuses = $d['allowed_statuses']; }
        $settings = [
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
            'hide_form_heading'=>!empty($input['hide_form_heading'])?'yes':'no',
            'before_form_text'=>self::sanitize_helper_text(wp_unslash($input['before_form_text'] ?? '')),
            'after_form_text'=>self::sanitize_helper_text(wp_unslash($input['after_form_text'] ?? '')),
            'change_order_status_on_submit'=>!empty($input['change_order_status_on_submit'])?'yes':'no',
        ];
        foreach(self::custom_button_class_fields() as $key=>$label){
            $settings[$key] = self::sanitize_css_class_list($input[$key] ?? '');
        }
        return $settings;
    }

    public function settings_page(): void {
        if (!current_user_can('manage_woocommerce')) { return; }
        $s=$this->get_settings(); $pages=get_pages(['sort_column'=>'post_title']); $trans=$this->translations(); $cats=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
        ?>
        <div class="wrap"><h1>EU Withdrawal Button Settings</h1>
        <?php if(isset($_GET['ewb_test_email'])){ $sent = sanitize_key(wp_unslash($_GET['ewb_test_email'])) === 'sent'; echo '<div class="notice notice-'.($sent ? 'success' : 'error').'"><p>'.esc_html($sent ? 'Test email sent.' : 'Test email could not be sent. Check the WooCommerce order notes/debug log or mail server configuration.').'</p></div>'; } ?>
        <p>Shortcodes: <code>[eu_withdrawal_form]</code> and <code>[eu_withdrawal_button]</code>. The frontend CSS is deliberately minimal so buttons/inputs inherit the active theme styling.</p>
        <p><a class="<?php echo esc_attr($this->admin_button_classes('admin_export_csv', 'button button-secondary')); ?>" href="<?php echo esc_url(admin_url('admin-post.php?action=ewb_export_csv&_wpnonce='.wp_create_nonce('ewb_export_csv'))); ?>">Export withdrawal requests CSV</a></p>
        <p><a class="<?php echo esc_attr($this->admin_button_classes('admin_test_email', 'button button-secondary')); ?>" href="<?php echo esc_url(admin_url('admin-post.php?action=ewb_send_test_email&_wpnonce='.wp_create_nonce('ewb_send_test_email'))); ?>">Send test email</a></p>
        <form method="post" action="options.php"><?php settings_fields('ewb_settings_group'); ?>
        <table class="form-table" role="presentation">
        <tr><th>Withdrawal page</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[page_id]"><option value="0">— Select page —</option><?php foreach($pages as $p){ echo '<option value="'.esc_attr($p->ID).'" '.selected((int)$s['page_id'],(int)$p->ID,false).'>'.esc_html($p->post_title).'</option>'; } ?></select></td></tr>
        <tr><th>Admin notification email</th><td><input type="email" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[admin_email]" value="<?php echo esc_attr($s['admin_email']); ?>"></td></tr>
        <tr><th>Frontend language</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[language]"><option value="auto" <?php selected($s['language'],'auto'); ?>>Auto-detect WPML/Polylang/site locale</option><?php foreach(['en','el','es','hu'] as $l){ echo '<option value="'.esc_attr($l).'" '.selected($s['language'],$l,false).'>'.esc_html($trans[$l]['language_name']).' ('.esc_html($l).')</option>'; } ?></select></td></tr>
        <tr><th>Button label override</th><td><input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[button_label_override]" value="<?php echo esc_attr($s['button_label_override']); ?>"><p class="description">Leave empty to use translated label.</p></td></tr>
        <tr><th>Form display and helper text</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[hide_form_heading]" value="yes" <?php checked($s['hide_form_heading'],'yes'); ?>> Hide plugin form heading</label><p class="description">Use this if your page/theme already displays a suitable page heading.</p><p><label>Before form text<br><textarea class="large-text" rows="4" name="<?php echo esc_attr(self::OPTION_KEY); ?>[before_form_text]" placeholder="<?php echo esc_attr($this->default_form_helper_text('before')); ?>"><?php echo esc_textarea($s['before_form_text'] ?? ''); ?></textarea></label></p><p class="description">Shown above the withdrawal form. Leave empty to use the localized default helper text.</p><p><label>After form text<br><textarea class="large-text" rows="4" name="<?php echo esc_attr(self::OPTION_KEY); ?>[after_form_text]" placeholder="<?php echo esc_attr($this->default_form_helper_text('after')); ?>"><?php echo esc_textarea($s['after_form_text'] ?? ''); ?></textarea></label></p><p class="description">Shown below the withdrawal form. Basic safe formatting is allowed; scripts and unsafe HTML are stripped.</p></td></tr>
        <tr><th>Custom button classes</th><td><p class="description">Optional CSS classes are appended after the default WooCommerce/WordPress button classes. Use class names only; HTML, scripts, inline styles, and attributes are stripped.</p><?php foreach(self::custom_button_class_fields() as $key=>$label){ echo '<p><label>'.esc_html($label).'<br><input type="text" class="regular-text" name="'.esc_attr(self::OPTION_KEY).'['.esc_attr($key).']" value="'.esc_attr($s[$key] ?? '').'" placeholder="my-custom-class"></label></p>'; } ?><p class="description">My Account &gt; Orders action classes are generated by the WooCommerce account orders template, so the plugin leaves those default template classes unchanged.</p></td></tr>
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

