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

        // $this->data から値を取得
        $postal_code = $this->data['postal_code'] ?? '';
        $prefecture = $this->data['prefecture'] ?? '';
        $city = $this->data['city'] ?? '';
        $address = $this->data['address'] ?? '';
        $building = $this->data['building'] ?? '';
        $customer = $this->data['customer'] ?? '';
        $user_name = $this->data['user_name'] ?? '';

        $replacements = array(
            '_%postal_code%_' => $postal_code,
            '_%prefecture%_' => $prefecture,
            '_%city%_' => $city,
            '_%address%_' => $address,
            '_%building%_' => $building,
            '_%customer%_' => $customer,
            '_%user_name%_' => $user_name
        );

        $html = strtr($template, $replacements);

        return $html;
    }
}