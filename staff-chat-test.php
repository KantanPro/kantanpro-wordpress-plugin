<?php
/**
 * スタッフチャットAJAX機能テストスクリプト
 * 
 * データベース接続が復旧した際に使用するテスト用ファイル
 * ブラウザから直接アクセスして動作確認を行う
 */

// WordPressの読み込み
require_once('wp-load.php');

// セキュリティチェック
if (!current_user_can('read')) {
    wp_die('権限がありません');
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スタッフチャットAJAXテスト</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .result { margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 4px; }
        .error { background: #ffebee; border-left: 4px solid #f44336; }
        .success { background: #e8f5e8; border-left: 4px solid #4caf50; }
        button { padding: 8px 16px; margin: 5px; }
        textarea { width: 300px; height: 60px; margin: 5px; }
        input[type="number"] { width: 100px; margin: 5px; }
    </style>
</head>
<body>
    <h1>スタッフチャットAJAX機能テスト</h1>
    
    <!-- データベース接続テスト -->
    <div class="test-section">
        <h2>1. データベース接続テスト</h2>
        <button onclick="testDatabaseConnection()">データベース接続テスト</button>
        <div id="db-result" class="result"></div>
    </div>
    
    <!-- スタッフチャットクラステスト -->
    <div class="test-section">
        <h2>2. スタッフチャットクラステスト</h2>
        <button onclick="testStaffChatClass()">クラス読み込みテスト</button>
        <div id="class-result" class="result"></div>
    </div>
    
    <!-- AJAX エンドポイントテスト -->
    <div class="test-section">
        <h2>3. AJAX エンドポイントテスト</h2>
        <div>
            <label>注文ID: <input type="number" id="test-order-id" value="1" placeholder="Order ID"></label><br>
            <label>メッセージ: <textarea id="test-message" placeholder="テストメッセージ"></textarea></label><br>
            <button onclick="testSendMessage()">メッセージ送信テスト</button>
            <button onclick="testGetMessages()">メッセージ取得テスト</button>
        </div>
        <div id="ajax-result" class="result"></div>
    </div>
    
    <!-- 実際のAJAX動作テスト -->
    <div class="test-section">
        <h2>4. リアルタイムチャットテスト</h2>
        <div id="mock-chat-container">
            <div id="mock-messages" style="height: 200px; border: 1px solid #ddd; overflow-y: auto; padding: 10px; margin: 10px 0;"></div>
            <input type="text" id="mock-input" placeholder="メッセージを入力..." style="width: 250px;">
            <button onclick="sendMockMessage()">送信</button>
        </div>
    </div>

    <script>
        // WordPressのAJAX URLとnonceを設定
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var staffChatNonce = '<?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?>';
        
        // データベース接続テスト
        function testDatabaseConnection() {
            var resultDiv = document.getElementById('db-result');
            resultDiv.innerHTML = 'テスト中...';
            
            // PHPで直接データベース接続をテスト
            fetch('?action=test_db_connection', {
                method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
                resultDiv.innerHTML = data;
                resultDiv.className = 'result success';
            })
            .catch(error => {
                resultDiv.innerHTML = 'エラー: ' + error.message;
                resultDiv.className = 'result error';
            });
        }
        
        // スタッフチャットクラステスト
        function testStaffChatClass() {
            var resultDiv = document.getElementById('class-result');
            resultDiv.innerHTML = 'テスト中...';
            
            <?php
            try {
                require_once('wp-content/plugins/KTPWP/includes/class-ktpwp-staff-chat.php');
                if (class_exists('KTPWP_Staff_Chat')) {
                    echo "resultDiv.innerHTML = 'KTPWP_Staff_Chat クラスが正常に読み込まれました';";
                    echo "resultDiv.className = 'result success';";
                } else {
                    echo "resultDiv.innerHTML = 'KTPWP_Staff_Chat クラスが見つかりません';";
                    echo "resultDiv.className = 'result error';";
                }
            } catch (Exception $e) {
                echo "resultDiv.innerHTML = 'エラー: " . addslashes($e->getMessage()) . "';";
                echo "resultDiv.className = 'result error';";
            }
            ?>
        }
        
        // メッセージ送信テスト
        function testSendMessage() {
            var orderId = document.getElementById('test-order-id').value;
            var message = document.getElementById('test-message').value;
            var resultDiv = document.getElementById('ajax-result');
            
            if (!message.trim()) {
                resultDiv.innerHTML = 'メッセージを入力してください';
                resultDiv.className = 'result error';
                return;
            }
            
            resultDiv.innerHTML = 'メッセージ送信中...';
            
            var formData = new FormData();
            formData.append('action', 'send_staff_chat_message');
            formData.append('order_id', orderId);
            formData.append('message', message);
            formData.append('_ajax_nonce', staffChatNonce);
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '✓ メッセージ送信成功: ' + JSON.stringify(data.data);
                    resultDiv.className = 'result success';
                    document.getElementById('test-message').value = '';
                } else {
                    resultDiv.innerHTML = '✗ メッセージ送信失敗: ' + (data.data || 'Unknown error');
                    resultDiv.className = 'result error';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '✗ ネットワークエラー: ' + error.message;
                resultDiv.className = 'result error';
            });
        }
        
        // メッセージ取得テスト
        function testGetMessages() {
            var orderId = document.getElementById('test-order-id').value;
            var resultDiv = document.getElementById('ajax-result');
            
            resultDiv.innerHTML = 'メッセージ取得中...';
            
            var formData = new FormData();
            formData.append('action', 'get_latest_staff_chat');
            formData.append('order_id', orderId);
            formData.append('_ajax_nonce', staffChatNonce);
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '✓ メッセージ取得成功 (' + data.data.length + '件): <pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                    resultDiv.className = 'result success';
                } else {
                    resultDiv.innerHTML = '✗ メッセージ取得失敗: ' + (data.data || 'Unknown error');
                    resultDiv.className = 'result error';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '✗ ネットワークエラー: ' + error.message;
                resultDiv.className = 'result error';
            });
        }
        
        // モックチャット送信
        function sendMockMessage() {
            var input = document.getElementById('mock-input');
            var message = input.value.trim();
            
            if (!message) return;
            
            var mockMessages = document.getElementById('mock-messages');
            var messageDiv = document.createElement('div');
            messageDiv.innerHTML = '<strong><?php echo wp_get_current_user()->display_name; ?>:</strong> ' + escapeHtml(message) + ' <small>(' + new Date().toLocaleTimeString() + ')</small>';
            messageDiv.style.marginBottom = '5px';
            
            mockMessages.appendChild(messageDiv);
            mockMessages.scrollTop = mockMessages.scrollHeight;
            
            input.value = '';
        }
        
        // HTMLエスケープ
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Enterキーでの送信
        document.getElementById('mock-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMockMessage();
            }
        });
    </script>
</body>
</html>

<?php
// データベース接続テスト用のハンドラー
if (isset($_GET['action']) && $_GET['action'] === 'test_db_connection') {
    global $wpdb;
    try {
        $result = $wpdb->get_results("SHOW TABLES LIKE 'wp_ktp_order%'", ARRAY_N);
        echo "✓ データベース接続成功<br>";
        echo "KTPテーブル数: " . count($result) . "<br>";
        if (!empty($result)) {
            echo "見つかったテーブル:<br>";
            foreach ($result as $table) {
                echo "- " . $table[0] . "<br>";
            }
        }
    } catch (Exception $e) {
        echo "✗ データベース接続エラー: " . $e->getMessage();
    }
    exit;
}
?>
