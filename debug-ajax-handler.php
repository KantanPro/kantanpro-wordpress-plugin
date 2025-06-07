<?php
// WordPress環境を読み込み
require_once '../../../wp-config.php';

// ログイン状態を確認
if (!is_user_logged_in()) {
    echo "ログインが必要です\n";
    exit;
}

echo "=== AJAXハンドラー登録状況確認 ===\n";

// KTPWP_Ajaxクラスの存在確認
echo "KTPWP_Ajax クラス存在: " . (class_exists('KTPWP_Ajax') ? 'YES' : 'NO') . "\n";

if (class_exists('KTPWP_Ajax')) {
    // インスタンス取得
    $ajax_instance = KTPWP_Ajax::get_instance();
    echo "インスタンス取得: " . (is_object($ajax_instance) ? 'SUCCESS' : 'FAILED') . "\n";
}

// グローバルなWordPressのAJAXアクション確認
global $wp_filter;

echo "\n=== 登録されているAJAXアクション ===\n";

// wp_ajax_send_staff_chat_messageのフック確認
if (isset($wp_filter['wp_ajax_send_staff_chat_message'])) {
    echo "wp_ajax_send_staff_chat_message: 登録済み\n";
    foreach ($wp_filter['wp_ajax_send_staff_chat_message']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function'])) {
                echo "  - Priority {$priority}: " . get_class($callback['function'][0]) . "::" . $callback['function'][1] . "\n";
            } else {
                echo "  - Priority {$priority}: " . $callback['function'] . "\n";
            }
        }
    }
} else {
    echo "wp_ajax_send_staff_chat_message: 未登録\n";
}

// wp_ajax_nopriv_send_staff_chat_messageのフック確認
if (isset($wp_filter['wp_ajax_nopriv_send_staff_chat_message'])) {
    echo "wp_ajax_nopriv_send_staff_chat_message: 登録済み\n";
} else {
    echo "wp_ajax_nopriv_send_staff_chat_message: 未登録\n";
}

// 手動でAJAXアクションを実行してテスト
echo "\n=== 手動AJAXテスト ===\n";

// POSTデータをシミュレート
$_POST['action'] = 'send_staff_chat_message';
$_POST['order_id'] = '2';
$_POST['message'] = 'テストメッセージ from debug';
$_POST['_ajax_nonce'] = wp_create_nonce('ktpwp_staff_chat_nonce');

echo "POSTデータ設定:\n";
echo "  action: " . $_POST['action'] . "\n";
echo "  order_id: " . $_POST['order_id'] . "\n";
echo "  message: " . $_POST['message'] . "\n";
echo "  nonce: " . $_POST['_ajax_nonce'] . "\n";

// AJAXアクションを直接実行
if (class_exists('KTPWP_Ajax')) {
    $ajax_instance = KTPWP_Ajax::get_instance();
    if (method_exists($ajax_instance, 'ajax_send_staff_chat_message')) {
        echo "\n直接メソッド実行:\n";
        ob_start();
        $ajax_instance->ajax_send_staff_chat_message();
        $output = ob_get_clean();
        echo "出力: " . $output . "\n";
    } else {
        echo "ajax_send_staff_chat_messageメソッドが存在しません\n";
    }
} else {
    echo "KTPWP_Ajaxクラスが見つかりません\n";
}

// WordPressのdo_actionでテスト
echo "\n=== WordPressアクションテスト ===\n";
ob_start();
do_action('wp_ajax_send_staff_chat_message');
$wp_output = ob_get_clean();
echo "WordPress AJAX出力: " . $wp_output . "\n";

// スタッフチャットクラスの直接テスト
echo "\n=== スタッフチャット直接テスト ===\n";
if (class_exists('KTPWP_Staff_Chat')) {
    $staff_chat = KTPWP_Staff_Chat::get_instance();
    echo "KTPWP_Staff_Chat インスタンス: " . (is_object($staff_chat) ? 'SUCCESS' : 'FAILED') . "\n";

    if (method_exists($staff_chat, 'add_message')) {
        $result = $staff_chat->add_message(2, 'Direct test message');
        echo "add_message結果: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    } else {
        echo "add_messageメソッドが存在しません\n";
    }
} else {
    echo "KTPWP_Staff_Chat クラスが見つかりません\n";
}
?>
