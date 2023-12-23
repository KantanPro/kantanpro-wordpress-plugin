<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // フォームデータの受け取り
    $name = $_POST['name'];
    $email = $_POST['email'];

    // データベース接続とデータの登録
    // データベース情報
    $servername = "localhost";
    $username = "username";
    $password = "password";
    $dbname = "myDB";

    // データベースに接続
    $conn = new mysqli($servername, $username, $password, $dbname);

    // 接続チェック
    if ($conn->connect_error) {
        die("接続失敗: " . $conn->connect_error);
    }

    // SQL文でデータを挿入
    $sql = "INSERT INTO users (name, email) VALUES ('$name', '$email')";

    if ($conn->query($sql) === TRUE) {
        echo "新規データを登録しました";
    } else {
        echo "エラー: " . $sql . "<br>" . $conn->error;
    }

    // 接続を閉じる
    $conn->close();
}
?>
