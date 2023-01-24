<?php

class Kantan_Supplier_Class{

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

    // -----------------------------
    // テーブルの表示
    // -----------------------------

    function View_Table( $name ) {

        // echo $name;
        // exit;


        global $wpdb;

        // ログインユーザー情報を取得
        global $current_user;
        $login_user = $current_user->nickname;

        // ログアウトのリンク
        $logout_link = wp_logout_url();
        
        // リスト表示設定（ページャー）
        $query_limit = '10'; //表示範囲
        $query_num = '0'; //スタート位置
        $query_range = $query_num . ',' . $query_limit;
        $table_name = $wpdb->prefix . 'ktp_' . $name;

        // リスト表示
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY `id` ASC LIMIT $query_range");
        $post_row = $wpdb->get_results($query);
        $results_h = <<<END
        <div class="data_contents">
            <div class="data_list_box">
            <h3>■ 協力会社リスト($query_range)</h3>
        END;
        if( $post_row ){
            foreach ($post_row as $row){
                $id = esc_html($row->id);
                $time = esc_html($row->time);
                $data_name = esc_html($row->name);
                $text = esc_html($row->text);
                $results[] = <<<END
                <div class="data_list_item">$id : $time : $data_name : $text : <a href="?tab_name=$name&data_id=$id"> → </a></div>
                END;
            }
        } else {
            $results[] = <<<END
            <div class="data_list_item">NO DATA！</div>
            END;
        }
        $results_f = '</div>';
        $data_list = $results_h . implode( $results ) . $results_f;

        // 詳細表示(GET)
        if(isset( $_GET['data_id'] )){
            $query_id = $_GET['data_id'];
        } else {
            $query_id = $wpdb->insert_id;
        }
        
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY `id` = $query_id");
        $post_row = $wpdb->get_results($query);
        foreach ($post_row as $row){
            $data_id = esc_html($row->id);
            $time = esc_html($row->time);
            $data_name = esc_html($row->name);
            $text = esc_html($row->text);
        }

        // 表題
        $data_title = <<<END
        <div class="data_detail">
            <h3>■ 協力会社の詳細（ID: $data_id  TIME: $time ）</h3>
        END;

        // フォーム表示
        $data_forms = <<<END
                <div class="box">
                    <form method="post" action="">
                    <p><label> 名&emsp;&emsp;前：</label> <input type="text" name="data_name" value="$data_name"></p>
                    <p><label> テキスト：</label> <input type="text" name="text" value="$text"></p>
                    <input type="hidden" name="query_post" value="update">
                    <input type="hidden" name="data_id" value="$data_id">
                    <div class="submit_button"><input type="submit" name="send_post" value="更新"></div>
                    </form>
                    <form method="post" action="">
                    <input type="hidden" name="data_id" value="$data_id">
                    <input type="hidden" name="query_post" value="delete">
                    <div class="submit_button"><input type="submit" name="send_post" value="削除"></div>
                    </form>
                </div>
                <div class="box">
                    <h3>■ 協力会社追加</h3>
                    <form method="post" action="">
                    <p><label> 名&emsp;&emsp;前：</label> <input type="text" name="data_name" value=""></p>
                    <p><label> テキスト：</label> <input type="text" name="text" value=""></p>
                    <input type="hidden" name="query_post" value="insert">
                    <div class="submit_button"><input type="submit" name="send_post" value="追加"></div>
                    </form>
                </div>
        END;

        // DIV閉じ
        $div_end = <<<END
            </div>
        </div>
        END;

        // 表示するもの
        $content = $data_list . $data_title . $data_forms . $div_end;
        return $content;

        // POSTデータをクリア
        unset($_POST);

    }

}

?>