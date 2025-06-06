<?php
/**
 * Debug file for staff chat display issues
 * 
 * This file helps identify why staff chat content is not displaying
 */

// WordPressの基本機能を読み込み
require_once(dirname(__FILE__) . '/../../../wp-load.php');

// WPDBオブジェクトのグローバル宣言
global $wpdb;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>スタッフチャット表示デバッグ</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 0; }
        code { background-color: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🔍 スタッフチャット表示デバッグ</h1>
    
    <?php
    // 1. 必要なクラスが存在するかチェック
    echo '<div class="section info">';
    echo '<h2>1️⃣ クラス存在チェック</h2>';
    
    $required_classes = [
        'KTPWP_Staff_Chat',
        'KTPWP_Ajax',
        'KTPWP_Order_UI'
    ];
    
    foreach ($required_classes as $class_name) {
        if (class_exists($class_name)) {
            echo "✅ {$class_name} クラスが存在します<br>";
        } else {
            echo "❌ {$class_name} クラスが存在しません<br>";
            // クラスファイルを手動で読み込んでみる
            $class_file = dirname(__FILE__) . '/class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
            if (file_exists($class_file)) {
                echo "&nbsp;&nbsp;📁 ファイルは存在します: {$class_file}<br>";
                require_once($class_file);
                if (class_exists($class_name)) {
                    echo "&nbsp;&nbsp;✅ 手動読み込み成功<br>";
                } else {
                    echo "&nbsp;&nbsp;❌ 手動読み込み失敗<br>";
                }
            } else {
                echo "&nbsp;&nbsp;❌ ファイルが見つかりません: {$class_file}<br>";
            }
        }
    }
    echo '</div>';
    
    // 2. テーブル存在チェック
    echo '<div class="section info">';
    echo '<h2>2️⃣ データベーステーブルチェック</h2>';
    
    $staff_chat_table = $wpdb->prefix . 'ktp_order_staff_chat';
    $order_table = $wpdb->prefix . 'ktp_order';
    
    $staff_chat_exists = $wpdb->get_var("SHOW TABLES LIKE '{$staff_chat_table}'");
    $order_exists = $wpdb->get_var("SHOW TABLES LIKE '{$order_table}'");
    
    if ($staff_chat_exists) {
        echo "✅ スタッフチャットテーブル '{$staff_chat_table}' が存在します<br>";
        
        // データ数をチェック
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$staff_chat_table}");
        echo "&nbsp;&nbsp;📊 データ数: {$count}件<br>";
        
        if ($count > 0) {
            $latest_data = $wpdb->get_results("SELECT * FROM {$staff_chat_table} ORDER BY created_at DESC LIMIT 3");
            echo "&nbsp;&nbsp;📋 最新データ（3件）:<br>";
            echo "<table>";
            echo "<tr><th>ID</th><th>注文ID</th><th>ユーザー名</th><th>メッセージ</th><th>作成日時</th></tr>";
            foreach ($latest_data as $row) {
                echo "<tr>";
                echo "<td>" . esc_html($row->id) . "</td>";
                echo "<td>" . esc_html($row->order_id) . "</td>";
                echo "<td>" . esc_html($row->user_display_name) . "</td>";
                echo "<td>" . esc_html(substr($row->message, 0, 50)) . "...</td>";
                echo "<td>" . esc_html($row->created_at) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "❌ スタッフチャットテーブル '{$staff_chat_table}' が存在しません<br>";
    }
    
    if ($order_exists) {
        echo "✅ 注文テーブル '{$order_table}' が存在します<br>";
        
        // 最新の注文を取得
        $latest_order = $wpdb->get_row("SELECT * FROM {$order_table} ORDER BY id DESC LIMIT 1");
        if ($latest_order) {
            echo "&nbsp;&nbsp;📋 最新注文ID: {$latest_order->id}<br>";
            echo "&nbsp;&nbsp;📋 プロジェクト名: " . esc_html($latest_order->project_name) . "<br>";
        }
    } else {
        echo "❌ 注文テーブル '{$order_table}' が存在しません<br>";
    }
    echo '</div>';
    
    // 3. スタッフチャット機能のテスト
    echo '<div class="section info">';
    echo '<h2>3️⃣ スタッフチャット機能テスト</h2>';
    
    // クラスファイルを手動で読み込む
    $staff_chat_file = dirname(__FILE__) . '/class-ktpwp-staff-chat.php';
    if (file_exists($staff_chat_file)) {
        require_once($staff_chat_file);
        echo "✅ スタッフチャットクラスファイルを読み込みました<br>";
        
        if (class_exists('KTPWP_Staff_Chat')) {
            echo "✅ KTPWP_Staff_Chat クラスが利用可能です<br>";
            
            // インスタンスを作成
            $staff_chat = KTPWP_Staff_Chat::get_instance();
            echo "✅ スタッフチャットインスタンスを作成しました<br>";
            
            // 最新の注文IDでHTMLを生成してみる
            if (isset($latest_order) && $latest_order) {
                echo "<br>🧪 注文ID {$latest_order->id} でHTMLを生成テスト:<br>";
                
                try {
                    $html_output = $staff_chat->generate_html($latest_order->id);
                    
                    if (!empty($html_output)) {
                        echo "✅ HTMLが生成されました（文字数: " . strlen($html_output) . "）<br>";
                        
                        // HTMLの内容を確認
                        if (strpos($html_output, '■ スタッフチャット') !== false) {
                            echo "&nbsp;&nbsp;✅ タイトルが含まれています<br>";
                        } else {
                            echo "&nbsp;&nbsp;❌ タイトルが含まれていません<br>";
                        }
                        
                        if (strpos($html_output, 'staff-chat-content') !== false) {
                            echo "&nbsp;&nbsp;✅ チャットコンテンツ要素が含まれています<br>";
                        } else {
                            echo "&nbsp;&nbsp;❌ チャットコンテンツ要素が含まれていません<br>";
                        }
                        
                        if (strpos($html_output, 'メッセージはありません') !== false) {
                            echo "&nbsp;&nbsp;⚠️ 「メッセージはありません」が表示されています<br>";
                        }
                        
                        // HTMLの最初の500文字を表示
                        echo "<br>📄 生成されたHTML（最初の500文字）:<br>";
                        echo "<pre>" . esc_html(substr($html_output, 0, 500)) . "...</pre>";
                        
                    } else {
                        echo "❌ HTMLが生成されませんでした<br>";
                    }
                    
                } catch (Exception $e) {
                    echo "❌ HTML生成でエラーが発生しました: " . esc_html($e->getMessage()) . "<br>";
                }
            } else {
                echo "⚠️ テスト用の注文データがありません<br>";
            }
            
        } else {
            echo "❌ KTPWP_Staff_Chat クラスが読み込まれていません<br>";
        }
    } else {
        echo "❌ スタッフチャットクラスファイルが見つかりません: {$staff_chat_file}<br>";
    }
    echo '</div>';
    
    // 4. 権限チェック
    echo '<div class="section info">';
    echo '<h2>4️⃣ ユーザー権限チェック</h2>';
    
    $current_user = wp_get_current_user();
    if ($current_user->ID) {
        echo "✅ ログインユーザー: " . esc_html($current_user->user_login) . " (ID: {$current_user->ID})<br>";
        
        if (current_user_can('edit_posts')) {
            echo "✅ edit_posts 権限があります<br>";
        } else {
            echo "❌ edit_posts 権限がありません<br>";
        }
        
        if (current_user_can('manage_options')) {
            echo "✅ manage_options 権限があります<br>";
        } else {
            echo "❌ manage_options 権限がありません<br>";
        }
        
    } else {
        echo "❌ ログインしていません<br>";
    }
    echo '</div>';
    
    // 5. 受注書ページでの呼び出しテスト
    echo '<div class="section info">';
    echo '<h2>5️⃣ 受注書ページ統合テスト</h2>';
    
    // タブオーダークラスの読み込み
    $tab_order_file = dirname(__FILE__) . '/class-tab-order.php';
    if (file_exists($tab_order_file)) {
        echo "✅ class-tab-order.php ファイルが存在します<br>";
        
        // ファイル内容でスタッフチャット関連の呼び出しを確認
        $file_content = file_get_contents($tab_order_file);
        
        if (strpos($file_content, 'Generate_Staff_Chat_HTML') !== false) {
            echo "&nbsp;&nbsp;✅ Generate_Staff_Chat_HTML の呼び出しが見つかりました<br>";
        } else {
            echo "&nbsp;&nbsp;❌ Generate_Staff_Chat_HTML の呼び出しが見つかりません<br>";
        }
        
        if (strpos($file_content, 'KTPWP_Staff_Chat') !== false) {
            echo "&nbsp;&nbsp;✅ KTPWP_Staff_Chat クラスの参照が見つかりました<br>";
        } else {
            echo "&nbsp;&nbsp;❌ KTPWP_Staff_Chat クラスの参照が見つかりません<br>";
        }
        
    } else {
        echo "❌ class-tab-order.php ファイルが見つかりません<br>";
    }
    echo '</div>';
    
    // 6. JavaScriptとCSSのチェック
    echo '<div class="section info">';
    echo '<h2>6️⃣ JS/CSS ファイルチェック</h2>';
    
    $js_file = dirname(__FILE__) . '/../js/ktp-js.js';
    $css_file = dirname(__FILE__) . '/../css/styles.css';
    
    if (file_exists($js_file)) {
        echo "✅ ktp-js.js ファイルが存在します<br>";
        
        $js_content = file_get_contents($js_file);
        if (strpos($js_content, 'staff-chat') !== false || strpos($js_content, 'toggle-staff-chat') !== false) {
            echo "&nbsp;&nbsp;✅ スタッフチャット関連のJavaScriptが含まれています<br>";
        } else {
            echo "&nbsp;&nbsp;⚠️ スタッフチャット関連のJavaScriptが見つかりません<br>";
        }
    } else {
        echo "❌ ktp-js.js ファイルが見つかりません<br>";
    }
    
    if (file_exists($css_file)) {
        echo "✅ styles.css ファイルが存在します<br>";
        
        $css_content = file_get_contents($css_file);
        if (strpos($css_content, 'staff-chat') !== false) {
            echo "&nbsp;&nbsp;✅ スタッフチャット関連のCSSが含まれています<br>";
        } else {
            echo "&nbsp;&nbsp;⚠️ スタッフチャット関連のCSSが見つかりません<br>";
        }
    } else {
        echo "❌ styles.css ファイルが見つかりません<br>";
    }
    echo '</div>';
    
    ?>
    
    <div class="section success">
        <h2>🎯 デバッグ完了</h2>
        <p>上記の結果を確認して、問題の原因を特定してください。</p>
        <ul>
            <li>すべてのクラスとファイルが正しく読み込まれているか</li>
            <li>データベーステーブルが存在し、データがあるか</li>
            <li>ユーザー権限が適切か</li>
            <li>HTML生成が正常に動作しているか</li>
        </ul>
    </div>
    
</body>
</html>
