<?php
/**
 * PHPキャッシュクリアスクリプト
 */

echo "=== PHP Cache Clear Script ===\n";

// オペコードキャッシュをクリア
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "OpCache cleared successfully\n";
    } else {
        echo "Failed to clear OpCache\n";
    }
} else {
    echo "OpCache not available\n";
}

// APCキャッシュをクリア
if (function_exists('apc_clear_cache')) {
    if (apc_clear_cache()) {
        echo "APC cache cleared successfully\n";
    } else {
        echo "Failed to clear APC cache\n";
    }
} else {
    echo "APC cache not available\n";
}

// 現在のPHPバージョンとキャッシュ設定を表示
echo "\nPHP Version: " . phpversion() . "\n";
echo "OpCache enabled: " . (function_exists('opcache_get_status') && opcache_get_status() ? 'Yes' : 'No') . "\n";

if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    if ($status) {
        echo "OpCache status: " . ($status['opcache_enabled'] ? 'Enabled' : 'Disabled') . "\n";
        echo "OpCache hit rate: " . round($status['opcache_statistics']['opcache_hit_rate'], 2) . "%\n";
    }
}

echo "\n=== Cache Clear Complete ===\n";

// タイムスタンプファイルを作成してキャッシュバスターとして使用
$timestamp_file = __DIR__ . '/cache_clear_timestamp.txt';
file_put_contents($timestamp_file, time());
echo "Cache clear timestamp: " . time() . "\n";
