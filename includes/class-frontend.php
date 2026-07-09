<?php
if (!defined('ABSPATH')) { exit; }

abstract class EU_Withdrawal_Button_Frontend extends EU_Withdrawal_Button_Emails {
    public function enqueue_styles(): void {
        $mode = $this->get_settings()['css_mode'];
        if ($mode === 'none') { return; }
        $css = '.ewb-form-wrap{max-width:var(--ewb-form-max-width,760px);margin-inline:auto;text-align:center}.ewb-form{max-width:100%;margin-inline:auto;text-align:start}.ewb-form .ewb-field{margin-block:1rem}.ewb-form label{display:block;margin-bottom:.35rem}.ewb-form input[type=text],.ewb-form input[type=email],.ewb-form input[type=number],.ewb-form textarea,.ewb-form select{width:100%;max-width:100%;box-sizing:border-box}.ewb-form fieldset{margin:1rem 0}.ewb-form .ewb-item{display:grid;grid-template-columns:minmax(0,1fr) minmax(90px,130px);gap:1rem;align-items:center;margin:.65rem 0}.ewb-form-heading,.ewb-form-intro,.ewb-form-helper,.ewb-message{text-align:center}.ewb-message{margin:1rem 0}.ewb-actions{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;justify-content:center}.ewb-muted{opacity:.75}';
        wp_register_style('ewb-inline', false, [], self::VERSION); wp_enqueue_style('ewb-inline'); wp_add_inline_style('ewb-inline', $css);
    }

    protected function button_label(): string { $s=$this->get_settings(); return $s['button_label_override'] ?: $this->t('button_label'); }
    protected function button_classes(string $context,string $extra=''): string { return $this->frontend_button_classes($context, trim('woocommerce-button button '.$extra)); }
    protected function render_form_heading(): void { if($this->get_settings()['hide_form_heading']!=='yes'){ echo '<h2 class="ewb-form-heading">'.esc_html($this->t('form_title')).'</h2>'; } }
    protected function render_form_helper_text(string $position): void { $text=$this->form_helper_text($position); if($text!==''){ echo '<div class="ewb-form-helper ewb-form-helper-'.esc_attr($position).'">'.wp_kses_post(wpautop($text)).'</div>'; } }
    protected function translated_page_id(int $page_id): int {
        if (!$page_id) { return 0; }
        $lang = $this->current_lang();
        $wpml_id = apply_filters('wpml_object_id', $page_id, 'page', true, $lang);
        if ($wpml_id) { return (int)$wpml_id; }
        if (function_exists('pll_get_post')) { $pll_id = pll_get_post($page_id, $lang); if ($pll_id) { return (int)$pll_id; } }
        return $page_id;
    }
    protected function page_url($order=null): string {
        $s=$this->get_settings();
        $page_id = !empty($s['page_id']) ? $this->translated_page_id((int)$s['page_id']) : 0;
        $url=$page_id ? get_permalink($page_id) : home_url('/withdrawal-cancel-contract/');
        $wpml_url = apply_filters('wpml_permalink', $url, $this->current_lang(), true);
        if (is_string($wpml_url) && $wpml_url) { $url = $wpml_url; }
        if($order instanceof WC_Order){ $url=add_query_arg(['order_id'=>$order->get_id(),'order_key'=>$order->get_order_key()],$url); }
        return $url;
    }
    public function shortcode_button(): string { return '<a class="'.esc_attr($this->button_classes('shortcode_button')).'" href="'.esc_url($this->page_url()).'">'.esc_html($this->button_label()).'</a>'; }

    public function add_order_action(array $actions,$order): array { if($order instanceof WC_Order && $this->is_order_eligible($order) && $this->eligible_items($order)){ $actions['ewb_withdrawal']=['url'=>$this->page_url($order),'name'=>$this->button_label()]; } return $actions; }
    public function order_details_button($order): void { if($order instanceof WC_Order && $this->is_order_eligible($order) && $this->eligible_items($order)){ echo '<p><a class="'.esc_attr($this->button_classes('order_details')).'" href="'.esc_url($this->page_url($order)).'">'.esc_html($this->button_label()).'</a></p>'; } }
    public function email_withdrawal_link($order,$sent_to_admin,$plain_text,$email): void { $s=$this->get_settings(); if($sent_to_admin || $s['show_in_order_emails']!=='yes' || !$order instanceof WC_Order || !$this->is_order_eligible($order)){ return; } $url=$this->page_url($order); if($plain_text){ echo "\n".$this->button_label().': '.esc_url_raw($url)."\n"; } else { echo '<p><a class="'.esc_attr($this->button_classes('email_link')).'" href="'.esc_url($url).'">'.esc_html($this->button_label()).'</a></p>'; } }

    protected function resolve_order_from_request(){
        $order_id=isset($_GET['order_id'])?absint($_GET['order_id']):(isset($_POST['ewb_order_id'])?absint($_POST['ewb_order_id']):0);
        $key=isset($_GET['order_key'])?sanitize_text_field(wp_unslash($_GET['order_key'])):(isset($_POST['ewb_order_key'])?sanitize_text_field(wp_unslash($_POST['ewb_order_key'])):'');
        if($order_id){ $o=wc_get_order($order_id); if($o && $key && hash_equals($o->get_order_key(),$key)){ return $o; } if($o && is_user_logged_in() && (int)$o->get_user_id()===get_current_user_id()){ return $o; } }
        if($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['ewb_lookup'])){ $num=sanitize_text_field(wp_unslash($_POST['ewb_order_number']??'')); $email=sanitize_email(wp_unslash($_POST['ewb_customer_email']??'')); return $this->find_order_by_number_email($num,$email); }
        return null;
    }
    protected function find_order_by_number_email($number,$email){
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
        if(!class_exists('WooCommerce')){ ob_start(); echo '<div class="ewb-form-wrap">'; $this->render_form_heading(); echo '<div class="ewb-message">WooCommerce is required.</div></div>'; return ob_get_clean(); }
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
        echo '<div class="ewb-form-wrap">';
        $this->render_form_heading();
        $this->render_form_helper_text('before');
        echo $message;
        if($render_form){ $this->render_form($order); }
        $this->render_form_helper_text('after');
        echo '</div>';
        return ob_get_clean();
    }

    protected function render_lookup_form(): void {
        $email = sanitize_email(wp_unslash($_POST['ewb_customer_email'] ?? ''));
        $num = sanitize_text_field(wp_unslash($_POST['ewb_order_number'] ?? ''));
        echo '<form class="ewb-form ewb-lookup-form" method="post">';
        wp_nonce_field(self::NONCE_ACTION,'ewb_nonce');
        echo '<p class="ewb-form-intro">'.esc_html($this->t('lookup_intro')).'</p>';
        echo '<div class="ewb-field"><label for="ewb_order_number">'.esc_html($this->t('order_number')).' *</label><input type="text" id="ewb_order_number" name="ewb_order_number" required value="'.esc_attr($num).'"></div>';
        echo '<div class="ewb-field"><label for="ewb_customer_email">'.esc_html($this->t('email')).' *</label><input type="email" id="ewb_customer_email" name="ewb_customer_email" required value="'.esc_attr($email).'"></div>';
        echo '<div class="ewb-actions"><button type="submit" class="'.esc_attr($this->button_classes('lookup_submit')).'" name="ewb_lookup" value="1">'.esc_html($this->t('lookup')).'</button></div>';
        echo '</form>';
    }

    protected function render_form($order=null): void {
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
        echo '<p class="ewb-form-intro">'.esc_html($this->t('form_intro')).'</p>';
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
            echo '<button type="submit" class="'.esc_attr($this->button_classes('form_submit', 'alt')).'" name="'.$btn.'" value="1">'.esc_html($this->get_settings()['require_two_step']==='yes'?$this->t('review_title'):$this->t('submit')).'</button>';
            echo '</div>';
        }
        echo '</form>';
    }

    protected function collect_posted_data(): array {
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

    protected function validate_data(array $d): string {
        if(!isset($_POST['ewb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ewb_nonce'])), self::NONCE_ACTION)){ return $this->t('security_failed'); }
        if(!$d['name'] || !is_email($d['email']) || !$d['order_number'] || !$d['products'] || !$d['declaration']){ return $this->t('required_fields'); }
        if($d['order_id']){ $order=wc_get_order($d['order_id']); if(!$order || !hash_equals($order->get_order_key(),$d['order_key']) || !$this->is_order_eligible($order)){ return $this->t('order_not_verified'); } }
        return '';
    }

    protected function render_review_step(): string {
        $d=$this->collect_posted_data(); $err=$this->validate_data($d); if($err){ return '<div class="ewb-message">'.esc_html($err).'</div>'; }
        ob_start(); echo '<form class="ewb-form" method="post"><div class="ewb-message"><h3>'.esc_html($this->t('review_title')).'</h3>';
        echo '<p><strong>'.esc_html($this->t('name')).':</strong> '.esc_html($d['name']).'<br><strong>'.esc_html($this->t('email')).':</strong> '.esc_html($d['email']).'<br><strong>'.esc_html($this->t('order')).':</strong> '.esc_html($d['order_number']).'</p><p><strong>'.esc_html($this->t('products')).':</strong></p><ul>'; foreach($d['products'] as $p){ echo '<li>'.esc_html($p).'</li>'; } echo '</ul><p><strong>'.esc_html($this->t('declaration_label')).':</strong><br>'.esc_html($this->t('declaration')).'</p></div>';
        wp_nonce_field(self::NONCE_ACTION,'ewb_nonce'); foreach($d as $k=>$v){ if($k==='products'){ foreach($v as $p){ echo '<input type="hidden" name="ewb_products_confirmed[]" value="'.esc_attr($p).'">'; } } elseif($k==='declaration'){ echo '<input type="hidden" name="ewb_declaration" value="1">'; } else { echo '<input type="hidden" name="ewb_'.esc_attr($k).'" value="'.esc_attr($v).'">'; } }
        echo '<div class="ewb-actions"><button type="submit" class="'.esc_attr($this->button_classes('confirm_back')).'" name="ewb_back" value="1">'.esc_html($this->t('back')).'</button><button type="submit" class="'.esc_attr($this->button_classes('confirm_submit', 'alt')).'" name="ewb_submit" value="1">'.esc_html($this->t('submit')).'</button></div></form>'; return ob_get_clean();
    }

    protected function handle_submission(): string {
        if(isset($_POST['ewb_products_confirmed'])){ $d=[ 'name'=>sanitize_text_field(wp_unslash($_POST['ewb_name']??'')), 'email'=>sanitize_email(wp_unslash($_POST['ewb_email']??'')), 'order_number'=>sanitize_text_field(wp_unslash($_POST['ewb_order_number']??'')), 'order_id'=>absint($_POST['ewb_order_id']??0), 'order_key'=>sanitize_text_field(wp_unslash($_POST['ewb_order_key']??'')), 'comments'=>sanitize_textarea_field(wp_unslash($_POST['ewb_comments']??'')), 'declaration'=>!empty($_POST['ewb_declaration']), 'products'=>array_map('sanitize_text_field', array_map('wp_unslash',(array)$_POST['ewb_products_confirmed'])) ]; }
        else { $d=$this->collect_posted_data(); }
        $err=$this->validate_data($d); if($err){ return '<div class="ewb-message">'.esc_html($err).'</div>'; }
        $submitted_at=current_time('mysql'); $payload=['order_number'=>$d['order_number'],'email'=>$d['email'],'products'=>$d['products'],'submitted_at'=>$submitted_at,'lang'=>$this->current_lang()]; $hash=hash('sha256', wp_json_encode($payload));
        $post_id=wp_insert_post(['post_type'=>self::CPT,'post_status'=>'publish','post_title'=>sprintf('Withdrawal request - Order %s - %s',$d['order_number'],$d['name'])], true);
        if(is_wp_error($post_id)){ return '<div class="ewb-message">Request could not be saved.</div>'; }
        $meta=['customer_name'=>$d['name'],'customer_email'=>$d['email'],'order_number'=>$d['order_number'],'order_id'=>$d['order_id'],'products'=>$d['products'],'comments'=>$d['comments'],'submitted_at'=>$submitted_at,'language'=>$this->current_lang(),'ip_address'=>$this->get_ip_address(),'user_agent'=>sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']??'')),'status'=>'submitted','proof_hash'=>$hash,'reference_code'=>$this->reference_code($post_id,$d['order_number'],$submitted_at)];
        foreach($meta as $k=>$v){ update_post_meta($post_id,'_ewb_'.$k,$v); }
        $this->maybe_update_order_status_on_submit($post_id,$meta);
        $this->send_emails($post_id,$meta);
        return '<div class="ewb-message"><strong>'.esc_html($this->t('success_title')).'</strong><br>'.esc_html($this->t('success_text')).'</div>';
    }

    protected function reference_code(int $post_id,string $order_number,string $submitted_at): string {
        $order = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$order_number));
        if($order === ''){ $order = (string)$post_id; }
        $date = date('Ymd', strtotime($submitted_at) ?: time());
        return sprintf('WD-%s-%s-%d', $order, $date, $post_id);
    }

    protected function get_ip_address(): string { foreach(['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key){ if(!empty($_SERVER[$key])){ $raw=sanitize_text_field(wp_unslash($_SERVER[$key])); return trim(explode(',',$raw)[0]); } } return ''; }
}

