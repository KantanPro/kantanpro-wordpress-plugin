<?php
/**
 * Plugin Name: kantan pro wp
 * Description: カンタンProWP
 * Version: 1.0
 */

// バージョン管理
if ( ! defined( 'WPINC' ) ) {
    die;
}

// WordPressが直接アクセスされるのを防ぐ
defined( 'ABSPATH' ) || exit;

// 定数の定義
define( 'KTP_VERSION', '1.0' );
define( 'KTP_PATH', plugin_dir_path( __FILE__ ) );
define( 'KTP_URL', plugins_url( '/', __FILE__ ) );

// インクルードステートメント
include_once KTP_PATH . 'includes/class-tab-list.php';
include_once KTP_PATH . 'includes/class-tab-order.php';
include_once KTP_PATH . 'includes/class-tab-client.php';
include_once KTP_PATH . 'includes/class-tab-service.php';
include_once KTP_PATH . 'includes/class-tab-supplier.php';
include_once KTP_PATH . 'includes/class-tab-report.php';
include_once KTP_PATH . 'includes/class-tab-setting.php';
include_once KTP_PATH . 'includes/class-login-error.php';
include_once KTP_PATH . 'includes/class-view-tab.php';

// スタイルとスクリプトの登録
function ktp_enqueue_scripts() {
    wp_register_style('ktp-style', KTP_URL . 'css/styles.css', [], KTP_VERSION, 'all');
    wp_enqueue_style('ktp-style');

    wp_register_script('ktp-script', KTP_URL . 'js/ktpjs.js', [], KTP_VERSION, true);
    wp_enqueue_script('ktp-script');
}
add_action('wp_enqueue_scripts', 'ktp_enqueue_scripts');

// ショートコードの追加
function kantan_all_tab_shortcode() {
    ob_start();
    ?>
    <div id="ktp-tabs">
        <div class="tab active" id="tab-list">仕事リスト</div>
        <div class="tab" id="tab-order">受注書</div>
        <div class="tab" id="tab-service">商品・サービス</div>
        <div class="tab" id="tab-client">顧客</div>
        <div class="tab" id="tab-supplier">協力会社</div>
        <div class="tab" id="tab-report">レポート</div>
        <div class="tab" id="tab-setting">設定</div>
    </div>

    <div id="tab-content">
        <div class="content active" id="content-list">仕事リストのコンテンツ...</div>
        <div class="content" id="content-order">受注書のコンテンツ...</div>
        <div class="content" id="content-service">商品・サービスのコンテンツ...</div>
        <!-- 顧客のコンテンツ -->
        <div class="content" id="content-client">
            <?php
            // 顧客タブのコンテンツを表示
            $client_tab = new KTP_Tab_Client();
            $client_tab->client_page_content();
            ?>
        </div>
        <div class="content" id="content-supplier">協力会社のコンテンツ...</div>
        <div class="content" id="content-report">レポートのコンテンツ...</div>
        <div class="content" id="content-setting">設定のコンテンツ...</div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('kantanAllTab', 'kantan_all_tab_shortcode');
