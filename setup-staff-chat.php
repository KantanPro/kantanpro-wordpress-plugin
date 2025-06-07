<?php
/**
 * スタッフチャットテーブルを作成し、基本機能をテストするスクリプト
 */

// WordPressの環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

// ログイン要件を一時的にコメントアウト
// if (!is_user_logged_in()) {
//     wp_die('ログインが必要です');
// }

echo "<h1>KTPWP Staff Chat Table Setup and Test</h1>";

// スタッフチャットクラスの読み込み
if (!class_exists('KTPWP_Staff_Chat')) {
    require_once KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-staff-chat.php';
    echo "<p>✓ KTPWP_Staff_Chat class loaded</p>";
} else {
    echo "<p>✓ KTPWP_Staff_Chat class already loaded</p>";
}

// インスタンス取得
$staff_chat = KTPWP_Staff_Chat::get_instance();
echo "<p>✓ KTPWP_Staff_Chat instance created</p>";

// テーブル作成
echo "<h2>Creating Staff Chat Table</h2>";
$table_created = $staff_chat->create_table();
if ($table_created) {
    echo "<p>✓ Staff chat table created or already exists</p>";
} else {
    echo "<p>❌ Failed to create staff chat table</p>";
}

// テーブル存在確認
global $wpdb;
$table_name = $wpdb->prefix . 'ktp_order_staff_chat';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

echo "<h2>Table Status Check</h2>";
if ($table_exists) {
    echo "<p>✓ Table '$table_name' exists</p>";

    // テーブル構造を表示
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column->Field}</td>";
        echo "<td>{$column->Type}</td>";
        echo "<td>{$column->Null}</td>";
        echo "<td>{$column->Key}</td>";
        echo "<td>{$column->Default}</td>";
        echo "<td>{$column->Extra}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // レコード数確認
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p>Current records: $count</p>";

} else {
    echo "<p>❌ Table '$table_name' does not exist</p>";
}

// 注文テーブルの確認
$order_table = $wpdb->prefix . 'ktp_order';
$order_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$order_table'");

echo "<h2>Order Table Check</h2>";
if ($order_table_exists) {
    echo "<p>✓ Order table '$order_table' exists</p>";
    $order_count = $wpdb->get_var("SELECT COUNT(*) FROM $order_table");
    echo "<p>Order records: $order_count</p>";

    // 最初の注文IDを取得
    $first_order = $wpdb->get_var("SELECT id FROM $order_table ORDER BY id LIMIT 1");
    if ($first_order) {
        echo "<p>First order ID: $first_order</p>";

        // テストメッセージを送信
        echo "<h2>Test Message Sending</h2>";
        $test_message = "Test message from setup script - " . date('Y-m-d H:i:s');
        $result = $staff_chat->add_message($first_order, $test_message);

        if ($result) {
            echo "<p>✓ Test message sent successfully</p>";

            // メッセージを取得
            $messages = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE order_id = %d ORDER BY created_at DESC LIMIT 5",
                $first_order
            ));

            if ($messages) {
                echo "<h3>Recent Messages for Order $first_order:</h3>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>User</th><th>Message</th><th>Created At</th></tr>";
                foreach ($messages as $msg) {
                    echo "<tr>";
                    echo "<td>{$msg->id}</td>";
                    echo "<td>{$msg->user_display_name} ({$msg->user_id})</td>";
                    echo "<td>" . esc_html($msg->message) . "</td>";
                    echo "<td>{$msg->created_at}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p>❌ Failed to send test message</p>";
        }
    } else {
        echo "<p>⚠️ No orders found in order table</p>";
    }
} else {
    echo "<p>❌ Order table '$order_table' does not exist</p>";

    // 注文テーブルを作成する簡単なテスト
    echo "<h3>Creating Test Order Table</h3>";
    $order_sql = "CREATE TABLE IF NOT EXISTS `{$order_table}` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL DEFAULT '',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) {$wpdb->get_charset_collate()};";

    $order_result = $wpdb->query($order_sql);
    if ($order_result !== false) {
        echo "<p>✓ Test order table created</p>";

        // テスト注文を挿入
        $insert_result = $wpdb->insert(
            $order_table,
            array(
                'title' => 'Test Order for Staff Chat',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s')
        );

        if ($insert_result) {
            $test_order_id = $wpdb->insert_id;
            echo "<p>✓ Test order created with ID: $test_order_id</p>";

            // テストメッセージを送信
            echo "<h3>Testing Message with New Order</h3>";
            $test_message = "Test message for new order - " . date('Y-m-d H:i:s');
            $result = $staff_chat->add_message($test_order_id, $test_message);

            if ($result) {
                echo "<p>✓ Test message sent successfully to new order</p>";
            } else {
                echo "<p>❌ Failed to send test message to new order</p>";
            }
        }
    } else {
        echo "<p>❌ Failed to create test order table</p>";
    }
}

echo "<h2>Current User Info</h2>";
$current_user = wp_get_current_user();
echo "<p>User ID: {$current_user->ID}</p>";
echo "<p>Username: {$current_user->user_login}</p>";
echo "<p>Display Name: {$current_user->display_name}</p>";
echo "<p>Can edit posts: " . (current_user_can('edit_posts') ? 'YES' : 'NO') . "</p>";

echo "<h2>Setup Complete</h2>";
echo "<p>You can now test the AJAX functionality using the test page.</p>";
echo '<p><a href="ajax-test.php">Go to AJAX Test Page</a></p>';
?>
