<?php

class Kntan_Client_Class{

    public $name;

    public function __construct() {

    }
    
    // -----------------------------
    // テーブル作成
    // -----------------------------

    // テーブルのカラム    
    //id
    //time
    //name
    //text
    //url
    //company_name
    //representative_name
    //email
    //phone
    //postal_code
    //prefecture
    //city
    //address
    //building
    //closing_day
    //payment_month
    //payment_day
    //payment_method
    //tax_category

    function Create_Table($name) {
        global $wpdb;
        $my_table_version = '1.0.1';
        $table_name = $wpdb->prefix . 'ktp_' . $name;
        $charset_collate = $wpdb->get_charset_collate();
    
        if ($wpdb->get_var("show tables like '$table_name'") != $table_name || get_option('my_table_version') !== $my_table_version) {
            $sql = "CREATE TABLE $table_name (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                time BIGINT(11) DEFAULT '0' NOT NULL,
                name TINYTEXT NOT NULL,
                text TEXT NOT NULL,
                url VARCHAR(55) NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;";
    
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            add_option('ktp_' . $name . '_table_version', $my_table_version);
            update_option('ktp_' . $name . '_table_version', $my_table_version);
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
        $email = $_POST['email'];
        $url = $_POST['url'];
        $company_name = $_POST['company_name'];
        $representative_name = $_POST['representative_name'];
        $phone = $_POST['phone'];
        $postal_code = $_POST['postal_code'];
        $prefecture = $_POST['prefecture'];
        $city = $_POST['city'];
        $address = $_POST['address'];
        $building = $_POST['building'];
        $closing_day = $_POST['closing_day'];
        $payment_month = $_POST['payment_month'];
        $payment_day = $_POST['payment_day'];
        $payment_method = $_POST['payment_method'];
        $tax_category = $_POST['tax_category'];
                    
        // 更新
        if( $query_post == 'update' ){
            
            $wpdb->update( 
                $table_name, 
                array( 
                    'name' => $data_name,
                    'text' => $text,
                    'email' => $email,
                    'url' => $url,
                    'company_name' => $company_name,
                    'representative_name' => $representative_name,
                    'phone' => $phone,
                    'postal_code' => $postal_code,
                    'prefecture' => $prefecture,
                    'city' => $city,
                    'address' => $address,
                    'building' => $building,
                    'closing_day' => $closing_day,
                    'payment_month' => $payment_month,
                    'payment_day' => $payment_day,
                    'payment_method' => $payment_method,
                    'tax_category' => $tax_category,
                ),
                array( 'ID' => $data_id ), 
                array( 
                    '%s',	// name
                    '%s',	// text
                    '%s'	// email
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
                        'email' => $email,
                        'url' => $url,
                        'company_name' => $company_name,
                        'representative_name' => $representative_name,
                        'phone' => $phone,
                        'postal_code' => $postal_code,
                        'prefecture' => $prefecture,
                        'city' => $city,
                        'address' => $address,
                        'building' => $building,
                        'closing_day' => $closing_day,
                        'payment_month' => $payment_month,
                        'payment_day' => $payment_day,
                        'payment_method' => $payment_method,
                        'tax_category' => $tax_category,
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

        // リストヘッダ表示
        $results_h = <<<END
        <div class="data_contents">
        <div class="data_list_box">
        <h3>■ 顧客リスト</h3>
        END;
        
        //
        // リスト表示
        //

        $table_name = $wpdb->prefix . 'ktp_' . $name;

        //表示範囲
        $query_limit = '11';

        //スタート位置を決める
        $page_stage = $_GET['page_stage'];
        $page_start = $_GET['page_start'];
        $flg = $_GET['flg'];
        
        if( $page_stage == '' ){
            $page_start = 0;
        }

        $query_range = $page_start . ',' . $query_limit;
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY `id` ASC LIMIT $query_range");
        $post_row = $wpdb->get_results($query);
        if( $post_row ){
            foreach ($post_row as $row){
                $id = esc_html($row->id);
                $time = esc_html($row->time);
                $data_name = esc_html($row->name);
                $text = esc_html($row->text);
                $email = esc_html($row->email);
                $url = esc_html($row->url);
                $company_name = esc_html($row->company_name);
                $representative_name = esc_html($row->representative_name);
                $phone = esc_html($row->phone);
                $postal_code = esc_html($row->postal_code);
                $prefecture = esc_html($row->prefecture);
                $city = esc_html($row->city);
                $address = esc_html($row->address);
                $building = esc_html($row->building);
                $closing_day = esc_html($row->closing_day);
                $payment_month = esc_html($row->payment_month);
                $payment_day = esc_html($row->payment_day);
                $payment_method = esc_html($row->payment_method);
                $tax_category = esc_html($row->tax_category);
                $results[] = <<<END
                <div class="data_list_item">$id : $time : $data_name : $text : $email : <a href="?tab_name=$name&data_id=$id&page_start=$page_start&page_stage=$page_stage"> → </a></div>
                END;
            }
            $query_max_num = $wpdb->num_rows;
            // $post_num = count($post_row);
        } else {
            $results[] = <<<END
            <div class="data_list_item">NO DATA！</div>
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
                &emsp; DATA END!
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
        
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY `id` = $query_id");
        $post_row = $wpdb->get_results($query);
        foreach ($post_row as $row){
            $data_id = esc_html($row->id);
            $time = esc_html($row->time);
            $data_name = esc_html($row->name);
            $email = esc_html($row->email);
            $text = esc_html($row->text);
            $url = esc_html($row->url);
            $company_name = esc_html($row->company_name);
            $representative_name = esc_html($row->representative_name);
            $phone = esc_html($row->phone);
            $postal_code = esc_html($row->postal_code);
            $prefecture = esc_html($row->prefecture);
            $city = esc_html($row->city);
            $address = esc_html($row->address);
            $building = esc_html($row->building);
            $closing_day = esc_html($row->closing_day);
            $payment_month = esc_html($row->payment_month);
            $payment_day = esc_html($row->payment_day);
            $payment_method = esc_html($row->payment_method);
            $tax_category = esc_html($row->tax_category);
        }

        // 表題
        $data_title = <<<END
        <div class="data_detail_box">
            <h3>■ 顧客の詳細（ID: $data_id  TIME: $time ）</h3>
        END;

        // フォーム表示
        $data_forms = <<<END
        <div class="box">
            <form method="post" action="">
            <p><label> 名　　前：</label> <input type="text" name="data_name" value="$data_name"></p>
            <p><label> メールアドレス：</label> <input type="email" name="email" value="$email"></p>
            <p><label> テキスト：</label> <input type="text" name="text" value="$text"></p>
            <p><label> URL：</label> <input type="text" name="url" value="$url"></p>
            <p><label> 会社名：</label> <input type="text" name="company_name" value="$company_name"></p>
            <p><label> 代表者名：</label> <input type="text" name="representative_name" value="$representative_name"></p>
            <p><label> 電話番号：</label> <input type="text" name="phone" value="$phone"></p>
            <p><label> 郵便番号：</label> <input type="text" name="postal_code" value="$postal_code"></p>
            <p><label> 都道府県：</label> <input type="text" name="prefecture" value="$prefecture"></p>
            <p><label> 市区町村：</label> <input type="text" name="city" value="$city"></p>
            <p><label> 番地：</label> <input type="text" name="address" value="$address"></p>
            <p><label> 建物名：</label> <input type="text" name="building" value="$building"></p>
            <p><label> 締め日：</label> <input type="text" name="closing_day" value="$closing_day"></p>
            <p><label> 支払月：</label> <input type="text" name="payment_month" value="$payment_month"></p>
            <p><label> 支払日：</label> <input type="text" name="payment_day" value="$payment_day"></p>
            <p><label> 支払方法：</label> <input type="text" name="payment_method" value="$payment_method"></p>
            <p><label> 税区分：</label> <input type="text" name="tax_category" value="$tax_category"></p>
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
            <h3>■ 顧客追加</h3>
            <form method="post" action="">
            <p><label> 名　　前：</label> <input type="text" name="data_name" value=""></p>
            <p><label> メールアドレス：</label> <input type="email" name="email" value=""></p>
            <p><label> テキスト：</label> <input type="text" name="text" value=""></p>
            <p><label> URL：</label> <input type="text" name="url" value=""></p>
            <p><label> 会社名：</label> <input type="text" name="company_name" value=""></p>
            <p><label> 代表者名：</label> <input type="text" name="representative_name" value=""></p>
            <p><label> 電話番号：</label> <input type="text" name="phone" value=""></p>
            <p><label> 郵便番号：</label> <input type="text" name="postal_code" value=""></p>
            <p><label> 都道府県：</label> <input type="text" name="prefecture" value=""></p>
            <p><label> 市区町村：</label> <input type="text" name="city" value=""></p>
            <p><label> 番地：</label> <input type="text" name="address" value=""></p>
            <p><label> 建物名：</label> <input type="text" name="building" value=""></p>
            <p><label> 締め日：</label> <input type="text" name="closing_day" value=""></p>
            <p><label> 支払月：</label> <input type="text" name="payment_month" value=""></p>
            <p><label> 支払日：</label> <input type="text" name="payment_day" value=""></p>
            <p><label> 支払方法：</label> <input type="text" name="payment_method" value=""></p>
            <p><label> 税区分：</label> <input type="text" name="tax_category" value=""></p>
            <input type="hidden" name="query_post" value="insert">
            <input type="hidden" name="data_id" value="$data_id">
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