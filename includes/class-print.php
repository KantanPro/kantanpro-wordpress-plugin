<?php

class Print_Class {
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    // Generate HTML
    public function generateHTML() {
        $template = file_get_contents(__DIR__ . '/../template/template.txt');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $allowedExtensions = ['txt'];
            $uploadDir = __DIR__ . '/../template/';
            $uploadFile = $uploadDir . basename($_FILES['templateFile']['name']);
            $fileExtension = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));

            if (in_array($fileExtension, $allowedExtensions) && move_uploaded_file($_FILES['templateFile']['tmp_name'], $uploadFile)) {
                $newUploadFile = $uploadDir . 'template.txt';
                rename($uploadFile, $newUploadFile);
                $template = file_get_contents($newUploadFile);
            //     echo '<script>alert("ファイルが有効で、アップロードされました。");</script>';
            // } else {
            //     echo '<script>alert("アップロードされたファイルは無効です。");</script>';
            }
        }

        // // Display the file upload form
        // <form method="POST" enctype="multipart/form-data">
        //     <input type="file" name="templateFile" accept=".txt">
        //     <button type="submit">Upload</button>
        // </form>

        $replacements = array(
            '_%postal_code%_' => $this->data['postal_code'],
            '_％prefecture％_' => $this->data['prefecture'],
            '_％city％_' => $this->data['city'],
            '_%address%_' => $this->data['address'],
            '_%customer%_' => $this->data['customer'],
            '_%user_name%_' => $this->data['user_name']
        );

        $html = strtr($template, $replacements);

        return $html;
    }

}
// $data = array(
//     'customer' => 'John Doe',
//     'amount' => 1000
// );

// $printEstimate = new Print_Class($data);
// $html = $printEstimate->generateHTML();
// echo $html;

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

?>