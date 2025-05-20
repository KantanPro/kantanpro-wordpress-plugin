<?php

class Kntan_Order_Class{

    // public $name;

    public function __construct() {
        // $this->name = 'order';
    }

    // -----------------------------
    // テーブル作成
    // -----------------------------
    function Create_Order_Table() {
        global $wpdb;
        $my_table_version = '1.0'; // 受注書テーブルのバージョン
        $table_name = $wpdb->prefix . 'ktp_order'; // 受注書テーブル名
        $charset_collate = $wpdb->get_charset_collate();

        $columns_def = [
            "id MEDIUMINT(9) NOT NULL AUTO_INCREMENT",
            "time BIGINT(11) DEFAULT '0' NOT NULL", // 作成日時
            "customer_name VARCHAR(100) NOT NULL", // 顧客名
            "user_name TINYTEXT", // 担当者名
            "progress TINYINT(1) NOT NULL DEFAULT 1", // 進捗状況 1:受付中 2:見積中 3:作成中 4:完成未請求 5:請求済 6:入金済
            "invoice_items TEXT", // 請求項目 (JSONまたはシリアライズされたデータ用)
            "cost_items TEXT", // コスト項目 (JSONまたはシリアライズされたデータ用)
            "memo TEXT", // メモ
            "search_field TEXT", // 検索用フィールド
            "UNIQUE KEY id (id)"
        ];

        // テーブルが存在しない場合のみ作成
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (" . implode(", ", $columns_def) . ") $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            add_option('ktp_order_table_version', $my_table_version);
        } else {
            // テーブルが存在する場合はカラムの差分を確認し追加
            $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name", 0);
            $def_column_names = [];
            foreach ($columns_def as $def) {
                if (preg_match('/^([a-zA-Z0-9_]+)/', $def, $m)) {
                    $def_column_names[] = $m[1];
                }
            }
            foreach ($def_column_names as $i => $col_name) {
                if (!in_array($col_name, $existing_columns)) {
                    if ($col_name === 'UNIQUE') continue;
                    $def = $columns_def[$i];
                    $wpdb->query("ALTER TABLE $table_name ADD COLUMN $def");
                }
            }
            // UNIQUE KEYの存在チェックと追加
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
            update_option('ktp_order_table_version', $my_table_version);
        }
    }

    function Order_Tab_View( $tab_name ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order'; // 受注書テーブル名
        // 進捗更新処理（POST時）
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

        // 受注書テーブルが存在しない場合は作成
        $this->Create_Order_Table();

        // URLパラメータから得意先情報を取得
        $customer_name = isset($_GET['customer_name']) ? htmlspecialchars($_GET['customer_name']) : '';
        $user_name = isset($_GET['user_name']) ? htmlspecialchars($_GET['user_name']) : '';
        $from_client = isset($_GET['from_client']) ? intval($_GET['from_client']) : 0; // 得意先タブからの遷移フラグ
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0; // 表示する受注書ID

        $content = ''; // 表示するHTMLコンテンツ

        // 受注書削除処理
        if (isset($_GET['delete_order']) && $_GET['delete_order'] == 1 && $order_id > 0) {
            // 削除処理
            $deleted = $wpdb->delete($table_name, array('id' => $order_id));
            if ($deleted) {
                // 削除後は最新の受注書または一覧にリダイレクト
                $latest_order = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY time DESC LIMIT 1");
                if ($latest_order) {
                    $redirect_url = add_query_arg('order_id', $latest_order->id, remove_query_arg(['delete_order', 'order_id']));
                } else {
                    $redirect_url = remove_query_arg(['delete_order', 'order_id']);
                }
                wp_redirect($redirect_url);
                exit;
            } else {
                $content .= '<div class="error">受注書の削除に失敗しました。</div>';
            }
        }

        // 得意先タブから遷移してきた場合（新規受注書作成）
        if ($from_client === 1 && $customer_name !== '') {
            // 受注書データをデータベースに挿入
            $insert_data = array(
                'time' => current_time( 'mysql' ),
                'customer_name' => $customer_name,
                'user_name' => $user_name,
                'invoice_items' => '', // 初期値は空
                'cost_items' => '', // 初期値は空
                'memo' => '', // 初期値は空
                'search_field' => implode(', ', [$customer_name, $user_name]), // 検索用フィールド
            );

            $inserted = $wpdb->insert($table_name, $insert_data);

            if ($inserted) {
                $new_order_id = $wpdb->insert_id; // 挿入された受注書IDを取得
                // 新規作成された受注書の詳細を表示するためにリダイレクト
                $redirect_url = add_query_arg('order_id', $new_order_id, remove_query_arg('from_client'));
                wp_redirect($redirect_url);
                exit;
            } else {
                // 挿入失敗時のエラーハンドリング
                $content .= '<div class="error">受注書の作成に失敗しました。</div>';
                error_log('受注書挿入エラー: ' . $wpdb->last_error);
            }
        }

        // 受注書IDが指定されていない場合は最新の受注書IDを取得
        if ($order_id === 0) {
            $latest_order = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY time DESC LIMIT 1");
            if ($latest_order) {
                $order_id = $latest_order->id;
            }
        }

        // 受注書データが存在する場合に詳細を表示
        if ($order_id > 0) {
            $order_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $order_id));

            if ($order_data) {
                // プレビュー用HTML（詳細表示用）
                $preview_html = "<div><strong>伝票プレビュー</strong><br>受注書ID: " . esc_html($order_data->id) . "<br>会社名：" . esc_html($order_data->customer_name) . "<br>担当者名：" . esc_html($order_data->user_name) . "</div>";
                $preview_html_json = json_encode($preview_html);

                // プレビュー・印刷ボタンのJavaScriptとHTMLを先に生成
                $content .= <<<END
                <script>
                var isOrderPreviewOpen = false;
                function printOrderContent() {
                    var printContent = $preview_html_json;
                    var printWindow = window.open('', '_blank');
                    printWindow.document.open();
                    printWindow.document.write('<html><head><title>印刷</title></head><body>');
                    printWindow.document.write(printContent);
                    printWindow.document.write('<script>window.onafterprint = function(){ window.close(); }<\/script>');
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();
                    printWindow.print();
                }
                function toggleOrderPreview() {
                    var previewWindow = document.getElementById('orderPreviewWindow');
                    var previewButton = document.getElementById('orderPreviewButton');
                    if (isOrderPreviewOpen) {
                        previewWindow.style.display = 'none';
                        previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="プレビュー">preview</span>';
                        isOrderPreviewOpen = false;
                        return; // 得意先タブに合わせてreturnを追加
                    } else {
                        var printContent = $preview_html_json;
                        previewWindow.innerHTML = printContent;
                        previewWindow.style.display = 'block';
                        previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="閉じる">close</span>';
                        isOrderPreviewOpen = true;
                        return; // 得意先タブに合わせてreturnを追加
                    }
                }
                </script>
                <div class="controller">
                    <div class="printer">
                        <div class="up-title">伝票処理：</div>
                        <button id="orderPreviewButton" onclick="toggleOrderPreview()" title="プレビュー">
                            <span class="material-symbols-outlined" aria-label="プレビュー">preview</span>
                        </button>
                        <button onclick="printOrderContent()" title="印刷する">
                            <span class="material-symbols-outlined" aria-label="印刷">print</span>
                        </button>
                    </div>
                </div>
                <div id="orderPreviewWindow" style="display: none;"></div>
                END;

                // 進捗プルダウン
                $progress_labels = [
                    1 => '受付中',
                    2 => '見積中',
                    3 => '作成中',
                    4 => '完成未請求',
                    5 => '請求済',
                    6 => '入金済'
                ];
                $content .= '<div class="order_progress_box box" style="margin:16px 0;">';
                $content .= '<form method="post" action="" style="display:inline;">';
                $content .= '<input type="hidden" name="update_progress_id" value="' . esc_html($order_data->id) . '" />';
                $content .= '<label for="order_progress_select">進捗：</label>';
                $content .= '<select id="order_progress_select" name="update_progress" onchange="this.form.submit()">';
                foreach ($progress_labels as $num => $label) {
                    $selected = ($order_data->progress == $num) ? 'selected' : '';
                    $content .= '<option value="' . $num . '" ' . $selected . '>' . $label . '</option>';
                }
                $content .= '</select>';
                $content .= '</form>';
                $content .= '</div>';

                // 受注書詳細の表示（以前のレイアウト）
                $content .= '<div class="order_contents">';
                $content .= '<div class="order_info_box box">';
                $content .= '<h4>■ 得意先情報</h4>';
                $content .= '<div>会社名：<span id="order_customer_name">' . esc_html($order_data->customer_name) . '</span></div>';
                $content .= '<div>担当者名：<span id="order_user_name">' . esc_html($order_data->user_name) . '</span></div>';
                $content .= '</div>'; // .order_info_box 終了

                $content .= '<div class="order_invoice_box box">';
                $content .= '<h4>■ 請求項目（受注書ID: ' . esc_html($order_data->id) . '）</h4>'; // 受注書IDをここに表示
                // TODO: 請求項目の表示・編集フォームを追加
                $content .= '<div>（後日指示）</div>';
                $content .= '</div>'; // .order_invoice_box 終了

                $content .= '<div class="order_cost_box box">';
                $content .= '<h4>■ コスト項目</h4>';
                // TODO: コスト項目の表示・編集フォームを追加
                $content .= '<div>（後日指示）</div>';
                $content .= '</div>'; // .order_cost_box 終了

                $content .= '<div class="order_memo_box box">';
                $content .= '<h4>■ メモ項目</h4>';
                // TODO: メモの表示・編集フォームを追加
                $content .= '<div>（後日指示）</div>';
                $content .= '</div>'; // .order_memo_box 終了

                $content .= '</div>'; // .order_contents 終了

                // 削除ボタンとJS
                $delete_url = add_query_arg(['order_id' => $order_id, 'delete_order' => 1]);
                $content .= '<div class="order_delete_box box" style="margin-top:20px;">';
                $content .= '<a href="#" id="orderDeleteButton" style="color:#fff;background:#d9534f;padding:8px 16px;border:none;border-radius:4px;cursor:pointer;display:inline-block;text-decoration:none;" onclick="event.preventDefault(); confirmDeleteOrder();">受注書を削除</a>';
                $content .= '</div>';
                $content .= "<script>\nfunction confirmDeleteOrder() {\n  if (window.confirm('本当にこの受注書を削除しますか？\\nこの操作は元に戻せません。')) {\n    window.location.href = '{$delete_url}';\n  }\n}\n</script>";

            } else {
                $content .= '<div class="error">指定された受注書は見つかりませんでした。</div>';
            }

        } else {
            // 受注書データが存在しない場合でもレイアウトを維持
            $content .= '
<div class="controller">
    <div class="printer">
        <div class="up-title">伝票処理：</div>
        <button id="orderPreviewButton" disabled title="プレビュー">
            <span class="material-symbols-outlined" aria-label="プレビュー">preview</span>
        </button>
        <button disabled title="印刷する">
            <span class="material-symbols-outlined" aria-label="印刷">print</span>
        </button>
    </div>
</div>
<p>表示する受注書がありません。</p>';
        }

        // ページネーションロジック（表示はしないが計算は残す）
        $query_limit = 10; // 1ページあたりの表示件数
        $page_start = isset($_GET['page_start']) ? intval($_GET['page_start']) : 0; // 表示開始位置

        // 全データ数を取得
        $total_query = "SELECT COUNT(*) FROM {$table_name}";
        $total_rows = $wpdb->get_var($total_query);
        $total_pages = ceil($total_rows / $query_limit);

        // 現在のページ番号を計算
        $current_page = floor($page_start / $query_limit) + 1;

        // TODO: ページネーションリンクのHTML生成は削除またはコメントアウト
        // $content .= "<div class='pagination'>";
        // ... ページネーションリンク生成コード ...
        // $content .= "</div>"; // .pagination 終了

        return $content;
    }

}