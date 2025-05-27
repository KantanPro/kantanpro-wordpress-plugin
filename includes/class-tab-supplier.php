<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('Kantan_Supplier_Class')) {
class Kantan_Supplier_Class{

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
        $my_table_version = '1.0.0';
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $charset_collate = $wpdb->get_charset_collate();

        // テーブルが存在しない場合は作成
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $sql = "CREATE TABLE {$table_name} (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                time BIGINT(11) DEFAULT '0' NOT NULL,
                name TINYTEXT NOT NULL,
                url VARCHAR(55) NOT NULL,
                company_name VARCHAR(100) NOT NULL DEFAULT 'いつもの業者',
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                postal_code VARCHAR(10) NOT NULL,
                prefecture TINYTEXT NOT NULL,
                city TINYTEXT NOT NULL,
                address TEXT NOT NULL,
                building TINYTEXT NOT NULL,
                closing_day TINYTEXT NOT NULL,
                payment_month TINYTEXT NOT NULL,
                payment_day TINYTEXT NOT NULL,
                payment_method TINYTEXT NOT NULL,
                tax_category VARCHAR(100) NOT NULL DEFAULT '税込',
                memo TEXT NOT NULL,
                search_field TEXT NOT NULL,
                frequency INT NOT NULL DEFAULT 0,
                category VARCHAR(100) NOT NULL DEFAULT '一般',
                UNIQUE KEY id (id)
            ) {$charset_collate};";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            add_option("{$table_name}_version", $my_table_version);
        }

        // カラム追加前に存在チェック
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table_name}", 0);

        // カラム名リスト
        $column_defs = [
            'id' => "id MEDIUMINT(9) NOT NULL AUTO_INCREMENT",
            'time' => "time BIGINT(11) DEFAULT '0' NOT NULL",
            'name' => "name TINYTEXT",
            'url' => "url VARCHAR(55)",
            'company_name' => "company_name VARCHAR(100) NOT NULL DEFAULT 'いつもの業者'",
            'representative_name' => "representative_name TINYTEXT",
            'email' => "email VARCHAR(100)",
            'phone' => "phone VARCHAR(20)",
            'postal_code' => "postal_code VARCHAR(10)",
            'prefecture' => "prefecture TINYTEXT",
            'city' => "city TINYTEXT",
            'address' => "address TEXT",
            'building' => "building TINYTEXT",
            'closing_day' => "closing_day TINYTEXT",
            'payment_month' => "payment_month TINYTEXT",
            'payment_day' => "payment_day TINYTEXT",
            'payment_method' => "payment_method TINYTEXT",
            'tax_category' => "tax_category VARCHAR(100) NOT NULL DEFAULT '税込'",
            'memo' => "memo TEXT",
            'search_field' => "search_field TEXT",
            'frequency' => "frequency INT NOT NULL DEFAULT 0",
            'category' => "category VARCHAR(100) NOT NULL DEFAULT '一般'",
        ];

        foreach ($column_defs as $col => $def) {
            if (!in_array($col, $columns)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN $def");
            }
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
        // ALTER TABLE ... ADD UNIQUE (id) は既にUNIQUEがなければのみ実行
        // ただし、UNIQUE KEYは「ADD COLUMN」ではなく「ADD UNIQUE」なので、重複エラー防止のため下記のように修正
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

        // CSRF対策: POST時のみnonceチェック
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['ktp_supplier_nonce']) || !wp_verify_nonce($_POST['ktp_supplier_nonce'], 'ktp_supplier_action')) {
                $wpdb->query("UNLOCK TABLES;");
                wp_die(__('不正なリクエストです。ページを再読み込みしてください。', 'ktpwp'));
            }
        }

        // テーブル名にロックをかける
        $wpdb->query("LOCK TABLES {$table_name} WRITE;");

        // POSTデータ受信＆サニタイズ
        $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : '';
        $query_post = isset($_POST['query_post']) ? sanitize_text_field($_POST['query_post']) : '';
        $company_name = isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '';
        $user_name = isset($_POST['user_name']) ? sanitize_text_field($_POST['user_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $representative_name = isset($_POST['representative_name']) ? sanitize_text_field($_POST['representative_name']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $postal_code = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';
        $prefecture = isset($_POST['prefecture']) ? sanitize_text_field($_POST['prefecture']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        $building = isset($_POST['building']) ? sanitize_text_field($_POST['building']) : '';
        $closing_day = isset($_POST['closing_day']) ? sanitize_text_field($_POST['closing_day']) : '';
        $payment_month = isset($_POST['payment_month']) ? sanitize_text_field($_POST['payment_month']) : '';
        $payment_day = isset($_POST['payment_day']) ? sanitize_text_field($_POST['payment_day']) : '';
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
        $tax_category = isset($_POST['tax_category']) ? sanitize_text_field($_POST['tax_category']) : '';
        $memo = isset($_POST['memo']) ? sanitize_textarea_field($_POST['memo']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $page_stage = isset($_POST['page_stage']) ? sanitize_text_field($_POST['page_stage']) : '';
        $page_start = isset($_POST['page_start']) ? intval($_POST['page_start']) : '';
        $flg = isset($_POST['flg']) ? sanitize_text_field($_POST['flg']) : '';

        $search_field_value = implode(', ', [
            $data_id,
            current_time('mysql'),
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
            // デバッグ用: 削除直前のIDとテーブル名を表示
            echo '<div style="color:blue;font-weight:bold;">DEBUG: 削除対象ID: ' . esc_html($data_id) . ' / テーブル: ' . esc_html($table_name) . '</div>';

            // 削除対象レコードの存在確認
            $debug_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $data_id), ARRAY_A);
            if ($debug_row) {
                echo '<div style="color:green;">DEBUG: レコード存在: '; var_dump($debug_row); echo '</div>';
            } else {
                echo '<div style="color:red;">DEBUG: id=' . esc_html($data_id) . ' のレコードは存在しません</div>';
            }

            // 実際の削除処理（型指定を外す）
            $deleted = $wpdb->delete(
                $table_name,
                array('id' => $data_id)
            );

            // DELETE直後のクエリを退避
            $delete_query = $wpdb->last_query;

            // ロックを解除する
            $wpdb->query("UNLOCK TABLES;");

            if ($deleted === false || $deleted === 0) {
                // 削除失敗時はエラーを表示・ログ出力
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Supplier delete error: ' . $wpdb->last_error . ' | SQL: ' . $delete_query);
                }
                echo '<div style="color:red;font-weight:bold;">削除に失敗しました。管理者にご連絡ください。<br>DBエラー: ' . esc_html($wpdb->last_error) . '<br>SQL: ' . esc_html($delete_query) . '</div>';
                // 画面描画を継続
            } else {
                // データ削除後に表示するデータIDを適切に設定
                $next_id_query = "SELECT id FROM {$table_name} WHERE id > %d ORDER BY id ASC LIMIT 1";
                $next_id_result = $wpdb->get_row($wpdb->prepare($next_id_query, $data_id));
                if ($next_id_result) {
                    $next_data_id = $next_id_result->id;
                } else {
                    $prev_id_query = "SELECT id FROM {$table_name} WHERE id < %d ORDER BY id DESC LIMIT 1";
                    $prev_id_result = $wpdb->get_row($wpdb->prepare($prev_id_query, $data_id));
                    $next_data_id = $prev_id_result ? $prev_id_result->id : '';
                }
                $cookie_name = 'ktp_' . $tab_name . '_id';
                $action = 'update';
                // 現在のURLを取得
                $current_url = add_query_arg(NULL, NULL);
                // tab_name, data_id, query_postパラメータを除去
                $base_url = remove_query_arg(['tab_name', 'data_id', 'query_post'], $current_url);
                // 新しいパラメータを追加
                $redirect_args = [
                    'tab_name' => $tab_name,
                    'query_post' => $action
                ];
                if ($next_data_id !== '') {
                    $redirect_args['data_id'] = $next_data_id;
                }
                $redirect_url = esc_url(add_query_arg($redirect_args, $base_url));
                setcookie($cookie_name, $next_data_id, time() + (86400 * 30), "/"); // 30日間有効
                header("Location: {$redirect_url}");
                exit;
            }
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
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Invalid or missing data_id in Update_Table function'); }
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
            // 現在のURLを取得
            $current_url = add_query_arg(NULL, NULL);
            // tab_name, data_id, query_postパラメータを除去
            $base_url = remove_query_arg(['tab_name', 'data_id', 'query_post'], $current_url);
            // 新しいパラメータを追加
            $redirect_url = esc_url(add_query_arg([
                'tab_name' => $tab_name,
                'data_id' => $data_id,
                'query_post' => $action
            ], $base_url));
            header("Location: {$redirect_url}");
            exit;
        }
        // 検索結果が複数ある場合の処理
        elseif (count($results) > 1) {
            // 得意先タブと同じく、base_page_urlを使い絶対パスでリンク生成
            global $wp;
            $current_page_id = get_queried_object_id();
            $base_page_url = add_query_arg(array('page_id' => $current_page_id), home_url($wp->request));
            $search_results_html = "<div class='data_contents'><div class='search_list_box'><h3>■ 検索結果が複数あります！</h3><ul>";
            foreach ($results as $row) {
                $id = esc_html($row->id);
                $company_name = esc_html($row->company_name);
                $category = esc_html($row->category);
                $link_url = esc_url(add_query_arg(array('tab_name' => $tab_name, 'data_id' => $id, 'query_post' => 'update'), $base_page_url));
                $search_results_html .= "<li style='text-align:left;'><a href='{$link_url}' style='text-align:left;'>ID：{$id} 会社名：{$company_name} カテゴリー：{$category}</a></li>";
            }
            $search_results_html .= "</ul></div></div>";
            $search_results_html_js = json_encode($search_results_html);
            $close_redirect_url = esc_url(add_query_arg(array('tab_name' => $tab_name, 'query_post' => 'srcmode'), $base_page_url));
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
                closeButton.style.color = 'black';
                closeButton.style.display = 'block';
                closeButton.style.margin = '10px auto 0';
                closeButton.style.padding = '10px';
                closeButton.style.backgroundColor = '#cdcccc';
                closeButton.style.borderRadius = '5px';
                closeButton.style.borderColor = '#999';
                closeButton.onclick = function() {
                    document.body.removeChild(popup);
                    // 元の検索モードに戻るために特定のURLにリダイレクト（srcmodeに戻す）
                    location.href = '" . $close_redirect_url . "';
                };
                popup.appendChild(closeButton);
            });
            </script>";
        }
        // 検索結果が0件の場合の処理
        else {
            // サプライヤも得意先タブと同じくセッションメッセージ＋リダイレクト方式に統一
            if (!session_id()) {
                session_start();
            }
            $_SESSION['ktp_search_message'] = '検索結果がありませんでした。';
            // 検索語とno_results=1を付与してsrcmodeにリダイレクト
            global $wp;
            $current_page_id = get_queried_object_id();
            $base_page_url = add_query_arg( array( 'page_id' => $current_page_id ), home_url( $wp->request ) );
            $search_query_encoded = urlencode($_POST['search_query']);
            $redirect_url_base = strtok($base_page_url, '?');
            $query_string = "?page_id=" . $current_page_id . "&tab_name=" . $tab_name . "&query_post=srcmode&search_query=" . $search_query_encoded . "&no_results=1";
            $redirect_url = $redirect_url_base . $query_string;
            header("Location: " . $redirect_url);
            exit;
        }

        // ロックを解除する
        $wpdb->query("UNLOCK TABLES;");
        // exit; を削除し、通常の画面描画を続行
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
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Insert error: ' . $wpdb->last_error); }
            } else {

            // ロックを解除する
            $wpdb->query("UNLOCK TABLES;");

            // 追加後に更新モードにする
            // リダイレクト
            $action = 'update';
            $data_id = $wpdb->insert_id;
            // 現在のURLを取得
            $current_url = add_query_arg(NULL, NULL);
            // tab_name, data_id, query_postパラメータを除去
            $base_url = remove_query_arg(['tab_name', 'data_id', 'query_post'], $current_url);
            // 新しいパラメータを追加
            $redirect_url = esc_url(add_query_arg([
                'tab_name' => $tab_name,
                'data_id' => $data_id,
                'query_post' => $action
            ], $base_url));
            header("Location: {$redirect_url}");
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
            $data->company_name .= '#';

            // IDを削除
            unset($data->id);

            // 頻度を0に設定
            $data->frequency = 0;

            // search_fieldの値を更新
            $data->search_field = implode(', ', [
                $data->time,
                $data->company_name,
                $data->name,
                $data->email,
                $data->url,
                $data->representative_name,
                $data->phone,
                $data->postal_code,
                $data->prefecture,
                $data->city,
                $data->address,
                $data->building,
                $data->closing_day,
                $data->payment_month,
                $data->payment_day,
                $data->payment_method,
                $data->tax_category,
                $data->memo,
                $data->category
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
                // 現在のURLを取得
                $current_url = add_query_arg(NULL, NULL);
                // tab_name, data_id, query_postパラメータを除去
                $base_url = remove_query_arg(['tab_name', 'data_id', 'query_post'], $current_url);
                // 新しいパラメータを追加
                $redirect_url = esc_url(add_query_arg([
                    'tab_name' => $tab_name,
                    'data_id' => $new_data_id,
                    'query_post' => $action
                ], $base_url));
                $cookie_name = 'ktp_' . $tab_name . '_id'; // クッキー名を設定
                setcookie($cookie_name, $new_data_id, time() + (86400 * 30), "/"); // クッキーを保存
                header("Location: {$redirect_url}");
                exit;
            }
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

        // $search_results_listの使用前に初期化
        if (!isset($search_results_list)) {
            $search_results_list = '';
        }

        // -----------------------------
        // リスト表示
        // -----------------------------
        
        // テーブル名
        $table_name = $wpdb->prefix . 'ktp_' . $name;
            // -----------------------------
        // ページネーションリンク
        // -----------------------------
        
        // 表示範囲
        $query_limit = 20;
        
        // リスト表示部分の開始
        $results_h = <<<END
        <div class="data_contents">
            <div class="data_list_box">
            <h3>■ 協力会社リスト（レンジ： $query_limit ）</h3>
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
               $frequency = esc_html($row->frequency);                // リスト項目
                $cookie_name = 'ktp_' . $name . '_id';

                $query_args = array(
                    'tab_name' => $name,
                    'data_id' => $id,
                    'page_start' => $page_start,
                    'page_stage' => $page_stage,
                );

                // 現在の 'page' パラメータを維持する
                if (isset($_GET['page'])) {
                    $query_args['page'] = sanitize_text_field($_GET['page']);
                }

                $item_link_url = esc_url(add_query_arg($query_args));
                $results[] = <<<END
                <a href="{$item_link_url}" onclick="document.cookie = '{$cookie_name}=' + {$id};">
                    <div class="data_list_item">ID: $id $company_name : $category : 頻度($frequency)</div>
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
            $first_page_link_url = esc_url(add_query_arg(array('tab_name' => $name, 'page_start' => $first_start, 'page_stage' => 2, 'flg' => $flg)));
            $results_f .= <<<END
            <a href="{$first_page_link_url}">|<</a> 
            END;
        }

        // 前へリンク
        if ($current_page > 1) {
            $prev_start = ($current_page - 2) * $query_limit;
            $prev_page_link_url = esc_url(add_query_arg(array('tab_name' => $name, 'page_start' => $prev_start, 'page_stage' => 2, 'flg' => $flg)));
            $results_f .= <<<END
            <a href="{$prev_page_link_url}"><</a>
            END;
        }

        // 現在のページ範囲表示と総数
        $page_end = min($total_rows, $current_page * $query_limit);
        $page_start_display = ($current_page - 1) * $query_limit + 1;
        $results_f .= "<div class='stage'> $page_start_display ~ $page_end / $total_rows</div>";

        // 次へリンク（現在のページが最後のページより小さい場合のみ表示）
        if ($current_page < $total_pages) {
            $next_start = $current_page * $query_limit;
            // 現在の 'page' パラメータを維持する
            $query_args_next = array(
                'tab_name' => $name,
                'page_start' => $next_start,
                'page_stage' => 2,
                'flg' => $flg
            );
            if (isset($_GET['page'])) {
                $query_args_next['page'] = sanitize_text_field($_GET['page']);
            }
            $next_page_link_url = esc_url(add_query_arg($query_args_next));
            $results_f .= <<<END
            <a href="{$next_page_link_url}">></a>
            END;
        }

        // 最後へリンク
        if ($current_page < $total_pages) {
            $last_start = ($total_pages - 1) * $query_limit; // 最後のページ
            // 現在の 'page' パラメータを維持する
            $query_args_last = array(
                'tab_name' => $name,
                'page_start' => $last_start,
                'page_stage' => 2,
                'flg' => $flg
            );
            if (isset($_GET['page'])) {
                $query_args_last['page'] = sanitize_text_field($_GET['page']);
            }
            $last_page_link_url = esc_url(add_query_arg($query_args_last));
            $results_f .= <<<END
             <a href="{$last_page_link_url}">>>|</a>
            END;
        }
        
                
        $results_f .= "</div></div>";

       $data_list = $results_h . implode( $results ) . $results_f;

        // -----------------------------
        // 詳細表示(GET)
        // -----------------------------

        // 現在表示中の詳細
        $cookie_name = 'ktp_' . $name . '_id';
        if (isset($_GET['data_id']) && $_GET['data_id'] !== '') {
            $query_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
        } elseif (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] !== '') {
            $cookie_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
            // クッキーIDがDBに存在するかチェック
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE id = %d", $cookie_id));
            if ($exists) {
                $query_id = $cookie_id;
            } else {
                // 存在しなければ最大ID
                $max_id_row = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1");
                $query_id = $max_id_row ? $max_id_row->id : '';
            }
        } else {
            // data_id未指定時は必ずID最大の協力会社を表示
            $max_id_row = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1");
            $query_id = $max_id_row ? $max_id_row->id : '';
        }
        
        // データを取得し変数に格納
        $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $query_id);
        $post_row = $wpdb->get_results($query);
        if (!$post_row || count($post_row) === 0) {
            // 存在しないIDの場合は最大IDを取得して再表示
            $max_id_row = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1");
            if ($max_id_row && isset($max_id_row->id)) {
                $query_id = $max_id_row->id;
                $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $query_id);
                $post_row = $wpdb->get_results($query);
            }
            // それでもデータがなければ「データがありません」
            if (!$post_row || count($post_row) === 0) {
                echo '<div class="data_detail_box"><h3>■ 協力会社の詳細</h3><div style="color:red;font-weight:bold;">データがありません（ID: ' . esc_html($query_id) . '）</div></div>';
                return;
            }
        }
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
        
        // アクションを取得（POST優先、なければGET、なければ'update'）
        $action = isset($_POST['query_post']) ? $_POST['query_post'] : (isset($_GET['query_post']) ? $_GET['query_post'] : 'update');
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
                <h3>■ 協力会社の詳細</h3>
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
            if (function_exists('wp_nonce_field')) { $data_forms .= wp_nonce_field('ktp_supplier_action', 'ktp_supplier_nonce', true, false); }
            foreach ($fields as $label => $field) {
                $value = $action === 'update' ? ${$field['name']} : '';
                $pattern = isset($field['pattern']) ? " pattern=\"{$field['pattern']}\"" : '';
                $required = isset($field['required']) && $field['required'] ? ' required' : '';
                $fieldName = $field['name'];
                $placeholder = isset($field['placeholder']) ? " placeholder=\"{$field['placeholder']}\"" : '';
                if ($field['type'] === 'textarea') {
                    $data_forms .= "<div class=\"form-group\"><label>{$label}：</label> <textarea name=\"{$fieldName}\"{$pattern}{$required}>{$value}</textarea></div>";
                } elseif ($field['type'] === 'select') {
                    $options = '';
                    foreach ($field['options'] as $option) {
                        $selected = $value === $option ? ' selected' : '';
                        $options .= "<option value=\"{$option}\"{$selected}>{$option}</option>";
                    }
                    $default = isset($field['default']) ? $field['default'] : '';
                    $data_forms .= "<div class=\"form-group\"><label>{$label}：</label> <select name=\"{$fieldName}\"{$required}><option value=\"\">{$default}</option>{$options}</select></div>";
                } else {
                    $data_forms .= "<div class=\"form-group\"><label>{$label}：</label> <input type=\"{$field['type']}\" name=\"{$fieldName}\" value=\"{$value}\"{$pattern}{$required}{$placeholder}></div>";
                }
            }
            $data_forms .= "<div class='button'>";
            // 追加実行ボタン
            $data_forms .= "<input type='hidden' name='query_post' value='insert'>";
            $data_forms .= "<input type='hidden' name='data_id' value='" . esc_attr($data_id + 1) . "'>";
            $data_forms .= "<button type='submit' name='send_post' title='追加実行'><span class='material-symbols-outlined'>select_check_box</span></button>";
            // キャンセルボタン
            $data_forms .= "<button type='submit' name='query_post' value='update' title='キャンセル'><input type='hidden' name='data_id' value='" . esc_attr($data_id) . "'><span class='material-symbols-outlined'>disabled_by_default</span></button>";
            $data_forms .= "<div class=\"add\"></div>";
            $data_forms .= '</div>';
            $data_forms .= '</form>';
        }

        // 空のフォームを表示(検索モードの場合)
        elseif ($action === 'srcmode') {
            // 表題
            $data_title = <<<END
            <div class="data_detail_box search-mode">
                <h3>■ 協力会社の詳細（検索モード）</h3>
            END;

            // 検索モード用のフォーム（得意先タブと同じ構造・装飾に）
            $data_forms = '<div class="search-mode-form ktpwp-search-form" style="background-color: #f8f9fa !important; border: 2px solid #0073aa !important; border-radius: 8px !important; padding: 20px !important; margin: 10px 0 !important; box-shadow: 0 2px 8px rgba(0, 115, 170, 0.1) !important;">';
            $data_forms .= '<form method="post" action="">';
            $data_forms .= function_exists('wp_nonce_field') ? wp_nonce_field('ktp_supplier_action', 'ktp_supplier_nonce', true, false) : '';
            // 検索クエリの値を取得（POSTが優先、次にGET）
            $search_query_value = '';
            if (isset($_POST['search_query'])) {
                $search_query_value = esc_attr($_POST['search_query']);
            } elseif (isset($_GET['search_query'])) {
                $search_query_value = esc_attr(urldecode($_GET['search_query']));
            }
            $data_forms .= '<div class="form-group" style="margin-bottom: 15px !important;">';
            $data_forms .= '<input type="text" name="search_query" placeholder="フリーワード検索" value="' . $search_query_value . '" style="width: 100% !important; padding: 12px !important; font-size: 16px !important; border: 2px solid #ddd !important; border-radius: 5px !important; box-sizing: border-box !important; transition: border-color 0.3s ease !important;">';
            $data_forms .= '</div>';

            // 検索結果がない場合のメッセージ表示
            if ((isset($_POST['query_post']) && $_POST['query_post'] === 'search' && empty($search_results_list)) || 
                (isset($_GET['no_results']) && $_GET['no_results'] === '1')) {
                $no_results_id = 'no-results-' . uniqid();
                $data_forms .= '<div id="' . $no_results_id . '" class="no-results" style="
                    padding: 15px 20px !important;
                    background: linear-gradient(135deg, #ffeef1 0%, #ffeff2 100%) !important;
                    border-radius: 6px !important;
                    margin: 15px 0 !important;
                    color: #333333 !important;
                    font-weight: 500 !important;
                    box-shadow: 0 3px 10px rgba(0,0,0,0.08) !important;
                    display: flex !important;
                    align-items: center !important;
                    font-size: 14px !important;
                    opacity: 1;
                    transition: opacity 0.3s ease-in-out !important;
                ">
                <span style="
                    margin-right: 10px !important;
                    color: #ff6b8b !important;
                    font-size: 18px !important;
                " class="material-symbols-outlined">search_off</span>
                検索結果が見つかりませんでした。別のキーワードをお試しください。
                </div>
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var noResultsEl = document.getElementById("' . $no_results_id . '");
                    if (noResultsEl) {
                        // 4秒後に非表示にする
                        setTimeout(function() {
                            noResultsEl.style.opacity = "0";
                            setTimeout(function() {
                                if (noResultsEl.parentNode) {
                                    noResultsEl.style.display = "none";
                                }
                            }, 300);
                        }, 4000);
                    }
                });
                </script>';
            }

            // ボタンを横並びにするためのラップクラスを追加
            $data_forms .= '<div class="button-group" style="display: flex !important; gap: 10px !important; margin-top: 15px !important; align-items: center !important;">';

            // 検索実行ボタン
            $data_forms .= '<input type="hidden" name="query_post" value="search">';
            $data_forms .= '<button type="submit" name="send_post" title="検索実行" style="background-color: #0073aa !important; color: white !important; border: none !important; padding: 10px 20px !important; cursor: pointer !important; border-radius: 5px !important; display: flex !important; align-items: center !important; gap: 5px !important; font-size: 14px !important; font-weight: 500 !important; transition: all 0.3s ease !important;">';
            $data_forms .= '<span class="material-symbols-outlined" style="font-size: 18px !important;">search</span>';
            $data_forms .= '検索実行';
            $data_forms .= '</button>';
            $data_forms .= '</form>';

            // 検索モードのキャンセルボタン（独立したフォーム）
            $data_forms .= '<form method="post" action="" style="margin: 0 !important;">';
            $data_forms .= function_exists('wp_nonce_field') ? wp_nonce_field('ktp_supplier_action', 'ktp_supplier_nonce', true, false) : '';
            $data_forms .= '<input type="hidden" name="query_post" value="update">';
            $data_forms .= '<button type="submit" name="send_post" title="キャンセル" style="background-color: #666 !important; color: white !important; border: none !important; padding: 10px 20px !important; cursor: pointer !important; border-radius: 5px !important; display: flex !important; align-items: center !important; gap: 5px !important; font-size: 14px !important; font-weight: 500 !important; transition: all 0.3s ease !important;">';
            $data_forms .= '<span class="material-symbols-outlined" style="font-size: 18px !important;">disabled_by_default</span>';
            $data_forms .= 'キャンセル';
            $data_forms .= '</button>';
            $data_forms .= '</form>';

            $data_forms .= '</div>'; // ボタンラップクラスの閉じタグ
            $data_forms .= '</div>'; // search-mode-formの閉じタグ
        }

        // 追加・検索 以外なら更新フォームを表示
        elseif ($action !== 'srcmode' && $action !== 'istmode') {

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
            // 1つのフォームで全ての操作ボタンをラップ
            $data_forms .= "<form method=\"post\" action=\"\">";
            if (function_exists('wp_nonce_field')) { $data_forms .= wp_nonce_field('ktp_supplier_action', 'ktp_supplier_nonce', true, false); }

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
                <h3>■ 協力会社の詳細（ ID: $data_id ）</h3>
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

            $data_forms .= "<input type=\"hidden\" name=\"data_id\" value=\"{$data_id}\">";

            // 検索リストを生成
            $data_forms .= $search_results_list;
            $data_forms .= "<div class='button'>";
            // 更新ボタン
            $data_forms .= '<button type="submit" name="query_post" value="update" title="更新する"><span class="material-symbols-outlined">cached</span></button>';
            // 削除ボタン
            $data_forms .= '<button type="submit" name="query_post" value="delete" title="削除する" onclick="return confirm(\'本当に削除しますか？\')"><span class="material-symbols-outlined">delete</span></button>';
            // 複製ボタンは削除
            // 追加モードボタン（data_idをhiddenで渡す）
            $next_data_id = $data_id + 1;
            $data_forms .= '<button type="submit" name="query_post" value="istmode" title="追加する" style="position:relative;"><span class="material-symbols-outlined">add</span><input type="hidden" name="data_id" value="' . esc_attr($next_data_id) . '"></button>';
            // 検索モードボタン
            $data_forms .= '<button type="submit" name="query_post" value="srcmode" title="検索する"><span class="material-symbols-outlined">search</span></button>';
            $data_forms .= "<div class=\"add\"></div>";
            $data_forms .= '</div>';
            $data_forms .= '</form>';
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
            'representative_name' => $representative_name,
            'postal_code' => $postal_code,
            'prefecture' => $prefecture,
            'city' => $city,
            'address' => $address,
            'building' => $data_src['building'],
        ];

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
            var isPreviewOpen = false;            function printContent() {
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

        </script>        <div class="controller">
            <div class="printer">
                <button id="previewButton" onclick="togglePreview()" title="プレビュー">
                    <span class="material-symbols-outlined" aria-label="プレビュー">preview</span>
                </button>
                <button onclick="printContent()" title="印刷する">
                    <span class="material-symbols-outlined" aria-label="印刷">print</span>
                </button>
            </div>        </div>
        <div class="workflow">
        </div>
        <div id="previewWindow" style="display: none;"></div>
        END;

        // コンテンツを返す
        $content = $print . $data_list . $data_title . $data_forms . $search_results_list . $div_end;
        return $content;
        
    }

}

// $search_results_listの初期化
if (!isset($search_results_list)) {
    $search_results_list = '';
}
} // class_exists