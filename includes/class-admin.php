<?php
if (!defined('ABSPATH')) { exit; }

abstract class EU_Withdrawal_Button_Admin extends EU_Withdrawal_Button_Frontend {
    public function admin_menu(): void { add_submenu_page('woocommerce','EU Withdrawal Settings','Withdrawal Settings','manage_woocommerce','ewb-settings',[$this,'settings_page']); }

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
    public function workflow_metabox($post): void {
        wp_nonce_field(self::ADMIN_NONCE,'ewb_admin_nonce');
        $status=get_post_meta($post->ID,'_ewb_status',true)?:'submitted';
        echo '<p><label>Status<br><select name="ewb_status">'; foreach($this->statuses() as $k=>$l){ echo '<option value="'.esc_attr($k).'" '.selected($status,$k,false).'>'.esc_html($l).'</option>'; } echo '</select></label></p>';
        echo '<p><label>Internal note<br><textarea name="ewb_internal_note" rows="5" style="width:100%">'.esc_textarea(get_post_meta($post->ID,'_ewb_internal_note',true)).'</textarea></label></p>';
        $oid=(int)get_post_meta($post->ID,'_ewb_order_id',true); if($oid){ $order=wc_get_order($oid); if($order instanceof WC_Order){ echo '<p><strong>Order status:</strong><br>'.esc_html(wc_get_order_status_name($order->get_status())).'</p><p><a class="button button-secondary" href="'.esc_url(admin_url('post.php?post='.$oid.'&action=edit')).'">Open order</a></p>'; } }
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

