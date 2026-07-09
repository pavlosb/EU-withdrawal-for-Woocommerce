<?php
/**
 * Plugin Name: EU Withdrawal Button for WooCommerce
 * Description: Online withdrawal/cancel contract flow for WooCommerce with multilingual labels, order-aware forms, eligibility rules, proof hash, PDF receipt and admin workflow.
 * Version: 0.4.0
 * Author: INLINE Technology Consultants
 * Text Domain: eu-withdrawal-button
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

if (!defined('ABSPATH')) { exit; }

final class EU_Withdrawal_Button_Plugin {
    const VERSION = '0.4.0';
    const CPT = 'ewb_withdrawal';
    const OPTION_KEY = 'ewb_settings';
    const NONCE_ACTION = 'ewb_submit_withdrawal';
    const ADMIN_NONCE = 'ewb_admin_action';

    private static $instance = null;
    private $active_lang = null;

    public static function instance(): self {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_withdrawal_order_status'], 9);
        add_action('init', [$this, 'register_cpt']);
        add_action('init', [$this, 'register_order_endpoint']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('wc_order_statuses', [$this, 'add_withdrawal_order_status']);
        add_shortcode('eu_withdrawal_form', [$this, 'shortcode_form']);
        add_shortcode('eu_withdrawal_button', [$this, 'shortcode_button']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_filter('woocommerce_my_account_my_orders_actions', [$this, 'add_order_action'], 20, 2);
        add_action('woocommerce_order_details_after_order_table', [$this, 'order_details_button']);
        add_action('woocommerce_email_after_order_table', [$this, 'email_withdrawal_link'], 20, 4);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_ewb_export_csv', [$this, 'export_csv']);
        add_action('admin_post_ewb_send_test_email', [$this, 'send_test_email']);
        add_filter('manage_' . self::CPT . '_posts_columns', [$this, 'admin_columns']);
        add_action('manage_' . self::CPT . '_posts_custom_column', [$this, 'admin_column_content'], 10, 2);
        add_filter('manage_edit-' . self::CPT . '_sortable_columns', [$this, 'sortable_columns']);
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

    private static function default_settings_static(): array {
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

    private function get_settings(): array {
        return wp_parse_args(get_option(self::OPTION_KEY, []), self::default_settings_static());
    }

    private static function get_setting_static(string $key, $default='') {
        $settings = get_option(self::OPTION_KEY, []);
        $defaults = self::default_settings_static();
        return $settings[$key] ?? ($defaults[$key] ?? $default);
    }

    private function translations(): array {
        return [
            'en'=>[
                'language_name'=>'English','button_label'=>'Withdrawal / Cancel Contract','form_title'=>'Withdrawal / Cancel Contract','form_intro'=>'Use this form to submit an online withdrawal / cancellation request for your order.','lookup_intro'=>'Enter your order number and billing email to retrieve eligible items.','full_name'=>'Full name','email'=>'Email','order_number'=>'Order number','lookup'=>'Find order','products_to_withdraw'=>'Products to withdraw','quantity'=>'Quantity','comments'=>'Comments / additional information','declaration'=>'I hereby declare that I withdraw from the purchase contract for the selected product(s) / order.','review_title'=>'Review and confirm your withdrawal request','back'=>'Back','submit'=>'Confirm withdrawal request','required_fields'=>'Please complete all required fields.','security_failed'=>'Security check failed. Please try again.','order_not_verified'=>'The order could not be verified. Please check your order details.','not_eligible'=>'This order is not currently eligible for online withdrawal. Please contact us if you believe this is incorrect.','no_eligible_items'=>'No eligible items were found for this order.','success_title'=>'Your withdrawal request has been submitted successfully.','success_text'=>'A confirmation email has been sent to your email address.','email_subject'=>'Withdrawal request received','received_body'=>'We have received your withdrawal / cancellation request.','request_id'=>'Request ID','proof_hash'=>'Proof hash','name'=>'Name','order'=>'Order','submitted_at'=>'Submitted at','products'=>'Product(s)','declaration_label'=>'Declaration','next_steps'=>'You will receive further information regarding the return process and refund, in accordance with the shop policy and applicable law.','admin_new_subject'=>'New withdrawal request - Order','admin_new_request'=>'A new withdrawal request has been submitted.','view_admin'=>'View request in admin','manual_products'=>'Product(s)','manual_products_placeholder'=>'Please enter the product(s) you wish to withdraw from.','status_submitted'=>'Submitted','status_in_review'=>'In Review','status_approved'=>'Approved','status_rejected'=>'Rejected','status_completed'=>'Completed','status_refunded'=>'Refunded'],
            'el'=>[
                'language_name'=>'Greek','button_label'=>'Υπαναχώρηση / Ακύρωση Σύμβασης','form_title'=>'Υπαναχώρηση / Ακύρωση Σύμβασης','form_intro'=>'Χρησιμοποιήστε αυτή τη φόρμα για να υποβάλετε ηλεκτρονικά αίτημα υπαναχώρησης / ακύρωσης για την παραγγελία σας.','lookup_intro'=>'Συμπληρώστε αριθμό παραγγελίας και email χρέωσης για να εμφανιστούν τα επιλέξιμα προϊόντα.','full_name'=>'Ονοματεπώνυμο','email'=>'Email','order_number'=>'Αριθμός παραγγελίας','lookup'=>'Εύρεση παραγγελίας','products_to_withdraw'=>'Προϊόντα για υπαναχώρηση','quantity'=>'Ποσότητα','comments'=>'Σχόλια / πρόσθετες πληροφορίες','declaration'=>'Δηλώνω ότι υπαναχωρώ από τη σύμβαση αγοράς για τα επιλεγμένα προϊόντα / την παραγγελία.','review_title'=>'Έλεγχος και επιβεβαίωση αιτήματος υπαναχώρησης','back'=>'Πίσω','submit'=>'Επιβεβαίωση αιτήματος υπαναχώρησης','required_fields'=>'Παρακαλώ συμπληρώστε όλα τα υποχρεωτικά πεδία.','security_failed'=>'Ο έλεγχος ασφαλείας απέτυχε. Παρακαλώ δοκιμάστε ξανά.','order_not_verified'=>'Η παραγγελία δεν μπόρεσε να επιβεβαιωθεί. Παρακαλώ ελέγξτε τα στοιχεία της παραγγελίας.','not_eligible'=>'Η παραγγελία δεν είναι αυτή τη στιγμή επιλέξιμη για online υπαναχώρηση. Επικοινωνήστε μαζί μας αν θεωρείτε ότι αυτό δεν είναι σωστό.','no_eligible_items'=>'Δεν βρέθηκαν επιλέξιμα προϊόντα για αυτή την παραγγελία.','success_title'=>'Το αίτημα υπαναχώρησης υποβλήθηκε επιτυχώς.','success_text'=>'Έχει σταλεί email επιβεβαίωσης στη διεύθυνση email σας.','email_subject'=>'Παραλάβαμε το αίτημα υπαναχώρησης','received_body'=>'Παραλάβαμε το αίτημα υπαναχώρησης / ακύρωσης.','request_id'=>'Αριθμός αιτήματος','proof_hash'=>'Αποδεικτικό hash','name'=>'Ονοματεπώνυμο','order'=>'Παραγγελία','submitted_at'=>'Ημερομηνία υποβολής','products'=>'Προϊόντα','declaration_label'=>'Δήλωση','next_steps'=>'Θα λάβετε νεότερη ενημέρωση σχετικά με τη διαδικασία επιστροφής και επιστροφής χρημάτων, σύμφωνα με την πολιτική του καταστήματος και την ισχύουσα νομοθεσία.','admin_new_subject'=>'Νέο αίτημα υπαναχώρησης - Παραγγελία','admin_new_request'=>'Υποβλήθηκε νέο αίτημα υπαναχώρησης.','view_admin'=>'Προβολή αιτήματος στο admin','manual_products'=>'Προϊόν/προϊόντα','manual_products_placeholder'=>'Συμπληρώστε το προϊόν ή τα προϊόντα για τα οποία επιθυμείτε να υπαναχωρήσετε.','status_submitted'=>'Υποβλήθηκε','status_in_review'=>'Σε έλεγχο','status_approved'=>'Εγκρίθηκε','status_rejected'=>'Απορρίφθηκε','status_completed'=>'Ολοκληρώθηκε','status_refunded'=>'Επιστράφηκε'],
            'es'=>[
                'language_name'=>'Spanish','button_label'=>'Desistimiento / Cancelar contrato','form_title'=>'Desistimiento / Cancelar contrato','form_intro'=>'Utilice este formulario para enviar una solicitud online de desistimiento / cancelación de su pedido.','lookup_intro'=>'Introduzca el número de pedido y el email de facturación para recuperar los artículos elegibles.','full_name'=>'Nombre completo','email'=>'Correo electrónico','order_number'=>'Número de pedido','lookup'=>'Buscar pedido','products_to_withdraw'=>'Productos objeto de desistimiento','quantity'=>'Cantidad','comments'=>'Comentarios / información adicional','declaration'=>'Declaro que desisto del contrato de compra de los productos seleccionados / del pedido.','review_title'=>'Revisar y confirmar la solicitud de desistimiento','back'=>'Volver','submit'=>'Confirmar solicitud de desistimiento','required_fields'=>'Complete todos los campos obligatorios.','security_failed'=>'La comprobación de seguridad ha fallado. Inténtelo de nuevo.','order_not_verified'=>'No se ha podido verificar el pedido. Compruebe los datos del pedido.','not_eligible'=>'Este pedido no es actualmente elegible para desistimiento online. Contáctenos si cree que esto es incorrecto.','no_eligible_items'=>'No se encontraron artículos elegibles para este pedido.','success_title'=>'Su solicitud de desistimiento se ha enviado correctamente.','success_text'=>'Se ha enviado un email de confirmación a su dirección de correo electrónico.','email_subject'=>'Solicitud de desistimiento recibida','received_body'=>'Hemos recibido su solicitud de desistimiento / cancelación.','request_id'=>'ID de solicitud','proof_hash'=>'Hash de prueba','name'=>'Nombre','order'=>'Pedido','submitted_at'=>'Fecha de envío','products'=>'Producto(s)','declaration_label'=>'Declaración','next_steps'=>'Recibirá más información sobre el proceso de devolución y reembolso, de acuerdo con la política de la tienda y la legislación aplicable.','admin_new_subject'=>'Nueva solicitud de desistimiento - Pedido','admin_new_request'=>'Se ha enviado una nueva solicitud de desistimiento.','view_admin'=>'Ver solicitud en administración','manual_products'=>'Producto(s)','manual_products_placeholder'=>'Indique el producto o productos sobre los que desea ejercer el desistimiento.','status_submitted'=>'Enviada','status_in_review'=>'En revisión','status_approved'=>'Aprobada','status_rejected'=>'Rechazada','status_completed'=>'Completada','status_refunded'=>'Reembolsada'],
            'hu'=>[
                'language_name'=>'Hungarian','button_label'=>'Elállás / Szerződés megszüntetése','form_title'=>'Elállás / Szerződés megszüntetése','form_intro'=>'Ezzel az űrlappal online elállási / szerződésmegszüntetési kérelmet nyújthat be a rendelésével kapcsolatban.','lookup_intro'=>'Adja meg a rendelési számot és a számlázási email címet a jogosult tételek megjelenítéséhez.','full_name'=>'Teljes név','email'=>'Email','order_number'=>'Rendelésszám','lookup'=>'Rendelés keresése','products_to_withdraw'=>'Elállással érintett termékek','quantity'=>'Mennyiség','comments'=>'Megjegyzések / további információk','declaration'=>'Kijelentem, hogy elállok a kiválasztott termék(ek)re / rendelésre vonatkozó adásvételi szerződéstől.','review_title'=>'Elállási kérelem ellenőrzése és megerősítése','back'=>'Vissza','submit'=>'Elállási kérelem megerősítése','required_fields'=>'Kérjük, töltse ki az összes kötelező mezőt.','security_failed'=>'A biztonsági ellenőrzés sikertelen. Kérjük, próbálja újra.','order_not_verified'=>'A rendelés nem ellenőrizhető. Kérjük, ellenőrizze a rendelési adatokat.','not_eligible'=>'Ez a rendelés jelenleg nem jogosult online elállásra. Kérjük, vegye fel velünk a kapcsolatot, ha ezt hibásnak gondolja.','no_eligible_items'=>'Ehhez a rendeléshez nem található jogosult tétel.','success_title'=>'Az elállási kérelmet sikeresen elküldte.','success_text'=>'A visszaigazoló emailt elküldtük az email címére.','email_subject'=>'Elállási kérelem beérkezett','received_body'=>'Megkaptuk az elállási / szerződésmegszüntetési kérelmét.','request_id'=>'Kérelem azonosító','proof_hash'=>'Bizonyító hash','name'=>'Név','order'=>'Rendelés','submitted_at'=>'Beküldés ideje','products'=>'Termék(ek)','declaration_label'=>'Nyilatkozat','next_steps'=>'A visszaküldés és visszatérítés folyamatáról további tájékoztatást fog kapni, az áruház szabályzata és az alkalmazandó jog szerint.','admin_new_subject'=>'Új elállási kérelem - Rendelés','admin_new_request'=>'Új elállási kérelem érkezett.','view_admin'=>'Kérelem megtekintése az adminban','manual_products'=>'Termék(ek)','manual_products_placeholder'=>'Adja meg, mely termék(ek)re vonatkozóan kíván élni az elállási jogával.','status_submitted'=>'Beküldve','status_in_review'=>'Ellenőrzés alatt','status_approved'=>'Jóváhagyva','status_rejected'=>'Elutasítva','status_completed'=>'Befejezve','status_refunded'=>'Visszatérítve'],
        ];
    }

    private function normalize_lang($lang): string {
        $lang = strtolower((string)$lang);
        if (strpos($lang, '_') !== false) { $lang = substr($lang, 0, 2); }
        return in_array($lang, ['en','el','es','hu'], true) ? $lang : 'en';
    }

    private function current_lang(): string {
        if ($this->active_lang) { return $this->active_lang; }
        $settings = $this->get_settings();
        if (!empty($settings['language']) && $settings['language'] !== 'auto') { return $this->normalize_lang($settings['language']); }
        if (!empty($_GET['lang'])) { return $this->normalize_lang(sanitize_text_field(wp_unslash($_GET['lang']))); }
        $wpml = apply_filters('wpml_current_language', null);
        if ($wpml) { return $this->normalize_lang($wpml); }
        if (defined('ICL_LANGUAGE_CODE')) { return $this->normalize_lang(ICL_LANGUAGE_CODE); }
        if (function_exists('pll_current_language')) { $pll = pll_current_language('slug'); if ($pll) { return $this->normalize_lang($pll); } }
        return $this->normalize_lang(get_locale());
    }

    private function t(string $key): string {
        $all = $this->translations(); $lang = $this->current_lang();
        return $all[$lang][$key] ?? $all['en'][$key] ?? $key;
    }

    public function register_cpt(): void {
        register_post_type(self::CPT, [
            'labels'=>['name'=>'Withdrawal Requests','singular_name'=>'Withdrawal Request','menu_name'=>'Withdrawals','edit_item'=>'View Withdrawal Request'],
            'public'=>false,'show_ui'=>true,'show_in_menu'=>'woocommerce','show_in_admin_bar'=>false,'supports'=>['title'],'capability_type'=>'shop_order','map_meta_cap'=>true,'capabilities'=>['create_posts'=>'do_not_allow'],
        ]);
    }
    public function register_withdrawal_order_status(): void {
        register_post_status('wc-withdrawal-requested', [
            'label' => 'Withdrawal requested',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Withdrawal requested <span class="count">(%s)</span>', 'Withdrawal requested <span class="count">(%s)</span>', 'eu-withdrawal-button'),
        ]);
    }

    public function add_withdrawal_order_status(array $statuses): array {
        $custom_key = 'wc-withdrawal-requested';
        if (isset($statuses[$custom_key])) { return $statuses; }
        $out = [];
        $inserted = false;
        foreach ($statuses as $key => $label) {
            $out[$key] = $label;
            if (!$inserted && in_array($key, ['wc-on-hold','wc-processing'], true)) {
                $out[$custom_key] = 'Withdrawal requested';
                $inserted = true;
            }
        }
        if (!$inserted) { $out[$custom_key] = 'Withdrawal requested'; }
        return $out;
    }

    public function register_order_endpoint(): void { add_rewrite_endpoint('request-withdrawal', EP_ROOT | EP_PAGES); }
    public function add_query_vars(array $vars): array { $vars[]='request-withdrawal'; return $vars; }

    public function enqueue_styles(): void {
        $mode = $this->get_settings()['css_mode'];
        if ($mode === 'none') { return; }
        $css = '.ewb-form{max-width:var(--ewb-form-max-width,760px)}.ewb-form .ewb-field{margin-block:1rem}.ewb-form label{display:block;margin-bottom:.35rem}.ewb-form input[type=text],.ewb-form input[type=email],.ewb-form input[type=number],.ewb-form textarea,.ewb-form select{width:100%;max-width:100%;box-sizing:border-box}.ewb-form fieldset{margin:1rem 0}.ewb-form .ewb-item{display:grid;grid-template-columns:minmax(0,1fr) minmax(90px,130px);gap:1rem;align-items:center;margin:.65rem 0}.ewb-message{margin:1rem 0}.ewb-actions{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center}.ewb-muted{opacity:.75}.ewb-button-link{display:inline-block}';
        wp_register_style('ewb-inline', false, [], self::VERSION); wp_enqueue_style('ewb-inline'); wp_add_inline_style('ewb-inline', $css);
    }

    public function admin_menu(): void { add_submenu_page('woocommerce','EU Withdrawal Settings','Withdrawal Settings','manage_woocommerce','ewb-settings',[$this,'settings_page']); }
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

    private function button_label(): string { $s=$this->get_settings(); return $s['button_label_override'] ?: $this->t('button_label'); }
    private function button_classes(string $extra=''): string { return trim('woocommerce-button button ewb-button-link '.$extra); }
    private function translated_page_id(int $page_id): int {
        if (!$page_id) { return 0; }
        $lang = $this->current_lang();
        $wpml_id = apply_filters('wpml_object_id', $page_id, 'page', true, $lang);
        if ($wpml_id) { return (int)$wpml_id; }
        if (function_exists('pll_get_post')) { $pll_id = pll_get_post($page_id, $lang); if ($pll_id) { return (int)$pll_id; } }
        return $page_id;
    }
    private function page_url($order=null): string {
        $s=$this->get_settings();
        $page_id = !empty($s['page_id']) ? $this->translated_page_id((int)$s['page_id']) : 0;
        $url=$page_id ? get_permalink($page_id) : home_url('/withdrawal-cancel-contract/');
        $wpml_url = apply_filters('wpml_permalink', $url, $this->current_lang(), true);
        if (is_string($wpml_url) && $wpml_url) { $url = $wpml_url; }
        if($order instanceof WC_Order){ $url=add_query_arg(['order_id'=>$order->get_id(),'order_key'=>$order->get_order_key()],$url); }
        return $url;
    }
    public function shortcode_button(): string { return '<a class="'.esc_attr($this->button_classes()).'" href="'.esc_url($this->page_url()).'">'.esc_html($this->button_label()).'</a>'; }

    private function is_order_eligible(WC_Order $order): bool {
        $s=$this->get_settings(); $status=$order->get_status(); if(!in_array($status,(array)$s['allowed_statuses'],true)){ return false; }
        $days=(int)$s['eligibility_days']; if($days>0){ $date=$order->get_date_completed() ?: $order->get_date_created(); if($date){ $limit=$date->getTimestamp()+($days*DAY_IN_SECONDS); if(time()>$limit){ return false; } } }
        return true;
    }

    private function excluded_ids(): array { $raw=$this->get_settings()['excluded_product_ids']; return array_filter(array_map('absint', preg_split('/[,\s]+/', (string)$raw))); }
    private function is_item_eligible(WC_Order_Item_Product $item): bool {
        $s=$this->get_settings(); $product=$item->get_product(); if(!$product){ return false; }
        $ids=$this->excluded_ids(); if(in_array((int)$product->get_id(),$ids,true) || in_array((int)$product->get_parent_id(),$ids,true)){ return false; }
        if($s['exclude_virtual']==='yes' && $product->is_virtual()){ return false; }
        if($s['exclude_downloadable']==='yes' && $product->is_downloadable()){ return false; }
        if($s['exclude_external']==='yes' && $product->is_type('external')){ return false; }
        $cat_ids=(array)$s['excluded_category_ids']; if($cat_ids){ $pid=$product->get_parent_id() ?: $product->get_id(); $terms=wc_get_product_cat_ids($pid); if(array_intersect($cat_ids,$terms)){ return false; } }
        return true;
    }

    private function eligible_items(WC_Order $order): array { $out=[]; foreach($order->get_items() as $item_id=>$item){ if($item instanceof WC_Order_Item_Product && $this->is_item_eligible($item)){ $out[$item_id]=$item; } } return $out; }

    public function add_order_action(array $actions,$order): array { if($order instanceof WC_Order && $this->is_order_eligible($order) && $this->eligible_items($order)){ $actions['ewb_withdrawal']=['url'=>$this->page_url($order),'name'=>$this->button_label()]; } return $actions; }
    public function order_details_button($order): void { if($order instanceof WC_Order && $this->is_order_eligible($order) && $this->eligible_items($order)){ echo '<p><a class="'.esc_attr($this->button_classes()).'" href="'.esc_url($this->page_url($order)).'">'.esc_html($this->button_label()).'</a></p>'; } }
    public function email_withdrawal_link($order,$sent_to_admin,$plain_text,$email): void { $s=$this->get_settings(); if($sent_to_admin || $s['show_in_order_emails']!=='yes' || !$order instanceof WC_Order || !$this->is_order_eligible($order)){ return; } $url=$this->page_url($order); if($plain_text){ echo "\n".$this->button_label().': '.esc_url_raw($url)."\n"; } else { echo '<p><a class="'.esc_attr($this->button_classes()).'" href="'.esc_url($url).'">'.esc_html($this->button_label()).'</a></p>'; } }

    private function resolve_order_from_request(){
        $order_id=isset($_GET['order_id'])?absint($_GET['order_id']):(isset($_POST['ewb_order_id'])?absint($_POST['ewb_order_id']):0);
        $key=isset($_GET['order_key'])?sanitize_text_field(wp_unslash($_GET['order_key'])):(isset($_POST['ewb_order_key'])?sanitize_text_field(wp_unslash($_POST['ewb_order_key'])):'');
        if($order_id){ $o=wc_get_order($order_id); if($o && $key && hash_equals($o->get_order_key(),$key)){ return $o; } if($o && is_user_logged_in() && (int)$o->get_user_id()===get_current_user_id()){ return $o; } }
        if($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['ewb_lookup'])){ $num=sanitize_text_field(wp_unslash($_POST['ewb_order_number']??'')); $email=sanitize_email(wp_unslash($_POST['ewb_customer_email']??'')); return $this->find_order_by_number_email($num,$email); }
        return null;
    }
    private function find_order_by_number_email($number,$email){
        if(!$number||!is_email($email)){ return null; }
        $wanted = strtolower(trim(ltrim((string)$number,'#')));
        $order = false;
        $id = absint($wanted);
        if($id){ $order = wc_get_order($id); }
        if($order && strtolower((string)$order->get_billing_email())===strtolower((string)$email)){ return $order; }

        // Fallback for stores using sequential/custom order numbers.
        $orders = wc_get_orders([
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => array_keys(wc_get_order_statuses()),
            'billing_email' => $email,
            'return' => 'objects',
        ]);
        foreach($orders as $candidate){
            if($candidate instanceof WC_Order && strtolower(trim(ltrim((string)$candidate->get_order_number(),'#'))) === $wanted){ return $candidate; }
        }
        return null;
    }

    public function shortcode_form(): string {
        if(!class_exists('WooCommerce')){ return '<div class="ewb-message">WooCommerce is required.</div>'; }
        $message='';
        $render_form = true;
        $order=$this->resolve_order_from_request();

        if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ewb_submit'])){
            $message=$this->handle_submission();
            $render_form = false;
        } elseif($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ewb_review'])){
            $message=$this->render_review_step();
            $render_form = (strpos($message, '<form') === false);
        } elseif($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ewb_lookup'])){
            if(!isset($_POST['ewb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ewb_nonce'])), self::NONCE_ACTION)){
                $message = '<div class="ewb-message">'.esc_html($this->t('security_failed')).'</div>';
            } elseif(!$order){
                $message = '<div class="ewb-message">'.esc_html($this->t('order_not_verified')).'</div>';
            }
        }

        ob_start();
        echo $message;
        if($render_form){ $this->render_form($order); }
        return ob_get_clean();
    }

    private function render_lookup_form(): void {
        $email = sanitize_email(wp_unslash($_POST['ewb_customer_email'] ?? ''));
        $num = sanitize_text_field(wp_unslash($_POST['ewb_order_number'] ?? ''));
        echo '<form class="ewb-form ewb-lookup-form" method="post">';
        wp_nonce_field(self::NONCE_ACTION,'ewb_nonce');
        echo '<h2>'.esc_html($this->t('form_title')).'</h2><p>'.esc_html($this->t('lookup_intro')).'</p>';
        echo '<div class="ewb-field"><label for="ewb_order_number">'.esc_html($this->t('order_number')).' *</label><input type="text" id="ewb_order_number" name="ewb_order_number" required value="'.esc_attr($num).'"></div>';
        echo '<div class="ewb-field"><label for="ewb_customer_email">'.esc_html($this->t('email')).' *</label><input type="email" id="ewb_customer_email" name="ewb_customer_email" required value="'.esc_attr($email).'"></div>';
        echo '<div class="ewb-actions"><button type="submit" class="'.esc_attr($this->button_classes()).'" name="ewb_lookup" value="1">'.esc_html($this->t('lookup')).'</button></div>';
        echo '</form>';
    }

    private function render_form($order=null): void {
        if(!$order && $this->get_settings()['allow_guest_lookup']==='yes'){
            $this->render_lookup_form();
            return;
        }
        $name=''; $email=''; $num='';
        if($order instanceof WC_Order){
            $name=trim($order->get_billing_first_name().' '.$order->get_billing_last_name());
            $email=$order->get_billing_email();
            $num=$order->get_order_number();
        }
        echo '<form class="ewb-form" method="post">'; wp_nonce_field(self::NONCE_ACTION,'ewb_nonce');
        echo '<h2>'.esc_html($this->t('form_title')).'</h2><p>'.esc_html($this->t('form_intro')).'</p>';
        echo '<div class="ewb-field"><label for="ewb_customer_name">'.esc_html($this->t('full_name')).' *</label><input type="text" id="ewb_customer_name" name="ewb_customer_name" required value="'.esc_attr($name).'"></div>';
        echo '<div class="ewb-field"><label for="ewb_customer_email">'.esc_html($this->t('email')).' *</label><input type="email" id="ewb_customer_email" name="ewb_customer_email" required value="'.esc_attr($email).'"></div>';
        echo '<div class="ewb-field"><label for="ewb_order_number">'.esc_html($this->t('order_number')).' *</label><input type="text" id="ewb_order_number" name="ewb_order_number" required value="'.esc_attr($num).'"></div>';

        $can_submit = true;
        if($order instanceof WC_Order){
            echo '<input type="hidden" name="ewb_order_id" value="'.esc_attr($order->get_id()).'"><input type="hidden" name="ewb_order_key" value="'.esc_attr($order->get_order_key()).'">';
            if(!$this->is_order_eligible($order)){
                $can_submit = false;
                echo '<div class="ewb-message">'.esc_html($this->t('not_eligible')).'</div>';
            } else {
                $items=$this->eligible_items($order);
                if($items){
                    echo '<fieldset><legend>'.esc_html($this->t('products_to_withdraw')).' *</legend>';
                    foreach($items as $item_id=>$item){
                        $max=(int)$item->get_quantity();
                        echo '<div class="ewb-item"><label><input type="checkbox" name="ewb_item_ids[]" value="'.esc_attr($item_id).'"> '.esc_html($item->get_name()).' × '.esc_html($max).'</label><label>'.esc_html($this->t('quantity')).'<input type="number" min="1" max="'.esc_attr($max).'" step="1" name="ewb_qty['.esc_attr($item_id).']" value="'.esc_attr($max).'"></label></div>';
                    }
                    echo '</fieldset>';
                } else {
                    $can_submit = false;
                    echo '<div class="ewb-message">'.esc_html($this->t('no_eligible_items')).'</div>';
                }
            }
        } else {
            echo '<div class="ewb-field"><label for="ewb_products_text">'.esc_html($this->t('manual_products')).' *</label><textarea id="ewb_products_text" name="ewb_products_text" rows="4" required placeholder="'.esc_attr($this->t('manual_products_placeholder')).'"></textarea></div>';
        }

        if($can_submit){
            echo '<div class="ewb-field"><label for="ewb_comments">'.esc_html($this->t('comments')).'</label><textarea id="ewb_comments" name="ewb_comments" rows="4"></textarea></div>';
            echo '<div class="ewb-field"><label><input type="checkbox" name="ewb_declaration" value="1" required> '.esc_html($this->t('declaration')).' *</label></div>';
            echo '<div class="ewb-actions">';
            $btn = $this->get_settings()['require_two_step']==='yes' ? 'ewb_review' : 'ewb_submit';
            echo '<button type="submit" class="'.esc_attr($this->button_classes('alt')).'" name="'.$btn.'" value="1">'.esc_html($this->get_settings()['require_two_step']==='yes'?$this->t('review_title'):$this->t('submit')).'</button>';
            echo '</div>';
        }
        echo '</form>';
    }

    private function collect_posted_data(): array {
        $data=[
            'name'=>sanitize_text_field(wp_unslash($_POST['ewb_customer_name']??'')),
            'email'=>sanitize_email(wp_unslash($_POST['ewb_customer_email']??'')),
            'order_number'=>sanitize_text_field(wp_unslash($_POST['ewb_order_number']??'')),
            'order_id'=>absint($_POST['ewb_order_id']??0),
            'order_key'=>sanitize_text_field(wp_unslash($_POST['ewb_order_key']??'')),
            'comments'=>sanitize_textarea_field(wp_unslash($_POST['ewb_comments']??'')),
            'declaration'=>!empty($_POST['ewb_declaration']),
            'products'=>[],
        ];
        $order = $data['order_id'] ? wc_get_order($data['order_id']) : null;
        if($order && hash_equals($order->get_order_key(), $data['order_key'])){
            $ids=array_map('absint',(array)($_POST['ewb_item_ids']??[])); $qtys=(array)($_POST['ewb_qty']??[]);
            foreach($ids as $item_id){ $item=$order->get_item($item_id); if($item instanceof WC_Order_Item_Product && $this->is_item_eligible($item)){ $max=(int)$item->get_quantity(); $q=max(1,min($max,absint($qtys[$item_id]??$max))); $data['products'][]=$item->get_name().' × '.$q; } }
        } elseif(!empty($_POST['ewb_products_text'])) { $data['products'][]=sanitize_textarea_field(wp_unslash($_POST['ewb_products_text'])); }
        return $data;
    }

    private function validate_data(array $d): string {
        if(!isset($_POST['ewb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ewb_nonce'])), self::NONCE_ACTION)){ return $this->t('security_failed'); }
        if(!$d['name'] || !is_email($d['email']) || !$d['order_number'] || !$d['products'] || !$d['declaration']){ return $this->t('required_fields'); }
        if($d['order_id']){ $order=wc_get_order($d['order_id']); if(!$order || !hash_equals($order->get_order_key(),$d['order_key']) || !$this->is_order_eligible($order)){ return $this->t('order_not_verified'); } }
        return '';
    }

    private function render_review_step(): string {
        $d=$this->collect_posted_data(); $err=$this->validate_data($d); if($err){ return '<div class="ewb-message">'.esc_html($err).'</div>'; }
        ob_start(); echo '<form class="ewb-form" method="post"><div class="ewb-message"><h3>'.esc_html($this->t('review_title')).'</h3>';
        echo '<p><strong>'.esc_html($this->t('name')).':</strong> '.esc_html($d['name']).'<br><strong>'.esc_html($this->t('email')).':</strong> '.esc_html($d['email']).'<br><strong>'.esc_html($this->t('order')).':</strong> '.esc_html($d['order_number']).'</p><p><strong>'.esc_html($this->t('products')).':</strong></p><ul>'; foreach($d['products'] as $p){ echo '<li>'.esc_html($p).'</li>'; } echo '</ul><p><strong>'.esc_html($this->t('declaration_label')).':</strong><br>'.esc_html($this->t('declaration')).'</p></div>';
        wp_nonce_field(self::NONCE_ACTION,'ewb_nonce'); foreach($d as $k=>$v){ if($k==='products'){ foreach($v as $p){ echo '<input type="hidden" name="ewb_products_confirmed[]" value="'.esc_attr($p).'">'; } } elseif($k==='declaration'){ echo '<input type="hidden" name="ewb_declaration" value="1">'; } else { echo '<input type="hidden" name="ewb_'.esc_attr($k).'" value="'.esc_attr($v).'">'; } }
        echo '<div class="ewb-actions"><button type="submit" class="'.esc_attr($this->button_classes()).'" name="ewb_back" value="1">'.esc_html($this->t('back')).'</button><button type="submit" class="'.esc_attr($this->button_classes('alt')).'" name="ewb_submit" value="1">'.esc_html($this->t('submit')).'</button></div></form>'; return ob_get_clean();
    }

    private function handle_submission(): string {
        if(isset($_POST['ewb_products_confirmed'])){ $d=[ 'name'=>sanitize_text_field(wp_unslash($_POST['ewb_name']??'')), 'email'=>sanitize_email(wp_unslash($_POST['ewb_email']??'')), 'order_number'=>sanitize_text_field(wp_unslash($_POST['ewb_order_number']??'')), 'order_id'=>absint($_POST['ewb_order_id']??0), 'order_key'=>sanitize_text_field(wp_unslash($_POST['ewb_order_key']??'')), 'comments'=>sanitize_textarea_field(wp_unslash($_POST['ewb_comments']??'')), 'declaration'=>!empty($_POST['ewb_declaration']), 'products'=>array_map('sanitize_text_field', array_map('wp_unslash',(array)$_POST['ewb_products_confirmed'])) ]; }
        else { $d=$this->collect_posted_data(); }
        $err=$this->validate_data($d); if($err){ return '<div class="ewb-message">'.esc_html($err).'</div>'; }
        $submitted_at=current_time('mysql'); $payload=['order_number'=>$d['order_number'],'email'=>$d['email'],'products'=>$d['products'],'submitted_at'=>$submitted_at,'lang'=>$this->current_lang()]; $hash=hash('sha256', wp_json_encode($payload));
        $post_id=wp_insert_post(['post_type'=>self::CPT,'post_status'=>'publish','post_title'=>sprintf('Withdrawal request - Order %s - %s',$d['order_number'],$d['name'])], true);
        if(is_wp_error($post_id)){ return '<div class="ewb-message">Request could not be saved.</div>'; }
        $meta=['customer_name'=>$d['name'],'customer_email'=>$d['email'],'order_number'=>$d['order_number'],'order_id'=>$d['order_id'],'products'=>$d['products'],'comments'=>$d['comments'],'submitted_at'=>$submitted_at,'language'=>$this->current_lang(),'ip_address'=>$this->get_ip_address(),'user_agent'=>sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']??'')),'status'=>'submitted','proof_hash'=>$hash];
        foreach($meta as $k=>$v){ update_post_meta($post_id,'_ewb_'.$k,$v); }
        $this->maybe_update_order_status_on_submit($post_id,$meta);
        $this->send_emails($post_id,$meta);
        return '<div class="ewb-message"><strong>'.esc_html($this->t('success_title')).'</strong><br>'.esc_html($this->t('success_text')).'</div>';
    }

    private function maybe_update_order_status_on_submit(int $post_id,array $meta): void {
        $order_id = (int)($meta['order_id'] ?? 0);
        if(!$order_id){ return; }
        $order = wc_get_order($order_id);
        if(!$order instanceof WC_Order){ return; }
        $previous = $order->get_status();
        update_post_meta($post_id,'_ewb_previous_order_status',$previous);
        $note = sprintf('Withdrawal request #%d submitted. Products: %s', $post_id, implode('; ', (array)($meta['products'] ?? [])));
        if($this->get_settings()['change_order_status_on_submit']==='yes' && $previous !== 'withdrawal-requested'){
            $order->update_status('withdrawal-requested', $note, true);
        } else {
            $order->add_order_note($note);
        }
    }

    private function get_ip_address(): string { foreach(['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key){ if(!empty($_SERVER[$key])){ $raw=sanitize_text_field(wp_unslash($_SERVER[$key])); return trim(explode(',',$raw)[0]); } } return ''; }

    private function email_body(int $post_id,array $m): string { $products='<ul><li>'.implode('</li><li>',array_map('esc_html',(array)$m['products'])).'</li></ul>'; $body='<p>'.esc_html($this->t('received_body')).'</p><p><strong>'.esc_html($this->t('request_id')).':</strong> #'.esc_html($post_id).'<br><strong>'.esc_html($this->t('proof_hash')).':</strong> '.esc_html($m['proof_hash']).'<br><strong>'.esc_html($this->t('name')).':</strong> '.esc_html($m['customer_name']).'<br><strong>'.esc_html($this->t('email')).':</strong> '.esc_html($m['customer_email']).'<br><strong>'.esc_html($this->t('order')).':</strong> '.esc_html($m['order_number']).'<br><strong>'.esc_html($this->t('submitted_at')).':</strong> '.esc_html($m['submitted_at']).'</p><p><strong>'.esc_html($this->t('products')).':</strong></p>'.$products.'<p><strong>'.esc_html($this->t('declaration_label')).':</strong><br>'.esc_html($this->t('declaration')).'</p>'; if(!empty($m['comments'])){ $body.='<p><strong>'.esc_html($this->t('comments')).':</strong><br>'.nl2br(esc_html($m['comments'])).'</p>'; } return $body.'<p>'.esc_html($this->t('next_steps')).'</p>'; }

    private function get_admin_email_recipient(): string {
        $s = $this->get_settings();
        $admin = sanitize_email($s['admin_email'] ?? '');
        if(!$admin || !is_email($admin)){ $admin = sanitize_email(get_option('admin_email')); }
        return is_email($admin) ? $admin : '';
    }

    private function add_email_failure_note(int $post_id,string $recipient_type,string $recipient,string $message): void {
        $message = sprintf('Withdrawal request #%d %s email failed for %s: %s', $post_id, $recipient_type, $recipient ?: 'no valid recipient', $message);
        add_post_meta($post_id, '_ewb_email_error', $message);
        $order_id = (int)get_post_meta($post_id, '_ewb_order_id', true);
        $order = $order_id ? wc_get_order($order_id) : false;
        if($order instanceof WC_Order){ $order->add_order_note($message); }
        if(defined('WP_DEBUG') && WP_DEBUG){ error_log('[EU Withdrawal Button] '.$message); }
    }

    private function send_emails(int $post_id,array $meta): void {
        update_post_meta($post_id, '_ewb_email_attempted_at', current_time('mysql'));
        $headers = ['Content-Type: text/html; charset=UTF-8'];
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
    }

    private function send_customer_email(int $post_id,array $meta,string $body,array $headers,array $attachments): void {
        $customer_email = sanitize_email($meta['customer_email'] ?? '');
        if($customer_email && is_email($customer_email)){
            $customer_sent = wp_mail($customer_email, $this->t('email_subject'), $body, $headers, $attachments);
            update_post_meta($post_id, '_ewb_customer_email_sent', $customer_sent ? 'yes' : 'no');
            if(!$customer_sent){ $this->add_email_failure_note($post_id, 'customer', $customer_email, 'wp_mail returned false'); }
        } else {
            update_post_meta($post_id, '_ewb_customer_email_sent', 'no');
            $this->add_email_failure_note($post_id, 'customer', $customer_email, 'invalid recipient');
        }
    }

    private function send_admin_email(int $post_id,array $meta,string $body,array $headers): void {
        $admin_email = $this->get_admin_email_recipient();
        $admin = $body.'<p><a href="'.esc_url(admin_url('post.php?post='.$post_id.'&action=edit')).'">'.esc_html($this->t('view_admin')).'</a></p>';
        if($admin_email){
            $admin_sent = wp_mail($admin_email, $this->t('admin_new_subject').' '.$meta['order_number'], $admin, $headers);
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
            $sent = wp_mail($recipient, 'EU Withdrawal Button test email', '<p>This is a test email from EU Withdrawal Button for WooCommerce.</p>', ['Content-Type: text/html; charset=UTF-8']);
        }
        if(!$sent && defined('WP_DEBUG') && WP_DEBUG){ error_log('[EU Withdrawal Button] Test email failed for '.($recipient ?: 'no valid recipient')); }
        wp_safe_redirect(add_query_arg('ewb_test_email', $sent ? 'sent' : 'failed', admin_url('admin.php?page=ewb-settings')));
        exit;
    }

    private function create_pdf_receipt(int $post_id,array $m): string {
        $upload=wp_upload_dir(); if(!empty($upload['error'])){ return ''; } $dir=trailingslashit($upload['basedir']).'ewb-receipts'; if(!wp_mkdir_p($dir)){ return ''; }
        $lines=[ $this->t('email_subject'), $this->t('request_id').': #'.$post_id, $this->t('proof_hash').': '.$m['proof_hash'], $this->t('name').': '.$m['customer_name'], $this->t('email').': '.$m['customer_email'], $this->t('order').': '.$m['order_number'], $this->t('submitted_at').': '.$m['submitted_at'], $this->t('products').': '.implode('; ',(array)$m['products']), $this->t('declaration_label').': '.$this->t('declaration') ];
        $text=implode("\n", array_map([$this,'pdf_sanitize'], $lines)); $content=$this->simple_pdf($text); $path=$dir.'/withdrawal-receipt-'.$post_id.'.pdf'; return @file_put_contents($path,$content) === false ? '' : $path;
    }
    private function pdf_sanitize($s): string { $s=wp_strip_all_tags((string)$s); $map=['€'=>'EUR','–'=>'-','—'=>'-','“'=>'"','”'=>'"','’'=>"'"]; $s=strtr($s,$map); return function_exists('iconv') ? @iconv('UTF-8','ISO-8859-1//TRANSLIT//IGNORE',$s) ?: $s : $s; }
    private function simple_pdf(string $text): string { $text=str_replace(["\\","(",")"],["\\\\","\\(","\\)"],$text); $rows=explode("\n",wordwrap($text,92,"\n")); $stream="BT /F1 10 Tf 50 790 Td 14 TL "; foreach($rows as $row){ $stream.='('.$row.') Tj T* '; } $stream.='ET'; $objs=[]; $objs[]='1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj'; $objs[]='2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj'; $objs[]='3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj'; $objs[]='4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj'; $objs[]='5 0 obj << /Length '.strlen($stream).' >> stream' . "\n" . $stream . "\nendstream endobj"; $pdf="%PDF-1.4\n"; $xref=[]; foreach($objs as $o){ $xref[]=strlen($pdf); $pdf.=$o."\n"; } $start=strlen($pdf); $pdf.="xref\n0 ".(count($objs)+1)."\n0000000000 65535 f \n"; foreach($xref as $x){ $pdf.=sprintf('%010d 00000 n ', $x)."\n"; } $pdf.='trailer << /Size '.(count($objs)+1).' /Root 1 0 R >>' . "\nstartxref\n$start\n%%EOF"; return $pdf; }

    public function admin_columns(array $cols): array { return ['cb'=>$cols['cb']??'','title'=>'Request','order'=>'Order','customer'=>'Customer','language'=>'Lang','submitted'=>'Submitted','status'=>'Status','hash'=>'Proof hash','date'=>'Created']; }
    public function admin_column_content(string $col,int $post_id): void {
        if($col==='order'){
            $oid=(int)get_post_meta($post_id,'_ewb_order_id',true);
            $num=get_post_meta($post_id,'_ewb_order_number',true);
            echo $oid ? '<a href="'.esc_url(admin_url('post.php?post='.$oid.'&action=edit')).'">#'.esc_html($num).'</a>' : esc_html($num);
        } elseif($col==='customer'){
            echo esc_html(get_post_meta($post_id,'_ewb_customer_name',true)).'<br><a href="mailto:'.esc_attr(get_post_meta($post_id,'_ewb_customer_email',true)).'">'.esc_html(get_post_meta($post_id,'_ewb_customer_email',true)).'</a>';
        } elseif($col==='language'){
            echo esc_html(get_post_meta($post_id,'_ewb_language',true));
        } elseif($col==='submitted'){
            echo esc_html(get_post_meta($post_id,'_ewb_submitted_at',true));
        } elseif($col==='status'){
            $statuses=$this->statuses(); $status=get_post_meta($post_id,'_ewb_status',true)?:'submitted'; echo esc_html($statuses[$status] ?? $status);
        } elseif($col==='hash'){
            echo '<code>'.esc_html(substr((string)get_post_meta($post_id,'_ewb_proof_hash',true),0,12)).'…</code>';
        }
    }
    public function sortable_columns(array $cols): array { $cols['submitted']='submitted'; return $cols; }
    public function add_metaboxes(): void { add_meta_box('ewb_details','Withdrawal Request Details',[$this,'details_metabox'],self::CPT,'normal','high'); add_meta_box('ewb_workflow','Workflow',[$this,'workflow_metabox'],self::CPT,'side','default'); }
    public function details_metabox($post): void {
        $fields=['customer_name'=>'Customer name','customer_email'=>'Customer email','order_number'=>'Order number','submitted_at'=>'Submitted at','language'=>'Language','status'=>'Request status','previous_order_status'=>'Previous order status','proof_hash'=>'Proof hash','comments'=>'Comments','ip_address'=>'IP address','user_agent'=>'User agent'];
        echo '<table class="widefat striped"><tbody>';
        foreach($fields as $k=>$l){
            $value = get_post_meta($post->ID,'_ewb_'.$k,true);
            if($k==='status'){ $statuses=$this->statuses(); $value=$statuses[$value] ?? $value; }
            echo '<tr><th style="width:180px">'.esc_html($l).'</th><td>'.nl2br(esc_html((string)$value)).'</td></tr>';
        }
        $products=get_post_meta($post->ID,'_ewb_products',true); echo '<tr><th>Products</th><td><ul>'; foreach((array)$products as $p){ echo '<li>'.esc_html($p).'</li>'; } echo '</ul></td></tr>';
        $oid=(int)get_post_meta($post->ID,'_ewb_order_id',true); if($oid){ $order=wc_get_order($oid); $status=$order instanceof WC_Order ? wc_get_order_status_name($order->get_status()) : ''; echo '<tr><th>WooCommerce order</th><td><a href="'.esc_url(admin_url('post.php?post='.$oid.'&action=edit')).'">View order #'.esc_html($oid).'</a>'.($status?' — '.esc_html($status):'').'</td></tr>'; }
        echo '</tbody></table>';
    }
    private function statuses(): array { return ['submitted'=>'Submitted','in_review'=>'In Review','approved'=>'Approved','rejected'=>'Rejected','completed'=>'Completed','refunded'=>'Refunded']; }
    public function workflow_metabox($post): void {
        wp_nonce_field(self::ADMIN_NONCE,'ewb_admin_nonce');
        $status=get_post_meta($post->ID,'_ewb_status',true)?:'submitted';
        echo '<p><label>Status<br><select name="ewb_status">'; foreach($this->statuses() as $k=>$l){ echo '<option value="'.esc_attr($k).'" '.selected($status,$k,false).'>'.esc_html($l).'</option>'; } echo '</select></label></p>';
        echo '<p><label>Internal note<br><textarea name="ewb_internal_note" rows="5" style="width:100%">'.esc_textarea(get_post_meta($post->ID,'_ewb_internal_note',true)).'</textarea></label></p>';
        $oid=(int)get_post_meta($post->ID,'_ewb_order_id',true); if($oid){ $order=wc_get_order($oid); if($order instanceof WC_Order){ echo '<p><strong>Order status:</strong><br>'.esc_html(wc_get_order_status_name($order->get_status())).'</p><p><a class="button" href="'.esc_url(admin_url('post.php?post='.$oid.'&action=edit')).'">Open order</a></p>'; } }
        echo '<p class="description">Use this status for the internal withdrawal workflow. The WooCommerce order is automatically set to “Withdrawal requested” when the customer submits the request, if enabled in settings.</p>';
    }
    public function save_request_admin(int $post_id,$post): void {
        if(!isset($_POST['ewb_admin_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ewb_admin_nonce'])), self::ADMIN_NONCE) || !current_user_can('edit_shop_orders')){ return; }
        $old_status=get_post_meta($post_id,'_ewb_status',true)?:'submitted';
        $status=sanitize_key($_POST['ewb_status']??'submitted');
        if(array_key_exists($status,$this->statuses())){ update_post_meta($post_id,'_ewb_status',$status); }
        $note=sanitize_textarea_field(wp_unslash($_POST['ewb_internal_note']??''));
        update_post_meta($post_id,'_ewb_internal_note',$note);
        if($status !== $old_status){
            $oid=(int)get_post_meta($post_id,'_ewb_order_id',true); $order=$oid?wc_get_order($oid):false;
            if($order instanceof WC_Order){ $order->add_order_note(sprintf('Withdrawal request #%d workflow status changed from %s to %s.%s', $post_id, $old_status, $status, $note ? ' Note: '.$note : '')); }
        }
    }
    public function export_csv(): void { if(!current_user_can('manage_woocommerce') || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce']??'')),'ewb_export_csv')){ wp_die('Forbidden'); } header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=withdrawal-requests.csv'); $out=fopen('php://output','w'); fputcsv($out,['ID','Order','Customer','Email','Submitted','Status','Language','Products','Proof hash']); $q=new WP_Query(['post_type'=>self::CPT,'posts_per_page'=>-1,'post_status'=>'publish']); foreach($q->posts as $p){ fputcsv($out,[$p->ID,get_post_meta($p->ID,'_ewb_order_number',true),get_post_meta($p->ID,'_ewb_customer_name',true),get_post_meta($p->ID,'_ewb_customer_email',true),get_post_meta($p->ID,'_ewb_submitted_at',true),get_post_meta($p->ID,'_ewb_status',true),get_post_meta($p->ID,'_ewb_language',true),implode('; ',(array)get_post_meta($p->ID,'_ewb_products',true)),get_post_meta($p->ID,'_ewb_proof_hash',true)]); } exit; }
}

register_activation_hook(__FILE__, ['EU_Withdrawal_Button_Plugin','activate']);
register_deactivation_hook(__FILE__, ['EU_Withdrawal_Button_Plugin','deactivate']);
add_action('plugins_loaded', ['EU_Withdrawal_Button_Plugin','instance']);
