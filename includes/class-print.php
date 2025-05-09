<?php

class Print_Class {
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    // Generate HTML
    public function generateHTML() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_setting'; // テーブル名を適切に設定してください

        // データベースからテンプレートを取得
        $template_row = $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1");
        $template = $template_row->template_content;

        $replacements = array(
            '_%postal_code%_' => $this->data['postal_code'],
            '_％prefecture％_' => $this->data['prefecture'],
            '_％city％_' => $this->data['city'],
            '_%address%_' => $this->data['address'],
            '_%building%_' => $this->data['building'],
            '_%customer%_' => $this->data['customer'],
            '_%user_name%_' => $this->data['user_name']
        );

        $html = strtr($template, $replacements);

        return $html;
    }
}