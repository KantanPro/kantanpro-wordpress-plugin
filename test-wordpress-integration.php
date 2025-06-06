<?php
/**
 * WordPress経由でのサプライヤーデータ処理テスト
 */

// Basic WordPress bootstrap
define('ABSPATH', '/Users/kantanpro/Local Sites/kantanpro-local-site/app/public/');
require_once ABSPATH . 'wp-config.php';
require_once ABSPATH . 'wp-includes/wp-db.php';
require_once ABSPATH . 'wp-includes/pluggable.php';
require_once ABSPATH . 'wp-includes/functions.php';

// WordPress variables
global $wpdb;
$wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);

echo "WordPress データベース接続テスト\n";

// Test basic database connection
$result = $wpdb->get_results("SHOW TABLES LIKE 'wp_ktp_supplier'");
if (empty($result)) {
    echo "エラー: wp_ktp_supplierテーブルが見つかりません\n";
    exit(1);
}

echo "wp_ktp_supplierテーブル: 存在確認済み\n";

// Load supplier data class
require_once '/Users/kantanpro/Local Sites/kantanpro-local-site/app/public/wp-content/plugins/KTPWP/includes/class-supplier-data.php';

// Mock $_POST data
$mock_post_data = array(
    'company_name' => 'WordPressテスト会社',
    'user_name' => 'WordPressテスト担当者',
    'representative_name' => 'WordPressテスト代表者',
    'email' => 'wordpress-test@example.com',
    'url' => 'https://wordpress-test.example.com',
    'phone' => '03-9999-8888',
    'postal_code' => '100-0002',
    'prefecture' => '東京都',
    'city' => '千代田区',
    'address' => '丸の内2-2-2',
    'building' => 'WordPressテストビル',
    'closing_day' => '20日',
    'payment_month' => '翌々月',
    'payment_day' => '10日',
    'payment_method' => '現金',
    'tax_category' => '内税',
    'category' => 'WordPress',
    'memo' => 'WordPress経由のテストデータ',
    'query_post' => 'insert',
    'send_post' => 'submit',
    'ktp_supplier_nonce' => 'test_nonce_bypass'
);

echo "テストデータ準備完了\n";

// Create supplier data instance
$supplier_data = new KTPWP_Supplier_Data();

echo "KTPWP_Supplier_Dataクラス読み込み完了\n";

// Get record count before
$count_before = $wpdb->get_var("SELECT COUNT(*) FROM wp_ktp_supplier");
echo "挿入前のレコード数: $count_before\n";

// Test data insertion
echo "データ挿入テスト開始...\n";

// Bypass nonce check for testing
function bypass_nonce_check($check, $action, $nonce) {
    if ($action === 'ktp_supplier_action' && $nonce === 'test_nonce_bypass') {
        return true;
    }
    return $check;
}
add_filter('wp_verify_nonce', 'bypass_nonce_check', 10, 3);

try {
    $supplier_data->update_table('supplier', $mock_post_data);
    echo "update_table メソッド実行完了\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}

// Get record count after
$count_after = $wpdb->get_var("SELECT COUNT(*) FROM wp_ktp_supplier");
echo "挿入後のレコード数: $count_after\n";

if ($count_after > $count_before) {
    echo "成功: WordPress経由でのデータ挿入が成功しました！\n";
    
    // Show latest record
    $latest = $wpdb->get_row("SELECT * FROM wp_ktp_supplier ORDER BY id DESC LIMIT 1", ARRAY_A);
    echo "最新レコード:\n";
    echo "  ID: " . $latest['id'] . "\n";
    echo "  会社名: " . $latest['company_name'] . "\n";
    echo "  担当者名: " . $latest['name'] . "\n";
    echo "  代表者名: " . $latest['representative_name'] . "\n";
    echo "  メール: " . $latest['email'] . "\n";
} else {
    echo "失敗: データが挿入されませんでした\n";
    if ($wpdb->last_error) {
        echo "SQLエラー: " . $wpdb->last_error . "\n";
    }
}

echo "テスト完了\n";
