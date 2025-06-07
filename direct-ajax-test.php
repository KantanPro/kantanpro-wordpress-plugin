<?php
/**
 * スタッフチャット送信テスト（直接JavaScript設定）
 */

// WordPress環境の読み込み
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    die('WordPressの読み込みに失敗しました');
}

// ログイン確認
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
    exit;
}

$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html>
<head>
    <title>スタッフチャット送信テスト</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-container { max-width: 600px; border: 1px solid #ccc; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, button { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        input[type="text"], input[type="number"], textarea { width: 100%; }
        button { background-color: #0073aa; color: white; cursor: pointer; }
        button:hover { background-color: #005177; }
        .log { background-color: #f5f5f5; padding: 10px; margin-top: 20px; border-radius: 4px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>スタッフチャット送信テスト</h1>

    <div class="test-container">
        <h2>ユーザー情報</h2>
        <p><strong>ログインユーザー:</strong> <?php echo esc_html($current_user->user_login); ?></p>
        <p><strong>ユーザーID:</strong> <?php echo esc_html($current_user->ID); ?></p>
        <p><strong>権限:</strong> <?php echo esc_html(implode(', ', $current_user->roles)); ?></p>

        <h2>メッセージ送信</h2>
        <form id="test-form">
            <div class="form-group">
                <label for="order_id">注文ID:</label>
                <input type="number" id="order_id" name="order_id" value="2" required>
            </div>

            <div class="form-group">
                <label for="message">メッセージ:</label>
                <textarea id="message" name="message" rows="3" required placeholder="テストメッセージを入力してください"></textarea>
            </div>

            <div class="form-group">
                <button type="submit">メッセージ送信</button>
            </div>
        </form>

        <div id="log" class="log">
            <strong>ログ:</strong><br>
            <div id="log-content">テスト開始...<br></div>
        </div>
    </div>

    <script>
        // AJAX設定を直接埋め込み
        window.ktpwp_ajax = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonces: {
                staff_chat: '<?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?>'
            }
        };

        window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

        function addLog(message, type = 'info') {
            const logContent = document.getElementById('log-content');
            const timestamp = new Date().toLocaleTimeString();
            const className = type === 'error' ? 'error' : (type === 'success' ? 'success' : '');
            logContent.innerHTML += `<span class="${className}">[${timestamp}] ${message}</span><br>`;
            logContent.scrollTop = logContent.scrollHeight;
        }

        document.addEventListener('DOMContentLoaded', function() {
            addLog('DOM読み込み完了');
            addLog('AJAX設定: ' + JSON.stringify(window.ktpwp_ajax));

            document.getElementById('test-form').addEventListener('submit', function(e) {
                e.preventDefault();

                const orderId = document.getElementById('order_id').value;
                const message = document.getElementById('message').value;

                if (!orderId || !message) {
                    addLog('注文IDとメッセージが必要です', 'error');
                    return;
                }

                addLog('メッセージ送信開始...');
                addLog(`注文ID: ${orderId}, メッセージ: ${message}`);

                // AJAX送信
                const xhr = new XMLHttpRequest();
                const url = window.ktpwp_ajax.ajax_url;
                let params = `action=send_staff_chat_message&order_id=${orderId}&message=${encodeURIComponent(message)}`;

                // nonceを追加
                if (window.ktpwp_ajax.nonces && window.ktpwp_ajax.nonces.staff_chat) {
                    params += `&_ajax_nonce=${window.ktpwp_ajax.nonces.staff_chat}`;
                    addLog('nonce追加: ' + window.ktpwp_ajax.nonces.staff_chat);
                }

                addLog('送信URL: ' + url);
                addLog('送信パラメータ: ' + params);

                xhr.open('POST', url, true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        addLog(`HTTP ステータス: ${xhr.status}`);
                        addLog(`レスポンス: ${xhr.responseText}`);

                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                addLog('パース済みレスポンス: ' + JSON.stringify(response));

                                if (response.success) {
                                    addLog('メッセージ送信成功！', 'success');
                                    document.getElementById('message').value = '';
                                } else {
                                    addLog('送信失敗: ' + (response.data || '不明なエラー'), 'error');
                                }
                            } catch (e) {
                                addLog('JSON解析エラー: ' + e.message, 'error');
                            }
                        } else {
                            addLog('HTTPエラーが発生しました', 'error');
                        }
                    }
                };

                xhr.send(params);
            });
        });
    </script>
</body>
</html>
