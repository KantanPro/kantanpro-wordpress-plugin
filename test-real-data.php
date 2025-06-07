<?php
/**
 * 実際のオーダーデータ確認
 */

// WordPress環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

// ログインチェック
if (!is_user_logged_in()) {
    die('ログインが必要です');
}

echo "<h1>実際のオーダーデータ確認</h1>";

global $wpdb;

// オーダーテーブルの確認
$order_table = $wpdb->prefix . 'ktp_order';
$chat_table = $wpdb->prefix . 'ktp_order_staff_chat';

echo "<h2>オーダーテーブル確認</h2>";
$order_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $order_table));

if ($order_exists) {
    echo "<p>✅ オーダーテーブル '{$order_table}' が存在します</p>";

    // オーダー数を確認
    $order_count = $wpdb->get_var("SELECT COUNT(*) FROM {$order_table}");
    echo "<p>オーダー総数: <strong>{$order_count}</strong> 件</p>";

    if ($order_count > 0) {
        // 最新のオーダーを表示
        $recent_orders = $wpdb->get_results("SELECT id, project_name, created_at FROM {$order_table} ORDER BY created_at DESC LIMIT 5", ARRAY_A);

        echo "<h3>最新のオーダー（最大5件）:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>プロジェクト名</th><th>作成日</th><th>チャットテスト</th></tr>";

        foreach ($recent_orders as $order) {
            $test_url = "http://kantanpro2.local/wp-content/plugins/KTPWP/test-pollnew-standalone.php?order_id=" . $order['id'];
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>" . esc_html($order['project_name']) . "</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "<td><a href='{$test_url}' target='_blank'>テスト</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p>❌ オーダーテーブル '{$order_table}' が存在しません</p>";
}

echo "<h2>チャットテーブル確認</h2>";
$chat_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $chat_table));

if ($chat_exists) {
    echo "<p>✅ チャットテーブル '{$chat_table}' が存在します</p>";

    // チャットメッセージ数を確認
    $chat_count = $wpdb->get_var("SELECT COUNT(*) FROM {$chat_table}");
    echo "<p>チャットメッセージ総数: <strong>{$chat_count}</strong> 件</p>";

    if ($chat_count > 0) {
        // オーダーIDごとのメッセージ数
        $chat_by_order = $wpdb->get_results("
            SELECT order_id, COUNT(*) as message_count, MAX(created_at) as last_message
            FROM {$chat_table}
            GROUP BY order_id
            ORDER BY last_message DESC
            LIMIT 10
        ", ARRAY_A);

        echo "<h3>オーダー別チャットメッセージ数:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>オーダーID</th><th>メッセージ数</th><th>最終メッセージ</th><th>テスト</th></tr>";

        foreach ($chat_by_order as $chat) {
            $test_url = "http://kantanpro2.local/wp-content/plugins/KTPWP/test-pollnew-standalone.php?order_id=" . $chat['order_id'];
            echo "<tr>";
            echo "<td>{$chat['order_id']}</td>";
            echo "<td>{$chat['message_count']}</td>";
            echo "<td>{$chat['last_message']}</td>";
            echo "<td><a href='{$test_url}' target='_blank'>テスト</a></td>";
            echo "</tr>";
        }
        echo "</table>";

        // 最新のチャットメッセージをサンプル表示
        $recent_chats = $wpdb->get_results("
            SELECT order_id, user_display_name, message, created_at, is_initial
            FROM {$chat_table}
            ORDER BY created_at DESC
            LIMIT 3
        ", ARRAY_A);

        echo "<h3>最新チャットメッセージ（3件）:</h3>";
        foreach ($recent_chats as $chat) {
            echo "<div style='border: 1px solid #ccc; margin: 10px 0; padding: 10px;'>";
            echo "<strong>オーダーID:</strong> {$chat['order_id']}<br>";
            echo "<strong>ユーザー:</strong> " . esc_html($chat['user_display_name']) . "<br>";
            echo "<strong>メッセージ:</strong> " . esc_html($chat['message']) . "<br>";
            echo "<strong>送信時刻:</strong> {$chat['created_at']}<br>";
            echo "<strong>初期メッセージ:</strong> " . ($chat['is_initial'] ? 'はい' : 'いいえ') . "<br>";
            echo "</div>";
        }
    }
} else {
    echo "<p>❌ チャットテーブル '{$chat_table}' が存在しません</p>";
}

// 実際のWordPress投稿/ページでKTPWPが動作している場所を確認
echo "<h2>KTPWP動作ページ確認</h2>";
$posts_with_ktpwp = $wpdb->get_results("
    SELECT ID, post_title, post_type, post_status
    FROM {$wpdb->posts}
    WHERE post_content LIKE '%ktp%'
       OR post_content LIKE '%ktpwp%'
       OR post_title LIKE '%ktp%'
    ORDER BY post_modified DESC
    LIMIT 10
", ARRAY_A);

if ($posts_with_ktpwp) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>タイトル</th><th>タイプ</th><th>ステータス</th><th>URL</th></tr>";

    foreach ($posts_with_ktpwp as $post) {
        $post_url = get_permalink($post['ID']);
        echo "<tr>";
        echo "<td>{$post['ID']}</td>";
        echo "<td>" . esc_html($post['post_title']) . "</td>";
        echo "<td>{$post['post_type']}</td>";
        echo "<td>{$post['post_status']}</td>";
        echo "<td><a href='{$post_url}' target='_blank'>表示</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>KTPWP関連のコンテンツが見つかりませんでした</p>";
}

?>
