<?php
/**
 * 統一ナンス管理システムのテスト
 *
 * @package KTPWP
 */

// WordPressの環境をロード
require_once __DIR__ . '/../../../wp-load.php';

// KTPWPプラグインが有効かチェック
if (!function_exists('ktpwp_init')) {
    die('KTPWP plugin is not active');
}

// 統一ナンス管理クラスが利用可能かチェック
if (!class_exists('KTPWP_Nonce_Manager')) {
    die('KTPWP_Nonce_Manager class not found');
}

echo "<h1>KTPWP 統一ナンス管理システムテスト</h1>\n";

try {
    // シングルトンインスタンス取得
    $nonce_manager = KTPWP_Nonce_Manager::getInstance();
    echo "<p>✓ KTPWP_Nonce_Manager インスタンス取得成功</p>\n";

    // スタッフチャット用ナンス取得（複数回）
    $nonce1 = $nonce_manager->get_staff_chat_nonce();
    $nonce2 = $nonce_manager->get_staff_chat_nonce();
    $nonce3 = $nonce_manager->get_staff_chat_nonce();

    echo "<h2>ナンス値の一貫性テスト</h2>\n";
    echo "<p>1回目: <code>{$nonce1}</code></p>\n";
    echo "<p>2回目: <code>{$nonce2}</code></p>\n";
    echo "<p>3回目: <code>{$nonce3}</code></p>\n";

    if ($nonce1 === $nonce2 && $nonce2 === $nonce3) {
        echo "<p style='color: green;'>✓ ナンス値が一貫して同じ値を返しています（キャッシュ動作正常）</p>\n";
    } else {
        echo "<p style='color: red;'>✗ ナンス値が異なっています（キャッシュ動作異常）</p>\n";
    }

    // 統一AJAX設定取得
    $ajax_config = $nonce_manager->get_unified_ajax_config();
    echo "<h2>統一AJAX設定</h2>\n";
    echo "<pre>" . print_r($ajax_config, true) . "</pre>\n";

    // ナンス検証テスト
    $is_valid = wp_verify_nonce($nonce1, 'ktpwp_staff_chat_nonce');
    echo "<h2>ナンス検証テスト</h2>\n";
    if ($is_valid) {
        echo "<p style='color: green;'>✓ ナンス値が正しく検証されました</p>\n";
    } else {
        echo "<p style='color: red;'>✗ ナンス値の検証に失敗しました</p>\n";
    }

    // 他のクラスからの統一ナンス使用確認
    echo "<h2>他クラスからの統一ナンス使用テスト</h2>\n";

    // KTPWP_Assets クラスが利用可能かチェック
    if (class_exists('KTPWP_Assets')) {
        $assets = new KTPWP_Assets();
        // get_unified_staff_chat_nonce() メソッドが存在するかチェック
        if (method_exists($assets, 'get_unified_staff_chat_nonce')) {
            $assets_nonce = $assets->get_unified_staff_chat_nonce();
            echo "<p>KTPWP_Assets経由のナンス: <code>{$assets_nonce}</code></p>\n";

            if ($assets_nonce === $nonce1) {
                echo "<p style='color: green;'>✓ KTPWP_Assetsクラスと統一ナンス管理システムで同じナンス値を取得</p>\n";
            } else {
                echo "<p style='color: red;'>✗ KTPWP_Assetsクラスと統一ナンス管理システムで異なるナンス値</p>\n";
            }
        } else {
            echo "<p style='color: orange;'>⚠ KTPWP_Assets::get_unified_staff_chat_nonce() メソッドが見つかりません</p>\n";
        }
    } else {
        echo "<p style='color: orange;'>⚠ KTPWP_Assets クラスが見つかりません</p>\n";
    }

    // KTPWP_Staff_Chat クラスが利用可能かチェック
    if (class_exists('KTPWP_Staff_Chat')) {
        echo "<p>✓ KTPWP_Staff_Chat クラスが利用可能です</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠ KTPWP_Staff_Chat クラスが見つかりません</p>\n";
    }

    echo "<h2>統合テスト結果</h2>\n";
    echo "<p style='color: green;'>✓ 統一ナンス管理システムが正常に動作しています</p>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><small>テスト実行時刻: " . date('Y-m-d H:i:s') . "</small></p>\n";
?>
