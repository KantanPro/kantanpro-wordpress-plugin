<?php
/**
 * 最小限の環境でのAJAXテスト（他のプラグイン干渉をチェック）
 */

// 直接WordPressを読み込み、他のプラグインの影響を排除
define('WP_USE_THEMES', false);
require_once(dirname(__FILE__) . '/../../../wp-load.php');

// ユーザーログイン
if (!is_user_logged_in()) {
    wp_set_current_user(1);
}

// この時点でKTPWPプラグインのみを読み込み
if (!class_exists('KTPWP_Ajax')) {
    require_once(dirname(__FILE__) . '/includes/class-ktpwp-ajax.php');
}

if (!class_exists('KTPWP_Staff_Chat')) {
    require_once(dirname(__FILE__) . '/includes/class-ktpwp-staff-chat.php');
}

// nonceを生成
$nonce = wp_create_nonce('staff_chat_nonce');

// POSTリクエストをシミュレート
$_POST = array(
    'action' => 'send_staff_chat_message',
    'order_id' => 1,
    'message' => '干渉テストメッセージ',
    '_ajax_nonce' => $nonce
);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Isolated AJAX Test</title></head><body>\n";
echo "<h1>🔬 分離環境でのAJAXテスト</h1>\n";

echo "<h2>📋 テスト情報</h2>\n";
echo "<p>Nonce: " . htmlspecialchars($nonce) . "</p>\n";
echo "<p>User ID: " . get_current_user_id() . "</p>\n";
echo "<p>Active Plugins: " . count(get_option('active_plugins', array())) . "</p>\n";

echo "<h2>🧪 直接実行テスト</h2>\n";

// 出力バッファリングで結果をキャプチャ
ob_start();

try {
    // AJAXハンドラーを直接実行
    $ajax_instance = KTPWP_Ajax::get_instance();

    // レスポンスをキャプチャするため、wp_send_json_*をオーバーライド
    $original_exit = false;

    // 実際のメソッドを実行
    $ajax_instance->ajax_send_staff_chat_message();

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 例外エラー: " . htmlspecialchars($e->getMessage()) . "</p>\n";
} catch (Error $e) {
    echo "<p style='color: red;'>❌ PHPエラー: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

$captured_output = ob_get_clean();

echo "<h3>📄 キャプチャされた出力</h3>\n";
echo "<p><strong>Length:</strong> " . strlen($captured_output) . " bytes</p>\n";

if (!empty($captured_output)) {
    echo "<pre style='background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow: auto;'>" . htmlspecialchars($captured_output) . "</pre>\n";

    echo "<h3>🔍 JSON解析テスト</h3>\n";
    $json_decoded = json_decode($captured_output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ JSON パース成功</p>\n";
        echo "<pre>" . print_r($json_decoded, true) . "</pre>\n";
    } else {
        echo "<p style='color: red;'>❌ JSON パースエラー: " . json_last_error_msg() . "</p>\n";
    }
} else {
    echo "<p style='color: orange;'>⚠️ 出力がキャプチャされませんでした（wp_send_json_*が直接exitした可能性）</p>\n";
}

echo "</body></html>\n";
?>
