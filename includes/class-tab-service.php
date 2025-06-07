<?php
/**
 * Service class for KTPWP plugin
 *
 * Handles service data management including table creation,
 * data operations (CRUD), and security implementations.
 *
 * @package KTPWP
 * @subpackage Includes
 * @since 1.0.0
 * @author Kantan Pro
 * @copyright 2024 Kantan Pro
 * @license GPL-2.0+
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once 'class-image_processor.php';
require_once 'class-ktpwp-service-ui.php';
require_once 'class-ktpwp-service-db.php';

if ( ! class_exists( 'Kntan_Service_Class' ) ) {

/**
 * Service class for managing service data
 *
 * @since 1.0.0
 */
class Kntan_Service_Class {

    /**
     * UI helper instance
     *
     * @var KTPWP_Service_UI
     */
    private $ui_helper;

    /**
     * DB helper instance
     *
     * @var KTPWP_Service_DB
     */
    private $db_helper;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $tab_name The tab name
     */
    public function __construct( $tab_name = '' ) {
        // Initialize helper classes using singleton pattern
        $this->ui_helper = KTPWP_Service_UI::get_instance();
        $this->db_helper = KTPWP_Service_DB::get_instance();
    }
    
    // -----------------------------
    // Table Operations
    // -----------------------------

    /**
     * Set cookie for UI session management (delegated to UI helper)
     *
     * @since 1.0.0
     * @param string $name The name parameter for cookie
     * @return int The query ID
     */
    public function set_cookie( $name ) {
        return $this->ui_helper->set_cookie( $name );
    }

    /**
     * Create service table (delegated to DB helper)
     *
     * @since 1.0.0
     * @param string $tab_name The table name suffix
     * @return bool True on success, false on failure
     */
    public function create_table( $tab_name ) {
        return $this->db_helper->create_table( $tab_name );
    }

    // -----------------------------
    // Table Operations (CRUD)
    // -----------------------------

    /**
     * Update table with POST data (delegated to DB helper)
     *
     * @since 1.0.0
     * @param string $tab_name The table name suffix
     * @return void
     */
    public function update_table( $tab_name ) {
        return $this->db_helper->update_table( $tab_name );
    }


    // -----------------------------
    // テーブルの表示
    // -----------------------------

    function View_Table( $name ) {

        global $wpdb;

        // Ensure table exists
        $table_name = $wpdb->prefix . 'ktp_' . sanitize_key( $name );
        $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
        
        if ( ! $table_exists ) {
            // Create table if it doesn't exist
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'KTPWP Service: Table does not exist, creating: ' . $table_name );
            }
            $this->create_table( $name );
        }

        // Handle POST requests by calling update_table
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            // Debug logging
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'KTPWP Service: POST request detected in View_Table' );
                error_log( 'KTPWP Service: Full POST data: ' . print_r( $_POST, true ) );
                error_log( 'KTPWP Service: Full GET data: ' . print_r( $_GET, true ) );
                error_log( 'KTPWP Service: Request URI: ' . $_SERVER['REQUEST_URI'] );
            }
            
            // istmode（追加モード）の場合は update_table を呼ばない
            $query_post = isset( $_POST['query_post'] ) ? sanitize_text_field( $_POST['query_post'] ) : '';
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'KTPWP Service: Extracted query_post: "' . $query_post . '"' );
            }
            
            if ( $query_post !== 'istmode' ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPWP Service: Calling update_table with query_post: "' . $query_post . '"' );
                }
                $this->update_table( $name );
            } else {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPWP Service: Skipping update_table for istmode' );
                }
            }
        }

        // GETパラメータからメッセージを取得して表示
        if (isset($_GET['message'])) {
            $message_type = sanitize_text_field($_GET['message']);
            $message_text = '';
            $notice_class = '';

            // ソート操作時はメッセージを表示しないようにする
            $is_sorting_action = isset($_GET['sort_by']) || isset($_GET['sort_order']);

            if ($message_type === 'updated' && !$is_sorting_action) {
                $message_text = esc_html__('更新しました。', 'ktpwp');
                $notice_class = 'notice-success is-dismissible';
            } elseif ($message_type === 'added') {
                $message_text = esc_html__('新しいサービスを追加しました。', 'ktpwp');
                $notice_class = 'notice-success is-dismissible';
            } elseif ($message_type === 'deleted') {
                $message_text = esc_html__('削除しました。', 'ktpwp');
                $notice_class = 'notice-success is-dismissible';
            } elseif ($message_type === 'duplicated') {
                $message_text = esc_html__('複製しました。', 'ktpwp');
                $notice_class = 'notice-success is-dismissible';
            } elseif ($message_type === 'search_cancelled') {
                $message_text = esc_html__('検索をキャンセルしました。', 'ktpwp');
                $notice_class = 'notice-info is-dismissible';
            }
            // 他のメッセージタイプも必要に応じて追加

            if (!empty($message_text) && !empty($notice_class)) {
                echo '<div class="notice ' . esc_attr($notice_class) . '"><p>' . $message_text . '</p></div>';
                // JavaScript を追加して、表示後にURLから 'message' パラメータを削除 (DOMContentLoaded内で実行)
                echo '<script type="text/javascript">' .
                     'document.addEventListener("DOMContentLoaded", function() {' .
                     '  if (window.history.replaceState) {' .
                     '    const currentUrl = new URL(window.location.href);' .
                     '    if (currentUrl.searchParams.has(\'message\')) {' .
                     '      currentUrl.searchParams.delete(\'message\');' .
                     '      window.history.replaceState({ path: currentUrl.href }, \'\', currentUrl.href);' .
                     '    }' .
                     '  }' .
                     '});' .
                     '</script>';
            }
        }

        // セッション変数をチェックしてメッセージを表示 (これは前の修正の名残なので、GETパラメータ方式に統一した場合は削除またはコメントアウトを検討)
        // if (isset($_SESSION['ktp_service_message']) && isset($_SESSION['ktp_service_message_type'])) {
        //     $message_text = $_SESSION['ktp_service_message'];
        //     $message_type = $_SESSION['ktp_service_message_type'];
        //     unset($_SESSION['ktp_service_message']); // メッセージを表示したらセッション変数を削除
        //     unset($_SESSION['ktp_service_message_type']);
        //
        //     $notice_class = 'notice-success'; // デフォルトは成功メッセージ
        //     if ($message_type === 'error') {
        //         $notice_class = 'notice-error';
        //     } elseif ($message_type === 'updated') {
        //         $notice_class = 'notice-success is-dismissible'; // 更新成功のクラス
        //     }
        //
        //     echo '<div class="notice ' . esc_attr($notice_class) . '"><p>' . esc_html($message_text) . '</p></div>';
        // }


        // 検索モードの確認
        $search_mode = false;
        $search_message = '';
        if (!session_id()) {
            session_start();
        }
        if (isset($_SESSION['ktp_service_search_mode']) && $_SESSION['ktp_service_search_mode']) {
            $search_mode = true;
            $search_message = isset($_SESSION['ktp_service_search_message']) ? $_SESSION['ktp_service_search_message'] : '';
        }

        // 成功メッセージの表示
        $message = '';
        // JavaScript-based notifications instead of static HTML
        if (isset($_GET['message'])) {
            echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const messageType = "' . esc_js($_GET['message']) . '";
                switch (messageType) {
                    case "updated":
                        showSuccessNotification("' . esc_js(__('更新しました。', 'ktpwp')) . '");
                        break;
                    case "added":
                        showSuccessNotification("' . esc_js(__('新しいサービスを追加しました。', 'ktpwp')) . '");
                        break;
                    case "deleted":
                        showSuccessNotification("' . esc_js(__('削除しました。', 'ktpwp')) . '");
                        break;
                    case "duplicated":
                        showSuccessNotification("' . esc_js(__('複製しました。', 'ktpwp')) . '");
                        break;
                    case "search_found":
                        showInfoNotification("' . esc_js(__('検索結果を表示しています。', 'ktpwp')) . '");
                        break;
                    case "search_cancelled":
                        showInfoNotification("' . esc_js(__('検索をキャンセルしました。', 'ktpwp')) . '");
                        break;
                }
            });
            </script>';
        }

        // 検索メッセージの表示
        if ($search_mode && $search_message) {
            $message .= '<div class="notice notice-info" style="margin: 10px 0; padding: 10px; background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; border-radius: 4px;">' 
                . '<span style="margin-right: 10px; color: #17a2b8; font-size: 18px;" class="material-symbols-outlined">search</span>'
                . esc_html($search_message) . '</div>';
        }

        // -----------------------------
        // リスト表示
        // -----------------------------
        
        // テーブル名
        $table_name = $wpdb->prefix . 'ktp_' . $name;        // -----------------------------
        // ページネーションリンク
        // -----------------------------
// ソート順の取得（デフォルトはIDの降順 - 新しい順）
        $sort_by = 'id';
        $sort_order = 'DESC';
        
        if (isset($_GET['sort_by'])) {
            $sort_by = sanitize_text_field($_GET['sort_by']);
            // 安全なカラム名のみ許可（SQLインジェクション対策）
            $allowed_columns = array('id', 'service_name', 'price', 'unit', 'frequency', 'time', 'category');
            if (!in_array($sort_by, $allowed_columns)) {
                $sort_by = 'id'; // 不正な値の場合はデフォルトに戻す
            }
        }
        
        if (isset($_GET['sort_order'])) {
            $sort_order_param = strtoupper(sanitize_text_field($_GET['sort_order']));
            // ASCかDESCのみ許可
            $sort_order = ($sort_order_param === 'ASC') ? 'ASC' : 'DESC';
        }
        
        // 現在のページのURLを生成 (この$base_page_urlは現在のリクエストのパラメータを含む可能性がある)
        global $wp;
        $current_page_id = get_queried_object_id();
        $base_page_url = add_query_arg( array( 'page_id' => $current_page_id ), home_url( $wp->request ) );
        
        // 表示範囲（1ページあたりの表示件数）
        // 一般設定から表示件数を取得（設定クラスが利用可能な場合）
        if (class_exists('KTP_Settings')) {
            $query_limit = KTP_Settings::get_work_list_range();
        } else {
            $query_limit = 20; // フォールバック値
        }
        if (!is_numeric($query_limit) || $query_limit <= 0) {
            $query_limit = 20; // 不正な値の場合はデフォルト値に
        }
        
        // ソートプルダウンを追加
        // ソートフォームのアクションURLからは 'message' を除去
        $sort_action_url = remove_query_arg('message', $base_page_url);
        
        $sort_dropdown = '<div class="sort-dropdown" style="float:right;margin-left:10px;">' .
            '<form method="get" action="' . esc_url($sort_action_url) . '" style="display:flex;align-items:center;">';
        
        // 現在のGETパラメータを維持するための隠しフィールド (messageとソート自体に関連するキーは除く)
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['message', 'sort_by', 'sort_order', '_ktp_service_nonce', 'query_post', 'send_post'])) {
                $sort_dropdown .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr(stripslashes($value)) . '">';
            }
        }
        
        $sort_dropdown .= 
            '<select id="sort-select" name="sort_by" style="margin-right:5px;">' .
            '<option value="id" ' . selected($sort_by, 'id', false) . '>' . esc_html__('ID', 'ktpwp') . '</option>' .
            '<option value="service_name" ' . selected($sort_by, 'service_name', false) . '>' . esc_html__('サービス名', 'ktpwp') . '</option>' .
            '<option value="price" ' . selected($sort_by, 'price', false) . '>' . esc_html__('価格', 'ktpwp') . '</option>' .
            '<option value="unit" ' . selected($sort_by, 'unit', false) . '>' . esc_html__('単位', 'ktpwp') . '</option>' .
            '<option value="category" ' . selected($sort_by, 'category', false) . '>' . esc_html__('カテゴリー', 'ktpwp') . '</option>' .
            '<option value="frequency" ' . selected($sort_by, 'frequency', false) . '>' . esc_html__('頻度', 'ktpwp') . '</option>' .
            '<option value="time" ' . selected($sort_by, 'time', false) . '>' . esc_html__('登録日', 'ktpwp') . '</option>' .
            '</select>' .
            '<select id="sort-order" name="sort_order">' .
            '<option value="ASC" ' . selected($sort_order, 'ASC', false) . '>' . esc_html__('昇順', 'ktpwp') . '</option>' .
            '<option value="DESC" ' . selected($sort_order, 'DESC', false) . '>' . esc_html__('降順', 'ktpwp') . '</option>' .
            '</select>' .
            '<button type="submit" style="margin-left:5px;padding:4px 8px;background:#f0f0f0;border:1px solid #ccc;border-radius:3px;cursor:pointer;" title="' . esc_attr__('適用', 'ktpwp') . '">' .
            '<span class="material-symbols-outlined" style="font-size:18px;line-height:18px;vertical-align:middle;">check</span>' .
            '</button>' .
            '</form></div>';

        // リスト表示部分の開始
        $results_h = <<<END
            <div class="ktp_data_list_box">
            <div class="data_list_title">■ サービスリスト {$sort_dropdown}</div>
        END;
        // スタート位置を決める
        $page_stage = $_GET['page_stage'] ?? '';
        $page_start = $_GET['page_start'] ?? 0;
        $flg = $_GET['flg'] ?? '';
        if ($page_stage == '') {
            $page_start = 0;
        }        $query_range = $page_start . ',' . $query_limit;

        // 全データ数を取得
        $total_query = "SELECT COUNT(*) FROM {$table_name}";
        $total_rows = $wpdb->get_var($total_query);
        
        // ゼロ除算防止のための安全対策
        if ($query_limit <= 0) {
            if (class_exists('KTP_Settings')) {
                $query_limit = KTP_Settings::get_work_list_range();
            } else {
                $query_limit = 20; // フォールバック値
            }
        }
        
        $total_pages = ceil($total_rows / $query_limit);

        // 現在のページ番号を計算
        $current_page = floor($page_start / $query_limit) + 1;

        // データを取得（ソート順を適用）
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY {$sort_by} {$sort_order} LIMIT %d, %d", $page_start, $query_limit);
        $post_row = $wpdb->get_results($query);
        $results = []; // ← 追加：未定義エラー防止
        if( $post_row ){
            foreach ($post_row as $row){
                $id = esc_html($row->id);
                $service_name = esc_html($row->service_name);
                $price = isset($row->price) ? intval($row->price) : 0;
                $unit = isset($row->unit) ? esc_html($row->unit) : '';
                $category = esc_html($row->category);
                $frequency = esc_html($row->frequency);
                  // リスト項目
                $cookie_name = 'ktp_' . $name . '_id';
                // $base_page_url を add_query_arg の第2引数として使用
                $item_link_args = array(
                    'tab_name' => $name, 
                    'data_id' => $id, 
                    'page_start' => $page_start, 
                    'page_stage' => $page_stage
                );
                // 他のソートやフィルタ関連のGETパラメータを維持しつつ、'message'は含めない
                foreach ($_GET as $getKey => $getValue) {
                    if (!in_array($getKey, ['tab_name', 'data_id', 'page_start', 'page_stage', 'message', '_ktp_service_nonce', 'query_post', 'send_post'])) {
                        $item_link_args[$getKey] = $getValue;
                    }
                }
                $results[] = '<a href="' . esc_url(add_query_arg($item_link_args, $base_page_url)) . '">'.
                    '<div class="ktp_data_list_item">' . esc_html__('ID', 'ktpwp') . ': ' . $id . ' ' . $service_name . ' : ' . number_format($price) . '円' . ($unit ? '/' . $unit : '') . ' : ' . $category . ' : ' . esc_html__('頻度', 'ktpwp') . '(' . $frequency . ')</div>'.
                '</a>';
            }
            $query_max_num = $wpdb->num_rows;
        } else {
            $results[] = '<div class="ktp_data_list_item" style="padding: 15px 20px; background: linear-gradient(135deg, #ffeef1 0%, #ffeff2 100%); border-radius: 6px; margin: 15px 0; color: #333333; font-weight: 500; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08); display: flex; align-items: center; font-size: 14px;">'
                . '<span style="margin-right: 10px; color: #ff6b8b; font-size: 18px;" class="material-symbols-outlined">search_off</span>'
                . esc_html__('データーがありません。', 'ktpwp')
                . '<span style="margin-left: 16px; font-size: 13px; color: #888;">'
                . esc_html__('フォームに入力して更新してください。', 'ktpwp')
                . '</span>'
                . '</div>';
        }

        $results_f = '<div class="pagination">';

        // 最初へリンク
        if ($current_page > 1) {
            $first_start = 0;
            $first_page_link_args = array('tab_name' => $name, 'page_start' => $first_start, 'page_stage' => 2, 'flg' => $flg);
            // 現在のソート順を維持
            if (isset($_GET['sort_by'])) $first_page_link_args['sort_by'] = $_GET['sort_by'];
            if (isset($_GET['sort_order'])) $first_page_link_args['sort_order'] = $_GET['sort_order'];
            $first_page_link_url = esc_url(add_query_arg($first_page_link_args, $base_page_url));
            $results_f .= <<<END
            <a href="{$first_page_link_url}">|<</a> 
            END;
        }

        // 前へリンク
        if ($current_page > 1) {
            $prev_start = ($current_page - 2) * $query_limit;
            $prev_page_link_args = array('tab_name' => $name, 'page_start' => $prev_start, 'page_stage' => 2, 'flg' => $flg);
            // 現在のソート順を維持
            if (isset($_GET['sort_by'])) $prev_page_link_args['sort_by'] = $_GET['sort_by'];
            if (isset($_GET['sort_order'])) $prev_page_link_args['sort_order'] = $_GET['sort_order'];
            $prev_page_link_url = esc_url(add_query_arg($prev_page_link_args, $base_page_url));
            $results_f .= <<<END
            <a href="{$prev_page_link_url}"><</a>
            END;
        }

        // 現在のページ範囲表示と総数
        $page_end = min($total_rows, $current_page * $query_limit);
        $page_start_display = ($current_page - 1) * $query_limit + 1;
        $results_f .= "<div class='stage'> $page_start_display ~ $page_end / $total_rows</div>";

        // 次へリンク
        if ($current_page < $total_pages) {
            $next_start = $current_page * $query_limit;
            $next_page_link_args = array('tab_name' => $name, 'page_start' => $next_start, 'page_stage' => 2, 'flg' => $flg);
            // 現在のソート順を維持
            if (isset($_GET['sort_by'])) $next_page_link_args['sort_by'] = $_GET['sort_by'];
            if (isset($_GET['sort_order'])) $next_page_link_args['sort_order'] = $_GET['sort_order'];
            $next_page_link_url = esc_url(add_query_arg($next_page_link_args, $base_page_url));
            $results_f .= <<<END
            <a href="{$next_page_link_url}">></a>
            END;
        }

        // 最後へリンク
        if ($current_page < $total_pages) {
            $last_start = ($total_pages - 1) * $query_limit;
            $last_page_link_args = array('tab_name' => $name, 'page_start' => $last_start, 'page_stage' => 2, 'flg' => $flg);
            // 現在のソート順を維持
            if (isset($_GET['sort_by'])) $last_page_link_args['sort_by'] = $_GET['sort_by'];
            if (isset($_GET['sort_order'])) $last_page_link_args['sort_order'] = $_GET['sort_order'];
            $last_page_link_url = esc_url(add_query_arg($last_page_link_args, $base_page_url));
            $results_f .= <<<END
             <a href="{$last_page_link_url}">>|</a>
            END;
        }

        $results_f .= '</div>';

        $data_list = $results_h . implode( $results ) . $results_f . '</div>'; // ktp_data_list_box を閉じる

        // -----------------------------
        // 詳細表示(GET)
        // -----------------------------

        // アクションを取得（POSTパラメータを優先、次にGETパラメータ、デフォルトは'update'）
        $action = isset($_POST['query_post']) ? sanitize_text_field($_POST['query_post']) : (isset($_GET['query_post']) ? sanitize_text_field($_GET['query_post']) : 'update');
        
        // 安全性確保: GETリクエストの場合は危険なアクションを実行しない
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && in_array($action, ['duplicate', 'delete', 'insert', 'search', 'search_execute', 'upload_image'])) {
            $action = 'update';
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
        }
        
        // デバッグ: タブクリック時の動作をログに記録
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        
        // 初期化
        $data_id = '';
        $time = '';
        $service_name = '';
        $price = 0;
        $unit = '';
        $memo = '';
        $category = '';
        $image_url = '';
        $query_id = 0;
        
        // 追加モード以外の場合のみデータを取得
        if ($action !== 'istmode') {
            // 現在表示中の詳細
            $cookie_name = 'ktp_' . $name . '_id';
            
            // デバッグログ：初期状態の確認
            
            if (isset($_GET['data_id']) && $_GET['data_id'] !== '') {
                $query_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
                // GETパラメータで取得したIDをクッキーに保存
                setcookie($cookie_name, $query_id, time() + (86400 * 30), '/');
            } elseif (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] !== '') {
                $cookie_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
                // クッキーIDがDBに存在するかチェック
                $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE id = %d", $cookie_id));
                if ($exists) {
                    $query_id = $cookie_id;
                } else {
                    // 存在しなければ最新ID（降順トップ）
                    $last_id_row = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1");
                    $query_id = $last_id_row ? $last_id_row->id : 1;
                    // 最新IDをクッキーに保存
                    setcookie($cookie_name, $query_id, time() + (86400 * 30), '/');
                }
            } else {
                // data_id未指定時は必ずID最新のサービスを表示（降順トップ）
                $last_id_row = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1");
                $query_id = $last_id_row ? $last_id_row->id : 1;
                // 最新IDをクッキーに保存
                setcookie($cookie_name, $query_id, time() + (86400 * 30), '/');
            }

            // データを取得し変数に格納
            $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $query_id);
            $post_row = $wpdb->get_results($query);
            if (!$post_row || count($post_row) === 0) {
                // 存在しないIDの場合は最新IDを取得して再表示
                $last_id_row = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1");
                if ($last_id_row && isset($last_id_row->id)) {
                    $query_id = $last_id_row->id;
                    $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $query_id);
                    $post_row = $wpdb->get_results($query);
                }
                // それでもデータがなければ「データがありません」は後で処理
            }
            foreach ($post_row as $row){
                $data_id = esc_html($row->id);
                $time = esc_html($row->time);
                $service_name = esc_html($row->service_name);
                $price = isset($row->price) ? intval($row->price) : 0;
                $unit = isset($row->unit) ? esc_html($row->unit) : '';
                $memo = esc_html($row->memo);
                $category = esc_html($row->category);
                $image_url = esc_html($row->image_url);
            }
        }
          // 表示するフォーム要素を定義
        $fields = [
            // 'ID' => ['type' => 'text', 'name' => 'data_id', 'readonly' => true], 
            esc_html__('サービス名', 'ktpwp') => ['type' => 'text', 'name' => 'service_name', 'required' => true, 'placeholder' => esc_attr__('必須 サービス名', 'ktpwp')],
            esc_html__('価格', 'ktpwp') => ['type' => 'number', 'name' => 'price', 'placeholder' => esc_attr__('価格（円）', 'ktpwp')],
            esc_html__('単位', 'ktpwp') => ['type' => 'text', 'name' => 'unit', 'placeholder' => esc_attr__('月、件、時間など', 'ktpwp')],
            // '画像URL' => ['type' => 'text', 'name' => 'image_url'], // サービス画像のURLフィールドはコメントアウト
            esc_html__('メモ', 'ktpwp') => ['type' => 'textarea', 'name' => 'memo'],
            esc_html__('カテゴリー', 'ktpwp') => [
                'type' => 'text',
                'name' => 'category',
                'options' => esc_html__('一般', 'ktpwp'),
                'suggest' => true,
            ],
        ];
        
        // アクションを取得（POSTパラメータを優先、次にGETパラメータ、デフォルトは'update'）
        $action = 'update';
        if (isset($_POST['query_post'])) {
            $action = sanitize_text_field($_POST['query_post']);
        } elseif (isset($_GET['query_post'])) {
            $action = sanitize_text_field($_GET['query_post']);
        }
        
        $data_forms = ''; // フォームのHTMLコードを格納する変数を初期化
        
        // 検索モードの場合は検索フォームを表示
        if ($search_mode) {
            // 検索フォームの表示
            $data_title = '<div class="data_detail_box search-mode">' .
                          '<div class="data_detail_title">■ ' . esc_html__('サービス検索', 'ktpwp') . '</div>';
            
            // 検索フォーム
            $data_forms .= '<form method="post" action="" class="search-form">';
            if (function_exists('wp_nonce_field')) { 
                $data_forms .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); 
            }
            
            // 検索フィールド
            $data_forms .= '<div class="form-group">';
            $data_forms .= '<label>' . esc_html__('サービス名で検索', 'ktpwp') . '：</label>';
            $data_forms .= '<input type="text" name="search_service_name" placeholder="' . esc_attr__('サービス名を入力', 'ktpwp') . '" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">';
            $data_forms .= '</div>';
            
            $data_forms .= '<div class="form-group">';
            $data_forms .= '<label>' . esc_html__('カテゴリーで検索', 'ktpwp') . '：</label>';
            $data_forms .= '<input type="text" name="search_category" placeholder="' . esc_attr__('カテゴリーを入力', 'ktpwp') . '" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">';
            $data_forms .= '</div>';
            
            // 検索ボタン群
            $data_forms .= '<div class="search-button-group" style="margin-top: 20px; display: flex; gap: 10px;">';
            
            // 検索実行ボタン
            $data_forms .= '<input type="hidden" name="query_post" value="search_execute">';
            $data_forms .= '<button type="submit" name="send_post" title="' . esc_attr__('検索実行', 'ktpwp') . '" style="background-color: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 5px;">';
            $data_forms .= '<span class="material-symbols-outlined" style="font-size: 18px;">search</span>';
            $data_forms .= esc_html__('検索実行', 'ktpwp');
            $data_forms .= '</button>';
            $data_forms .= '</form>';
            
            // キャンセルボタン（別フォーム）
            $data_forms .= '<form method="post" action="" style="margin: 0;">';
            if (function_exists('wp_nonce_field')) { 
                $data_forms .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); 
            }
            $data_forms .= '<input type="hidden" name="query_post" value="search_cancel">';
            $data_forms .= '<button type="submit" name="send_post" title="' . esc_attr__('検索キャンセル', 'ktpwp') . '" style="background-color: #666; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 5px;">';
            $data_forms .= '<span class="material-symbols-outlined" style="font-size: 18px;">close</span>';
            $data_forms .= esc_html__('キャンセル', 'ktpwp');
            $data_forms .= '</button>';
            $data_forms .= '</form>';
            
            $data_forms .= '</div>'; // search-button-group の終了
            // Removed: $data_forms .= '</div>'; // data_detail_box の終了 (This was incorrectly closing the detail box here)
            
        }
        // 空のフォームを表示(追加モードの場合)
        elseif ($action === 'istmode') {
            // 追加モードは data_id を空にする
            $data_id = '';
            // 詳細表示部分の開始
            $data_title = '<div class="data_detail_box">' .
                          '<div class="data_detail_title">■ ' . esc_html__('サービス追加中', 'ktpwp') . '</div>';
            
            // 追加フォーム
            $data_forms .= "<form name='service_form' method='post' action=''>";
            if (function_exists('wp_nonce_field')) { $data_forms .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); }
            
            // フィールド生成
            foreach ($fields as $label => $field) {
                $value = ''; // 追加モードでは常に空
                $pattern = isset($field['pattern']) ? " pattern=\"" . esc_attr($field['pattern']) . "\"" : '';
                $required = isset($field['required']) && $field['required'] ? ' required' : '';
                $fieldName = esc_attr($field['name']);
                $placeholder = isset($field['placeholder']) ? " placeholder=\"" . esc_attr__($field['placeholder'], 'ktpwp') . "\"" : '';
                $label_i18n = esc_html__($label, 'ktpwp');
                
                if ($field['type'] === 'textarea') {
                    $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <textarea name=\"{$fieldName}\"{$pattern}{$required}>" . esc_textarea($value) . "</textarea></div>";
                } elseif ($field['type'] === 'select') {
                    $options = '';
                    foreach ((array)$field['options'] as $option) {
                        $options .= "<option value=\"" . esc_attr($option) . "\">" . esc_html__($option, 'ktpwp') . "</option>";
                    }
                    $default = isset($field['default']) ? esc_html__($field['default'], 'ktpwp') : '';
                    $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <select name=\"{$fieldName}\"{$required}><option value=\"\">{$default}</option>{$options}</select></div>";
                } else {
                    $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <input type=\"{$field['type']}\" name=\"{$fieldName}\" value=\"" . esc_attr($value) . "\"{$pattern}{$required}{$placeholder}></div>";
                }
            }
            
            $data_forms .= "<div class='button'>";
            // 追加実行ボタン
            $data_forms .= "<input type='hidden' name='query_post' value='new'>";
            $data_forms .= "<input type='hidden' name='data_id' value=''>";
            $data_forms .= "<input type='hidden' name='action_type' value='create_new'>";
            $data_forms .= "<button type='submit' name='send_post' value='create' title='追加実行'><span class='material-symbols-outlined'>select_check_box</span></button>";
            $data_forms .= "</form>";
            
            // キャンセルボタン（独立したフォーム）
            $data_forms .= "<form method='post' action='' style='display:inline-block;margin-left:10px;'>";
            if (function_exists('wp_nonce_field')) { 
                $data_forms .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); 
            }
            $data_forms .= "<input type='hidden' name='query_post' value='update'>";
            $data_forms .= "<input type='hidden' name='action_type' value='cancel'>";
            $data_forms .= "<button type='submit' name='send_post' value='cancel' title='キャンセル'><span class='material-symbols-outlined'>disabled_by_default</span></button>";
            $data_forms .= "</form>";
            $data_forms .= "<div class=\"add\"></div>";
            $data_forms .= '</div>';
        } else {
            // 通常モード：既存の詳細フォーム表示
        
        // データー量を取得（追加モード以外の場合）
        if ($action !== 'istmode') {
            $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $query_id);
            $data_num = $wpdb->get_results($query);
            $data_num = count($data_num); // 現在のデータ数を取得し$data_numに格納
        } else {
            $data_num = 0; // 新規追加の場合はデータ数を0に設定
        }

        // 更新フォームを表示
        // cookieに保存されたIDを取得
        $cookie_name = 'ktp_'. $name . '_id';
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
        if ($data_id) {
            $button_group_html .= '<form method="post" action="" style="margin: 0;" onsubmit="return confirm(\'' . esc_js(__('本当に削除しますか？この操作は元に戻せません。', 'ktpwp')) . '\');">';
            if (function_exists('wp_nonce_field')) { 
                $button_group_html .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); 
            }
            $button_group_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
            $button_group_html .= '<input type="hidden" name="query_post" value="delete">';
            $button_group_html .= '<button type="submit" name="send_post" title="' . esc_attr__('削除する', 'ktpwp') . '" class="button-style delete-submit-btn">';
            $button_group_html .= '<span class="material-symbols-outlined">delete</span>';
            $button_group_html .= '</button>';
            $button_group_html .= '</form>';
        }

        // 追加モードボタン
        $add_action = 'istmode';
        $button_group_html .= '<form method="post" action="" style="margin: 0;">';
        if (function_exists('wp_nonce_field')) { 
            $button_group_html .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); 
        }
        $button_group_html .= '<input type="hidden" name="data_id" value="">';
        $button_group_html .= '<input type="hidden" name="query_post" value="' . esc_attr($add_action) . '">';
        $button_group_html .= '<button type="submit" name="send_post" title="' . esc_attr__('追加する', 'ktpwp') . '" class="button-style add-submit-btn">';
        $button_group_html .= '<span class="material-symbols-outlined">add</span>';
        $button_group_html .= '</button>';
        $button_group_html .= '</form>';

        // 複製ボタン
        if ($data_id) {
            $button_group_html .= '<form method="post" action="" style="margin: 0;">';
            if (function_exists('wp_nonce_field')) { 
                $button_group_html .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); 
            }
            $button_group_html .= '<input type="hidden" name="query_post" value="duplicate">';
            $button_group_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
            $button_group_html .= '<button type="submit" name="send_post" title="' . esc_attr__('複製する', 'ktpwp') . '" class="button-style duplicate-submit-btn">';
            $button_group_html .= '<span class="material-symbols-outlined">content_copy</span>';
            $button_group_html .= '</button>';
            $button_group_html .= '</form>';
        }

        // 検索モードボタン
        $search_action = 'srcmode';
        $button_group_html .= '<form method="post" action="" style="margin: 0;">';
        if (function_exists('wp_nonce_field')) { 
            $button_group_html .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); 
        }
        $button_group_html .= '<input type="hidden" name="query_post" value="' . esc_attr($search_action) . '">';
        $button_group_html .= '<button type="submit" name="send_post" title="' . esc_attr__('検索する', 'ktpwp') . '" class="button-style search-mode-btn">';
        $button_group_html .= '<span class="material-symbols-outlined">search</span>';
        $button_group_html .= '</button>';
        $button_group_html .= '</form>';
        
        $button_group_html .= '</div>'; // ボタングループ終了
        
        // データを取得
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $name;
        
        // データを取得
        $query = "SELECT * FROM {$table_name} WHERE id = %d";
        $post_row = $wpdb->get_results($wpdb->prepare($query, $data_id));
        $image_url = '';
        foreach ($post_row as $row) {
            $image_url = esc_html($row->image_url);
        }
        
        // 画像URLが空または無効な場合、デフォルト画像を使用
        if (empty($image_url)) {
            $image_url = plugin_dir_url(dirname(__FILE__)) . 'images/default/no-image-icon.jpg';
        }
        
        // アップロード画像が存在するか確認
        $upload_dir = dirname(__FILE__) . '/../images/upload/';
        $upload_file = $upload_dir . $data_id . '.jpeg';
        if (file_exists($upload_file)) {
            $plugin_url = plugin_dir_url(dirname(__FILE__));
            $image_url = $plugin_url . 'images/upload/' . $data_id . '.jpeg';
        }
        
        // 画像とアップロードフォームのHTML
        $image_section_html = '<div style="margin-top: 10px;">'; // 画像セクション開始
        $image_section_html .= '<div class="image"><img src="' . $image_url . '" alt="' . esc_attr__('サービス画像', 'ktpwp') . '" class="product-image" onerror="this.src=\'' . plugin_dir_url(dirname(__FILE__)) . 'images/default/no-image-icon.jpg\'" style="width: 100%; height: auto; max-width: 100%;"></div>';
        $image_section_html .= '<div class="image_upload_form">';
        
        // サービス画像アップロードフォーム
        $nonce_field_upload = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : '';
        $image_section_html .= '<form action="" method="post" enctype="multipart/form-data" onsubmit="return checkImageUpload(this);">';
        $image_section_html .= $nonce_field_upload;
        $image_section_html .= '<div class="file-upload-container">';
        $image_section_html .= '<input type="file" name="image" class="file-input">';
        $image_section_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
        $image_section_html .= '<input type="hidden" name="query_post" value="upload_image">';
        $image_section_html .= '<button type="submit" name="send_post" class="upload-btn" title="画像をアップロード">';
        $image_section_html .= '<span class="material-symbols-outlined">upload</span>';
        $image_section_html .= '</button>';
        $image_section_html .= '</div>';
        $image_section_html .= '</form>';
        $image_section_html .= '<script>function checkImageUpload(form) { if (!form.image.value) { alert("画像が選択されていません。アップロードする画像を選択してください。"); return false; } return true; }</script>';

        // サービス画像削除ボタン
        $nonce_field_delete = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : '';
        $image_section_html .= '<form method="post" action="">';
        $image_section_html .= $nonce_field_delete;
        $image_section_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
        $image_section_html .= '<input type="hidden" name="query_post" value="delete_image">';
        $image_section_html .= '<button type="submit" name="send_post" title="削除する" onclick="return confirm(\'本当に削除しますか？\')">';
        $image_section_html .= '<span class="material-symbols-outlined">delete</span>';
        $image_section_html .= '</button>';
        $image_section_html .= '</form>';
        $image_section_html .= '</div>'; // image_upload_form終了
        $image_section_html .= '</div>'; // 画像セクション終了
        
        // 表題にボタングループと画像セクションを含める
        $data_title = '<div class="data_detail_box"><div class="data_detail_title" style="display: flex; align-items: center; justify-content: space-between;">
        <div>■ サービスの詳細（ ID： ' . $data_id . ' ）</div>' . $button_group_html . '</div>' . $image_section_html;
        
        // 更新フォームの開始
        $data_forms .= "<form name='service_form' method='post' action=''>";
        if (function_exists('wp_nonce_field')) { $data_forms .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); }
        foreach ($fields as $label => $field) {
            $value = $action === 'update' ? ${$field['name']} : '';
            $pattern = isset($field['pattern']) ? " pattern=\"" . esc_attr($field['pattern']) . "\"" : '';
            $required = isset($field['required']) && $field['required'] ? ' required' : '';
            $fieldName = esc_attr($field['name']);
            $placeholder = isset($field['placeholder']) ? " placeholder=\"" . esc_attr__($field['placeholder'], 'ktpwp') . "\"" : '';
            $label_i18n = esc_html__($label, 'ktpwp');
            if ($field['type'] === 'textarea') {
                $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <textarea name=\"{$fieldName}\"{$pattern}{$required}>" . esc_textarea($value) . "</textarea></div>";
            } elseif ($field['type'] === 'select') {
                $options = '';
                foreach ((array)$field['options'] as $option) {
                    $selected = $value === $option ? ' selected' : '';
                    $options .= "<option value=\"" . esc_attr($option) . "\"{$selected}>" . esc_html__($option, 'ktpwp') . "</option>";
                }
                $default = isset($field['default']) ? esc_html__($field['default'], 'ktpwp') : '';
                $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <select name=\"{$fieldName}\"{$required}><option value=\"\">{$default}</option>{$options}</select></div>";
            } else {
                $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <input type=\"{$field['type']}\" name=\"{$fieldName}\" value=\"" . esc_attr($value) . "\"{$pattern}{$required}{$placeholder}></div>";
            }
        }
        $data_forms .= "<input type=\"hidden\" name=\"query_post\" value=\"update\">";
        $data_forms .= "<input type=\"hidden\" name=\"data_id\" value=\"{$data_id}\">";
        $data_forms .= "<div class='button'>";
        $data_forms .= "<button type=\"submit\" name=\"send_post\" title=\"" . esc_attr__('更新する', 'ktpwp') . "\" class=\"update-submit-btn\"><span class=\"material-symbols-outlined\">cached</span></button>";
        $data_forms .= "</div>";
        $data_forms .= "</form>";

        } // 通常モード分岐の終了
            
            $data_forms .= "<div class=\"add\">";
            // 表題は上部で既に定義済み、重複フォーム削除完了
            
        $data_forms .= '</div>'; // フォームを囲む<div>タグの終了
        
        // 詳細表示部分の終了
        $div_end = '</div> <!-- data_detail_boxの終了 -->';

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
        $content = $message . $print . '<div class="data_contents">' . $data_list . $data_title . $data_forms . $div_end . '</div> <!-- data_contentsの終了 -->';
        return $content;
    }

} // End class Kntan_Service_Class

} // End if class_exists