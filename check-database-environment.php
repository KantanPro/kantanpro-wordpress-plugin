<?php
/**
 * データベース環境とスタッフチャット関連テーブルの確認
 */

// WordPressの環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

echo "=== Database and Staff Chat Environment Check ===\n\n";

global $wpdb;

// データベース接続状況確認
if ($wpdb->last_error) {
    echo "DATABASE ERROR: " . $wpdb->last_error . "\n";
} else {
    echo "Database connection: OK\n";
}

// テーブルプレフィックス確認
echo "Table prefix: " . $wpdb->prefix . "\n";

// 現在のデータベース名
echo "Database name: " . DB_NAME . "\n";

// スタッフチャット関連テーブルを確認
$staff_chat_table = $wpdb->prefix . 'ktp_order_staff_chat';

echo "\n=== Staff Chat Tables Check ===\n";

// テーブル存在確認
$tables_exist = [];
$tables_to_check = [
    'staff_chat' => $staff_chat_table
];

foreach ($tables_to_check as $name => $table_name) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    $tables_exist[$name] = $table_exists;
    echo "$name table ($table_name): " . ($table_exists ? "EXISTS" : "NOT EXISTS") . "\n";

    if ($table_exists) {
        // テーブル構造を表示
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        echo "  Columns:\n";
        foreach ($columns as $column) {
            echo "    - {$column->Field} ({$column->Type})\n";
        }

        // レコード数を表示
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo "  Records: $count\n";
    }
}

// KTPWPクラスの状況確認
echo "\n=== KTPWP Classes Status ===\n";

$classes_to_check = [
    'KTPWP_Main',
    'KTPWP_Ajax',
    'KTPWP_Staff_Chat',
    'KTPWP_Assets'
];

foreach ($classes_to_check as $class_name) {
    echo "$class_name: " . (class_exists($class_name) ? "EXISTS" : "NOT EXISTS") . "\n";
}

// スタッフチャットクラスのインスタンス化テスト
echo "\n=== Staff Chat Class Test ===\n";

try {
    if (class_exists('KTPWP_Staff_Chat')) {
        $staff_chat = KTPWP_Staff_Chat::get_instance();
        echo "KTPWP_Staff_Chat instantiation: SUCCESS\n";

        // メソッド存在確認
        $methods_to_check = ['add_message', 'get_messages', 'setup_table'];
        foreach ($methods_to_check as $method) {
            echo "  Method $method: " . (method_exists($staff_chat, $method) ? "EXISTS" : "NOT EXISTS") . "\n";
        }
    } else {
        echo "KTPWP_Staff_Chat class not available\n";
    }
} catch (Exception $e) {
    echo "Error instantiating KTPWP_Staff_Chat: " . $e->getMessage() . "\n";
}

// WordPress AJAX機能の確認
echo "\n=== WordPress AJAX Status ===\n";

echo "DOING_AJAX defined: " . (defined('DOING_AJAX') ? 'YES' : 'NO') . "\n";
echo "wp_doing_ajax() function: " . (function_exists('wp_doing_ajax') ? 'EXISTS' : 'NOT EXISTS') . "\n";

// 現在のユーザー情報
echo "\n=== Current User Info ===\n";
$current_user = wp_get_current_user();
if ($current_user->ID) {
    echo "User ID: " . $current_user->ID . "\n";
    echo "Username: " . $current_user->user_login . "\n";
    echo "User roles: " . implode(', ', $current_user->roles) . "\n";
    echo "Can read: " . (current_user_can('read') ? 'YES' : 'NO') . "\n";
    echo "Is admin: " . (current_user_can('manage_options') ? 'YES' : 'NO') . "\n";
} else {
    echo "No user logged in\n";
}

echo "\n=== Check Complete ===\n";
