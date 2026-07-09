<?php
if (!defined('ABSPATH')) { exit; }

abstract class EU_Withdrawal_Button_PDF extends EU_Withdrawal_Button_Order_Status {
    protected function create_pdf_receipt(int $post_id,array $m): string {
        $upload=wp_upload_dir(); if(!empty($upload['error'])){ return ''; } $dir=trailingslashit($upload['basedir']).'ewb-receipts'; if(!wp_mkdir_p($dir)){ return ''; }
        $lines=[ $this->t('email_subject'), $this->t('request_id').': #'.$post_id, $this->t('proof_hash').': '.$m['proof_hash'], $this->t('name').': '.$m['customer_name'], $this->t('email').': '.$m['customer_email'], $this->t('order').': '.$m['order_number'], $this->t('submitted_at').': '.$m['submitted_at'], $this->t('products').': '.implode('; ',(array)$m['products']), $this->t('declaration_label').': '.$this->t('declaration') ];
        $text=implode("\n", array_map([$this,'pdf_sanitize'], $lines)); $content=$this->simple_pdf($text); $path=$dir.'/withdrawal-receipt-'.$post_id.'.pdf'; return @file_put_contents($path,$content) === false ? '' : $path;
    }
    protected function pdf_sanitize($s): string { $s=wp_strip_all_tags((string)$s); $map=['€'=>'EUR','–'=>'-','—'=>'-','“'=>'"','”'=>'"','’'=>"'"]; $s=strtr($s,$map); return function_exists('iconv') ? @iconv('UTF-8','ISO-8859-1//TRANSLIT//IGNORE',$s) ?: $s : $s; }
    protected function simple_pdf(string $text): string { $text=str_replace(["\\","(",")"],["\\\\","\\(","\\)"],$text); $rows=explode("\n",wordwrap($text,92,"\n")); $stream="BT /F1 10 Tf 50 790 Td 14 TL "; foreach($rows as $row){ $stream.='('.$row.') Tj T* '; } $stream.='ET'; $objs=[]; $objs[]='1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj'; $objs[]='2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj'; $objs[]='3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj'; $objs[]='4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj'; $objs[]='5 0 obj << /Length '.strlen($stream).' >> stream' . "\n" . $stream . "\nendstream endobj"; $pdf="%PDF-1.4\n"; $xref=[]; foreach($objs as $o){ $xref[]=strlen($pdf); $pdf.=$o."\n"; } $start=strlen($pdf); $pdf.="xref\n0 ".(count($objs)+1)."\n0000000000 65535 f \n"; foreach($xref as $x){ $pdf.=sprintf('%010d 00000 n ', $x)."\n"; } $pdf.='trailer << /Size '.(count($objs)+1).' /Root 1 0 R >>' . "\nstartxref\n$start\n%%EOF"; return $pdf; }
}

