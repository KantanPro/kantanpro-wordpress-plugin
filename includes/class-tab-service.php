<?php
// OK

class Kntan_Service_Class{

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
        $my_table_version = '1.0.0'; // 更新する場合はバージョンを変更
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
        
        global $wpdb;

        // ログインユーザー情報を取得
        global $current_user;
        $login_user = $current_user->nickname;

        // ログアウトのリンク
        $logout_link = wp_logout_url();

        // リストヘッダ表示
        $results_h = <<<END
        <div class="data_contents">
        <div class="data_list_box">
        <h3>■ 商品リスト</h3>
        END;
        
        //
        // リスト表示
        //

        $table_name = $wpdb->prefix . 'ktp_' . $name;

        //表示範囲
        $query_limit = '15';

        //スタート位置を決める
        $page_stage = $_GET['page_stage'];
        $page_start = $_GET['page_start'];
        $flg = $_GET['flg'];
        
        if( $page_stage == '' ){
            $page_start = 0;
        }

        $query_range = $page_start . ',' . $query_limit;
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY 'id' ASC LIMIT $query_range");
        $post_row = $wpdb->get_results($query);
        if( $post_row ){
            foreach ($post_row as $row){
                $id = esc_html($row->id);
                $time = esc_html($row->time);
                $data_name = esc_html($row->name);
                $text = esc_html($row->text);
                $results[] = <<<END
                <div class="data_list_item">$id : $time : $data_name : $text : <a href="?tab_name=$name&data_id=$id&page_start=$page_start&page_stage=$page_stage"> → </a></div>
                END;
            }
            $query_max_num = $wpdb->num_rows;
            // $post_num = count($post_row);
        } else {
            
            // データーがない場合
            $results[] = <<<END
            <div class="data_list_item">商品を追加してください。</div>
            END;
        }
        
        // ページネーションリンク
        $post_num = count($post_row); // 現在の項目数（可変）
        $page_buck = ''; // 前のスタート位置
        $flg = ''; // ステージが２回目以降かどうかを判別するフラグ
        // 現在表示中の詳細
        if(isset( $_GET['data_id'] )){
            $data_id = $_GET['data_id'];
        } else {
            $data_id = $wpdb->insert_id;
        }
        
        if( !$page_stage || $page_stage == 1 ){
            if( $post_num >= $query_limit ){ $page_stage = 2; $page_buck = $post_num - $page_start; $page_buck_stage = 1; } else { $page_stage = 3;  $page_buck_stage = 2; }
            $page_start ++;
            $page_next_start = $query_max_num;
            $flg ++;
            $results_f = <<<END
            <div class="pagination">
            END;
            // $page_buck_stage = 2;
            if( $page_start > 1 && $flg >= 2 ){
                $page_buck_stage = 2;
            } else {
                $page_buck_stage = 1;
            }
            if( $post_num >= $query_limit ){
                $results_f .= <<<END
                $page_start ~ $query_max_num &emsp;<a href="?tab_name=$name&data_id=$data_id&page_start=$page_next_start&page_stage=$page_stage&flg=$flg"> > </a>
                </div>
                END;
            } else {
                $results_f .= <<<END
                &emsp; $page_start ~ $query_max_num
                </div>
                END;
            }
            $page_start = $page_start + $query_limit;

        } elseif( $page_stage == 2 ) {
            if( $post_num >= $query_limit ){ $page_stage = 2; $page_buck = $post_num - $page_start; $page_buck_stage = 1; } else { $page_stage = 3; $page_buck_stage = 2; }
            $page_buck = $page_start - $query_limit;
            $page_next_start = $page_start + $post_num;
            $query_max_num = $query_max_num + $page_start;
            $page_start ++;
            $flg = 2;
            $results_f = <<<END
            <div class="pagination">
            END;
            if( $page_start > 1 && $flg >= 2 ){
                $page_buck_stage = 2;
                $results_f .= <<<END
                <a href="?tab_name=$name&data_id=$data_id&page_start=$page_buck&page_stage=$page_buck_stage&flg=$flg"> < </a>
                END;
            } else {
                $page_buck_stage = 1;
            }
            if( $post_num >= $query_limit ){
                $results_f .= <<<END
                &emsp; $page_start ~ $query_max_num &emsp;<a href="?tab_name=$name&data_id=$data_id&page_start=$page_next_start&page_stage=$page_stage&flg=$flg"> > </a>
                </div>
                END;
                $flg ++;
            } else {
                $results_f .= <<<END
                &emsp; リストの最後です。
                </div>
                END;
            }
        } elseif( $page_stage == 3 ) {
            if( $post_num >= $query_limit ){ $page_buck = $post_num - $page_start; $page_buck_stage = 2; } else { $page_buck_stage = 1; }
            // $page_buck = $page_start - $post_num;
            // $page_stage = 2;
            $results_f = <<<END
            <div class="pagination">
            <a href="?tab_name=$name&data_id=$data_id&page_start=$page_buck&page_stage=$page_buck_stage&flg=$flg"> < </a>
            </div>
            END;
        }

        $results_f .= '</div>';
        $data_list = $results_h . implode( $results ) . $results_f;

        // 詳細表示(GET)
        if(isset( $_GET['data_id'] )){
            $query_id = $_GET['data_id'];
        } else {
            $query_id = $wpdb->insert_id;
        }
        
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY 'id' = $query_id");
        $post_row = $wpdb->get_results($query);
        foreach ($post_row as $row){
            $data_id = esc_html($row->id);
            $time = esc_html($row->time);
            $data_name = esc_html($row->name);
            $text = esc_html($row->text);
        }

        // 表題
        $data_title = <<<END
        <div class="data_detail_box">
            <h3>■ 商品の詳細（ID: $data_id  TIME: $time ）</h3>
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
                    <h3>■ 商品追加</h3>
                    <form method="post" action="">
                    <p><label> 名&emsp;&emsp;前：</label> <input type="text" name="data_name" value=""></p>
                    <p><label> テキスト：</label> <input type="text" name="text" value=""></p>
                    <input type="hidden" name="query_post" value="insert">
                    <input type="hidden" name="data_id" value="">
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