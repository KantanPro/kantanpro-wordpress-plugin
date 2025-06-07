<?php
/**
 * 最終テスト: 実際の環境でのスタッフチャット動作確認
 */

// WordPress環境の読み込み
require_once(dirname(__FILE__) . '/../../../wp-config.php');

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// ユーザーログイン状態を確認
if (!is_user_logged_in()) {
    wp_set_current_user(1);
}

// nonceを生成
$nonce = wp_create_nonce('staff_chat_nonce');

?><!DOCTYPE html>
<html>
<head>
    <title>Final Staff Chat Test</title>
    <meta charset="utf-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<h1>最終テスト: スタッフチャット機能</h1>

<div style="background: #f0f0f0; padding: 15px; margin: 10px 0;">
    <h3>🧪 テスト項目</h3>
    <ul>
        <li>✅ HTTP 500エラーの解消</li>
        <li>❓ JavaScript エラーアラートの解消</li>
        <li>❓ 正常なJSON レスポンス処理</li>
        <li>❓ メッセージ送信・表示の正常動作</li>
    </ul>
</div>

<div style="border: 1px solid #ddd; padding: 20px; margin: 20px 0;">
    <h3>📝 スタッフチャット テスト</h3>
    <p>Order ID: 1</p>
    <p>Test Nonce: <?php echo $nonce; ?></p>

    <div style="margin: 10px 0;">
        <input type="text" id="message-input" placeholder="テストメッセージを入力..." style="width: 300px; padding: 8px;">
        <button id="send-message" style="padding: 8px 15px;">送信</button>
    </div>

    <div style="margin: 10px 0;">
        <button id="test-clean-response" style="padding: 8px 15px; background: #28a745; color: white;">クリーンレスポンステスト</button>
        <button id="test-original-js" style="padding: 8px 15px; background: #007bff; color: white;">実際のJS処理テスト</button>
    </div>
</div>

<div style="border: 1px solid #ddd; padding: 15px; margin: 20px 0; height: 400px; overflow-y: auto; background: #f8f9fa;">
    <h4>📊 テスト結果</h4>
    <div id="test-results"></div>
</div>

<script>
function logResult(message, type = 'info') {
    const results = document.getElementById('test-results');
    const timestamp = new Date().toLocaleTimeString();
    const color = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    }[type] || '#17a2b8';

    results.innerHTML += `<div style="margin: 5px 0; padding: 8px; border-left: 4px solid ${color}; background: white;">
        <strong>[${timestamp}]</strong> ${message}
    </div>`;
    results.scrollTop = results.scrollHeight;
}

// クリーンレスポンステスト（error_log削除後の確認）
document.getElementById('test-clean-response').addEventListener('click', function() {
    logResult('🧪 クリーンレスポンステスト開始', 'info');

    const testData = {
        action: 'send_staff_chat_message',
        order_id: 1,
        message: 'クリーンテスト ' + Date.now(),
        _ajax_nonce: '<?php echo $nonce; ?>'
    };

    const xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            logResult(`HTTP Status: ${xhr.status}`, xhr.status === 200 ? 'success' : 'error');
            logResult(`Response Length: ${xhr.responseText.length}`, 'info');

            // 最初と最後の文字をチェック
            const first = xhr.responseText.charCodeAt(0);
            const last = xhr.responseText.charCodeAt(xhr.responseText.length - 1);
            logResult(`First char: ${first} (${String.fromCharCode(first)})`, 'info');
            logResult(`Last char: ${last} (${String.fromCharCode(last)})`, 'info');

            // BOMチェック
            if (first === 65279) {
                logResult('⚠️ BOM detected!', 'warning');
            }

            // 空白チェック
            const trimmed = xhr.responseText.trim();
            if (trimmed !== xhr.responseText) {
                logResult(`⚠️ Extra whitespace detected. Original: ${xhr.responseText.length}, Trimmed: ${trimmed.length}`, 'warning');
            }

            // JSON解析テスト
            try {
                const parsed = JSON.parse(xhr.responseText);
                logResult('✅ JSON Parse Successful!', 'success');
                logResult(`Response: ${JSON.stringify(parsed, null, 2)}`, 'success');

                if (parsed.success) {
                    logResult('🎉 Message sent successfully!', 'success');
                } else {
                    logResult(`❌ Server reported error: ${parsed.data}`, 'error');
                }
            } catch (e) {
                logResult(`❌ JSON Parse Error: ${e.message}`, 'error');
                logResult(`Raw response: ${xhr.responseText}`, 'error');

                // トリムして再試行
                try {
                    const trimmedParsed = JSON.parse(trimmed);
                    logResult('✅ Trimmed JSON Parse Successful!', 'success');
                    logResult('💡 Solution: Response needs trimming', 'warning');
                } catch (e2) {
                    logResult(`❌ Even trimmed parse failed: ${e2.message}`, 'error');
                }
            }
        }
    };

    xhr.open('POST', '/wp-admin/admin-ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    const params = Object.keys(testData)
        .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(testData[key])}`)
        .join('&');

    xhr.send(params);
});

// 実際のJavaScript処理をシミュレート
document.getElementById('test-original-js').addEventListener('click', function() {
    logResult('🎭 実際のJavaScript処理テスト開始', 'info');

    const messageInput = document.getElementById('message-input');
    const message = messageInput.value || 'JSテスト ' + Date.now();

    // ktp-js.js の処理をシミュレート
    const params = 'action=send_staff_chat_message&order_id=1&message=' +
                  encodeURIComponent(message) +
                  '&_ajax_nonce=<?php echo $nonce; ?>';

    logResult(`送信パラメータ: ${params}`, 'info');

    const xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            logResult(`📡 ktp-js.js スタイル - Status: ${xhr.status}`, 'info');

            if (xhr.status === 200) {
                try {
                    // これが実際のJavaScriptコードと同じ処理
                    var response = JSON.parse(xhr.responseText);
                    logResult('📝 パース済みレスポンス: ' + JSON.stringify(response), 'info');

                    if (response.success) {
                        logResult('✅ メッセージ送信成功 - エラーアラートなし!', 'success');
                        messageInput.value = '';
                    } else {
                        logResult('❌ メッセージ送信エラー: ' + (response.data || '不明なエラー'), 'error');
                    }
                } catch (e) {
                    logResult('❌ レスポンス解析エラー: ' + e.message, 'error');
                    logResult('🚨 これが「メッセージの送信中にエラーが発生しました」の原因', 'error');
                    logResult('生レスポンス: ' + xhr.responseText, 'error');
                }
            } else {
                logResult('❌ HTTP エラー: ' + xhr.status, 'error');
            }
        }
    };

    xhr.open('POST', '/wp-admin/admin-ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send(params);
});

// 簡単な送信テスト
document.getElementById('send-message').addEventListener('click', function() {
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value || '簡単テスト ' + Date.now();

    logResult(`📤 簡単送信テスト: "${message}"`, 'info');

    // 最もシンプルなAJAX送信
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=send_staff_chat_message&order_id=1&message=${encodeURIComponent(message)}&_ajax_nonce=<?php echo $nonce; ?>`
    })
    .then(response => response.text())
    .then(data => {
        logResult('Fetch API Response: ' + data, 'info');
        try {
            const parsed = JSON.parse(data);
            logResult('✅ Fetch API - JSON解析成功', 'success');
        } catch (e) {
            logResult('❌ Fetch API - JSON解析エラー', 'error');
        }
    })
    .catch(error => {
        logResult('❌ Fetch API Error: ' + error.message, 'error');
    });
});

logResult('🚀 最終テストツール準備完了', 'success');
logResult('各ボタンをクリックして、エラーアラートが解消されたかを確認してください', 'info');
</script>

</body>
</html>
