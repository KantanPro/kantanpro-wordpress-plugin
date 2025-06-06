<?php
/**
 * スタッフチャット環境テストページ
 * 新しい開発環境での動作確認用
 */

// セキュリティ: 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

// WordPressの設定ファイルを読み込み
require_once dirname(__FILE__) . '/../../../wp-config.php';
require_once ABSPATH . 'wp-settings.php';

// 管理者権限チェック
if (!current_user_can('manage_options')) {
    wp_die('このページにアクセスする権限がありません。');
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スタッフチャット環境テスト - kantanpro.local</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #23282d;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        .status-ok {
            color: #00a32a;
            font-weight: bold;
        }
        .status-error {
            color: #d63638;
            font-weight: bold;
        }
        .status-info {
            color: #0073aa;
            font-weight: bold;
        }
        .test-button {
            background: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .test-button:hover {
            background: #005a87;
        }
        .test-output {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            border-left: 4px solid #0073aa;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .chat-test-area {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .message-form {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .message-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 スタッフチャット環境テスト</h1>
        <p><strong>新しい開発環境:</strong> <span class="status-info">kantanpro.local</span></p>
        <p><strong>テスト実行時刻:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

        <!-- 環境情報テスト -->
        <div class="test-section">
            <h3>📋 環境情報確認</h3>
            
            <p><strong>サイトURL:</strong> 
                <span class="<?php echo (strpos(get_site_url(), 'kantanpro.local') !== false) ? 'status-ok' : 'status-error'; ?>">
                    <?php echo esc_html(get_site_url()); ?>
                </span>
            </p>
            
            <p><strong>現在のURL:</strong> 
                <span class="status-info"><?php echo esc_html($_SERVER['HTTP_HOST']); ?></span>
            </p>
            
            <p><strong>WordPress DB_HOST:</strong> 
                <span class="status-info"><?php echo defined('DB_HOST') ? esc_html(DB_HOST) : 'undefined'; ?></span>
            </p>
            
            <p><strong>Ajax URL:</strong> 
                <span class="status-info"><?php echo esc_html(admin_url('admin-ajax.php')); ?></span>
            </p>
        </div>

        <!-- プラグインクラス確認 -->
        <div class="test-section">
            <h3>🔧 プラグインクラス確認</h3>
            
            <p><strong>KTPWP_Ajax クラス:</strong> 
                <span class="<?php echo class_exists('KTPWP_Ajax') ? 'status-ok' : 'status-error'; ?>">
                    <?php echo class_exists('KTPWP_Ajax') ? '✓ 存在' : '✗ 存在しない'; ?>
                </span>
            </p>
            
            <p><strong>KTPWP_Staff_Chat クラス:</strong> 
                <span class="<?php echo class_exists('KTPWP_Staff_Chat') ? 'status-ok' : 'status-error'; ?>">
                    <?php echo class_exists('KTPWP_Staff_Chat') ? '✓ 存在' : '✗ 存在しない'; ?>
                </span>
            </p>
            
            <?php
            // KTPWPプラグインのメインクラスを確認
            if (class_exists('KTPWP')) {
                echo '<p><strong>KTPWP メインクラス:</strong> <span class="status-ok">✓ 存在</span></p>';
            } else {
                echo '<p><strong>KTPWP メインクラス:</strong> <span class="status-error">✗ 存在しない</span></p>';
            }
            ?>
        </div>

        <!-- Ajax Nonce確認 -->
        <div class="test-section">
            <h3>🔐 Ajax Nonce確認</h3>
            
            <?php
            // KTPWP_Ajaxインスタンスを取得してnonce設定を確認
            if (class_exists('KTPWP_Ajax')) {
                $ajax_instance = KTPWP_Ajax::get_instance();
                
                // nonceを生成
                $staff_chat_nonce = wp_create_nonce('ktpwp_staff_chat_nonce');
                echo '<p><strong>スタッフチャット Nonce:</strong> <span class="status-ok">✓ 生成済み</span></p>';
                echo '<div class="test-output">Nonce: ' . esc_html($staff_chat_nonce) . '</div>';
                
                // Ajax URLの確認
                $ajax_url = admin_url('admin-ajax.php');
                echo '<p><strong>Ajax URL:</strong> <span class="status-ok">✓ アクセス可能</span></p>';
                echo '<div class="test-output">URL: ' . esc_html($ajax_url) . '</div>';
            } else {
                echo '<p><strong>Ajax設定:</strong> <span class="status-error">✗ KTPWP_Ajaxクラスが見つかりません</span></p>';
            }
            ?>
        </div>

        <!-- データベーステスト -->
        <div class="test-section">
            <h3>🗄️ データベーステスト</h3>
            
            <?php
            global $wpdb;
            
            // スタッフチャットテーブルの存在確認
            $table_name = $wpdb->prefix . 'ktpwp_staff_chat';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            echo '<p><strong>スタッフチャットテーブル:</strong> ';
            if ($table_exists) {
                echo '<span class="status-ok">✓ 存在</span></p>';
                
                // レコード数確認
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                echo '<div class="test-output">テーブル名: ' . esc_html($table_name) . '<br>';
                echo 'レコード数: ' . esc_html($count) . '</div>';
            } else {
                echo '<span class="status-error">✗ 存在しない</span></p>';
                echo '<div class="test-output">テーブルが見つかりません: ' . esc_html($table_name) . '</div>';
            }
            ?>
        </div>

        <!-- Ajax機能テスト -->
        <div class="test-section">
            <h3>🚀 Ajax機能テスト</h3>
            
            <div class="chat-test-area">
                <p><strong>テスト用受注ID:</strong> <input type="number" id="test-order-id" value="1" min="1"></p>
                
                <div class="message-form">
                    <input type="text" id="test-message" class="message-input" placeholder="テストメッセージを入力してください..." value="環境テスト メッセージ - kantanpro.local">
                    <button type="button" class="test-button" onclick="testSendMessage()">📤 送信テスト</button>
                    <button type="button" class="test-button" onclick="testGetMessages()">📥 取得テスト</button>
                </div>
                
                <div id="test-output" class="test-output" style="display: none;"></div>
            </div>
        </div>
    </div>

    <script>
        // Ajax URL設定
        const ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        const staffChatNonce = '<?php echo esc_js(wp_create_nonce('ktpwp_staff_chat_nonce')); ?>';
        
        function showOutput(message, isError = false) {
            const output = document.getElementById('test-output');
            output.style.display = 'block';
            output.style.borderLeftColor = isError ? '#d63638' : '#00a32a';
            output.innerHTML = '<strong>' + new Date().toLocaleTimeString() + '</strong><br>' + message;
        }
        
        function testSendMessage() {
            const orderId = document.getElementById('test-order-id').value;
            const message = document.getElementById('test-message').value;
            
            if (!orderId || !message) {
                showOutput('受注IDとメッセージを入力してください。', true);
                return;
            }
            
            showOutput('メッセージ送信中...');
            
            const xhr = new XMLHttpRequest();
            const params = new URLSearchParams({
                action: 'send_staff_chat_message',
                order_id: orderId,
                message: message,
                _ajax_nonce: staffChatNonce
            });
            
            xhr.open('POST', ajaxUrl, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showOutput('✅ メッセージ送信成功!<br>レスポンス: ' + JSON.stringify(response, null, 2));
                            } else {
                                showOutput('❌ メッセージ送信失敗<br>エラー: ' + (response.data || 'Unknown error'), true);
                            }
                        } catch (e) {
                            showOutput('❌ レスポンス解析エラー<br>生レスポンス: ' + xhr.responseText, true);
                        }
                    } else {
                        showOutput('❌ HTTP エラー: ' + xhr.status + '<br>レスポンス: ' + xhr.responseText, true);
                    }
                }
            };
            
            xhr.send(params);
        }
        
        function testGetMessages() {
            const orderId = document.getElementById('test-order-id').value;
            
            if (!orderId) {
                showOutput('受注IDを入力してください。', true);
                return;
            }
            
            showOutput('メッセージ取得中...');
            
            const xhr = new XMLHttpRequest();
            const params = new URLSearchParams({
                action: 'get_latest_staff_chat',
                order_id: orderId,
                _ajax_nonce: staffChatNonce
            });
            
            xhr.open('POST', ajaxUrl, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                showOutput('✅ メッセージ取得成功!<br>メッセージ数: ' + (response.data ? response.data.length : 0) + '<br>レスポンス: ' + JSON.stringify(response, null, 2));
                            } else {
                                showOutput('❌ メッセージ取得失敗<br>エラー: ' + (response.data || 'Unknown error'), true);
                            }
                        } catch (e) {
                            showOutput('❌ レスポンス解析エラー<br>生レスポンス: ' + xhr.responseText, true);
                        }
                    } else {
                        showOutput('❌ HTTP エラー: ' + xhr.status + '<br>レスポンス: ' + xhr.responseText, true);
                    }
                }
            };
            
            xhr.send(params);
        }
        
        // ページ読み込み時の自動チェック
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🧪 スタッフチャット環境テストページが読み込まれました');
            console.log('Ajax URL:', ajaxUrl);
            console.log('Staff Chat Nonce:', staffChatNonce);
        });
    </script>
</body>
</html>
