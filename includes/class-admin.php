<?php
if (!defined('ABSPATH')) { exit; }

abstract class EU_Withdrawal_Button_Admin extends EU_Withdrawal_Button_Frontend {
    public function admin_menu(): void { add_submenu_page(null,'EU Withdrawal Settings','EU Withdrawal Settings','manage_woocommerce','ewb-settings',[$this,'settings_page']); }

    protected function actionable_statuses(): array { return ['submitted','in_review']; }
    protected function actionable_request_count(): int {
        if(!is_admin() || !current_user_can('edit_shop_orders')){ return 0; }
        global $wpdb;
        $statuses=$this->actionable_statuses();
        $placeholders=implode(',', array_fill(0, count($statuses), '%s'));
        $sql="SELECT COUNT(1) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key=%s WHERE p.post_type=%s AND p.post_status=%s AND pm.meta_value IN ($placeholders)";
        return (int)$wpdb->get_var($wpdb->prepare($sql, array_merge(['_ewb_status', self::CPT, 'publish'], $statuses)));
    }
    public function add_withdrawals_menu_badge(): void {
        if(!is_admin() || !current_user_can('edit_shop_orders')){ return; }
        $count=$this->actionable_request_count();
        if($count<1){ return; }
        global $submenu;
        if(empty($submenu['woocommerce']) || !is_array($submenu['woocommerce'])){ return; }
        foreach($submenu['woocommerce'] as &$item){
            if(!isset($item[2]) || $item[2] !== 'edit.php?post_type='.self::CPT){ continue; }
            $label=wp_strip_all_tags((string)$item[0]);
            $item[0]=esc_html($label).' <span class="update-plugins count-'.esc_attr((string)$count).'"><span class="plugin-count">'.esc_html(number_format_i18n($count)).'</span></span>';
            break;
        }
        unset($item);
    }

    public function admin_columns(array $cols): array { return ['cb'=>$cols['cb']??'','request_id'=>'Request ID','order'=>'WooCommerce order','customer_name'=>'Customer name','customer_email'=>'Customer email','language'=>'Language','submitted'=>'Submitted date','status'=>'Request status','order_status'=>'Order status']; }
    public function admin_column_content(string $col,int $post_id): void {
        if($col==='request_id'){
            echo '<strong>#'.esc_html($post_id).'</strong>';
        } elseif($col==='order'){
            $oid=(int)get_post_meta($post_id,'_ewb_order_id',true);
            $num=get_post_meta($post_id,'_ewb_order_number',true);
            echo $oid ? '<a href="'.esc_url(admin_url('post.php?post='.$oid.'&action=edit')).'">#'.esc_html($num).'</a>' : esc_html($num);
        } elseif($col==='customer_name'){
            echo esc_html(get_post_meta($post_id,'_ewb_customer_name',true));
        } elseif($col==='customer_email'){
            $email = get_post_meta($post_id,'_ewb_customer_email',true);
            echo $email ? '<a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a>' : '&mdash;';
        } elseif($col==='language'){
            echo esc_html(get_post_meta($post_id,'_ewb_language',true));
        } elseif($col==='submitted'){
            echo esc_html(get_post_meta($post_id,'_ewb_submitted_at',true));
        } elseif($col==='status'){
            $statuses=$this->statuses(); $status=get_post_meta($post_id,'_ewb_status',true)?:'submitted'; echo esc_html($statuses[$status] ?? $status);
        } elseif($col==='order_status'){
            $order = $this->request_order($post_id);
            echo $order instanceof WC_Order ? esc_html(wc_get_order_status_name($order->get_status())) : '&mdash;';
        }
    }
    public function sortable_columns(array $cols): array { $cols['request_id']='ID'; $cols['submitted']='submitted'; return $cols; }
    public function primary_column(string $default,string $screen_id): string {
        return $screen_id==='edit-'.self::CPT ? 'request_id' : $default;
    }
    public function add_metaboxes(): void { add_meta_box('ewb_details','Withdrawal Request Details',[$this,'details_metabox'],self::CPT,'normal','high'); add_meta_box('ewb_workflow','Workflow',[$this,'workflow_metabox'],self::CPT,'side','default'); }
    public function details_metabox($post): void {
        $fields=['customer_name'=>'Customer name','customer_email'=>'Customer email','order_number'=>'Order number','submitted_at'=>'Submitted at','language'=>'Language','status'=>'Request status','previous_order_status'=>'Previous order status','reference_code'=>'Reference code','proof_hash'=>'Proof hash','pdf_receipt_skipped'=>'PDF receipt skipped','comments'=>'Comments','ip_address'=>'IP address','user_agent'=>'User agent'];
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
    protected function statuses(): array { return ['submitted'=>'Submitted','in_review'=>'In Review','approved'=>'Approved','rejected'=>'Rejected','completed'=>'Completed','refunded'=>'Refunded']; }
    protected function row_action_statuses(): array { return ['in_review'=>'Mark In Review','approved'=>'Approve','rejected'=>'Reject','completed'=>'Complete','refunded'=>'Refund']; }
    protected function request_order(int $post_id) {
        $oid=(int)get_post_meta($post_id,'_ewb_order_id',true);
        return $oid ? wc_get_order($oid) : false;
    }
    protected function workflow_action_url(int $post_id,string $status): string {
        return wp_nonce_url(admin_url('admin-post.php?action=ewb_workflow_action&request_id='.$post_id.'&workflow_status='.rawurlencode($status)), 'ewb_workflow_action_'.$post_id.'_'.$status);
    }
    protected function update_workflow_status(int $post_id,string $status,string $note=''): bool {
        if(!array_key_exists($status,$this->statuses())){ return false; }
        $old_status=get_post_meta($post_id,'_ewb_status',true)?:'submitted';
        update_post_meta($post_id,'_ewb_status',$status);
        if($note !== ''){ update_post_meta($post_id,'_ewb_internal_note',$note); }
        if($status !== $old_status){
            $order=$this->request_order($post_id);
            if($order instanceof WC_Order){
                $statuses=$this->statuses();
                $order->add_order_note(sprintf('Withdrawal request #%d workflow status changed from %s to %s.%s', $post_id, $statuses[$old_status] ?? $old_status, $statuses[$status] ?? $status, $note ? ' Note: '.$note : ''));
            }
        }
        return true;
    }
    public function row_actions(array $actions,$post): array {
        if(!$post instanceof WP_Post || $post->post_type!==self::CPT || !current_user_can('edit_shop_order', $post->ID)){ return $actions; }
        $current=get_post_meta($post->ID,'_ewb_status',true)?:'submitted';
        foreach($this->row_action_statuses() as $status=>$label){
            if($status===$current){ continue; }
            $actions['ewb_'.$status]='<a href="'.esc_url($this->workflow_action_url((int)$post->ID,$status)).'">'.esc_html($label).'</a>';
        }
        $order=$this->request_order((int)$post->ID);
        if($order instanceof WC_Order){
            $actions['ewb_view_order']='<a href="'.esc_url(admin_url('post.php?post='.$order->get_id().'&action=edit')).'">'.esc_html__('View Order','eu-withdrawal-button').'</a>';
        }
        return $actions;
    }
    public function handle_workflow_action(): void {
        $post_id=absint($_GET['request_id']??0);
        $status=sanitize_key($_GET['workflow_status']??'');
        if(!$post_id || get_post_type($post_id)!==self::CPT || !current_user_can('edit_shop_order',$post_id)){ wp_die('Forbidden'); }
        check_admin_referer('ewb_workflow_action_'.$post_id.'_'.$status);
        $this->update_workflow_status($post_id,$status);
        wp_safe_redirect(add_query_arg(['post_type'=>self::CPT,'ewb_workflow_updated'=>$status], admin_url('edit.php')));
        exit;
    }
    public function admin_filters(string $post_type): void {
        if($post_type!==self::CPT){ return; }
        $request_status=sanitize_key($_GET['ewb_request_status']??'');
        $language=sanitize_key($_GET['ewb_language']??'');
        $order_status=sanitize_key($_GET['ewb_order_status']??'');
        $submitted_from=sanitize_text_field(wp_unslash($_GET['ewb_submitted_from']??''));
        $submitted_to=sanitize_text_field(wp_unslash($_GET['ewb_submitted_to']??''));
        echo '<select name="ewb_request_status"><option value="">All request statuses</option>'; foreach($this->statuses() as $key=>$label){ echo '<option value="'.esc_attr($key).'" '.selected($request_status,$key,false).'>'.esc_html($label).'</option>'; } echo '</select>';
        echo '<select name="ewb_language"><option value="">All languages</option>'; foreach(['en'=>'English','el'=>'Greek','es'=>'Spanish','hu'=>'Hungarian'] as $key=>$label){ echo '<option value="'.esc_attr($key).'" '.selected($language,$key,false).'>'.esc_html($label).'</option>'; } echo '</select>';
        echo '<select name="ewb_order_status"><option value="">All order statuses</option>'; foreach(wc_get_order_statuses() as $key=>$label){ $value=str_replace('wc-','',$key); echo '<option value="'.esc_attr($value).'" '.selected($order_status,$value,false).'>'.esc_html($label).'</option>'; } echo '</select>';
        echo '<input type="date" name="ewb_submitted_from" value="'.esc_attr($submitted_from).'" aria-label="Submitted from">';
        echo '<input type="date" name="ewb_submitted_to" value="'.esc_attr($submitted_to).'" aria-label="Submitted to">';
    }
    public function filter_admin_requests($query): void {
        if(!is_admin() || !$query->is_main_query() || $query->get('post_type')!==self::CPT){ return; }
        if($query->get('orderby')==='submitted'){ $query->set('meta_key','_ewb_submitted_at'); $query->set('orderby','meta_value'); }
        $meta_query=$query->get('meta_query');
        $meta_query=is_array($meta_query) ? $meta_query : [];
        $request_status=sanitize_key($_GET['ewb_request_status']??'');
        if($request_status && array_key_exists($request_status,$this->statuses())){ $meta_query[]=['key'=>'_ewb_status','value'=>$request_status]; }
        $language=sanitize_key($_GET['ewb_language']??'');
        if(in_array($language,['en','el','es','hu'],true)){ $meta_query[]=['key'=>'_ewb_language','value'=>$language]; }
        $submitted_from=sanitize_text_field(wp_unslash($_GET['ewb_submitted_from']??''));
        $submitted_to=sanitize_text_field(wp_unslash($_GET['ewb_submitted_to']??''));
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$submitted_from)){ $meta_query[]=['key'=>'_ewb_submitted_at','value'=>$submitted_from.' 00:00:00','compare'=>'>=','type'=>'DATETIME']; }
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$submitted_to)){ $meta_query[]=['key'=>'_ewb_submitted_at','value'=>$submitted_to.' 23:59:59','compare'=>'<=','type'=>'DATETIME']; }
        $order_status=sanitize_key($_GET['ewb_order_status']??'');
        if($order_status){
            $matching=$this->request_ids_for_order_status($order_status);
            $query->set('post__in',$matching ?: [0]);
        }
        if($meta_query){ $query->set('meta_query',$meta_query); }
    }
    protected function request_ids_for_order_status(string $status): array {
        $valid=array_map(static function($key){ return str_replace('wc-','',$key); }, array_keys(wc_get_order_statuses()));
        if(!in_array($status,$valid,true)){ return []; }
        static $running=false;
        if($running){ return []; }
        $running=true;
        remove_action('pre_get_posts', [$this, 'filter_admin_requests']);
        $ids=get_posts(['post_type'=>self::CPT,'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','no_found_rows'=>true,'suppress_filters'=>true]);
        add_action('pre_get_posts', [$this, 'filter_admin_requests']);
        $running=false;
        $matching=[];
        foreach($ids as $post_id){
            $order=$this->request_order((int)$post_id);
            if($order instanceof WC_Order && $order->get_status()===$status){ $matching[]=(int)$post_id; }
        }
        return $matching;
    }
    public function workflow_metabox($post): void {
        wp_nonce_field(self::ADMIN_NONCE,'ewb_admin_nonce');
        $status=get_post_meta($post->ID,'_ewb_status',true)?:'submitted';
        echo '<p><label>Status<br><select name="ewb_status">'; foreach($this->statuses() as $k=>$l){ echo '<option value="'.esc_attr($k).'" '.selected($status,$k,false).'>'.esc_html($l).'</option>'; } echo '</select></label></p>';
        echo '<p><label>Internal note<br><textarea name="ewb_internal_note" rows="5" style="width:100%">'.esc_textarea(get_post_meta($post->ID,'_ewb_internal_note',true)).'</textarea></label></p>';
        $oid=(int)get_post_meta($post->ID,'_ewb_order_id',true); if($oid){ $order=wc_get_order($oid); if($order instanceof WC_Order){ echo '<p><strong>Order status:</strong><br>'.esc_html(wc_get_order_status_name($order->get_status())).'</p><p><a class="'.esc_attr($this->admin_button_classes('admin_workflow_action', 'button button-secondary')).'" href="'.esc_url(admin_url('post.php?post='.$oid.'&action=edit')).'">Open order</a></p>'; } }
        echo '<p class="description">Use this status for the internal withdrawal workflow. The WooCommerce order is automatically set to “Withdrawal requested” when the customer submits the request, if enabled in settings.</p>';
    }
    public function save_request_admin(int $post_id,$post): void {
        if(!isset($_POST['ewb_admin_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ewb_admin_nonce'])), self::ADMIN_NONCE) || !current_user_can('edit_shop_orders')){ return; }
        $status=sanitize_key($_POST['ewb_status']??'submitted');
        $note=sanitize_textarea_field(wp_unslash($_POST['ewb_internal_note']??''));
        $this->update_workflow_status($post_id,$status,$note);
    }
    public function export_csv(): void { if(!current_user_can('manage_woocommerce') || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce']??'')),'ewb_export_csv')){ wp_die('Forbidden'); } header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=withdrawal-requests.csv'); $out=fopen('php://output','w'); fputcsv($out,['ID','Order','Customer','Email','Submitted','Status','Language','Products','Proof hash']); $q=new WP_Query(['post_type'=>self::CPT,'posts_per_page'=>-1,'post_status'=>'publish']); foreach($q->posts as $p){ fputcsv($out,[$p->ID,get_post_meta($p->ID,'_ewb_order_number',true),get_post_meta($p->ID,'_ewb_customer_name',true),get_post_meta($p->ID,'_ewb_customer_email',true),get_post_meta($p->ID,'_ewb_submitted_at',true),get_post_meta($p->ID,'_ewb_status',true),get_post_meta($p->ID,'_ewb_language',true),implode('; ',(array)get_post_meta($p->ID,'_ewb_products',true)),get_post_meta($p->ID,'_ewb_proof_hash',true)]); } exit; }
}
