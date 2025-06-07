<?php
/**
 * スタッフチャット送信機能テスト
 */

// WordPress環境の読み込み
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    die('WordPressの読み込みに失敗しました');
}

echo "<h2>スタッフチャット送信機能テスト</h2>\n";

// 1. クラスの存在確認
echo "<h3>1. クラスの存在確認</h3>\n";
$staff_chat_class_file = KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-staff-chat.php';
if (file_exists($staff_chat_class_file)) {
    require_once $staff_chat_class_file;
    echo "✓ class-ktpwp-staff-chat.php が見つかりました<br>\n";

    if (class_exists('KTPWP_Staff_Chat')) {
        echo "✓ KTPWP_Staff_Chat クラスが存在します<br>\n";
    } else {
        echo "✗ KTPWP_Staff_Chat クラスが見つかりません<br>\n";
    }
} else {
    echo "✗ class-ktpwp-staff-chat.php が見つかりません<br>\n";
}

// 2. AJAXクラスの確認
echo "<h3>2. AJAXクラスの確認</h3>\n";
$ajax_class_file = KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-ajax.php';
if (file_exists($ajax_class_file)) {
    require_once $ajax_class_file;
    echo "✓ class-ktpwp-ajax.php が見つかりました<br>\n";

    if (class_exists('KTPWP_Ajax')) {
        echo "✓ KTPWP_Ajax クラスが存在します<br>\n";

        // AJAXインスタンス取得
        $ajax_instance = KTPWP_Ajax::get_instance();
        echo "✓ KTPWP_Ajax インスタンスを取得しました<br>\n";
    } else {
        echo "✗ KTPWP_Ajax クラスが見つかりません<br>\n";
    }
} else {
    echo "✗ class-ktpwp-ajax.php が見つかりません<br>\n";
}

// 3. ユーザーログイン状態確認
echo "<h3>3. ユーザーログイン状態</h3>\n";
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "✓ ユーザーがログインしています<br>\n";
    echo "ユーザー名: " . $current_user->user_login . "<br>\n";
    echo "ユーザーID: " . $current_user->ID . "<br>\n";
    echo "権限: " . implode(', ', $current_user->roles) . "<br>\n";
} else {
    echo "✗ ユーザーがログインしていません<br>\n";
}

// 4. データベーステーブル確認
echo "<h3>4. データベーステーブル確認</h3>\n";
global $wpdb;
$table_name = $wpdb->prefix . 'ktp_order_staff_chat';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo "✓ テーブル {$table_name} が存在します<br>\n";

    // 最新メッセージを確認
    $latest_messages = $wpdb->get_results(
        "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 3"
    );

    echo "最新のメッセージ（3件）:<br>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>ID</th><th>注文ID</th><th>ユーザー</th><th>メッセージ</th><th>作成日時</th></tr>\n";
    foreach ($latest_messages as $message) {
        echo "<tr>";
        echo "<td>{$message->id}</td>";
        echo "<td>{$message->order_id}</td>";
        echo "<td>{$message->user}</td>";
        echo "<td>" . htmlspecialchars($message->message) . "</td>";
        echo "<td>{$message->created_at}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "✗ テーブル {$table_name} が存在しません<br>\n";
}

// 5. テストメッセージ送信
echo "<h3>5. テストメッセージ送信</h3>\n";
if (is_user_logged_in() && class_exists('KTPWP_Staff_Chat')) {
    $staff_chat = new KTPWP_Staff_Chat();
    $test_order_id = 2; // 既存の注文IDを使用
    $test_message = "テストメッセージ - " . date('Y-m-d H:i:s');

    echo "注文ID: {$test_order_id}<br>\n";
    echo "メッセージ: {$test_message}<br>\n";

    $result = $staff_chat->add_message($test_order_id, $test_message);

    if ($result) {
        echo "✓ メッセージ送信成功<br>\n";

        // 送信後の確認
        $latest_message = $wpdb->get_row(
            "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 1"
        );

        if ($latest_message && $latest_message->message === $test_message) {
            echo "✓ データベースに正常に保存されました<br>\n";
            echo "保存されたメッセージ: " . htmlspecialchars($latest_message->message) . "<br>\n";
        } else {
            echo "✗ データベースに正常に保存されませんでした<br>\n";
        }
    } else {
        echo "✗ メッセージ送信失敗<br>\n";
    }
} else {
    echo "ログインが必要、またはクラスが利用できません<br>\n";
}

// 6. AJAX nonce確認
echo "<h3>6. AJAX nonce確認</h3>\n";
if (class_exists('KTPWP_Ajax')) {
    $ajax_instance = KTPWP_Ajax::get_instance();

    // nonceを生成
    $staff_chat_nonce = wp_create_nonce('ktpwp_staff_chat_nonce');
    echo "生成されたnonce: {$staff_chat_nonce}<br>\n";

    // nonce検証
    $verify_result = wp_verify_nonce($staff_chat_nonce, 'ktpwp_staff_chat_nonce');
    echo "nonce検証結果: " . ($verify_result ? '成功' : '失敗') . "<br>\n";
}

echo "<h3>テスト完了</h3>\n";
?>
