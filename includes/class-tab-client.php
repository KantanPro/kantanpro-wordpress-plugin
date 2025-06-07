<?php
/**
 * Client Tab Class for KTPWP Plugin
 *
 * Handles client management functionality including table creation,
 * data display, and client information management.
 *
 * @package KTPWP
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('Kntan_Client_Class')) {
class Kntan_Client_Class {

    public function __construct() {

    }
    
    /**
     * ソートプルダウンを生成するメソッド
     * 
     * @param string $name テーブル名サフィックス
     * @param string $view_mode 表示モード
     * @param string $base_page_url 基本URL
     * @param string $sort_by ソートカラム
     * @param string $sort_order ソート順
     * @param string $order_sort_by 注文履歴ソートカラム
     * @param string $order_sort_order 注文履歴ソート順
     * @return string ソートプルダウンHTML
     */
    private function generate_sort_dropdown($name, $view_mode, $base_page_url, $sort_by, $sort_order, $order_sort_by, $order_sort_order) {
        global $wpdb;
        $sort_dropdown = '';
        
        // 顧客リストのソートプルダウン
        if ($view_mode !== 'order_history') {
            // 現在のURLからソート用プルダウンのアクションURLを生成
            $sort_url = add_query_arg(array('tab_name' => $name), $base_page_url);
            
            // ソート用プルダウンのHTMLを構築
            $sort_dropdown = '<div class="sort-dropdown" style="float:right;margin-left:10px;">' .
                '<form method="get" action="' . esc_url($sort_url) . '" style="display:flex;align-items:center;">';
            
            // 現在のGETパラメータを維持するための隠しフィールド
            foreach ($_GET as $key => $value) {
                if ($key !== 'sort_by' && $key !== 'sort_order') {
                    $sort_dropdown .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }
            
            $sort_dropdown .= 
                '<select id="sort-select" name="sort_by" style="margin-right:5px;">' .
                '<option value="id" ' . selected($sort_by, 'id', false) . '>' . esc_html__('ID', 'ktpwp') . '</option>' .
                '<option value="company_name" ' . selected($sort_by, 'company_name', false) . '>' . esc_html__('会社名', 'ktpwp') . '</option>' .
                '<option value="frequency" ' . selected($sort_by, 'frequency', false) . '>' . esc_html__('頻度', 'ktpwp') . '</option>' .
                '<option value="time" ' . selected($sort_by, 'time', false) . '>' . esc_html__('登録日', 'ktpwp') . '</option>' .
                '<option value="client_status" ' . selected($sort_by, 'client_status', false) . '>' . esc_html__('対象｜対象外', 'ktpwp') . '</option>' .
                '<option value="category" ' . selected($sort_by, 'category', false) . '>' . esc_html__('カテゴリー', 'ktpwp') . '</option>' .
                '</select>' .
                '<select id="sort-order" name="sort_order">' .
                '<option value="ASC" ' . selected($sort_order, 'ASC', false) . '>' . esc_html__('昇順', 'ktpwp') . '</option>' .
                '<option value="DESC" ' . selected($sort_order, 'DESC', false) . '>' . esc_html__('降順', 'ktpwp') . '</option>' .
                '</select>' .
                '<button type="submit" style="margin-left:5px;padding:4px 8px;background:#f0f0f0;border:1px solid #ccc;border-radius:3px;cursor:pointer;" title="' . esc_attr__('適用', 'ktpwp') . '">' .
                '<span class="material-symbols-outlined" style="font-size:18px;line-height:18px;vertical-align:middle;">check</span>' .
                '</button>' .
                '</form></div>';
        }
        // 注文履歴のソートプルダウン
        else {
            // 現在表示中の顧客ID
            $cookie_name = 'ktp_' . $name . '_id';
            $client_id = null;
            
            if (isset($_GET['data_id'])) {
                $client_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
            } elseif (isset($_COOKIE[$cookie_name])) {
                $client_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
            }
            
            // 現在のURLからソート用プルダウンのアクションURLを生成
            $sort_url = add_query_arg(array(
                'tab_name' => $name, 
                'view_mode' => 'order_history',
                'data_id' => $client_id ?? ''
            ), $base_page_url);
            
            // ソート用プルダウンのHTMLを構築
            $sort_dropdown = '<div class="sort-dropdown" style="float:right;margin-left:10px;">' .
                '<form method="get" action="' . esc_url($sort_url) . '" style="display:flex;align-items:center;">';
            
            // 現在のGETパラメータを維持するための隠しフィールド
            foreach ($_GET as $key => $value) {
                if ($key !== 'order_sort_by' && $key !== 'order_sort_order') {
                    $sort_dropdown .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }
            
            $sort_dropdown .= 
                '<select id="order-sort-select" name="order_sort_by" style="margin-right:5px;">' .
                '<option value="id" ' . selected($order_sort_by, 'id', false) . '>' . esc_html__('注文ID', 'ktpwp') . '</option>' .
                '<option value="time" ' . selected($order_sort_by, 'time', false) . '>' . esc_html__('日付', 'ktpwp') . '</option>' .
                '<option value="progress" ' . selected($order_sort_by, 'progress', false) . '>' . esc_html__('進捗', 'ktpwp') . '</option>' .
                '<option value="project_name" ' . selected($order_sort_by, 'project_name', false) . '>' . esc_html__('案件名', 'ktpwp') . '</option>' .
                '</select>' .
                '<select id="order-sort-order" name="order_sort_order">' .
                '<option value="ASC" ' . selected($order_sort_order, 'ASC', false) . '>' . esc_html__('昇順', 'ktpwp') . '</option>' .
                '<option value="DESC" ' . selected($order_sort_order, 'DESC', false) . '>' . esc_html__('降順', 'ktpwp') . '</option>' .
                '</select>' .
                '<button type="submit" style="margin-left:5px;padding:4px 8px;background:#f0f0f0;border:1px solid #ccc;border-radius:3px;cursor:pointer;" title="' . esc_attr__('適用', 'ktpwp') . '">' .
                '<span class="material-symbols-outlined" style="font-size:18px;line-height:18px;vertical-align:middle;">check</span>' .
                '</button>' .
                '</form></div>';
        }
        
        return $sort_dropdown;
    }
    
    // -----------------------------
    // テーブル作成
    // -----------------------------
    

    /**
     * Get cookie value or default
     *
     * @deprecated 1.1.0 Use KTPWP_Client_UI::get_instance()->set_cookie() instead
     * @param string $name Cookie name suffix
     * @return int Sanitized ID value
     */
    public function set_cookie($name) {
        if ( ! class_exists('KTPWP_Client_UI') ) {
            require_once dirname(__FILE__) . '/class-ktpwp-client-ui.php';
        }
        return KTPWP_Client_UI::get_instance()->set_cookie($name);
    }

    /**
     * Create client table
     *
     * @param string $tab_name Table name suffix (sanitized)
     * @return bool Success status
     */
    public function create_table($tab_name) {
        if ( ! class_exists('KTPWP_Client_DB') ) {
            require_once dirname(__FILE__) . '/class-ktpwp-client-db.php';
        }
        return KTPWP_Client_DB::get_instance()->create_table($tab_name);
    }

    // -----------------------------
    // テーブルの操作（更新・追加・削除・検索）
    // -----------------------------

    /**
     * Update table and handle POST operations
     *
     * @deprecated 1.1.0 Use KTPWP_Client_DB::get_instance()->update_table() instead
     * @param string $tab_name Table name suffix
     * @return void
     */
    function Update_Table($tab_name) {
        if ( ! class_exists('KTPWP_Client_DB') ) {
            require_once dirname(__FILE__) . '/class-ktpwp-client-db.php';
        }
        return KTPWP_Client_DB::get_instance()->update_table($tab_name);
    }
                    
    // 次に表示するIDを取得するヘルパーメソッド
    /**
     * @deprecated 1.1.0 Use KTPWP_Client_DB::get_instance()->get_next_display_id() instead
     */
    private function get_next_display_id($table_name, $deleted_id) {
        if ( ! class_exists('KTPWP_Client_DB') ) {
            require_once dirname(__FILE__) . '/class-ktpwp-client-db.php';
        }
        return KTPWP_Client_DB::get_instance()->get_next_display_id($table_name, $deleted_id);
    }

    
    // -----------------------------
    // テーブルの表示
    // -----------------------------

    /**
     * View client table
     * 
     * @param string $name Table name suffix
     * @return void
     */
    function View_Table($name) {
        global $wpdb;

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // View_Table method started
            // error_log('KTPWP Client: View_Table method started');
        }

        // $search_results_listの使用前に初期化
        $search_results_list = '';

        // テーブル名
        $table_name = $wpdb->prefix . 'ktp_' . $name;
        
        // 表示モードの取得（デフォルトは顧客一覧）
        $view_mode = isset($_GET['view_mode']) ? sanitize_text_field($_GET['view_mode']) : 'customer_list';

        // ソート順の取得（デフォルトはIDの降順）
        $sort_by = 'id';
        $sort_order = 'DESC';
        
        // 注文履歴用のソート順（デフォルトは日付の降順）
        $order_sort_by = 'time';
        $order_sort_order = 'DESC';
        
        if (isset($_GET['sort_by'])) {
            $sort_by = sanitize_text_field($_GET['sort_by']);
            // 安全なカラム名のみ許可（SQLインジェクション対策）
            $allowed_columns = array('id', 'company_name', 'frequency', 'time', 'client_status', 'category');
            if (!in_array($sort_by, $allowed_columns)) {
                $sort_by = 'id'; // 不正な値の場合はデフォルトに戻す
            }
        }
        
        if (isset($_GET['sort_order'])) {
            $sort_order_param = strtoupper(sanitize_text_field($_GET['sort_order']));
            // ASCかDESCのみ許可
            $sort_order = ($sort_order_param === 'ASC') ? 'ASC' : 'DESC';
        }
        
        // 注文履歴のソート順を取得
        if (isset($_GET['order_sort_by'])) {
            $order_sort_by = sanitize_text_field($_GET['order_sort_by']);
            // 安全なカラム名のみ許可（SQLインジェクション対策）
            $allowed_order_columns = array('id', 'time', 'progress', 'project_name');
            if (!in_array($order_sort_by, $allowed_order_columns)) {
                $order_sort_by = 'time'; // 不正な値の場合はデフォルトに戻す
            }
        }
        
        if (isset($_GET['order_sort_order'])) {
            $order_sort_order_param = strtoupper(sanitize_text_field($_GET['order_sort_order']));
            // ASCかDESCのみ許可
            $order_sort_order = ($order_sort_order_param === 'ASC') ? 'ASC' : 'DESC';
        }

        // 現在のページのURLを生成
        global $wp;
        $current_page_id = get_queried_object_id();
        // home_url() と $wp->request を使用して、現在のURLを取得し、page_idを追加
        $base_page_url = add_query_arg( array( 'page_id' => $current_page_id ), home_url( $wp->request ) );

        // 表示タイトルの設定（国際化対応）
        $list_title = ($view_mode === 'order_history')
            ? esc_html__('■ 注文履歴', 'ktpwp')
            : esc_html__('■ 顧客リスト', 'ktpwp');

        // ソートプルダウンを生成
        if (method_exists($this, 'generate_sort_dropdown')) {
            $sort_dropdown = $this->generate_sort_dropdown($name, $view_mode, $base_page_url, $sort_by, $sort_order, $order_sort_by, $order_sort_order);
        } else {
            // UIクラスから適切なソートプルダウンを取得
            if (!class_exists('KTPWP_Client_UI')) {
                require_once dirname(__FILE__) . '/class-ktpwp-client-ui.php';
            }
            $ui = KTPWP_Client_UI::get_instance();
            $sort_dropdown = $ui->render_list_header($name, $view_mode, '', $base_page_url, $sort_by, $sort_order, $order_sort_by, $order_sort_order);
            // ヘッダー全体ではなくドロップダウンだけを取得するため、必要な部分だけを抽出
            if (preg_match('/<div class="sort-dropdown".*?<\/div><\/div>/s', $sort_dropdown, $matches)) {
                $sort_dropdown = $matches[0];
            } else {
                $sort_dropdown = '';
            }
        }
        
        // 段階的にUIクラスに処理を委譲
        if ( ! class_exists('KTPWP_Client_UI') ) {
            require_once dirname(__FILE__) . '/class-ktpwp-client-ui.php';
        }
        $client_ui = KTPWP_Client_UI::get_instance();
        $results_h = $client_ui->view_table($name);
        
       // スタート位置を決める
       $page_stage = $_GET['page_stage'] ?? '';
       $page_start = $_GET['page_start'] ?? 0;
       $flg = $_GET['flg'] ?? '';
       if ($page_stage == '') {
           $page_start = 0;
       }
       
       // 表示件数を取得
       $query_limit = 20; // デフォルト値
       if (class_exists('KTP_Settings')) {
           $query_limit = KTP_Settings::get_work_list_range();
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
           $related_client_ids = [$client_id];
           
           // IDのリストを文字列に変換（安全対策）
           $client_ids_str = implode(',', array_map('intval', $related_client_ids));
           
           if (empty($client_ids_str)) {
               $client_ids_str = '0'; // 安全なフォールバック値
           }
           
           // IDが複数ある場合、IN句を使用
           $total_query = "SELECT COUNT(*) FROM {$order_table} WHERE client_id IN ({$client_ids_str})";
           $total_rows = $wpdb->get_var($total_query);
           $total_pages = ceil($total_rows / $query_limit);
           
           // 現在のページ番号を計算
           $current_page = floor($page_start / $query_limit) + 1;
           
           // この顧客の受注書を取得
           $related_client_ids = [$client_id];
           
           // IDのリストを文字列に変換（safety check）
           $client_ids_str = implode(',', array_map('intval', $related_client_ids));
           
           if (empty($client_ids_str)) {
               $client_ids_str = '0'; // 安全なフォールバック値
           }
           
           // IDが複数ある場合、IN句を使用（ソートオプションを適用）
           $order_sort_column = esc_sql($order_sort_by); // SQLインジェクション対策
           $order_sort_direction = $order_sort_order === 'ASC' ? 'ASC' : 'DESC'; // SQLインジェクション対策
           
           $query = $wpdb->prepare(
               "SELECT * FROM {$order_table} WHERE client_id IN ({$client_ids_str}) ORDER BY {$order_sort_column} {$order_sort_direction} LIMIT %d, %d", 
               intval($page_start), intval($query_limit)
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
               <div class="ktp_data_contents">
                   <div class="ktp_data_list_box">
                   <div class="data_list_title">■ {$client_name} の注文履歴</div>
               END;
               
               if (isset($sort_dropdown)) {
                   $results_h = str_replace('</div>', "$sort_dropdown</div>", $results_h);
               }
               
               $results = array(); // 結果を格納する配列を初期化
               
               if ($order_rows) {
                   // 進捗ラベル
                   $progress_labels = [
                       1 => esc_html__('受付中', 'ktpwp'),
                       2 => esc_html__('見積中', 'ktpwp'),
                       3 => esc_html__('作成中', 'ktpwp'),
                       4 => esc_html__('完成未請求', 'ktpwp'),
                       5 => esc_html__('請求済', 'ktpwp'),
                       6 => esc_html__('入金済', 'ktpwp')
                   ];
                   
                   foreach ($order_rows as $order) {
                       $order_id = esc_html($order->id);
                       $project_name = isset($order->project_name) ? esc_html($order->project_name) : '';
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
                       
                       // 受注書の詳細へのリンク（シンプルなURL生成）
                       $detail_url = add_query_arg(array('tab_name' => 'order', 'order_id' => $order_id), $base_page_url);
                       
                       // リスト項目を生成
                       $results[] = <<<END
                       <a href="{$detail_url}">
                           <div class="ktp_data_list_item">ID: {$order_id} - {$project_name} <span style="float:right;" class="status-{$progress}">{$progress_label}</span></div>
                       </a>
                       END;
                   }
               } else {
                   $results[] = '<div class="ktp_data_list_item">' . esc_html__('この顧客の受注データはありません。', 'ktpwp') . '</div>';
               }
           } else {
               $results[] = '<div class="ktp_data_list_item">' . esc_html__('顧客データが見つかりません。', 'ktpwp') . '</div>';
           }
       } else {
           // 通常の顧客一覧表示（既存のコード）
           // 全データ数を取得
           $total_query = "SELECT COUNT(*) FROM {$table_name}";
           $total_rows = $wpdb->get_var($total_query);
           $total_pages = ceil($total_rows / $query_limit);

           // 現在のページ番号を計算
           $current_page = floor($page_start / $query_limit) + 1;

           // データを取得（選択されたソート順で）
           $sort_column = esc_sql($sort_by); // SQLインジェクション対策
           $sort_direction = $sort_order === 'ASC' ? 'ASC' : 'DESC'; // SQLインジェクション対策
           $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY {$sort_column} {$sort_direction} LIMIT %d, %d", intval($page_start), intval($query_limit));
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
                   $client_status = esc_html($row->client_status);
                   $frequency = esc_html($row->frequency);
                   
                   // リスト項目
                   $cookie_name = 'ktp_' . $name . '_id';
                   $link_url = esc_url(add_query_arg(array('tab_name' => $name, 'data_id' => $id, 'page_start' => $page_start, 'page_stage' => $page_stage), $base_page_url));
                   
                   // 削除済み（対象外）の場合の視覚的スタイリング
                   $list_style = '';
                   $deleted_mark = '';
                   if ($client_status === '対象外') {
                       $list_style = ' style="background-color: #ffe6e6; border-left: 3px solid #ff4444;"';
                       $deleted_mark = '<span style="color: #ff4444; font-weight: bold; margin-right: 5px;">[削除済み]</span>';
                   }
                   
           $results[] = '<a href="' . $link_url . '" onclick="document.cookie = \'{$cookie_name}=\' + ' . $id . ';">'
               . '<div class="ktp_data_list_item"' . $list_style . '>' . $deleted_mark . sprintf(esc_html__('ID: %1$s %2$s : %3$s : %4$s : 頻度(%5$s)', 'ktpwp'), $id, $company_name, $user_name, $client_status, $frequency) . '</div>'
               . '</a>';
               }
           } else {
           $results[] = '<div class="ktp_data_list_item" style="padding: 15px 20px; background: linear-gradient(135deg, #ffeef1 0%, #ffeff2 100%); border-radius: 6px; margin: 15px 0; color: #333333; font-weight: 500; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08); display: flex; align-items: center; font-size: 14px;">'
               . '<span style="margin-right: 10px; color: #ff6b8b; font-size: 18px;" class="material-symbols-outlined">search_off</span>'
               . esc_html__('データーがありません。', 'ktpwp')
               . '<span style="margin-left: 16px; font-size: 13px; color: #888;">'
               . esc_html__('フォームに入力して更新してください。', 'ktpwp')
               . '</span>'
               . '</div>';
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
            // $first_link = '?' . http_build_query($base_params);
            $first_link = esc_url(add_query_arg($base_params, $base_page_url));
            $results_f .= <<<END
            <a href="$first_link">|<</a> 
            END;
        }

        // 前へリンク
        if ($current_page > 1) {
            $base_params['page_start'] = ($current_page - 2) * $query_limit;
            // $prev_link = '?' . http_build_query($base_params);
            $prev_link = esc_url(add_query_arg($base_params, $base_page_url));
            $results_f .= <<<END
            <a href="$prev_link"><</a>
            END;
        }

        // 現在のページ範囲表示と総数
        $page_end = min($total_rows, $current_page * $query_limit);
        $page_start_display = ($current_page - 1) * $query_limit + 1;
        $results_f .= "<div class='stage'> " . sprintf(esc_html__('%1$s ~ %2$s / %3$s', 'ktpwp'), $page_start_display, $page_end, $total_rows) . "</div>";

        // 次へリンク（現在のページが最後のページより小さい場合のみ表示）
        if ($current_page < $total_pages) {
            $base_params['page_start'] = $current_page * $query_limit;
            // $next_link = '?' . http_build_query($base_params);
            $next_link = esc_url(add_query_arg($base_params, $base_page_url));
            $results_f .= <<<END
             <a href="$next_link">></a>
            END;
        }

        // 最後へリンク
        if ($current_page < $total_pages) {
            $base_params['page_start'] = ($total_pages - 1) * $query_limit;
            // $last_link = '?' . http_build_query($base_params);
            $last_link = esc_url(add_query_arg($base_params, $base_page_url));
            $results_f .= <<<END
             <a href="$last_link">>|</a>
            END;
        }
                        
        $results_f .= "</div></div>";

       $data_list = $results_h . implode( $results ) . $results_f;

        // -----------------------------
        // 詳細表示(GET)
        // -----------------------------

        // アクションを取得（POSTパラメータを優先、次にGETパラメータ、デフォルトは'update'）
        $action = isset($_POST['query_post']) ? $_POST['query_post'] : (isset($_GET['query_post']) ? $_GET['query_post'] : 'update');
        
        // 安全性確保: GETリクエストの場合は危険なアクションを実行しない
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && in_array($action, ['delete', 'insert', 'search', 'duplicate', 'istmode', 'srcmode'])) {
            $action = 'update';
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
        }
        
        // デバッグ: アクション値を確認
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        }
        
        // アクション値を保護するため、元の値を保存
        $original_action = $action;
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        }

        // 初期化：追加モードかどうかを最初に判定
        $cookie_name = 'ktp_' . $name . '_id';
        $query_id = null;
        
        // 変数の初期化
        $data_id = '';
        $time = '';
        $company_name = '';
        $user_name = '';
        $email = '';
        $url = '';
        $representative_name = '';
        $phone = '';
        $postal_code = '';
        $prefecture = '';
        $city = '';
        $address = '';
        $building = '';
        $closing_day = '';
        $payment_month = '';
        $payment_day = '';
        $payment_method = '';
        $tax_category = '';
        $memo = '';
        $client_status = '';
        $order_customer_name = '';
        $order_user_name = '';
        
        // 追加モード以外の場合のみデータを取得
        if ($action !== 'istmode') {
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
                // data_id未指定時は必ずID最大の得意先を表示
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
                    // データが0件でもフォーム・レイアウトを必ず出す
                    $data_id = '';
                    $time = '';
                    $company_name = '';
                    $user_name = '';
                    $email = '';
                    $url = '';
                    $representative_name = '';
                    $phone = '';
                    $postal_code = '';
                    $prefecture = '';
                    $city = '';
                    $address = '';
                    $building = '';
                    $closing_day = '';
                    $payment_month = '';
                    $payment_day = '';
                    $payment_method = '';
                    $tax_category = '';
                    $memo = '';
                    $client_status = '対象';
                    $order_customer_name = '';
                    $order_user_name = '';
                    // $post_row を空配列にして以降のフォーム生成処理を通す
                    $post_row = [];
                    // リスト部分にだけ「データがありません」メッセージを出す
                    $results[] = '<div class="ktp_data_list_item">' . esc_html__('データーがありません。', 'ktpwp') . '</div>';
                }
            }
            // 表示したIDをクッキーに保存
            setcookie($cookie_name, $query_id, time() + (86400 * 30), "/"); // 30日間有効
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
                $client_status = esc_html($row->client_status);
                // 受注書作成用のデータを保持
                $order_customer_name = $company_name;
                $order_user_name = $user_name;
            }
        } else {
            // 追加モードの場合は全ての変数を空で初期化
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            }
            $data_id = '';
            $time = '';
            $company_name = '';
            $user_name = '';
            $email = '';
            $url = '';
            $representative_name = '';
            $phone = '';
            $postal_code = '';
            $prefecture = '';
            $city = '';
            $address = '';
            $building = '';
            $closing_day = '';
            $payment_month = '';
            $payment_day = '';
            $payment_method = '';
            $tax_category = '';
            $memo = '';
            $client_status = '対象'; // デフォルト値を設定
            $order_customer_name = '';
            $order_user_name = '';
        }
        
        // カテゴリーフィールド用の値を初期化（未定義警告対策）
        $category_value = '';
        // 表示するフォーム要素を定義
        $fields = [
            // 'ID' => ['type' => 'text', 'name' => 'data_id', 'readonly' => true],
            '会社名' => ['type' => 'text', 'name' => 'company_name', 'required' => true, 'placeholder' => '必須 法人名または屋号'],
            '名前' => ['type' => 'text', 'name' => 'user_name', 'placeholder' => '担当者名'],
            'メール' => ['type' => 'email', 'name' => 'email'],
            'URL' => ['type' => 'text', 'name' => 'url', 'placeholder' => 'https://....'],
            '代表者名' => ['type' => 'text', 'name' => 'representative_name', 'placeholder' => '代表者名'],
            '電話番号' => ['type' => 'text', 'name' => 'phone', 'pattern' => '\\d*', 'placeholder' => '半角数字 ハイフン不要'],
            '郵便番号' => ['type' => 'text', 'name' => 'postal_code', 'pattern' => '[0-9]*', 'placeholder' => '半角数字 ハイフン不要'],
            '都道府県' => ['type' => 'text', 'name' => 'prefecture'],
            '市区町村' => ['type' => 'text', 'name' => 'city'],
            '番地' => ['type' => 'text', 'name' => 'address'],
            '建物名' => ['type' => 'text', 'name' => 'building'],
            '締め日' => ['type' => 'select', 'name' => 'closing_day', 'options' => ['5日', '10日', '15日', '20日', '25日', '末日', 'なし'], 'default' => 'なし'],
            '支払月' => ['type' => 'select', 'name' => 'payment_month', 'options' => ['今月', '翌月', '翌々月', 'その他'], 'default' => 'その他'],
            '支払日' => ['type' => 'select', 'name' => 'payment_day', 'options' => ['即日', '5日', '10日', '15日', '20日', '25日', '末日'], 'default' => '即日'],
            '支払方法' => ['type' => 'select', 'name' => 'payment_method', 'options' => ['銀行振込（後）','銀行振込（前）', 'クレジットカード', '現金集金'], 'default' => '銀行振込（前）'],
            '税区分' => ['type' => 'select', 'name' => 'tax_category', 'options' => ['税込', '税抜'], 'default' => '税込'],
            'カテゴリー' => ['type' => 'text', 'name' => 'category', 'value' => $category_value], // 新しいカテゴリーフィールド
            '対象｜対象外' => ['type' => 'select', 'name' => 'client_status', 'options' => ['対象', '対象外'], 'default' => '対象'],
            'メモ' => ['type' => 'textarea', 'name' => 'memo'],
        ];
        
        $data_forms = ''; // フォームのHTMLコードを格納する変数を初期化
        $data_title = ''; // タイトルのHTMLコードを格納する変数を初期化
        $div_end = ''; // 終了タグを格納する変数を初期化
        
        $data_forms .= '<div class="box">'; // フォームを囲む<div>タグの開始タグを追加

        // URL パラメータからのメッセージ表示処理を追加
        $session_message = '';
        if (isset($_GET['message'])) {
            echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const messageType = "' . esc_js($_GET['message']) . '";
                switch (messageType) {
                    case "updated":
                        showSuccessNotification("' . esc_js(__('更新しました。', 'ktpwp')) . '");
                        break;
                    case "added":
                        showSuccessNotification("' . esc_js(__('新しい顧客を追加しました。', 'ktpwp')) . '");
                        break;
                    case "deleted":
                        showSuccessNotification("' . esc_js(__('削除しました。', 'ktpwp')) . '");
                        break;
                    case "found":
                        showInfoNotification("' . esc_js(__('検索結果を表示しています。', 'ktpwp')) . '");
                        break;
                    case "not_found":
                        showWarningNotification("' . esc_js(__('該当する顧客が見つかりませんでした。', 'ktpwp')) . '");
                        break;
                }
            });
            </script>';
        }

        // セッションメッセージの確認と表示
        if (!session_id()) {
            session_start();
        }
        if (isset($_SESSION['ktp_search_message'])) {
            $message_id = 'ktp-message-' . uniqid();
            $session_message = '<div id="' . $message_id . '" class="ktp-message" style="
                padding: 15px 20px;
                background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
                border-radius: 6px;
                margin: 10px 0;
                color: #333333;
                font-weight: 500;
                box-shadow: 0 3px 10px rgba(0,0,0,0.08);
                display: flex;
                align-items: center;
                font-size: 14px;
                max-width: 90%;
            ">
            <span style="margin-right: 10px; color: #ff6b8b; font-size: 18px;" class="material-symbols-outlined">info</span>'
                . esc_html($_SESSION['ktp_search_message']) . '</div>';
            unset($_SESSION['ktp_search_message']); // メッセージを表示後に削除
        }

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
            // $wpdb と $table_name がこのスコープで利用可能である必要がある
            if ( isset( $wpdb, $table_name ) ) {
                $query = $wpdb->prepare( "SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1" );
                $last_id_row = $wpdb->get_row($query);
                $current_client_id = $last_id_row ? $last_id_row->id : 0;
            }
        }
        $current_client_id = (int) $current_client_id;
        
        // 受注書作成用に現在の顧客IDから最新のデータを取得する
        $current_customer_name = '';
        $current_user_name = '';
        if ($current_client_id > 0) {
            $current_client_data_query = $wpdb->prepare("SELECT company_name, name FROM {$table_name} WHERE id = %d", $current_client_id);
            $current_client_data = $wpdb->get_row($current_client_data_query);
            if ($current_client_data) {
                $current_customer_name = esc_html($current_client_data->company_name);
                $current_user_name = esc_html($current_client_data->name);
            }
        }
        
        // 注文履歴ボタン - 現在の顧客IDを保持して遷移
        $order_history_active = (isset($view_mode) && $view_mode === 'order_history') ? 'active' : '';
        $order_history_params = array(
            'tab_name'  => 'client',
            'view_mode' => 'order_history',
            'data_id'   => $current_client_id
        );
        $order_history_url = add_query_arg( $order_history_params, $base_page_url );
        $js_redirect_order_history = sprintf("window.location.href='%s'", esc_url($order_history_url));
        $workflow_html .= '<button type="button" class="view-mode-btn order-history-btn ' . $order_history_active . '" onclick="' . $js_redirect_order_history . '" style="padding: 8px 12px; font-size: 14px;">注文履歴</button>';
        
        // 顧客一覧ボタン - 現在の顧客IDを保持して遷移
        $customer_list_active = (isset($view_mode) && $view_mode === 'customer_list') ? 'active' : '';
        $customer_list_params = array(
            'tab_name'  => 'client',
            'view_mode' => 'customer_list',
            'data_id'   => $current_client_id
        );
        $customer_list_url = add_query_arg( $customer_list_params, $base_page_url );
        $js_redirect_customer_list = sprintf("window.location.href='%s'", esc_url($customer_list_url));
        $workflow_html .= '<button type="button" class="view-mode-btn customer-list-btn ' . $customer_list_active . '" onclick="' . $js_redirect_customer_list . '" style="padding: 8px 12px; font-size: 14px;">顧客一覧</button>';
        
        $workflow_html .= '<div class="order-btn-box" style="margin-left:auto;">';
        $workflow_html .= '<form method="post" action="" id="create-order-form">';
        $workflow_html .= wp_nonce_field('ktp_client_action', 'ktp_client_nonce', true, false);
        $workflow_html .= '<input type="hidden" name="tab_name" value="order">';
        $workflow_html .= '<input type="hidden" name="from_client" value="1">';
        // 常に最新の顧客データを使用する（複製後のデータを反映）
        $customer_name_to_use = !empty($current_customer_name) ? $current_customer_name : $order_customer_name;
        $user_name_to_use = !empty($current_user_name) ? $current_user_name : $order_user_name;
        $workflow_html .= '<input type="hidden" name="customer_name" value="' . esc_attr($customer_name_to_use) . '">';
        $workflow_html .= '<input type="hidden" name="user_name" value="' . esc_attr($user_name_to_use) . '">';
        $workflow_html .= '<input type="hidden" id="client-id-input" name="client_id" value="' . esc_attr($current_client_id) . '">';
        $is_data_empty = empty($post_row) && empty($data_id);
        $disabled_attr = $is_data_empty ? 'disabled style="background:#ccc;color:#888;cursor:not-allowed;"' : '';
        $button_style = 'font-size: 14px;';
        if (!$is_data_empty) {
            $button_style = 'padding: 8px 12px; font-size: 14px;';
        }
        $workflow_html .= '<button type="submit" class="create-order-btn" ' . $disabled_attr . ' style="' . $button_style . '">受注書作成</button>';
        $workflow_html .= '</form>';
        
        $workflow_html .= '</div>';
        $workflow_html .= '</div>';
        $workflow_html .= '</div>';

        // 空のフォームを表示(追加モードの場合)
        if ($action === 'istmode') {

                // デバッグ: 追加モード実行時のアクション値確認
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                }

                $data_id = $wpdb->insert_id;

                // 詳細表示部分の開始
            $data_title = '<div class="data_detail_box"><div class="data_detail_title">' . esc_html__('■ 顧客の詳細', 'ktpwp') . '</div>';

                // 追加モード用のフォーム開始
            $data_forms .= '<form method="post" action="">';
            // nonceフィールド追加
            $data_forms .= wp_nonce_field('ktp_client_action', 'ktp_client_nonce', true, false);
            
            // デバッグ: フォーム生成開始をログ出力
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            }
            
            // 空のフォームフィールドを生成
            foreach ($fields as $label => $field) {
                // 追加モード（istmode）では常に空の値を設定
                $value = ($action === 'istmode') ? '' : (isset(${$field['name']}) ? ${$field['name']} : '');
                
                // デバッグ: istmode時のフィールド値をログ出力
                if ($action === 'istmode') {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    }
                }
                
                $pattern = isset($field['pattern']) ? " pattern=\"{$field['pattern']}\"" : '';
                $required = isset($field['required']) && $field['required'] ? ' required' : '';
                $fieldName = $field['name'];
                $placeholder = isset($field['placeholder']) ? " placeholder=\"" . esc_attr__($field['placeholder'], 'ktpwp') . "\"" : '';
                $label_i18n = esc_html__($label, 'ktpwp');
                if ($field['type'] === 'textarea') {
                    $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <textarea name=\"{$fieldName}\"{$pattern}{$required}>" . esc_textarea($value) . "</textarea></div>";
                } elseif ($field['type'] === 'select') {
                    $options = '';
                    foreach ($field['options'] as $option) {
                        // 追加モードではデフォルト値を選択、更新モードでは現在の値を選択
                        if ($action === 'istmode') {
                            // 追加モードの場合、デフォルト値があれば選択
                            $selected = (isset($field['default']) && $field['default'] === $option) ? ' selected' : '';
                        } else {
                            // 更新モードの場合、現在の値を選択
                            $selected = ($value === $option) ? ' selected' : '';
                        }
                        $options .= "<option value=\"" . esc_attr($option) . "\"{$selected}>" . esc_html__($option, 'ktpwp') . "</option>";
                    }
                    $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <select name=\"{$fieldName}\"{$required}>{$options}</select></div>";
                } else {
                    $generated_html = "<div class=\"form-group\"><label>{$label_i18n}：</label> <input type=\"{$field['type']}\" name=\"{$fieldName}\" value=\"" . esc_attr($value) . "\"{$pattern}{$required}{$placeholder}></div>";
                    $data_forms .= $generated_html;
                    
                    // デバッグ: 生成されたHTMLをログ出力（istmodeの場合のみ）
                    if ($action === 'istmode') {
                    }
                }
            }

            // ボタン群
            $data_forms .= "<div class='button'>";

            if( $action === 'istmode'){
                // 追加実行ボタン（同じフォーム内）
                $data_forms .= '<input type="hidden" name="query_post" value="insert">' 
                    . '<input type="hidden" name="data_id" value="">' 
                    . '<button type="submit" name="send_post" title="' . esc_attr__('追加実行', 'ktpwp') . '" class="insert-submit-btn">' 
                    . '<span class="material-symbols-outlined">select_check_box</span>' 
                    . esc_html__('追加実行', 'ktpwp') . '</button>';
                
                // キャンセルボタン（独立したフォーム）
                $data_forms .= '</form>'; // 追加フォーム終了
                $data_forms .= '<form method="post" action="" style="display:inline-block;margin-left:10px;">';
                $data_forms .= wp_nonce_field('ktp_client_action', 'ktp_client_nonce', true, false);
                $data_forms .= '<input type="hidden" name="query_post" value="update">';
                $data_forms .= '<button type="submit" title="' . esc_attr__('キャンセル', 'ktpwp') . '" style="background-color: #666 !important; margin-left: 10px;">' 
                    . '<span class="material-symbols-outlined">disabled_by_default</span>' 
                    . esc_html__('キャンセル', 'ktpwp') . '</button>';
                $data_forms .= '</form>'; // キャンセルフォーム終了
            }
            $data_forms .= "</div>"; // button div終了

            $data_forms .= "<div class=\"add\">";
            $data_forms .= '</div>';
        }

        // 空のフォームを表示(検索モードの場合)
        elseif ($action === 'srcmode') {
            
            // デバッグ: 検索モード実行時のアクション値確認
            
            $data_title = <<<END
            <div class="data_detail_box search-mode">
                <div class="data_detail_title">■ 顧客の詳細（検索モード）</div>
            END;

            // 検索モード用のフォーム
            $data_forms = '<div class="search-mode-form ktpwp-search-form" style="background-color: #f8f9fa !important; border: 2px solid #0073aa !important; border-radius: 8px !important; padding: 20px !important; margin: 10px 0 !important; box-shadow: 0 2px 8px rgba(0, 115, 170, 0.1) !important;">';
            $data_forms .= '<form method="post" action="">';
            $data_forms .= wp_nonce_field('ktp_client_action', 'ktp_client_nonce', true, false);
            
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
                    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08) !important;
                    display: flex !important;
                                       align-items: center !important;
                    font-size: 14px !important;
                ">
                <span style="margin-right: 10px !important; color: #ff6b8b !important; font-size: 18px !important;" class="material-symbols-outlined">search_off</span>
                検索結果が見つかりませんでした。別のキーワードをお試しください。
                </div>';
            }

            // ボタンを横並びにするためのラップクラスを追加
            $data_forms .= '<div class="button-group" style="display: flex; gap: 10px; margin-top: 15px !important; justify-content: flex-end !important;">';

            // 検索実行ボタン
            $data_forms .= '<input type="hidden" name="query_post" value="search">';
            $data_forms .= '<button type="submit" name="send_post" title="検索実行" class="search-submit-btn" style="background-color: #0073aa !important; color: white !important; border: none !important; padding: 10px 20px !important; cursor: pointer !important; border-radius: 5px !important; display: flex !important; align-items: center !important; gap: 5px !important; font-size: 14px !important; font-weight: 500 !important; transition: all 0.3s ease !important;">';
            $data_forms .= '<span class="material-symbols-outlined" style="font-size: 18px !important;">search</span>';
            $data_forms .= '検索実行';
            $data_forms .= '</button>';

            $data_forms .= '</form>'; // 検索フォームの閉じタグ

            // 検索モードのキャンセルボタン（独立したフォーム）
            $data_forms .= '<form method="post" action="" style="margin: 0 !important;">';
            $data_forms .= wp_nonce_field('ktp_client_action', 'ktp_client_nonce', true, false);
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
        elseif ($action !== 'srcmode' && $action !== 'istmode' && $action !== 'search') { // searchも除外

            // Simple postal code auto-fill functionality
            $data_forms .= <<<END
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var postalCode = document.querySelector('input[name="postal_code"]');
                var prefecture = document.querySelector('input[name="prefecture"]');
                var city = document.querySelector('input[name="city"]');
                
                if (postalCode) {
                    postalCode.addEventListener('blur', function() {
                        var zipcode = postalCode.value.replace(/[^0-9]/g, '');
                        if (zipcode.length === 7) {
                            var xhr = new XMLHttpRequest();
                            xhr.open('GET', 'https://zipcloud.ibsnet.co.jp/api/search?zipcode=' + zipcode);
                            xhr.onload = function() {
                                try {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.results && response.results.length > 0) {
                                        var data = response.results[0];
                                        if (prefecture) prefecture.value = data.address1;
                                        if (city) city.value = data.address2 + data.address3;
                                    }
                                } catch (error) {
                                    console.error('郵便番号検索エラー:', error);
                                }
                            };
                            xhr.send();
                        }
                    });
                }
            });
            </script>
            END;
                        
            // cookieに保存されたIDを取得
            $cookie_name = 'ktp_' . $name . '_id';
            if (isset($_GET['data_id'])) {
                $data_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
            } elseif (isset($_COOKIE[$cookie_name])) {
                $data_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
            } else {
                $data_id = $last_id_row ? $last_id_row->id : Null;
            }

            // ボタン群HTMLの準備
            $button_group_html = '<div class="button-group" style="display: flex; gap: 8px; margin-left: auto;">';

            // 削除ボタン
            $button_group_html .= '<form method="post" action="" style="margin: 0;">';
            $button_group_html .= wp_nonce_field('ktp_client_action', 'ktp_client_nonce', true, false);
            $button_group_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
            $button_group_html .= '<input type="hidden" name="query_post" value="delete">';
            $button_group_html .= '<button type="submit" name="send_post" title="' . esc_attr__('削除（無効化）する', 'ktpwp') . '" onclick="return confirm(\"' . esc_js(__('この顧客を削除（無効化）しますか？\\nデータは残りますが、表示ラベルが「対象外」に変更されます。', 'ktpwp')) . '\")" class="button-style delete-submit-btn">';
            $button_group_html .= '<span class="material-symbols-outlined">delete</span>';
            $button_group_html .= '</button>';
            $button_group_html .= '</form>';

            // 追加モードボタン
            $add_action = 'istmode';
            $next_data_id = $data_id + 1;
            $button_group_html .= '<form method="post" action="" style="margin: 0;">';
            $button_group_html .= wp_nonce_field('ktp_client_action', 'ktp_client_nonce', true, false);
            $button_group_html .= '<input type="hidden" name="data_id" value="">';
            $button_group_html .= '<input type="hidden" name="query_post" value="' . esc_attr($add_action) . '">';
            $button_group_html .= '<input type="hidden" name="data_id" value="' . esc_attr($next_data_id) . '">';
            $button_group_html .= '<button type="submit" name="send_post" title="' . esc_attr__('追加する', 'ktpwp') . '" class="button-style add-submit-btn">';
            $button_group_html .= '<span class="material-symbols-outlined">add</span>';
            $button_group_html .= '</button>';
            $button_group_html .= '</form>';

            // 検索モードボタン
            $search_action = 'srcmode';
            $button_group_html .= '<form method="post" action="" style="margin: 0;">';
            $button_group_html .= wp_nonce_field('ktp_client_action', 'ktp_client_nonce', true, false);
            $button_group_html .= '<input type="hidden" name="query_post" value="' . esc_attr($search_action) . '">';
            $button_group_html .= '<button type="submit" name="send_post" title="' . esc_attr__('検索する', 'ktpwp') . '" class="button-style search-mode-btn">';
            $button_group_html .= '<span class="material-symbols-outlined">search</span>';
            $button_group_html .= '</button>';
            $button_group_html .= '</form>';
            
            $button_group_html .= '</div>'; // ボタングループ終了
            
            // 表題にボタングループを含める
            $data_title = '<div class="data_detail_box"><div class="data_detail_title" style="display: flex; align-items: center; justify-content: space-between;">
            <div>■ 顧客の詳細（ ID: ' . esc_html($data_id) . ' ）</div>' . $button_group_html . '</div>';

            // メイン更新フォーム
            $data_forms .= '<form method="post" action="">';
            $data_forms .= wp_nonce_field('ktp_client_action', 'ktp_client_nonce', true, false);
            foreach ($fields as $label => $field) {
                $value = $action === 'update' ? (isset(${$field['name']}) ? ${$field['name']} : '') : '';
                $pattern = isset($field['pattern']) ? ' pattern="' . esc_attr($field['pattern']) . '"' : '';
                $required = isset($field['required']) && $field['required'] ? ' required' : '';
                $placeholder = isset($field['placeholder']) ? ' placeholder="' . esc_attr($field['placeholder']) . '"' : '';
                if ($field['type'] === 'textarea') {
                    $data_forms .= '<div class="form-group"><label>' . esc_html($label) . '：</label> <textarea name="' . esc_attr($field['name']) . '"' . $pattern . $required . '>' . esc_textarea($value) . '</textarea></div>';
                } elseif ($field['type'] === 'select') {
                    $options = '';
                    foreach ($field['options'] as $option) {
                        $selected = $value === $option ? ' selected' : '';
                        $options .= '<option value="' . esc_attr($option) . '"' . $selected . '>' . esc_html($option) . '</option>';
                    }
                    $data_forms .= '<div class="form-group"><label>' . esc_html($label) . '：</label> <select name="' . esc_attr($field['name']) . '"' . $required . '>' . $options . '</select></div>';
                } else {
                    $data_forms .= '<div class="form-group"><label>' . esc_html($label) . '：</label> <input type="' . esc_attr($field['type']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '"' . $pattern . $required . $placeholder . '></div>';
                }
            }
            $data_forms .= '<input type="hidden" name="query_post" value="update">';
            $data_forms .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
            $data_forms .= '<div class="button">';
            $data_forms .= '<button type="submit" name="send_post" title="' . esc_attr__('更新する', 'ktpwp') . '" class="update-submit-btn"><span class="material-symbols-outlined">cached</span></button>';
            $data_forms .= '</div>';
            $data_forms .= '</form>';
            
            // ボタン群は既にタイトル内に配置済み

            $data_forms .= '</div>';
        }
                            
        $data_forms .= '</div>'; // フォームを囲む<div>タグの終了
        
        // 詳細表示部分の終了タグを設定（全モード共通）
        // if (empty($div_end)) {
        // </div> <!-- data_contentsの終了 -->
        // END;
        // }

        // -----------------------------
        // テンプレート印刷
        // -----------------------------

       



       

        // Print_Classのパスを指定
        require_once( dirname( __FILE__ ) . '/class-print.php' );

        // 変数の初期化（未定義の場合に備えて）
        if (!isset($company_name)) $company_name = '';
        if (!isset($user_name)) $user_name = '';
        if (!isset($representative_name)) $representative_name = '';
        if (!isset($postal_code)) $postal_code = '';
        if (!isset($prefecture)) $prefecture = '';
        if (!isset($city)) $city = '';
        if (!isset($address)) $address = '';
        if (!isset($building)) $building = '';

        // データを指定
        $data_src = [
            'company_name' => $company_name,
            'name' => $user_name,
            'representative_name' => $representative_name,
            'postal_code' => $postal_code,
            'prefecture' => $prefecture,
            'city' => $city,
            'address' => $address,
            'building' => $building,
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

        // Simplified JavaScript - matching Update_Table approach
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
                printWindow.print();
                
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
                    
                    if (!previewWindow) {
                        previewWindow = document.createElement('div');
                        previewWindow.id = 'previewWindow';
                        previewWindow.style.cssText = 'display:none;position:relative;z-index:100;background:#fff;padding:20px;border:1px solid #ccc;margin:10px 0;';
                        
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
        </script>
        END;

        // コンテンツを返す
        // controller, workflow（受注書作成ボタン）を$print直後に追加
        // controller_html, workflow_htmlが重複しないようにcontroller_htmlは1回のみ出力
        // プレビューウィンドウはJavaScriptで動的に作成されるため、HTMLに直接書く必要はなくなった
        
        // 必要な変数の初期化確認
        if (!isset($search_results_list)) {
            $search_results_list = '';
        }
        if (!isset($data_title)) {
            $data_title = '';
        }
        if (!isset($data_forms)) {
            $data_forms = '';
        }
        if (!isset($div_end)) {
            $div_end = '';
        }
        // 検索モードでも顧客リストを表示する
        $content = $print . $session_message . $controller_html . $workflow_html . $data_list . $data_title . $data_forms . $search_results_list . $div_end;
        return $content;
    }
}
// class_exists
}