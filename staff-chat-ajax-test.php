<?php
/**
 * Staff Chat AJAX Test Script
 *
 * このスクリプトは、スタッフチャット機能のAJAX実装をテストするためのものです。
 * データベース接続が復旧したら実行してください。
 *
 * @package KTPWP
 * @since 1.0.0
 */

// WordPressの読み込み
require_once( '../../../../wp-config.php' );
require_once( '../../../../wp-load.php' );

// セキュリティチェック
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'このページにアクセスする権限がありません。' );
}

// スタッフチャットクラスの読み込み
require_once( 'class-ktpwp-staff-chat.php' );

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スタッフチャット AJAX テスト</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 40px;
            line-height: 1.6;
        }
        .test-section {
            background: #f9f9f9;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success {
            color: #46b450;
        }
        .error {
            color: #dc3232;
        }
        .warning {
            color: #ffb900;
        }
        pre {
            background: #23282d;
            color: #eee;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
        button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 3px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #005a87;
        }
        #test-results {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>スタッフチャット AJAX 機能テスト</h1>
    
    <div class="test-section">
        <h2>1. データベース接続テスト</h2>
        <?php
        try {
            global $wpdb;
            $result = $wpdb->get_var( "SELECT 1" );
            if ( $result == 1 ) {
                echo '<p class="success">✓ データベース接続成功</p>';
                $db_connected = true;
            } else {
                echo '<p class="error">✗ データベース接続失敗</p>';
                $db_connected = false;
            }
        } catch ( Exception $e ) {
            echo '<p class="error">✗ データベース接続エラー: ' . esc_html( $e->getMessage() ) . '</p>';
            $db_connected = false;
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. スタッフチャットクラステスト</h2>
        <?php
        if ( $db_connected ) {
            try {
                $staff_chat = new KTPWP_Staff_Chat();
                echo '<p class="success">✓ KTPWP_Staff_Chat クラスのインスタンス化成功</p>';
                
                // テーブル作成テスト
                $table_created = $staff_chat->create_table();
                if ( $table_created ) {
                    echo '<p class="success">✓ スタッフチャットテーブル作成/更新成功</p>';
                } else {
                    echo '<p class="error">✗ スタッフチャットテーブル作成/更新失敗</p>';
                }
                
            } catch ( Exception $e ) {
                echo '<p class="error">✗ クラステストエラー: ' . esc_html( $e->getMessage() ) . '</p>';
            }
        } else {
            echo '<p class="warning">⚠ データベース接続が必要です</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>3. AJAX エンドポイントテスト</h2>
        <?php if ( $db_connected ): ?>
        <p>以下のボタンをクリックしてAJAX機能をテストしてください：</p>
        
        <button onclick="testGetLatestMessages()">最新メッセージ取得テスト</button>
        <button onclick="testSendMessage()">メッセージ送信テスト</button>
        
        <div id="test-results"></div>
        
        <script>
        // AJAX URL設定
        const ajaxUrl = '<?php echo admin_url( "admin-ajax.php" ); ?>';
        const testNonce = '<?php echo wp_create_nonce( "ktpwp_staff_chat_nonce" ); ?>';
        
        function testGetLatestMessages() {
            const resultDiv = document.getElementById('test-results');
            resultDiv.innerHTML = '<p>最新メッセージ取得をテスト中...</p>';
            
            const formData = new FormData();
            formData.append('action', 'get_latest_staff_chat');
            formData.append('order_id', '1'); // テスト用注文ID
            formData.append('_ajax_nonce', testNonce);
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<p class="success">✓ 最新メッセージ取得成功</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    resultDiv.innerHTML = '<p class="error">✗ 最新メッセージ取得失敗: ' + (data.data || '不明なエラー') + '</p>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">✗ AJAX エラー: ' + error.message + '</p>';
            });
        }
        
        function testSendMessage() {
            const resultDiv = document.getElementById('test-results');
            resultDiv.innerHTML = '<p>メッセージ送信をテスト中...</p>';
            
            const formData = new FormData();
            formData.append('action', 'send_staff_chat_message');
            formData.append('order_id', '1'); // テスト用注文ID
            formData.append('message', 'テストメッセージ - ' + new Date().toLocaleString());
            formData.append('_ajax_nonce', testNonce);
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<p class="success">✓ メッセージ送信成功</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    resultDiv.innerHTML = '<p class="error">✗ メッセージ送信失敗: ' + (data.data || '不明なエラー') + '</p>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">✗ AJAX エラー: ' + error.message + '</p>';
            });
        }
        </script>
        
        <?php else: ?>
        <p class="warning">⚠ データベース接続が必要です</p>
        <?php endif; ?>
    </div>
    
    <div class="test-section">
        <h2>4. 実装された機能</h2>
        <ul>
            <li>✓ <strong>AJAX エンドポイント</strong>: get_latest_staff_chat, send_staff_chat_message</li>
            <li>✓ <strong>nonce セキュリティ</strong>: WordPress標準のセキュリティトークン実装</li>
            <li>✓ <strong>権限チェック</strong>: current_user_can()によるユーザー権限確認</li>
            <li>✓ <strong>入力サニタイズ</strong>: WordPress標準のサニタイズ関数使用</li>
            <li>✓ <strong>エラーハンドリング</strong>: try-catch とログ記録</li>
            <li>✓ <strong>リアルタイム更新</strong>: JavaScript ポーリング機能</li>
            <li>✓ <strong>フォーム送信</strong>: AJAX による非同期メッセージ送信</li>
        </ul>
    </div>
    
    <div class="test-section">
        <h2>5. データベーステーブル情報</h2>
        <?php if ( $db_connected ): ?>
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_staff_chat';
        
        // テーブル存在確認
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
        
        if ( $table_exists ) {
            echo '<p class="success">✓ テーブル ' . esc_html( $table_name ) . ' が存在します</p>';
            
            // カラム情報取得
            $columns = $wpdb->get_results( "SHOW COLUMNS FROM `{$table_name}`" );
            echo '<h4>カラム構造:</h4>';
            echo '<pre>';
            foreach ( $columns as $column ) {
                echo esc_html( $column->Field . ' | ' . $column->Type . ' | ' . $column->Null . ' | ' . $column->Key ) . "\n";
            }
            echo '</pre>';
            
            // レコード数取得
            $count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_name}`" );
            echo '<p>現在のレコード数: ' . intval( $count ) . '</p>';
            
        } else {
            echo '<p class="warning">⚠ テーブル ' . esc_html( $table_name ) . ' が存在しません</p>';
        }
        ?>
        <?php else: ?>
        <p class="warning">⚠ データベース接続が必要です</p>
        <?php endif; ?>
    </div>
    
    <p><a href="<?php echo admin_url(); ?>">WordPress 管理画面に戻る</a></p>
</body>
</html>
