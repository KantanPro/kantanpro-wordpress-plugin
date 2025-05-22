<?php

if (!class_exists('Kntan_Setting_Class')) {
class Kntan_Setting_Class {

    // ...existing code...

    public function Create_Table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_setting';

        // テーブルが存在しない場合のみ作成
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                email_address varchar(255) DEFAULT '' NOT NULL,
                tax_rate varchar(255) DEFAULT '' NOT NULL,
                closing_date varchar(255) DEFAULT '' NOT NULL,
                invoice varchar(255) DEFAULT '' NOT NULL,
                bank_account varchar(255) DEFAULT '' NOT NULL,
                my_company_content longtext DEFAULT '' NOT NULL,
                template_content longtext DEFAULT '' NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public function Update_Table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_setting';

        // カラム一覧取得（小文字化して厳密比較）
        $columns = $wpdb->get_col("DESC $table_name", 0);
        $columns_lower = array_map(function($col){ return strtolower(trim($col)); }, $columns);

        // idカラムの存在をINFORMATION_SCHEMAでも二重チェック
        $db_name = DB_NAME;
        $id_exists = in_array('id', $columns_lower);
        if (!$id_exists) {
            $id_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'id'",
                    $db_name, $table_name
                )
            ) > 0;
        }

        // idカラム追加は必ず単独で実行（AUTO_INCREMENTなしで追加）
        if (!$id_exists) {
            // 念のため再取得
            $columns = $wpdb->get_col("DESC $table_name", 0);
            $columns_lower = array_map(function($col){ return strtolower(trim($col)); }, $columns);
            if (!in_array('id', $columns_lower)) {
                $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN id mediumint(9) NOT NULL");
                if ($result === false) {
                    return;
                }
                // カラム追加後に再取得
                $columns = $wpdb->get_col("DESC $table_name", 0);
                $columns_lower = array_map(function($col){ return strtolower(trim($col)); }, $columns);
            }
        }

        // idカラムのAUTO_INCREMENT属性の確認と付与
        // 念のため再取得
        $columns = $wpdb->get_col("DESC $table_name", 0);
        $columns_lower = array_map(function($col){ return strtolower(trim($col)); }, $columns);
        if (in_array('id', $columns_lower)) {
            $id_column = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'id'");
            if ($id_column && isset($id_column->Extra) && strpos((string)$id_column->Extra, 'auto_increment') === false) {
                $wpdb->query("ALTER TABLE $table_name MODIFY id mediumint(9) NOT NULL AUTO_INCREMENT");
            }
        }

        // 他カラムの存在チェック（id以外のみまとめて追加）
        $fields = [
            'email_address' => "varchar(255) DEFAULT '' NOT NULL",
            'tax_rate' => "varchar(255) DEFAULT '' NOT NULL",
            'closing_date' => "varchar(255) DEFAULT '' NOT NULL",
            'invoice' => "varchar(255) DEFAULT '' NOT NULL",
            'bank_account' => "varchar(255) DEFAULT '' NOT NULL",
            'my_company_content' => "longtext DEFAULT '' NOT NULL",
            'template_content' => "longtext DEFAULT '' NOT NULL"
        ];
        // 念のため再取得して重複回避
        $columns = $wpdb->get_col("DESC $table_name", 0);
        $columns_lower = array_map(function($col){ return strtolower(trim($col)); }, $columns);
        $add_columns = array();
        foreach ($fields as $field => $def) {
            if (!in_array(strtolower($field), $columns_lower)) {
                $add_columns[] = "ADD COLUMN $field $def";
            }
        }
        if (!empty($add_columns)) {
            // 再取得して重複回避
            $columns = $wpdb->get_col("DESC $table_name", 0);
            $columns_lower = array_map(function($col){ return strtolower(trim($col)); }, $columns);
            $filtered_add_columns = [];
            foreach ($fields as $field => $def) {
                if (!in_array(strtolower($field), $columns_lower)) {
                    $filtered_add_columns[] = "ADD COLUMN $field $def";
                }
            }
            if (!empty($filtered_add_columns)) {
                $sql = "ALTER TABLE $table_name " . implode(', ', $filtered_add_columns);
                $result = $wpdb->query($sql);
                if ($result === false) {
                    return;
                }
            }
        }

        // 主キー追加の存在チェック（必ず単独で実行）
        $primary = $wpdb->get_results("SHOW KEYS FROM $table_name WHERE Key_name = 'PRIMARY'");
        if (empty($primary) && in_array('id', $columns_lower)) {
            // 念のため再取得して重複回避
            $primary = $wpdb->get_results("SHOW KEYS FROM $table_name WHERE Key_name = 'PRIMARY'");
            if (empty($primary)) {
                $wpdb->query("ALTER TABLE $table_name ADD PRIMARY KEY (id)");
            }
        }
    }

    /**
     * テーブル構造の検証
     * @return array 問題がなければ空配列、問題があればエラー内容を配列で返す
     */
    public function Validate_Table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_setting';
        $errors = [];

        // テーブル存在チェック
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $errors[] = 'テーブルが存在しません';
            return $errors;
        }

        // カラム一覧取得
        $columns = $wpdb->get_col("DESC $table_name", 0);
        $columns_lower = array_map(function($col){ return strtolower(trim($col)); }, $columns);

        // 想定カラム
        $fields = [
            'id',
            'email_address',
            'tax_rate',
            'closing_date',
            'invoice',
            'bank_account',
            'my_company_content',
            'template_content'
        ];
        foreach ($fields as $field) {
            if (!in_array(strtolower($field), $columns_lower)) {
                $errors[] = "カラムが存在しません: $field";
            }
        }

        // idカラムのAUTO_INCREMENT属性
        $id_column = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'id'");
        if (!$id_column) {
            $errors[] = 'idカラムが存在しません';
        } elseif (strpos($id_column->Extra, 'auto_increment') === false) {
            $errors[] = 'idカラムにAUTO_INCREMENT属性がありません';
        }

        // 主キー存在チェック
        $primary = $wpdb->get_results("SHOW KEYS FROM $table_name WHERE Key_name = 'PRIMARY'");
        if (empty($primary)) {
            $errors[] = '主キーが設定されていません';
        }

        return $errors;
    }

    // ...existing code...

}} // class_exists
