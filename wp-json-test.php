<?php
/**
 * wp_send_json_* 関数の動作テスト
 */

// WordPress環境の読み込み
require_once(dirname(__FILE__) . '/../../../wp-config.php');

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// テストモードパラメータ
$test_mode = $_GET['test'] ?? 'info';

echo "<!DOCTYPE html>\n";
echo "<html><head><title>wp_send_json Test</title></head><body>\n";
echo "<h1>🧪 wp_send_json 関数テスト</h1>\n";

if ($test_mode === 'info') {
    echo "<h2>📋 テストメニュー</h2>\n";
    echo "<ul>\n";
    echo "<li><a href='?test=success'>wp_send_json_success テスト</a></li>\n";
    echo "<li><a href='?test=error'>wp_send_json_error テスト</a></li>\n";
    echo "<li><a href='?test=manual'>手動JSON出力テスト</a></li>\n";
    echo "<li><a href='?test=buffer'>バッファ状況テスト</a></li>\n";
    echo "</ul>\n";

    echo "<h2>🔍 現在の環境情報</h2>\n";
    echo "<p>PHP Version: " . phpversion() . "</p>\n";
    echo "<p>WordPress Version: " . get_bloginfo('version') . "</p>\n";
    echo "<p>Output Buffer Level: " . ob_get_level() . "</p>\n";
    echo "<p>Headers Sent: " . (headers_sent() ? 'Yes' : 'No') . "</p>\n";

} elseif ($test_mode === 'success') {
    // 成功レスポンステスト
    echo "<p>Testing wp_send_json_success...</p>\n";
    echo "<script>console.log('Before wp_send_json_success');</script>\n";

    // 出力バッファをクリア
    while (ob_get_level()) {
        ob_end_clean();
    }

    wp_send_json_success(array(
        'message' => 'テスト成功',
        'timestamp' => time()
    ));

} elseif ($test_mode === 'error') {
    // エラーレスポンステスト
    echo "<p>Testing wp_send_json_error...</p>\n";
    echo "<script>console.log('Before wp_send_json_error');</script>\n";

    // 出力バッファをクリア
    while (ob_get_level()) {
        ob_end_clean();
    }

    wp_send_json_error('テストエラーメッセージ');

} elseif ($test_mode === 'manual') {
    // 手動JSON出力テスト
    echo "<p>Testing manual JSON output...</p>\n";

    // 出力バッファをクリア
    while (ob_get_level()) {
        ob_end_clean();
    }

    // 手動でヘッダーとJSONを出力
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => true,
        'data' => array(
            'message' => '手動JSON出力テスト',
            'method' => 'manual'
        )
    ));
    exit;

} elseif ($test_mode === 'buffer') {
    // バッファ状況詳細テスト
    echo "<h2>🔍 出力バッファ詳細分析</h2>\n";

    echo "<h3>初期状態</h3>\n";
    echo "<p>Buffer Level: " . ob_get_level() . "</p>\n";
    if (ob_get_level() > 0) {
        $content = ob_get_contents();
        echo "<p>Buffer Content Length: " . strlen($content) . "</p>\n";
        echo "<p>First 200 chars: " . htmlspecialchars(substr($content, 0, 200)) . "</p>\n";
    }

    echo "<h3>バッファ操作テスト</h3>\n";
    ob_start();
    echo "テスト出力1\n";
    echo "テスト出力2\n";
    $captured = ob_get_clean();
    echo "<p>Captured: " . htmlspecialchars($captured) . "</p>\n";

    echo "<h3>wp_send_json テスト</h3>\n";
    echo "<p>次の行でwp_send_json_successを実行します...</p>\n";

    // 強制的にバッファをクリア
    while (ob_get_level()) {
        ob_end_clean();
    }

    wp_send_json_success(array(
        'test' => 'buffer_test',
        'message' => 'バッファテスト完了'
    ));
}

echo "</body></html>\n";
?>
