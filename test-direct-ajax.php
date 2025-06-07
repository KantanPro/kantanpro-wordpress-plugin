<?php
/**
 * get_latest_staff_chat エンドポイント直接テスト
 */

// WordPress環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

// ログインしていない場合は管理画面にリダイレクト
if (!is_user_logged_in()) {
    wp_redirect(admin_url());
    exit;
}

$order_id = $_GET['order_id'] ?? 1;

// Direct Ajax Call Simulation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_ajax'])) {
    echo "<h1>Ajax レスポンステスト結果</h1>";

    // AJAXハンドラーを直接呼び出し
    $_POST['action'] = 'get_latest_staff_chat';
    $_POST['order_id'] = $order_id;
    $_POST['_ajax_nonce'] = wp_create_nonce('ktpwp_staff_chat_nonce');

    // DOINGAJAXフラグを設定
    define('DOING_AJAX', true);

    echo "<h2>リクエストパラメータ:</h2>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";

    // 出力バッファを開始
    ob_start();

    try {
        // KTPWPAjaxクラスの取得
        if (class_exists('KTPWP_Ajax')) {
            $ajax_instance = KTPWP_Ajax::get_instance();
            echo "<p>✅ KTPWP_Ajax インスタンス取得成功</p>";

            // Ajax ハンドラーを直接呼び出し
            if (method_exists($ajax_instance, 'ajax_get_latest_staff_chat')) {
                echo "<p>✅ ajax_get_latest_staff_chat メソッド存在確認</p>";
                echo "<h3>Ajax ハンドラー実行:</h3>";

                // 実際のハンドラーを呼び出し
                $ajax_instance->ajax_get_latest_staff_chat();

            } else {
                echo "<p>❌ ajax_get_latest_staff_chat メソッドが見つかりません</p>";
            }
        } else {
            echo "<p>❌ KTPWP_Ajax クラスが見つかりません</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ エラー: " . esc_html($e->getMessage()) . "</p>";
        echo "<p>スタックトレース:</p>";
        echo "<pre>" . esc_html($e->getTraceAsString()) . "</pre>";
    }

    // 出力を取得
    $output = ob_get_contents();
    ob_end_clean();

    echo "<h3>実行結果:</h3>";
    echo "<div style='background: #f0f0f0; padding: 10px; white-space: pre-wrap;'>" . esc_html($output) . "</div>";

    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>get_latest_staff_chat 直接テスト</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>get_latest_staff_chat 直接テスト</h1>

    <div class="test-section">
        <h3>現在の設定</h3>
        <p>注文ID: <?php echo esc_html($order_id); ?></p>
        <p>ユーザー: <?php echo esc_html(wp_get_current_user()->display_name); ?> (ID: <?php echo get_current_user_id(); ?>)</p>
        <p>Nonce: <code><?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?></code></p>
    </div>

    <div class="test-section">
        <h3>Ajax ハンドラー直接実行</h3>
        <form method="post">
            <input type="hidden" name="test_ajax" value="1">
            <button type="submit">Ajax ハンドラーを直接実行</button>
        </form>
    </div>

    <div class="test-section">
        <h3>curl コマンド例</h3>
        <p>以下のコマンドをターミナルで実行してテストできます：</p>
        <pre>curl -X POST "<?php echo admin_url('admin-ajax.php'); ?>" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: <?php
  $cookies = array();
  foreach ($_COOKIE as $name => $value) {
      $cookies[] = $name . '=' . $value;
  }
  echo implode('; ', $cookies);
  ?>" \
  -d "action=get_latest_staff_chat&order_id=<?php echo $order_id; ?>&_ajax_nonce=<?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?>" \
  -v</pre>
    </div>

    <div class="test-section">
        <h3>JavaScript テスト</h3>
        <button onclick="testAjax()">JavaScript でテスト</button>
        <div id="js-result" style="margin-top: 10px; padding: 10px; background: #f0f0f0; min-height: 20px;"></div>
    </div>

    <script>
        function testAjax() {
            const resultDiv = document.getElementById('js-result');
            resultDiv.innerHTML = 'テスト実行中...';

            const xhr = new XMLHttpRequest();
            const url = '<?php echo admin_url('admin-ajax.php'); ?>';
            const params = 'action=get_latest_staff_chat&order_id=<?php echo $order_id; ?>&_ajax_nonce=<?php echo wp_create_nonce('ktpwp_staff_chat_nonce'); ?>';

            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    let result = `
                        <strong>HTTPステータス:</strong> ${xhr.status} ${xhr.statusText}<br>
                        <strong>レスポンス長:</strong> ${xhr.responseText.length}<br>
                        <strong>Content-Type:</strong> ${xhr.getResponseHeader('Content-Type')}<br>
                        <strong>レスポンス内容:</strong><br>
                        <pre style="max-height: 200px; overflow-y: auto;">${xhr.responseText}</pre>
                    `;

                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            result += `<br><strong>✅ JSON解析成功:</strong><br><pre>${JSON.stringify(response, null, 2)}</pre>`;
                        } catch (e) {
                            result += `<br><strong>❌ JSON解析エラー:</strong> ${e.message}`;

                            // 制御文字の検出
                            const controlChars = xhr.responseText.match(/[\x00-\x1F\x7F]/g);
                            if (controlChars) {
                                result += `<br><strong>制御文字発見:</strong> ${controlChars.map(c => '0x' + c.charCodeAt(0).toString(16)).join(', ')}`;
                            }
                        }
                    }

                    resultDiv.innerHTML = result;
                }
            };

            xhr.send(params);
        }
    </script>
</body>
</html>
