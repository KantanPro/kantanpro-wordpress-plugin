<?php
require_once 'class-image_processor.php';

class Kntan_Service_Class {

    public function __construct($tab_name = '') {

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
        $my_table_version = '1.0.0';
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $charset_collate = $wpdb->get_charset_collate();
        // テーブルが存在しない場合は作成
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                time BIGINT(11) DEFAULT '0' NOT NULL,
                service_name TINYTEXT,
                image_url VARCHAR(255),
                memo TEXT,
                search_field TEXT,
                frequency INT NOT NULL DEFAULT 0,
                category VARCHAR(100) NOT NULL DEFAULT '一般',
                UNIQUE KEY id (id)
            ) {$charset_collate};";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            add_option("{$table_name}_version", $my_table_version);
        }
        

        $columns = [
            "id MEDIUMINT(9) NOT NULL AUTO_INCREMENT",
            "time BIGINT(11) DEFAULT '0' NOT NULL",
            "service_name TINYTEXT",
            "image_url VARCHAR(255)", // 商品画像のURLを追加
            "memo TEXT",
            "search_field TEXT", // 検索用フィールドを追加
            "frequency INT NOT NULL DEFAULT 0", // 頻度
            "category VARCHAR(100) NOT NULL DEFAULT '一般'",
            "UNIQUE KEY id (id)"
        ];
    
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (" . implode(", ", $columns) . ") $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            add_option('ktp_' . $tab_name . '_table_version', $my_table_version);
        } else {
            $existing_columns = $wpdb->get_col("DESCRIBE $table_name", 0);
            $missing_columns = array_diff($columns, $existing_columns);
            foreach ($missing_columns as $missing_column) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN $missing_column");
            }
            update_option('ktp_' . $tab_name . '_table_version', $my_table_version);
        }
    }

    // -----------------------------
    // テーブルの操作（更新・追加・削除・検索）
    // -----------------------------

    function Update_Table( $tab_name) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;

// POSTデーター受信
        $data_id = $_POST['data_id'];
        // その他のPOSTデータを受信...

        // データIDが指定されているか確認
        if (!empty($data_id)) {
        // データが存在するか確認
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE id = %d", $data_id));
        if ($exists) {
        // 既存のデータを更新
        $wpdb->update(
            $table_name,
            array( /* 更新するデータの配列 */ ),
            array('id' => $data_id) // 条件
        );
        } else {
        // 新しいデータを追加
        $wpdb->insert(
            $table_name,
            array( /* 追加するデータの配列 */ )
        );
        }
        } else {
        // $data_idが不適切な場合のエラーハンドリング
        }

    // データが0の場合、デフォルトデータを1つ作成する
    $data_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    if($data_count == 0) {
        // デフォルトデータの作成
        $default_data = array(
            'time' => current_time('mysql'),
            'service_name' => '初めての商品',
            'memo' => '',
            'category' => '一般',
            'image_url' => plugin_dir_url(''). 'kantan-pro-wp/images/default/no-image-icon.jpg', // デフォルト画像URL
            'search_field' => '',
            'frequency' => 0
        );
        $wpdb->insert($table_name, $default_data);
        $data_id = $wpdb->insert_id;
        if ($data_id != 0) {
                // 作成したデータIDをクッキーに保存する
                $cookie_name = 'ktp_' . $tab_name . '_id';
                setcookie($cookie_name, $data_id, time() + (86400 * 30), "/"); // 30日間有効
        }
    }

        // テーブル名にロックをかける
        $wpdb->query("LOCK TABLES {$table_name} WRITE;");
        
        // POSTデーター受信
        $data_id = $_POST['data_id'];
        $query_post = $_POST['query_post'];
        $service_name = $_POST['service_name'];
        $memo = $_POST['memo'];
        $category = $_POST['category'];
        
        $search_field_value = implode(', ', [
            $data_id,
            current_time('mysql'),
            $service_name,
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

            // データ削除後にデーターが0ならデフォルトデータを1つ作成する
            $data_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            
            if($data_count == 0) {
                // デフォルトデータの作成
                $default_data = [
                    'time' => current_time('mysql'),
                    'service_name' => '初めての商品',
                    'memo' => '',
                    'category' => '一般',
                    'image_url' => plugin_dir_url(''). 'kantan-pro-wp/images/default/no-image-icon.jpg', // デフォルト画像URL
                    'search_field' => 'デフォルト',
                    'frequency' => 0
                ];
                $wpdb->insert($table_name, $default_data);
                $data_id = $wpdb->insert_id;
                if ($data_id != 0) {
                    // 作成したデータIDをクッキーに保存する
                    $cookie_name = 'ktp_' . $tab_name . '_id';
                    setcookie($cookie_name, $data_id, time() + (86400 * 30), "/"); // 30日間有効
                }
            }

            // 最後のデータにリダイレクト
            $last_id_query = "SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1";
            $last_id_result = $wpdb->get_row($last_id_query);
            if ($last_id_result) {
                $last_id = $last_id_result->id;
                $url = '?tab_name='. $tab_name . '&data_id=' . $last_id;
                header("Location: {$url}");
}

            exit;

        }    
        
        // 更新
        elseif( $query_post == 'update' ){

            $wpdb->update( 
                $table_name, 
                array( 
                    'service_name' => $service_name,
                    'memo' => $memo,
                    'category' => $category,
                    'search_field' => $search_field_value,
                ),
                    array( 'id' => $data_id ), 
                    array( 
                        '%s',  // service_name
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
                    $service_name = esc_html($row->service_name); // 商品名を取得
                    $category = esc_html($row->category); // カテゴリーを取得
                    // 各検索結果に対してリンクを設定
                    $search_results_html .= "<li style='text-align:left; width:100%;'><a href='?tab_name={$tab_name}&data_id={$id}&query_post=update' style='text-align:left;'>ID：{$id} 商品名：{$service_name} カテゴリー：{$category}</a></li>";
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
            // デフォルト画像のURLを設定
            $default_image_url = plugin_dir_url(''). 'kantan-pro-wp/images/default/no-image-icon.jpg';

            // 画像URLが空の場合、デフォルト画像のURLを使用
            $image_url = empty($_POST['image_url']) ? $default_image_url : $_POST['image_url'];

            $insert_result = $wpdb->insert( 
                $table_name, 
                array( 
                    'time' => current_time( 'mysql' ),
                    'service_name' => $service_name,
                    'memo' => $memo,
                    'category' => $category,
                    'image_url' => $image_url, // 修正された部分
                    'search_field' => $search_field_value
                ) 
            );
            if($insert_result === false) {
                error_log('Insert error: ' . $wpdb->last_error);
            } else {

                // ロックを解除する
                $wpdb->query("UNLOCK TABLES;");
            
                // 追加後に更新モードにしてリダイレクトしIDをクッキーに保存
                $new_data_id = $wpdb->insert_id;
                $action = 'update';
                $url = '?tab_name='. $tab_name . '&data_id=' . $new_data_id . '&query_post=' . $action;
                $cookie_name = 'ktp_' . $tab_name . '_id'; // クッキー名を設定
                setcookie($cookie_name, $new_data_id, time() + (86400 * 30), "/"); // クッキーを保存
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
    
            // 商品名の最後に#を追加
            $data['service_name'] .= '#';
    
            // IDを削除
            unset($data['id']);
    
            // 頻度を0に設定
            $data['frequency'] = 0;
    
            // search_fieldの値を更新
            $data['search_field'] = implode(', ', [
                $data['time'],
                $data['service_name'],
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

        // データが存在しない場合、デフォルト画像を使用
        // デフォルト画像のURLを設定
        $default_image_url = plugin_dir_url(''). 'kantan-pro-wp/images/default/no-image-icon.jpg';

        // データを取得し変数に格納
        $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $query_id);
        $post_row = $wpdb->get_results($query);
        if(empty($post_row)) {
            // データが存在しない場合、デフォルト画像を使用
            $image_url = $default_image_url;
        } else {
            foreach ($post_row as $row){
                // 画像URLが空、または設定されていない場合、デフォルト画像を使用
                $image_url = !empty($row->image_url) ? esc_html($row->image_url) : $default_image_url;
            }
        }

        // -----------------------------
        // ページネーションリンク
        // -----------------------------
        
        // 表示範囲
        $query_limit = 5;

        // リスト表示部分の開始
        $results_h = <<<END
        <div class="data_contents">
            <div class="data_list_box">
            <h3>■ 商品リスト（レンジ： $query_limit ）</h3>
        END;

        // スタート位置を決める
        $page_stage = $_GET['page_stage'];
        $page_start = $_GET['page_start'];
        $flg = $_GET['flg'];
        if ($page_stage == '') {
            $page_start = 0;
        }
        $query_range = $page_start . ',' . $query_limit;

        $query_order_by = 'frequency';

        // 全データ数を取得
        $total_query = "SELECT COUNT(*) FROM {$table_name}";
        $total_rows = $wpdb->get_var($total_query);
        $total_pages = ceil($total_rows / $query_limit);

        // 現在のページ番号を計算
        $current_page = floor($page_start / $query_limit) + 1;

        // データを取得
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY frequency DESC LIMIT %d, %d", $page_start, $query_limit);
        $post_row = $wpdb->get_results($query);
        if( $post_row ){
            foreach ($post_row as $row){
                $id = esc_html($row->id);
                $time = esc_html($row->time);
                $service_name = esc_html($row->service_name);
                $memo = esc_html($row->memo);
                $category = esc_html($row->category);
                $image_url = esc_html($row->image_url);
                $frequency = esc_html($row->frequency);
                
                // リスト項目
                $cookie_name = 'ktp_' . $name . '_id';
                $results[] = <<<END
                <a href="?tab_name={$name}&data_id={$id}&page_start={$page_start}&page_stage={$page_stage}" onclick="document.cookie = '{$cookie_name}=' + {$id};">
                    <div class="data_list_item">$id : $service_name : $category : 頻度($frequency)</div>
                </a>
                END;

            }
            $query_max_num = $wpdb->num_rows;
        } else {
            $results[] = <<<END
            <div class="data_list_item">データーがありません。</div>
            END;            
        }

        $results_f = "<div class=\"pagination\">";

        // 最初へリンク
        if ($current_page > 1) {
            $first_start = 0; // 最初のページ
            $results_f .= <<<END
            <a href="?tab_name=$name&page_start=$first_start&page_stage=2&flg=$flg">|<</a> 
            END;
        }

        // 前へリンク
        if ($current_page > 1) {
            $prev_start = ($current_page - 2) * $query_limit;
            $results_f .= <<<END
            <a href="?tab_name=$name&page_start=$prev_start&page_stage=2&flg=$flg"><</a>
            END;
        }

        // 現在のページ範囲表示と総数
        $page_end = min($total_rows, $current_page * $query_limit);
        $page_start_display = ($current_page - 1) * $query_limit + 1;
        $results_f .= "<div class='stage'> $page_start_display ~ $page_end / $total_rows</div>";

        // 次へリンク（現在のページが最後のページより小さい場合のみ表示）
        if ($current_page < $total_pages) {
            $next_start = $current_page * $query_limit;
            $results_f .= <<<END
             <a href="?tab_name=$name&page_start=$next_start&page_stage=2&flg=$flg">></a>
            END;
        }

        // 最後へリンク
        if ($current_page < $total_pages) {
            $last_start = ($total_pages - 1) * $query_limit; // 最後のページ
            $results_f .= <<<END
             <a href="?tab_name=$name&page_start=$last_start&page_stage=2&flg=$flg">>|</a>
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
            $service_name = esc_html($row->service_name);
            $memo = esc_html($row->memo);
            $category = esc_html($row->category);
            $image_url = esc_html($row->image_url);
        }
        
        // 表示するフォーム要素を定義
        $fields = [
            // 'ID' => ['type' => 'text', 'name' => 'data_id', 'readonly' => true], 
            '商品名' => ['type' => 'text', 'name' => 'service_name', 'required' => true, 'placeholder' => '必須 商品・サービス名'],
            // '画像URL' => ['type' => 'text', 'name' => 'image_url'],
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
                    <h3>■ 商品の詳細</h3>
                END;


                // 空のフォームフィールドを生成
                $data_forms .= '<form method="post" action="">';
                foreach ($fields as $label => $field) {
                    $value = $action === 'update' ? ${$field['name']} : ''; // フォームフィールドの値を取得
                    if ($field['name'] === 'category') { // カテゴリーの値をデフォルトの「一般」に設定
                        $value = '一般';
                    }
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
                <h3>■ 商品の詳細（ 検索モード ）</h3>
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
            
            // データを取得
            global $wpdb;
            $table_name = $wpdb->prefix . 'ktp_' . $name;
            
            // データを取得
            $query = "SELECT * FROM {$table_name} WHERE id = %d";
            $post_row = $wpdb->get_results($wpdb->prepare($query, $data_id));
        
            foreach ($post_row as $row) {
                $image_url = esc_html($row->image_url);
            }
            
            $data_forms .= "<div class=\"image\"><img src=\"{$image_url}\" alt=\"商品画像\" class=\"product-image\"></div>";

            $data_forms .= '<div class=image_upload_form>';

            // 商品画像アップロードフォームを追加
            $data_forms .= <<<END
            <form action="" method="post" enctype="multipart/form-data" onsubmit="return !!this.image.value;">
            <div style="display: flex; align-items: center;">
            <input type="file" name="image" style="width: 80%;">
            <input type="hidden" name="data_id" value="$data_id">
            <input type="hidden" name="query_post" value="upload_image">
            <input type="submit" value="アップロード" style="width: 50%; background-color: #0096ff; color: white;">
            </div>
            </form>
            END;

            // 商品画像削除ボタンを追加
            $data_forms .= <<<END
            <form method="post" action="">
                <input type="hidden" name="data_id" value="{$data_id}">
                <input type="hidden" name="query_post" value="delete_image">
                <button type="submit" name="send_post" title="削除する" onclick="return confirm('本当に削除しますか？')">
                    <span class="material-symbols-outlined">
                        delete
                    </span>
                </button>
            </form>
            END;
            
            $data_forms .= '</div>';
            
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

            // URLからdata_idを取得、なければ、クッキーから取得
            $data_id = isset($_GET['data_id']) ? $_GET['data_id'] : $_COOKIE[$cookie_name];
            
            // 表題
            $data_title = <<<END
            <div class="data_detail_box">
                <h3>■ 商品の詳細（ ID： $data_id ）</h3>
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
            'service_name' => $service_name,
            'category' => $category,
            'image_url' => $image_url
        ];

        $customer = $data_src['service_name'];
        $data = [
            'service_name' => $service_name,
            'category' => $category,
            'image_url' => $image_url
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
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();  // Add this line
            }

            function togglePreview() {
                var previewWindow = document.getElementById('previewWindow');
                var previewButton = document.getElementById('previewButton');
                if (isPreviewOpen) {
                    previewWindow.style.display = 'none';
                    previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="プレビュー">preview</span>';
                    isPreviewOpen = false;
                } else {
                    var printContent = $print_html;
                    previewWindow.innerHTML = printContent;
                    previewWindow.style.display = 'block';
                    previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="閉じる">close</span>';
                    isPreviewOpen = true;
                }
            }

            // about:blankを閉じる
            // window.onafterprint = function() {
            //     window.close();
            // }

        </script>
        <div class="controller">
            <div class="printer">
                <div class="up-title">商品印刷：</div>
                <button id="previewButton" onclick="togglePreview()" title="プレビュー">
                    <span class="material-symbols-outlined" aria-label="プレビュー">preview</span>
                </button>
                <button onclick="printContent()" title="印刷する">
                    <span class="material-symbols-outlined" aria-label="印刷">print</span>
                </button>
            </div>
        </div>
        <div id="previewWindow" style="display: none;"></div>
        END;

        // コンテンツを返す
        $content = $print . $data_list . $data_title . $data_forms . $search_results_list . $div_end;
        return $content;
        
    }

}

?>

