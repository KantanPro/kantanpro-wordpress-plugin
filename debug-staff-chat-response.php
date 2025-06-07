<?php
/**
 * スタッフチャットのレスポンス詳細デバッグ
 */

// WordPress環境の読み込み
require_once(dirname(__FILE__) . '/../../../wp-config.php');

// WordPress初期化
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// ユーザーログイン状態を確認（テスト用に管理者としてログイン）
if (!is_user_logged_in()) {
    wp_set_current_user(1);
}

// nonceを生成
$nonce = wp_create_nonce('staff_chat_nonce');

?><!DOCTYPE html>
<html>
<head>
    <title>Staff Chat Response Debug</title>
    <meta charset="utf-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<h1>スタッフチャットレスポンス詳細デバッグ</h1>

<h2>テスト設定:</h2>
<p>Order ID: 1</p>
<p>Generated Nonce: <?php echo $nonce; ?></p>

<button id="test-send">メッセージ送信テスト</button>
<button id="test-direct">直接AJAX送信</button>

<h2>詳細ログ:</h2>
<div id="debug-output" style="border: 1px solid #ccc; padding: 10px; height: 400px; overflow-y: auto; white-space: pre-wrap; font-family: monospace;"></div>

<script>
function log(message) {
    const output = document.getElementById('debug-output');
    const timestamp = new Date().toLocaleTimeString();
    output.textContent += `[${timestamp}] ${message}\n`;
    output.scrollTop = output.scrollHeight;
}

// 直接AJAX送信テスト
document.getElementById('test-direct').addEventListener('click', function() {
    log('=== 直接AJAX送信テスト開始 ===');

    const xhr = new XMLHttpRequest();
    const data = new FormData();
    data.append('action', 'send_staff_chat_message');
    data.append('order_id', '1');
    data.append('message', 'デバッグテストメッセージ ' + Date.now());
    data.append('_ajax_nonce', '<?php echo $nonce; ?>');

    xhr.onreadystatechange = function() {
        log(`ReadyState: ${xhr.readyState}, Status: ${xhr.status}`);

        if (xhr.readyState === 4) {
            log(`=== 最終レスポンス ===`);
            log(`Status: ${xhr.status}`);
            log(`Status Text: ${xhr.statusText}`);
            log(`Response Headers: ${xhr.getAllResponseHeaders()}`);
            log(`Response Text Length: ${xhr.responseText.length}`);
            log(`Response Text (first 500 chars): ${xhr.responseText.substring(0, 500)}`);
            log(`Response Text (raw): ${JSON.stringify(xhr.responseText)}`);

            if (xhr.status === 200) {
                try {
                    const parsed = JSON.parse(xhr.responseText);
                    log(`JSON Parse Success: ${JSON.stringify(parsed, null, 2)}`);
                } catch (e) {
                    log(`JSON Parse Error: ${e.message}`);
                    log(`Error at position: ${e.message.match(/position (\d+)/) ? e.message.match(/position (\d+)/)[1] : 'unknown'}`);

                    // 文字コード解析
                    const bytes = [];
                    for (let i = 0; i < Math.min(xhr.responseText.length, 100); i++) {
                        bytes.push(xhr.responseText.charCodeAt(i));
                    }
                    log(`First 100 character codes: ${bytes.join(', ')}`);
                }
            }
        }
    };

    xhr.open('POST', '/wp-admin/admin-ajax.php', true);
    xhr.send(data);
});

// KTPWPスタイルのAJAX送信テスト
document.getElementById('test-send').addEventListener('click', function() {
    log('=== KTPWPスタイルAJAX送信テスト開始 ===');

    const params = 'action=send_staff_chat_message&order_id=1&message=' +
                  encodeURIComponent('KTPWPテストメッセージ ' + Date.now()) +
                  '&_ajax_nonce=<?php echo $nonce; ?>';

    log(`送信パラメータ: ${params}`);

    const xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            log(`=== KTPWPスタイル最終レスポンス ===`);
            log(`Status: ${xhr.status}`);
            log(`Response: ${xhr.responseText}`);
            log(`Response Type: ${typeof xhr.responseText}`);
            log(`Response Length: ${xhr.responseText.length}`);

            // 改行文字やBOMをチェック
            const firstChar = xhr.responseText.charCodeAt(0);
            const lastChar = xhr.responseText.charCodeAt(xhr.responseText.length - 1);
            log(`First char code: ${firstChar} (${String.fromCharCode(firstChar)})`);
            log(`Last char code: ${lastChar} (${String.fromCharCode(lastChar)})`);

            // BOM検出
            if (firstChar === 65279) {
                log('⚠️ BOM (Byte Order Mark) detected!');
            }

            // 先頭・末尾の空白文字チェック
            const trimmed = xhr.responseText.trim();
            if (trimmed !== xhr.responseText) {
                log(`⚠️ Whitespace detected! Original length: ${xhr.responseText.length}, Trimmed: ${trimmed.length}`);
            }

            try {
                const parsed = JSON.parse(xhr.responseText);
                log(`✅ JSON Parse Success: ${JSON.stringify(parsed, null, 2)}`);
            } catch (e) {
                log(`❌ JSON Parse Error: ${e.message}`);

                // トリムしてリトライ
                try {
                    const trimmedParsed = JSON.parse(trimmed);
                    log(`✅ Trimmed JSON Parse Success: ${JSON.stringify(trimmedParsed, null, 2)}`);
                    log('💡 Solution: Response needs trimming before JSON.parse()');
                } catch (e2) {
                    log(`❌ Even trimmed parse failed: ${e2.message}`);
                }
            }
        }
    };

    xhr.open('POST', '/wp-admin/admin-ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send(params);
});

log('デバッグツール準備完了');
</script>

</body>
</html>
