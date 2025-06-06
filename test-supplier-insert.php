<?php
/**
 * サプライヤーデータ挿入の直接テスト
 */

// WordPress環境を読み込み
define('WP_USE_THEMES', false);
require_once '/Users/kantanpro/Local Sites/kantanpro-local-site/app/public/wp-load.php';

// デバッグモードを有効にする
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

echo "=== サプライヤーデータ挿入テスト ===\n";

// クラスファイルを読み込み
require_once dirname(__FILE__) . '/includes/class-supplier-data.php';

// テストデータを準備
$test_data = array(
    'company_name' => 'テスト株式会社',
    'user_name' => '田中太郎',
    'representative_name' => '佐藤花子',
    'email' => 'test@example.com',
    'url' => 'https://test.example.com',
    'phone' => '03-1234-5678',
    'postal_code' => '100-0001',
    'prefecture' => '東京都',
    'city' => '千代田区',
    'address' => '丸の内1-1-1',
    'building' => 'テストビル101',
    'closing_day' => '月末',
    'payment_month' => '翌月',
    'payment_day' => '末日',
    'payment_method' => '銀行振込',
    'tax_category' => '外税',
    'category' => '一般',
    'memo' => 'テスト用のサプライヤーデータです',
    'query_post' => 'insert',
    'send_post' => 'submit'
);

echo "テストデータ準備完了\n";

// サプライヤーデータクラスをインスタンス化
$supplier_data = new KTPWP_Supplier_Data();

echo "クラスインスタンス化完了\n";

// テーブルが存在することを確認
global $wpdb;
$table_name = $wpdb->prefix . 'ktp_supplier';
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

if ($table_exists) {
    echo "テーブル存在確認: OK ($table_name)\n";
} else {
    echo "エラー: テーブルが存在しません ($table_name)\n";
    exit(1);
}

// 挿入前のレコード数を確認
$count_before = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "挿入前のレコード数: $count_before\n";

// データ挿入を実行
echo "データ挿入を実行中...\n";

try {
    // POSTデータをシミュレート
    $_POST = $test_data;
    
    // update_table メソッドを呼び出し
    $supplier_data->update_table('supplier', $test_data);
    
    echo "update_table メソッド実行完了\n";
    
} catch (Exception $e) {
    echo "エラー発生: " . $e->getMessage() . "\n";
}

// 挿入後のレコード数を確認
$count_after = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "挿入後のレコード数: $count_after\n";

// 最新のレコードを確認
if ($count_after > $count_before) {
    $latest_record = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1", ARRAY_A);
    echo "挿入されたレコード:\n";
    echo "ID: " . $latest_record['id'] . "\n";
    echo "会社名: " . $latest_record['company_name'] . "\n";
    echo "担当者名: " . $latest_record['name'] . "\n";
    echo "代表者名: " . $latest_record['representative_name'] . "\n";
    echo "メール: " . $latest_record['email'] . "\n";
    echo "\n成功: データが正常に挿入されました！\n";
} else {
    echo "\n失敗: データが挿入されませんでした。\n";
    
    // エラーログを確認
    if ($wpdb->last_error) {
        echo "データベースエラー: " . $wpdb->last_error . "\n";
    }
}

echo "\n=== テスト完了 ===\n";
