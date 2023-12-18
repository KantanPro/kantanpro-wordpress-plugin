<?php


class Kantan_List_Class{

    public function render() {
        // 必要な処理を実装する
    }

    public $name;

    public function __construct() {
        $this->$name;
        // add_action( 'get_header', 'my_setcookie');
        // add_action('');
        // add_filter('');
    }
    
    // -----------------------------
    // テーブル作成
    // -----------------------------

    function Create_Table( $name ){
        
        global $wpdb;
        global $my_table_version;
        $my_table_version = '1.0.1'; // 更新する場合はバージョンを変更
        $table_name = $wpdb->prefix . 'ktp_' . $name; // テーブル名を設定
        $charset_collate = $wpdb->get_charset_collate(); // 文字コードを設定

        // テーブル名またはテーブルバージョンを変更した場合にdbDelta($sql)が実行される
        if ($wpdb->get_var("show tables like '$table_name'") != $table_name || get_option('my_table_version') !== $my_table_version) {
            $sql = $wpdb->prepare("CREATE TABLE " . $table_name . " (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                time BIGINT(11) DEFAULT '0' NOT NULL,
                name TINYTEXT NOT NULL,
                text TEXT NOT NULL,
                url VARCHAR(55) NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;");

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql); // テーブル作成実行
            add_option( 'ktp_'.$name.'_table_version', $my_table_version ); // wp_optionsにテーブルバージョンを登録する 
            update_option( 'ktp_'.$name.'_table_version', $my_table_version ); // wp_optionsにテーブルバージョンをアップデートする
        }
    
    }

    // -----------------------------
    // テーブルの操作（更新・追加・削除）
    // -----------------------------

    function Update_Table( $name ) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $name;
        
        // POSTデーター受信
        $data_id = $_POST['data_id'];
        $query_post = $_POST['query_post'];
        $data_name = $_POST['data_name'];
        $text = $_POST['text'];
                
        // 更新
        if( $query_post == 'update' ){
            
            $wpdb->update( 
                $table_name, 
                array( 
                    'name' => $data_name,
                    'text' => $text,
                ),
                array( 'ID' => $data_id ), 
                array( 
                    '%s',	// name
                    '%s'	// text
                ), 
                array( '%d' ) 
            
            );
        }
        
        // 追加
        elseif( $query_post == 'insert' ) {
            $wpdb->insert( 
                $table_name, 
                array( 
                    'time' => current_time( 'mysql' ),
                    'name' => $data_name,
                    'text' => $text,
                ) 
            );
        }
        
        // 削除
        elseif( $query_post == 'delete' ) {
            $wpdb->delete(
                $table_name,
                array(
                    'id' => $data_id
                ),
                array(
                    '%d'
                )
            );
        }
        
        // エラー処理
        else {
            // $query_postがないよ
            // echo 'NG';
        }

    }


}

?>