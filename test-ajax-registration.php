<?php
/**
 * AJAX登録確認テスト
 *
 * このファイルでWordPressのAJAXハンドラーが正しく登録されているかをテストします。
 * ブラウザでアクセス: http://kantanpro2.local/wp-content/plugins/KTPWP/test-ajax-registration.php
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
    <title>AJAX登録確認テスト</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>AJAX登録確認テスト</h1>

    <div class="section">
        <h2>1. クラスの存在確認</h2>
        <?php
        $classes_to_check = [
            'KTPWP_Main',
            'KTPWP_Ajax',
            'KTPWP_Staff_Chat',
            'KTPWP_Assets'
        ];

        foreach ($classes_to_check as $class) {
            if (class_exists($class)) {
                echo "<div class='status success'>✓ クラス {$class} は存在します</div>";
            } else {
                echo "<div class='status error'>✗ クラス {$class} が見つかりません</div>";
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>2. グローバルAJAXアクションの確認</h2>
        <?php
        global $wp_filter;

        $ajax_actions_to_check = [
            'wp_ajax_send_staff_chat_message',
            'wp_ajax_get_latest_staff_chat'
        ];

        foreach ($ajax_actions_to_check as $action) {
            if (isset($wp_filter[$action]) && !empty($wp_filter[$action]->callbacks)) {
                echo "<div class='status success'>✓ AJAXアクション {$action} が登録されています</div>";
                echo "<pre>";
                foreach ($wp_filter[$action]->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $callback) {
                        if (is_array($callback['function'])) {
                            $class_name = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                            $method_name = $callback['function'][1];
                            echo "  優先度 {$priority}: {$class_name}::{$method_name}\n";
                        } else {
                            echo "  優先度 {$priority}: {$callback['function']}\n";
                        }
                    }
                }
                echo "</pre>";
            } else {
                echo "<div class='status error'>✗ AJAXアクション {$action} が登録されていません</div>";
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>3. KTPWP_Main インスタンスの確認</h2>
        <?php
        if (class_exists('KTPWP_Main')) {
            try {
                $main_instance = KTPWP_Main::get_instance();
                if ($main_instance) {
                    echo "<div class='status success'>✓ KTPWP_Main インスタンスが取得できました</div>";

                    // プロパティの確認
                    $reflection = new ReflectionClass($main_instance);
                    $properties = $reflection->getProperties();

                    echo "<h3>インスタンスのプロパティ:</h3><pre>";
                    foreach ($properties as $property) {
                        $property->setAccessible(true);
                        $value = $property->getValue($main_instance);
                        echo $property->getName() . ": " . (is_object($value) ? get_class($value) : gettype($value)) . "\n";
                    }
                    echo "</pre>";
                } else {
                    echo "<div class='status error'>✗ KTPWP_Main インスタンスが取得できませんでした</div>";
                }
            } catch (Exception $e) {
                echo "<div class='status error'>✗ KTPWP_Main インスタンス取得エラー: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='status error'>✗ KTPWP_Main クラスが存在しません</div>";
        }
        ?>
    </div>

    <div class="section">
        <h2>4. KTPWP_Ajax インスタンスの確認</h2>
        <?php
        if (class_exists('KTPWP_Ajax')) {
            try {
                $ajax_instance = KTPWP_Ajax::get_instance();
                if ($ajax_instance) {
                    echo "<div class='status success'>✓ KTPWP_Ajax インスタンスが取得できました</div>";

                    // メソッドの確認
                    $reflection = new ReflectionClass($ajax_instance);
                    $methods = $reflection->getMethods();

                    echo "<h3>利用可能なメソッド:</h3><pre>";
                    foreach ($methods as $method) {
                        if ($method->isPublic() && !$method->isConstructor()) {
                            echo $method->getName() . "\n";
                        }
                    }
                    echo "</pre>";
                } else {
                    echo "<div class='status error'>✗ KTPWP_Ajax インスタンスが取得できませんでした</div>";
                }
            } catch (Exception $e) {
                echo "<div class='status error'>✗ KTPWP_Ajax インスタンス取得エラー: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='status error'>✗ KTPWP_Ajax クラスが存在しません</div>";
        }
        ?>
    </div>

    <div class="section">
        <h2>5. WordPress initアクションの確認</h2>
        <?php
        if (isset($wp_filter['init']) && !empty($wp_filter['init']->callbacks)) {
            echo "<div class='status info'>initアクションに登録されているコールバック:</div>";
            echo "<pre>";
            foreach ($wp_filter['init']->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function'])) {
                        $class_name = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                        $method_name = $callback['function'][1];
                        echo "  優先度 {$priority}: {$class_name}::{$method_name}\n";
                    } else {
                        echo "  優先度 {$priority}: {$callback['function']}\n";
                    }
                }
            }
            echo "</pre>";
        }
        ?>
    </div>

    <div class="section">
        <h2>6. 実際のAJAX URLテスト</h2>
        <div class="info">
            <p>以下のURLでAJAXエンドポイントをテストできます:</p>
            <ul>
                <li><a href="<?php echo admin_url('admin-ajax.php?action=send_staff_chat_message'); ?>" target="_blank">send_staff_chat_message</a></li>
                <li><a href="<?php echo admin_url('admin-ajax.php?action=get_latest_staff_chat'); ?>" target="_blank">get_latest_staff_chat</a></li>
            </ul>
            <p><strong>注意:</strong> これらのリンクはGETリクエストなので、実際にはエラーが返されますが、404ではなく400や別のエラーが返されれば、ハンドラーは登録されています。</p>
        </div>
    </div>

    <div class="section">
        <h2>7. AJAX Settings確認</h2>
        <?php
        if (class_exists('KTPWP_Assets')) {
            $assets = KTPWP_Assets::get_instance();
            echo "<div class='status info'>KTPWP_Assets インスタンスが利用可能です</div>";

            // JavaScript用のAJAX設定を取得
            echo "<h3>JavaScript用AJAX設定:</h3>";
            echo "<pre>";
            echo "AJAX URL: " . admin_url('admin-ajax.php') . "\n";
            echo "Staff Chat Nonce: " . wp_create_nonce('staff_chat_nonce') . "\n";
            echo "</pre>";
        } else {
            echo "<div class='status error'>✗ KTPWP_Assets クラスが見つかりません</div>";
        }
        ?>
    </div>

    <script>
    // JavaScriptでもAJAX設定を確認
    if (typeof ktpwp_ajax !== 'undefined') {
        console.log('KTPWP AJAX設定:', ktpwp_ajax);
    } else {
        console.log('KTPWP AJAX設定が見つかりません');
    }
    </script>
</body>
</html>
