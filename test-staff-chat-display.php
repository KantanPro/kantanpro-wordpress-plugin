<?php
/**
 * 現在のスタッフチャット表示状態をテストするファイル
 */

// WordPressの基本機能を読み込み
require_once(dirname(__FILE__) . '/../../../wp-load.php');

// 最新の注文IDを取得
global $wpdb;
$order_table = $wpdb->prefix . 'ktp_order';
$latest_order = $wpdb->get_row("SELECT * FROM {$order_table} ORDER BY id DESC LIMIT 1");

if (!$latest_order) {
    echo "注文データがありません";
    exit;
}

$order_id = $latest_order->id;

// スタッフチャットクラスを読み込み
require_once(dirname(__FILE__) . '/class-ktpwp-staff-chat.php');

// スタッフチャットのHTMLを生成
if (class_exists('KTPWP_Staff_Chat')) {
    $staff_chat = KTPWP_Staff_Chat::get_instance();
    
    // chat_open=1パラメータをシミュレート
    $_GET['chat_open'] = '1';
    
    $html = $staff_chat->generate_html($order_id);
    
    echo "<!DOCTYPE html>";
    echo "<html><head>";
    echo "<meta charset='UTF-8'>";
    echo "<title>スタッフチャット表示テスト</title>";
    echo "<style>";
    
    // CSSファイルを読み込み
    $css_file = dirname(__FILE__) . '/../css/styles.css';
    if (file_exists($css_file)) {
        echo file_get_contents($css_file);
    }
    
    echo "</style>";
    echo "</head><body>";
    echo "<h1>スタッフチャット表示テスト（注文ID: {$order_id}）</h1>";
    echo "<div style='max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ccc;'>";
    echo $html;
    echo "</div>";
    
    // JavaScriptも追加
    echo "<script>";
    $js_file = dirname(__FILE__) . '/../js/ktp-js.js';
    if (file_exists($js_file)) {
        echo file_get_contents($js_file);
    }
    echo "</script>";
    
    echo "</body></html>";
} else {
    echo "KTPWP_Staff_Chat クラスが見つかりません";
}
?>
