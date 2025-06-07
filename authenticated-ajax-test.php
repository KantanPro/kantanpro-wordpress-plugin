<?php
/**
 * 認証済み状態でのAJAXテスト
 */

// WordPress環境の読み込み
require_once(dirname(__FILE__) . '/../../../wp-config.php');

// 管理者としてログイン
$admin_user = get_user_by('login', 'admin');
if (!$admin_user) {
    $admin_user = get_user_by('ID', 1);
}

if ($admin_user) {
    wp_set_current_user($admin_user->ID);
    wp_set_auth_cookie($admin_user->ID);
}

echo "<!DOCTYPE html>\n<html><head><title>Authenticated AJAX Test</title></head><body>\n";
echo "<h1>🔐 認証済みAJAXテスト</h1>\n";

echo "<h2>🔑 認証情報</h2>\n";
echo "<p>Current User ID: " . get_current_user_id() . "</p>\n";
echo "<p>User Login: " . (is_user_logged_in() ? wp_get_current_user()->user_login : 'Not logged in') . "</p>\n";
echo "<p>Can Read: " . (current_user_can('read') ? 'Yes' : 'No') . "</p>\n";

// 有効なnonceを生成
$nonce = wp_create_nonce('staff_chat_nonce');
echo "<p>Generated Nonce: " . htmlspecialchars($nonce) . "</p>\n";

// 実際のAJAXエンドポイントをJavaScriptでテスト
?>

<h2>🧪 JavaScriptテスト</h2>
<button id="test-button">メッセージ送信テスト</button>
<div id="test-results" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.getElementById('test-button').addEventListener('click', function() {
    const resultsDiv = document.getElementById('test-results');
    resultsDiv.innerHTML = '<p>🔄 テスト実行中...</p>';

    const xhr = new XMLHttpRequest();
    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';

    const formData = new FormData();
    formData.append('action', 'send_staff_chat_message');
    formData.append('order_id', '1');
    formData.append('message', '認証済みテストメッセージ: ' + new Date().toLocaleTimeString());
    formData.append('_ajax_nonce', '<?php echo $nonce; ?>');

    console.log('=== AJAX送信開始 ===');
    console.log('URL:', ajaxUrl);
    console.log('User ID:', <?php echo get_current_user_id(); ?>);
    console.log('Nonce:', '<?php echo $nonce; ?>');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            console.log('=== AJAX完了 ===');
            console.log('Status:', xhr.status);
            console.log('Response Text:', xhr.responseText);
            console.log('Response Length:', xhr.responseText.length);
            console.log('Content-Type:', xhr.getResponseHeader('Content-Type'));

            let html = '<h3>📊 テスト結果</h3>';
            html += '<p><strong>Status:</strong> ' + xhr.status + '</p>';
            html += '<p><strong>Content-Type:</strong> ' + (xhr.getResponseHeader('Content-Type') || 'なし') + '</p>';
            html += '<p><strong>Response Length:</strong> ' + xhr.responseText.length + ' bytes</p>';

            html += '<h4>📄 Raw Response</h4>';
            html += '<textarea style="width: 100%; height: 100px; font-family: monospace;">' +
                    xhr.responseText + '</textarea>';

            html += '<h4>🔍 Hex Dump (最初の200バイト)</h4>';
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
                html += '<pre style="background: #f0fff0; padding: 10px;">' +
                        JSON.stringify(parsed, null, 2) + '</pre>';

                if (parsed.success) {
                    html += '<p style="color: blue; font-weight: bold;">🎉 メッセージ送信成功！</p>';
                } else {
                    html += '<p style="color: orange; font-weight: bold;">⚠️ サーバーエラー: ' +
                            (parsed.data || '不明') + '</p>';
                }
            } catch (e) {
                html += '<p style="color: red;">❌ JSON解析エラー: ' + e.message + '</p>';

                // 問題の文字を探す
                html += '<h5>問題の文字検出</h5>';
                let issues = [];

                // BOM検出
                if (xhr.responseText.charCodeAt(0) === 0xFEFF) {
                    issues.push('BOM detected at start');
                }

                // 制御文字検出
                for (let i = 0; i < xhr.responseText.length; i++) {
                    const code = xhr.responseText.charCodeAt(i);
                    if (code < 32 && code !== 9 && code !== 10 && code !== 13) {
                        issues.push(`Control char at pos ${i}: ${code}`);
                    }
                }

                // JSON境界検出
                const jsonStart = xhr.responseText.indexOf('{');
                const jsonEnd = xhr.responseText.lastIndexOf('}');

                if (jsonStart > 0) {
                    issues.push(`Non-JSON prefix: "${xhr.responseText.substring(0, jsonStart)}"`);
                }

                if (jsonEnd < xhr.responseText.length - 1) {
                    issues.push(`Non-JSON suffix: "${xhr.responseText.substring(jsonEnd + 1)}"`);
                }

                if (issues.length > 0) {
                    html += '<ul>';
                    issues.forEach(issue => {
                        html += '<li style="color: red;">' + issue + '</li>';
                    });
                    html += '</ul>';
                } else {
                    html += '<p>具体的な問題は検出されませんでした</p>';
                }

                // クリーンアップ試行
                if (jsonStart >= 0 && jsonEnd >= 0) {
                    const cleanJson = xhr.responseText.substring(jsonStart, jsonEnd + 1);
                    html += '<h5>クリーンアップ試行</h5>';
                    html += '<p>Extracted JSON: <code>' + cleanJson + '</code></p>';
                    try {
                        const cleanParsed = JSON.parse(cleanJson);
                        html += '<p style="color: green;">✅ クリーンアップ後の解析成功</p>';
                        html += '<pre>' + JSON.stringify(cleanParsed, null, 2) + '</pre>';
                    } catch (cleanError) {
                        html += '<p style="color: red;">❌ クリーンアップ後も解析失敗: ' + cleanError.message + '</p>';
                    }
                }
            }

            resultsDiv.innerHTML = html;
        }
    };

    xhr.open('POST', ajaxUrl, true);
    xhr.send(formData);
});
</script>

</body></html>
<?php
?>
