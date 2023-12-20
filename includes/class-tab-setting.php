<?php

class KTP_Tab_Setting {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_setting_menu'));
    }

    public function display() {
        // ここに表示する内容を実装します。
        echo '<h3>設定</h3>';
    }

    public function add_setting_menu() {
        add_submenu_page(
            'ktp-main-menu', // 親メニューのスラッグ
            '設定', // ページタイトル
            '設定', // メニュータイトル
            'manage_options', // 権限
            'ktp-tab-setting', // メニュースラッグ
            array($this, 'setting_page_content') // 表示内容を生成するコールバック関数
        );
    }

    public function setting_page_content() {
        echo '<h1>プラグイン設定</h1>';
        echo '<p>ここにプラグインの設定オプションを表示します。</p>';
        // ここに設定ページのフォームやオプションを管理するコードを追加
        // 例えば、フォームを使って設定を保存し、WordPressのオプションテーブルに保存する
    }
}

// インスタンス化
new KTP_Tab_Setting();
