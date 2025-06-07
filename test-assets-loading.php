<?php
// WordPress環境を読み込み
require_once '../../../wp-config.php';

// 管理画面フラグを設定
define('WP_ADMIN', true);

// ログイン状態を確認
if (!is_user_logged_in()) {
    echo "ログインが必要です\n";
    exit;
}

echo "=== Assets クラステスト ===\n";

// クラスの存在確認
echo "KTPWP_Assets クラス存在: " . (class_exists('KTPWP_Assets') ? 'YES' : 'NO') . "\n";

// インスタンス取得
if (class_exists('KTPWP_Assets')) {
    $assets = KTPWP_Assets::get_instance();
    echo "インスタンス取得: " . (is_object($assets) ? 'SUCCESS' : 'FAILED') . "\n";

    // 手動でadmin_enqueue_scriptsフックを実行
    echo "\n=== 手動フック実行テスト ===\n";
    if (method_exists($assets, 'enqueue_admin_assets')) {
        $hook_suffix = 'admin_page_ktpwp-order-list';
        echo "Hook実行: {$hook_suffix}\n";
        $assets->enqueue_admin_assets($hook_suffix);
        echo "admin_enqueue_scripts実行完了\n";

        // スクリプト状態確認
        echo "ktp-js登録状態: " . (wp_script_is('ktp-js', 'registered') ? 'REGISTERED' : 'NOT_REGISTERED') . "\n";
        echo "ktp-js待機状態: " . (wp_script_is('ktp-js', 'enqueued') ? 'ENQUEUED' : 'NOT_ENQUEUED') . "\n";

        // インラインスクリプト確認
        global $wp_scripts;
        if (isset($wp_scripts->registered['ktp-js'])) {
            $script_data = $wp_scripts->registered['ktp-js'];
            echo "依存関係: " . implode(', ', $script_data->deps) . "\n";

            if (isset($wp_scripts->registered['ktp-js']->extra['after'])) {
                echo "インラインスクリプト: " . count($wp_scripts->registered['ktp-js']->extra['after']) . " 個\n";
                foreach ($wp_scripts->registered['ktp-js']->extra['after'] as $inline) {
                    echo "  - " . substr($inline, 0, 100) . "...\n";
                }
            } else {
                echo "インラインスクリプト: なし\n";
            }
        }

        // 手動でoutput_ajax_configを実行
        echo "\n=== 手動AJAX設定出力テスト ===\n";
        if (method_exists($assets, 'output_ajax_config')) {
            ob_start();
            $assets->output_ajax_config();
            $output = ob_get_clean();
            echo "AJAX設定出力:\n" . $output . "\n";
        }

    } else {
        echo "enqueue_admin_assetsメソッドが存在しません\n";
    }
} else {
    echo "KTPWP_Assetsクラスが見つかりません\n";
}

echo "\n=== WordPressスクリプト状態 ===\n";
global $wp_scripts;
if (isset($wp_scripts->queue)) {
    echo "エンキュー待ちスクリプト: " . implode(', ', $wp_scripts->queue) . "\n";
} else {
    echo "エンキュー待ちスクリプト: なし\n";
}
?>
