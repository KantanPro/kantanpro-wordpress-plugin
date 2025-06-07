<?php
/**
 * Ajax エンドポイント完全テスト
 */

// WordPress環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

// ログインチェック
if (!is_user_logged_in()) {
    die('ログインが必要です');
}

$order_id = $_GET['order_id'] ?? 1;

// 現在のセッションのクッキー情報を取得
$cookies = $_COOKIE;
$cookie_string = array();
foreach ($cookies as $name => $value) {
    $cookie_string[] = $name . '=' . $value;
}
$cookie_header = implode('; ', $cookie_string);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajax エンドポイント完全テスト</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .test-section { border: 1px solid #ddd; margin: 15px 0; padding: 20px; border-radius: 4px; }
        button { padding: 12px 20px; margin: 8px; cursor: pointer; background: #0073aa; color: white; border: none; border-radius: 4px; }
        button:hover { background: #005a87; }
        .result { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; font-family: monospace; white-space: pre-wrap; }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .warning { border-left: 4px solid #ffc107; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; max-height: 300px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ajax エンドポイント完全テスト</h1>

        <div class="test-section">
            <h3>テスト環境情報</h3>
            <p><strong>注文ID:</strong> <?php echo esc_html($order_id); ?></p>
            <p><strong>Ajax URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
            <p><strong>Nonce:</strong> <?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?></p>
            <p><strong>ユーザーID:</strong> <?php echo get_current_user_id(); ?></p>
            <p><strong>ユーザー名:</strong> <?php echo wp_get_current_user()->display_name; ?></p>
        </div>

        <div class="test-section">
            <h3>JavaScript Ajax テスト</h3>
            <button onclick="testJavaScriptAjax()">JavaScript でテスト</button>
            <button onclick="testWithFormData()">FormData でテスト</button>
            <button onclick="testWithInvalidNonce()">無効なNonce でテスト</button>
            <div id="js-result"></div>
        </div>

        <div class="test-section">
            <h3>cURL コマンド生成</h3>
            <p>以下のコマンドをターミナルで実行してサーバーサイドからテストできます：</p>
            <button onclick="copyToClipboard('curl-command')">コピー</button>
            <pre id="curl-command">curl -X POST "<?php echo admin_url('admin-ajax.php'); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: <?php echo esc_attr($cookie_header); ?>" \
  -d "action=get_latest_staff_chat&order_id=<?php echo $order_id; ?>&_ajax_nonce=<?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?>" \
  -v</pre>
        </div>

        <div class="test-section">
            <h3>PHP直接実行テスト</h3>
            <button onclick="testPHPDirect()">PHP でハンドラー直接実行</button>
            <div id="php-result"></div>
        </div>

        <div class="test-section">
            <h3>デバッグ情報収集</h3>
            <button onclick="collectDebugInfo()">デバッグ情報を収集</button>
            <div id="debug-info"></div>
        </div>
    </div>

    <script>
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const orderId = <?php echo $order_id; ?>;
        const staffChatNonce = '<?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?>';

        function displayResult(elementId, content, type = 'info') {
            const element = document.getElementById(elementId);
            const resultDiv = document.createElement('div');
            resultDiv.className = 'result ' + type;
            resultDiv.textContent = content;
            element.innerHTML = '';
            element.appendChild(resultDiv);
        }

        function testJavaScriptAjax() {
            const resultDiv = document.getElementById('js-result');
            resultDiv.innerHTML = '<div class="result">テスト実行中...</div>';

            const xhr = new XMLHttpRequest();
            const params = `action=get_latest_staff_chat&order_id=${orderId}&_ajax_nonce=${staffChatNonce}`;

            xhr.open('POST', ajaxUrl, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    let result = `HTTPステータス: ${xhr.status} ${xhr.statusText}\n`;
                    result += `レスポンス長: ${xhr.responseText.length}\n`;
                    result += `Content-Type: ${xhr.getResponseHeader('Content-Type')}\n\n`;
                    result += `レスポンス内容:\n${xhr.responseText}`;

                    const type = xhr.status === 200 ? 'success' : 'error';
                    displayResult('js-result', result, type);

                    // JSON解析テスト
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            console.log('✅ JSON解析成功:', response);
                        } catch (e) {
                            console.error('❌ JSON解析エラー:', e.message);
                        }
                    }
                }
            };

            xhr.send(params);
        }

        function testWithFormData() {
            const resultDiv = document.getElementById('js-result');
            resultDiv.innerHTML = '<div class="result">FormData テスト実行中...</div>';

            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            formData.append('action', 'get_latest_staff_chat');
            formData.append('order_id', orderId);
            formData.append('_ajax_nonce', staffChatNonce);

            xhr.open('POST', ajaxUrl, true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    let result = `FormData テスト結果:\n`;
                    result += `HTTPステータス: ${xhr.status} ${xhr.statusText}\n`;
                    result += `レスポンス: ${xhr.responseText}`;

                    const type = xhr.status === 200 ? 'success' : 'error';
                    displayResult('js-result', result, type);
                }
            };

            xhr.send(formData);
        }

        function testWithInvalidNonce() {
            const resultDiv = document.getElementById('js-result');
            resultDiv.innerHTML = '<div class="result">無効なNonce テスト実行中...</div>';

            const xhr = new XMLHttpRequest();
            const params = `action=get_latest_staff_chat&order_id=${orderId}&_ajax_nonce=invalid_nonce_123`;

            xhr.open('POST', ajaxUrl, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    let result = `無効なNonce テスト結果:\n`;
                    result += `HTTPステータス: ${xhr.status} ${xhr.statusText}\n`;
                    result += `レスポンス: ${xhr.responseText}`;

                    displayResult('js-result', result, 'warning');
                }
            };

            xhr.send(params);
        }

        function testPHPDirect() {
            const resultDiv = document.getElementById('php-result');
            resultDiv.innerHTML = '<div class="result">PHP直接実行テスト中...</div>';

            const xhr = new XMLHttpRequest();
            const params = `test_php_direct=1&order_id=${orderId}`;

            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    displayResult('php-result', xhr.responseText, xhr.status === 200 ? 'success' : 'error');
                }
            };

            xhr.send(params);
        }

        function collectDebugInfo() {
            const resultDiv = document.getElementById('debug-info');
            resultDiv.innerHTML = '<div class="result">デバッグ情報収集中...</div>';

            let debugInfo = '=== JavaScript環境情報 ===\n';
            debugInfo += `User Agent: ${navigator.userAgent}\n`;
            debugInfo += `ajaxurl: ${typeof ajaxurl !== 'undefined' ? ajaxurl : '未定義'}\n`;
            debugInfo += `ktpwp_ajax: ${typeof ktpwp_ajax !== 'undefined' ? JSON.stringify(ktpwp_ajax, null, 2) : '未定義'}\n`;
            debugInfo += `ktpwpDebugMode: ${typeof ktpwpDebugMode !== 'undefined' ? ktpwpDebugMode : '未定義'}\n\n`;

            debugInfo += '=== DOM要素確認 ===\n';
            debugInfo += `staff-chat-content: ${document.getElementById('staff-chat-content') ? '存在' : '存在しない'}\n`;
            debugInfo += `staff_chat_order_id input: ${document.querySelector('input[name="staff_chat_order_id"]') ? '存在' : '存在しない'}\n`;
            debugInfo += `staff-chat-messages: ${document.getElementById('staff-chat-messages') ? '存在' : '存在しない'}\n\n`;

            debugInfo += '=== Cookie情報 ===\n';
            debugInfo += `Cookies: ${document.cookie}\n`;

            displayResult('debug-info', debugInfo, 'info');
        }

        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            navigator.clipboard.writeText(text).then(function() {
                alert('クリップボードにコピーしました');
            });
        }
    </script>

    <?php
    // PHP直接テスト処理
    if (isset($_POST['test_php_direct'])) {
        echo "<script>document.getElementById('php-result').innerHTML = '';</script>";

        $order_id = intval($_POST['order_id'] ?? 1);

        echo "=== PHP直接実行テスト結果 ===\n";
        echo "注文ID: $order_id\n";
        echo "実行時刻: " . date('Y-m-d H:i:s') . "\n\n";

        try {
            // KTPWP_Ajax クラスの取得
            if (class_exists('KTPWP_Ajax')) {
                $ajax_instance = KTPWP_Ajax::get_instance();
                echo "✅ KTPWP_Ajax インスタンス取得成功\n";

                // POSTデータを設定
                $_POST['action'] = 'get_latest_staff_chat';
                $_POST['order_id'] = $order_id;
                $_POST['_ajax_nonce'] = wp_create_nonce('ktpwp_staff_chat_nonce');

                // DOING_AJAX フラグを設定
                if (!defined('DOING_AJAX')) {
                    define('DOING_AJAX', true);
                }

                echo "📤 Ajaxハンドラー実行中...\n";

                // 出力バッファを開始
                ob_start();
                $ajax_instance->ajax_get_latest_staff_chat();
                $output = ob_get_clean();

                echo "📥 ハンドラー実行完了\n";
                echo "レスポンス長: " . strlen($output) . " 文字\n";
                echo "レスポンス内容:\n" . $output . "\n";

                // JSON解析テスト
                if (!empty($output)) {
                    $decoded = json_decode($output, true);
                    if ($decoded !== null) {
                        echo "✅ JSON解析成功\n";
                        echo "解析済みデータ: " . print_r($decoded, true) . "\n";
                    } else {
                        echo "❌ JSON解析失敗: " . json_last_error_msg() . "\n";
                    }
                }
            } else {
                echo "❌ KTPWP_Ajax クラスが見つかりません\n";
            }
        } catch (Exception $e) {
            echo "❌ エラー: " . $e->getMessage() . "\n";
            echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
        }

        exit;
    }
    ?>
</body>
</html>
