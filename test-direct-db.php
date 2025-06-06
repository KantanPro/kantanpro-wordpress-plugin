<?php
/**
 * シンプルなWordPress接続テスト
 */

echo "WordPress接続テスト開始\n";

// wp-config.phpを直接読み込み
require_once('/Users/kantanpro/Local Sites/kantanpro-local-site/app/public/wp-config.php');

echo "wp-config.php読み込み完了\n";

// MySQLi接続をテスト
$connection = new mysqli('localhost:/Users/kantanpro/Library/Application Support/Local/run/_yMJnUQWb/mysql/mysqld.sock', DB_USER, DB_PASSWORD, DB_NAME);

if ($connection->connect_error) {
    echo "接続失敗: " . $connection->connect_error . "\n";
    exit(1);
}

echo "データベース接続: OK\n";

// テーブル確認
$result = $connection->query("SHOW TABLES LIKE 'wp_ktp_supplier'");
if ($result->num_rows > 0) {
    echo "wp_ktp_supplierテーブル: 存在\n";
} else {
    echo "wp_ktp_supplierテーブル: 存在しない\n";
}

// レコード数確認
$result = $connection->query("SELECT COUNT(*) as count FROM wp_ktp_supplier");
$row = $result->fetch_assoc();
echo "現在のレコード数: " . $row['count'] . "\n";

// テストデータを手動挿入
$test_insert = "INSERT INTO wp_ktp_supplier (
    time, name, url, company_name, email, representative_name, phone, postal_code, 
    prefecture, city, address, building, closing_day, payment_month, payment_day, 
    payment_method, tax_category, memo, search_field, frequency, category
) VALUES (
    " . time() . ",
    'テスト担当者',
    'https://test.example.com',
    'テスト会社',
    'test@example.com',
    'テスト代表者',
    '03-1234-5678',
    '100-0001',
    '東京都',
    '千代田区',
    '丸の内1-1-1',
    'テストビル',
    '月末',
    '翌月',
    '末日',
    '銀行振込',
    '外税',
    'テストメモ',
    'テスト検索フィールド',
    0,
    '一般'
)";

echo "テストデータ挿入中...\n";

if ($connection->query($test_insert)) {
    echo "挿入成功！新しいID: " . $connection->insert_id . "\n";
    
    // 挿入後のレコード数を確認
    $result = $connection->query("SELECT COUNT(*) as count FROM wp_ktp_supplier");
    $row = $result->fetch_assoc();
    echo "挿入後のレコード数: " . $row['count'] . "\n";
    
    // 最新レコードを表示
    $result = $connection->query("SELECT id, company_name, name, representative_name, email FROM wp_ktp_supplier ORDER BY id DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        echo "最新レコード:\n";
        echo "  ID: " . $row['id'] . "\n";
        echo "  会社名: " . $row['company_name'] . "\n";
        echo "  担当者名: " . $row['name'] . "\n";
        echo "  代表者名: " . $row['representative_name'] . "\n";
        echo "  メール: " . $row['email'] . "\n";
    }
} else {
    echo "挿入失敗: " . $connection->error . "\n";
}

$connection->close();
echo "テスト完了\n";
