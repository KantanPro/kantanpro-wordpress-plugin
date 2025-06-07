<?php
/**
 * スタッフチャット送信テスト（修正版）
 *
 * このファイルでスタッフチャットの送信機能をテストします。
 * ブラウザでアクセス: http://kantanpro2.local/wp-content/plugins/KTPWP/test-staff-chat-send-v2.php
 */

// WordPressの環境を読み込み
require_once dirname(__FILE__) . '/../../../wp-load.php';

// 管理者権限が必要
if (!current_user_can('manage_options')) {
    wp_die('このテストは管理者権限が必要です。', 'アクセス権限エラー');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>スタッフチャット送信テスト（修正版）</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .test-form { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        input, textarea { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        button:hover { background: #005a87; }
        #result { margin-top: 20px; padding: 15px; border: 1px solid #ddd; min-height: 100px; }
    </style>
</head>
<body>
    <h1>スタッフチャット送信テスト（修正版）</h1>

    <div class="status info">
        <p>修正後のAJAXハンドラーをテストします。</p>
        <p>現在のユーザー: <?php echo wp_get_current_user()->display_name; ?> (ID: <?php echo get_current_user_id(); ?>)</p>
        <p>AJAX URL: <?php echo admin_url('admin-ajax.php'); ?></p>
        <p>Staff Chat Nonce: <?php echo wp_create_nonce('staff_chat_nonce'); ?></p>
    </div>

    <div class="test-form">
        <h2>スタッフチャット送信テスト</h2>
        <form id="staff-chat-test-form">
            <input type="text" id="order_id" placeholder="受注ID" value="1" required>
            <textarea id="message" placeholder="メッセージを入力してください" required>テストメッセージです。</textarea>
            <button type="submit">メッセージを送信</button>
        </form>
    </div>

    <div id="result">
        <h3>テスト結果:</h3>
        <div id="result-content">まだテストが実行されていません。</div>
    </div>

    <script src="<?php echo site_url('/wp-includes/js/jquery/jquery.min.js'); ?>"></script>
    <script>
    jQuery(document).ready(function($) {
        // AJAX設定をコンソールに表示
        console.log('テスト開始');
        console.log('AJAX URL:', '<?php echo admin_url('admin-ajax.php'); ?>');
        console.log('Nonce:', '<?php echo wp_create_nonce('staff_chat_nonce'); ?>');

        $('#staff-chat-test-form').on('submit', function(e) {
            e.preventDefault();

            var orderId = $('#order_id').val();
            var message = $('#message').val();
            var nonce = '<?php echo wp_create_nonce('staff_chat_nonce'); ?>';

            console.log('送信データ:', {
                action: 'send_staff_chat_message',
                order_id: orderId,
                message: message,
                nonce: nonce
            });

            $('#result-content').html('送信中...');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'send_staff_chat_message',
                    order_id: orderId,
                    message: message,
                    nonce: nonce
                },
                beforeSend: function(xhr) {
                    console.log('AJAX送信開始');
                },
                success: function(response) {
                    console.log('AJAX成功:', response);
                    $('#result-content').html(
                        '<div class="status success">' +
                        '<strong>成功！</strong><br>' +
                        'レスポンス: <pre>' + JSON.stringify(response, null, 2) + '</pre>' +
                        '</div>'
                    );
                },
                error: function(xhr, status, error) {
                    console.log('AJAX エラー:', xhr, status, error);
                    console.log('レスポンステキスト:', xhr.responseText);

                    $('#result-content').html(
                        '<div class="status error">' +
                        '<strong>エラーが発生しました</strong><br>' +
                        'ステータス: ' + xhr.status + '<br>' +
                        'エラー: ' + error + '<br>' +
                        'レスポンス: <pre>' + xhr.responseText + '</pre>' +
                        '</div>'
                    );
                },
                complete: function(xhr, status) {
                    console.log('AJAX完了:', status);
                }
            });
        });

        // ページ読み込み時にAJAXハンドラーが登録されているかテスト
        setTimeout(function() {
            console.log('AJAXハンドラー登録テスト開始');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'send_staff_chat_message'
                    // nonceなしでテスト
                },
                success: function(response) {
                    console.log('ハンドラー登録テスト - 成功（予期しない結果）:', response);
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 400) {
                        console.log('ハンドラー登録テスト - 400エラー（ハンドラーは登録されているが、データが不正）');
                    } else if (xhr.status === 404) {
                        console.log('ハンドラー登録テスト - 404エラー（ハンドラーが登録されていない）');
                    } else {
                        console.log('ハンドラー登録テスト - その他のエラー:', xhr.status, error);
                    }
                }
            });
        }, 1000);
    });
    </script>
</body>
</html>
