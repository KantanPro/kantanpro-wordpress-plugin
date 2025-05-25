<?php
// パスをデバッグするためのファイル
echo "プラグインのベースURL: " . plugins_url('', __FILE__) . "<br>";
echo "プラグインのディレクトリ: " . plugin_dir_path(__FILE__) . "<br>";
echo "プラグインのURL: " . plugin_dir_url(__FILE__) . "<br>";

// デフォルト画像のURL
echo "デフォルト画像URL（現在の実装）: " . plugin_dir_url(__FILE__) . 'images/default/no-image-icon.jpg' . "<br>";
echo "デフォルト画像URL（includes/ から相対パスを使用）: " . plugin_dir_url(dirname(__FILE__) . '/includes') . '../images/default/no-image-icon.jpg' . "<br>";

// プラグイン情報
$plugin_data = get_plugin_data(__FILE__);
echo "プラグイン名: " . $plugin_data['Name'] . "<br>";
echo "プラグインのスラッグ: " . plugin_basename(__FILE__) . "<br>";

// アクティブなプラグインのリスト
echo "アクティブなプラグイン:<br>";
$active_plugins = get_option('active_plugins');
print_r($active_plugins);
