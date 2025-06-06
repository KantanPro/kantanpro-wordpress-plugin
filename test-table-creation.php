<?php
// WordPress環境を読み込み
require_once '../../../wp-config.php';
require_once '../../../wp-load.php';

// サプライヤーデータクラスを読み込み
require_once 'includes/class-supplier-data.php';

// テーブルを作成
$supplier_data = new KTPWP_Supplier_Data();
$supplier_data->create_table('supplier');

echo "テーブル作成処理が完了しました。\n";

// テーブル構造を確認
global $wpdb;
$table_name = $wpdb->prefix . 'ktp_supplier';
$results = $wpdb->get_results("DESCRIBE $table_name");

echo "テーブル構造:\n";
foreach ($results as $row) {
    echo "- " . $row->Field . " (" . $row->Type . ")\n";
}

// バージョンを確認
$version = get_option('ktp_supplier_table_version');
echo "テーブルバージョン: " . $version . "\n";
