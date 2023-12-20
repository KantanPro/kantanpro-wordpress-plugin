<?php

class KTP_Tab_Service {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_service_menu'));
    }

    public function display() {
        // ここに表示する内容を実装します。
        echo '<h3>商品・サービス管理</h3>';
    }

    public function add_service_menu() {
        add_submenu_page(
            'ktp-main-menu', // 親メニューのスラッグ
            '商品・サービス', // ページタイトル
            '商品・サービス', // メニュータイトル
            'manage_options', // 権限
            'ktp-tab-service', // メニュースラッグ
            array($this, 'service_page_content') // 表示内容を生成するコールバック関数
        );
    }

    public function service_page_content() {
        echo '<h1>商品・サービス管理</h1>';
        echo '<p>ここに商品やサービスの管理と表示に関するコンテンツを表示します。</p>';
        // ここに商品・サービスデータを取得し、表示するコードを追加
        // 例えば、WordPressのデータベースから商品・サービスデータを取得し、表形式で表示する
    }
}

// インスタンス化
new KTP_Tab_Service();
