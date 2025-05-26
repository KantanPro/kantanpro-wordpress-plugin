<?php

if (!class_exists('Kntan_Client_Relationship_Class')) {
class Kntan_Client_Relationship_Class {

    /**
     * 顧客関連テーブルを作成する
     * 顧客間の関係（複製元と複製先）を記録するテーブル
     */
    public static function create_relationship_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_client_relationship';
        $charset_collate = $wpdb->get_charset_collate();

        // テーブルが存在しない場合は作成
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $sql = "CREATE TABLE {$table_name} (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                source_client_id MEDIUMINT(9) NOT NULL,
                duplicated_client_id MEDIUMINT(9) NOT NULL,
                time BIGINT(11) DEFAULT '0' NOT NULL,
                UNIQUE KEY id (id)
            ) {$charset_collate};";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * 顧客関連データを登録（複製関係）
     * 
     * @param int $source_id 複製元の顧客ID
     * @param int $duplicated_id 複製先の顧客ID
     * @return bool 保存が成功したかどうか
     */
    public static function register_duplication($source_id, $duplicated_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_client_relationship';

        // テーブルが存在しない場合は作成
        self::create_relationship_table();

        // 現在のUNIXタイムスタンプ
        $timestamp = time();

        // データ挿入
        $result = $wpdb->insert(
            $table_name,
            [
                'source_client_id' => $source_id,
                'duplicated_client_id' => $duplicated_id,
                'time' => $timestamp
            ],
            [
                '%d',
                '%d',
                '%d'
            ]
        );

        error_log("KTPWP Debug: 顧客複製関係を記録: 元ID={$source_id}, 複製ID={$duplicated_id}, 結果=" . ($result ? '成功' : '失敗'));
        
        return $result !== false;
    }

    /**
     * 指定された顧客IDに紐づく最新の複製顧客IDを取得
     * 
     * @param int $client_id 元となる顧客ID
     * @return int|null 複製された顧客ID（見つからない場合はnull）
     */
    public static function get_latest_duplicated_client($client_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_client_relationship';

        // テーブルが存在しない場合は作成
        self::create_relationship_table();

        // 指定された顧客IDを元に最新の複製顧客IDを取得
        $duplicated = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT duplicated_client_id FROM {$table_name} WHERE source_client_id = %d ORDER BY time DESC LIMIT 1",
                $client_id
            )
        );

        if ($duplicated) {
            error_log("KTPWP Debug: 元ID={$client_id}の最新の複製IDを取得: {$duplicated->duplicated_client_id}");
            return (int)$duplicated->duplicated_client_id;
        }

        error_log("KTPWP Debug: 元ID={$client_id}の複製IDは見つかりませんでした");
        return null;
    }

    /**
     * 指定された複製顧客IDに紐づく元の顧客IDを取得
     * 
     * @param int $duplicated_id 複製された顧客ID
     * @return int|null 元の顧客ID（見つからない場合はnull）
     */
    public static function get_source_client($duplicated_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_client_relationship';

        // テーブルが存在しない場合は作成
        self::create_relationship_table();

        // 指定された複製顧客IDを元に、元の顧客IDを取得
        $source = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT source_client_id FROM {$table_name} WHERE duplicated_client_id = %d ORDER BY time DESC LIMIT 1",
                $duplicated_id
            )
        );

        if ($source) {
            error_log("KTPWP Debug: 複製ID={$duplicated_id}の元のIDを取得: {$source->source_client_id}");
            return (int)$source->source_client_id;
        }

        error_log("KTPWP Debug: 複製ID={$duplicated_id}の元IDは見つかりませんでした");
        return null;
    }
}
}

// プラグイン初期化時に関係テーブルを作成
add_action('init', function() {
    if (class_exists('Kntan_Client_Relationship_Class')) {
        Kntan_Client_Relationship_Class::create_relationship_table();
    }
});

?>
