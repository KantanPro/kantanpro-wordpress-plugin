<?php
/**
 * KTPWP設定ページテスト用スクリプト
 * このファイルはWordPressのプラグイン管理画面からKTPWP設定ページをテストするためのガイドです。
 * 
 * 以下の手順でテストを行ってください：
 * 
 * 1. WordPressにログインし、管理画面に移動します
 * 2. 左側のメニューから「KTPWP設定」をクリックします
 * 3. 以下の各設定項目を順番に確認・テストします
 */

// テスト項目リスト
$test_items = [
    '基本表示テスト' => [
        '設定ページが表示されるか',
        '「メール設定」セクションが表示されるか',
        '「SMTP設定」セクションが表示されるか',
        '「ライセンス設定」セクションが表示されるか',
    ],
    
    'メール設定テスト' => [
        '自社メールアドレスが入力できるか',
        'メールアドレス形式のバリデーションが機能するか',
        '無効な形式のメールアドレスを入力するとエラーになるか',
    ],
    
    'SMTP設定テスト' => [
        'SMTPホストが入力できるか',
        'SMTPポートが入力できるか',
        'SMTPユーザーが入力できるか',
        'SMTPパスワードが入力できるか',
        '暗号化方式が選択できるか（なし/SSL/TLS）',
        '送信者名が入力できるか',
    ],
    
    'ライセンス設定テスト' => [
        'アクティベーションキーが入力できるか',
        'アクティベーションキーが保存されるか',
    ],
    
    '保存機能テスト' => [
        '設定を保存ボタンをクリックすると設定が保存されるか',
        '保存後に「設定を保存しました」メッセージが表示されるか',
    ],
    
    'テストメール送信テスト' => [
        'テストメール送信ボタンをクリックするとメール送信処理が実行されるか',
        '送信成功時に成功メッセージが表示されるか',
        '送信失敗時にエラーメッセージが表示されるか',
    ],
];

// ===================================================
// 以下はCHECKLIST出力用のコード（ブラウザでアクセスするとチェックリストが表示されます）
// ===================================================

if (isset($_SERVER['HTTP_HOST'])) {
    // ブラウザでアクセスされた場合、チェックリストを表示
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>KTPWP設定ページテストチェックリスト</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            h1 { color: #2271b1; }
            h2 { color: #1d2327; margin-top: 30px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
            ul { list-style-type: none; padding-left: 10px; }
            li { margin-bottom: 10px; }
            input[type="checkbox"] { margin-right: 10px; }
            .instructions { background: #f0f6fc; padding: 15px; border-left: 4px solid #2271b1; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <h1>KTPWP設定ページテストチェックリスト</h1>
        
        <div class="instructions">
            <p>以下のチェックリストを使用して、KTPWP設定ページの機能をテストしてください。</p>
            <p>WordPressの管理画面で「KTPWP設定」メニューをクリックし、各項目を順番に確認していきます。</p>
        </div>';
        
    foreach ($test_items as $category => $items) {
        echo "<h2>{$category}</h2>
        <ul>";
        foreach ($items as $item) {
            echo "<li><input type='checkbox' id='" . md5($item) . "'><label for='" . md5($item) . "'>{$item}</label></li>";
        }
        echo "</ul>";
    }
    
    echo '<h2>その他の気づき</h2>
    <textarea style="width: 100%; height: 100px;"></textarea>
    
    </body>
    </html>';
}
?>
