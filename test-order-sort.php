<?php
/**
 * テスト用：注文履歴のソート確認
 * 
 * このファイルを実行すると、ktp_orderテーブルの全データを日付順に取得して表示します。
 */

// WordPress ファイルへのパス
require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

global $wpdb;
$order_table = $wpdb->prefix . 'ktp_order';

// timeカラムの型を確認
$table_structure = $wpdb->get_results("DESCRIBE {$order_table}");
echo "<h2>テーブル構造</h2>";
echo "<pre>";
print_r($table_structure);
echo "</pre>";

// 全データを時間の降順でソート（最新が最初）
$query = "SELECT id, time, customer_name, project_name FROM {$order_table} ORDER BY time DESC LIMIT 100";
$orders = $wpdb->get_results($query);

echo "<h2>注文リスト（最新順）</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Time DB値</th><th>フォーマット済み日時</th><th>顧客名</th><th>プロジェクト名</th></tr>";

foreach ($orders as $order) {
    $raw_time = $order->time;
    $formatted_time = '';
    
    if (!empty($raw_time)) {
        if (is_numeric($raw_time) && strlen($raw_time) >= 10) {
            $timestamp = (int)$raw_time;
            $dt = new DateTime('@' . $timestamp);
            $dt->setTimezone(new DateTimeZone('Asia/Tokyo'));
        } else {
            $dt = date_create($raw_time, new DateTimeZone('Asia/Tokyo'));
        }
        if ($dt) {
            $formatted_time = $dt->format('Y/m/d H:i:s');
        }
    }
    
    echo "<tr>";
    echo "<td>" . esc_html($order->id) . "</td>";
    echo "<td>" . esc_html($raw_time) . "</td>";
    echo "<td>" . esc_html($formatted_time) . "</td>";
    echo "<td>" . esc_html($order->customer_name) . "</td>";
    echo "<td>" . esc_html($order->project_name) . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
