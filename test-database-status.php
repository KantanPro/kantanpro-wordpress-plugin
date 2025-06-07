<?php
/**
 * スタッフチャットのデータベース状態確認
 */

// WordPress環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

// ログインチェック
if (!is_user_logged_in()) {
    die('ログインが必要です');
}

echo "<h1>スタッフチャット データベース状態確認</h1>";

global $wpdb;
$table_name = $wpdb->prefix . 'ktp_order_staff_chat';

// テーブル存在確認
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

echo "<h2>テーブル存在確認</h2>";
if ($table_exists) {
    echo "<p>✅ テーブル '{$table_name}' が存在します</p>";

    // テーブル構造確認
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    echo "<h3>テーブル構造:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>フィールド</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column->Field}</td>";
        echo "<td>{$column->Type}</td>";
        echo "<td>{$column->Null}</td>";
        echo "<td>{$column->Key}</td>";
        echo "<td>{$column->Default}</td>";
        echo "<td>{$column->Extra}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // データ件数確認
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    echo "<p>データ件数: <strong>{$count}</strong> 件</p>";

    // サンプルデータ表示
    if ($count > 0) {
        $sample_data = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 5", ARRAY_A);
        echo "<h3>最新データ（最大5件）:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Order ID</th><th>User Display Name</th><th>Message</th><th>Created At</th><th>Is Initial</th></tr>";
        foreach ($sample_data as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['order_id']}</td>";
            echo "<td>" . esc_html($row['user_display_name']) . "</td>";
            echo "<td>" . esc_html(substr($row['message'], 0, 50)) . (strlen($row['message']) > 50 ? '...' : '') . "</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "<td>{$row['is_initial']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} else {
    echo "<p>❌ テーブル '{$table_name}' が存在しません</p>";
}

// KTPWP_Staff_Chat クラスの確認
echo "<h2>KTPWP_Staff_Chat クラス確認</h2>";
if (class_exists('KTPWP_Staff_Chat')) {
    echo "<p>✅ KTPWP_Staff_Chat クラスは読み込まれています</p>";

    try {
        $staff_chat = KTPWP_Staff_Chat::get_instance();
        echo "<p>✅ KTPWP_Staff_Chat インスタンス作成成功</p>";

        // get_messages_after メソッドのテスト
        echo "<h3>get_messages_after メソッドテスト</h3>";
        $test_order_id = 1;
        $messages = $staff_chat->get_messages_after($test_order_id);
        echo "<p>注文ID {$test_order_id} のメッセージ件数: <strong>" . count($messages) . "</strong> 件</p>";

        if (!empty($messages)) {
            echo "<h4>メッセージサンプル:</h4>";
            echo "<pre>" . esc_html(print_r(array_slice($messages, 0, 2), true)) . "</pre>";
        }

    } catch (Exception $e) {
        echo "<p>❌ KTPWP_Staff_Chat インスタンス作成エラー: " . esc_html($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>❌ KTPWP_Staff_Chat クラスが見つかりません</p>";

    // クラスファイルの確認
    $class_file = KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-staff-chat.php';
    if (file_exists($class_file)) {
        echo "<p>クラスファイルは存在します: {$class_file}</p>";
        echo "<p>手動でクラスを読み込み中...</p>";
        require_once $class_file;

        if (class_exists('KTPWP_Staff_Chat')) {
            echo "<p>✅ 手動読み込み後、クラスが利用可能になりました</p>";
        } else {
            echo "<p>❌ 手動読み込み後もクラスが利用できません</p>";
        }
    } else {
        echo "<p>❌ クラスファイルが見つかりません: {$class_file}</p>";
    }
}

// Ajax ハンドラーの登録確認
echo "<h2>Ajax ハンドラー登録確認</h2>";
global $wp_filter;

$get_latest_action = 'wp_ajax_get_latest_staff_chat';
if (isset($wp_filter[$get_latest_action])) {
    echo "<p>✅ '{$get_latest_action}' アクションが登録されています</p>";
    echo "<p>登録されたコールバック数: " . count($wp_filter[$get_latest_action]->callbacks) . "</p>";

    foreach ($wp_filter[$get_latest_action]->callbacks as $priority => $callbacks) {
        echo "<p>優先度 {$priority}:</p>";
        foreach ($callbacks as $callback) {
            if (is_array($callback['function'])) {
                $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                $method = $callback['function'][1];
                echo "<p>  - {$class}::{$method}</p>";
            } else {
                echo "<p>  - " . (is_string($callback['function']) ? $callback['function'] : 'クロージャ') . "</p>";
            }
        }
    }
} else {
    echo "<p>❌ '{$get_latest_action}' アクションが登録されていません</p>";
}

// WordPress設定確認
echo "<h2>WordPress設定確認</h2>";
echo "<p>WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? '有効' : '無効') . "</p>";
echo "<p>DOING_AJAX: " . (defined('DOING_AJAX') && DOING_AJAX ? '有効' : '無効') . "</p>";
echo "<p>現在のユーザー: " . wp_get_current_user()->user_login . " (ID: " . get_current_user_id() . ")</p>";
echo "<p>current_user_can('read'): " . (current_user_can('read') ? '可能' : '不可') . "</p>";

?>
