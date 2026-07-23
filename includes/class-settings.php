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
            'role_availability_mode' => 'all',
            'role_availability_roles' => [],
            'attach_pdf_receipt' => 'yes',
            'excluded_product_ids' => '',
            'excluded_category_ids' => [],
            'exclude_virtual' => 'no',
            'exclude_downloadable' => 'no',
            'exclude_external' => 'yes',
            'withdrawal_action_label' => '',
            'button_label_override' => '',
            'hide_form_heading' => 'no',
            'before_form_text' => '',
            'after_form_text' => '',
            'non_eligible_messages' => self::default_non_eligible_messages(),
            'guest_role_not_eligible_messages' => self::default_guest_role_not_eligible_messages(),
            'email_templates' => self::default_email_templates(),
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
        $settings = wp_parse_args(get_option(self::OPTION_KEY, []), self::default_settings_static());
        $settings['email_templates'] = self::normalize_email_templates($settings['email_templates'] ?? []);
        $settings['non_eligible_messages'] = self::normalize_non_eligible_messages($settings['non_eligible_messages'] ?? []);
        $settings['guest_role_not_eligible_messages'] = self::normalize_guest_role_not_eligible_messages($settings['guest_role_not_eligible_messages'] ?? []);
        $mode = sanitize_key($settings['role_availability_mode'] ?? 'all');
        $settings['role_availability_mode'] = in_array($mode, ['all','include','exclude'], true) ? $mode : 'all';
        $settings['role_availability_roles'] = $this->sanitize_availability_roles($settings['role_availability_roles'] ?? []);
        return $settings;
    }

    protected static function get_setting_static(string $key, $default='') {
        $settings = get_option(self::OPTION_KEY, []);
        $defaults = self::default_settings_static();
        return $settings[$key] ?? ($defaults[$key] ?? $default);
    }

    public function register_settings(): void { register_setting('ewb_settings_group', self::OPTION_KEY, [$this,'sanitize_settings']); }

    protected function settings_url(): string {
        return admin_url('admin.php?page=wc-settings&tab=ewb_withdrawal');
    }

    public function add_woocommerce_settings_tab(array $tabs): array {
        $tabs['ewb_withdrawal'] = 'EU Withdrawal';
        return $tabs;
    }

    protected static function email_template_languages(): array {
        return ['en'=>'English','el'=>'Greek','es'=>'Spanish','hu'=>'Hungarian'];
    }

    protected static function email_template_fields(): array {
        return [
            'customer_subject' => ['label'=>'Customer confirmation subject','type'=>'subject'],
            'customer_body' => ['label'=>'Customer confirmation body','type'=>'body'],
            'admin_subject' => ['label'=>'Admin notification subject','type'=>'subject'],
            'admin_body' => ['label'=>'Admin notification body','type'=>'body'],
        ];
    }

    protected static function default_email_templates(): array {
        $templates = [];
        foreach(self::email_template_fields() as $field=>$config){
            $templates[$field] = [];
            foreach(self::email_template_languages() as $lang=>$label){
                $templates[$field][$lang] = '';
            }
        }
        return $templates;
    }

    protected static function default_non_eligible_messages(): array {
        $messages = [];
        foreach(self::email_template_languages() as $lang=>$label){
            $messages[$lang] = '';
        }
        return $messages;
    }

    protected static function default_guest_role_not_eligible_messages(): array {
        return [
            'en' => 'Withdrawal requests are not available for this account. To cancel this specific order, please contact the sales department.',
            'el' => 'Δεν είναι δυνατή η υποβολή αιτήματος ακύρωσης για τον λογαριασμό σας. Για να κάνετε ακύρωση της συγκεκριμένης παραγγελίας παρακαλούμε επικοινωνήστε με το τμήμα πωλήσεων.',
            'es' => 'Las solicitudes de desistimiento no están disponibles para esta cuenta. Para cancelar este pedido, contacte con el departamento de ventas.',
            'hu' => 'Ehhez a fiókhoz nem érhető el elállási kérelem. A konkrét rendelés lemondásához kérjük, vegye fel a kapcsolatot az értékesítési osztállyal.',
        ];
    }

    protected static function normalize_email_templates($templates): array {
        $normalized = self::default_email_templates();
        $templates = is_array($templates) ? $templates : [];
        foreach($normalized as $field=>$langs){
            foreach($langs as $lang=>$default){
                if(isset($templates[$field]) && is_array($templates[$field]) && isset($templates[$field][$lang])){
                    $normalized[$field][$lang] = (string)$templates[$field][$lang];
                }
            }
        }
        return $normalized;
    }

    protected static function normalize_non_eligible_messages($messages): array {
        $normalized = self::default_non_eligible_messages();
        $messages = is_array($messages) ? $messages : [];
        foreach($normalized as $lang=>$default){
            if(isset($messages[$lang])){
                $normalized[$lang] = (string)$messages[$lang];
            }
        }
        return $normalized;
    }

    protected static function normalize_guest_role_not_eligible_messages($messages): array {
        $defaults = self::default_guest_role_not_eligible_messages();
        $messages = is_array($messages) ? $messages : [];
        foreach($defaults as $lang=>$default){
            if(isset($messages[$lang])){
                $defaults[$lang] = (string)$messages[$lang];
            }
        }
        return $defaults;
    }

    protected static function email_template_allowed_html(): array {
        return [
            'a' => ['href'=>[], 'title'=>[]],
            'br' => [],
            'p' => [],
            'strong' => [],
            'em' => [],
            'b' => [],
            'i' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'code' => [],
        ];
    }

    protected static function sanitize_email_template_subject($value): string {
        return sanitize_text_field(wp_unslash((string)$value));
    }

    protected static function sanitize_email_template_body($value): string {
        return wp_kses(wp_unslash((string)$value), self::email_template_allowed_html());
    }

    protected static function sanitize_email_templates($templates): array {
        $templates = is_array($templates) ? $templates : [];
        $sanitized = self::default_email_templates();
        foreach(self::email_template_fields() as $field=>$config){
            foreach(self::email_template_languages() as $lang=>$label){
                $value = $templates[$field][$lang] ?? '';
                $sanitized[$field][$lang] = $config['type'] === 'subject' ? self::sanitize_email_template_subject($value) : self::sanitize_email_template_body($value);
            }
        }
        return $sanitized;
    }

    protected static function sanitize_non_eligible_messages($messages): array {
        $messages = is_array($messages) ? $messages : [];
        $sanitized = self::default_non_eligible_messages();
        foreach($sanitized as $lang=>$default){
            $sanitized[$lang] = sanitize_textarea_field(wp_unslash($messages[$lang] ?? ''));
        }
        return $sanitized;
    }

    protected static function sanitize_guest_role_not_eligible_messages($messages): array {
        $messages = is_array($messages) ? $messages : [];
        $sanitized = self::default_guest_role_not_eligible_messages();
        foreach($sanitized as $lang=>$default){
            $sanitized[$lang] = sanitize_textarea_field(wp_unslash($messages[$lang] ?? ''));
        }
        return $sanitized;
    }

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

    protected function non_eligible_order_message(): string {
        $settings = $this->get_settings();
        $messages = self::normalize_non_eligible_messages($settings['non_eligible_messages'] ?? []);
        $message = trim((string)($messages[$this->current_lang()] ?? ''));
        return $message !== '' ? $message : $this->t('not_eligible');
    }

    protected function guest_role_not_eligible_message(): string {
        $settings = $this->get_settings();
        $messages = self::normalize_guest_role_not_eligible_messages($settings['guest_role_not_eligible_messages'] ?? []);
        $defaults = self::default_guest_role_not_eligible_messages();
        $lang = $this->current_lang();
        $message = trim((string)($messages[$lang] ?? ''));
        return $message !== '' ? $message : ($defaults[$lang] ?? $defaults['en']);
    }

    protected function availability_role_options(): array {
        $roles = wp_roles();
        if(!$roles || empty($roles->roles) || !is_array($roles->roles)){ return []; }
        $out = [];
        foreach($roles->roles as $key=>$role){
            $out[sanitize_key($key)] = translate_user_role($role['name'] ?? $key);
        }
        natcasesort($out);
        return $out;
    }

    protected function sanitize_availability_roles($roles): array {
        $roles = array_map('sanitize_key', (array)$roles);
        $valid = array_keys($this->availability_role_options());
        return array_values(array_intersect(array_unique($roles), $valid));
    }

    protected function user_can_withdraw_by_role(int $user_id=0): bool {
        if(!$user_id){ return true; }
        $settings = $this->get_settings();
        $mode = sanitize_key($settings['role_availability_mode'] ?? 'all');
        if($mode === 'all'){ return true; }
        $selected = $this->sanitize_availability_roles($settings['role_availability_roles'] ?? []);
        $user = get_userdata($user_id);
        if(!$user){ return false; }
        $roles = array_map('sanitize_key', (array)$user->roles);
        $has_match = (bool)array_intersect($roles, $selected);
        if($mode === 'include'){ return $has_match; }
        if($mode === 'exclude'){ return !$has_match; }
        return true;
    }

    protected function guest_email_user_can_withdraw_by_role(string $email): bool {
        if(is_user_logged_in()){ return true; }
        $email = sanitize_email($email);
        if($email === '' || !is_email($email)){ return true; }
        $user = get_user_by('email', $email);
        if(!$user instanceof WP_User){ return true; }
        return $this->user_can_withdraw_by_role((int)$user->ID);
    }

    protected function current_user_can_withdraw_by_role(): bool {
        return $this->user_can_withdraw_by_role(is_user_logged_in() ? get_current_user_id() : 0);
    }

    protected function withdrawal_role_unavailable_message(): string {
        return __('Withdrawal requests are not available for your account type. Please contact us if you need help with this order.', 'eu-withdrawal-button');
    }

    public function sanitize_settings($input): array {
        $d = self::default_settings_static();
        $existing = get_option(self::OPTION_KEY, []);
        $lang = sanitize_text_field($input['language'] ?? 'auto'); if (!in_array($lang, ['auto','en','el','es','hu'], true)) { $lang='auto'; }
        $css = sanitize_text_field($input['css_mode'] ?? 'theme'); if (!in_array($css, ['theme','none'], true)) { $css='theme'; }
        $role_mode = sanitize_key($input['role_availability_mode'] ?? 'all'); if(!in_array($role_mode, ['all','include','exclude'], true)){ $role_mode='all'; }
        $statuses = array_map('sanitize_key', (array)($input['allowed_statuses'] ?? $d['allowed_statuses']));
        $valid_statuses = array_map(static function($key){ return str_replace('wc-', '', $key); }, array_keys(wc_get_order_statuses()));
        $statuses = array_values(array_intersect($statuses, $valid_statuses));
        if (!$statuses) { $statuses = $d['allowed_statuses']; }
        $action_label = sanitize_text_field(wp_unslash($input['withdrawal_action_label'] ?? ($input['button_label_override'] ?? ($existing['withdrawal_action_label'] ?? ($existing['button_label_override'] ?? '')))));
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
            'role_availability_mode'=>$role_mode,
            'role_availability_roles'=>$this->sanitize_availability_roles($input['role_availability_roles'] ?? []),
            'attach_pdf_receipt'=>!empty($input['attach_pdf_receipt'])?'yes':'no',
            'excluded_product_ids'=>sanitize_text_field($input['excluded_product_ids'] ?? ''),
            'excluded_category_ids'=>array_map('absint', (array)($input['excluded_category_ids'] ?? [])),
            'exclude_virtual'=>!empty($input['exclude_virtual'])?'yes':'no',
            'exclude_downloadable'=>!empty($input['exclude_downloadable'])?'yes':'no',
            'exclude_external'=>!empty($input['exclude_external'])?'yes':'no',
            'withdrawal_action_label'=>$action_label,
            'button_label_override'=>$action_label,
            'hide_form_heading'=>!empty($input['hide_form_heading'])?'yes':'no',
            'before_form_text'=>self::sanitize_helper_text(wp_unslash($input['before_form_text'] ?? '')),
            'after_form_text'=>self::sanitize_helper_text(wp_unslash($input['after_form_text'] ?? '')),
            'non_eligible_messages'=>self::sanitize_non_eligible_messages($input['non_eligible_messages'] ?? []),
            'guest_role_not_eligible_messages'=>self::sanitize_guest_role_not_eligible_messages($input['guest_role_not_eligible_messages'] ?? []),
            'email_templates'=>self::sanitize_email_templates($input['email_templates'] ?? []),
            'change_order_status_on_submit'=>!empty($input['change_order_status_on_submit'])?'yes':'no',
        ];
        foreach(self::custom_button_class_fields() as $key=>$label){
            $settings[$key] = self::sanitize_css_class_list($input[$key] ?? '');
        }
        return $settings;
    }

    protected function render_settings_notices(): void {
        if(isset($_GET['ewb_test_email'])){
            $sent = sanitize_key(wp_unslash($_GET['ewb_test_email'])) === 'sent';
            echo '<div class="notice notice-'.($sent ? 'success' : 'error').'"><p>'.esc_html($sent ? 'Test email sent.' : 'Test email could not be sent. Check the WooCommerce order notes/debug log or mail server configuration.').'</p></div>';
        }
    }

    protected function render_settings_fields(): void {
        $s=$this->get_settings(); $pages=get_pages(['sort_column'=>'post_title']); $trans=$this->translations(); $cats=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
        $email_templates = self::normalize_email_templates($s['email_templates'] ?? []);
        $non_eligible_messages = self::normalize_non_eligible_messages($s['non_eligible_messages'] ?? []);
        $guest_role_not_eligible_messages = self::normalize_guest_role_not_eligible_messages($s['guest_role_not_eligible_messages'] ?? []);
        $role_options = $this->availability_role_options();
        $selected_roles = $this->sanitize_availability_roles($s['role_availability_roles'] ?? []);
        $customer_placeholders = ['{request_id}','{order_number}','{customer_name}','{customer_email}','{products}','{submitted_at}','{withdrawal_status}','{reference_code}','{withdrawal_url}','{site_name}'];
        $admin_placeholders = array_merge($customer_placeholders, ['{proof_hash}']);
        ?>
        <p>Shortcodes: <code>[eu_withdrawal_form]</code> and <code>[eu_withdrawal_button]</code>. The frontend CSS is deliberately minimal so buttons/inputs inherit the active theme styling.</p>
        <p><a class="<?php echo esc_attr($this->admin_button_classes('admin_export_csv', 'button button-secondary')); ?>" href="<?php echo esc_url(admin_url('admin-post.php?action=ewb_export_csv&_wpnonce='.wp_create_nonce('ewb_export_csv'))); ?>">Export withdrawal requests CSV</a></p>
        <p><a class="<?php echo esc_attr($this->admin_button_classes('admin_test_email', 'button button-secondary')); ?>" href="<?php echo esc_url(admin_url('admin-post.php?action=ewb_send_test_email&_wpnonce='.wp_create_nonce('ewb_send_test_email'))); ?>">Send test email</a></p>
        <table class="form-table" role="presentation">
        <tr><th>Withdrawal page</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[page_id]"><option value="0">— Select page —</option><?php foreach($pages as $p){ echo '<option value="'.esc_attr($p->ID).'" '.selected((int)$s['page_id'],(int)$p->ID,false).'>'.esc_html($p->post_title).'</option>'; } ?></select></td></tr>
        <tr><th>Admin notification email</th><td><input type="email" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[admin_email]" value="<?php echo esc_attr($s['admin_email']); ?>"></td></tr>
        <tr><th>Frontend language</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[language]"><option value="auto" <?php selected($s['language'],'auto'); ?>>Auto-detect WPML/Polylang/site locale</option><?php foreach(['en','el','es','hu'] as $l){ echo '<option value="'.esc_attr($l).'" '.selected($s['language'],$l,false).'>'.esc_html($trans[$l]['language_name']).' ('.esc_html($l).')</option>'; } ?></select></td></tr>
        <tr><th>Withdrawal action label</th><td><input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[withdrawal_action_label]" value="<?php echo esc_attr($s['withdrawal_action_label'] ?: ($s['button_label_override'] ?? '')); ?>"><p class="description">Shown on customer-facing withdrawal links and buttons. Leave empty to use the translated default label.</p></td></tr>
        <tr><th>Email templates</th><td><p class="description">Optional multilingual templates for withdrawal request emails. Leave any field empty to use the current built-in translated default for that language.</p><p><strong>Customer placeholders:</strong> <?php foreach($customer_placeholders as $placeholder){ echo '<code>'.esc_html($placeholder).'</code> '; } ?></p><p><strong>Admin placeholders:</strong> <?php foreach($admin_placeholders as $placeholder){ echo '<code>'.esc_html($placeholder).'</code> '; } ?></p><p class="description">Customer templates do not expose the raw proof hash; use <code>{reference_code}</code> for customer-facing references. Admin/internal templates may use <code>{proof_hash}</code>. Bodies allow basic email HTML only; scripts, iframes, event-handler attributes, styles, and unsafe HTML are stripped.</p><?php foreach(self::email_template_languages() as $lang=>$label){ ?><div style="margin:1em 0;padding:1em;border:1px solid #ccd0d4;"><h4><?php echo esc_html($label.' ('.$lang.')'); ?></h4><p><label>Customer confirmation subject<br><input type="text" class="large-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[email_templates][customer_subject][<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr($email_templates['customer_subject'][$lang]); ?>" placeholder="<?php echo esc_attr($trans[$lang]['email_subject'] ?? $this->t('email_subject')); ?>"></label></p><p><label>Customer confirmation body<br><textarea class="large-text" rows="5" name="<?php echo esc_attr(self::OPTION_KEY); ?>[email_templates][customer_body][<?php echo esc_attr($lang); ?>]" placeholder="<?php echo esc_attr('Leave empty to use the built-in translated customer receipt.'); ?>"><?php echo esc_textarea($email_templates['customer_body'][$lang]); ?></textarea></label></p><p><label>Admin notification subject<br><input type="text" class="large-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[email_templates][admin_subject][<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr($email_templates['admin_subject'][$lang]); ?>" placeholder="<?php echo esc_attr(($trans[$lang]['admin_new_subject'] ?? $this->t('admin_new_subject')).' {order_number}'); ?>"></label></p><p><label>Admin notification body<br><textarea class="large-text" rows="5" name="<?php echo esc_attr(self::OPTION_KEY); ?>[email_templates][admin_body][<?php echo esc_attr($lang); ?>]" placeholder="<?php echo esc_attr('Leave empty to use the built-in translated admin notification.'); ?>"><?php echo esc_textarea($email_templates['admin_body'][$lang]); ?></textarea></label></p></div><?php } ?></td></tr>
        <tr><th>Form display and helper text</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[hide_form_heading]" value="yes" <?php checked($s['hide_form_heading'],'yes'); ?>> Hide plugin form heading</label><p class="description">Use this if your page/theme already displays a suitable page heading.</p><p><label>Before form text<br><textarea class="large-text" rows="4" name="<?php echo esc_attr(self::OPTION_KEY); ?>[before_form_text]" placeholder="<?php echo esc_attr($this->default_form_helper_text('before')); ?>"><?php echo esc_textarea($s['before_form_text'] ?? ''); ?></textarea></label></p><p class="description">Shown above the withdrawal form. Leave empty to use the localized default helper text.</p><p><label>After form text<br><textarea class="large-text" rows="4" name="<?php echo esc_attr(self::OPTION_KEY); ?>[after_form_text]" placeholder="<?php echo esc_attr($this->default_form_helper_text('after')); ?>"><?php echo esc_textarea($s['after_form_text'] ?? ''); ?></textarea></label></p><p class="description">Shown below the withdrawal form. Basic safe formatting is allowed; scripts and unsafe HTML are stripped.</p></td></tr>
        <tr><th>Non-eligible order message</th><td><p class="description">Optional customer-facing message shown when an order is not currently eligible for online withdrawal. Leave a language empty to use the built-in translated default. The notice uses <code>ewb-notice ewb-notice--not-eligible</code> so site CSS can style it.</p><?php foreach(self::email_template_languages() as $lang=>$label){ echo '<p><label>'.esc_html($label.' ('.$lang.')').'<br><textarea class="large-text" rows="3" name="'.esc_attr(self::OPTION_KEY).'[non_eligible_messages]['.esc_attr($lang).']" placeholder="'.esc_attr($trans[$lang]['not_eligible'] ?? $this->t('not_eligible')).'">'.esc_textarea($non_eligible_messages[$lang]).'</textarea></label></p>'; } ?></td></tr>
        <tr><th>Guest account role-not-eligible message</th><td><p class="description">Optional customer-facing message shown when a logged-out lookup/submission email belongs to an existing account whose role is not eligible for withdrawal. Leave a language empty to use the built-in translated default. The notice uses <code>ewb-notice ewb-notice--role-not-eligible</code> so site CSS can style it without exposing account roles.</p><?php $guest_role_defaults = self::default_guest_role_not_eligible_messages(); foreach(self::email_template_languages() as $lang=>$label){ echo '<p><label>'.esc_html($label.' ('.$lang.')').'<br><textarea class="large-text" rows="3" name="'.esc_attr(self::OPTION_KEY).'[guest_role_not_eligible_messages]['.esc_attr($lang).']" placeholder="'.esc_attr($guest_role_defaults[$lang] ?? $guest_role_defaults['en']).'">'.esc_textarea($guest_role_not_eligible_messages[$lang]).'</textarea></label></p>'; } ?></td></tr>
        <tr><th>Custom button classes</th><td><p class="description">Optional CSS classes are appended after the default WooCommerce/WordPress button classes. Use class names only; HTML, scripts, inline styles, and attributes are stripped.</p><?php foreach(self::custom_button_class_fields() as $key=>$label){ echo '<p><label>'.esc_html($label).'<br><input type="text" class="regular-text" name="'.esc_attr(self::OPTION_KEY).'['.esc_attr($key).']" value="'.esc_attr($s[$key] ?? '').'" placeholder="my-custom-class"></label></p>'; } ?><p class="description">My Account &gt; Orders action classes are generated by the WooCommerce account orders template, so the plugin leaves those default template classes unchanged.</p></td></tr>
        <tr><th>Eligibility window</th><td><input type="number" min="0" step="1" name="<?php echo esc_attr(self::OPTION_KEY); ?>[eligibility_days]" value="<?php echo esc_attr($s['eligibility_days']); ?>"> days <p class="description">0 disables date limit. Default EU withdrawal period is commonly 14 days; confirm legal wording with counsel.</p></td></tr>
        <tr><th>Allowed order statuses</th><td><?php foreach(wc_get_order_statuses() as $key=>$label){ $key=str_replace('wc-','',$key); echo '<label style="display:block"><input type="checkbox" name="'.esc_attr(self::OPTION_KEY).'[allowed_statuses][]" value="'.esc_attr($key).'" '.checked(in_array($key,(array)$s['allowed_statuses'],true),true,false).'> '.esc_html($label).'</label>'; } ?></td></tr>
        <tr><th>Display / CSS</th><td><label><input type="radio" name="<?php echo esc_attr(self::OPTION_KEY); ?>[css_mode]" value="theme" <?php checked($s['css_mode'],'theme'); ?>> Minimal structural CSS, inherit site/theme styles</label><br><label><input type="radio" name="<?php echo esc_attr(self::OPTION_KEY); ?>[css_mode]" value="none" <?php checked($s['css_mode'],'none'); ?>> No frontend CSS</label></td></tr>
        <tr><th>Flow</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[require_two_step]" value="yes" <?php checked($s['require_two_step'],'yes'); ?>> Require review/confirmation step</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[allow_guest_lookup]" value="yes" <?php checked($s['allow_guest_lookup'],'yes'); ?>> Allow guest lookup by order number + billing email</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[show_in_order_emails]" value="yes" <?php checked($s['show_in_order_emails'],'yes'); ?>> Add link to customer order emails</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[attach_pdf_receipt]" value="yes" <?php checked($s['attach_pdf_receipt'],'yes'); ?>> Attach simple PDF receipt to customer email</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[change_order_status_on_submit]" value="yes" <?php checked($s['change_order_status_on_submit'],'yes'); ?>> Change WooCommerce order status to <strong>Withdrawal requested</strong> after submission</label></td></tr>
        <tr><th>Withdrawal availability by role</th><td><fieldset><legend class="screen-reader-text"><span>Withdrawal availability by role</span></legend><label><input type="radio" name="<?php echo esc_attr(self::OPTION_KEY); ?>[role_availability_mode]" value="all" <?php checked($s['role_availability_mode'],'all'); ?>> All roles can withdraw</label><br><label><input type="radio" name="<?php echo esc_attr(self::OPTION_KEY); ?>[role_availability_mode]" value="include" <?php checked($s['role_availability_mode'],'include'); ?>> Only selected roles can withdraw</label><br><label><input type="radio" name="<?php echo esc_attr(self::OPTION_KEY); ?>[role_availability_mode]" value="exclude" <?php checked($s['role_availability_mode'],'exclude'); ?>> Selected roles cannot withdraw</label><p class="description">Guests are handled separately by the existing guest lookup setting. For logged-out lookup/submission emails that match an existing WordPress user, the same role rules are applied to that matched account without exposing role details.</p><?php foreach($role_options as $role_key=>$role_label){ echo '<label style="display:block"><input type="checkbox" name="'.esc_attr(self::OPTION_KEY).'[role_availability_roles][]" value="'.esc_attr($role_key).'" '.checked(in_array($role_key,$selected_roles,true),true,false).'> '.esc_html($role_label).' <code>'.esc_html($role_key).'</code></label>'; } ?><p class="description">Custom roles such as registered partners, wholesale customers, B2B accounts, or partner roles appear here automatically when registered by WordPress, WooCommerce, or another plugin. For users with multiple roles, include mode allows access if any role is selected; exclude mode blocks access if any role is selected.</p></fieldset></td></tr>
        <tr><th>Excluded products</th><td><input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[excluded_product_ids]" value="<?php echo esc_attr($s['excluded_product_ids']); ?>"><p class="description">Comma-separated product or variation IDs.</p></td></tr>
        <tr><th>Excluded categories</th><td><?php if(!is_wp_error($cats)){ foreach($cats as $cat){ echo '<label style="display:block"><input type="checkbox" name="'.esc_attr(self::OPTION_KEY).'[excluded_category_ids][]" value="'.esc_attr($cat->term_id).'" '.checked(in_array((int)$cat->term_id,(array)$s['excluded_category_ids'],true),true,false).'> '.esc_html($cat->name).'</label>'; } } ?></td></tr>
        <tr><th>Excluded product types</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[exclude_virtual]" value="yes" <?php checked($s['exclude_virtual'],'yes'); ?>> Virtual</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[exclude_downloadable]" value="yes" <?php checked($s['exclude_downloadable'],'yes'); ?>> Downloadable</label><br><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[exclude_external]" value="yes" <?php checked($s['exclude_external'],'yes'); ?>> External/Affiliate</label></td></tr>
        </table>
        <?php
    }

    public function output_woocommerce_settings_tab(): void {
        if (!current_user_can('manage_woocommerce')) { return; }
        echo '<h2>EU Withdrawal</h2>';
        $this->render_settings_notices();
        $this->render_settings_fields();
    }

    public function save_woocommerce_settings_tab(): void {
        if (!current_user_can('manage_woocommerce')) { return; }
        $input = isset($_POST[self::OPTION_KEY]) ? (array)$_POST[self::OPTION_KEY] : [];
        update_option(self::OPTION_KEY, $this->sanitize_settings($input));
    }

    public function settings_page(): void {
        if (!current_user_can('manage_woocommerce')) { return; }
        if(!headers_sent()){
            wp_safe_redirect($this->settings_url());
            exit;
        }
        echo '<div class="wrap"><h1>EU Withdrawal Button Settings</h1><p><a class="button button-primary" href="'.esc_url($this->settings_url()).'">Open WooCommerce EU Withdrawal settings</a></p></div>';
    }
}
