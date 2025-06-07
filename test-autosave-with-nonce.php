<?php
/**
 * Test auto-save with valid nonce
 */

// WordPress環境を読み込み
require_once('../../../wp-config.php');

global $wpdb;

// 有効なオーダーを取得
$orders = $wpdb->get_results("SELECT id, customer_name, project_name FROM {$wpdb->prefix}ktp_order LIMIT 5", ARRAY_A);

echo "<h1>KTPWP Auto-Save Test with Valid Nonce</h1>\n";

if (empty($orders)) {
    echo "<p>No orders found in database.</p>\n";
    exit;
}

echo "<h2>Available Orders:</h2>\n";
foreach ($orders as $order) {
    echo "<p>ID: {$order['id']} - Customer: {$order['customer_name']} - Project: {$order['project_name']}</p>\n";
}

$test_order_id = $orders[0]['id'];

// 有効なnonceを生成
$nonce = wp_create_nonce('ktp_ajax_nonce');

echo "<h2>Test Configuration:</h2>\n";
echo "<p>Order ID: {$test_order_id}</p>\n";
echo "<p>Generated Nonce: {$nonce}</p>\n";

// 請求項目を取得
$invoice_items = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ktp_order_invoice_items WHERE order_id = %d LIMIT 5",
    $test_order_id
), ARRAY_A);

echo "<h2>Available Invoice Items:</h2>\n";
if (empty($invoice_items)) {
    echo "<p>No invoice items found. Creating a test item...</p>\n";

    // テスト用のアイテムを作成
    $result = $wpdb->insert(
        $wpdb->prefix . 'ktp_order_invoice_items',
        array(
            'order_id' => $test_order_id,
            'product_name' => 'Test Product',
            'price' => 1000,
            'quantity' => 1,
            'unit' => '式',
            'amount' => 1000,
            'remarks' => 'Test remarks',
            'sort_order' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%d', '%s', '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s')
    );

    if ($result) {
        $test_item_id = $wpdb->insert_id;
        echo "<p>Created test item with ID: {$test_item_id}</p>\n";

        $invoice_items = array(array(
            'id' => $test_item_id,
            'order_id' => $test_order_id,
            'product_name' => 'Test Product',
            'price' => 1000,
            'quantity' => 1,
            'unit' => '式',
            'amount' => 1000,
            'remarks' => 'Test remarks'
        ));
    } else {
        echo "<p>Failed to create test item: " . $wpdb->last_error . "</p>\n";
        exit;
    }
} else {
    foreach ($invoice_items as $item) {
        echo "<p>ID: {$item['id']} - Product: {$item['product_name']} - Price: {$item['price']}</p>\n";
    }
}

$test_item_id = $invoice_items[0]['id'];

?>

<script>
// 有効なnonceでAJAXテストを実行
const testData = {
    order_id: <?php echo $test_order_id; ?>,
    item_id: <?php echo $test_item_id; ?>,
    nonce: '<?php echo $nonce; ?>'
};

console.log('Test configuration:', testData);

// 自動保存テスト
function testAutoSave() {
    const ajaxData = {
        action: 'ktp_auto_save_item',
        item_type: 'invoice',
        item_id: testData.item_id,
        field_name: 'product_name',
        field_value: 'Updated Test Product ' + new Date().getTime(),
        order_id: testData.order_id,
        nonce: testData.nonce
    };

    console.log('Sending request:', ajaxData);

    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(ajaxData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response:', data);
        document.getElementById('results').innerHTML +=
            '<div><strong>' + new Date().toLocaleTimeString() + '</strong>: ' +
            (data.success ? 'SUCCESS' : 'FAILURE') + ' - ' +
            (data.data || JSON.stringify(data)) + '</div>';
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('results').innerHTML +=
            '<div><strong>' + new Date().toLocaleTimeString() + '</strong>: ERROR - ' + error.message + '</div>';
    });
}

// ページ読み込み後にテストを開始
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('test-button').addEventListener('click', testAutoSave);

    // 自動実行
    setTimeout(testAutoSave, 1000);
});
</script>

<h2>Test Results:</h2>
<button id="test-button">Run Auto-Save Test</button>
<div id="results" style="border: 1px solid #ccc; padding: 10px; margin-top: 10px; height: 300px; overflow-y: auto;"></div>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
p { margin: 5px 0; }
#results { background: #f9f9f9; }
#results div { margin: 5px 0; padding: 5px; border-bottom: 1px solid #eee; }
button { padding: 10px 20px; font-size: 16px; }
</style>
