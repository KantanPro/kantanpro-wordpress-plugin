<?php

class KTP_Tab_Order {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_order_menu'));
    }

    public function display() {
        // ここに表示する内容を実装します。
        echo '<h3>受注書</h3>';
    }

    public function add_order_menu() {
        add_submenu_page(
            'ktp-main-menu', // 親メニューのスラッグ
            '受注書', // ページタイトル
            '受注書', // メニュータイトル
            'manage_options', // 権限
            'ktp-tab-order', // メニュースラッグ
            array($this, 'order_page_content') // 表示内容を生成するコールバック関数
        );
    }

    public function order_page_content() {
        echo '<h1>受注書管理</h1>';
        echo '<p>ここに受注書の管理と表示に関するコンテンツを表示します。</p>';
        // ここに受注書のデータを取得し、表示するコードを追加
        // 例えば、WordPressのデータベースから受注データを取得し、表形式で表示する
    }
}

// インスタンス化
new KTP_Tab_Order();
