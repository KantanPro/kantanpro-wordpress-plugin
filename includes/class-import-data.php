<?php

class Kantan_Import_data_Class {
    public function import_csv($file_path) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'imported_data';

        // テーブルが存在しない場合は作成
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$table_name} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                company_name tinytext NOT NULL,
                name tinytext NOT NULL,
                email varchar(255) NOT NULL,
                url varchar(255),
                representative_name tinytext,
                phone_number varchar(20),
                postal_code varchar(10),
                prefecture tinytext NOT NULL,
                city tinytext NOT NULL,
                address text NOT NULL,
                building tinytext,
                closing_day tinyint,
                payment_month tinyint,
                payment_day tinyint,
                payment_method tinytext,
                tax_category tinytext,
                memo text,
                category tinytext,
                PRIMARY KEY  (id)
            ) {$charset_collate};";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // CSVファイルを開く
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            // ヘッダー行をスキップ
            fgetcsv($handle);
            $wpdb->query('START TRANSACTION'); // トランザクション開始
            try {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'company_name' => $data[0],
                            'name' => $data[1],
                            'email' => $data[2],
                            'url' => $data[3],
                            'representative_name' => $data[4],
                            'phone_number' => $data[5],
                            'postal_code' => $data[6],
                            'prefecture' => $data[7],
                            'city' => $data[8],
                            'address' => $data[9],
                            'building' => $data[10],
                            'closing_day' => $data[11],
                            'payment_month' => $data[12],
                            'payment_day' => $data[13],
                            'payment_method' => $data[14],
                            'tax_category' => $data[15],
                            'memo' => $data[16],
                            'category' => $data[17],
                        ),
                        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s')
                    );
                }
                $wpdb->query('COMMIT'); // コミット
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK'); // ロールバック
                // エラーログに記録するなどのエラーハンドリング
            }
            fclose($handle);
        } else {
            // ファイルが開けなかった場合のエラーハンドリング
        }
    }
}

function handle_file_upload() {
    if (isset($_FILES['ktp_data_import']) && $_FILES['ktp_data_import']['error'] == UPLOAD_ERR_OK) {
        // アップロードされたファイルを取得
        $uploaded_file = $_FILES['ktp_data_import'];

        // ファイルを保存するディレクトリのパス
        $upload_dir = WP_PLUGIN_DIR . '/kantan-pro-wp/data';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // ファイルを指定されたディレクトリに移動
        $destination_path = $upload_dir . '/' . basename($uploaded_file['name']);
        if (move_uploaded_file($uploaded_file['tmp_name'], $destination_path)) {
            // ファイルの移動に成功した場合、インポート処理を実行
            $importer = new Kantan_Import_data_Class();
            $importer->import_csv($destination_path);

            // 処理が完了したらメッセージを表示
            echo "Import successful.";
        } else {
            // ファイルの移動に失敗した場合
            echo "Failed to move the file.";
        }
    } else {
        // ファイルがアップロードされていない、またはエラーが発生した場合
        echo "File upload failed.";
    }
    exit;
}

// Redirect without using options.php
add_action('admin_init', 'handle_file_upload');

?>