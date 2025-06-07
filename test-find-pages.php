<?php
/**
 * KTPWPショートコードを使用しているページを検索
 */

// WordPress環境を読み込み
require_once(__DIR__ . '/../../../wp-load.php');

// ログインチェック
if (!is_user_logged_in()) {
    die('ログインが必要です');
}

echo "<h1>KTPWPショートコード使用ページ検索</h1>";

global $wpdb;

// ショートコードを使用している投稿を検索
$shortcodes = array('kantanAllTab', 'ktpwp_all_tab');
$posts_with_shortcode = array();

foreach ($shortcodes as $shortcode) {
    $posts = $wpdb->get_results($wpdb->prepare("
        SELECT ID, post_title, post_type, post_status, post_content
        FROM {$wpdb->posts}
        WHERE post_content LIKE %s
        AND post_status IN ('publish', 'draft', 'private')
        ORDER BY post_modified DESC
    ", '%[' . $shortcode . '%'), ARRAY_A);

    $posts_with_shortcode = array_merge($posts_with_shortcode, $posts);
}

if ($posts_with_shortcode) {
    echo "<h2>ショートコードを使用している投稿/ページ:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>タイトル</th><th>タイプ</th><th>ステータス</th><th>アクション</th></tr>";

    foreach ($posts_with_shortcode as $post) {
        $post_url = get_permalink($post['ID']);
        $edit_url = admin_url('post.php?post=' . $post['ID'] . '&action=edit');

        echo "<tr>";
        echo "<td>{$post['ID']}</td>";
        echo "<td>" . esc_html($post['post_title']) . "</td>";
        echo "<td>{$post['post_type']}</td>";
        echo "<td>{$post['post_status']}</td>";
        echo "<td>";
        echo "<a href='{$post_url}' target='_blank'>表示</a> | ";
        echo "<a href='{$edit_url}' target='_blank'>編集</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>推奨テスト手順:</h3>";
    echo "<ol>";
    echo "<li>上記のいずれかのページの「表示」リンクをクリック</li>";
    echo "<li>ブラウザの開発者ツール（F12）を開いてコンソールタブを表示</li>";
    echo "<li>スタッフチャットを開いて、メッセージ送信を試行</li>";
    echo "<li>コンソールでエラーメッセージを確認</li>";
    echo "</ol>";

} else {
    echo "<p>❌ ショートコードを使用している投稿/ページが見つかりませんでした</p>";

    // デモページを作成するオプションを提供
    echo "<h2>デモページの作成</h2>";
    echo "<p>テスト用のページを作成してKTPWPプラグインをテストできます。</p>";

    if (isset($_POST['create_demo_page'])) {
        $demo_page_content = '[ktpwp_all_tab]';
        $demo_page = array(
            'post_title'   => 'KTPWP テストページ',
            'post_content' => $demo_page_content,
            'post_status'  => 'publish',
            'post_type'    => 'page'
        );

        $page_id = wp_insert_post($demo_page);

        if ($page_id) {
            $page_url = get_permalink($page_id);
            echo "<p>✅ デモページを作成しました: <a href='{$page_url}' target='_blank'>{$page_url}</a></p>";
        } else {
            echo "<p>❌ デモページの作成に失敗しました</p>";
        }
    } else {
        echo "<form method='post'>";
        echo "<input type='hidden' name='create_demo_page' value='1'>";
        echo "<button type='submit' style='padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px;'>デモページを作成</button>";
        echo "</form>";
    }
}

// 現在有効なプラグインの確認
echo "<h2>プラグイン状態確認</h2>";
$active_plugins = get_option('active_plugins');
$ktpwp_active = in_array('KTPWP/ktpwp.php', $active_plugins);

echo "<p>KTPWP プラグイン状態: " . ($ktpwp_active ? "✅ 有効" : "❌ 無効") . "</p>";

if (!$ktpwp_active) {
    echo "<p>⚠️ KTPWPプラグインが有効化されていません。管理画面のプラグインページで有効化してください。</p>";
}

// JavaScript Ajax設定の確認
echo "<h2>JavaScript Ajax設定確認</h2>";
echo "<p>現在のページでKTPWP Ajax設定を確認:</p>";

echo "<script>";
echo "document.addEventListener('DOMContentLoaded', function() {";
echo "  console.log('=== KTPWP Ajax設定確認 ===');";
echo "  console.log('ajaxurl:', typeof ajaxurl !== 'undefined' ? ajaxurl : '未定義');";
echo "  console.log('ktpwp_ajax:', typeof ktpwp_ajax !== 'undefined' ? ktpwp_ajax : '未定義');";
echo "  console.log('ktpwpDebugMode:', typeof ktpwpDebugMode !== 'undefined' ? ktpwpDebugMode : '未定義');";
echo "  ";
echo "  var resultDiv = document.createElement('div');";
echo "  resultDiv.style.cssText = 'background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 4px; font-family: monospace;';";
echo "  resultDiv.innerHTML = '<h3>JavaScript Ajax設定確認結果:</h3>' +";
echo "    '<p><strong>ajaxurl:</strong> ' + (typeof ajaxurl !== 'undefined' ? ajaxurl : '❌ 未定義') + '</p>' +";
echo "    '<p><strong>ktpwp_ajax:</strong> ' + (typeof ktpwp_ajax !== 'undefined' ? JSON.stringify(ktpwp_ajax, null, 2) : '❌ 未定義') + '</p>' +";
echo "    '<p><strong>ktpwpDebugMode:</strong> ' + (typeof ktpwpDebugMode !== 'undefined' ? ktpwpDebugMode : '❌ 未定義') + '</p>';";
echo "  document.body.appendChild(resultDiv);";
echo "});";
echo "</script>";

?>
