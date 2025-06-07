<?php
/**
 * 実際のKTPWPページでのライブテスト
 */

// WordPress環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

// ログインチェック
if (!is_user_logged_in()) {
    die('ログインが必要です');
}

echo "<h1>KTPWPライブページテスト</h1>";

// 現在のユーザー情報
$current_user = wp_get_current_user();
echo "<h2>現在のユーザー:</h2>";
echo "<p>ID: {$current_user->ID}, ログイン名: {$current_user->user_login}, 表示名: {$current_user->display_name}</p>";

// nonce値を取得
$nonce = wp_create_nonce('ktpwp_ajax_nonce');
echo "<h2>nonce値:</h2>";
echo "<p>{$nonce}</p>";

// KTPWP AJAX設定を確認
$ajax_url = admin_url('admin-ajax.php');
echo "<h2>AJAX URL:</h2>";
echo "<p>{$ajax_url}</p>";

// KTPWPクラスが利用可能か確認
echo "<h2>KTPWPクラス状況:</h2>";
if (class_exists('KTPWP_Ajax')) {
    echo "<p>✅ KTPWP_Ajax クラスが利用可能</p>";
} else {
    echo "<p>❌ KTPWP_Ajax クラスが利用不可</p>";
}

if (class_exists('KTPWP_Staff_Chat')) {
    echo "<p>✅ KTPWP_Staff_Chat クラスが利用可能</p>";
} else {
    echo "<p>❌ KTPWP_Staff_Chat クラスが利用不可</p>";
}

// 実際のAJAXリクエストをテスト
echo "<h2>実際のAJAXリクエストテスト:</h2>";

$test_data = array(
    'action' => 'get_latest_staff_chat',
    'nonce' => $nonce,
    'user_id' => $current_user->ID
);

echo "<h3>テストリクエストデータ:</h3>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

// cURLでAJAXエンドポイントをテスト
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $ajax_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($test_data),
    CURLOPT_HEADER => true,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/x-www-form-urlencoded',
        'X-Requested-With: XMLHttpRequest'
    ),
    CURLOPT_COOKIE => $_SERVER['HTTP_COOKIE'] ?? '',
    CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ?? 'KTPWP-Test/1.0'
));

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
curl_close($curl);

$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

echo "<h3>HTTPレスポンス:</h3>";
echo "<p>ステータスコード: {$http_code}</p>";

echo "<h4>レスポンスヘッダー:</h4>";
echo "<pre>" . htmlspecialchars($headers) . "</pre>";

echo "<h4>レスポンスボディ:</h4>";
echo "<pre>" . htmlspecialchars($body) . "</pre>";

// レスポンスボディの詳細分析
echo "<h4>レスポンスボディ分析:</h4>";
if (!empty($body)) {
    echo "<p>長さ: " . strlen($body) . " バイト</p>";

    // 制御文字をチェック
    $control_chars = array();
    for ($i = 0; $i < strlen($body); $i++) {
        $char = $body[$i];
        $ord = ord($char);
        if ($ord < 32 && $ord !== 9 && $ord !== 10 && $ord !== 13) {
            $control_chars[] = "位置 {$i}: 0x" . sprintf('%02X', $ord);
        }
    }

    if (!empty($control_chars)) {
        echo "<p>❌ 制御文字が検出されました:</p>";
        echo "<ul>";
        foreach ($control_chars as $char_info) {
            echo "<li>{$char_info}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>✅ 制御文字は検出されませんでした</p>";
    }

    // JSON妥当性チェック
    $json_data = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p>✅ 有効なJSONです</p>";
        echo "<h5>デコードされたJSON:</h5>";
        echo "<pre>" . print_r($json_data, true) . "</pre>";
    } else {
        echo "<p>❌ 無効なJSON: " . json_last_error_msg() . "</p>";

        // 最初と最後の100文字を表示
        echo "<h5>レスポンスの開始部分:</h5>";
        echo "<pre>" . htmlspecialchars(substr($body, 0, 200)) . "</pre>";

        if (strlen($body) > 200) {
            echo "<h5>レスポンスの終了部分:</h5>";
            echo "<pre>" . htmlspecialchars(substr($body, -200)) . "</pre>";
        }
    }
}

// 直接KTPWP_Ajaxクラスでテスト
echo "<h2>直接クラステスト:</h2>";
if (class_exists('KTPWP_Ajax')) {
    try {
        // AJAXハンドラーを直接呼び出し
        $_POST['action'] = 'get_latest_staff_chat';
        $_POST['nonce'] = $nonce;
        $_POST['user_id'] = $current_user->ID;

        // 出力バッファを開始
        ob_start();

        // AJAXハンドラーを実行
        do_action('wp_ajax_get_latest_staff_chat');

        // 出力を取得
        $direct_output = ob_get_clean();

        echo "<h3>直接クラス実行結果:</h3>";
        echo "<pre>" . htmlspecialchars($direct_output) . "</pre>";

        // JSON妥当性チェック
        $direct_json = json_decode($direct_output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p>✅ 直接実行: 有効なJSON</p>";
        } else {
            echo "<p>❌ 直接実行: 無効なJSON - " . json_last_error_msg() . "</p>";
        }

    } catch (Exception $e) {
        echo "<p>❌ 直接実行エラー: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>JavaScript動作テスト:</h2>";
echo "<div id='js-test-results'></div>";

// JavaScriptテスト用のスクリプトを追加
?>
<script src="/wp-content/plugins/KTPWP/js/ktp-js.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const resultsDiv = document.getElementById('js-test-results');

    // AJAX設定をテスト
    resultsDiv.innerHTML += '<h3>JavaScript設定:</h3>';
    resultsDiv.innerHTML += '<p>ktp_ajax_url: ' + (typeof ktp_ajax_url !== 'undefined' ? ktp_ajax_url : '未定義') + '</p>';
    resultsDiv.innerHTML += '<p>ktp_ajax_nonce: ' + (typeof ktp_ajax_nonce !== 'undefined' ? ktp_ajax_nonce : '未定義') + '</p>';

    // pollNewMessages関数をテスト
    if (typeof pollNewMessages === 'function') {
        resultsDiv.innerHTML += '<p>✅ pollNewMessages関数が利用可能</p>';

        // 実際にpollNewMessagesを実行
        resultsDiv.innerHTML += '<h4>pollNewMessages実行テスト:</h4>';
        resultsDiv.innerHTML += '<div id="poll-test">実行中...</div>';

        // コンソールログをキャプチャするためのモンキーパッチ
        const originalLog = console.log;
        const originalError = console.error;
        let logs = [];

        console.log = function(...args) {
            logs.push('LOG: ' + args.join(' '));
            originalLog.apply(console, arguments);
        };

        console.error = function(...args) {
            logs.push('ERROR: ' + args.join(' '));
            originalError.apply(console, arguments);
        };

        // pollNewMessagesを実行
        try {
            pollNewMessages();

            // 2秒後に結果を表示
            setTimeout(function() {
                document.getElementById('poll-test').innerHTML = '<pre>' + logs.join('\n') + '</pre>';

                // コンソールを元に戻す
                console.log = originalLog;
                console.error = originalError;
            }, 2000);

        } catch (error) {
            document.getElementById('poll-test').innerHTML = '<p>❌ JavaScript実行エラー: ' + error.message + '</p>';
            console.log = originalLog;
            console.error = originalError;
        }

    } else {
        resultsDiv.innerHTML += '<p>❌ pollNewMessages関数が利用不可</p>';
    }
});
</script>
<?php
