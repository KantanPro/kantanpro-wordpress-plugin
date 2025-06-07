<?php
/**
 * AJAX test page for debugging
 */

// WordPressの環境を読み込み
require_once(dirname(__FILE__, 4) . '/wp-load.php');

if (!is_user_logged_in()) {
    wp_die('ログインが必要です');
}

// AJAXテストページのHTMLを出力
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTPWP AJAX Test Page</title>
    <?php wp_head(); ?>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        button { padding: 10px 20px; margin: 5px; }
        .log { background: #f5f5f5; padding: 10px; margin: 10px 0; border: 1px solid #ddd; max-height: 300px; overflow-y: auto; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body <?php body_class(); ?>>
    <h1>KTPWP AJAX Test Page</h1>
    <p>Current user: <?php echo wp_get_current_user()->user_login; ?> (ID: <?php echo get_current_user_id(); ?>)</p>

    <div class="test-section">
        <h2>1. WordPress AJAX Settings Check</h2>
        <button onclick="checkAjaxSettings()">Check AJAX Settings</button>
        <div id="ajax-settings-result" class="log"></div>
    </div>

    <div class="test-section">
        <h2>2. Send Staff Chat Message Test</h2>
        <input type="number" id="order-id" placeholder="Order ID" value="1">
        <input type="text" id="test-message" placeholder="Test message" value="Test message from browser">
        <button onclick="sendStaffChatMessage()">Send Staff Chat Message</button>
        <div id="staff-chat-result" class="log"></div>
    </div>

    <div class="test-section">
        <h2>3. Get Latest Staff Chat Test</h2>
        <input type="number" id="get-order-id" placeholder="Order ID" value="1">
        <button onclick="getLatestStaffChat()">Get Latest Staff Chat</button>
        <div id="get-chat-result" class="log"></div>
    </div>

    <div class="test-section">
        <h2>4. Raw AJAX Test</h2>
        <button onclick="testRawAjax()">Test Raw AJAX to admin-ajax.php</button>
        <div id="raw-ajax-result" class="log"></div>
    </div>

    <script>
        function log(elementId, message, isError = false) {
            const element = document.getElementById(elementId);
            const time = new Date().toLocaleTimeString();
            const className = isError ? 'error' : 'success';
            element.innerHTML += `<div class="${className}">[${time}] ${message}</div>`;
            element.scrollTop = element.scrollHeight;
        }

        function checkAjaxSettings() {
            const resultId = 'ajax-settings-result';
            document.getElementById(resultId).innerHTML = '';

            log(resultId, '=== AJAX Settings Check ===');

            // WordPress AJAX URL
            if (typeof ajaxurl !== 'undefined') {
                log(resultId, `ajaxurl: ${ajaxurl}`);
            } else {
                log(resultId, 'ajaxurl: NOT DEFINED', true);
            }

            // KTPWP AJAX settings
            if (typeof ktpwp_ajax !== 'undefined') {
                log(resultId, 'ktpwp_ajax object: FOUND');
                log(resultId, `ktpwp_ajax.ajax_url: ${ktpwp_ajax.ajax_url || 'NOT SET'}`);
                log(resultId, `ktpwp_ajax.nonces: ${JSON.stringify(ktpwp_ajax.nonces || {})}`);
            } else {
                log(resultId, 'ktpwp_ajax object: NOT FOUND', true);
            }

            log(resultId, '=== Check Complete ===');
        }

        function sendStaffChatMessage() {
            const resultId = 'staff-chat-result';
            document.getElementById(resultId).innerHTML = '';

            const orderId = document.getElementById('order-id').value;
            const message = document.getElementById('test-message').value;

            if (!orderId || !message) {
                log(resultId, 'Order ID and message are required', true);
                return;
            }

            log(resultId, '=== Send Staff Chat Message ===');
            log(resultId, `Order ID: ${orderId}, Message: ${message}`);

            const url = (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.ajax_url) ?
                ktpwp_ajax.ajax_url :
                (typeof ajaxurl !== 'undefined') ? ajaxurl :
                '/wp-admin/admin-ajax.php';

            let params = `action=send_staff_chat_message&order_id=${orderId}&message=${encodeURIComponent(message)}`;

            if (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces && ktpwp_ajax.nonces.staff_chat) {
                params += `&_ajax_nonce=${ktpwp_ajax.nonces.staff_chat}`;
                log(resultId, `Using nonce: ${ktpwp_ajax.nonces.staff_chat}`);
            } else {
                log(resultId, 'No nonce available - proceeding without nonce', true);
            }

            log(resultId, `Sending to: ${url}`);
            log(resultId, `Parameters: ${params}`);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    log(resultId, `Response Status: ${xhr.status}`);
                    log(resultId, `Response Headers: ${xhr.getAllResponseHeaders()}`);
                    log(resultId, `Response Text: ${xhr.responseText.substring(0, 1000)}`);

                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            log(resultId, `Parsed Response: ${JSON.stringify(response, null, 2)}`);
                        } catch (e) {
                            log(resultId, 'Failed to parse JSON response', true);
                        }
                    } else {
                        log(resultId, `HTTP Error: ${xhr.status}`, true);
                    }
                }
            };

            xhr.send(params);
        }

        function getLatestStaffChat() {
            const resultId = 'get-chat-result';
            document.getElementById(resultId).innerHTML = '';

            const orderId = document.getElementById('get-order-id').value;

            if (!orderId) {
                log(resultId, 'Order ID is required', true);
                return;
            }

            log(resultId, '=== Get Latest Staff Chat ===');
            log(resultId, `Order ID: ${orderId}`);

            const url = (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.ajax_url) ?
                ktpwp_ajax.ajax_url :
                (typeof ajaxurl !== 'undefined') ? ajaxurl :
                '/wp-admin/admin-ajax.php';

            let params = `action=get_latest_staff_chat&order_id=${orderId}`;

            if (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces && ktpwp_ajax.nonces.staff_chat) {
                params += `&_ajax_nonce=${ktpwp_ajax.nonces.staff_chat}`;
            }

            log(resultId, `Sending to: ${url}`);
            log(resultId, `Parameters: ${params}`);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    log(resultId, `Response Status: ${xhr.status}`);
                    log(resultId, `Response Text: ${xhr.responseText.substring(0, 1000)}`);

                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            log(resultId, `Parsed Response: ${JSON.stringify(response, null, 2)}`);
                        } catch (e) {
                            log(resultId, 'Failed to parse JSON response', true);
                        }
                    } else {
                        log(resultId, `HTTP Error: ${xhr.status}`, true);
                    }
                }
            };

            xhr.send(params);
        }

        function testRawAjax() {
            const resultId = 'raw-ajax-result';
            document.getElementById(resultId).innerHTML = '';

            log(resultId, '=== Raw AJAX Test ===');

            const url = '<?php echo admin_url('admin-ajax.php'); ?>';
            const params = 'action=heartbeat&_wpnonce=' + Math.random();

            log(resultId, `Testing basic AJAX to: ${url}`);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    log(resultId, `Response Status: ${xhr.status}`);
                    if (xhr.status === 200) {
                        log(resultId, 'Basic AJAX functionality is working');
                    } else {
                        log(resultId, `Basic AJAX failed with status: ${xhr.status}`, true);
                    }
                }
            };

            xhr.send(params);
        }

        // Automatically check AJAX settings on page load
        window.onload = function() {
            checkAjaxSettings();
        };
    </script>
    <?php wp_footer(); ?>
</body>
</html>
