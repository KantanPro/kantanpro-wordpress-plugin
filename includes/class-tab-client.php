<?php

class KTP_Tab_Client {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_client_menu'));
    }

    public function display() {
        // ここに表示する内容を実装します。
        echo '<h3>顧客管理</h3>';
    }

    public function add_client_menu() {
        add_submenu_page(
            'ktp-main-menu', // 親メニューのスラッグ
            '顧客', // ページタイトル
            '顧客', // メニュータイトル
            'manage_options', // 権限
            'ktp-tab-client', // メニュースラッグ
            array($this, 'client_page_content') // 表示内容を生成するコールバック関数
        );
    }

    public function client_page_content() {
        echo '<h1>顧客管理</h1>';
        echo '<p>ここに顧客の管理と表示に関するコンテンツを表示します。</p>';
        // ここに顧客データを取得し、表示するコードを追加
        // 例えば、WordPressのデータベースから顧客データを取得し、表形式で表示する
    }
}

// インスタンス化
new KTP_Tab_Client();
