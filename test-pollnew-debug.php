<?php
/**
 * pollNewMessages デバッグ専用テストページ
 * Usage: /wp-content/plugins/KTPWP/test-pollnew-debug.php?order_id=123
 */

// WordPress環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

// ログインチェック
if (!is_user_logged_in()) {
    die('ログインが必要です');
}

$order_id = $_GET['order_id'] ?? 1;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pollNewMessages デバッグテスト</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; }
        .test-result { background: #f5f5f5; padding: 10px; margin-top: 10px; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        #debug-output { background: #000; color: #0f0; padding: 15px; font-family: monospace; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <h1>pollNewMessages デバッグテスト</h1>

    <div class="test-section">
        <h3>テスト設定</h3>
        <p>注文ID: <strong><?php echo esc_html($order_id); ?></strong></p>
        <p>現在のユーザー: <strong><?php echo esc_html(wp_get_current_user()->user_login); ?></strong></p>
        <p>Ajax URL: <strong><?php echo admin_url('admin-ajax.php'); ?></strong></p>
    </div>

    <div class="test-section">
        <h3>テスト実行</h3>
        <button onclick="testDirectAjax()">Direct AJAX Test</button>
        <button onclick="testPollNewMessages()">pollNewMessages Test</button>
        <button onclick="clearDebugOutput()">ログクリア</button>
    </div>

    <div class="test-section">
        <h3>デバッグ出力</h3>
        <div id="debug-output"></div>
    </div>

    <!-- デバッグ用の隠し要素 -->
    <input type="hidden" name="staff_chat_order_id" value="<?php echo esc_attr($order_id); ?>">

    <script>
        // WordPress AJAX URL
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

        // デバッグモードを有効
        window.ktpDebugMode = true;

        // KTPWP Ajax nonceを設定
        window.ktpwp_ajax = {
            nonces: {
                staff_chat: '<?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?>'
            }
        };

        function log(message, type = 'info') {
            const output = document.getElementById('debug-output');
            const timestamp = new Date().toLocaleTimeString();
            const typeSymbol = type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️';
            output.innerHTML += `[${timestamp}] ${typeSymbol} ${message}\n`;
            output.scrollTop = output.scrollHeight;
        }

        function clearDebugOutput() {
            document.getElementById('debug-output').innerHTML = '';
        }

        function testDirectAjax() {
            log('=== Direct AJAX Test 開始 ===');

            const orderId = <?php echo intval($order_id); ?>;
            const xhr = new XMLHttpRequest();
            const url = ajaxurl;
            const params = `action=get_latest_staff_chat&order_id=${orderId}&_ajax_nonce=${ktpwp_ajax.nonces.staff_chat}`;

            log(`リクエストURL: ${url}`);
            log(`パラメータ: ${params}`);

            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    log(`HTTPステータス: ${xhr.status} ${xhr.statusText}`);
                    log(`レスポンス長: ${xhr.responseText.length}`);
                    log(`レスポンスヘッダー: ${xhr.getAllResponseHeaders()}`);

                    if (xhr.status === 200) {
                        try {
                            log(`レスポンス内容 (最初の500文字): ${xhr.responseText.substring(0, 500)}`);
                            const response = JSON.parse(xhr.responseText);
                            log(`✅ JSON解析成功: ${JSON.stringify(response, null, 2)}`, 'success');
                        } catch (e) {
                            log(`❌ JSON解析エラー: ${e.message}`, 'error');
                            log(`レスポンス全体: ${xhr.responseText}`, 'error');

                            // 制御文字の検出
                            const controlChars = xhr.responseText.match(/[\x00-\x1F\x7F]/g);
                            if (controlChars) {
                                log(`制御文字発見: ${controlChars.map(c => '0x' + c.charCodeAt(0).toString(16)).join(', ')}`, 'error');
                            }
                        }
                    } else {
                        log(`❌ HTTPエラー: ${xhr.responseText}`, 'error');
                    }
                }
            };

            log('リクエスト送信中...');
            xhr.send(params);
        }

        // pollNewMessages関数の最小版
        function testPollNewMessages() {
            log('=== pollNewMessages Test 開始 ===');

            const orderId = document.querySelector('input[name="staff_chat_order_id"]')?.value;
            if (!orderId) {
                log('❌ 注文IDが見つかりません', 'error');
                return;
            }

            log(`注文ID: ${orderId}`);

            const xhr = new XMLHttpRequest();
            const url = ajaxurl;
            let params = `action=get_latest_staff_chat&order_id=${orderId}`;

            // nonceを追加
            if (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces && ktpwp_ajax.nonces.staff_chat) {
                params += `&_ajax_nonce=${ktpwp_ajax.nonces.staff_chat}`;
            }

            log(`パラメータ: ${params}`);

            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    log(`HTTPステータス: ${xhr.status} ${xhr.statusText}`);

                    if (xhr.status === 200) {
                        try {
                            log(`レスポンス内容 (最初の500文字): ${xhr.responseText.substring(0, 500)}`);
                            const response = JSON.parse(xhr.responseText);
                            log(`✅ pollNewMessages JSON解析成功: ${JSON.stringify(response, null, 2)}`, 'success');

                            if (response.success && response.data && response.data.length > 0) {
                                log(`✅ 新しいメッセージ ${response.data.length} 件を取得`, 'success');
                            } else {
                                log('新しいメッセージはありません');
                            }
                        } catch (e) {
                            log(`❌ pollNewMessages JSON解析エラー: ${e.name}: ${e.message}`, 'error');
                            log(`レスポンス長: ${xhr.responseText.length}`, 'error');
                            log(`レスポンスヘッダー: ${xhr.getAllResponseHeaders()}`, 'error');

                            if (xhr.responseText) {
                                log(`レスポンス最初の200文字: ${xhr.responseText.substring(0, 200)}`, 'error');
                                log(`レスポンス最後の200文字: ${xhr.responseText.substring(Math.max(0, xhr.responseText.length - 200))}`, 'error');

                                // 制御文字の検出
                                const controlChars = xhr.responseText.match(/[\x00-\x1F\x7F]/g);
                                if (controlChars) {
                                    log(`制御文字発見: ${controlChars.map(c => '0x' + c.charCodeAt(0).toString(16)).join(', ')}`, 'error');
                                }
                            }
                        }
                    } else {
                        log(`❌ pollNewMessages HTTPエラー: ${xhr.status} ${xhr.statusText}`, 'error');
                        log(`レスポンス: ${xhr.responseText}`, 'error');
                    }
                }
            };

            log('pollNewMessages リクエスト送信中...');
            xhr.send(params);
        }

        log('pollNewMessages デバッグテストページ読み込み完了');
        log(`Ajax URL: ${ajaxurl}`);
        log(`Nonce: ${ktpwp_ajax.nonces.staff_chat}`);
    </script>
</body>
</html>
