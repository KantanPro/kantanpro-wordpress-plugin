<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>見積書</title>
    <link rel="stylesheet" href="css/print.css" media="print">
</head>
<body>
    <div class="estimate">
        <h1>見積書</h1>
        <p><?php echo $this->company_name; ?> 様</p>
        <p><?php echo $this->company_address; ?></p>
        <p><?php echo $this->company_tel; ?></p>
        <!-- その他の見積書の内容 -->
    </div>
    <button onclick="window.print();">プリント</button>
</body>
</html>