<?php

class KTP_View_Tab {
    private $tabs;

    public function __construct() {
        $this->tabs = array(
            'list' => '仕事リスト',
            'order' => '受注書',
            'client' => '顧客',
            'service' => '商品・サービス',
            'supplier' => '協力会社',
            'report' => 'レポート',
            'setting' => '設定'
        );
    }

    public function init() {
        add_action('admin_menu', array($this, 'add_tabs_menu'));
    }

    public function add_tabs_menu() {
        foreach ($this->tabs as $slug => $title) {
            add_submenu_page(
                'ktp-main-menu', // 親メニューのスラッグ
                $title, // ページタイトル
                $title, // メニュータイトル
                'manage_options', // 権限
                'ktp-tab-' . $slug, // メニュースラッグ
                array($this, 'render_tab_page') // 表示内容を生成するコールバック関数
            );
        }
    }

    public function render_tab_page() {
        $current_tab = isset($_GET['page']) ? $_GET['page'] : 'list';
        echo '<h1>' . $this->tabs[str_replace('ktp-tab-', '', $current_tab)] . '</h1>';
        echo '<p>ここに ' . $current_tab . ' タブの内容を表示します。</p>';
        // ここに各タブに応じた内容を表示するコードを追加
    }
}

// インスタンス化
$ktp_view_tab = new KTP_View_Tab();
$ktp_view_tab->init();
