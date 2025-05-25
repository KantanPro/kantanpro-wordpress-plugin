<?php

if (!class_exists('Kntan_Order_Class')) {
class Kntan_Order_Class{



    public function __construct() {
        // $this->name = 'order';
    }

    // -----------------------------
    // テーブル作成
    // -----------------------------
    function Create_Order_Table() {
        global $wpdb;
        $my_table_version = '1.1'; // 受注書テーブルのバージョンを1.1に変更（カラム追加のため）
        $table_name = $wpdb->prefix . 'ktp_order'; // 受注書テーブル名
        $charset_collate = $wpdb->get_charset_collate();

        $columns_def = [
            "id MEDIUMINT(9) NOT NULL AUTO_INCREMENT",
            "time BIGINT(11) DEFAULT '0' NOT NULL", // 作成日時
            "client_id MEDIUMINT(9) DEFAULT NULL", // 顧客ID：顧客テーブルとの紐付け用
            "customer_name VARCHAR(100) NOT NULL", // 顧客名
            "user_name TINYTEXT", // 担当者名
            "project_name VARCHAR(255)", // 案件名
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
        $client_table = $wpdb->prefix . 'ktp_client'; // 顧客テーブル名

        // メール送信処理（編集フォームのみ）
        $mail_form_html = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_order_mail_id'])) {
            $order_id = intval($_POST['send_order_mail_id']);
            $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $order_id));
            if ($order) {
                // 顧客情報取得
                $client = null;
                // まずはclient_idで検索
                if (!empty($order->client_id)) {
                    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$client_table} WHERE id = %d", $order->client_id));
                }
                // 見つからない場合は会社名と担当者名で検索（後方互換性）
                if (!$client) {
                    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$client_table} WHERE company_name = %s AND name = %s", 
                        $order->customer_name, $order->user_name));
                }
                $to = $client && !empty($client->email) ? $client->email : '';
                if (empty($to)) {
                    echo '<script>alert("得意先のメールアドレスが未入力です。顧客管理画面でメールアドレスを登録してください。");</script>';
                } else {
                    // 自社情報取得
                    $setting_table = $wpdb->prefix . 'ktp_setting';
                    $setting = $wpdb->get_row("SELECT * FROM {$setting_table} WHERE id = 1");
                    $my_company = $setting ? strip_tags($setting->my_company_content) : '';
                    $my_email = $setting ? $setting->email_address : '';
                    $my_name = '';

                    // 請求項目リスト・金額（仮実装）
                    $invoice_items = $order->invoice_items ? $order->invoice_items : '';
                    $amount = '';
                    if ($invoice_items) {
                        $items = @json_decode($invoice_items, true);
                        if (is_array($items)) {
                            $amount = 0;
                            foreach ($items as $item) {
                                $amount += isset($item['amount']) ? (int)$item['amount'] : 0;
                            }
                            $invoice_list = "\n";
                            foreach ($items as $item) {
                                $invoice_list .= (isset($item['name']) ? $item['name'] : '') . '：' . (isset($item['amount']) ? $item['amount'] : '') . "円\n";
                            }
                        } else {
                            $invoice_list = $invoice_items;
                        }
                    } else {
                        $invoice_list = '（請求項目未入力）';
                    }
                    $amount_str = $amount ? number_format($amount) . '円' : '';

                    // 進捗ごとに件名・本文
                    $progress = (int)$order->progress;
                    $project_name = $order->project_name ? $order->project_name : '';
                    $customer_name = $order->customer_name;
                    $user_name = $order->user_name;
                    $body = $subject = '';
                    if ($progress === 1) {
                        $subject = "お見積り：{$project_name}";
                        $body = "{$customer_name}\n{$user_name} 様\n\nこの度はご依頼ありがとうございます。\n{$project_name}につきましてお見積させていただきます。\n\n＜お見積り＞\n{$project_name}\n{$invoice_list}\n{$amount_str}\n\n—\n{$my_company}\n{$my_email}";
                    } elseif ($progress === 2) {
                        $subject = "ご注文ありがとうございます：{$project_name}";
                        $body = "{$customer_name}\n{$user_name} 様\n\nこの度はご注文頂きありがとうございます。\n{$project_name}につきまして対応させていただきます。\n\n＜ご注文内容＞\n{$project_name}\n{$invoice_list}\n{$amount_str}\n\n—\n{$my_company}\n{$my_email}";
                    } elseif ($progress === 3) {
                        $subject = "{$project_name}につきまして質問です";
                        $body = "{$customer_name}\n{$user_name} 様\n\nお世話になります。\n{$project_name}につきまして質問です。\n\n＜質問内容＞\n（ご質問内容をここにご記入ください）\n\n—\n{$my_company}\n{$my_email}";
                    } elseif ($progress === 4) {
                        $subject = "{$project_name}の請求書です";
                        $body = "{$customer_name}\n{$user_name} 様\n\nお世話になります。\n{$project_name}につきまして請求させていただきます。\n\n＜請求書＞\n{$project_name}\n{$invoice_list}\n{$amount_str}\n\n—\n{$my_company}\n{$my_email}";                    } elseif ($progress === 5) {
                        $subject = "{$project_name}のご入金を確認しました";
                        $body = "{$customer_name}\n{$user_name} 様\n\nお世話になります。\n{$project_name}につきましてご入金いただきありがとうございます。\n今後ともよろしくお願い申し上げます。\n\n—\n{$my_company}\n{$my_email}";
                    } elseif ($progress === 6) {
                        $subject = "{$project_name}";
                        $body = "{$customer_name}\n{$user_name} 様\n\nお世話になります。\n\n—\n{$my_company}\n{$my_email}";
                    }

                    $edit_subject = isset($_POST['edit_subject']) ? stripslashes($_POST['edit_subject']) : $subject;
                    $edit_body = isset($_POST['edit_body']) ? stripslashes($_POST['edit_body']) : $body;

                    // 送信ボタンが押された場合
                    if (isset($_POST['do_send_mail']) && $_POST['do_send_mail'] == '1') {
                        $headers = [];
                        if ($my_email) $headers[] = 'From: ' . $my_email;
                        $sent = wp_mail($to, $edit_subject, $edit_body, $headers);
                        if ($sent) {
                            echo '<script>alert("メールを送信しました。\n宛先: ' . esc_js($to) . '");</script>';
                        } else {
                            echo '<script>alert("メール送信に失敗しました。サーバー設定をご確認ください。");</script>';
                        }
                    } else {
                        // 編集フォームHTMLを生成
                        $mail_form_html = '<div id="order-mail-form" style="background:#fff;border:2px solid #2196f3;padding:24px;max-width:520px;margin:32px auto 16px auto;border-radius:8px;box-shadow:0 2px 12px #0002;z-index:9999;">';
                        $mail_form_html .= '<h3 style="margin-top:0;">メール送信内容の編集</h3>';
                        $mail_form_html .= '<form method="post" action="">';
                        $mail_form_html .= '<input type="hidden" name="send_order_mail_id" value="' . esc_attr($order_id) . '">';
                        $mail_form_html .= '<div style="margin-bottom:12px;"><label>宛先：</label><input type="email" value="' . esc_attr($to) . '" readonly style="width:320px;max-width:100%;background:#f5f5f5;"></div>';
                        $mail_form_html .= '<div style="margin-bottom:12px;"><label>件名：</label><input type="text" name="edit_subject" value="' . esc_attr($edit_subject) . '" style="width:320px;max-width:100%;"></div>';
                        $mail_form_html .= '<div style="margin-bottom:12px;"><label>本文：</label><textarea name="edit_body" rows="10" style="width:100%;max-width:480px;">' . esc_textarea($edit_body) . '</textarea></div>';
                        $mail_form_html .= '<button type="submit" name="do_send_mail" value="1" style="background:#2196f3;color:#fff;padding:8px 18px;border:none;border-radius:4px;font-size:15px;">送信</button>';
                        $mail_form_html .= '<button type="button" onclick="document.getElementById(\'order-mail-form\').style.display=\'none\';" style="margin-left:16px;padding:8px 18px;border:none;border-radius:4px;font-size:15px;">キャンセル</button>';
                        $mail_form_html .= '</form>';
                        $mail_form_html .= '</div>';
                    }
                }
            }
        }
        // 案件名の保存処理
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project_name_id'], $_POST['order_project_name'])) {
            $update_id = intval($_POST['update_project_name_id']);
            $project_name = sanitize_text_field($_POST['order_project_name']);
            if ($update_id > 0) {
                $wpdb->update($table_name, ['project_name' => $project_name], ['id' => $update_id]);
                // POSTリダブミット防止のためリダイレクト
                $redirect_url = $_SERVER['REQUEST_URI'];
                header('Location: ' . $redirect_url);
                exit;
            }
        }
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
                // リダイレクト処理を無効化 - 代わりに最新の受注書IDを直接設定
                $latest_order = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY time DESC LIMIT 1");
                if ($latest_order) {
                    $_GET['order_id'] = $latest_order->id;
                    $order_id = $latest_order->id;
                } else {
                    $_GET['order_id'] = 0;
                    $order_id = 0;
                }
                $_GET['delete_order'] = null; // delete_orderフラグをクリア
                
                error_log("KTPWP Debug: Order deleted, redirect disabled");
            } else {
                $content .= '<div class="error">受注書の削除に失敗しました。</div>';
            }
        }

        // 得意先タブから遷移してきた場合（新規受注書作成）
        if ($from_client === 1 && $customer_name !== '') {
            // 顧客IDを取得
            $client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
            
            // 顧客IDが提供されなかった場合は、会社名と担当者名から顧客IDを検索
            if ($client_id <= 0 && $customer_name !== '') {
                $client = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$client_table} WHERE company_name = %s AND name = %s",
                    $customer_name, 
                    $user_name
                ));
                if ($client) {
                    $client_id = $client->id;
                }
            }
            
            // 受注書データをデータベースに挿入
            // 標準的なUNIXタイムスタンプを使用（UTCベース）
            $timestamp = time(); // 標準のUTC UNIXタイムスタンプを取得
            
            $insert_data = array(
                'time' => $timestamp, // 標準のUTC UNIXタイムスタンプで保存
                'client_id' => $client_id, // 顧客IDを保存
                'customer_name' => $customer_name,
                'user_name' => $user_name,
                'project_name' => '※ 入力してください', // 案件名の初期値を設定
                'invoice_items' => '', // 初期値は空
                'cost_items' => '', // 初期値は空
                'memo' => '', // 初期値は空
                'search_field' => implode(', ', [$customer_name, $user_name]), // 検索用フィールド
            );

            $inserted = $wpdb->insert($table_name, $insert_data);

            if ($inserted) {
                $new_order_id = $wpdb->insert_id; // 挿入された受注書IDを取得
                
                // リダイレクト処理を無効化 - 代わりにorder_idを直接設定
                $_GET['order_id'] = $new_order_id;
                $_GET['from_client'] = null; // from_clientフラグをクリア
                $order_id = $new_order_id; // ローカル変数も更新
                
                // デバッグ用ログ
                error_log("KTPWP Debug: Order created with ID: {$new_order_id}, redirect disabled");
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
                $client_id_text = !empty($order_data->client_id) ? "（顧客ID: " . esc_html($order_data->client_id) . "）" : "（顧客ID未設定）";
                $preview_html = "<div><strong>伝票プレビュー</strong><br>受注書ID: " . esc_html($order_data->id) . "<br>会社名：" . esc_html($order_data->customer_name) . " " . $client_id_text . "<br>担当者名：" . esc_html($order_data->user_name) . "</div>";
                $preview_html_json = json_encode($preview_html);

                // プレビュー・印刷ボタンのJavaScriptとHTMLを先に生成
                $content .= '<script>';
                $content .= 'var isOrderPreviewOpen = false;';
                $content .= 'function printOrderContent() {';
                $content .= '    var printContent = ' . $preview_html_json . ';';
                $content .= '    var printWindow = window.open("", "_blank");';
                $content .= '    printWindow.document.open();';
                $content .= '    printWindow.document.write("<html><head><title>印刷</title></head><body>");';
                $content .= '    printWindow.document.write(printContent);';
                $content .= '    printWindow.document.write("<script>window.onafterprint = function(){ window.close(); }<\\/script>");';
                $content .= '    printWindow.document.write("</body></html>");';
                $content .= '    printWindow.document.close();';
                $content .= '    printWindow.print();';
                $content .= '}';
                $content .= 'function toggleOrderPreview() {';
                $content .= '    var previewWindow = document.getElementById("orderPreviewWindow");';
                $content .= '    var previewButton = document.getElementById("orderPreviewButton");';
                $content .= '    if (isOrderPreviewOpen) {';
                $content .= '        previewWindow.style.display = "none";';
                $content .= '        previewButton.innerHTML = "<span class=\"material-symbols-outlined\" aria-label=\"プレビュー\">preview</span>";';
                $content .= '        isOrderPreviewOpen = false;';
                $content .= '        return;';
                $content .= '    } else {';
                $content .= '        var printContent = ' . $preview_html_json . ';';
                $content .= '        previewWindow.innerHTML = printContent;';
                $content .= '        previewWindow.style.display = "block";';
                $content .= '        previewButton.innerHTML = "<span class=\"material-symbols-outlined\" aria-label=\"閉じる\">close</span>";';
                $content .= '        isOrderPreviewOpen = true;';
                $content .= '        return;';
                $content .= '    }';
                $content .= '}';
                $content .= '</script>';

                $content .= '<div class="controller">';
                $content .= '<div class="printer">';
                $content .= '<button id="orderPreviewButton" onclick="toggleOrderPreview()" title="プレビュー">';
                $content .= '<span class="material-symbols-outlined" aria-label="プレビュー">preview</span>';
                $content .= '</button>';
                $content .= '<button onclick="printOrderContent()" title="印刷する">';
                $content .= '<span class="material-symbols-outlined" aria-label="印刷">print</span>';
                $content .= '</button>';
                $content .= '<form id="orderMailForm" method="post" action="" style="display:inline;margin-top:0px;">';
                $content .= '<input type="hidden" name="send_order_mail_id" value="' . esc_attr($order_data->id) . '">';
$content .= '<button type="submit" id="orderMailButton" title="メール">';
$content .= '<span class="material-symbols-outlined" aria-label="メール">mail</span>';
                $content .= '</button>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div id="orderPreviewWindow" style="display: none;"></div>';

                // workflowセクション追加（デザイン統一）
                $content .= '<div class="workflow">';
                // 削除ボタンをworkflow内に移動（フォーム送信ベース）
                $content .= '<form method="post" action="" style="display:inline-block;margin-left:10px;" onsubmit="return confirm(\'本当にこの受注書を削除しますか？\\nこの操作は元に戻せません。\');">';
                $content .= '<input type="hidden" name="order_id" value="' . esc_attr($order_id) . '">';
                $content .= '<input type="hidden" name="delete_order" value="1">';
                $content .= '<button type="submit" style="color:#fff;background:#d9534f;padding:0px 8px;font-size:16px;border:none;border-radius:4px;cursor:pointer;">受注書を削除</button>';
                $content .= '</form>';
                $content .= '</div>';

                // メール編集フォーム導入により、進捗3の質問内容プロンプトは不要になったため削除

                // メール編集フォームがあればworkflowの直後で$contentに追加
                if (!empty($mail_form_html)) {
                    $content .= $mail_form_html;
                }


                // 進捗ラベルを明示的に定義
                $progress_labels = [
                    1 => '受付中',
                    2 => '見積中',
                    3 => '作成中',
                    4 => '完成未請求',
                    5 => '請求済',
                    6 => '入金済'
                ];

                // 受注書詳細の表示（以前のレイアウト）
                $content .= '<div class="order_contents">';
                $content .= '<div class="order_info_box box">';
// ■ 受注書概要（ID: *）案件名フィールドを同一div内で横並びに
$content .= '<div class="order-header-flex order-header-inline-summary">';
$content .= '<span class="order-header-title-id">■ 受注書概要（ID: ' . esc_html($order_data->id) . '）'
    . '<input type="text" id="order_project_name_inline" name="order_project_name_inline" value="' . (isset($order_data->project_name) ? esc_html($order_data->project_name) : '') . '" data-order-id="' . esc_html($order_data->id) . '" class="order-header-projectname" placeholder="案件名" autocomplete="off" />'
    . '</span>';
$content .= '<form method="post" action="" class="progress-filter order-header-progress-form" style="display:flex;align-items:center;gap:8px;flex-wrap:nowrap;margin-left:auto;">';
$content .= '<input type="hidden" name="update_progress_id" value="' . esc_html($order_data->id) . '" />';
$content .= '<label for="order_progress_select" style="white-space:nowrap;margin-right:4px;font-weight:bold;">進捗：</label>';
$content .= '<select id="order_progress_select" name="update_progress" onchange="this.form.submit()" style="min-width:120px;max-width:200px;width:auto;">';
foreach ($progress_labels as $num => $label) {
    $selected = ($order_data->progress == $num) ? 'selected' : '';
    $content .= '<option value="' . $num . '" ' . $selected . '>' . $label . '</option>';
}
$content .= '</select>';
$content .= '</form>';
$content .= '</div>';
                $client_id_display = !empty($order_data->client_id) ? '（顧客ID: ' . esc_html($order_data->client_id) . '）' : '（顧客ID未設定）';
                $content .= '<div>会社名：<span id="order_customer_name">' . esc_html($order_data->customer_name) . '</span> <span class="client-id" style="color:#666;font-size:0.9em;">' . $client_id_display . '</span></div>';
                // 担当者名の横に得意先メールアドレスのmailtoリンク（あれば）
                $client_email = '';
                $client = null;
                
                // まず顧客IDがある場合はIDで検索
                if (!empty($order_data->client_id)) {
                    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$client_table} WHERE id = %d", $order_data->client_id));
                }
                
                // IDで見つからない場合は会社名と担当者名で検索（後方互換性）
                if (!$client) {
                    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$client_table} WHERE company_name = %s AND name = %s", 
                        $order_data->customer_name, $order_data->user_name));
                }
                
                if ($client && !empty($client->email)) {
                    $client_email = esc_attr($client->email);
                    $content .= '<div>担当者名：<span id="order_user_name">' . esc_html($order_data->user_name) . '</span>';
                    $content .= ' <a href="mailto:' . $client_email . '" style="margin-left:8px;vertical-align:middle;" title="メール送信">';
                    $content .= '<span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;color:#2196f3;">mail</span>';
                    $content .= '</a></div>';
                } else {
                    $content .= '<div>担当者名：<span id="order_user_name">' . esc_html($order_data->user_name) . '</span></div>';
                }
                // 作成日時の表示
                $raw_time = $order_data->time;
                $formatted_time = '';
                if (!empty($raw_time)) {
                    if (is_numeric($raw_time) && strlen($raw_time) >= 10) {
                        // time()で取得したUNIXタイムスタンプはUTCベース
                        // UTCとして解釈して、適切にタイムゾーン変換する
                        $unix_timestamp = (int)$raw_time;
                        
                        // UTCタイムスタンプからDateTimeオブジェクトを作成し、WPタイムゾーンに変換
                        $dt = new DateTime('@' . $unix_timestamp); // '@'プレフィックスでUTCとして解釈
                        $dt->setTimezone(new DateTimeZone(wp_timezone_string())); // WordPressのタイムゾーンを適用
                    } else {
                        $dt = date_create($raw_time, new DateTimeZone(wp_timezone_string()));
                    }
                    if ($dt) {
                        // ロケールに応じた曜日の取得
                        $locale = get_locale();
                        if (substr($locale, 0, 2) === 'ja') {
                            // 日本語の場合
                            $week = ['日','月','火','水','木','金','土'];
                            $w = $dt->format('w');
                            $formatted_time = $dt->format('Y/n/j') . '（' . $week[$w] . '）' . $dt->format(' H:i');
                        } else {
                            // その他の言語の場合は国際的な形式を使用
                            $formatted_time = $dt->format('Y-m-d l H:i');
                        }
                    }
                }
                $content .= '<div>作成日時：<span id="order_created_time">' . esc_html($formatted_time) . '</span></div>';
                // 案件名インライン入力をh4タイトル行に移動
                $project_name = isset($order_data->project_name) ? esc_html($order_data->project_name) : '';
                $content = preg_replace(
                  '/(<h4[^>]*>■ 受注書概要.*?)(<span[^>]*>（ID:.*?）<\/span>)/s',
                  '$1$2'
                  . '<input type="text" id="order_project_name_inline" name="order_project_name_inline" value="' . $project_name . '" '
                  . 'data-order-id="' . esc_html($order_data->id) . '" '
                  . 'style="margin-left:12px;width:220px;max-width:40vw;display:inline-block;font-size:1em;vertical-align:middle;" '
                  . 'placeholder="案件名" autocomplete="off" />',
                  $content
                );
                $content .= '</div>'; // .order_info_box 終了

                $content .= '<div class="order_invoice_box box">';
                $content .= '<h4>■ 請求項目</h4>';
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

                // 削除ボタンはworkflow内に移動済み

            } else {
                $content .= '<div class="error">指定された受注書は見つかりませんでした。</div>';
            }

        } else {
            // 受注書データが存在しない場合でもレイアウトを維持
            $content .= '
<div class="controller">
    <div class="printer">
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
        $query_limit = 20; // 1ページあたりの表示件数
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
} // class_exists