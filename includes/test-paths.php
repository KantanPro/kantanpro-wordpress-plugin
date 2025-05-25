<?php
// テスト用スクリプト
// このファイルをブラウザでアクセスして、画像パスの違いを確認

// 現在のファイル（includes/class-tab-service.php）からの相対パスで画像を参照
$method1 = plugin_dir_url(__FILE__) . '../images/default/no-image-icon.jpg';
echo "方法1 (現在の実装): " . $method1 . "<br>";

// プラグインのルートディレクトリを取得して画像を参照
$method2 = plugin_dir_url(dirname(__FILE__)) . 'images/default/no-image-icon.jpg';
echo "方法2 (推奨): " . $method2 . "<br>";

// 生のパスを出力
echo "ファイルパス: " . __FILE__ . "<br>";
echo "ディレクトリパス: " . dirname(__FILE__) . "<br>";
echo "プラグインパス: " . dirname(dirname(__FILE__)) . "<br>";

// 問題のパスも表示
$problem_path = plugin_dir_url('') . 'KTPWP/images/default/no-image-icon.jpg';
echo "以前の問題のあったパス（KTPWP): " . $problem_path . "<br>";

$problem_path2 = plugin_dir_url('') . 'kantan-pro-wp/images/default/no-image-icon.jpg';
echo "以前の問題のあったパス（kantan-pro-wp): " . $problem_path2 . "<br>";
