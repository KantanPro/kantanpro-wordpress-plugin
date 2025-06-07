<?php
// WordPress環境を読み込み
require_once '../../../wp-config.php';

// ログイン状態を確認
if (!is_user_logged_in()) {
    echo "ログインが必要です\n";
    exit;
}

echo "=== AJAX設定デバッグ ===\n";

// 現在のユーザー情報
$current_user = wp_get_current_user();
echo "ユーザーID: " . $current_user->ID . "\n";
echo "ユーザー名: " . $current_user->user_login . "\n";

// nonce生成テスト
$nonce = wp_create_nonce('ktpwp_staff_chat_nonce');
echo "生成されたnonce: " . $nonce . "\n";
echo "nonce検証: " . (wp_verify_nonce($nonce, 'ktpwp_staff_chat_nonce') ? 'OK' : 'NG') . "\n";

// AJAX URL
echo "AJAX URL: " . admin_url('admin-ajax.php') . "\n";

// KTPWPプラグイン状態
echo "KTPWPプラグイン有効: " . (is_plugin_active('KTPWP/ktpwp.php') ? 'YES' : 'NO') . "\n";

// Assetsクラスの存在確認
echo "Assetsクラス存在: " . (class_exists('KTPWP_Assets') ? 'YES' : 'NO') . "\n";

// AJAXクラスの存在確認  
echo "AJAXクラス存在: " . (class_exists('KTPWP_Ajax') ? 'YES' : 'NO') . "\n";

// フックの確認
echo "\n=== 登録されているフック ===\n";
global $wp_filter;

if (isset($wp_filter['wp_head'])) {
    foreach ($wp_filter['wp_head']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && 
                is_object($callback['function'][0]) && 
                get_class($callback['function'][0]) === 'KTPWP_Assets') {
                echo "wp_head[{$priority}]: " . get_class($callback['function'][0]) . "::" . $callback['function'][1] . "\n";
            }
        }
    }
}

if (isset($wp_filter['wp_footer'])) {
    foreach ($wp_filter['wp_footer']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && 
                is_object($callback['function'][0]) && 
                get_class($callback['function'][0]) === 'KTPWP_Assets') {
                echo "wp_footer[{$priority}]: " . get_class($callback['function'][0]) . "::" . $callback['function'][1] . "\n";
            }
        }
    }
}

// AJAX設定生成
$ajax_data = array(
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'nonces' => array(
        'staff_chat' => wp_create_nonce('ktpwp_staff_chat_nonce')
    )
);

echo "\n=== 生成すべきAJAX設定 ===\n";
echo json_encode($ajax_data, JSON_PRETTY_PRINT) . "\n";
?>
