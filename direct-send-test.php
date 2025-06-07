<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>スタッフチャットAJAX設定テスト</title>
    <script>
        // AJAX設定を直接設定
        window.ktpwp_ajax = {
            ajax_url: 'http://kantanpro2.local/wp-admin/admin-ajax.php',
            nonces: {
                staff_chat: '<?php echo wp_create_nonce("ktpwp_staff_chat_nonce"); ?>'
            }
        };

        // 従来の変数名も設定
        window.ktp_ajax_object = window.ktpwp_ajax;
        window.ajaxurl = window.ktpwp_ajax.ajax_url;

        console.log('直接設定されたAJAX設定:', window.ktpwp_ajax);
    </script>
</head>
<body>
    <h1>スタッフチャット送信テスト</h1>

    <div>
        <label>注文ID:</label>
        <input type="number" id="order-id" value="2" />
    </div>

    <div>
        <label>メッセージ:</label>
        <input type="text" id="message" value="直接設定テスト" />
    </div>

    <button onclick="testSendMessage()">送信テスト</button>

    <div id="result"></div>

    <script>
        function testSendMessage() {
            const orderId = document.getElementById('order-id').value;
            const message = document.getElementById('message').value;

            console.log('テスト送信開始:', {
                orderId: orderId,
                message: message,
                hasKtpwpAjax: typeof ktpwp_ajax !== 'undefined',
                hasNonces: typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces,
                ajaxData: window.ktpwp_ajax
            });

            // パラメータ構築
            let params = 'action=send_staff_chat_message&order_id=' + orderId + '&message=' + encodeURIComponent(message);

            if (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces && ktpwp_ajax.nonces.staff_chat) {
                params += '&_ajax_nonce=' + ktpwp_ajax.nonces.staff_chat;
                console.log('nonce追加済み:', ktpwp_ajax.nonces.staff_chat);
            } else {
                console.log('nonceなしで送信');
            }

            console.log('送信URL:', ktpwp_ajax.ajax_url);
            console.log('送信パラメータ:', params);

            // AJAX送信
            fetch(ktpwp_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            })
            .then(response => {
                console.log('レスポンス受信:', response.status, response.statusText);
                return response.text();
            })
            .then(data => {
                console.log('レスポンスデータ:', data);
                document.getElementById('result').innerHTML =
                    '<h3>送信結果</h3><pre>' + JSON.stringify({
                        status: 'success',
                        response: data
                    }, null, 2) + '</pre>';
            })
            .catch(error => {
                console.error('送信エラー:', error);
                document.getElementById('result').innerHTML =
                    '<h3>送信エラー</h3><pre>' + error.toString() + '</pre>';
            });
        }
    </script>
</body>
</html>

<?php
// WordPress環境を読み込み（nonceのため）
require_once '../../../wp-config.php';
?>
