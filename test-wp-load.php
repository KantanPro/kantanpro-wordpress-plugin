<?php
/**
 * WordPress基本読み込みテスト
 */

echo "Starting WordPress load test...\n";

try {
    require_once(__DIR__ . '/../../../wp-load.php');
    echo "WordPress loaded successfully\n";

    echo "ABSPATH: " . (defined('ABSPATH') ? ABSPATH : 'NOT DEFINED') . "\n";
    echo "WP_DEBUG: " . (defined('WP_DEBUG') ? (WP_DEBUG ? 'true' : 'false') : 'NOT DEFINED') . "\n";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";

    // WordPressが正常に初期化されているかチェック
    if (function_exists('is_user_logged_in')) {
        echo "WordPress functions available\n";
    } else {
        echo "WordPress functions NOT available\n";
    }

    // KTPWPプラグインクラスの読み込み状況
    if (class_exists('KTPWP_Main')) {
        echo "KTPWP_Main class loaded\n";
    } else {
        echo "KTPWP_Main class NOT loaded\n";
    }

    if (class_exists('KTPWP_Ajax')) {
        echo "KTPWP_Ajax class loaded\n";
    } else {
        echo "KTPWP_Ajax class NOT loaded\n";
    }

} catch (Exception $e) {
    echo "Exception during WordPress load: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "Error during WordPress load: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "Test complete\n";
