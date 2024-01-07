<?php
// WordPressの環境を読み込む
require_once( $_SERVER['DOCUMENT_ROOT'] . '/ktp/wp-load.php' );

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    global $wpdb;

    // フォームデータの受け取り
    $name = $_POST['name'];
    $email = $_POST['email'];

    // テーブル名の設定（プレフィックスを含む）
    $table_name = $wpdb->prefix . 'users'; // 'users'はあなたのテーブル名に置き換えてください

    // データを挿入
    $result = $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'email' => $email
        ),
        array(
            '%s',
            '%s'
        )
    );

    // 結果の確認
    if ($result) {
        echo "新規データを登録しました";
    } else {
        echo "エラー: " . $wpdb->last_error;
    }
}

// デバッグ情報の出力
echo "<p>リクエストメソッド: " . $_SERVER["REQUEST_METHOD"] . "</p>";
echo "<p>フォームアクションURL: " . esc_html(plugins_url('submit.php', __FILE__)) . "</p>";
echo "<p>フォームデータ:</p>";
echo "<pre>";
var_dump($_POST);
echo "</pre>";

?>
