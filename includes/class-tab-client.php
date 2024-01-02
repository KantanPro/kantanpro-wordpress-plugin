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
    //memo
    //UNIQUE KEY id (id)

    function Create_Table($name) {
        global $wpdb;
        $my_table_version = '1.0.1';
        $table_name = $wpdb->prefix . 'ktp_' . $name;
        $charset_collate = $wpdb->get_charset_collate();
    
        $columns = [
            "id MEDIUMINT(9) NOT NULL AUTO_INCREMENT",
            "time BIGINT(11) DEFAULT '0' NOT NULL",
            "name TINYTEXT",
            "text TEXT",
            "url VARCHAR(55)",
            "company_name VARCHAR(100) NOT NULL DEFAULT '初めてのお客様'",
            "representative_name TINYTEXT",
            "email VARCHAR(100)",
            "phone VARCHAR(20)",
            "postal_code VARCHAR(10)",
            "prefecture TINYTEXT",
            "city TINYTEXT",
            "address TEXT",
            "building TINYTEXT",
            "closing_day TINYTEXT",
            "payment_month TINYTEXT",
            "payment_day TINYTEXT",
            "payment_method TINYTEXT",
            "tax_category VARCHAR(100) NOT NULL DEFAULT '税込'",
            "memo TEXT",
            "UNIQUE KEY id (id)"
        ];
    
        try {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $sql = "CREATE TABLE $table_name (" . implode(", ", $columns) . ") $charset_collate;";
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
                add_option('ktp_' . $name . '_table_version', $my_table_version);
            } else {
                $existing_columns = $wpdb->get_col("DESCRIBE $table_name", 0);
                $missing_columns = array_diff($columns, $existing_columns);
                foreach ($missing_columns as $missing_column) {
                    $wpdb->query("ALTER TABLE $table_name ADD COLUMN $missing_column");
                }
                update_option('ktp_' . $name . '_table_version', $my_table_version);
            }
        } catch (Exception $e) {
            error_log("Error occurred while creating/updating the table: " . $e->getMessage());
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
        $company_name = $_POST['company_name'];
        $user_name = $_POST['user_name'];
        $email = $_POST['email'];
        $url = $_POST['url'];
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
        $memo = $_POST['memo'];
        $text = $_POST['text'];
                    
        // 更新
        if( $query_post == 'update' ){
            
            $wpdb->update( 
                $table_name, 
                array( 
                    'company_name' => $company_name,
                    'name' => $user_name,
                    'email' => $email,
                    'url' => $url,
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
                    'memo' => $memo,
                    'text' => $text,
                ),
                array( 'ID' => $data_id ), 
                array( 
                    '%s',  // name
                    '%s',  // text
                    '%s',  // email
                    '%s',  // url
                    '%s',  // company_name
                    '%s',  // representative_name
                    '%s',  // phone
                    '%s',  // postal_code
                    '%s',  // prefecture
                    '%s',  // city
                    '%s',  // address
                    '%s',  // building
                    '%s',  // closing_day
                    '%s',  // payment_month
                    '%s',  // payment_day
                    '%s',  // payment_method
                    '%s',  // tax_category
                    '%s',  // memo
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
                        'company_name' => $company_name,
                        'name' => $user_name,
                        'email' => $email,
                        'url' => $url,
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
                        'memo' => $memo,
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
            $query_post = 'error';
            echo 'NG';
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
                $company_name = esc_html($row->company_name);
                $user_name = esc_html($row->name);
                $email = esc_html($row->email);
                $url = esc_html($row->url);
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
                $memo = esc_html($row->memo);
                $text = esc_html($row->text);
                $results[] = <<<END
                <div class="data_list_item">$id : $company_name : $user_name : $text : $email : <a href="?tab_name=$name&data_id=$id&page_start=$page_start&page_stage=$page_stage"> → </a></div>
                END;
            }
            $query_max_num = $wpdb->num_rows;
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
            $company_name = esc_html($row->company_name);
            $user_name = esc_html($row->name);
            $email = esc_html($row->email);
            $url = esc_html($row->url);
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
            $memo = esc_html($row->memo);
            $text = esc_html($row->text);
        }

        // 表題
        $data_title = <<<END
        <div class="data_detail_box">
            <h3>■ 顧客の詳細（ID: $data_id  TIME: $time ）</h3>
        END;

        // フォーム表示
        $fields = [
            '会社名' => ['type' => 'text', 'name' => 'company_name', 'required' => true],
            '名前' => ['type' => 'text', 'name' => 'user_name'],
            'メールアドレス' => ['type' => 'email', 'name' => 'email'],
            'URL' => ['type' => 'text', 'name' => 'url'],
            '代表者名' => ['type' => 'text', 'name' => 'representative_name'],
            '電話番号' => ['type' => 'text', 'name' => 'phone', 'pattern' => '\d*'],
            '郵便番号' => ['type' => 'text', 'name' => 'postal_code'],
            '都道府県' => ['type' => 'text', 'name' => 'prefecture'],
            '市区町村' => ['type' => 'text', 'name' => 'city'],
            '番地' => ['type' => 'text', 'name' => 'address'],
            '建物名' => ['type' => 'text', 'name' => 'building'],
            '締め日' => ['type' => 'date', 'name' => 'closing_day'],
            '支払月' => ['type' => 'date', 'name' => 'payment_month'],
            '支払日' => ['type' => 'date', 'name' => 'payment_day'],
            '支払方法' => ['type' => 'text', 'name' => 'payment_method'],
            '税区分' => ['type' => 'select', 'name' => 'tax_category', 'options' => ['外税', '内税']],
            'メモ' => ['type' => 'textarea', 'name' => 'memo'],
            'テキスト' => ['type' => 'text', 'name' => 'text'],
        ];

        // フォームの値を取得
        $data_forms = ''; // フォームのHTMLコードを格納する変数を初期化

        foreach (['update', 'insert'] as $action) {
            $data_forms .= '<div class="box">'; // フォームを囲む<div>タグの開始タグを追加

            if ($action === 'insert') {
                $data_forms .= '<h3>■ 顧客追加</h3>'; // 顧客追加フォームの見出しを追加
            }

            $data_forms .= "<form method=\"post\" action=\"\">"; // フォームの開始タグを追加

            foreach ($fields as $label => $field) {
                $value = $action === 'update' ? ${$field['name']} : ''; // フォームフィールドの値を取得
                $pattern = isset($field['pattern']) ? " pattern=\"{$field['pattern']}\"" : ''; // バリデーションパターンが指定されている場合は、パターン属性を追加
                $required = isset($field['required']) && $field['required'] ? ' required' : ''; // 必須フィールドの場合は、required属性を追加

                if ($field['type'] === 'textarea') {
                    $data_forms .= "<div class=\"form-group\"><label>{$label}：</label> <textarea name=\"{$field['name']}\"{$pattern}{$required}>{$value}</textarea></div>"; // テキストエリアのフォームフィールドを追加
                } elseif ($field['type'] === 'select') {
                    $options = '';

                    foreach ($field['options'] as $option) {
                        $selected = $value === $option ? ' selected' : ''; // 選択されたオプションを判定し、selected属性を追加
                        $options .= "<option value=\"{$option}\"{$selected}>{$option}</option>"; // オプション要素を追加
                    }

                    $data_forms .= "<div class=\"form-group\"><label>{$label}：</label> <select name=\"{$field['name']}\"{$required}>{$options}</select></div>"; // セレクトボックスのフォームフィールドを追加
                } else {
                    $data_forms .= "<div class=\"form-group\"><label>{$label}：</label> <input type=\"{$field['type']}\" name=\"{$field['name']}\" value=\"{$value}\"{$pattern}{$required}></div>"; // その他のフォームフィールドを追加
                }
            }

            $data_forms .= "<input type=\"hidden\" name=\"query_post\" value=\"{$action}\">"; // フォームのアクションを指定する隠しフィールドを追加
            $data_forms .= "<input type=\"hidden\" name=\"data_id\" value=\"{$data_id}\">"; // データIDを指定する隠しフィールドを追加
            $data_forms .= '<div class="submit_button"><input type="submit" name="send_post" value="更新"></div></form>'; // 更新ボタンを追加

            if ($action === 'update') {
                $data_forms .= "<form method=\"post\" action=\"\"><input type=\"hidden\" name=\"data_id\" value=\"{$data_id}\"><input type=\"hidden\" name=\"query_post\" value=\"delete\"><div class=\"submit_button\"><input type=\"submit\" name=\"send_post\" value=\"削除\"></div></form>"; // 削除ボタンを追加
            }

            $data_forms .= '</div>'; // フォームを囲む<div>タグの終了タグを追加
        }

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