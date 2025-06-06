<?php
/**
 * WordPress デバッグログテスト
 */

// WordPress環境を読み込み
define('WP_USE_THEMES', false);
define('ABSPATH', '/Users/kantanpro/Local Sites/kantanpro-local-site/app/public/');

require_once ABSPATH . 'wp-config.php';

// デバッグモードを確認
echo "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'TRUE' : 'FALSE') . "\n";
echo "WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'TRUE' : 'FALSE') . "\n";

// ログファイルパスを確認
$log_path = WP_CONTENT_DIR . '/debug.log';
echo "Log file path: " . $log_path . "\n";
echo "Log file exists: " . (file_exists($log_path) ? 'YES' : 'NO') . "\n";
echo "Log file writable: " . (is_writable(dirname($log_path)) ? 'YES' : 'NO') . "\n";

// テストログメッセージを書き込み
error_log('TEST MESSAGE: WordPress debug logging is working!');

// ファイルに直接書き込みをテスト
file_put_contents($log_path, "[" . date('d-M-Y H:i:s UTC') . "] DIRECT WRITE TEST: File write is working!\n", FILE_APPEND | LOCK_EX);

echo "Test messages written. Check the log file.\n";
