<?php
/**
 * テストデータ作成スクリプト
 */

// WordPressの環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

// ログイン要件を一時的にコメントアウト
// if (!is_user_logged_in()) {
//     wp_die('ログインが必要です');
// }

echo "=== Test Data Setup ===\n";

global $wpdb;

// 現在のユーザー情報
$current_user = wp_get_current_user();
echo "Current user: " . $current_user->user_login . " (ID: " . $current_user->ID . ")\n";

// オーダーテーブルの存在確認と作成
$order_table = $wpdb->prefix . 'ktp_order';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$order_table'");

echo "\nOrder table ($order_table): " . ($table_exists ? "EXISTS" : "NOT EXISTS") . "\n";

if (!$table_exists) {
    echo "Creating order table...\n";

    $sql = "CREATE TABLE `{$order_table}` (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        order_name VARCHAR(255) NOT NULL DEFAULT '',
        order_status VARCHAR(50) NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) " . $wpdb->get_charset_collate() . ";";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    if ($wpdb->last_error) {
        echo "Error creating order table: " . $wpdb->last_error . "\n";
    } else {
        echo "Order table created successfully\n";
    }
}

// テストオーダーの存在確認と作成
$test_order_id = 1;
$existing_order = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM `{$order_table}` WHERE id = %d",
    $test_order_id
));

if (!$existing_order) {
    echo "\nCreating test order (ID: $test_order_id)...\n";

    $inserted = $wpdb->insert(
        $order_table,
        array(
            'id' => $test_order_id,
            'order_name' => 'テストオーダー #1',
            'order_status' => 'active'
        ),
        array('%d', '%s', '%s')
    );

    if ($inserted) {
        echo "Test order created successfully\n";
    } else {
        echo "Error creating test order: " . $wpdb->last_error . "\n";
    }
} else {
    echo "\nTest order (ID: $test_order_id) already exists: " . $existing_order->order_name . "\n";
}

// スタッフチャットテーブルの存在確認
$chat_table = $wpdb->prefix . 'ktp_order_staff_chat';
$chat_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$chat_table'");

echo "\nStaff chat table ($chat_table): " . ($chat_table_exists ? "EXISTS" : "NOT EXISTS") . "\n";

if (!$chat_table_exists) {
    echo "Creating staff chat table...\n";

    // KTPWP_Staff_Chatクラスを使用してテーブル作成
    if (class_exists('KTPWP_Staff_Chat')) {
        $staff_chat = KTPWP_Staff_Chat::get_instance();
        if (method_exists($staff_chat, 'create_table')) {
            $result = $staff_chat->create_table();
            echo "Staff chat table creation: " . ($result ? "SUCCESS" : "FAILED") . "\n";
        } else {
            echo "create_table method not found in KTPWP_Staff_Chat\n";
        }
    } else {
        echo "KTPWP_Staff_Chat class not found\n";
    }
}

// 既存のスタッフチャットメッセージ確認
if ($chat_table_exists) {
    $message_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$chat_table}` WHERE order_id = %d",
        $test_order_id
    ));

    echo "\nExisting chat messages for order $test_order_id: $message_count\n";
}

echo "\n=== Test Data Setup Complete ===\n";
echo "\nNow you can test AJAX functionality with order ID: $test_order_id\n";
