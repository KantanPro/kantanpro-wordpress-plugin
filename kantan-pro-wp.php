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

// インクルードステートメントを確認
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

// データベーステーブルの作成と更新
// (ここに以前のデータベーステーブル作成コードを続ける)

// ショートコードの追加
function kantan_all_tab_shortcode() {
    ob_start();
    echo '<div id="ktp-tabs">';

    // // KTP_Tab_List クラスのインスタンスを作成し、display メソッドを呼び出す
    // $ktp_tab_list = new KTP_Tab_List();
    // echo '<div id="tab-list">';
    // $ktp_tab_list->display();
    // echo '</div>';

    // // KTP_Tab_Order クラスのインスタンスを作成し、display メソッドを呼び出す
    // $ktp_tab_order = new KTP_Tab_Order();
    // echo '<div id="tab-order">';
    // $ktp_tab_order->display();
    // echo '</div>';

    // // KTP_Tab_Client クラスのインスタンスを作成し、display メソッドを呼び出す
    // $ktp_tab_client = new KTP_Tab_Client();
    // echo '<div id="tab-client">';
    // $ktp_tab_client->display();
    // echo '</div>';

    // // KTP_Tab_Service クラスのインスタンスを作成し、display メソッドを呼び出す
    // $ktp_tab_service = new KTP_Tab_Service();
    // echo '<div id="tab-service">';
    // $ktp_tab_service->display();
    // echo '</div>';

    // // KTP_Tab_Supplier クラスのインスタンスを作成し、display メソッドを呼び出す
    // $ktp_tab_supplier = new KTP_Tab_Supplier();
    // echo '<div id="tab-supplier">';
    // $ktp_tab_supplier->display();
    // echo '</div>';

    // // KTP_Tab_Report クラスのインスタンスを作成し、display メソッドを呼び出す
    // $ktp_tab_report = new KTP_Tab_Report();
    // echo '<div id="tab-report">';
    // $ktp_tab_report->display();
    // echo '</div>';

    // // KTP_Tab_Setting クラスのインスタンスを作成し、display メソッドを呼び出す
    // $ktp_tab_setting = new KTP_Tab_Setting();
    // echo '<div id="tab-setting">';
    // $ktp_tab_setting->display();
    // echo '</div>';

    // タブレイアウトの実装
    ob_start();
    echo '<div id="ktp-tabs">';

    // タブ
    echo '<div class="tab active" data-target="#tab-list">仕事リスト</div>';
    echo '<div class="tab" data-target="#tab-order">受注書</div>';
    // ...他のタブ

    // コンテンツ
    echo '<div id="tab-list" class="content">'; // 仕事リスト
    // ...仕事リストの内容
    echo '</div>';

    echo '<div id="tab-order" class="content">'; // 受注書
    // ...受注書の内容
    echo '</div>';

    // ...他のタブの内容

    echo '</div>';
    return ob_get_clean();
    
}
add_shortcode('kantanAllTab', 'kantan_all_tab_shortcode');

// 以下に、各タブのコンテンツを生成するためのクラス定義を続ける
// 例えば、TabListクラスでは、display_listメソッドを定義して、仕事リストの内容を出力する
