<?php

class Kantan_Import_data_Class{

    public function import_csv($file_path) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'imported_data';

        // テーブルが存在しない場合は作成
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$table_name} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                postal_code varchar(10) NOT NULL,
                prefecture tinytext NOT NULL,
                city tinytext NOT NULL,
                address text NOT NULL,
                building tinytext NOT NULL,
                customer tinytext NOT NULL,
                user_name tinytext NOT NULL,
                PRIMARY KEY  (id)
            ) {$charset_collate};";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // CSVファイルを開く
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            // ヘッダー行をスキップ
            fgetcsv($handle);
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'postal_code' => $data[0],
                        'prefecture' => $data[1],
                        'city' => $data[2],
                        'address' => $data[3],
                        'building' => $data[4],
                        'customer' => $data[5],
                        'user_name' => $data[6],
                    ),
                    array(
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s'
                    )
                );
            }
            fclose($handle);
        }
    }
}

?>
