<?php

if (!class_exists('Kantan_List_Class')) {
class Kantan_List_Class{



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

        // 進捗状況ボタン
        $progress_labels = [
            1 => '受付中',
            2 => '見積中',
            3 => '作成中',
            4 => '完成未請求',
            5 => '請求済'
        ];
        $selected_progress = isset($_GET['progress']) ? intval($_GET['progress']) : 1;

        // 各進捗ごとの件数を取得
        $progress_counts = [];
        foreach ($progress_labels as $num => $label) {
            $progress_counts[$num] = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE progress = %d", $num));
        }

        $content .= '</div>'; // .controller 終了

        // 進捗ボタンを全幅で表示するworkflowエリア
        $content .= '<div class="workflow" style="width:100%;margin:10px 0 20px 0;">';
        $content .= '<div class="progress-filter" style="display:flex;gap:8px;width:100%;justify-content:center;">';
        foreach ($progress_labels as $num => $label) {
            $active = ($selected_progress === $num) ? 'style=\"font-weight:bold;background:#1976d2;color:#fff;\"' : '';
            $btn_label = $label . ' (' . $progress_counts[$num] . ')';
            $content .= '<a href="?tab_name=' . urlencode($tab_name) . '&progress=' . $num . '" class="progress-btn" '.$active.'>' . $btn_label . '</a>';
        }
        $content .= '</div>';
        $content .= '</div>';

        // 受注書リスト表示
        // $content .= '<h3>■ 受注書リスト</h3>';

        // ページネーション設定
        $query_limit = 5;
        $page_stage = isset($_GET['page_stage']) ? $_GET['page_stage'] : '';
        $page_start = isset($_GET['page_start']) ? intval($_GET['page_start']) : 0;
        $flg = isset($_GET['flg']) ? $_GET['flg'] : '';
        $selected_progress = isset($_GET['progress']) ? intval($_GET['progress']) : 1;
        if ($page_stage == '') {
            $page_start = 0;
        }
        // 総件数取得
        $total_query = $wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE progress = %d", $selected_progress);
        $total_rows = $wpdb->get_var($total_query);
        $total_pages = ceil($total_rows / $query_limit);
        $current_page = floor($page_start / $query_limit) + 1;
        // データ取得
        $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE progress = %d ORDER BY time DESC LIMIT %d, %d", $selected_progress, $page_start, $query_limit);
        $order_list = $wpdb->get_results($query);

        // --- ここからラッパー追加 ---
        $content .= '<div class="work_list_box">';
        if ($order_list) {
            // 進捗ラベル
            $progress_labels = [
                1 => '受付中',
                2 => '見積中',
                3 => '作成中',
                4 => '完成未請求',
                5 => '請求済',
                6 => '入金済'
            ];
            $content .= '<ul>';
            foreach ($order_list as $order) {
                $order_id = esc_html($order->id);
                $customer_name = esc_html($order->customer_name);
                $user_name = esc_html($order->user_name);
                $project_name = isset($order->project_name) ? esc_html($order->project_name) : '';
                // 日時フォーマット変換
                $raw_time = $order->time;
                $formatted_time = '';
                if (!empty($raw_time)) {
                    // UNIXタイムスタンプかMySQL日付か判定
                    if (is_numeric($raw_time) && strlen($raw_time) >= 10) {
                        // UNIXタイムスタンプ（秒単位）
                        $timestamp = (int)$raw_time;
                        $dt = new DateTime('@' . $timestamp);
                        $dt->setTimezone(new DateTimeZone('Asia/Tokyo'));
                    } else {
                        // MySQL DATETIME形式
                        $dt = date_create($raw_time, new DateTimeZone('Asia/Tokyo'));
                    }
                    if ($dt) {
                        $week = ['日','月','火','水','木','金','土'];
                        $w = $dt->format('w');
                        $formatted_time = $dt->format('n/j') . '（' . $week[$w] . '）' . $dt->format(' H:i');
                    }
                }
                $time = esc_html($formatted_time);
                $progress = intval($order->progress);
                $detail_url = add_query_arg('order_id', $order_id, '?tab_name=order');

                // プルダウンフォーム
                $content .= "<li style='display:flex;align-items:center;gap:8px;'>";
                $content .= "<a href='{$detail_url}'>ID: {$order_id} - {$customer_name} ({$user_name})";
                if ($project_name !== '') {
                    $content .= " - <span class='project_name'>{$project_name}</span>";
                }
                $content .= " - {$time}</a>";
                $content .= "<form method='post' action='' style='margin:0;display:inline;'>";
                $content .= "<input type='hidden' name='update_progress_id' value='{$order_id}' />";
                $content .= "<select name='update_progress' onchange='this.form.submit()' style='margin-left:8px;'>";
                foreach ($progress_labels as $num => $label) {
                    if ($num == 6) continue; // 入金済はリストで管理しない
                    $selected = ($progress === $num) ? 'selected' : '';
                    $content .= "<option value='{$num}' {$selected}>{$label}</option>";
                }
                $content .= "</select>";
                $content .= "</form>";
                $content .= "</li>";
            }
            $content .= '</ul>';
        } else {
            $content .= '<p>受注書データがありません。</p>';
        }
        // 進捗更新処理
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress_id'], $_POST['update_progress'])) {
            $update_id = intval($_POST['update_progress_id']);
            $update_progress = intval($_POST['update_progress']);
            if ($update_id > 0 && $update_progress >= 1 && $update_progress <= 6) {
                $wpdb->update($table_name, ['progress' => $update_progress], ['id' => $update_id]);
                // リダイレクトで再読み込み（POSTリダブミット防止）
                $redirect_url = $_SERVER['REQUEST_URI'];
                header('Location: ' . $redirect_url);
                exit;
            }
        }
        // --- ページネーション ---
        if ($total_pages > 1) {
            // 現在のGETパラメータを維持
            $base_params = $_GET;
            $base_params['tab_name'] = $tab_name;
            $base_params['page_stage'] = 2;
            $base_params['flg'] = $flg;
            $base_params['progress'] = $selected_progress;
            $content .= '<div class="pagination">';
            // 最初へ
            if ($current_page > 1) {
                $base_params['page_start'] = 0;
                $content .= '<a href="?' . http_build_query($base_params) . '">|&lt;</a>';
            }
            // 前へ
            if ($current_page > 1) {
                $base_params['page_start'] = ($current_page - 2) * $query_limit;
                $content .= '<a href="?' . http_build_query($base_params) . '">&lt;</a>';
            }
            // 現在のページ範囲表示と総数
            $page_end = min($total_rows, $current_page * $query_limit);
            $page_start_display = ($current_page - 1) * $query_limit + 1;
            $content .= "<div class='stage'> $page_start_display ~ $page_end / $total_rows</div>";
            // 次へ
            if ($current_page < $total_pages) {
                $base_params['page_start'] = $current_page * $query_limit;
                $content .= '<a href="?' . http_build_query($base_params) . '">&gt;</a>';
            }
            // 最後へ
            if ($current_page < $total_pages) {
                $base_params['page_start'] = ($total_pages - 1) * $query_limit;
                $content .= '<a href="?' . http_build_query($base_params) . '">&gt;|</a>';
            }
            $content .= '</div>';
        }
        $content .= '</div>'; // .work_list_box 終了
        // --- ここまでラッパー追加 ---

        return $content;
    }

}
} // class_exists