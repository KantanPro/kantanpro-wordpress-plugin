<?php

class KTP_Tab_List {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_list_menu'));
    }

    public function display() {
        // ここに表示する内容を実装します。
        echo '仕事じゃ';
    }

    public function add_list_menu() {
        add_submenu_page(
            'ktp-main-menu', // 親メニューのスラッグ
            '仕事リスト', // ページタイトル
            '仕事リスト', // メニュータイトル
            'manage_options', // 権限
            'ktp-tab-list', // メニュースラッグ
            array($this, 'list_page_content') // 表示内容を生成するコールバック関数
        );
    }

    public function list_page_content() {
        echo '<h1>仕事リスト</h1>';
        echo '<p>ここに仕事リストの管理と表示に関するコンテンツを表示します。</p>';
        // ここに仕事リストデータを取得し、表示するコードを追加
        // 例えば、WordPressのデータベースから仕事リストデータを取得し、表形式で表示する
    }
}

// インスタンス化
new KTP_Tab_List();
