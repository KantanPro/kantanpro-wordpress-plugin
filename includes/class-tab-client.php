<?php

class Kntan_Client_Class{

    public $name;

    public function __construct() {
        $this->$name;
        // add_action( 'get_header', 'my_setcookie');
        // add_action('');
        // add_filter('');
    }
    
    // クライアントテーブル作成
    function Client_Table_Create(){
        
        // テーブルのバージョン管理
        global $wpdb;
        global $my_client_table_version;
        $my_client_table_version = '1.0.1'; // 更新する場合はバージョンを変更

        // テーブル名を設定
        $table_name = $wpdb->prefix. 'ktpwp_client';

        // 文字コードを設定
        $charset_collate = $wpdb->get_charset_collate();

        // テーブル名またはテーブルバージョンを変更した場合にdbDelta($sql)が実行される
        if ($wpdb->get_var("show tables like '$table_name'") != $table_name || get_option('my_client_table_version') !== $my_client_table_version) {
            $sql = $wpdb->prepare("CREATE TABLE " . $table_name . " (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                time BIGINT(11) DEFAULT '0' NOT NULL,
                name TINYTEXT NOT NULL,
                text TEXT NOT NULL,
                url VARCHAR(55) NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;");

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            // テーブル更新実行
            dbDelta($sql);
            
            // wp_optionsにテーブルバージョンを登録する
            add_option( 'my_client_table_version', $my_client_table_version );

            // wp_optionsにテーブルバージョンをアップデートする
            update_option( 'my_client_table_version', $my_client_table_version );
        }
    
    }
    
    // フォームから値を受信しレコードを更新または追加する
    function Client_Table_Data() {
        global $wpdb;
        $table_name = $wpdb->prefix. 'ktpwp_client';
        
        // POSTデーター受信
        $client_id = $_POST['client_id'];
        $query_post = $_POST['query_post'];
        $client_name = $_POST['client_name'];
        $text = $_POST['text'];
        
        // // GETデーター受信
        // $client_id = $_GET['client_id'];
        // $query_post = $_GET['query_post'];
        // $client_name = $_GET['client_name'];
        // $text = $_GET['text'];
        
        // 更新
        if( $query_post == 'update' ){
            
            // // クッキーでクライアントIDを取得
            // $client_id = $_COOKIE['ktp_client_id'];

            $wpdb->update( 
                $table_name, 
                array( 
                    'name' => $client_name,
                    'text' => $text,
                ),
                array( 'ID' => $client_id ), 
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
                    'name' => $client_name,
                    'text' => $text,
                ) 
            );

        }
        
        // 削除
        elseif( $query_post == 'delete' ) {
            $wpdb->delete(
                $table_name,
                array(
                    'id' => $client_id
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

    // 表示する
    function Client_Table_View( $name ) {

        global $wpdb;

        // ログインユーザー情報を取得
        global $current_user;
        $login_user = $current_user->nickname;

        // ログアウトのリンク
        $logout_link = wp_logout_url();
        
        // テーブルデータを表示する（ページャー）
        $query_limit = '20'; //表示範囲
        $query_num = '0'; //スタート位置
        $query_range = $query_num . ',' . $query_limit;
        $table_name = $wpdb->prefix . 'ktpwp_client';

        // クライアントリスト表示
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY `id` ASC LIMIT $query_range");
        $post_row = $wpdb->get_results($query);
        $results_h = <<<END
        <div class="client_contents">
            <div class="client_list_box">
            <h3>■ 顧客リスト($query_range)</h3>
        END;
        foreach ($post_row as $row){
            $id = esc_html($row->id);
            $time = esc_html($row->time);
            $client_name = esc_html($row->name);
            $text = esc_html($row->text);
            $results[] = <<<END
            <div class="client_list_item">$id : $time : $client_name : $text : <a href="?tab_name=$name&client_id=$id"> → </a></div>
            END;
        }
        $results_f = '</div>';
        $client_list = $results_h . implode( $results ) . $results_f;

        // 任意のIDからクライアント情報を取得(GET)
        if(isset( $_GET['client_id'] )){
            $query_id = $_GET['client_id'];
            // function my_setcookie() {
            //     // IDをクッキーに保存（１０年間保存します）
            //     setcookie( 'ktp_client_id', $query_id, time() + (20 * 365 * 24 * 60 * 60), '/' );
            //     // タブポジションをクッキーに保存（１０年間保存します）
            //     setcookie( 'ktp_tab_position', $name, time() + (20 * 365 * 24 * 60 * 60), '/' );
            // }
        } else {
            $query_id = $wpdb->insert_id;
        }

        // 詳細表示
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY `id` = $query_id");
        $post_row = $wpdb->get_results($query);
        foreach ($post_row as $row){
            $client_id = esc_html($row->id);
            $time = esc_html($row->time);
            $client_name = esc_html($row->name);
            $text = esc_html($row->text);
        }

        // 表題
        $client_title = <<<END
        <div class="client_detail">
            <h3>■ 顧客の詳細（ID: $client_id  TIME: $time ）</h3>
        END;

        // フォーム表示
        $client_forms = <<<END
                <div class="box">
                    <form method="post" action="">
                    <p><label> 名　　前：</label> <input type="text" name="client_name" value="$client_name"></p>
                    <p><label> テキスト：</label> <input type="text" name="text" value="$text"></p>
                    <input type="hidden" name="query_post" value="update">
                    <input type="hidden" name="client_id" value="$client_id">
                    <div class="submit_button"><input type="submit" name="send_post" value="更新"></div>
                    </form>
                    <form method="post" action="">
                    <input type="hidden" name="client_id" value="$client_id">
                    <input type="hidden" name="query_post" value="delete">
                    <div class="submit_button"><input type="submit" name="send_post" value="削除"></div>
                    </form>
                </div>
                <div class="box">
                    <h3>■ 顧客追加</h3>
                    <form method="post" action="">
                    <p><label> 名　　前：</label> <input type="text" name="client_name" value=""></p>
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
        $content = $client_list . $client_title . $client_forms . $div_end;
        return $content;

        // POSTデータをクリア
        unset($_POST);

    }

}

?>