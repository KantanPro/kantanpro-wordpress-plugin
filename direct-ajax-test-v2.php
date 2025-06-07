<?php
/**
 * 直接AJAX URLをテストして、実際のエラーを確認するスクリプト
 */

// WordPressの環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

echo "=== Direct AJAX Handler Test ===\n\n";

// 管理者ユーザーでログイン
$admin_users = get_users(['role' => 'administrator', 'number' => 1]);
if (!empty($admin_users)) {
    wp_set_current_user($admin_users[0]->ID);
    echo "Logged in as: " . $admin_users[0]->user_login . " (ID: " . $admin_users[0]->ID . ")\n";
} else {
    echo "ERROR: No admin user found\n";
    exit;
}

// AJAXリクエストのシミュレーション
$_POST['action'] = 'send_staff_chat_message';
$_POST['order_id'] = '1';  // テスト用の注文ID
$_POST['message'] = 'Test message from direct script';
$_POST['_ajax_nonce'] = wp_create_nonce('staff_chat');

echo "Simulating AJAX request with:\n";
echo "- action: " . $_POST['action'] . "\n";
echo "- order_id: " . $_POST['order_id'] . "\n";
echo "- message: " . $_POST['message'] . "\n";
echo "- nonce: " . $_POST['_ajax_nonce'] . "\n\n";

// WordPressのAJAX処理をトリガー
echo "Triggering WordPress AJAX processing...\n";

try {
    // WordPress AJAX処理を直接実行
    define('DOING_AJAX', true);

    // wp_ajax_アクションを実行
    ob_start();
    do_action('wp_ajax_send_staff_chat_message');
    $output = ob_get_clean();

    echo "AJAX Handler Output:\n";
    echo $output ? $output : "(No output)\n";

} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
