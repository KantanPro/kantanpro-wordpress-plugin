<?php
if (!defined('ABSPATH')) exit;

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

    // Refactored: Mail sending logic
    private function handle_order_mail_sending(&$content, $order_id, $order_data, $client_table, $setting_table) {
        global $wpdb;
        $mail_form_html = ''; // Syntax error fixed here

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_order_mail_id'])) {
            // Nonceの検証 (メール編集フォーム表示要求時)
            if (!isset($_POST['_wpnonce_send_order_mail']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_send_order_mail'])), 'ktp_send_order_mail_action_' . sanitize_text_field(wp_unslash($_POST['send_order_mail_id'])))) {
                wp_die(esc_html__('Nonce verification failed for initiating mail sending.', 'ktpwp'));
            }
            $current_order_id = intval($_POST['send_order_mail_id']);
            // $order_data は既に Order_Tab_View から渡されているものを使用する想定だが、
            // この独立したメソッドでは、改めて取得するか、引数で渡された $order_data が正しいIDのものか確認が必要。
            // ここでは引数で渡された $order_data が正しいと仮定する。
            // if ($order_data && $order_data->id == $current_order_id) { ... }

            // 顧客情報取得
            $client = null;
            if (!empty($order_data->client_id)) {
                $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$client_table} WHERE id = %d", $order_data->client_id));
            }
            if (!$client) {
                $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$client_table} WHERE company_name = %s AND name = %s",
                    $order_data->customer_name, $order_data->user_name));
            }
            $to = $client && !empty($client->email) ? $client->email : '';

            if (empty($to)) {
                // JavaScriptアラートではなく、管理画面通知などの方が望ましい
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('得意先のメールアドレスが未入力です。顧客管理画面でメールアドレスを登録してください。', 'ktpwp') . '</p></div>';
                });
            } else {
                $setting = $wpdb->get_row("SELECT * FROM {$setting_table} WHERE id = 1");
                $my_company = $setting ? strip_tags($setting->my_company_content) : '';
                $my_email = $setting ? $setting->email_address : '';

                $invoice_items_raw = $order_data->invoice_items ? $order_data->invoice_items : '';
                $invoice_list = '';
                $total_amount = 0;
                if ($invoice_items_raw) {
                    $items = json_decode($invoice_items_raw, true);
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $item_name = isset($item['name']) ? $item['name'] : '';
                            $item_amount = isset($item['amount']) ? (int)$item['amount'] : 0;
                            $invoice_list .= $item_name . '：' . number_format($item_amount) . esc_html__('円', 'ktpwp') . "\n";
                            $total_amount += $item_amount;
                        }
                    } else {
                        $invoice_list = $invoice_items_raw; // JSONデコード失敗時はそのまま表示
                    }
                } else {
                    /* translators: Placeholder for when invoice items are not entered. */
                    $invoice_list = '(' . esc_html__('請求項目未入力', 'ktpwp') . ')';
                }
                $amount_str = $total_amount ? number_format($total_amount) . esc_html__('円', 'ktpwp') : '';

                $progress = (int)$order_data->progress;
                $project_name = $order_data->project_name ? $order_data->project_name : '';
                $customer_name = $order_data->customer_name;
                $user_name = $order_data->user_name;
                $body = $subject = '';

                if ($progress === 1) {
                    /* translators: Email subject for quote. %s: project name. */
                    $subject = sprintf(esc_html__('お見積り：%s', 'ktpwp'), $project_name);
                    /* translators: Email body for quote. %1$s: customer name, %2$s: user name, %3$s: project name, %4$s: invoice list, %5$s: total amount, %6$s: my company, %7$s: my email. */
                    $body = sprintf(
                        esc_html__("%1\$s\n%2\$s 様\n\nこの度はご依頼ありがとうございます。\n%3\$sにつきましてお見積させていただきます。\n\n＜お見積り＞\n%3\$s\n%4\$s\n%5\$s\n\n—\n%6\$s\n%7\$s", 'ktpwp'),
                        $customer_name, $user_name, $project_name, $invoice_list, $amount_str, $my_company, $my_email
                    );
                } elseif ($progress === 2) {
                    /* translators: Email subject for order confirmation. %s: project name. */
                    $subject = sprintf(esc_html__('ご注文ありがとうございます：%s', 'ktpwp'), $project_name);
                    /* translators: Email body for order confirmation. %1$s: customer name, %2$s: user name, %3$s: project name, %4$s: invoice list, %5$s: total amount, %6$s: my company, %7$s: my email. */
                    $body = sprintf(
                        esc_html__("%1\$s\n%2\$s 様\n\nこの度はご注文頂きありがとうございます。\n%3\$sにつきまして対応させていただきます。\n\n＜ご注文内容＞\n%3\$s\n%4\$s\n%5\$s\n\n—\n%6\$s\n%7\$s", 'ktpwp'),
                        $customer_name, $user_name, $project_name, $invoice_list, $amount_str, $my_company, $my_email
                    );
                } elseif ($progress === 3) {
                    /* translators: Email subject for inquiry. %s: project name. */
                    $subject = sprintf(esc_html__('%sにつきまして質問です', 'ktpwp'), $project_name);
                    /* translators: Email body for inquiry. %1$s: customer name, %2$s: user name, %3$s: project name, %4$s: my company, %5$s: my email. */
                    $body = sprintf(
                        esc_html__("%1\$s\n%2\$s 様\n\nお世話になります。\n%3\$sにつきまして質問です。\n\n＜質問内容＞\n（ご質問内容をここにご記入ください）\n\n—\n%4\$s\n%5\$s", 'ktpwp'),
                        $customer_name, $user_name, $project_name, $my_company, $my_email
                    );
                } elseif ($progress === 4) {
                    /* translators: Email subject for invoice. %s: project name. */
                    $subject = sprintf(esc_html__('%sの請求書です', 'ktpwp'), $project_name);
                    /* translators: Email body for invoice. %1$s: customer name, %2$s: user name, %3$s: project name, %4$s: invoice list, %5$s: total amount, %6$s: my company, %7$s: my email. */
                    $body = sprintf(
                        esc_html__("%1\$s\n%2\$s 様\n\nお世話になります。\n%3\$sにつきまして請求させていただきます。\n\n＜請求書＞\n%3\$s\n%4\$s\n%5\$s\n\n—\n%6\$s\n%7\$s", 'ktpwp'),
                        $customer_name, $user_name, $project_name, $invoice_list, $amount_str, $my_company, $my_email
                    );
                } elseif ($progress === 5) {
                    /* translators: Email subject for payment confirmation. %s: project name. */
                    $subject = sprintf(esc_html__('%sのご入金を確認しました', 'ktpwp'), $project_name);
                    /* translators: Email body for payment confirmation. %1$s: customer name, %2$s: user name, %3$s: project name, %4$s: my company, %5$s: my email. */
                    $body = sprintf(
                        esc_html__("%1\$s\n%2\$s 様\n\nお世話になります。\n%3\$sにつきましてご入金いただきありがとうございます。\n今後ともよろしくお願い申し上げます。\n\n—\n%4\$s\n%5\$s", 'ktpwp'),
                        $customer_name, $user_name, $project_name, $my_company, $my_email
                    );
                } elseif ($progress === 6) {
                    /* translators: Email subject for general communication. %s: project name. */
                    $subject = $project_name; // 件名は案件名のみ
                    /* translators: Email body for general communication. %1$s: customer name, %2$s: user name, %3$s: my company, %4$s: my email. */
                    $body = sprintf(
                        esc_html__("%1\$s\n%2\$s 様\n\nお世話になります。\n\n—\n%3\$s\n%4\$s", 'ktpwp'),
                        $customer_name, $user_name, $my_company, $my_email
                    );
                }


                $edit_subject = isset($_POST['edit_subject']) ? sanitize_text_field(wp_unslash($_POST['edit_subject'])) : $subject;
                $edit_body = isset($_POST['edit_body']) ? sanitize_textarea_field(wp_unslash($_POST['edit_body'])) : $body;

                if (isset($_POST['do_send_mail']) && $_POST['do_send_mail'] == '1') {
                    if (!isset($_POST['_wpnonce_send_order_mail']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_send_order_mail'])), 'ktp_send_order_mail_action_' . $current_order_id)) {
                        wp_die(esc_html__('Nonce verification failed for sending mail.', 'ktpwp'));
                    }
                    $headers = [];
                    if ($my_email) $headers[] = 'From: ' . $my_email;
                    $sent = wp_mail($to, $edit_subject, $edit_body, $headers);
                    if ($sent) {
                        add_action('admin_notices', function() use ($to) {
                            /* translators: %s: recipient email address. */
                            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('メールを送信しました。宛先: %s', 'ktpwp'), esc_html($to)) . '</p></div>';
                        });
                    } else {
                        add_action('admin_notices', function() {
                            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('メール送信に失敗しました。サーバー設定をご確認ください。', 'ktpwp') . '</p></div>';
                        });
                    }
                } else {
                    $mail_form_html = '<div id="order-mail-form" style="background:#fff;border:2px solid #2196f3;padding:24px;max-width:520px;margin:32px auto 16px auto;border-radius:8px;box-shadow:0 2px 12px #0002;z-index:9999;">';
                    /* translators: Title for the email editing form. */
                    $mail_form_html .= '<h3 style="margin-top:0;">' . esc_html__('メール送信内容の編集', 'ktpwp') . '</h3>';
                    $mail_form_html .= '<form method="post" action="">';
                    $mail_form_html .= wp_nonce_field('ktp_send_order_mail_action_' . $current_order_id, '_wpnonce_send_order_mail', true, false);
                    $mail_form_html .= '<input type="hidden" name="send_order_mail_id" value="' . esc_attr($current_order_id) . '">';
                    /* translators: Label for the recipient email address field. */
                    $mail_form_html .= '<div style="margin-bottom:12px;"><label>' . esc_html__('宛先：', 'ktpwp') . '</label><input type="email" value="' . esc_attr($to) . '" readonly style="width:320px;max-width:100%;background:#f5f5f5;"></div>';
                    /* translators: Label for the email subject field. */
                    $mail_form_html .= '<div style="margin-bottom:12px;"><label>' . esc_html__('件名：', 'ktpwp') . '</label><input type="text" name="edit_subject" value="' . esc_attr($edit_subject) . '" style="width:320px;max-width:100%;"></div>';
                    /* translators: Label for the email body field. */
                    $mail_form_html .= '<div style="margin-bottom:12px;"><label>' . esc_html__('本文：', 'ktpwp') . '</label><textarea name="edit_body" rows="10" style="width:100%;max-width:480px;">' . esc_textarea($edit_body) . '</textarea></div>';
                    /* translators: Button text for sending email. */
                    $mail_form_html .= '<button type="submit" name="do_send_mail" value="1" style="background:#2196f3;color:#fff;padding:8px 18px;border:none;border-radius:4px;font-size:15px;">' . esc_html__('送信', 'ktpwp') . '</button>';
                    /* translators: Button text for cancelling email sending. */
                    $mail_form_html .= '<button type="button" onclick="document.getElementById(\'order-mail-form\').style.display=\'none\';" style="margin-left:16px;padding:8px 18px;border:none;border-radius:4px;font-size:15px;">' . esc_html__('キャンセル', 'ktpwp') . '</button>';
                    $mail_form_html .= '</form>';
                    $mail_form_html .= '</div>';
                }
            }
        }
        return $mail_form_html; // Return the form HTML to be appended to $content
    }

    // Refactored: Project name update logic
    private function handle_project_name_update($table_name) {
        global $wpdb;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project_name_id'], $_POST['order_project_name'])) {
            $update_id = intval($_POST['update_project_name_id']);
            if (!isset($_POST['_wpnonce_update_project_name']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_update_project_name'])), 'ktp_update_project_name_action_' . $update_id)) {
                wp_die(esc_html__('Nonce verification failed for updating project name.', 'ktpwp'));
            }
            $project_name = sanitize_text_field($_POST['order_project_name']);
            if ($update_id > 0) {
                $wpdb->update($table_name, ['project_name' => $project_name], ['id' => $update_id]);
                $redirect_url = remove_query_arg(array('update_project_name_id', 'order_project_name', '_wpnonce_update_project_name'), esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
    }

    // Refactored: Progress update logic
    private function handle_progress_update($table_name) {
        global $wpdb;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress_id'], $_POST['update_progress'])) {
            $update_id = intval($_POST['update_progress_id']);
            if (!isset($_POST['_wpnonce_update_progress']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_update_progress'])), 'ktp_update_progress_action_' . $update_id)) {
                wp_die(esc_html__('Nonce verification failed for updating progress.', 'ktpwp'));
            }
            $update_progress = intval($_POST['update_progress']);
            if ($update_id > 0 && $update_progress >= 1 && $update_progress <= 6) {
                $wpdb->update($table_name, ['progress' => $update_progress], ['id' => $update_id]);
                $redirect_url = remove_query_arg(array('update_progress_id', 'update_progress', '_wpnonce_update_progress'), esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
    }

    // Refactored: Order deletion logic
    private function handle_order_deletion($table_name) {
        global $wpdb;
        if (isset($_POST['delete_order']) && $_POST['delete_order'] == '1' && isset($_POST['order_id'])) {
            $order_id_to_delete = intval($_POST['order_id']);
            if (!isset($_POST['_wpnonce_delete_order']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_delete_order'])), 'ktp_delete_order_action_' . $order_id_to_delete)) {
                wp_die(esc_html__('Nonce verification failed for deleting order.', 'ktpwp'));
            }

            if ($order_id_to_delete > 0) {
                $deleted = $wpdb->delete($table_name, array('id' => $order_id_to_delete));
                $current_page_url = remove_query_arg(array('delete_order', '_wpnonce_delete_order', 'order_id'), esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
                $latest_order_after_delete = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY time DESC LIMIT 1");
                $redirect_url = $latest_order_after_delete ? add_query_arg('order_id', $latest_order_after_delete->id, $current_page_url) : $current_page_url;
                
                if ($deleted) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('受注書を削除しました。', 'ktpwp') . '</p></div>';
                    });
                } else {
                     add_action('admin_notices', function() {
                        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('受注書の削除に失敗しました。', 'ktpwp') . '</p></div>';
                    });
                }
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
    }

    // Refactored: New order creation logic
    private function handle_new_order_creation($table_name, $client_table, &$order_id, &$content) {
        global $wpdb;
        $customer_name_get = isset($_GET['customer_name']) ? sanitize_text_field(wp_unslash($_GET['customer_name'])) : '';
        $user_name_get = isset($_GET['user_name']) ? sanitize_text_field(wp_unslash($_GET['user_name'])) : '';
        $from_client_get = isset($_GET['from_client']) ? intval($_GET['from_client']) : 0;

        if ($from_client_get === 1 && $customer_name_get !== '') {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start(); // エラーを抑制
            }

            $client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
            if ($client_id <= 0 && isset($_POST['client_id']) && intval($_POST['client_id']) > 0) {
                $client_id = intval($_POST['client_id']);
            }
            if ($client_id <= 0 && $customer_name_get !== '') {
                $client = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$client_table} WHERE company_name = %s AND name = %s",
                    $customer_name_get, $user_name_get
                ));
                if ($client) $client_id = $client->id;
            }

            $timestamp = time();
            $insert_data = array(
                'time' => $timestamp,
                'client_id' => $client_id > 0 ? $client_id : null, // 0または無効なIDの場合はNULL
                'customer_name' => $customer_name_get,
                'user_name' => $user_name_get,
                /* translators: Default project name for new orders. */
                'project_name' => esc_html__('※ 入力してください', 'ktpwp'),
                'invoice_items' => '',
                'cost_items' => '',
                'memo' => '',
                'search_field' => implode(', ', [$customer_name_get, $user_name_get]),
            );

            $inserted = $wpdb->insert($table_name, $insert_data);

            if ($inserted) {
                $new_order_id = $wpdb->insert_id;
                // from_clientフラグを削除し、order_idをセットしてリダイレクト
                $redirect_url = remove_query_arg('from_client', esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
                $redirect_url = add_query_arg('order_id', $new_order_id, $redirect_url);
                wp_safe_redirect($redirect_url);
                exit;
            } else {
                /* translators: Error message when order creation fails. */
                $content .= '<div class="notice notice-error"><p>' . esc_html__('受注書の作成に失敗しました。', 'ktpwp') . '</p></div>';
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('KTPWP Order Insert Error: ' . $wpdb->last_error); }
            }
        }
    }
    
    // Refactored: Display order details
    private function display_order_details(&$content, $order_data, $client_table, $mail_form_html) {
        global $wpdb;
        if (!$order_data) {
            /* translators: Error message shown when a specified order is not found. */
            $content .= '<div class="notice notice-warning"><p>' . esc_html__('指定された受注書は見つかりませんでした。', 'ktpwp') . '</p></div>';
            return;
        }

        // Preview and Print Buttons
        $client_id_text = !empty($order_data->client_id) ? sprintf(/* translators: %d: client ID */ esc_html__('（顧客ID: %d）', 'ktpwp'), $order_data->client_id) : '(' . esc_html__('顧客ID未設定', 'ktpwp') . ')';
        /* translators: Preview content. %1$s: Title (Order Preview), %2$s: Label (Order ID), %3$d: Order ID value, %4$s: Label (Company Name), %5$s: Company Name value, %6$s: Client ID text, %7$s: Label (Contact Name), %8$s: Contact Name value. */
        $preview_html = sprintf(
            '<div><strong>%1$s</strong><br>%2$s: %3$d<br>%4$s：%5$s %6$s<br>%7$s：%8$s</div>',
            esc_html__('伝票プレビュー', 'ktpwp'),
            esc_html__('受注書ID', 'ktpwp'),
            $order_data->id,
            esc_html__('会社名', 'ktpwp'),
            esc_html($order_data->customer_name),
            $client_id_text,
            esc_html__('担当者名', 'ktpwp'),
            esc_html($order_data->user_name)
        );
        $preview_html_json = json_encode($preview_html);

        $content .= '<script>';
        $content .= 'var isOrderPreviewOpen = false;';
        $content .= 'function printOrderContent() {';
        $content .= '    var printContent = ' . $preview_html_json . ';';
        $content .= '    var printWindow = window.open("", "_blank");';
        $content .= '    printWindow.document.open();';
        $content .= '    printWindow.document.write("<html><head><title>' . esc_js(__('印刷', 'ktpwp')) . '</title></head><body>");';
        $content .= '    printWindow.document.write(printContent);';
        $content .= '    printWindow.document.write("<script>window.onafterprint = function(){ window.close(); }<\/script>");'; // Corrected escaping for </script>
        $content .= '    printWindow.document.write("</body></html>");';
        $content .= '    printWindow.document.close();';
        $content .= '    printWindow.print();';
        $content .= '}';
        $content .= 'function toggleOrderPreview() {';
        $content .= '    var previewWindow = document.getElementById("orderPreviewWindow");';
        $content .= '    var previewButton = document.getElementById("orderPreviewButton");';
        $content .= '    if (isOrderPreviewOpen) {';
        $content .= '        previewWindow.style.display = "none";';
        /* translators: Screen reader text for preview button icon (closed state). */
        $content .= '        previewButton.innerHTML = "<span class=\"material-symbols-outlined\" aria-label=\"' . esc_js(__('プレビュー', 'ktpwp')) . '\">preview</span>";'; // Corrected escaping for quotes
        $content .= '        isOrderPreviewOpen = false;';
        $content .= '    } else {';
        $content .= '        var printContent = ' . $preview_html_json . ';';
        $content .= '        previewWindow.innerHTML = printContent;';
        $content .= '        previewWindow.style.display = "block";';
        /* translators: Screen reader text for preview button icon (open state). */
        $content .= '        previewButton.innerHTML = "<span class=\"material-symbols-outlined\" aria-label=\"' . esc_js(__('閉じる', 'ktpwp')) . '\">close</span>";'; // Corrected escaping for quotes
        $content .= '        isOrderPreviewOpen = true;';
        $content .= '    }';
        $content .= '}';
        $content .= '</script>';

        $content .= '<div class="controller">';
        $content .= '<div class="printer">';
        /* translators: Button title: Preview. */
        $content .= '<button id="orderPreviewButton" onclick="toggleOrderPreview()" title="' . esc_attr__('プレビュー', 'ktpwp') . '" style="padding: 8px 12px; font-size: 14px;">';
        /* translators: Screen reader text for preview button icon. */
        $content .= '<span class="material-symbols-outlined" aria-label="' . esc_attr__('プレビュー', 'ktpwp') . '">preview</span>';
        $content .= '</button>';
        /* translators: Button title: Print. */
        $content .= '<button onclick="printOrderContent()" title="' . esc_attr__('印刷する', 'ktpwp') . '" style="padding: 8px 12px; font-size: 14px;">';
        /* translators: Screen reader text for print button icon. */
        $content .= '<span class="material-symbols-outlined" aria-label="' . esc_attr__('印刷', 'ktpwp') . '">print</span>';
        $content .= '</button>';
        $content .= '<form id="orderMailForm" method="post" action="" style="display:inline;margin-top:0px;">';
        $content .= wp_nonce_field('ktp_send_order_mail_action_' . $order_data->id, '_wpnonce_send_order_mail', true, false);
        $content .= '<input type="hidden" name="send_order_mail_id" value="' . esc_attr($order_data->id) . '">';
        /* translators: Button title: Email. */
        $content .= '<button type="submit" id="orderMailButton" title="' . esc_attr__('メール', 'ktpwp') . '" style="padding: 8px 12px; font-size: 14px;">';
        /* translators: Screen reader text for email button icon. */
        $content .= '<span class="material-symbols-outlined" aria-label="' . esc_attr__('メール', 'ktpwp') . '">mail</span>';
        $content .= '</button>';
        $content .= '</form>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '<div id="orderPreviewWindow" style="display: none; border: 1px solid #ccc; padding: 10px; margin-top: 10px; background-color: #f9f9f9;"></div>';

        $content .= '<div class="workflow">';
        $content .= '<form method="post" action="" style="display:inline-block;margin-left:10px;" onsubmit="return confirm(\'' . esc_js(__('本当にこの受注書を削除しますか？\nこの操作は元に戻せません。', 'ktpwp')) . '\');">'; // Corrected escaping for confirm
        $content .= wp_nonce_field('ktp_delete_order_action_' . $order_data->id, '_wpnonce_delete_order', true, false);
        $content .= '<input type="hidden" name="order_id" value="' . esc_attr($order_data->id) . '">';
        $content .= '<input type="hidden" name="delete_order" value="1">';
        /* translators: Button text for deleting an order. */
        $content .= '<button type="submit" style="color:#fff;background:#d9534f;padding: 8px 12px;font-size:14px;border:none;border-radius:4px;cursor:pointer;">' . esc_html__('受注書を削除', 'ktpwp') . '</button>';
        $content .= '</form>';
        $content .= '</div>';

        if (!empty($mail_form_html)) {
            $content .= $mail_form_html;
        }

        $progress_labels = [
            1 => esc_html__('受付中', 'ktpwp'),
            2 => esc_html__('見積中', 'ktpwp'),
            3 => esc_html__('作成中', 'ktpwp'),
            4 => esc_html__('完成未請求', 'ktpwp'),
            5 => esc_html__('請求済', 'ktpwp'),
            6 => esc_html__('入金済', 'ktpwp')
        ];

        $content .= '<div class="order_contents">';
        $content .= '<div class="order_info_box box">';
        $content .= '<div class="order-header-flex order-header-inline-summary">';
        /* translators: %d: Order ID. */
        $content .= '<span class="order-header-title-id">' . sprintf(esc_html__('■ 受注書概要（ID: %d）', 'ktpwp'), $order_data->id)
            . '<input type="text" class="order_project_name_inline order-header-projectname" name="order_project_name_inline" value="' . esc_attr($order_data->project_name) . '" data-order-id="' . esc_attr($order_data->id) . '" placeholder="' . esc_attr__('案件名', 'ktpwp') . '" autocomplete="off" />'
            . '</span>';
        $content .= '<form method="post" action="" class="progress-filter order-header-progress-form" style="display:flex;align-items:center;gap:8px;flex-wrap:nowrap;margin-left:auto;">';
        $content .= wp_nonce_field('ktp_update_progress_action_' . $order_data->id, '_wpnonce_update_progress', true, false);
        $content .= '<input type="hidden" name="update_progress_id" value="' . esc_attr($order_data->id) . '" />';
        $content .= '<label for="order_progress_select_' . esc_attr($order_data->id) . '" style="white-space:nowrap;margin-right:4px;font-weight:bold;">' . esc_html__('進捗：', 'ktpwp') . '</label>'; // IDをユニークに
        $content .= '<select id="order_progress_select_' . esc_attr($order_data->id) . '" name="update_progress" onchange="this.form.submit()" style="min-width:120px;max-width:200px;width:auto;">';
        foreach ($progress_labels as $num => $label) {
            $selected = ($order_data->progress == $num) ? 'selected' : '';
            $content .= '<option value="' . esc_attr($num) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        $content .= '</select>';
        $content .= '</form>';
        $content .= '</div>';

        $client_id_display = '';
        if (!empty($order_data->client_id)) {
            $client_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$client_table} WHERE id = %d", $order_data->client_id));
            if ($client_exists) {
                /* translators: %d: Client ID. */
                $client_id_display = sprintf(esc_html__('（顧客ID: %d）', 'ktpwp'), $order_data->client_id);
            } else {
                /* translators: %d: Client ID. Indicates that the client data for this ID is missing. */
                $client_id_display = sprintf(esc_html__('（顧客ID: <span style="color:red;">%d - 顧客データが存在しません</span>）', 'ktpwp'), $order_data->client_id);
            }
        } else {
            $client_id_display = '(' . esc_html__('顧客ID未設定', 'ktpwp') . ')';
        }
        /* translators: %1$s: Customer name, %2$s: Client ID display string. */
        $content .= '<div>' . sprintf(esc_html__('会社名：%1$s %2$s', 'ktpwp'), esc_html($order_data->customer_name), '<span class="client-id" style="color:#666;font-size:0.9em;">' . $client_id_display . '</span>') . '</div>';

        $client_email = '';
        $client = null;
        if (!empty($order_data->client_id)) {
            $client = $wpdb->get_row($wpdb->prepare("SELECT email FROM {$client_table} WHERE id = %d", $order_data->client_id));
        }
        if (!$client && !empty($order_data->customer_name)) { // client_idがない場合や、IDで見つからなかった場合に名前で検索
            $client = $wpdb->get_row($wpdb->prepare("SELECT email FROM {$client_table} WHERE company_name = %s AND name = %s", $order_data->customer_name, $order_data->user_name));
        }

        /* translators: %s: User name. */
        $user_name_html = sprintf(esc_html__('担当者名：%s', 'ktpwp'), esc_html($order_data->user_name));
        if ($client && !empty($client->email)) {
            $client_email_attr = esc_attr($client->email);
            /* translators: Mailto link title. */
            $user_name_html .= ' <a href="mailto:' . $client_email_attr . '" style="margin-left:8px;vertical-align:middle;" title="' . esc_attr__('メール送信', 'ktpwp') . '">';
            $user_name_html .= '<span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;color:#2196f3;">mail</span>';
            $user_name_html .= '</a>';
        }
        $content .= '<div>' . $user_name_html . '</div>';
        
        $raw_time = $order_data->time;
        $formatted_time = '';
        if (!empty($raw_time) && is_numeric($raw_time)) {
            $unix_timestamp = (int)$raw_time;
            try {
                $dt = new DateTime('@' . $unix_timestamp);
                $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
                $locale = get_locale();
                if (strpos($locale, 'ja') === 0) {
                    $week = ['日','月','火','水','木','金','土'];
                    $w = (int)$dt->format('w');
                    $formatted_time = $dt->format('Y/n/j') . '（' . $week[$w] . '）' . $dt->format(' H:i');
                } else {
                    $formatted_time = $dt->format('Y-m-d l H:i'); // l は曜日
                }
            } catch (Exception $e) {
                $formatted_time = esc_html__('日付形式エラー', 'ktpwp');
                 if (defined('WP_DEBUG') && WP_DEBUG) { error_log('KTPWP DateTime Error: ' . $e->getMessage());}
            }
        }
        /* translators: %s: Formatted creation time. */
        $content .= '<div>' . sprintf(esc_html__('作成日時：%s', 'ktpwp'), esc_html($formatted_time)) . '</div>';
        $content .= '</div>'; // .order_info_box

        $content .= '<div class="order_invoice_box box">';
        /* translators: Section title for invoice items. */
        $content .= '<h4>■ ' . esc_html__('請求項目', 'ktpwp') . '</h4>';
        /* translators: Placeholder text indicating invoice items will be instructed later. */
        $content .= '<div>（' . esc_html__('後日指示', 'ktpwp') . '）</div>';
        $content .= '</div>';

        $content .= '<div class="order_cost_box box">';
        /* translators: Section title for cost items. */
        $content .= '<h4>■ ' . esc_html__('コスト項目', 'ktpwp') . '</h4>';
        /* translators: Placeholder text indicating cost items will be instructed later. */
        $content .= '<div>（' . esc_html__('後日指示', 'ktpwp') . '）</div>';
        $content .= '</div>';

        $content .= '<div class="order_memo_box box">';
        /* translators: Section title for memo items. */
        $content .= '<h4>■ ' . esc_html__('メモ項目', 'ktpwp') . '</h4>';
        /* translators: Placeholder text indicating memo content will be instructed later. */
        $content .= '<div>（' . esc_html__('後日指示', 'ktpwp') . '）</div>';
        $content .= '</div>';

        $content .= '</div>'; // .order_contents
    }


    function Order_Tab_View( $tab_name ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order';
        $client_table = $wpdb->prefix . 'ktp_client';
        $setting_table = $wpdb->prefix . 'ktp_setting';

        $content = '';
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order_data = null; 

        $this->handle_project_name_update($table_name);
        $this->handle_progress_update($table_name);
        $this->handle_order_deletion($table_name); 
        $this->handle_new_order_creation($table_name, $client_table, $order_id, $content);

        $this->Create_Order_Table();

        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

        if ($order_id === 0) {
            $latest_order = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY time DESC LIMIT 1");
            if ($latest_order) {
                $order_id = $latest_order->id;
            }
        }
        
        if ($order_id > 0) {
            $order_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $order_id));
        }

        $mail_form_html = '';
        if ($order_data) { 
             $mail_form_html = $this->handle_order_mail_sending($content, $order_id, $order_data, $client_table, $setting_table);
        }

        if ($order_data) {
            $this->display_order_details($content, $order_data, $client_table, $mail_form_html);
        } else {
            $content .= '<div class="controller">';
            $content .= '    <div class="printer">';
            /* translators: Button title: Preview (disabled). */
            $content .= '        <button id="orderPreviewButton" disabled title="' . esc_attr__('プレビュー', 'ktpwp') . '">';
            /* translators: Screen reader text for preview button icon (disabled). */
            $content .= '            <span class="material-symbols-outlined" aria-label="' . esc_attr__('プレビュー', 'ktpwp') . '">preview</span>';
            $content .= '        </button>';
            /* translators: Button title: Print (disabled). */
            $content .= '        <button disabled title="' . esc_attr__('印刷する', 'ktpwp') . '">';
            /* translators: Screen reader text for print button icon (disabled). */
            $content .= '            <span class="material-symbols-outlined" aria-label="' . esc_attr__('印刷', 'ktpwp') . '">print</span>';
            $content .= '        </button>';
            /* translators: Button title: Email (disabled). */
            $content .= '        <button disabled title="' . esc_attr__('メール', 'ktpwp') . '">';
            /* translators: Screen reader text for email button icon (disabled). */
            $content .= '            <span class="material-symbols-outlined" aria-label="' . esc_attr__('メール', 'ktpwp') . '">mail</span>';
            $content .= '        </button>';
            $content .= '    </div>';
            $content .= '</div>';
            $content .= '<div id="orderPreviewWindow" style="display: none;"></div>'; // プレビューウィンドウのコンテナは常に用意
            $content .= '<div class="workflow"></div>'; // workflowコンテナも用意

            if ($order_id > 0 && !$order_data) { 
                 /* translators: Error message shown when a specified order is not found. */
                $content .= '<div class="notice notice-warning"><p>' . esc_html__('指定された受注書は見つかりませんでした。', 'ktpwp') . '</p></div>';
            } else { // ID指定がなく、最新の受注もなかった場合
                /* translators: Message shown when there are no orders to display. */
                $content .= '<p>' . esc_html__('表示する受注書がありません。', 'ktpwp') . '</p>';
            }
        }
        
        // ページネーションは現状維持（表示はしない）
        // ... existing pagination logic ...

        return $content;
    }

}
} // class_exists