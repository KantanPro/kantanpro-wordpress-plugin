<?php
/**
 * 実際の受注詳細ページでのスタッフチャットデバッグ
 */

// WordPress環境の読み込み
require_once(dirname(__FILE__) . '/../../../wp-config.php');

// 管理者としてログイン
wp_set_current_user(1);

// テスト用の受注IDを取得または作成
global $wpdb;

// 既存の受注を検索
$existing_order = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}order_data ORDER BY id DESC LIMIT 1");

if (!$existing_order) {
    // テスト用受注を作成
    $wpdb->insert(
        $wpdb->prefix . 'order_data',
        array(
            'project_name' => 'スタッフチャットテスト案件',
            'client_name' => 'テストクライアント',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        )
    );
    $order_id = $wpdb->insert_id;
} else {
    $order_id = $existing_order->id;
}

echo "<!DOCTYPE html>\n<html><head><title>Production Staff Chat Debug</title></head><body>\n";
echo "<h1>🚀 プロダクション環境スタッフチャットデバッグ</h1>\n";

echo "<h2>📋 テスト情報</h2>\n";
echo "<p>Order ID: " . $order_id . "</p>\n";
echo "<p>User ID: " . get_current_user_id() . "</p>\n";
echo "<p>User Login: " . wp_get_current_user()->user_login . "</p>\n";

// 有効なnonceを生成
$nonce = wp_create_nonce('staff_chat_nonce');
echo "<p>Nonce: " . htmlspecialchars($nonce) . "</p>\n";

// KTPWP設定情報
echo "<h2>🔧 KTPWP設定</h2>\n";
echo "<p>KTPWP Plugin Active: " . (is_plugin_active('KTPWP/ktpwp.php') ? 'Yes' : 'No') . "</p>\n";
echo "<p>KTPWP_PLUGIN_DIR: " . (defined('KTPWP_PLUGIN_DIR') ? KTPWP_PLUGIN_DIR : 'Not defined') . "</p>\n";

// AJAX URL 確認
echo "<p>AJAX URL: " . admin_url('admin-ajax.php') . "</p>\n";

// スタッフチャットクラスの存在確認
if (class_exists('KTPWP_Staff_Chat')) {
    echo "<p style='color: green;'>✅ KTPWP_Staff_Chat class exists</p>\n";
    $staff_chat = KTPWP_Staff_Chat::get_instance();
    echo "<p>Staff Chat Instance: " . get_class($staff_chat) . "</p>\n";
} else {
    echo "<p style='color: red;'>❌ KTPWP_Staff_Chat class not found</p>\n";
}

// AJAXクラスの存在確認
if (class_exists('KTPWP_Ajax')) {
    echo "<p style='color: green;'>✅ KTPWP_Ajax class exists</p>\n";
    $ajax = KTPWP_Ajax::get_instance();
    echo "<p>Ajax Instance: " . get_class($ajax) . "</p>\n";
} else {
    echo "<p style='color: red;'>❌ KTPWP_Ajax class not found</p>\n";
}

// AJAX ハンドラー登録確認
global $wp_filter;
if (isset($wp_filter['wp_ajax_send_staff_chat_message'])) {
    echo "<p style='color: green;'>✅ wp_ajax_send_staff_chat_message handler registered</p>\n";
} else {
    echo "<p style='color: red;'>❌ wp_ajax_send_staff_chat_message handler not registered</p>\n";
}

?>

<h2>🧪 インタラクティブテスト</h2>

<div style="border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9;">
    <h3>📝 スタッフチャットフォーム</h3>
    <textarea id="message-input" placeholder="テストメッセージを入力してください..."
              style="width: 100%; height: 100px; padding: 10px; margin: 10px 0;"></textarea>
    <br>
    <button id="send-button" style="padding: 10px 20px; font-size: 16px;">📤 メッセージ送信</button>
    <button id="clear-log" style="padding: 10px 20px; font-size: 16px; margin-left: 10px;">🗑️ ログクリア</button>
</div>

<div id="debug-log" style="border: 1px solid #ccc; padding: 15px; background: #f0f0f0; height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px;"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let debugLog = document.getElementById('debug-log');
let messageInput = document.getElementById('message-input');
let sendButton = document.getElementById('send-button');

function log(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const colors = {
        info: '#333',
        success: '#006600',
        error: '#cc0000',
        warning: '#ff6600'
    };

    debugLog.innerHTML += `<div style="color: ${colors[type]}; margin: 2px 0;">[${timestamp}] ${message}</div>`;
    debugLog.scrollTop = debugLog.scrollHeight;
}

document.getElementById('clear-log').addEventListener('click', function() {
    debugLog.innerHTML = '';
    log('ログをクリアしました', 'info');
});

sendButton.addEventListener('click', function() {
    const message = messageInput.value.trim();
    if (!message) {
        log('❌ メッセージが空です', 'error');
        return;
    }

    log('🚀 AJAX送信開始...', 'info');
    log(`📝 メッセージ: "${message}"`, 'info');
    log(`🆔 Order ID: <?php echo $order_id; ?>`, 'info');
    log(`🔑 Nonce: <?php echo $nonce; ?>`, 'info');

    sendButton.disabled = true;
    sendButton.textContent = '送信中...';

    const xhr = new XMLHttpRequest();
    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';

    const formData = new FormData();
    formData.append('action', 'send_staff_chat_message');
    formData.append('order_id', '<?php echo $order_id; ?>');
    formData.append('message', message);
    formData.append('_ajax_nonce', '<?php echo $nonce; ?>');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            sendButton.disabled = false;
            sendButton.textContent = '📤 メッセージ送信';

            log(`📊 HTTP Status: ${xhr.status}`, xhr.status === 200 ? 'success' : 'error');
            log(`📏 Response Length: ${xhr.responseText.length} bytes`, 'info');
            log(`🏷️ Content-Type: ${xhr.getResponseHeader('Content-Type') || 'なし'}`, 'info');

            if (xhr.responseText.length > 0) {
                log(`📄 Raw Response: "${xhr.responseText.substring(0, 200)}${xhr.responseText.length > 200 ? '...' : ''}"`, 'info');

                // Hex dump (最初の50バイト)
                let hexDump = 'Hex: ';
                for (let i = 0; i < Math.min(50, xhr.responseText.length); i++) {
                    hexDump += xhr.responseText.charCodeAt(i).toString(16).padStart(2, '0') + ' ';
                }
                log(hexDump, 'info');

                try {
                    const response = JSON.parse(xhr.responseText);
                    log('✅ JSON解析成功', 'success');
                    log(`📊 Success: ${response.success}`, response.success ? 'success' : 'warning');
                    log(`📝 Data: ${JSON.stringify(response.data)}`, 'info');

                    if (response.success) {
                        log('🎉 メッセージ送信成功！', 'success');
                        messageInput.value = '';
                    } else {
                        log(`⚠️ サーバーエラー: ${response.data}`, 'warning');
                    }
                } catch (e) {
                    log(`❌ JSON解析エラー: ${e.message}`, 'error');

                    // 詳細な問題分析
                    const text = xhr.responseText;

                    // BOMチェック
                    if (text.charCodeAt(0) === 0xFEFF) {
                        log('🔍 BOM detected at start', 'warning');
                    }

                    // 制御文字チェック
                    let controlChars = [];
                    for (let i = 0; i < text.length; i++) {
                        const code = text.charCodeAt(i);
                        if (code < 32 && code !== 9 && code !== 10 && code !== 13) {
                            controlChars.push({pos: i, code: code});
                        }
                    }
                    if (controlChars.length > 0) {
                        log(`🔍 制御文字検出: ${controlChars.length}個`, 'warning');
                        controlChars.slice(0, 5).forEach(char => {
                            log(`   位置 ${char.pos}: コード ${char.code}`, 'warning');
                        });
                    }

                    // JSON境界検出
                    const jsonStart = text.indexOf('{');
                    const jsonEnd = text.lastIndexOf('}');
                    if (jsonStart >= 0 && jsonEnd >= 0) {
                        if (jsonStart > 0) {
                            log(`🔍 JSON前のデータ: "${text.substring(0, jsonStart)}"`, 'warning');
                        }
                        if (jsonEnd < text.length - 1) {
                            log(`🔍 JSON後のデータ: "${text.substring(jsonEnd + 1)}"`, 'warning');
                        }

                        // クリーンJSON試行
                        const cleanJson = text.substring(jsonStart, jsonEnd + 1);
                        try {
                            const cleanResponse = JSON.parse(cleanJson);
                            log('✅ クリーンJSON解析成功', 'success');
                            log(`📊 Clean Success: ${cleanResponse.success}`, 'info');
                            log(`📝 Clean Data: ${JSON.stringify(cleanResponse.data)}`, 'info');
                        } catch (cleanError) {
                            log(`❌ クリーンJSON解析も失敗: ${cleanError.message}`, 'error');
                        }
                    } else {
                        log('🔍 JSONの開始/終了ブラケットが見つからない', 'error');
                    }
                }
            } else {
                log('❌ レスポンスが空です', 'error');
            }

            log('─'.repeat(50), 'info');
        }
    };

    xhr.open('POST', ajaxUrl, true);
    xhr.send(formData);
});

// 初期ログ
log('🔧 プロダクション環境スタッフチャットデバッグ開始', 'info');
log(`🌐 AJAX URL: <?php echo admin_url('admin-ajax.php'); ?>`, 'info');
log(`👤 User: <?php echo wp_get_current_user()->user_login; ?> (ID: <?php echo get_current_user_id(); ?>)`, 'info');
log('📝 メッセージを入力して送信ボタンを押してください', 'info');
</script>

</body></html>
<?php
?>
