<?php
/**
 * HTTPヘッダー詳細分析
 */

// WordPress環境の読み込み
require_once(dirname(__FILE__) . '/../../../wp-config.php');

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// ヘッダー出力前に検証
echo "<!DOCTYPE html>\n";
echo "<html><head><title>Header Analysis</title></head><body>\n";
echo "<h1>📡 HTTPヘッダー分析</h1>\n";

// 現在のヘッダー状況をチェック
echo "<h2>🔍 現在のヘッダー状況</h2>\n";
echo "<p>Headers sent: " . (headers_sent() ? 'Yes' : 'No') . "</p>\n";

if (headers_sent($file, $line)) {
    echo "<p style='color: red;'>⚠️ ヘッダーが既に送信されています: {$file}:{$line}</p>\n";
}

// 出力バッファ状況
echo "<p>Output buffer level: " . ob_get_level() . "</p>\n";
if (ob_get_level() > 0) {
    echo "<p>Buffer contents length: " . strlen(ob_get_contents()) . "</p>\n";
}

// JavaScriptでのAJAXリクエスト実行とヘッダー分析
?>

<h2>🧪 リアルタイムAJAXテスト</h2>
<button id="test-ajax">AJAXテストを実行</button>
<div id="results"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.getElementById('test-ajax').addEventListener('click', function() {
    const results = document.getElementById('results');
    results.innerHTML = '<p>テスト実行中...</p>';

    const xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            let html = '<h3>📊 結果</h3>';
            html += '<p><strong>Status:</strong> ' + xhr.status + '</p>';
            html += '<p><strong>Status Text:</strong> ' + xhr.statusText + '</p>';

            html += '<h4>📋 全ヘッダー</h4>';
            html += '<pre style="background: #f0f0f0; padding: 10px; overflow-x: auto;">' +
                    xhr.getAllResponseHeaders() + '</pre>';

            html += '<h4>🎯 重要なヘッダー</h4>';
            html += '<ul>';
            html += '<li><strong>Content-Type:</strong> ' + (xhr.getResponseHeader('Content-Type') || 'なし') + '</li>';
            html += '<li><strong>Content-Length:</strong> ' + (xhr.getResponseHeader('Content-Length') || 'なし') + '</li>';
            html += '<li><strong>Transfer-Encoding:</strong> ' + (xhr.getResponseHeader('Transfer-Encoding') || 'なし') + '</li>';
            html += '</ul>';

            html += '<h4>📄 レスポンス内容</h4>';
            html += '<p><strong>Length:</strong> ' + xhr.responseText.length + '</p>';
            html += '<textarea style="width: 100%; height: 200px; font-family: monospace;">' +
                    xhr.responseText + '</textarea>';

            html += '<h4>🔍 バイナリ解析</h4>';
            let hexDump = '';
            for (let i = 0; i < Math.min(200, xhr.responseText.length); i++) {
                const byte = xhr.responseText.charCodeAt(i);
                hexDump += byte.toString(16).padStart(2, '0') + ' ';
                if ((i + 1) % 16 === 0) hexDump += '\n';
            }
            html += '<pre style="background: #f0f8ff; padding: 10px; font-family: monospace; font-size: 12px;">' +
                    hexDump + '</pre>';

            html += '<h4>🧪 JSON解析テスト</h4>';
            try {
                const parsed = JSON.parse(xhr.responseText);
                html += '<p style="color: green;">✅ JSON解析成功</p>';
                html += '<pre>' + JSON.stringify(parsed, null, 2) + '</pre>';
            } catch (e) {
                html += '<p style="color: red;">❌ JSON解析エラー: ' + e.message + '</p>';

                // 問題の文字を特定
                for (let i = 0; i < xhr.responseText.length; i++) {
                    const char = xhr.responseText[i];
                    const code = char.charCodeAt(0);
                    if (code < 32 && code !== 9 && code !== 10 && code !== 13) {
                        html += '<p style="color: orange;">制御文字発見: 位置 ' + i + ', コード ' + code + '</p>';
                    }
                }
            }

            results.innerHTML = html;
        }
    };

    // AJAX リクエストの送信
    xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    const formData = new URLSearchParams({
        action: 'send_staff_chat_message',
        order_id: 1,
        message: 'ヘッダー分析テスト',
        _ajax_nonce: '<?php echo wp_create_nonce('staff_chat_nonce'); ?>'
    });

    xhr.send(formData.toString());
});
</script>

</body></html>
<?php
?>
