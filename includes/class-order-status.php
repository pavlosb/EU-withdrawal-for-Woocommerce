<?php
if (!defined('ABSPATH')) { exit; }

abstract class EU_Withdrawal_Button_Order_Status extends EU_Withdrawal_Button_Settings {
    const ORDER_STATUS = 'withdrawal-req';
    const LEGACY_ORDER_STATUS = 'withdrawal-requested';
    const LEGACY_TRUNCATED_ORDER_STATUS = 'withdrawal-reques';

    public function register_cpt(): void {
        register_post_type(self::CPT, [
            'labels'=>['name'=>'Withdrawal Requests','singular_name'=>'Withdrawal Request','menu_name'=>'Withdrawals','edit_item'=>'View Withdrawal Request'],
            'public'=>false,'show_ui'=>true,'show_in_menu'=>'woocommerce','show_in_admin_bar'=>false,'supports'=>['title'],'capability_type'=>'shop_order','map_meta_cap'=>true,'capabilities'=>['create_posts'=>'do_not_allow'],
        ]);
    }
    public function register_withdrawal_order_status(): void {
        register_post_status('wc-'.self::ORDER_STATUS, $this->withdrawal_order_status_args());
    }

    public function register_withdrawal_order_status_args(array $statuses): array {
        $statuses['wc-'.self::ORDER_STATUS] = $this->withdrawal_order_status_args();
        return $statuses;
    }

    protected function withdrawal_order_status_args(): array {
        return [
            'label' => 'Withdrawal requested',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Withdrawal requested <span class="count">(%s)</span>', 'Withdrawal requested <span class="count">(%s)</span>', 'eu-withdrawal-button'),
        ];
    }

    public function add_withdrawal_order_status(array $statuses): array {
        $custom_key = 'wc-'.self::ORDER_STATUS;
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

    protected function is_order_eligible(WC_Order $order): bool {
        $s=$this->get_settings(); $status=$order->get_status(); if(!in_array($status,(array)$s['allowed_statuses'],true)){ return false; }
        $days=(int)$s['eligibility_days']; if($days>0){ $date=$order->get_date_completed() ?: $order->get_date_created(); if($date){ $limit=$date->getTimestamp()+($days*DAY_IN_SECONDS); if(time()>$limit){ return false; } } }
        return true;
    }

    protected function excluded_ids(): array { $raw=$this->get_settings()['excluded_product_ids']; return array_filter(array_map('absint', preg_split('/[,\s]+/', (string)$raw))); }
    protected function is_item_eligible(WC_Order_Item_Product $item): bool {
        $s=$this->get_settings(); $product=$item->get_product(); if(!$product){ return false; }
        $ids=$this->excluded_ids(); if(in_array((int)$product->get_id(),$ids,true) || in_array((int)$product->get_parent_id(),$ids,true)){ return false; }
        if($s['exclude_virtual']==='yes' && $product->is_virtual()){ return false; }
        if($s['exclude_downloadable']==='yes' && $product->is_downloadable()){ return false; }
        if($s['exclude_external']==='yes' && $product->is_type('external')){ return false; }
        $cat_ids=(array)$s['excluded_category_ids']; if($cat_ids){ $pid=$product->get_parent_id() ?: $product->get_id(); $terms=wc_get_product_cat_ids($pid); if(array_intersect($cat_ids,$terms)){ return false; } }
        return true;
    }

    protected function eligible_items(WC_Order $order): array { $out=[]; foreach($order->get_items() as $item_id=>$item){ if($item instanceof WC_Order_Item_Product && $this->is_item_eligible($item)){ $out[$item_id]=$item; } } return $out; }

    protected function maybe_update_order_status_on_submit(int $post_id,array $meta): void {
        $order_id = (int)($meta['order_id'] ?? 0);
        if(!$order_id){ return; }
        $order = wc_get_order($order_id);
        if(!$order instanceof WC_Order){ return; }
        $previous = $order->get_status();
        update_post_meta($post_id,'_ewb_previous_order_status',$previous);
        $note = sprintf('Withdrawal request #%d submitted. Products: %s', $post_id, implode('; ', (array)($meta['products'] ?? [])));
        if($this->get_settings()['change_order_status_on_submit']==='yes' && !$this->is_withdrawal_order_status($previous)){
            $this->persist_withdrawal_order_status($order,$post_id,$note);
        } else {
            $order->add_order_note($note);
        }
    }

    protected function is_withdrawal_order_status(string $status): bool {
        return in_array($status, [self::ORDER_STATUS,self::LEGACY_ORDER_STATUS,self::LEGACY_TRUNCATED_ORDER_STATUS], true);
    }

    protected function persist_withdrawal_order_status(WC_Order $order,int $post_id,string $note): void {
        try {
            $order->set_status(self::ORDER_STATUS);
            $order->save();
            $fresh = wc_get_order($order->get_id());
            $persisted = $fresh instanceof WC_Order ? $fresh->get_status() : '';
            if($persisted === self::ORDER_STATUS){
                $fresh->add_order_note($note);
                update_post_meta($post_id,'_ewb_order_status_changed','yes');
                return;
            }
            $this->log_order_status_failure($post_id,$order,sprintf('Expected %s after save, got %s.', self::ORDER_STATUS, $persisted ?: 'unknown'));
        } catch (Throwable $e) {
            $this->log_order_status_failure($post_id,$order,$e->getMessage());
        }
    }

    protected function log_order_status_failure(int $post_id,WC_Order $order,string $message): void {
        $message = 'Withdrawal request #'.$post_id.' could not persist order status "Withdrawal requested": '.$message;
        update_post_meta($post_id,'_ewb_order_status_changed','no');
        update_post_meta($post_id,'_ewb_order_status_error',$message);
        $order->add_order_note($message);
        if(defined('WP_DEBUG') && WP_DEBUG){ error_log('[EU Withdrawal Button] '.$message); }
    }
}

