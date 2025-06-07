<?php
/**
 * pollNewMessages デバッグ用単体テストページ
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
    <title>pollNewMessages 単体テスト</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .test-section { border: 1px solid #ddd; margin: 15px 0; padding: 20px; border-radius: 4px; }
        button { padding: 12px 20px; margin: 8px; cursor: pointer; background: #0073aa; color: white; border: none; border-radius: 4px; }
        button:hover { background: #005a87; }
        .debug-console { background: #000; color: #0f0; padding: 15px; font-family: monospace; max-height: 400px; overflow-y: auto; border-radius: 4px; }
        .fake-chat { border: 2px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .status-indicator { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px; }
        .status-success { background: #28a745; }
        .status-error { background: #dc3545; }
        .status-waiting { background: #ffc107; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>pollNewMessages 単体テスト</h1>

        <div class="test-section">
            <h3>テスト環境</h3>
            <p><span class="status-indicator status-success"></span>注文ID: <strong><?php echo esc_html($order_id); ?></strong></p>
            <p><span class="status-indicator status-success"></span>ユーザー: <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong></p>
            <p><span class="status-indicator status-success"></span>Ajax URL: <strong><?php echo admin_url('admin-ajax.php'); ?></strong></p>
            <p><span class="status-indicator status-success"></span>Nonce: <strong><?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?></strong></p>
            <p><span class="status-indicator status-success"></span>デバッグモード: <strong>強制有効</strong></p>
        </div>

        <!-- 模擬チャット要素 -->
        <div class="test-section">
            <h3>模擬チャット要素（DOM テスト用）</h3>
            <div class="fake-chat">
                <div id="staff-chat-content" style="display: block;">
                    <div id="staff-chat-messages">
                        <div class="staff-chat-message">
                            <span data-timestamp="2024-01-01 12:00:00">既存メッセージ</span>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="staff_chat_order_id" value="<?php echo esc_attr($order_id); ?>">
                <button id="staff-chat-toggle-btn">チャットトグル</button>
            </div>
        </div>

        <div class="test-section">
            <h3>テスト実行</h3>
            <button onclick="testPollNewMessages()">pollNewMessages() 実行</button>
            <button onclick="testAjaxDirect()">Ajax エンドポイント直接テスト</button>
            <button onclick="testTimerStart()">タイマー開始テスト</button>
            <button onclick="testTimerStop()">タイマー停止</button>
            <button onclick="clearConsole()">ログクリア</button>
        </div>

        <div class="test-section">
            <h3>デバッグコンソール</h3>
            <div id="debug-console" class="debug-console"></div>
        </div>
    </div>

    <script>
        // 強制的にデバッグモードを有効にする
        window.ktpDebugMode = true;

        // KTPWP Ajax 設定
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        window.ktpwp_ajax = {
            nonces: {
                staff_chat: '<?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?>'
            }
        };

        // デバッグログ関数
        function debugLog(message, type = 'info') {
            const console_div = document.getElementById('debug-console');
            const timestamp = new Date().toLocaleTimeString();
            const typeSymbol = type === 'error' ? '❌' : type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';
            console_div.innerHTML += `[${timestamp}] ${typeSymbol} ${message}\n`;
            console_div.scrollTop = console_div.scrollHeight;

            // ブラウザコンソールにも出力
            console.log(`[${timestamp}] ${typeSymbol} ${message}`);
        }

        function clearConsole() {
            document.getElementById('debug-console').innerHTML = '';
        }

        // ポーリング状態管理
        var lastMessageTime = null;
        var isPollingActive = false;
        var pollInterval = null;

        // pollNewMessages 関数（改良版）
        function pollNewMessages() {
            debugLog('🔄 pollNewMessages 実行開始', 'info');

            if (isPollingActive) {
                debugLog('⏳ 既にポーリング中のためスキップ', 'warning');
                return;
            }

            // チャット要素の確認
            var chatContent = document.getElementById('staff-chat-content');
            if (!chatContent) {
                debugLog('❌ staff-chat-content 要素が見つかりません', 'error');
                return;
            }

            if (chatContent.style.display === 'none') {
                debugLog('💤 チャットが閉じているためスキップ', 'info');
                return;
            }

            var orderId = document.querySelector('input[name="staff_chat_order_id"]')?.value;
            if (!orderId) {
                debugLog('❌ staff_chat_order_id が見つかりません', 'error');
                return;
            }

            debugLog(`📡 Ajax リクエスト準備 - 注文ID: ${orderId}`, 'info');

            isPollingActive = true;

            // 最後のメッセージ時刻を取得
            var lastMessageElement = document.querySelector('.staff-chat-message:last-child [data-timestamp]');
            if (lastMessageElement) {
                lastMessageTime = lastMessageElement.getAttribute('data-timestamp');
                debugLog(`🕐 最終メッセージ時刻: ${lastMessageTime}`, 'info');
            }

            // AJAX リクエスト
            var xhr = new XMLHttpRequest();
            var url = ajaxurl;
            var params = `action=get_latest_staff_chat&order_id=${orderId}`;
            if (lastMessageTime) {
                params += `&last_time=${encodeURIComponent(lastMessageTime)}`;
            }

            // nonceを追加
            if (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces && ktpwp_ajax.nonces.staff_chat) {
                params += `&_ajax_nonce=${ktpwp_ajax.nonces.staff_chat}`;
                debugLog(`🔐 Nonce追加: ${ktpwp_ajax.nonces.staff_chat}`, 'info');
            } else {
                debugLog('⚠️ Nonce が見つかりません', 'warning');
            }

            debugLog(`📤 リクエストパラメータ: ${params}`, 'info');

            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    isPollingActive = false;

                    debugLog(`📥 レスポンス受信 - ステータス: ${xhr.status} ${xhr.statusText}`, xhr.status === 200 ? 'success' : 'error');
                    debugLog(`📏 レスポンス長: ${xhr.responseText.length} 文字`, 'info');

                    if (xhr.status === 200) {
                        try {
                            debugLog(`📄 レスポンス内容: ${xhr.responseText.substring(0, 300)}${xhr.responseText.length > 300 ? '...' : ''}`, 'info');

                            var response = JSON.parse(xhr.responseText);
                            debugLog(`✅ JSON解析成功: ${JSON.stringify(response, null, 2)}`, 'success');

                            if (response.success && response.data && response.data.length > 0) {
                                debugLog(`🆕 新しいメッセージ ${response.data.length} 件を取得`, 'success');
                                // 実際のDOM操作は省略（テスト環境のため）
                                debugLog(`📝 メッセージサンプル: ${JSON.stringify(response.data[0], null, 2)}`, 'info');
                            } else if (response.success) {
                                debugLog('📭 新しいメッセージはありません', 'info');
                            } else {
                                debugLog(`❌ サーバーエラー: ${response.data || 'Unknown error'}`, 'error');
                            }
                        } catch (e) {
                            debugLog(`❌ JSON解析エラー: ${e.name}: ${e.message}`, 'error');
                            debugLog(`🔍 レスポンス最初の200文字: ${xhr.responseText.substring(0, 200)}`, 'error');
                            debugLog(`🔍 レスポンス最後の200文字: ${xhr.responseText.substring(Math.max(0, xhr.responseText.length - 200))}`, 'error');

                            // 制御文字の検出
                            const controlChars = xhr.responseText.match(/[\x00-\x1F\x7F]/g);
                            if (controlChars) {
                                debugLog(`🔧 制御文字発見: ${controlChars.map(c => '0x' + c.charCodeAt(0).toString(16)).join(', ')}`, 'error');
                            }
                        }
                    } else {
                        debugLog(`❌ HTTPエラー: ${xhr.status} ${xhr.statusText}`, 'error');
                        debugLog(`📄 エラーレスポンス: ${xhr.responseText}`, 'error');
                    }
                }
            };

            debugLog('🚀 リクエスト送信', 'info');
            xhr.send(params);
        }

        // テスト関数
        function testPollNewMessages() {
            debugLog('=== pollNewMessages 単体テスト開始 ===', 'info');
            pollNewMessages();
        }

        function testAjaxDirect() {
            debugLog('=== Ajax エンドポイント直接テスト開始 ===', 'info');

            const xhr = new XMLHttpRequest();
            const url = ajaxurl;
            const params = `action=get_latest_staff_chat&order_id=<?php echo $order_id; ?>&_ajax_nonce=${ktpwp_ajax.nonces.staff_chat}`;

            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    debugLog(`Direct Test - Status: ${xhr.status}, Response: ${xhr.responseText.substring(0, 200)}`, xhr.status === 200 ? 'success' : 'error');
                }
            };

            xhr.send(params);
        }

        function testTimerStart() {
            if (pollInterval) {
                clearInterval(pollInterval);
            }

            debugLog('🔄 タイマー開始 - 5秒間隔でpollNewMessagesを実行', 'info');
            pollInterval = setInterval(function() {
                debugLog('⏰ タイマー実行', 'info');
                pollNewMessages();
            }, 5000);
        }

        function testTimerStop() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
                debugLog('⏹️ タイマー停止', 'warning');
            } else {
                debugLog('⚠️ 実行中のタイマーがありません', 'warning');
            }
        }

        // 初期化
        debugLog('🎯 pollNewMessages 単体テストページ読み込み完了', 'success');
        debugLog(`🔧 デバッグモード: ${window.ktpDebugMode}`, 'info');
        debugLog(`🔧 Ajax URL: ${ajaxurl}`, 'info');
        debugLog(`🔧 Nonce: ${ktpwp_ajax.nonces.staff_chat}`, 'info');
    </script>
</body>
</html>
