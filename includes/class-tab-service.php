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

    public function View_Table($tab_name = '') {
        return <<<HTML
        <h3>ここは [{$tab_name}] です。</h3>
        商品・サービスのリストを表示します。
        <table>
            <tr><th>商品名</th><th>価格</th><th>説明</th></tr>
            <tr><td>Webサイト制作</td><td>300,000円</td><td>企業向けWebサイト</td></tr>
            <tr><td>SEOコンサル</td><td>100,000円</td><td>検索順位対策</td></tr>
        </table>
        HTML;
    }

}