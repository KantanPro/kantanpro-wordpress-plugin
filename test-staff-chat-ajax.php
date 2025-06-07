<?php
/**
 * Staff Chat AJAX Test Page
 * Tests the fixed staff chat functionality
 */

// WordPress環境の読み込み
require_once(dirname(__FILE__) . '/../../../wp-config.php');

// WordPress初期化
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// ユーザーログイン状態を確認（テスト用に管理者としてログイン）
if (!is_user_logged_in()) {
    wp_set_current_user(1); // 管理者ユーザー（ID: 1）としてログイン
}

// KTPWPプラグインが有効かチェック
if (!class_exists('KTPWP_Staff_Chat')) {
    exit('KTPWP plugin is not active.');
}

// テスト用の注文データを取得
$orders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ktp_orders LIMIT 3");

// nonceを生成
$nonce = wp_create_nonce('ktp_staff_chat_nonce');

// 既存のチャットメッセージを取得
$chat_messages = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ktp_order_staff_chat
         WHERE order_id = %d
         ORDER BY created_at DESC LIMIT 5",
        1
    )
);

?><!DOCTYPE html>
<html>
<head>
    <title>KTPWP Staff Chat Test</title>
    <meta charset="utf-8">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php
echo '<h1>KTPWP Staff Chat Test</h1>';

echo '<h2>Available Orders:</h2>';
foreach ($orders as $order) {
    echo '<p>ID: ' . $order->id . ' - Customer: ' . esc_html($order->customer_name) . ' - Project: ' . esc_html($order->project_name) . '</p>';
}

echo '<h2>Test Configuration:</h2>';
echo '<p>Order ID: 1</p>';
echo '<p>Generated Nonce: ' . $nonce . '</p>';

echo '<h2>Existing Chat Messages:</h2>';
if ($chat_messages) {
    foreach ($chat_messages as $message) {
        echo '<p><strong>' . esc_html($message->created_at) . '</strong>: ' . esc_html($message->message) . '</p>';
    }
} else {
    echo '<p>No chat messages found.</p>';
}
?>

<script>
// 有効なnonceでAJAXテストを実行
const testData = {
    order_id: 1,
    nonce: '<?php echo $nonce; ?>'
};

console.log('Test configuration:', testData);

// スタッフチャットメッセージ送信テスト
function testSendMessage() {
    const message = 'Test message sent at ' + new Date().toLocaleTimeString();

    const ajaxData = {
        action: 'ktp_send_staff_chat_message',
        order_id: testData.order_id,
        message: message,
        nonce: testData.nonce
    };

    console.log('Sending chat message:', ajaxData);

    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(ajaxData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(data => {
        console.log('Raw response:', data);
        try {
            const jsonData = JSON.parse(data);
            console.log('Parsed response:', jsonData);
            document.getElementById('results').innerHTML +=
                '<div><strong>' + new Date().toLocaleTimeString() + '</strong>: ' +
                (jsonData.success ? 'SUCCESS' : 'FAILURE') + ' - ' +
                (jsonData.data || JSON.stringify(jsonData)) + '</div>';
        } catch (e) {
            document.getElementById('results').innerHTML +=
                '<div><strong>' + new Date().toLocaleTimeString() + '</strong>: RAW RESPONSE - ' + data + '</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('results').innerHTML +=
            '<div><strong>' + new Date().toLocaleTimeString() + '</strong>: ERROR - ' + error.message + '</div>';
    });
}

// スタッフチャットメッセージ取得テスト
function testGetMessages() {
    const ajaxData = {
        action: 'ktp_get_staff_chat_messages',
        order_id: testData.order_id,
        nonce: testData.nonce
    };

    console.log('Getting chat messages:', ajaxData);

    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(ajaxData)
    })
    .then(response => response.text())
    .then(data => {
        console.log('Get messages response:', data);
        try {
            const jsonData = JSON.parse(data);
            document.getElementById('results').innerHTML +=
                '<div><strong>' + new Date().toLocaleTimeString() + '</strong>: GET MESSAGES ' +
                (jsonData.success ? 'SUCCESS' : 'FAILURE') + ' - ' +
                (jsonData.data ? 'Messages: ' + JSON.stringify(jsonData.data) : JSON.stringify(jsonData)) + '</div>';
        } catch (e) {
            document.getElementById('results').innerHTML +=
                '<div><strong>' + new Date().toLocaleTimeString() + '</strong>: GET RAW - ' + data + '</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('results').innerHTML +=
            '<div><strong>' + new Date().toLocaleTimeString() + '</strong>: GET ERROR - ' + error.message + '</div>';
    });
}

// ページ読み込み後にテストを開始
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('send-button').addEventListener('click', testSendMessage);
    document.getElementById('get-button').addEventListener('click', testGetMessages);

    // 自動実行
    setTimeout(testGetMessages, 1000);
    setTimeout(testSendMessage, 2000);
});
</script>

<h2>Test Controls:</h2>
<button id="send-button">Send Chat Message</button>
<button id="get-button">Get Chat Messages</button>

<h2>Test Results:</h2>
<div id="results" style="border: 1px solid #ccc; padding: 10px; margin-top: 10px; height: 400px; overflow-y: auto;"></div>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
p { margin: 5px 0; }
#results { background: #f9f9f9; }
#results div { margin: 5px 0; padding: 5px; border-bottom: 1px solid #eee; }
button { padding: 10px 20px; font-size: 16px; margin: 5px; }
</style>

</body>
</html>
