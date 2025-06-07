<?php
/**
 * 高精度レスポンスデバッグツール
 * 実際のAJAXレスポンスの詳細分析
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
    <title>高精度レスポンスデバッグ</title>
    <meta charset="utf-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<h1>🔬 高精度レスポンスデバッグ</h1>

<div style="background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px;">
    <h3>🎯 調査目標</h3>
    <p><strong>「メッセージの送信中にエラーが発生しました」</strong>アラートの原因を特定</p>
    <p>このアラートは <code>JSON.parse(xhr.responseText)</code> でcatchエラーが発生した際に表示される</p>
</div>

<div style="border: 1px solid #ddd; padding: 20px; margin: 20px 0;">
    <h3>🧪 デバッグ設定</h3>
    <p>Order ID: 1</p>
    <p>Nonce: <?php echo $nonce; ?></p>
    <button id="run-debug" style="padding: 10px 20px; background: #ff6b35; color: white; border: none; border-radius: 3px; cursor: pointer;">🚀 詳細デバッグ実行</button>
</div>

<div style="border: 1px solid #ddd; padding: 15px; margin: 20px 0; height: 500px; overflow-y: auto; background: #f8f9fa; font-family: 'Courier New', monospace; font-size: 12px;">
    <div id="debug-output"></div>
</div>

<script>
function debugLog(message, level = 'info') {
    const output = document.getElementById('debug-output');
    const timestamp = new Date().toLocaleTimeString();
    const colors = {
        'error': '#dc3545',
        'success': '#28a745',
        'warning': '#ffc107',
        'info': '#17a2b8',
        'debug': '#6c757d'
    };

    const color = colors[level] || colors.info;
    output.innerHTML += `<div style="margin: 2px 0; padding: 3px; border-left: 3px solid ${color};">
        <span style="color: #666;">[${timestamp}]</span> <span style="color: ${color};">[${level.toUpperCase()}]</span> ${message}
    </div>`;
    output.scrollTop = output.scrollHeight;
}

function analyzeResponseBytes(text) {
    debugLog('=== バイトレベル解析 ===', 'debug');
    debugLog(`文字列長: ${text.length}`, 'debug');

    // 最初の20文字のバイト解析
    const firstBytes = [];
    for (let i = 0; i < Math.min(text.length, 20); i++) {
        const char = text.charAt(i);
        const code = text.charCodeAt(i);
        firstBytes.push(`${i}: '${char}' (${code})`);
    }
    debugLog('最初の20文字:', 'debug');
    firstBytes.forEach(byte => debugLog(`  ${byte}`, 'debug'));

    // 最後の20文字のバイト解析
    const lastBytes = [];
    const start = Math.max(0, text.length - 20);
    for (let i = start; i < text.length; i++) {
        const char = text.charAt(i);
        const code = text.charCodeAt(i);
        lastBytes.push(`${i}: '${char}' (${code})`);
    }
    debugLog('最後の20文字:', 'debug');
    lastBytes.forEach(byte => debugLog(`  ${byte}`, 'debug'));

    // 特殊文字検出
    const bomDetected = text.charCodeAt(0) === 65279;
    const nullDetected = text.indexOf('\0') !== -1;
    const crlfDetected = text.indexOf('\r\n') !== -1;
    const extraSpaces = text !== text.trim();

    if (bomDetected) debugLog('🚨 BOM (Byte Order Mark) detected!', 'error');
    if (nullDetected) debugLog('🚨 NULL文字が検出されました!', 'error');
    if (crlfDetected) debugLog('⚠️ CRLF改行が検出されました', 'warning');
    if (extraSpaces) debugLog('⚠️ 前後に余分な空白が検出されました', 'warning');

    return {
        bom: bomDetected,
        null: nullDetected,
        crlf: crlfDetected,
        extraSpaces: extraSpaces,
        trimmed: text.trim()
    };
}

function tryVariousJsonParsing(text) {
    debugLog('=== JSON解析試行 ===', 'debug');

    const tests = [
        { name: '生データ', data: text },
        { name: 'trim()', data: text.trim() },
        { name: 'BOM除去', data: text.charCodeAt(0) === 65279 ? text.slice(1) : text },
        { name: 'BOM除去+trim', data: (text.charCodeAt(0) === 65279 ? text.slice(1) : text).trim() },
        { name: 'NULL文字除去', data: text.replace(/\0/g, '') },
        { name: '改行正規化', data: text.replace(/\r\n/g, '\n').replace(/\r/g, '\n') },
        { name: '全角スペース除去', data: text.replace(/\u3000/g, '') },
        { name: '制御文字除去', data: text.replace(/[\u0000-\u001F\u007F-\u009F]/g, '') }
    ];

    for (const test of tests) {
        try {
            const parsed = JSON.parse(test.data);
            debugLog(`✅ ${test.name}: 成功`, 'success');
            debugLog(`結果: ${JSON.stringify(parsed, null, 2)}`, 'success');
            return { success: true, method: test.name, result: parsed };
        } catch (e) {
            debugLog(`❌ ${test.name}: ${e.message}`, 'error');
        }
    }

    return { success: false };
}

document.getElementById('run-debug').addEventListener('click', function() {
    debugLog('🚀 高精度デバッグ開始', 'info');

    const testMessage = 'デバッグテスト ' + Date.now();
    debugLog(`送信メッセージ: ${testMessage}`, 'info');

    const xhr = new XMLHttpRequest();
    const params = `action=send_staff_chat_message&order_id=1&message=${encodeURIComponent(testMessage)}&_ajax_nonce=<?php echo $nonce; ?>`;

    debugLog(`送信パラメータ: ${params}`, 'debug');

    xhr.onreadystatechange = function() {
        debugLog(`ReadyState: ${xhr.readyState}`, 'debug');

        if (xhr.readyState === 4) {
            debugLog('=== 最終レスポンス受信 ===', 'info');
            debugLog(`HTTP Status: ${xhr.status}`, xhr.status === 200 ? 'success' : 'error');
            debugLog(`Status Text: ${xhr.statusText}`, 'debug');
            debugLog(`Content-Type: ${xhr.getResponseHeader('Content-Type')}`, 'debug');

            const responseText = xhr.responseText;
            debugLog(`Response Length: ${responseText.length}`, 'debug');

            if (responseText.length === 0) {
                debugLog('🚨 空のレスポンス!', 'error');
                return;
            }

            // レスポンス内容を16進数ダンプ
            const hexDump = [];
            for (let i = 0; i < Math.min(responseText.length, 200); i++) {
                hexDump.push(responseText.charCodeAt(i).toString(16).padStart(2, '0'));
            }
            debugLog(`16進数ダンプ (最初の200文字): ${hexDump.join(' ')}`, 'debug');

            // バイトレベル解析
            const analysis = analyzeResponseBytes(responseText);

            // JSON解析試行
            const parseResult = tryVariousJsonParsing(responseText);

            if (parseResult.success) {
                debugLog(`🎉 解決方法: ${parseResult.method}`, 'success');
                debugLog('この方法でJavaScriptコードを修正する必要があります', 'warning');
            } else {
                debugLog('🚨 すべてのJSON解析方法が失敗しました', 'error');
                debugLog('サーバーサイドの出力に問題があります', 'error');

                // レスポンス内容を詳細表示
                debugLog('=== 生レスポンス内容 ===', 'error');
                debugLog(responseText, 'error');
            }
        }
    };

    xhr.open('POST', '/wp-admin/admin-ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send(params);
});

debugLog('🔬 高精度デバッグツール準備完了', 'success');
debugLog('「🚀 詳細デバッグ実行」ボタンをクリックして開始してください', 'info');
</script>

</body>
</html>
