<?php
// プラグイン診断スクリプト

// 標準出力をフラッシュして即時表示
ob_implicit_flush(true);
ob_end_flush();

echo "診断を開始します...\n";

// 現在のPHPバージョンを確認
echo "PHP Version: " . PHP_VERSION . "\n";

// プラグインのディレクトリ構造を確認
$plugin_dir = __DIR__;
echo "Plugin Directory: " . $plugin_dir . "\n";

// 主要なクラスの存在確認
$class_files = [
    'KTP_Settings' => $plugin_dir . '/includes/class-ktp-settings.php',
    'KTP_Upgrade' => $plugin_dir . '/includes/class-ktp-upgrade.php',
    'Main Plugin File' => $plugin_dir . '/kantan-pro-wp.php',
    'Admin Form' => $plugin_dir . '/includes/ktp-admin-form.php'
];

echo "\nChecking files:\n";
foreach ($class_files as $class => $file) {
    echo "Checking: " . $class . " - ";
    if (file_exists($file)) {
        echo "FOUND\n";
    } else {
        echo "NOT FOUND\n";
    }
}

// ファイルの一覧を表示
echo "\nListing plugin directory contents:\n";
$files = scandir($plugin_dir);
foreach ($files as $file) {
    if ($file != "." && $file != "..") {
        echo $file . "\n";
    }
}

echo "\nListing includes directory contents:\n";
$includes_dir = $plugin_dir . '/includes';
if (is_dir($includes_dir)) {
    $files = scandir($includes_dir);
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            echo $file . "\n";
        }
    }
}

echo "\nDiagnostic complete.\n";
