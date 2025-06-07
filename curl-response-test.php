<?php
/**
 * cURLを使用したAJAXレスポンステスト
 */

// WordPress環境の読み込み
require_once(dirname(__FILE__) . '/../../../wp-config.php');

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// ユーザーログイン状態を確認・設定
if (!is_user_logged_in()) {
    wp_set_current_user(1);
}

// Cookieを取得
$cookie_name = '';
$cookie_value = '';
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'wordpress_logged_in_') === 0) {
        $cookie_name = $name;
        $cookie_value = $value;
        break;
    }
}

// nonceを生成
$nonce = wp_create_nonce('staff_chat_nonce');

echo "<!DOCTYPE html>\n";
echo "<html><head><title>cURL Response Test</title></head><body>\n";
echo "<h1>🧪 cURL レスポンステスト</h1>\n";

echo "<h2>🔑 認証情報</h2>\n";
echo "<p>Cookie Name: " . htmlspecialchars($cookie_name) . "</p>\n";
echo "<p>Cookie Value: " . htmlspecialchars(substr($cookie_value, 0, 50)) . "...</p>\n";
echo "<p>Nonce: " . htmlspecialchars($nonce) . "</p>\n";

// AJAX URLを構築
$ajax_url = admin_url('admin-ajax.php');

// POSTデータを準備
$post_data = array(
    'action' => 'send_staff_chat_message',
    'order_id' => 1,
    'message' => 'cURLテストメッセージ',
    '_ajax_nonce' => $nonce
);

// cURLでリクエストを送信
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ajax_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_COOKIE, $cookie_name . '=' . $cookie_value);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; KTPWP-Test)');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

// レスポンスをヘッダーとボディに分離
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

echo "<h2>📡 レスポンス結果</h2>\n";
echo "<p><strong>HTTP Status:</strong> " . $http_code . "</p>\n";

echo "<h3>📋 レスポンスヘッダー</h3>\n";
echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>" . htmlspecialchars($headers) . "</pre>\n";

echo "<h3>📄 レスポンスボディ</h3>\n";
echo "<p><strong>Length:</strong> " . strlen($body) . " bytes</p>\n";
echo "<p><strong>Content:</strong></p>\n";
echo "<pre style='background: #f9f9f9; padding: 10px; border: 1px solid #ddd; overflow-x: auto;'>" . htmlspecialchars($body) . "</pre>\n";

echo "<h3>🔍 バイナリ分析 (最初の100バイト)</h3>\n";
$hex_dump = '';
for ($i = 0; $i < min(100, strlen($body)); $i++) {
    $hex_dump .= sprintf('%02x ', ord($body[$i]));
    if (($i + 1) % 16 === 0) {
        $hex_dump .= "\n";
    }
}
echo "<pre style='background: #f0f8ff; padding: 10px; font-family: monospace;'>" . $hex_dump . "</pre>\n";

echo "<h3>🧪 JSON検証</h3>\n";
$json_decoded = json_decode($body, true);
$json_error = json_last_error();

if ($json_error === JSON_ERROR_NONE) {
    echo "<p style='color: green;'>✅ <strong>JSON パース成功</strong></p>\n";
    echo "<pre style='background: #f0fff0; padding: 10px;'>" . print_r($json_decoded, true) . "</pre>\n";
} else {
    echo "<p style='color: red;'>❌ <strong>JSON パースエラー:</strong> " . json_last_error_msg() . " (Code: " . $json_error . ")</p>\n";

    // 無効な文字を探す
    echo "<h4>🔍 無効文字の検出</h4>\n";
    $clean_body = trim($body);
    if ($clean_body !== $body) {
        echo "<p style='color: orange;'>⚠️ 前後に空白文字が検出されました</p>\n";
    }

    // 制御文字をチェック
    $has_control_chars = false;
    for ($i = 0; $i < strlen($body); $i++) {
        $char = ord($body[$i]);
        if ($char < 32 && $char !== 9 && $char !== 10 && $char !== 13) {
            $has_control_chars = true;
            echo "<p style='color: red;'>制御文字検出: 位置 " . $i . ", コード " . $char . "</p>\n";
        }
    }

    if (!$has_control_chars) {
        echo "<p style='color: blue;'>制御文字は検出されませんでした</p>\n";
    }
}

echo "</body></html>\n";
?>
