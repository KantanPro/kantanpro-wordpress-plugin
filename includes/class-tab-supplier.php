<?php

class KTP_Tab_Supplier {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_supplier_menu'));
    }

    public function display() {
        // ここに表示する内容を実装します。
        echo '<h3>協力会社管理</h3>';
    }

    public function add_supplier_menu() {
        add_submenu_page(
            'ktp-main-menu', // 親メニューのスラッグ
            '協力会社', // ページタイトル
            '協力会社', // メニュータイトル
            'manage_options', // 権限
            'ktp-tab-supplier', // メニュースラッグ
            array($this, 'supplier_page_content') // 表示内容を生成するコールバック関数
        );
    }

    public function supplier_page_content() {
        echo '<h1>協力会社管理</h1>';
        echo '<p>ここに協力会社の管理と表示に関するコンテンツを表示します。</p>';
        // ここに協力会社データを取得し、表示するコードを追加
        // 例えば、WordPressのデータベースから協力会社データを取得し、表形式で表示する
    }
}

// インスタンス化
new KTP_Tab_Supplier();
