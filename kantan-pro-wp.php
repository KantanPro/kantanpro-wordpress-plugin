<?php
/*
Plugin Name: kantan pro wp
Description: 固定ページにショートコード[kantanAllTab]を記載することで表示されます。
// ここに他のショートコードがあれば追記
// 例: [kantanOrderTab] ・・・受注書タブのみ表示
//     [kantanClientTab] ・・・クライアントタブのみ表示

Version: 1.0
*/

if (!defined('ABSPATH')) exit;

// クラスファイルをインクルード
include_once 'includes/class-tab-list.php';
include_once 'includes/class-tab-order.php';
include_once 'includes/class-tab-client.php';
include_once 'includes/class-tab-service.php';
include_once 'includes/class-tab-supplier.php';
include_once 'includes/class-tab-report.php';
include_once 'includes/class-tab-setting.php';
include_once 'includes/class-login-error.php';
include_once 'includes/class-view-tab.php';

// ショートコード登録
add_action('init', function() {
    add_shortcode('kantanAllTab', 'kantanAllTab_handler');
});

// スタイル・スクリプト
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('ktpwp-css', plugins_url('/css/styles.css', __FILE__));
    wp_enqueue_script('jquery');
});

// ショートコードハンドラ
function kantanAllTab_handler() {
    if (!is_user_logged_in()) {
        $login_error = new Kantan_Login_Error();
        return $login_error->Error_View();
    }
    $current_user = wp_get_current_user();
    $logout_link = wp_logout_url();
    $login_user = esc_html($current_user->nickname ?: $current_user->user_login);
    $header = <<<HTML
    <div class="ktp_header">
        ログイン中：$login_user さん&emsp;<a href="$logout_link">ログアウト</a>&emsp;<a href="/">更新</a>&emsp;
    </div>
    HTML;

    // タブUI
    $tabs = [
        'list' => '仕事リスト',
        'order' => '受注書',
        'client' => 'クライアント',
        'service' => '商品・サービス',
        'supplier' => '協力会社',
        'report' => 'レポート',
        'setting' => '設定'
    ];
    $tab_nav = '<ul class="ktpwp-tab-nav">';
    foreach ($tabs as $key => $label) {
        $tab_nav .= "<li data-tab='$key'>$label</li>";
    }
    $tab_nav .= '</ul>';

    // 各タブの内容（各クラスのView_Tableを呼ぶだけ）
    $tab_contents = '<div class="ktpwp-tab-contents">';
    $tab_contents .= '<div class="ktpwp-tab-content" data-tab="list">' . (new Kantan_List_Class())->View_Table('list') . '</div>';
    $tab_contents .= '<div class="ktpwp-tab-content" data-tab="order">' . (new Kantan_Order_Class())->View_Table('order') . '</div>';
    $tab_contents .= '<div class="ktpwp-tab-content" data-tab="client">' . (new Kantan_Client_Class())->View_Table('client') . '</div>';
    $tab_contents .= '<div class="ktpwp-tab-content" data-tab="service">' . (new Kantan_Service_Class())->View_Table('service') . '</div>';
    $tab_contents .= '<div class="ktpwp-tab-content" data-tab="supplier">' . (new Kantan_Supplier_Class())->View_Table('supplier') . '</div>';
    $tab_contents .= '<div class="ktpwp-tab-content" data-tab="report">' . (new Kantan_Report_Class())->View_Table('report') . '</div>';
    $tab_contents .= '<div class="ktpwp-tab-content" data-tab="setting">' . (new Kantan_Setting_Class())->View_Table('setting') . '</div>';
    $tab_contents .= '</div>';

    // タブ切り替えJS
    $js = <<<JS
    <script>
    jQuery(function($){
        $('.ktpwp-tab-nav li').on('click', function(){
            var tab = $(this).data('tab');
            $('.ktpwp-tab-nav li').removeClass('active');
            $(this).addClass('active');
            $('.ktpwp-tab-content').hide();
            $('.ktpwp-tab-content[data-tab="'+tab+'"]').show();
        });
        $('.ktpwp-tab-nav li').first().click();
    });
    </script>
    JS;

    return $header . $tab_nav . $tab_contents . $js;
}
