<?php
/**
 * AJAX エラーデバッグ用テストスクリプト
 */

// WordPress環境の読み込み
require_once('../../../wp-load.php');

// セキュリティチェック
if (!current_user_can('edit_posts')) {
    wp_die('権限がありません。管理者としてログインしてください。');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>AJAX エラーデバッグ</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f9f9f9; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .info { background: #cce7ff; border: 1px solid #99d6ff; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        textarea { width: 100%; height: 100px; }
        .log-area { background: #f8f9fa; padding: 15px; border-radius: 3px; min-height: 100px; font-family: monospace; font-size: 12px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>🔧 AJAX エラーデバッグ</h1>
    
    <div class="test-section info">
        <h2>1. 基本設定確認</h2>
        <?php
        echo '<p>✅ WordPress読み込み完了</p>';
        echo '<p>✅ 現在のユーザー: ' . wp_get_current_user()->display_name . '</p>';
        echo '<p>✅ AJAX URL: ' . admin_url('admin-ajax.php') . '</p>';
        
        // KTPWP_Ajax クラスが存在するかチェック
        if (class_exists('KTPWP_Ajax')) {
            echo '<p>✅ KTPWP_Ajax クラスが存在します</p>';
            $ajax = KTPWP_Ajax::get_instance();
            echo '<p>✅ KTPWP_Ajax インスタンス取得成功</p>';
        } else {
            echo '<p>❌ KTPWP_Ajax クラスが見つかりません</p>';
        }
        
        // KTPWP_Staff_Chat クラスが存在するかチェック
        if (class_exists('KTPWP_Staff_Chat')) {
            echo '<p>✅ KTPWP_Staff_Chat クラスが存在します</p>';
        } else {
            echo '<p>❌ KTPWP_Staff_Chat クラスが見つかりません</p>';
        }
        ?>
    </div>
    
    <div class="test-section info">
        <h2>2. Nonce生成テスト</h2>
        <?php
        $staff_chat_nonce = wp_create_nonce('ktpwp_staff_chat_nonce');
        echo '<p>✅ スタッフチャット用Nonce: <code>' . $staff_chat_nonce . '</code></p>';
        ?>
    </div>
    
    <div class="test-section info">
        <h2>3. AJAXアクション登録確認</h2>
        <?php
        $ajax_actions = array('send_staff_chat_message', 'get_latest_staff_chat');
        foreach ($ajax_actions as $action) {
            $logged_in = has_action("wp_ajax_{$action}");
            $not_logged_in = has_action("wp_ajax_nopriv_{$action}");
            
            if ($logged_in) {
                echo "<p>✅ wp_ajax_{$action} が登録されています</p>";
            } else {
                echo "<p>❌ wp_ajax_{$action} が登録されていません</p>";
            }
            
            if ($not_logged_in) {
                echo "<p>ℹ️ wp_ajax_nopriv_{$action} が登録されています</p>";
            } else {
                echo "<p>ℹ️ wp_ajax_nopriv_{$action} は登録されていません</p>";
            }
        }
        ?>
    </div>

    <div class="test-section info">
        <h2>4. テスト用注文</h2>
        <?php
        global $wpdb;
        $order_table = $wpdb->prefix . 'ktp_order';
        $test_order = $wpdb->get_row("SELECT id, project_name FROM {$order_table} LIMIT 1");
        
        if ($test_order) {
            echo '<p>✅ テスト用注文: ID ' . $test_order->id . ' - ' . esc_html($test_order->project_name) . '</p>';
        } else {
            echo '<p>❌ テスト用注文が見つかりません</p>';
        }
        ?>
    </div>
    
    <?php if ($test_order): ?>
    <div class="test-section">
        <h2>5. リアルタイムAJAXテスト</h2>
        <div>
            <label for="test_message">テストメッセージ:</label><br>
            <textarea id="test_message" placeholder="ここにテストメッセージを入力してください...">テストメッセージ - <?php echo current_time('Y-m-d H:i:s'); ?></textarea>
        </div>
        <br>
        <button type="button" onclick="sendTestMessage()">📤 メッセージ送信テスト</button>
        <button type="button" onclick="clearLog()">🗑️ ログクリア</button>
        
        <h3>ログ出力:</h3>
        <div id="log-area" class="log-area">テスト準備完了...\n</div>
    </div>
    <?php endif; ?>

    <div class="test-section info">
        <h2>5. データベーステーブル確認</h2>
        <?php
        global $wpdb;
        $staff_chat_table = $wpdb->prefix . 'ktp_order_staff_chat';
        
        // テーブルの存在確認
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$staff_chat_table}'");
        
        if ($table_exists) {
            echo '<p>✅ スタッフチャットテーブル存在: ' . $staff_chat_table . '</p>';
            
            // テーブル構造確認
            $columns = $wpdb->get_results("DESCRIBE {$staff_chat_table}");
            echo '<p>📋 テーブル構造:</p><ul>';
            foreach ($columns as $column) {
                echo '<li>' . $column->Field . ' (' . $column->Type . ')</li>';
            }
            echo '</ul>';
            
            // 既存のメッセージ数を確認
            $message_count = $wpdb->get_var("SELECT COUNT(*) FROM {$staff_chat_table}");
            echo '<p>📊 既存メッセージ数: ' . $message_count . '</p>';
        } else {
            echo '<p>❌ スタッフチャットテーブルが見つかりません: ' . $staff_chat_table . '</p>';
        }
        ?>
    </div>
    
    <div class="test-section info">
        <h2>6. Nonce検証テスト</h2>
        <?php
        // 実際のAJAXクラスのnonce名を確認
        if (class_exists('KTPWP_Ajax')) {
            $ajax_instance = KTPWP_Ajax::get_instance();
            $reflection = new ReflectionClass($ajax_instance);
            
            if ($reflection->hasProperty('nonce_names')) {
                $property = $reflection->getProperty('nonce_names');
                $property->setAccessible(true);
                $nonce_names = $property->getValue($ajax_instance);
                
                echo '<p>✅ AJAX クラスのnonce設定:</p><ul>';
                foreach ($nonce_names as $key => $value) {
                    echo '<li>' . $key . ': ' . $value . '</li>';
                }
                echo '</ul>';
                
                // staff_chat用のnonceを確認
                if (isset($nonce_names['staff_chat'])) {
                    $correct_nonce = wp_create_nonce($nonce_names['staff_chat']);
                    echo '<p>✅ 正しいスタッフチャット用Nonce (' . $nonce_names['staff_chat'] . '): <code>' . $correct_nonce . '</code></p>';
                    
                    // 現在生成しているnonceと比較
                    if ($staff_chat_nonce !== $correct_nonce) {
                        echo '<p>❌ Nonceが一致しません！</p>';
                        echo '<p>🔧 修正が必要です</p>';
                    } else {
                        echo '<p>✅ Nonceが一致しています</p>';
                    }
                } else {
                    echo '<p>❌ staff_chat用のnonce設定が見つかりません</p>';
                }
            } else {
                echo '<p>❌ nonce_names プロパティが見つかりません</p>';
            }
        }
        ?>
    </div>

    <script>
        // グローバル変数
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const orderId = <?php echo $test_order ? $test_order->id : 'null'; ?>;
        // 正しいnonce名を使用
        <?php
        $correct_nonce_action = 'ktpwp_staff_chat_nonce'; // デフォルト
        if (class_exists('KTPWP_Ajax')) {
            $ajax_instance = KTPWP_Ajax::get_instance();
            $reflection = new ReflectionClass($ajax_instance);
            if ($reflection->hasProperty('nonce_names')) {
                $property = $reflection->getProperty('nonce_names');
                $property->setAccessible(true);
                $nonce_names = $property->getValue($ajax_instance);
                if (isset($nonce_names['staff_chat'])) {
                    $correct_nonce_action = $nonce_names['staff_chat'];
                }
            }
        }
        ?>
        const staffChatNonce = '<?php echo wp_create_nonce($correct_nonce_action); ?>';
        const nonceAction = '<?php echo $correct_nonce_action; ?>';
        
        // ログ出力関数
        function addLog(message, type = 'info') {
            const logArea = document.getElementById('log-area');
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'red' : type === 'success' ? 'green' : 'black';
            logArea.innerHTML += `<span style="color: ${color};">[${timestamp}] ${message}</span>\n`;
            logArea.scrollTop = logArea.scrollHeight;
        }

        // ログクリア
        function clearLog() {
            document.getElementById('log-area').innerHTML = 'ログをクリアしました...\n';
        }

        // メッセージ送信テスト
        function sendTestMessage() {
            const messageInput = document.getElementById('test_message');
            const message = messageInput.value.trim();
            
            if (!message) {
                addLog('❌ メッセージが空です', 'error');
                return;
            }
            
            if (!orderId) {
                addLog('❌ 注文IDが設定されていません', 'error');
                return;
            }
            
            addLog('📤 メッセージ送信開始...');
            addLog(`ℹ️ 送信データ: order_id=${orderId}, message="${message.substring(0, 50)}..."`);
            addLog(`ℹ️ Nonce: ${staffChatNonce}`);
            addLog(`ℹ️ Nonce Action: ${nonceAction}`);
            addLog(`ℹ️ AJAX URL: ${ajaxUrl}`);
            
            const formData = new FormData();
            formData.append('action', 'send_staff_chat_message');
            formData.append('order_id', orderId);
            formData.append('message', message);
            formData.append('_ajax_nonce', staffChatNonce);
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                addLog(`ℹ️ HTTPステータス: ${response.status}`);
                return response.text();
            })
            .then(text => {
                addLog(`ℹ️ 生レスポンス: ${text.substring(0, 200)}...`);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        addLog('✅ メッセージ送信成功: ' + JSON.stringify(data), 'success');
                        messageInput.value = '';
                    } else {
                        addLog('❌ メッセージ送信失敗: ' + JSON.stringify(data), 'error');
                    }
                } catch (e) {
                    addLog('❌ JSON解析エラー: ' + e.message, 'error');
                    addLog('❌ 受信データがJSONではありません: ' + text.substring(0, 500), 'error');
                }
            })
            .catch(error => {
                addLog('❌ ネットワークエラー: ' + error.message, 'error');
            });
        }

        // 初期化
        document.addEventListener('DOMContentLoaded', function() {
            addLog('✅ テストページ初期化完了');
            if (orderId) {
                addLog('✅ 注文ID: ' + orderId);
            }
            if (staffChatNonce) {
                addLog('✅ Nonce生成済み: ' + staffChatNonce);
                addLog('✅ Nonce Action: ' + nonceAction);
            }
        });
    </script>
</body>
</html>

<?php
// admin_url の動作確認用ログ
error_log('Admin AJAX URL: ' . admin_url('admin-ajax.php'));
?>
