<?php

class Kantan_List_Class{

    // public $name;

    public function __construct() {
        // $this->name = 'list';
    }
    
    function List_Tab_View( $tab_name ) {
        global $wpdb; // $wpdbオブジェクトを使用可能にする
        $table_name = $wpdb->prefix . 'ktp_order'; // 受注書テーブル名

        $content = ''; // 表示するHTMLコンテンツ

        // controllerコンテナを上部に表示
        $content .= '<div class="controller">';
        $content .= '<div class="printer">';
        $content .= '<div class="up-title">仕事リスト：</div>';
        // 印刷ボタン（ダミー）
        $content .= '<button title="印刷する" onclick="alert(\'印刷ダミー\')">';
        $content .= '<span class="material-symbols-outlined" aria-label="印刷">print</span>';
        $content .= '</button>';
        $content .= '</div>'; // .printer 終了
        $content .= '</div>'; // .controller 終了

        // 受注書リスト表示
        // $content .= '<h3>■ 受注書リスト</h3>';

        // ページネーション設定
        $query_limit = 5;
        $page_stage = isset($_GET['page_stage']) ? $_GET['page_stage'] : '';
        $page_start = isset($_GET['page_start']) ? intval($_GET['page_start']) : 0;
        $flg = isset($_GET['flg']) ? $_GET['flg'] : '';
        if ($page_stage == '') {
            $page_start = 0;
        }
        $query_range = $page_start . ',' . $query_limit;

        // 総件数取得
        $total_query = "SELECT COUNT(*) FROM {$table_name}";
        $total_rows = $wpdb->get_var($total_query);
        $total_pages = ceil($total_rows / $query_limit);
        $current_page = floor($page_start / $query_limit) + 1;

        // データ取得
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY time DESC LIMIT %d, %d", $page_start, $query_limit);
        $order_list = $wpdb->get_results($query);

        // --- ここからラッパー追加 ---
        $content .= '<div class="work_list_box">';
        if ($order_list) {
            $content .= '<ul>';
            foreach ($order_list as $order) {
                $order_id = esc_html($order->id);
                $customer_name = esc_html($order->customer_name);
                $user_name = esc_html($order->user_name);
                $time = esc_html($order->time);

                // 受注書詳細（伝票処理タブ）へのリンク
                $detail_url = add_query_arg('order_id', $order_id, '?tab_name=order');

                $content .= "<li><a href='{$detail_url}'>ID: {$order_id} - {$customer_name} ({$user_name}) - {$time}</a></li>";
            }
            $content .= '</ul>';
        } else {
            $content .= '<p>受注書データがありません。</p>';
        }
        // --- ページネーション ---
        if ($total_pages > 1) {
            $content .= '<div class="pagination">';
            // 最初へ
            if ($current_page > 1) {
                $first_start = 0;
                $content .= '<a href="?tab_name=' . urlencode($tab_name) . '&page_start=' . $first_start . '&page_stage=2&flg=' . $flg . '">|&lt;</a>';
            }
            // 前へ
            if ($current_page > 1) {
                $prev_start = ($current_page - 2) * $query_limit;
                $content .= '<a href="?tab_name=' . urlencode($tab_name) . '&page_start=' . $prev_start . '&page_stage=2&flg=' . $flg . '">&lt;</a>';
            }
            // 現在のページ範囲表示と総数
            $page_end = min($total_rows, $current_page * $query_limit);
            $page_start_display = ($current_page - 1) * $query_limit + 1;
            $content .= "<div class='stage'> $page_start_display ~ $page_end / $total_rows</div>";
            // 次へ
            if ($current_page < $total_pages) {
                $next_start = $current_page * $query_limit;
                $content .= '<a href="?tab_name=' . urlencode($tab_name) . '&page_start=' . $next_start . '&page_stage=2&flg=' . $flg . '">&gt;</a>';
            }
            // 最後へ
            if ($current_page < $total_pages) {
                $last_start = ($total_pages - 1) * $query_limit;
                $content .= '<a href="?tab_name=' . urlencode($tab_name) . '&page_start=' . $last_start . '&page_stage=2&flg=' . $flg . '">&gt;|</a>';
            }
            $content .= '</div>';
        }
        $content .= '</div>'; // .work_list_box 終了
        // --- ここまでラッパー追加 ---

        return $content;
    }

}