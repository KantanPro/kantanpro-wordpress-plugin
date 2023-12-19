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
include_once 'includes/class-tab-list.php';
include_once 'includes/class-tab-order.php';
include_once 'includes/class-tab-client.php';
include_once 'includes/class-tab-service.php';
include_once 'includes/class-tab-supplier.php';
include_once 'includes/class-tab-report.php';
include_once 'includes/class-tab-setting.php';
include_once 'includes/class-login-error.php';
include_once 'includes/class-view-tab.php';

// プラグインの有効化時に実行される処理
// スタイルとスクリプトの登録
function ktp_enqueue_scripts() {
    wp_register_style('ktp-style', KTP_URL . 'css/styles.css', [], KTP_VERSION, 'all');
    wp_enqueue_style('ktp-style');

    wp_register_script('ktp-script', KTP_URL . 'js/ktpjs.js', [], KTP_VERSION, true);
    wp_enqueue_script('ktp-script');
}
add_action('wp_enqueue_scripts', 'ktp_enqueue_scripts');

// データベーステーブルの作成と更新
function ktp_create_update_tables() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $charset_collate = $wpdb->get_charset_collate();

    // 仕事リストテーブル
    $table_name = $wpdb->prefix . 'ktp_list';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        description text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql );

    // 受注書テーブル
    $table_name = $wpdb->prefix . 'ktp_order';
    $sql = "CREATE TABLE $table_name (
        order_id mediumint(9) NOT NULL AUTO_INCREMENT,
        client_id mediumint(9) NOT NULL,
        order_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        total_amount float NOT NULL,
        PRIMARY KEY  (order_id)
    ) $charset_collate;";
    dbDelta( $sql );

    // 顧客テーブル
    $table_name = $wpdb->prefix . 'ktp_client';
    $sql = "CREATE TABLE $table_name (
        client_id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        email text NOT NULL,
        phone text,
        PRIMARY KEY  (client_id)
    ) $charset_collate;";
    dbDelta( $sql );

    // 商品・サービステーブル
    $table_name = $wpdb->prefix . 'ktp_service';
    $sql = "CREATE TABLE $table_name (
        service_id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        price float NOT NULL,
        description text,
        PRIMARY KEY  (service_id)
    ) $charset_collate;";
    dbDelta( $sql );

    // 協力会社テーブル
    $table_name = $wpdb->prefix . 'ktp_supplier';
    $sql = "CREATE TABLE $table_name (
        supplier_id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        contact_info text,
        PRIMARY KEY  (supplier_id)
    ) $charset_collate;";
    dbDelta( $sql );

    // レポートテーブル
    $table_name = $wpdb->prefix . 'ktp_report';
    $sql = "CREATE TABLE $table_name (
        report_id mediumint(9) NOT NULL AUTO_INCREMENT,
        content longtext NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (report_id)
    ) $charset_collate;";
    dbDelta( $sql );

    // 設定テーブル（任意の設定項目を追加）
    $table_name = $wpdb->prefix . 'ktp_setting';
    $sql = "CREATE TABLE $table_name (
        setting_id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        value text NOT NULL,
        PRIMARY KEY  (setting_id)
    ) $charset_collate;";
    dbDelta( $sql );
}

register_activation_hook(__FILE__, 'ktp_create_update_tables');

// ショートコードの追加
function kantan_all_tab_shortcode() {
    ob_start();

    // タブインターフェースの出力
    echo '<div id="ktp-tabs">';
    
    // 各タブのコンテンツを読み込む
    // ここでは、各タブを表す関数を呼び出す例を示します。
    // 実際の実装では、これらの関数がタブのコンテンツを生成する必要があります。
    
    echo '<div id="tab-list">';
    ktp_tab_list(); // 仕事リストタブの内容
    echo '</div>';

    echo '<div id="tab-order">';
    ktp_tab_order(); // 受注書タブの内容
    echo '</div>';

    echo '<div id="tab-client">';
    ktp_tab_client(); // 顧客タブの内容
    echo '</div>';

    echo '<div id="tab-service">';
    ktp_tab_service(); // 商品・サービスタブの内容
    echo '</div>';

    echo '<div id="tab-supplier">';
    ktp_tab_supplier(); // 協力会社タブの内容
    echo '</div>';

    echo '<div id="tab-report">';
    ktp_tab_report(); // レポートタブの内容
    echo '</div>';

    echo '<div id="tab-setting">';
    ktp_tab_setting(); // 設定タブの内容
    echo '</div>';

    echo '</div>';

    // 出力のバッファリングを終了し、内容を返す
    return ob_get_clean();
}

function ktp_tab_list() {
    // 仕事リストデータを取得するロジック（省略）
    // HTMLコンテンツを出力
    echo '<h3>仕事リスト</h3>';
    // 仕事リストのデータを表示するコード
}

function ktp_tab_order() {
    // 受注データを取得するロジック（省略）
    echo '<h3>受注書</h3>';
    // 受注書のデータを表示するコード
}

function ktp_tab_client() {
    echo '<h3>顧客</h3>';
    // 顧客データを表示するコード
}

function ktp_tab_service() {
    echo '<h3>商品・サービス</h3>';
    // 商品・サービスデータを表示するコード
}

function ktp_tab_supplier() {
    echo '<h3>協力会社</h3>';
    // 協力会社データを表示するコード
}

function ktp_tab_report() {
    echo '<h3>レポート</h3>';
    // レポートデータを表示するコード
}

function ktp_tab_setting() {
    echo '<h3>設定</h3>';
    // 設定オプションを表示するコード
}

add_shortcode('kantanAllTab', 'kantan_all_tab_shortcode');
