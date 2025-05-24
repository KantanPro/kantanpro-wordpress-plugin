<?php

if (!class_exists('Kntan_Client_Class')) {
class Kntan_Client_Class {

    public function __construct() {

    }
    
    // -----------------------------
    // テーブル作成
    // -----------------------------
    

    // クッキーの設定
    function Set_Cookie($name) {
        $cookie_name = 'ktp_' . $name . '_id';
        if (isset($_COOKIE[$cookie_name])) {
            $query_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
        } elseif (isset($_GET['data_id'])) {
            $query_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
        } else {
            $query_id = 1;
        }
    }

    function Create_Table($tab_name) {
        global $wpdb;
        $my_table_version = '1.0.1';
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $charset_collate = $wpdb->get_charset_collate();
    
        $columns_def = [
            "id MEDIUMINT(9) NOT NULL AUTO_INCREMENT",
            "time BIGINT(11) DEFAULT '0' NOT NULL",
            "name TINYTEXT",
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
            "search_field TEXT", // 検索用フィールドを追加
            "frequency INT NOT NULL DEFAULT 0", // 頻度
            "category VARCHAR(100) NOT NULL DEFAULT '一般'",
            "UNIQUE KEY id (id)"
        ];
    
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (" . implode(", ", $columns_def) . ") $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            add_option('ktp_' . $tab_name . '_table_version', $my_table_version);
        } else {
            // カラム名のみ取得
            $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name", 0);
            // カラム定義からカラム名だけ抽出
            $def_column_names = [];
            foreach ($columns_def as $def) {
                if (preg_match('/^([a-zA-Z0-9_]+)/', $def, $m)) {
                    $def_column_names[] = $m[1];
                }
            }
            // 足りないカラムだけ追加
            foreach ($def_column_names as $i => $col_name) {
                if (!in_array($col_name, $existing_columns)) {
                    // UNIQUE KEYは別途処理
                    if ($col_name === 'UNIQUE') continue;
                    $def = $columns_def[$i];
                    $wpdb->query("ALTER TABLE $table_name ADD COLUMN $def");
                }
            }
            update_option('ktp_' . $tab_name . '_table_version', $my_table_version);
        }

        // UNIQUE KEY追加（存在チェック）
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name}");
        $has_unique_id = false;
        foreach ($indexes as $idx) {
            if ($idx->Key_name === 'id' && $idx->Non_unique == 0) {
                $has_unique_id = true;
                break;
            }
        }
        if (!$has_unique_id) {
            $wpdb->query("ALTER TABLE {$table_name} ADD UNIQUE (id)");
        }
    }

    // -----------------------------
    // テーブルの操作（更新・追加・削除・検索）
    // -----------------------------

    function Update_Table( $tab_name) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;

        // テーブル名にロックをかける
        $wpdb->query("LOCK TABLES {$table_name} WRITE;");
        
        // POSTデーター受信
        $data_id = $_POST['data_id'] ?? '';
        $query_post = $_POST['query_post'] ?? '';
        $company_name = $_POST['company_name'] ?? '';
        $user_name = $_POST['user_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $url = $_POST['url'] ?? '';
        $representative_name = $_POST['representative_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $postal_code = $_POST['postal_code'] ?? '';
        $prefecture = $_POST['prefecture'] ?? '';
        $city = $_POST['city'] ?? '';
        $address = $_POST['address'] ?? '';
        $building = $_POST['building'] ?? '';
        $closing_day = $_POST['closing_day'] ?? '';
        $payment_month = $_POST['payment_month'] ?? '';
        $payment_day = $_POST['payment_day'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';
        $tax_category = $_POST['tax_category'] ?? '';
        $memo = $_POST['memo'] ?? '';
        $category = $_POST['category'] ?? '';
        $page_stage = $_POST['page_stage'] ?? '';
        $page_start = $_POST['page_start'] ?? '';
        $flg = $_POST['flg'] ?? '';
        
        $search_field_value = implode(', ', [
            $data_id,
            current_time( 'mysql' ),
            $company_name,
            $user_name,
            $email,
            $url,
            $representative_name,
            $phone,
            $postal_code,
            $prefecture,
            $city,
            $address,
            $building,
            $closing_day,
            $payment_month,
            $payment_day,
            $payment_method,
            $tax_category,
            $memo,
            $category
        ]);

        if ($data_id === 0) {
            $last_id_query = "SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1";
            $last_id_result = $wpdb->get_row($last_id_query);
            if ($last_id_result) {
                $data_id = $last_id_result->id;
            }
        }

        // 削除
        if ($query_post == 'delete' && $data_id > 0) {
            $wpdb->delete(
                $table_name,
                array(
                    'id' => $data_id
                ),
                array(
                    '%d'
                )
            );

            // ロックを解除する
            $wpdb->query("UNLOCK TABLES;");

            // リダイレクト
            // データ削除後に表示するデータIDを適切に設定
            $next_id_query = "SELECT id FROM {$table_name} WHERE id > {$data_id} ORDER BY id ASC LIMIT 1";
            $next_id_result = $wpdb->get_row($next_id_query);
            if ($next_id_result) {
                $next_data_id = $next_id_result->id;
            } else {
                $prev_id_query = "SELECT id FROM {$table_name} WHERE id < {$data_id} ORDER BY id DESC LIMIT 1";
                $prev_id_result = $wpdb->get_row($prev_id_query);
                $next_data_id = $prev_id_result ? $prev_id_result->id : 0;
            }
            $cookie_name = 'ktp_' . $name . '_id';
            $action = 'update';
            $url = '?tab_name='. $tab_name . '&data_id=' . $next_data_id . '&query_post=' . $action;
$cookie_name = 'ktp_' . $tab_name . '_id';
            setcookie($cookie_name, $next_data_id, time() + (86400 * 30), "/"); // 30日間有効
            header("Location: {$url}");
            exit;
        }    
        
        // 更新
        elseif( $query_post == 'update' ){

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
                    'category' => $category,
                    'search_field' => $search_field_value,
                ),
                    array( 'id' => $data_id ), 
                    array( 
                        '%s',  // company_name
                        '%s',  // name
                        '%s',  // email
                        '%s',  // url
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
                        '%s',  // category
                        '%s',  // search_field
                    ),
                    array( '%d' ) 
            );

            // $data_idの取得方法を確認
            $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
            if($data_id > 0){
                // 頻度の値を+1する
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $table_name SET frequency = frequency + 1 WHERE id = %d",
                        $data_id
                    )
                );
            } else {
                // $data_idが不正な場合のエラーハンドリング
                // 例: IDが指定されていない、または不正な値の場合
                error_log('Invalid or missing data_id in Update_Table function');
            }

            // ロックを解除する
            $wpdb->query("UNLOCK TABLES;");
            
        }

        // 検索
        elseif( $query_post == 'search' ){

        // SQLクエリを準備（search_fieldを検索対象にする）
        $search_query = $_POST['search_query'];
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE search_field LIKE %s", '%' . $wpdb->esc_like($search_query) . '%'));

            // 検索結果が1つある場合の処理
            if (count($results) == 1) {

                // 検索結果のIDを取得
                $id = $results[0]->id;
                
                // 頻度の値を+1する
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $table_name SET frequency = frequency + 1 WHERE ID = %d",
                        $id
                    )
                );

                 // 検索後に更新モードにする
                 $action = 'update';
                 $data_id = $id;
                 $url = '?tab_name='. $tab_name . '&data_id=' . $data_id . '&query_post=' . $action;
                 header("Location: {$url}");

            }

            // 検索結果が複数ある場合の処理
            elseif (count($results) > 1) {
                // 検索結果を表示するHTMLを初期化
                $search_results_html = "<div class='data_contents'><div class='search_list_box'><h3>■ 検索結果が複数あります！</h3><ul>";

                // 検索結果のリストを生成
                foreach ($results as $row) {
                    $id = esc_html($row->id);
                    $email = esc_html($row->email);
                    $company_name = esc_html($row->company_name);
                    $category = esc_html($row->category);
                    
                    // 各検索結果に対してリンクを設定
                    $search_results_html .= "<li style='text-align:left;'><a href='?tab_name={$tab_name}&data_id={$id}&query_post=update' style='text-align:left;'>ID：{$id} 会社名：{$company_name} カテゴリー：{$category}</a></li>";
                }
                
                // HTMLを閉じる
                $search_results_html .= "</ul></div></div>";

                // JavaScriptに渡すために、検索結果のHTMLをエスケープ
                $search_results_html_js = json_encode($search_results_html);

                // JavaScriptでポップアップを表示
                echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    var searchResultsHtml = $search_results_html_js;
                    var popup = document.createElement('div');
                    popup.innerHTML = searchResultsHtml;
                    popup.style.position = 'fixed';
                    popup.style.top = '50%';
                    popup.style.left = '50%';
                    popup.style.transform = 'translate(-50%, -50%)';
                    popup.style.backgroundColor = '#fff';
                    popup.style.padding = '20px';
                    popup.style.zIndex = '1000';
                    popup.style.width = '80%';
                    popup.style.maxWidth = '600px';
                    popup.style.border = '1px solid #ccc';
                    popup.style.borderRadius = '5px';
                    popup.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
                    document.body.appendChild(popup);
                    // ポップアップを閉じるためのボタンを追加
                    var closeButton = document.createElement('button');
                    closeButton.textContent = '閉じる';
                    closeButton.style.fontSize = '0.8em';
                    closeButton.style.color = 'black'; // 文字をもう少し黒く
                    closeButton.style.display = 'block';
                    closeButton.style.margin = '10px auto 0';
                    closeButton.style.padding = '10px';
                    closeButton.style.backgroundColor = '#cdcccc'; // 背景は薄い緑
                    closeButton.style.borderRadius = '5px'; // 角を少し丸く
                    closeButton.style.borderColor = '#999'; // ボーダーカラーをもう少し明るく
                    closeButton.onclick = function() {
                        document.body.removeChild(popup);
                        // 元の検索モードに戻るために特定のURLにリダイレクト
                        location.href = '?tab_name={$tab_name}&query_post=search';
                    };
                    popup.appendChild(closeButton);
                });
                </script>";
            }

            // 検索結果が0件の場合の処理
            else {
                // JavaScriptを使用してポップアップ警告を表示
                echo "<script>
                alert('検索結果がありません！');
                window.location.href='?tab_name={$tab_name}&query_post=search';
                </script>";
            }

            // ロックを解除する
            $wpdb->query("UNLOCK TABLES;");
            exit;
        }
        
        // 追加
        elseif( $query_post == 'insert' ) {

            $insert_result = $wpdb->insert( 
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
                    'category' => $category,
                    'search_field' => $search_field_value
                ) 
            );
            if($insert_result === false) {
                error_log('Insert error: ' . $wpdb->last_error);
            } else {

                // ロックを解除する
                $wpdb->query("UNLOCK TABLES;");

                // 追加後に更新モードにする
                // リダイレクト
                $action = 'update';
                $data_id = $wpdb->insert_id;
                $url = '?tab_name='. $tab_name . '&data_id=' . $data_id . '&query_post=' . $action;
                header("Location: {$url}");
                exit;
            }

        }
        
        // 複製
        elseif( $query_post == 'duplication' ) {
            // データのIDを取得
            $data_id = $_POST['data_id'];

            // データを取得
            $data = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $data_id", ARRAY_A);

            // 会社名の最後に#を追加
            $data['company_name'] .= '#';

            // IDを削除
            unset($data['id']);

            // 頻度を0に設定
            $data['frequency'] = 0;

            // search_fieldの値を更新
            $data['search_field'] = implode(', ', [
                $data['time'],
                $data['company_name'],
                $data['name'],
                $data['email'],
                $data['url'],
                $data['representative_name'],
                $data['phone'],
                $data['postal_code'],
                $data['prefecture'],
                $data['city'],
                $data['address'],
                $data['building'],
                $data['closing_day'],
                $data['payment_month'],
                $data['payment_day'],
                $data['payment_method'],
                $data['tax_category'],
                $data['memo'],
                $data['category']
            ]);

            // データを挿入
            $insert_result = $wpdb->insert($table_name, $data);
            if($insert_result === false) {
                // エラーログに挿入エラーを記録
                error_log('Duplication error: ' . $wpdb->last_error);
            } else {
                // 挿入成功後の処理
                $new_data_id = $wpdb->insert_id;

                // ロックを解除する
                $wpdb->query("UNLOCK TABLES;");
                
                // 追加後に更新モードにする
                // リダイレクト
                $action = 'update';
                $url = '?tab_name='. $tab_name . '&data_id=' . $new_data_id . '&query_post=' . $action;
                $cookie_name = 'ktp_' . $tab_name . '_id'; // クッキー名を設定
                setcookie($cookie_name, $new_data_id, time() + (86400 * 30), "/"); // クッキーを保存
                header("Location: {$url}");
                exit;
            }
        }
        
        // 
        // 商品画像処理
        // 

        // 画像をアップロード
        elseif ($query_post == 'upload_image') {


            // 画像URLを取得
            $image_processor = new Image_Processor();
            $default_image_url = plugin_dir_url(''). 'kantan-pro-wp/images/default/no-image-icon.jpg';
            $image_url = $image_processor->handle_image($tab_name, $data_id, $default_image_url);

            // echo '$tab_name：'.$tab_name.'<br>$data_id：'.$data_id.'<br>$default_image_url：'.$default_image_url.'<br>$image_url：'.$image_url;
            // exit;

            $wpdb->update(
                $table_name,
                array(
                    'image_url' => $image_url
                ),
                array(
                    'id' => $data_id
                ),
                array(
                    '%s'
                ),
                array(
                    '%d'
                )
            );

            // echo $image_url;
            // exit;

            // リダイレクト
            $url = '?tab_name='. $tab_name . '&data_id=' . $data_id;
            header("Location: {$url}");
            exit;
        }

        // 画像削除：デフォルト画像に戻す
        elseif ($query_post == 'delete_image') {

            // デフォルト画像のURLを設定
            $default_image_url = plugin_dir_url(''). 'kantan-pro-wp/images/default/no-image-icon.jpg';

            $wpdb->update(
                $table_name,
                array(
                    'image_url' => $default_image_url
                ),
                array(
                    'id' => $data_id
                ),
                array(
                    '%s'
                ),
                array(
                    '%d'
                )
            );

            // リダイレクト
            $url = '?tab_name='. $tab_name . '&data_id=' . $data_id;
            header("Location: {$url}");
            exit;
        }

        // どの処理にも当てはまらない場合はロック解除
        else {
            // ロックを解除する
            $wpdb->query("UNLOCK TABLES;");
        }


    }

    
    // -----------------------------
    // テーブルの表示
    // -----------------------------

    function View_Table( $name ) {

        global $wpdb;

        // -----------------------------
        // リスト表示
        // -----------------------------
        
        // テーブル名
        $table_name = $wpdb->prefix . 'ktp_' . $name;
        
        // 表示モードの取得（デフォルトは顧客一覧）
        $view_mode = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'customer_list';
        
        // -----------------------------
        // ページネーションリンク
        // -----------------------------
        
        // 表示範囲
        $query_limit = 5;
        
        // 表示タイトルの設定
        $list_title = ($view_mode === 'order_history') ? '■ 注文履歴（レンジ： ' . $query_limit . ' ）' : '■ 顧客リスト（レンジ： ' . $query_limit . ' ）';
        
        // リスト表示部分の開始
        $results_h = <<<END
        <div class="data_contents">
            <div class="data_list_box">
            <h3>$list_title</h3>
        END;
        
       // スタート位置を決める
       $page_stage = $_GET['page_stage'] ?? '';
       $page_start = $_GET['page_start'] ?? 0;
       $flg = $_GET['flg'] ?? '';
       if ($page_stage == '') {
           $page_start = 0;
       }
       $query_range = $page_start . ',' . $query_limit;

       $query_order_by = 'frequency';

       // 注文履歴モードの場合
       if ($view_mode === 'order_history') {
           // 現在表示中の顧客ID
           $cookie_name = 'ktp_' . $name . '_id';
           if (isset($_GET['data_id'])) {
               $client_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
           } elseif (isset($_COOKIE[$cookie_name])) {
               $client_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
           } else {
               // 最後のIDを取得して表示
               $query = "SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1";
               $last_id_row = $wpdb->get_row($query);
               $client_id = $last_id_row ? $last_id_row->id : 1;
           }
           
           // 受注書テーブル
           $order_table = $wpdb->prefix . 'ktp_order';
           
           // 全データ数を取得（この顧客IDに関連する受注書）
           $total_query = $wpdb->prepare("SELECT COUNT(*) FROM {$order_table} WHERE client_id = %d", $client_id);
           $total_rows = $wpdb->get_var($total_query);
           $total_pages = ceil($total_rows / $query_limit);
           
           // 現在のページ番号を計算
           $current_page = floor($page_start / $query_limit) + 1;
           
           // この顧客の受注書を取得
           $query = $wpdb->prepare(
               "SELECT * FROM {$order_table} WHERE client_id = %d ORDER BY time DESC LIMIT %d, %d", 
               $client_id, $page_start, $query_limit
           );
           
           $order_rows = $wpdb->get_results($query);
           
           // 顧客情報を取得して表示
           $client_query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $client_id);
           $client_data = $wpdb->get_row($client_query);
           
           if ($client_data) {
               $client_name = esc_html($client_data->company_name);
               $client_user_name = esc_html($client_data->name);
               
               // 注文履歴のリストヘッダーを更新
               $results_h = <<<END
               <div class="data_contents">
                   <div class="data_list_box">
                   <h3>■ {$client_name} の注文履歴（担当者：{$client_user_name}）</h3>
               END;
               
               $results = array(); // 結果を格納する配列を初期化
               
               if ($order_rows) {
                   // 進捗ラベル
                   $progress_labels = [
                       1 => '受付中',
                       2 => '見積中',
                       3 => '作成中',
                       4 => '完成未請求',
                       5 => '請求済',
                       6 => '入金済'
                   ];
                   
                   foreach ($order_rows as $order) {
                       $order_id = esc_html($order->id);
                       $project_name = isset($order->project_name) ? esc_html($order->project_name) : '（案件名なし）';
                       $progress = intval($order->progress);
                       $progress_label = isset($progress_labels[$progress]) ? $progress_labels[$progress] : '不明';
                       
                       // 日時フォーマット変換
                       $raw_time = $order->time;
                       $formatted_time = '';
                       if (!empty($raw_time)) {
                           if (is_numeric($raw_time) && strlen($raw_time) >= 10) {
                               $timestamp = (int)$raw_time;
                               $dt = new DateTime('@' . $timestamp);
                               $dt->setTimezone(new DateTimeZone('Asia/Tokyo'));
                           } else {
                               $dt = date_create($raw_time, new DateTimeZone('Asia/Tokyo'));
                           }
                           if ($dt) {
                               $week = ['日','月','火','水','木','金','土'];
                               $w = $dt->format('w');
                               $formatted_time = $dt->format('Y/n/j') . '（' . $week[$w] . '）' . $dt->format(' H:i');
                           }
                       }
                       
                       // 受注書の詳細へのリンク
                       $detail_url = add_query_arg('order_id', $order_id, '?tab_name=order');
                       
                       // リスト項目を生成
                       $results[] = <<<END
                       <a href="{$detail_url}" style="display:flex;justify-content:space-between;align-items:center;padding:5px 10px;">
                           <div style="flex:1;">ID: {$order_id} - {$project_name}</div>
                           <div style="width:180px;">{$formatted_time}</div>
                           <div style="width:100px;text-align:center;" class="status-{$progress}">{$progress_label}</div>
                       </a>
                       END;
                   }
               } else {
                   $results[] = <<<END
                   <div class="data_list_item">この顧客の受注データはありません。</div>
                   END;
               }
           } else {
               $results[] = <<<END
               <div class="data_list_item">顧客データが見つかりません。</div>
               END;
           }
       } else {
           // 通常の顧客一覧表示（既存のコード）
           // 全データ数を取得
           $total_query = "SELECT COUNT(*) FROM {$table_name}";
           $total_rows = $wpdb->get_var($total_query);
           $total_pages = ceil($total_rows / $query_limit);

           // 現在のページ番号を計算
           $current_page = floor($page_start / $query_limit) + 1;

           // データを取得
           $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY frequency DESC LIMIT %d, %d", $page_start, $query_limit);
           $post_row = $wpdb->get_results($query);
           
           $results = array(); // 結果を格納する配列を初期化
           
           if ($post_row) {
               foreach ($post_row as $row) {
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
                   $category = esc_html($row->category);
                   $frequency = esc_html($row->frequency);
                   
                   // リスト項目
                   $cookie_name = 'ktp_' . $name . '_id';
                   $results[] = <<<END
                   <a href="?tab_name={$name}&data_id={$id}&page_start={$page_start}&page_stage={$page_stage}" onclick="document.cookie = '{$cookie_name}=' + {$id};">
                   <div class="data_list_item">$id : $company_name : $user_name : $category : $email : 頻度($frequency)</div>
                   </a>
                   END;
               }
           } else {
               $results[] = <<<END
               <div class="data_list_item">データーがありません。</div>
               END;
           }
       }

       $results_f = "<div class=\"pagination\">";

        // ページネーションリンク用の基本パラメータ
        $base_params = [
            'tab_name' => $name,
            'page_stage' => 2,
            'flg' => $flg,
            'view_mode' => $view_mode
        ];
        
        // 注文履歴表示の場合は顧客IDも追加
        if ($view_mode === 'order_history' && isset($client_id)) {
            $base_params['data_id'] = $client_id;
        }
        
        // 最初へリンク
        if ($current_page > 1) {
            $base_params['page_start'] = 0;
            $first_link = '?' . http_build_query($base_params);
            $results_f .= <<<END
            <a href="$first_link">|<</a> 
            END;
        }

        // 前へリンク
        if ($current_page > 1) {
            $base_params['page_start'] = ($current_page - 2) * $query_limit;
            $prev_link = '?' . http_build_query($base_params);
            $results_f .= <<<END
            <a href="$prev_link"><</a>
            END;
        }

        // 現在のページ範囲表示と総数
        $page_end = min($total_rows, $current_page * $query_limit);
        $page_start_display = ($current_page - 1) * $query_limit + 1;
        $results_f .= "<div class='stage'> $page_start_display ~ $page_end / $total_rows</div>";

        // 次へリンク（現在のページが最後のページより小さい場合のみ表示）
        if ($current_page < $total_pages) {
            $base_params['page_start'] = $current_page * $query_limit;
            $next_link = '?' . http_build_query($base_params);
            $results_f .= <<<END
             <a href="$next_link">></a>
            END;
        }

        // 最後へリンク
        if ($current_page < $total_pages) {
            $base_params['page_start'] = ($total_pages - 1) * $query_limit;
            $last_link = '?' . http_build_query($base_params);
            $results_f .= <<<END
             <a href="$last_link">>|</a>
            END;
        }
                        
        $results_f .= "</div></div>";

       $data_list = $results_h . implode( $results ) . $results_f;

        // -----------------------------
        // 詳細表示(GET)
        // -----------------------------

        // 現在表示中の詳細
        $cookie_name = 'ktp_' . $name . '_id';
        if (isset($_GET['data_id'])) {
            $query_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
        } elseif (isset($_COOKIE[$cookie_name])) {
            $query_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
        } else {
            // 最後のIDを取得して表示
            $query = "SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1";
            $last_id_row = $wpdb->get_row($query);
            $query_id = $last_id_row ? $last_id_row->id : 1;
        }

        // データを取得し変数に格納
        $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $query_id);
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
            $category = esc_html($row->category);
            // 受注書作成用のデータを保持
            $order_customer_name = $company_name;
            $order_user_name = $user_name;
        }
        
        // 表示するフォーム要素を定義
        $fields = [
            // 'ID' => ['type' => 'text', 'name' => 'data_id', 'readonly' => true],
            '会社名' => ['type' => 'text', 'name' => 'company_name', 'required' => true, 'placeholder' => '必須 法人名または屋号'],
            '名前' => ['type' => 'text', 'name' => 'user_name', 'placeholder' => '担当者名'],
            'メール' => ['type' => 'email', 'name' => 'email'],
            'URL' => ['type' => 'text', 'name' => 'url', 'placeholder' => 'https://....'],
            '代表者名' => ['type' => 'text', 'name' => 'representative_name', 'placeholder' => '代表者名'],
            '電話番号' => ['type' => 'text', 'name' => 'phone', 'pattern' => '\d*', 'placeholder' => '半角数字 ハイフン不要'],
            '郵便番号' => ['type' => 'text', 'name' => 'postal_code', 'pattern' => '[0-9]*', 'placeholder' => '半角数字 ハイフン不要'],
            '都道府県' => ['type' => 'text', 'name' => 'prefecture'],
            '市区町村' => ['type' => 'text', 'name' => 'city'],
            '番地' => ['type' => 'text', 'name' => 'address'],
            '建物名' => ['type' => 'text', 'name' => 'building'],
            '締め日' => ['type' => 'select', 'name' => 'closing_day', 'options' => ['5日', '10日', '15日', '20日', '25日', '末日', 'なし'], 'default' => 'なし'],
            '支払月' => ['type' => 'select', 'name' => 'payment_month', 'options' => ['今月', '翌月', '翌々月', 'その他'], 'default' => 'その他'],
            '支払日' => ['type' => 'select', 'name' => 'payment_day', 'options' => ['即日', '5日', '10日', '15日', '20日', '25日', '末日'], 'default' => '即日'],
            '支払方法' => ['type' => 'select', 'name' => 'payment_method', 'options' => ['銀行振込（後）','銀行振込（前）', 'クレジットカード', '現金集金'], 'default' => '銀行振込（前）'],
            '税区分' => ['type' => 'select', 'name' => 'tax_category', 'options' => ['外税', '内税'], 'default' => '内税'],
            'メモ' => ['type' => 'textarea', 'name' => 'memo'],
            'カテゴリー' => [
                'type' => 'text',
                'name' => 'category',
                'options' => '一般',
                'suggest' => true,
            ],
        ];
        
        $action = isset($_POST['query_post']) ? $_POST['query_post'] : 'update';// アクションを取得（デフォルトは'update'）
        $data_forms = ''; // フォームのHTMLコードを格納する変数を初期化
        $data_forms .= '<div class="box">'; // フォームを囲む<div>タグの開始タグを追加


        // controllerブロックを必ず先頭に追加
        $controller_html = '<div class="controller">'
            . '<div class="printer">'
            . '<button id="previewButton" onclick="togglePreview()" title="プレビュー">'
            . '<span class="material-symbols-outlined" aria-label="プレビュー">preview</span>'
            . '</button>'
            . '<button onclick="printContent()" title="印刷する">'
            . '<span class="material-symbols-outlined" aria-label="印刷">print</span>'
            . '</button>'
            . '</div>'
            . '</div>';

        // 受注書作成ボタンはworkflowブロックに分離
        $workflow_html = '<div class="workflow">';
        
        // 表示モードボタンの追加
        $workflow_html .= '<div class="view-mode-buttons" style="display:flex;gap:8px;margin:0px 0;align-items:center;">';
        
        // 現在の顧客IDを取得（後で使用するため）
        $current_client_id = 0;
        $cookie_name = 'ktp_' . $name . '_id';
        if (isset($_GET['data_id'])) {
            $current_client_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
        } elseif (isset($_COOKIE[$cookie_name])) {
            $current_client_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
        } else {
            // 最後のIDを取得
            $query = "SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1";
            $last_id_row = $wpdb->get_row($query);
            $current_client_id = $last_id_row ? $last_id_row->id : 0;
        }
        
        // 注文履歴ボタン - 現在の顧客IDを保持して遷移
        $order_history_active = ($view_mode === 'order_history') ? 'active' : '';
        $workflow_html .= '<button type="button" class="view-mode-btn order-history-btn ' . $order_history_active . '" onclick="window.location.href=\'?tab_name=client&view_mode=order_history&data_id=' . $current_client_id . '\'">注文履歴</button>';
        
        // 顧客一覧ボタン - 現在の顧客IDを保持して遷移
        $customer_list_active = ($view_mode === 'customer_list') ? 'active' : '';
        $workflow_html .= '<button type="button" class="view-mode-btn customer-list-btn ' . $customer_list_active . '" onclick="window.location.href=\'?tab_name=client&view_mode=customer_list&data_id=' . $current_client_id . '\'">顧客一覧</button>';
        
        $workflow_html .= '<div class="order-btn-box" style="margin-left:auto;">';
        $workflow_html .= '<form method="post" action="" onsubmit="event.preventDefault(); window.location.href=\'?tab_name=order&from_client=1&customer_name=' . urlencode($order_customer_name) . '&user_name=' . urlencode($order_user_name) . '&client_id=' . urlencode($data_id) . '\';">';
        $workflow_html .= '<button type="submit" class="create-order-btn">受注書作成</button>';
        $workflow_html .= '</form>';
        $workflow_html .= '</div>';
        $workflow_html .= '</div>';
        $workflow_html .= '</div>';

        // データー量を取得
        $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $query_id);
        $data_num = $wpdb->get_results($query);
        $data_num = count($data_num); // 現在のデータ数を取得し$data_numに格納

        // 空のフォームを表示(追加モードの場合)
        if ($action === 'istmode') {

                $data_id = $wpdb->insert_id;

                // 詳細表示部分の開始
                $data_title = <<<END
                    <div class="data_detail_box">
                    <h3>■ 顧客の詳細</h3>
                END;

                // 郵便番号から住所を自動入力するためのJavaScriptコードを追加（日本郵政のAPIを利用）
                $data_forms = <<<END
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var postalCode = document.querySelector('input[name="postal_code"]');
                    var prefecture = document.querySelector('input[name="prefecture"]');
                    var city = document.querySelector('input[name="city"]');
                    var address = document.querySelector('input[name="address"]');
                    postalCode.addEventListener('blur', function() {
                        var xhr = new XMLHttpRequest();
                        xhr.open('GET', 'https://zipcloud.ibsnet.co.jp/api/search?zipcode=' + postalCode.value);
                        xhr.addEventListener('load', function() {
                            var response = JSON.parse(xhr.responseText);
                            if (response.results) {
                                var data = response.results[0];
                                prefecture.value = data.address1;
                                city.value = data.address2 + data.address3; // 市区町村と町名を結合
                                address.value = ''; // 番地は空欄に
                            }
                        });
                        xhr.send();
                    });
                });
                </script>
                END;

                // 空のフォームフィールドを生成
                $data_forms .= '<form method="post" action="">';
                foreach ($fields as $label => $field) {
                    $value = $action === 'update' ? ${$field['name']} : ''; // フォームフィールドの値を取得
                    $pattern = isset($field['pattern']) ? " pattern=\"{$field['pattern']}\"" : ''; // バリデーションパターンが指定されている場合は、パターン属性を追加
                    $required = isset($field['required']) && $field['required'] ? ' required' : ''; // 必須フィールドの場合は、required属性を追加
                    $fieldName = $field['name'];
                    $placeholder = isset($field['placeholder']) ? " placeholder=\"{$field['placeholder']}\"" : ''; // プレースホルダーが指定されている場合は、プレースホルダー属性を追加
                    if ($field['type'] === 'textarea') {
                        $data_forms .= "<div class=\"form-group\"><label>{$label}：</label> <textarea name=\"{$fieldName}\"{$pattern}{$required}>{$value}</textarea></div>"; // テキストエリアのフォームフィールドを追加
                    } elseif ($field['type'] === 'select') {
                        $options = '';

                        foreach ($field['options'] as $option) {
                            $selected = $value === $option ? ' selected' : ''; // 選択されたオプションを判定し、selected属性を追加
                            $options .= "<option value=\"{$option}\"{$selected}>{$option}</option>"; // オプション要素を追加
                        }

                        $default = isset($field['default']) ? $field['default'] : ''; // デフォルト値を取得

                        $data_forms .= "<div class=\"form-group\"><label>{$label}：</label> <select name=\"{$fieldName}\"{$required}><option value=\"\">{$default}</option>{$options}</select></div>"; // セレクトボックスのフォームフィールドを追加
                    } else {
                        $data_forms .= "<div class=\"form-group\"><label>{$label}：</label> <input type=\"{$field['type']}\" name=\"{$fieldName}\" value=\"{$value}\"{$pattern}{$required}{$placeholder}></div>"; // その他のフォームフィールドを追加
                    }
                }

                $data_forms .= "<div class='button'>";

                if( $action === 'istmode'){
                    // 追加実行ボタン
                    $action = 'insert';
                    $data_id = $data_id + 1;
                    $data_forms .= <<<END
                    <form method='post' action=''>
                    <input type='hidden' name='query_post' value='$action'>
                    <input type='hidden' name='data_id' value='$data_id'>
                    <button type='submit' name='send_post' title="追加実行">
                    <span class="material-symbols-outlined">
                    select_check_box
                    </span>
                    </button>
                    </form>
                    END;
                }
                
                elseif( $action === 'srcmode'){

                    // 検索実行ボタン
                    $action = 'search';
                    $data_forms .= <<<END
                    <form method='post' action=''>
                    <input type='hidden' name='query_post' value='$action'>
                    <button type='submit' name='send_post' title="検索実行">
                    <span class="material-symbols-outlined">
                    select_check_box
                    </span>
                    </button>
                    </form>
                    END;
                }
    
                // キャンセルボタン
                $action = 'update';
                $data_id = $data_id - 1;
                $data_forms .= <<<END
                <form method='post' action=''>
                <input type='hidden' name='data_id' value=''>
                <input type='hidden' name='query_post' value='$action'>
                <input type='hidden' name='data_id' value='$data_id'>
                <button type='submit' name='send_post' title="キャンセル">
                <span class="material-symbols-outlined">
                disabled_by_default
                </span>            
                </button>
                </form>
                END;

            $data_forms .= "<div class=\"add\">";
            $data_forms .= '</div>';
        }

        // 空のフォームを表示(検索モードの場合)
        elseif ($action === 'srcmode') {

            // 表題
            $data_title = <<<END
            <div class="data_detail_box">
                <h3>■ 顧客の詳細（ 検索モード ）</h3>
            END;

            // 検索フォームを生成
            $data_forms = '<form method="post" action="">';
            $data_forms .= "<div class=\"form-group\"><input type=\"text\" name=\"search_query\" placeholder=\"フリーワード\" required></div>";
               
            // 検索リストを生成
            $data_forms .= $search_results_list;

            // ボタン<div>タグを追加
            $data_forms .= "<div class='button'>";
            
            // 検索実行ボタン
            $action = 'search';
            $data_forms .= <<<END
            <form method='post' action=''>
            <input type='hidden' name='query_post' value='$action'>
            <button type='submit' name='send_post' title="検索実行">
            <span class="material-symbols-outlined">
            select_check_box
            </span>
            </button>
            </form>
            END;

            // キャンセルボタン
            $action = 'update';
            $data_id = $data_id - 1;
            $data_forms .= <<<END
            <form method='post' action=''>
            <input type='hidden' name='data_id' value=''>
            <input type='hidden' name='query_post' value='$action'>
            <input type='hidden' name='data_id' value='$data_id'>
            <button type='submit' name='send_post' title="キャンセル">
            <span class="material-symbols-outlined">
            disabled_by_default
            </span>            
            </button>
            </form>
            END;

            $data_forms .= "<div class=\"add\">";
            $data_forms .= '</div>';
        }            

        // 追加・検索 以外なら更新フォームを表示
        elseif ($action !== 'srcmode' || $action !== 'istmode') {

            // 郵便番号から住所を自動入力するためのJavaScriptコードを追加（日本郵政のAPIを利用）
            $data_forms .= <<<END
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var postalCode = document.querySelector('input[name="postal_code"]');
                var prefecture = document.querySelector('input[name="prefecture"]');
                var city = document.querySelector('input[name="city"]');
                var address = document.querySelector('input[name="address"]');
                postalCode.addEventListener('blur', function() {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'https://zipcloud.ibsnet.co.jp/api/search?zipcode=' + postalCode.value);
                    xhr.addEventListener('load', function() {
                        var response = JSON.parse(xhr.responseText);
                        if (response.results) {
                            var data = response.results[0];
                            prefecture.value = data.address1;
                            city.value = data.address2 + data.address3; // 市区町村と町名を結合
                            address.value = ''; // 番地は空欄に
                        }
                    });
                    xhr.send();
                });
            });
            </script>
            END;
                        
            $data_forms .= "<div class=\"add\">";
            $data_forms .= "<form method=\"post\" action=\"\">"; // フォームの開始タグを追加
            
            // cookieに保存されたIDを取得
            $cookie_name = 'ktp_'. $name . '_id';
            if (isset($_GET['data_id'])) {
                $data_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
            } elseif (isset($_COOKIE[$cookie_name])) {
                $data_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
            } else {
                $data_id = $last_id_row ? $last_id_row->id : Null;
            }

            // 表題
            $data_title = <<<END
            <div class="data_detail_box">
                <h3>■ 顧客の詳細（ ID: $data_id ）</h3>
            END;

            foreach ($fields as $label => $field) {
                $value = $action === 'update' ? ${$field['name']} : ''; // フォームフィールドの値を取得
                $pattern = isset($field['pattern']) ? " pattern=\"{$field['pattern']}\"" : ''; // バリデーションパターンが指定されている場合は、パターン属性を追加
                $required = isset($field['required']) && $field['required'] ? ' required' : ''; // 必須フィールドの場合は、required属性を追加
                $placeholder = isset($field['placeholder']) ? " placeholder=\"{$field['placeholder']}\"" : ''; // プレースホルダーが指定されている場合は、プレースホルダー属性を追加

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
                    $data_forms .= "<div class=\"form-group\"><label>{$label}：</label> <input type=\"{$field['type']}\" name=\"{$field['name']}\" value=\"{$value}\"{$pattern}{$required}{$placeholder}></div>"; // その他のフォームフィールドを追加
                }
            }

            $data_forms .= "<input type=\"hidden\" name=\"query_post\" value=\"{$action}\">"; // フォームのアクションを指定する隠しフィールドを追加
            $data_forms .= "<input type=\"hidden\" name=\"data_id\" value=\"{$data_id}\">"; // データIDを指定する隠しフィールドを追加
            
            // 検索リストを生成
            if (!isset($search_results_list)) {
                $search_results_list = '';
            }
            $data_forms .= $search_results_list;
            $data_forms .= "<div class='button'>";

            // 更新ボタンを追加
            $data_forms .= <<<END
            <form method="post" action="">
                <button type="submit" name="send_post" title="更新する">
                    <span class="material-symbols-outlined">
                    cached
                    </span>
                </button>
            </form>
            END;

            // 削除ボタン
            $data_forms .= <<<END
            <form method="post" action="">
                <input type="hidden" name="data_id" value="{$data_id}">
                <input type="hidden" name="query_post" value="delete">
                <button type="submit" name="send_post" title="削除する" onclick="return confirm('本当に削除しますか？')">
                    <span class="material-symbols-outlined">
                        delete
                    </span>
                </button>
            </form>
            END;

            // 複製ボタン
            $data_forms .= <<<END
            <form method="post" action="">
                <input type="hidden" name="data_id" value="{$data_id}">
                <input type="hidden" name="query_post" value="duplication">
                <button type="submit" name="send_post" title="複製する">
                    <span class="material-symbols-outlined">
                    content_copy
                    </span>
                </button>
            </form>
            END;

            // 追加モードボタン
            $action = 'istmode';
            $data_id = $data_id + 1;
            $data_forms .= <<<END
            <form method='post' action=''>
                <input type='hidden' name='data_id' value=''>
                <input type='hidden' name='query_post' value='$action'>
                <input type='hidden' name='data_id' value='$data_id'>
                <button type='submit' name='send_post' title="追加する">
                    <span class="material-symbols-outlined">
                    add
                    </span>
                </button>
            </form>
            END;

            // 検索モードボタン
            $action = 'srcmode';
            // $data_id = $data_id;
            $data_forms .= <<<END
            <form method='post' action=''>
                <input type='hidden' name='query_post' value='$action'>
                <button type='submit' name='send_post' title="検索する">
                    <span class="material-symbols-outlined">
                    search
                    </span>
                </button>
            </form>
            END;

            $data_forms .= '</div>';
        }
                            
        $data_forms .= '</div>'; // フォームを囲む<div>タグの終了タグを追加
        
        // 詳細表示部分の終了
        $div_end = <<<END
            </div> <!-- data_detail_boxの終了 -->
        </div> <!-- data_contentsの終了 -->
        END;

        // -----------------------------
        // テンプレート印刷
        // -----------------------------

        // Print_Classのパスを指定
        require_once( dirname( __FILE__ ) . '/class-print.php' );

        // データを指定
        $data_src = [
            'company_name' => $company_name,
            'name' => $user_name,
            'representative_name' => $data_src['representative_name'],
            'postal_code' => $data_src['postal_code'],
            'prefecture' => $data_src['prefecture'],
            'city' => $data_src['city'],
            'address' => $data_src['address'],
            'building' => $data_src['building'],
        ];

        // データを取得
        $customer = $data_src['company_name'];
        $user_name = $data_src['name'];
        $representative_name = $data_src['representative_name'];
        $postal_code = $data_src['postal_code'];
        $prefecture = $data_src['prefecture'];
        $city = $data_src['city'];
        $address = $data_src['address'];
        $building = $data_src['building'];

        $data = [
            'postal_code' => "$postal_code",
            'prefecture' => "$prefecture",
            'city' => "$city",
            'address' => "$address",
            'building' => "$building",
            'customer' => "$customer",
            'user_name' => "$user_name",
        ];

        $print_html = new Print_Class($data);
        $print_html = $print_html->generateHTML();

        // PHP
        $print_html = json_encode($print_html);  // JSON形式にエンコード

        // JavaScript
        $print = <<<END
        <script>
            var isPreviewOpen = false;

            function printContent() {
                var printContent = $print_html;
                var printWindow = window.open('', '_blank');
                printWindow.document.open();
                printWindow.document.write('<html><head><title>印刷</title></head><body>');
                printWindow.document.write(printContent);
                printWindow.document.write('<script>window.onafterprint = function(){ window.close(); }<\/script>');
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();  // Add this line
                
                // 印刷後、プレビューが開いていれば閉じる
                if (isPreviewOpen) {
                    togglePreview();
                }
            }

            function togglePreview() {
                var previewWindow = document.getElementById('previewWindow');
                var previewButton = document.getElementById('previewButton');
                
                if (isPreviewOpen) {
                    // プレビューを閉じる
                    previewWindow.style.display = 'none';
                    previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="プレビュー">preview</span>';
                    isPreviewOpen = false;
                } else {
                    // プレビューを表示
                    var printContent = $print_html;
                    
                    // プレビューウィンドウが存在しない場合は作成
                    if (!previewWindow) {
                        previewWindow = document.createElement('div');
                        previewWindow.id = 'previewWindow';
                        previewWindow.style.display = 'none';
                        previewWindow.style.position = 'relative';
                        previewWindow.style.zIndex = '100';
                        previewWindow.style.background = '#fff';
                        previewWindow.style.padding = '20px';
                        previewWindow.style.border = '1px solid #ccc';
                        previewWindow.style.margin = '10px 0';
                        
                        // controllerの直後に挿入
                        var controllerDiv = document.querySelector('.controller');
                        if (controllerDiv) {
                            controllerDiv.parentNode.insertBefore(previewWindow, controllerDiv.nextSibling);
                        } else {
                            document.querySelector('.box').appendChild(previewWindow);
                        }
                    }
                    
                    previewWindow.innerHTML = printContent;
                    previewWindow.style.display = 'block';
                    previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="閉じる">close</span>';
                    isPreviewOpen = true;
                }
            }

            // about:blankは自動的に閉じられます

            // DOMContentLoaded時にプレビューボタンの状態を設定
            document.addEventListener('DOMContentLoaded', function() {
                isPreviewOpen = false;
                var previewButton = document.getElementById('previewButton');
                if (previewButton) {
                    previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="プレビュー">preview</span>';
                }
            });
        </script>
        END;

        // コンテンツを返す
        // controller, workflow（受注書作成ボタン）を$print直後に追加
        // controller_html, workflow_htmlが重複しないようにcontroller_htmlは1回のみ出力
        // プレビューウィンドウはJavaScriptで動的に作成されるため、HTMLに直接書く必要はなくなった
        $content = $print . $controller_html . $workflow_html . $data_list . $data_title . $data_forms . $search_results_list . $div_end;
        return $content;
        
    }

}
} // class_exists