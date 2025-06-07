<?php
/**
 * AJAX機能の現在の状態をテストするスクリプト
 */

// WordPressの環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

echo "=== KTPWP AJAX Status Test ===\n\n";

// プラグインが有効かチェック
if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

$plugin_file = 'KTPWP/ktpwp.php';
echo "Plugin Status: " . (is_plugin_active($plugin_file) ? "ACTIVE" : "INACTIVE") . "\n";

// KTPWPメインクラスの状態
if (class_exists('KTPWP_Main')) {
    echo "KTPWP_Main class: EXISTS\n";
    $main_instance = KTPWP_Main::get_instance();
    echo "KTPWP_Main instance: " . (is_object($main_instance) ? "CREATED" : "FAILED") . "\n";
} else {
    echo "KTPWP_Main class: NOT EXISTS\n";
}

// KTPWP_Ajaxクラスの状態
if (class_exists('KTPWP_Ajax')) {
    echo "KTPWP_Ajax class: EXISTS\n";
    $ajax_instance = KTPWP_Ajax::get_instance();
    echo "KTPWP_Ajax instance: " . (is_object($ajax_instance) ? "CREATED" : "FAILED") . "\n";
} else {
    echo "KTPWP_Ajax class: NOT EXISTS\n";
}

// 登録されたAJAXアクションを確認
global $wp_filter;

echo "\n=== Registered AJAX Actions ===\n";
$ajax_actions = [
    'wp_ajax_send_staff_chat_message',
    'wp_ajax_get_latest_staff_chat',
    'wp_ajax_ktp_update_project_name',
    'wp_ajax_ktp_auto_save_item',
    'wp_ajax_ktp_create_new_item',
    'wp_ajax_get_logged_in_users'
];

foreach ($ajax_actions as $action) {
    if (isset($wp_filter[$action])) {
        echo "$action: REGISTERED\n";
        // コールバック関数も表示
        foreach ($wp_filter[$action]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function'])) {
                    $class_name = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                    echo "  -> {$class_name}::{$callback['function'][1]} (priority: $priority)\n";
                } else {
                    echo "  -> {$callback['function']} (priority: $priority)\n";
                }
            }
        }
    } else {
        echo "$action: NOT REGISTERED\n";
    }
}

// 実際にAJAXリクエストをテスト（send_staff_chat_message）
echo "\n=== Testing AJAX Handler Directly ===\n";

if (class_exists('KTPWP_Ajax')) {
    $ajax_instance = KTPWP_Ajax::get_instance();

    // テスト用のPOSTデータを設定
    $_POST['message'] = 'Test message from PHP script';
    $_POST['_ajax_nonce'] = wp_create_nonce('staff_chat');

    // 現在のユーザーを設定（管理者として）
    $admin_users = get_users(['role' => 'administrator', 'number' => 1]);
    if (!empty($admin_users)) {
        wp_set_current_user($admin_users[0]->ID);
        echo "Set current user to: " . $admin_users[0]->user_login . "\n";
    }

    try {
        // バッファリングを開始してレスポンスをキャプチャ
        ob_start();

        // AJAX ハンドラーを直接呼び出し
        if (method_exists($ajax_instance, 'ajax_send_staff_chat_message')) {
            echo "Calling ajax_send_staff_chat_message...\n";
            $ajax_instance->ajax_send_staff_chat_message();
        } else {
            echo "ajax_send_staff_chat_message method not found\n";
        }

        $output = ob_get_clean();
        echo "AJAX Handler Output: " . ($output ? $output : "(no output)") . "\n";

    } catch (Exception $e) {
        ob_end_clean();
        echo "Error calling AJAX handler: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
