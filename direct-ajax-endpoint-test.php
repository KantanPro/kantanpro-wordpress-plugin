<?php
/**
 * 直接AJAXエンドポイントテスト
 */

// WordPress管理者としてログイン
require_once(dirname(__FILE__) . '/../../../wp-config.php');
wp_set_current_user(1); // 管理者ユーザー

// nonceを生成
$nonce = wp_create_nonce('staff_chat_nonce');

// AJAX URLを取得
$ajax_url = admin_url('admin-ajax.php');

// POSTデータを準備
$post_data = array(
    'action' => 'send_staff_chat_message',
    'order_id' => 1,
    'message' => 'エンドポイント直接テスト',
    '_ajax_nonce' => $nonce
);

// WordPressのセッションCookieを取得
$cookie_header = '';
if (isset($_COOKIE)) {
    $cookies = array();
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'wordpress_') === 0 || strpos($name, 'wp_') === 0) {
            $cookies[] = $name . '=' . $value;
        }
    }
    if (!empty($cookies)) {
        $cookie_header = implode('; ', $cookies);
    }
}

echo "<!DOCTYPE html>\n<html><head><title>Direct AJAX Test</title></head><body>\n";
echo "<h1>🎯 直接AJAXエンドポイントテスト</h1>\n";
echo "<p>AJAX URL: " . htmlspecialchars($ajax_url) . "</p>\n";
echo "<p>Nonce: " . htmlspecialchars($nonce) . "</p>\n";
echo "<p>Cookie Header: " . htmlspecialchars($cookie_header) . "</p>\n";

// cURLでリクエスト実行
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => $ajax_url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($post_data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_COOKIE => $cookie_header,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => false
));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "<p style='color: red;'>cURLエラー: " . htmlspecialchars($curl_error) . "</p>\n";
} else {
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    echo "<h2>📊 結果</h2>\n";
    echo "<p><strong>HTTP Status:</strong> " . $http_code . "</p>\n";
    echo "<p><strong>Response Length:</strong> " . strlen($body) . " bytes</p>\n";

    echo "<h3>📋 レスポンスヘッダー</h3>\n";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>" . htmlspecialchars($headers) . "</pre>\n";

    echo "<h3>📄 レスポンスボディ</h3>\n";
    echo "<pre style='background: #f9f9f9; padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($body) . "</pre>\n";

    echo "<h3>🔍 バイナリ解析 (最初の100バイト)</h3>\n";
    echo "<div style='font-family: monospace; background: #f0f8ff; padding: 10px;'>\n";
    for ($i = 0; $i < min(100, strlen($body)); $i++) {
        $byte = ord($body[$i]);
        $char = ($byte >= 32 && $byte < 127) ? $body[$i] : '.';
        printf("%02x(%s) ", $byte, $char);
        if (($i + 1) % 8 === 0) echo "<br>\n";
    }
    echo "</div>\n";

    echo "<h3>🧪 JSON解析</h3>\n";
    $json_decoded = json_decode($body, true);
    $json_error = json_last_error();

    if ($json_error === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ JSON解析成功</p>\n";
        echo "<pre>" . print_r($json_decoded, true) . "</pre>\n";
    } else {
        echo "<p style='color: red;'>❌ JSON解析エラー: " . json_last_error_msg() . " (Code: " . $json_error . ")</p>\n";

        // 問題のある文字を特定
        echo "<h4>問題の文字検出</h4>\n";
        $clean_start = 0;
        $clean_end = strlen($body);

        // 先頭から有効でない文字を探す
        for ($i = 0; $i < strlen($body); $i++) {
            $char = $body[$i];
            if ($char === '{' || $char === '[') {
                $clean_start = $i;
                break;
            }
            if (ord($char) < 32 && $char !== "\t" && $char !== "\n" && $char !== "\r") {
                echo "<p style='color: orange;'>先頭制御文字: 位置 {$i}, コード " . ord($char) . "</p>\n";
            }
        }

        // 末尾から有効でない文字を探す
        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $char = $body[$i];
            if ($char === '}' || $char === ']') {
                $clean_end = $i + 1;
                break;
            }
        }

        if ($clean_start > 0 || $clean_end < strlen($body)) {
            echo "<p>クリーンな範囲: {$clean_start} - {$clean_end}</p>\n";
            $clean_json = substr($body, $clean_start, $clean_end - $clean_start);
            echo "<p>クリーンJSON:</p>\n";
            echo "<pre style='background: #f0fff0; padding: 10px;'>" . htmlspecialchars($clean_json) . "</pre>\n";

            $clean_decoded = json_decode($clean_json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<p style='color: green;'>✅ クリーンJSON解析成功</p>\n";
                echo "<pre>" . print_r($clean_decoded, true) . "</pre>\n";
            }
        }
    }
}

echo "</body></html>\n";
?>
