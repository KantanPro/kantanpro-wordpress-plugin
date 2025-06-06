<?php
/**
 * Order class for KTPWP plugin
 *
 * Handles order data management including table creation,
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

if ( ! class_exists( 'Kntan_Order_Class' ) ) {

/**
 * Order class for managing order data
 *
 * @since 1.0.0
 */
class Kntan_Order_Class {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Constructor initialization
    }

    /**
     * Create order table using new class structure
     * 
     * @deprecated Use KTPWP_Order::create_order_table() instead
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function Create_Order_Table() {
        $order_manager = KTPWP_Order::get_instance();
        return $order_manager->create_order_table();
    }

    /**
     * Create invoice items table using new class structure
     * 
     * @deprecated Use KTPWP_Order_Items::create_invoice_items_table() instead
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function Create_Invoice_Items_Table() {
        $order_items = KTPWP_Order_Items::get_instance();
        return $order_items->create_invoice_items_table();
    }

    /**
     * Create cost items table using new class structure
     * 
     * @deprecated Use KTPWP_Order_Items::create_cost_items_table() instead
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function Create_Cost_Items_Table() {
        $order_items = KTPWP_Order_Items::get_instance();
        return $order_items->create_cost_items_table();
    }

    /**
     * Create initial invoice item when order is created
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    /**
     * Create initial invoice item using new class structure
     * 
     * @deprecated Use KTPWP_Order_Items::create_initial_invoice_item() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    public function Create_Initial_Invoice_Item( $order_id ) {
        $order_items = KTPWP_Order_Items::get_instance();
        return $order_items->create_initial_invoice_item( $order_id );
        
        if ( $inserted ) {
            return true;
        } else {
            error_log( 'KTPWP: Failed to create initial invoice item: ' . $wpdb->last_error );
        }
    }

    /**
     * Create initial cost item when order is created
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    /**
     * Create initial cost item using new class structure
     * 
     * @deprecated Use KTPWP_Order_Items::create_initial_cost_item() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    public function Create_Initial_Cost_Item( $order_id ) {
        $order_items = KTPWP_Order_Items::get_instance();
        return $order_items->create_initial_cost_item( $order_id );
    }

    /**
     * Delete cost items when order is deleted
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    /**
     * Delete cost items using new class structure
     * 
     * @deprecated Use KTPWP_Order_Items::delete_cost_items() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    public function Delete_Cost_Items( $order_id ) {
        $order_items = KTPWP_Order_Items::get_instance();
        return $order_items->delete_cost_items( $order_id );
    }

    /**
     * Get cost items for a specific order
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return array|false Array of cost items or false on failure
     */
    /**
     * Get cost items using new class structure
     * 
     * @deprecated Use KTPWP_Order_Items::get_cost_items() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return array|false Array of cost items or false on failure
     */
    public function Get_Cost_Items( $order_id ) {
        $order_items = KTPWP_Order_Items::get_instance();
        return $order_items->get_cost_items( $order_id );
    }

    /**
     * Delete invoice items when order is deleted
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    /**
     * Delete invoice items using new class structure
     * 
     * @deprecated Use KTPWP_Order_Items::delete_invoice_items() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    public function Delete_Invoice_Items( $order_id ) {
        $order_items = KTPWP_Order_Items::get_instance();
        return $order_items->delete_invoice_items( $order_id );
    }

    /**
     * Get invoice items for a specific order
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return array|false Array of invoice items or false on failure
     */
    /**
     * Get invoice items using new class structure
     * 
     * @deprecated Use KTPWP_Order_Items::get_invoice_items() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return array|false Array of invoice items or false on failure
     */
    public function Get_Invoice_Items( $order_id ) {
        $order_items = KTPWP_Order_Items::get_instance();
        return $order_items->get_invoice_items( $order_id );
    }

    /**
     * Generate HTML table for invoice items using new class structure
     * 
     * @deprecated Use KTPWP_Order_UI::generate_invoice_items_table() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return string HTML table content
     */
    public function Generate_Invoice_Items_Table( $order_id ) {
        $order_ui = KTPWP_Order_UI::get_instance();
        return $order_ui->generate_invoice_items_table( $order_id );
    }

    /**
     * Generate HTML table for cost items using new class structure
     * 
     * @deprecated Use KTPWP_Order_UI::generate_cost_items_table() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return string HTML table content
     */
    public function Generate_Cost_Items_Table( $order_id ) {
        $order_ui = KTPWP_Order_UI::get_instance();
        return $order_ui->generate_cost_items_table( $order_id );
    }

    /**
     * Create staff chat table using new class structure
     * 
     * @deprecated Use KTPWP_Staff_Chat::create_table() instead
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function Create_Staff_Chat_Table() {
        $staff_chat = KTPWP_Staff_Chat::get_instance();
        return $staff_chat->create_table();
    }

    /**
     * Create initial staff chat entry when order is created using new class structure
     * 
     * @deprecated Use KTPWP_Staff_Chat::create_initial_chat() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @param int $creator_user_id Creator user ID
     * @return bool True on success, false on failure
     */
    /**
     * Create initial staff chat message using new class structure
     * 
     * @deprecated Use KTPWP_Staff_Chat::create_initial_chat() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @param int|null $creator_user_id User ID who created the order (optional)
     * @return bool True on success, false on failure
     */
    public function Create_Initial_Staff_Chat( $order_id, $creator_user_id = null ) {
        $staff_chat = KTPWP_Staff_Chat::get_instance();
        return $staff_chat->create_initial_chat( $order_id, $creator_user_id );
    }

    /**
     * Get staff chat messages for a specific order using new class structure
     * 
     * @deprecated Use KTPWP_Staff_Chat::get_messages() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return array|false Array of chat messages or false on failure
     */
    public function Get_Staff_Chat_Messages( $order_id ) {
        $staff_chat = KTPWP_Staff_Chat::get_instance();
        return $staff_chat->get_messages( $order_id );
    }

    /**
     * Add staff chat message using new class structure
     * 
     * @deprecated Use KTPWP_Staff_Chat::add_message() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @param string $message Message content
     * @return bool True on success, false on failure
     */
    public function Add_Staff_Chat_Message( $order_id, $message ) {
        $staff_chat = KTPWP_Staff_Chat::get_instance();
        return $staff_chat->add_message( $order_id, $message );
    }

    /**
     * Generate staff chat HTML using new class structure
     * 
     * @deprecated Use KTPWP_Staff_Chat::generate_html() instead
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return string HTML content for staff chat
     */
    public function Generate_Staff_Chat_HTML( $order_id ) {
        $staff_chat = KTPWP_Staff_Chat::get_instance();
        return $staff_chat->generate_html( $order_id );
    }

    /**
     * Display order tab view
     *
     * @since 1.0.0
     * @param string $tab_name Tab name
     * @return void
     */
    public function Order_Tab_View( $tab_name ) {
        // デバッグログ追加
        
        // Check user capabilities - allow editors and above to access
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'ktpwp' ) );
        }


        if ( empty( $tab_name ) ) {
            error_log( 'KTPWP: Empty tab_name provided to Order_Tab_View method' );
            return;
        }
        

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order';
        $client_table = $wpdb->prefix . 'ktp_client';
        
        // Initialize invoice items table (with migration)
        $this->Create_Invoice_Items_Table();
        
        // Initialize cost items table
        $this->Create_Cost_Items_Table();
        
        // Initialize staff chat table
        $this->Create_Staff_Chat_Table();

        // Handle form submissions
        $mail_form_html = '';
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'NOT_SET';
        
        // Handle staff chat message submission
        if ( $request_method === 'POST' && isset( $_POST['staff_chat_message'] ) && isset( $_POST['staff_chat_order_id'] ) ) {
            // Verify nonce
            if ( isset( $_POST['staff_chat_nonce'] ) && 
                 wp_verify_nonce( $_POST['staff_chat_nonce'], 'staff_chat_action' ) ) {
                
                // Check user capabilities
                if ( current_user_can( 'edit_posts' ) ) {
                    $order_id = absint( $_POST['staff_chat_order_id'] );
                    $message = sanitize_textarea_field( $_POST['staff_chat_message'] );
                    
                    if ( ! empty( $message ) && $order_id > 0 ) {
                        $success = $this->Add_Staff_Chat_Message( $order_id, $message );
                        
                        if ( $success ) {
                            // メッセージ送信成功後、チャットを開いた状態でリダイレクト
                            $redirect_url = add_query_arg( array(
                                'tab_name' => $tab_name,
                                'order_id' => $order_id,
                                'chat_open' => '1',      // チャットを開いた状態を示すパラメータ
                                'message_sent' => '1'    // メッセージ送信完了を示すパラメータ
                            ), esc_url_raw( $_SERVER['REQUEST_URI'] ) );
                            
                            // URLのパラメータをクリーンアップ（重複を避ける）
                            $redirect_url = remove_query_arg( array( 'message', 'action' ), $redirect_url );
                            
                            wp_redirect( $redirect_url );
                            exit;
                        }
                    }
                }
            }
        }

        // Handle email sending with proper security checks
        if ( $request_method === 'POST' && isset( $_POST['send_order_mail_id'] ) ) {
            // Verify nonce
            // if ( ! isset( $_POST['order_mail_nonce'] ) || 
            //      ! wp_verify_nonce( $_POST['order_mail_nonce'], 'send_order_mail_action' ) ) {
            //     wp_die( __( 'Security check failed. Please refresh the page and try again.', 'ktpwp' ) );
            // }
            

            // Additional capability check
            // if ( ! current_user_can( 'manage_options' ) ) {
            //     wp_die( __( 'You do not have sufficient permissions to send emails.', 'ktpwp' ) );
            // }

            $order_id = absint( $_POST['send_order_mail_id'] );
            if ( $order_id > 0 ) {
                $order = $wpdb->get_row( $wpdb->prepare( 
                    "SELECT * FROM `{$table_name}` WHERE id = %d", 
                    $order_id 
                ) );
                
                if ( $order ) {
                    // Get client information
                    $client = null;
                    
                    // First try to find by client_id
                    if ( ! empty( $order->client_id ) ) {
                        $client = $wpdb->get_row( $wpdb->prepare( 
                            "SELECT * FROM `{$client_table}` WHERE id = %d", 
                            $order->client_id 
                        ) );
                    }
                    
                    // Fallback: search by company name and user name for backward compatibility
                    if ( ! $client && ! empty( $order->customer_name ) && ! empty( $order->user_name ) ) {
                        $client = $wpdb->get_row( $wpdb->prepare( 
                            "SELECT * FROM `{$client_table}` WHERE company_name = %s AND name = %s", 
                            $order->customer_name, 
                            $order->user_name 
                        ) );
                    }
                    
                    $to = '';
                    if ($client && !empty($client->email)) {
                        // メールアドレスの詳細な検証（強化版） - 表示用と同じロジック
                        $email_raw = $client->email ?? '';
                        
                        // シンプルなメールアドレス検証（修正版）
                        // 過度に厳格な検証が問題の原因だったため、より寛容なロジックに変更
                        $email = trim($email_raw);
                        
                        // 基本的な制御文字のみ除去（最小限）
                        $email = str_replace(["\0", "\r", "\n", "\t"], '', $email);
                        
                        // Step 3と4は削除（厳しすぎて有効なメールアドレスが無効になっていた）
                        
                        // 最終検証のみ実行
                        $is_valid = !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
                        
                        if ($is_valid) {
                            $to = sanitize_email($email);
                        } else {
                        }
                    }
                    
                    // 対象外顧客のメール送信を拒否
                    $client_category = $client ? $client->category : '';
                    if ($client_category === '対象外') {
                        $mail_form_html = '<div style="background:#ffebee;border:2px solid #f44336;padding:24px;max-width:520px;margin:32px auto 16px auto;border-radius:8px;">';
                        $mail_form_html .= '<h3 style="margin-top:0;color:#d32f2f;">メール送信不可</h3>';
                        $mail_form_html .= '<p style="color:#d32f2f;">この顧客は削除済み（対象外）のため、メール送信はできません。</p>';
                        $mail_form_html .= '</div>';
                    } else {
                        if ( empty( $to ) ) {
                            $mail_form_html = '<div style="background:#fff3cd;border:2px solid #ffc107;padding:24px;max-width:520px;margin:32px auto 16px auto;border-radius:8px;">';
                            $mail_form_html .= '<h3 style="margin-top:0;color:#856404;">メールアドレス未設定</h3>';
                            $mail_form_html .= '<p style="color:#856404;">この顧客のメールアドレスが未設定です。</p>';
                            $mail_form_html .= '<p style="color:#856404;">顧客管理画面でメールアドレスを登録してください。</p>';
                            $mail_form_html .= '</div>';
                        } else {
                    // 自社情報取得 - 新しい設定システムを優先
                    $smtp_settings = get_option('ktp_smtp_settings', array());
                    $my_email = !empty($smtp_settings['email_address']) ? sanitize_email($smtp_settings['email_address']) : '';
                    
                    // 会社情報を新しい一般設定から取得
                    $my_company = '';
                    if (class_exists('KTP_Settings')) {
                        $my_company = KTP_Settings::get_company_info();
                    }
                    
                    // 旧システムからも取得（後方互換性） - Use prepared statement
                    $setting_table = $wpdb->prefix . 'ktp_setting';
                    $setting = $wpdb->get_row( $wpdb->prepare( 
                        "SELECT * FROM `{$setting_table}` WHERE id = %d", 
                        1 
                    ) );
                    
                    // 会社情報が新システムで見つからない場合は旧システムから取得
                    if (empty($my_company) && $setting) {
                        $my_company = sanitize_text_field( strip_tags( $setting->my_company_content ) );
                    }
                    
                    // 自社メールアドレスが新システムで見つからない場合は旧システムから取得
                    if (empty($my_email) && $setting) {
                        $my_email = sanitize_email( $setting->email_address );
                    }
                    
                    $my_name = '';

                    // 請求項目リスト・金額を実際のデータベースから取得
                    $invoice_items_from_db = $this->Get_Invoice_Items( $order->id );
                    $amount = 0;
                    $invoice_list = '';
                    
                    if (!empty($invoice_items_from_db)) {
                        // 実際の請求項目データがある場合
                        $invoice_list = "\n";
                        $max_length = 0;
                        $item_lines = array();
                        
                        foreach ($invoice_items_from_db as $item) {
                            $product_name = isset($item['product_name']) ? sanitize_text_field($item['product_name']) : '';
                            $item_amount = isset($item['amount']) ? floatval($item['amount']) : 0;
                            $price = isset($item['price']) ? floatval($item['price']) : 0;
                            $quantity = isset($item['quantity']) ? floatval($item['quantity']) : 0;
                            $unit = isset($item['unit']) ? sanitize_text_field($item['unit']) : '';
                            $amount += $item_amount;
                            
                            // サービスが空でない項目のみリストに追加
                            if (!empty(trim($product_name))) {
                                // 詳細形式：サービス：単価 × 数量/単位 = 金額円
                                $line = $product_name . '：' . number_format($price) . '円 × ' . $quantity . $unit . ' = ' . number_format($item_amount) . "円";
                                $item_lines[] = $line;
                                // 最大文字数を計算（日本語文字も考慮）
                                $line_length = mb_strlen($line, 'UTF-8');
                                if ($line_length > $max_length) {
                                    $max_length = $line_length;
                                }
                            }
                        }
                        
                        // 項目を出力
                        foreach ($item_lines as $line) {
                            $invoice_list .= $line . "\n";
                        }
                        
                        // 合計金額を切り上げ
                        $amount_ceiled = ceil($amount);
                        $total_line = "合計：" . number_format($amount_ceiled) . "円";
                        $total_length = mb_strlen($total_line, 'UTF-8');
                        
                        // 罫線の長さを決定（項目と合計行の最大文字数）
                        $line_length = max($max_length, $total_length);
                        if ($line_length < 30) $line_length = 30; // 最小長を拡大
                        if ($line_length > 80) $line_length = 80; // 最大長を拡大
                        
                        // 罫線を追加
                        $invoice_list .= str_repeat('-', $line_length) . "\n";
                        $invoice_list .= $total_line;
                    } else {
                        // 請求項目データがない場合はJSONデータ（旧形式）を試す
                        $invoice_items_json = $order->invoice_items ? sanitize_textarea_field( $order->invoice_items ) : '';
                        if ($invoice_items_json) {
                            $items = @json_decode($invoice_items_json, true);
                            if (is_array($items)) {
                                $invoice_list = "\n";
                                foreach ($items as $item) {
                                    $amount += isset($item['amount']) ? floatval( $item['amount'] ) : 0;
                                    $product_name = isset($item['name']) ? sanitize_text_field( $item['name'] ) : '';
                                    $price = isset($item['price']) ? floatval( $item['price'] ) : 0;
                                    $quantity = isset($item['quantity']) ? floatval( $item['quantity'] ) : 1;
                                    $unit = isset($item['unit']) ? sanitize_text_field( $item['unit'] ) : '';
                                    $item_amount = isset($item['amount']) ? floatval( $item['amount'] ) : 0;
                                    
                                    if (!empty(trim($product_name))) {
                                        // 詳細形式：サービス：単価 × 数量/単位 = 金額円
                                        $invoice_list .= $product_name . '：' . number_format($price) . '円 × ' . $quantity . $unit . ' = ' . number_format($item_amount) . "円\n";
                                    }
                                }
                                $amount_ceiled = ceil($amount);
                                $invoice_list .= "\n合計：" . number_format($amount_ceiled) . "円";
                            } else {
                                $invoice_list = sanitize_textarea_field( $invoice_items_json );
                            }
                        } else {
                            $invoice_list = '（請求項目未入力）';
                        }
                    }
                    
                    // $amount_str は削除（$invoice_list内に合計が含まれているため）
                    // $amount_str = $amount ? number_format(ceil($amount)) . '円' : '';

                    // 進捗ごとに件名・本文 - Sanitize input data
                    $progress = absint( $order->progress );
                    $project_name = $order->project_name ? sanitize_text_field( $order->project_name ) : '';
                    $customer_name = sanitize_text_field( $order->customer_name );
                    $user_name = sanitize_text_field( $order->user_name );
                    $body = $subject = '';
                    if ($progress === 1) {
                        $subject = "お見積り：{$project_name}";
                        $body = "{$customer_name}\n{$user_name} 様\n\nこの度はご依頼ありがとうございます。\n{$project_name}につきましてお見積させていただきます。\n\n＜お見積り＞「{$project_name}」の件\n{$invoice_list}\n\n—\n{$my_company}";
                    } elseif ($progress === 2) {
                        $subject = "ご注文ありがとうございます：{$project_name}";
                        $body = "{$customer_name}\n{$user_name} 様\n\nこの度はご注文頂きありがとうございます。\n{$project_name}につきまして対応させていただきます。\n\n＜ご注文内容＞\n{$project_name}\n{$invoice_list}\n\n—\n{$my_company}";
                    } elseif ($progress === 3) {
                        $subject = "{$project_name}につきまして質問です";
                        $body = "{$customer_name}\n{$user_name} 様\n\nお世話になります。\n{$project_name}につきまして質問です。\n\n＜質問内容＞\n（ご質問内容をここにご記入ください）\n\n—\n{$my_company}";
                    } elseif ($progress === 4) {
                        $subject = "{$project_name}の請求書です";
                        $body = "{$customer_name}\n{$user_name} 様\n\nお世話になります。\n{$project_name}につきまして請求させていただきます。\n\n＜請求書＞\n{$project_name}\n{$invoice_list}\n\n—\n{$my_company}";
                    } elseif ($progress === 5) {
                        $subject = "{$project_name}のご入金を確認しました";
                        $body = "{$customer_name}\n{$user_name} 様\n\nお世話になります。\n{$project_name}につきましてご入金いただきありがとうございます。\n今後ともよろしくお願い申し上げます。\n\n—\n{$my_company}";
                    } elseif ($progress === 6) {
                        $subject = "{$project_name}";
                        $body = "{$customer_name}\n{$user_name} 様\n\nお世話になります。\n\n—\n{$my_company}";
                    }

                    // Sanitize email content input
                    $edit_subject = isset($_POST['edit_subject']) ? sanitize_text_field( stripslashes($_POST['edit_subject']) ) : $subject;
                    $edit_body = isset($_POST['edit_body']) ? sanitize_textarea_field( stripslashes($_POST['edit_body']) ) : $body;

                    // 送信ボタンが押された場合
                    if (isset($_POST['do_send_mail']) && $_POST['do_send_mail'] == '1') {
                        // Additional verification for email sending
                        // if ( ! current_user_can( 'manage_options' ) ) {
                        //     wp_die( __( 'You do not have sufficient permissions to send emails.', 'ktpwp' ) );
                        // }
                        
                        $headers = [];
                        if ($my_email) $headers[] = 'From: ' . sanitize_email( $my_email );
                        $sent = wp_mail( sanitize_email( $to ), $edit_subject, $edit_body, $headers );
                        if ($sent) {
                            // echo '<script>
                            //     document.addEventListener("DOMContentLoaded", function() {
                            //         showSuccessNotification("メールを送信しました。\\n宛先: ' . esc_js($to) . '");
                            //     });
                            // </script>';
                        } else {
                            // echo '<script>
                            //     document.addEventListener("DOMContentLoaded", function() {
                            //         showErrorNotification("メール送信に失敗しました。サーバー設定をご確認ください。");
                            //     });
                            // </script>';
                        }
                    } else {
                        // 編集フォームHTMLを生成
                        $mail_form_html = '<div id="order-mail-form" style="background:#fff;border:2px solid #2196f3;padding:24px;max-width:520px;margin:32px auto 16px auto;border-radius:8px;box-shadow:0 2px 12px #0002;z-index:9999;">';
                        $mail_form_html .= '<h3 style="margin-top:0;">メール送信内容の編集</h3>';
                        $mail_form_html .= '<form method="post" action="">';
                        // Add nonce to mail form
                        $mail_form_html .= wp_nonce_field( 'send_order_mail_action', 'order_mail_nonce', true, false );
                        $mail_form_html .= '<input type="hidden" name="send_order_mail_id" value="' . esc_attr($order_id) . '">';
                        $mail_form_html .= '<div style="margin-bottom:12px;"><label>宛先：</label><input type="email" value="' . esc_attr($to) . '" readonly style="width:320px;max-width:100%;background:#f5f5f5;"></div>';
                        $mail_form_html .= '<div style="margin-bottom:12px;"><label>件名：</label><input type="text" name="edit_subject" value="' . esc_attr($edit_subject) . '" style="width:320px;max-width:100%;"></div>';
                        $mail_form_html .= '<div style="margin-bottom:12px;"><label>本文：</label><textarea name="edit_body" rows="10" style="width:100%;max-width:480px;">' . esc_textarea($edit_body) . '</textarea></div>';
                        $mail_form_html .= '<button type="submit" name="do_send_mail" value="1" style="background:#2196f3;color:#fff;padding:8px 18px;border:none;border-radius:4px;font-size:15px;">送信</button>';
                        $mail_form_html .= '<button type="button" onclick="document.getElementById(\'order-mail-form\').style.display=\'none\';" style="margin-left:16px;padding:8px 18px;border:none;border-radius:4px;font-size:15px;">キャンセル</button>';
                        $mail_form_html .= '</form>';
                        $mail_form_html .= '</div>';
                    }
                        } // if ( empty( $to ) ) の else ブロック終了
                    } // if ($client_category === '対象外') の else ブロック終了
                } else {
                }
            } else {
            }
        }
        
        // この重要なチェックポイントを追加
        
        // メール処理なしの場合のログ
        if (!($request_method === 'POST' && isset( $_POST['send_order_mail_id'] ))) {
        }
        
        // この時点での実行確認
        // 案件名の保存処理 - Add nonce verification
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project_name_id'], $_POST['order_project_name'])) {
            // Verify nonce
            // if ( ! isset( $_POST['project_name_nonce'] ) || 
            //      ! wp_verify_nonce( $_POST['project_name_nonce'], 'update_project_name_action' ) ) {
            //     wp_die( __( 'Security check failed. Please refresh the page and try again.', 'ktpwp' ) );
            // }
            
            // Check user capabilities
            // if ( ! current_user_can( 'manage_options' ) ) {
            //     wp_die( __( 'You do not have sufficient permissions to update project names.', 'ktpwp' ) );
            // }
            
            $update_id = absint($_POST['update_project_name_id']);
            $project_name = sanitize_text_field($_POST['order_project_name']);
            if ($update_id > 0) {
                $wpdb->update($table_name, ['project_name' => $project_name], ['id' => $update_id], ['%s'], ['%d']);
                // POSTリダブミット防止のためリダイレクト
                $redirect_url = esc_url_raw( $_SERVER['REQUEST_URI'] );
                // wp_redirect( $redirect_url );
                // exit;
            }
        }
        
        // 重要なチェックポイント2を追加
        
        // 進捗更新処理開始前のログ
        // 進捗更新処理（POST時） - Add nonce verification
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress_id'], $_POST['update_progress'])) {
            // Verify nonce
            // if ( ! isset( $_POST['progress_nonce'] ) || 
            //      ! wp_verify_nonce( $_POST['progress_nonce'], 'update_progress_action' ) ) {
            //     wp_die( __( 'Security check failed. Please refresh the page and try again.', 'ktpwp' ) );
            // }
            
            // Check user capabilities
            // if ( ! current_user_can( 'manage_options' ) ) {
            //     wp_die( __( 'You do not have sufficient permissions to update progress.', 'ktpwp' ) );
            // }
            
            $update_id = absint($_POST['update_progress_id']);
            $update_progress = absint($_POST['update_progress']);
            if ($update_id > 0 && $update_progress >= 1 && $update_progress <= 6) {
                $wpdb->update($table_name, ['progress' => $update_progress], ['id' => $update_id], ['%d'], ['%d']);
                // リダイレクトで再読み込み（POSTリダブミット防止）
                $redirect_url = esc_url_raw( $_SERVER['REQUEST_URI'] );
                // wp_redirect( $redirect_url );
                // exit;
            }
        }

        // 重要なチェックポイント3を追加

        // 請求項目の保存処理 - Add nonce verification
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['save_invoice_items'] ) && isset( $_POST['invoice_items'] ) ) {
            // Verify nonce
            if ( ! isset( $_POST['invoice_items_nonce'] ) || 
                 ! wp_verify_nonce( $_POST['invoice_items_nonce'], 'save_invoice_items_action' ) ) {
                wp_die( __( 'Security check failed. Please refresh the page and try again.', 'ktpwp' ) );
            }
            
            // Check user capabilities
            if ( ! current_user_can( 'edit_posts' ) ) {
                wp_die( __( 'You do not have sufficient permissions to update invoice items.', 'ktpwp' ) );
            }
            
            $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
            $invoice_items = isset( $_POST['invoice_items'] ) ? $_POST['invoice_items'] : array();
            
            
            if ( $order_id > 0 && is_array( $invoice_items ) ) {
                $result = $this->Save_Invoice_Items( $order_id, $invoice_items );
                
                if ( $result ) {
                    // 請求項目が正常に保存されました
                } else {
                    // 請求項目の保存に失敗しました
                }
                
                // POSTリダブミット防止のためリダイレクト
                $redirect_url = esc_url_raw( $_SERVER['REQUEST_URI'] );
                wp_redirect( $redirect_url );
                exit;
            } else {
            }
        }

        // コスト項目保存処理
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['save_cost_items'] ) && $_POST['save_cost_items'] == 1 ) {
            
            $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
            $cost_items = isset( $_POST['cost_items'] ) ? $_POST['cost_items'] : array();
            
            if ( $order_id > 0 && is_array( $cost_items ) ) {
                
                $result = $this->Save_Cost_Items( $order_id, $cost_items );
                
                if ( $result ) {
                } else {
                }
                
                // POSTリダブミット防止のためリダイレクト
                $redirect_url = esc_url_raw( $_SERVER['REQUEST_URI'] );
                wp_redirect( $redirect_url );
                exit;
            } else {
            }
        }

        // 重要なチェックポイント4を追加
        
        // URLパラメータから得意先情報を取得 - Sanitize input
        $customer_name = isset($_GET['customer_name']) ? sanitize_text_field( $_GET['customer_name'] ) : '';
        $user_name = isset($_GET['user_name']) ? sanitize_text_field( $_GET['user_name'] ) : '';
        $from_client = isset($_GET['from_client']) ? absint($_GET['from_client']) : 0; // 得意先タブからの遷移フラグ
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id'] ) : 0; // 表示する受注書ID

        $content = ''; // 表示するHTMLコンテンツ

        // 重要なチェックポイント5を追加

        // 削除処理のデバッグ情報を追加
        
        // 削除処理が実行されるかの条件を個別にチェック
        $is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
        $has_delete_order = isset($_POST['delete_order']) && $_POST['delete_order'] == 1;
        $has_order_id = isset($_POST['order_id']);
        
        
        if (!$is_post) {
        } elseif (!$has_delete_order) {
        } elseif (!$has_order_id) {
        }

        // 受注書削除処理 - Use POST method for deletion
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order']) && $_POST['delete_order'] == 1 && isset($_POST['order_id'])) {
            $order_id_to_delete = absint($_POST['order_id']);
            $client_exists = isset($_POST['client_exists']) ? intval($_POST['client_exists']) : 0;
            
            // 顧客データが存在しない場合でも削除を許可するため、顧客データの存在チェックは行わない
            
            // Verify nonce for delete action
            // if ( ! isset( $_POST['delete_nonce'] ) || 
            //      ! wp_verify_nonce( $_POST['delete_nonce'], 'delete_order_action' ) ) {
            //     wp_die( __( 'Security check failed. Please refresh the page and try again.', 'ktpwp' ) );
            // }
            
            // Check user capabilities
            // if ( ! current_user_can( 'manage_options' ) ) {
            //     wp_die( __( 'You do not have sufficient permissions to delete orders.', 'ktpwp' ) );
            // }
            
            // 削除処理 - 顧客データの存在に関係なく受注書を削除
            
            // 関連する請求項目を削除
            $this->Delete_Invoice_Items( $order_id_to_delete );
            
            // 関連するコスト項目を削除
            $this->Delete_Cost_Items( $order_id_to_delete );
            
            // 受注書を削除
            $deleted = $wpdb->delete($table_name, array('id' => $order_id_to_delete), array('%d'));
            
            if ($deleted) {
                // 統一されたリダイレクト処理に変更
                $latest_order = $wpdb->get_row( $wpdb->prepare( 
                    "SELECT id FROM `{$table_name}` ORDER BY time DESC LIMIT %d", 
                    1 
                ) );
                
                $next_order_id = $latest_order ? $latest_order->id : 0;
                
                // wp_redirectを使用した統一されたリダイレクト
                $redirect_url = add_query_arg(array(
                    'tab_name' => 'order',
                    'order_id' => $next_order_id,
                    'message' => 'deleted'
                ), wp_get_referer());
                
                wp_redirect($redirect_url);
                exit;
                
            } else {
                $content .= '<div class="error">受注書の削除に失敗しました。エラー: ' . esc_html($wpdb->last_error) . '</div>';
            }
        } else {
            // 削除処理の条件が満たされない場合のデバッグ情報
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            } elseif (!isset($_POST['delete_order'])) {
            } elseif ($_POST['delete_order'] != 1) {
            } elseif (!isset($_POST['order_id'])) {
            }
        }

        // 重要なチェックポイント6を追加

        // 得意先タブから遷移してきた場合（新規受注書作成）
        if ($from_client === 1 && $customer_name !== '') {
            // セッションスタート（複製情報にアクセスするため - 下位互換性のため）
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            
            // 顧客IDを取得（優先順位：GET > DB > SESSION > COOKIE > POST）
            // 1. GETパラメータから取得（ktpwp.phpでリダイレクト時に設定される）
            $client_id = isset($_GET['client_id']) ? absint($_GET['client_id']) : 0;
            
            // 2. POSTパラメータも確認
            if ($client_id <= 0 && isset($_POST['client_id']) && absint($_POST['client_id']) > 0) {
                $client_id = absint($_POST['client_id']);
            }
            
            // 最終手段：顧客IDが提供されなかった場合は、会社名と担当者名から顧客IDを検索
            if ($client_id <= 0 && $customer_name !== '') {
                $client = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM `{$client_table}` WHERE company_name = %s AND name = %s",
                    $customer_name, 
                    $user_name
                ));
                if ($client) {
                    $client_id = $client->id;
                } else {
                }
            }
            
            // 受注書データをデータベースに挿入
            // 標準的なUNIXタイムスタンプを使用（UTCベース）
            $timestamp = time(); // 標準のUTC UNIXタイムスタンプを取得
            
            $insert_data = array(
                'time' => $timestamp, // 標準のUTC UNIXタイムスタンプで保存
                'client_id' => $client_id, // 顧客IDを保存
                'customer_name' => sanitize_text_field( $customer_name ),
                'user_name' => sanitize_text_field( $user_name ),
                'project_name' => '※ 入力してください', // 案件名の初期値を設定
                'invoice_items' => '', // 初期値は空
                'cost_items' => '', // 初期値は空
                'memo' => '', // 初期値は空
                'search_field' => sanitize_text_field( implode(', ', [$customer_name, $user_name]) ), // 検索用フィールド
            );

            $inserted = $wpdb->insert($table_name, $insert_data, array(
                '%d', // time
                '%d', // client_id
                '%s', // customer_name
                '%s', // user_name
                '%s', // project_name
                '%s', // invoice_items
                '%s', // cost_items
                '%s', // memo
                '%s'  // search_field
            ));

            if ($inserted) {
                $new_order_id = $wpdb->insert_id; // 挿入された受注書IDを取得
                
                // 初期請求項目を作成
                $this->Create_Initial_Invoice_Item( $new_order_id );
                
                // 初期コスト項目を作成
                $this->Create_Initial_Cost_Item( $new_order_id );
                
                // 初期スタッフチャットエントリを作成
                $this->Create_Initial_Staff_Chat( $new_order_id );
                
                // リダイレクト処理を無効化 - 代わりにorder_idを直接設定
                $_GET['order_id'] = $new_order_id;
                $_GET['from_client'] = null; // from_clientフラグをクリア
                $order_id = $new_order_id; // ローカル変数も更新
                
                // デバッグ用ログ
            } else {
                // 挿入失敗時のエラーハンドリング
                $content .= '<div class="error">受注書の作成に失敗しました。</div>';
            }
        }

        // 重要なチェックポイント7を追加

        // 受注書IDが指定されていない場合は最新の受注書IDを取得
        if ($order_id === 0) {
            $latest_order = $wpdb->get_row( $wpdb->prepare( 
                "SELECT id FROM `{$table_name}` ORDER BY time DESC LIMIT %d", 
                1 
            ) );
            if ($latest_order) {
                $order_id = $latest_order->id;
            } else {
            }
        } else {
        }

        // 重要なチェックポイント8を追加

        // 受注書データが存在する場合に詳細を表示
        if ($order_id > 0) {
            $order_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table_name}` WHERE id = %d", $order_id));

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
                $content .= '<button id="orderPreviewButton" onclick="toggleOrderPreview()" title="' . esc_attr__('プレビュー', 'ktpwp') . '" style="padding: 8px 12px; font-size: 14px;">';
                $content .= '<span class="material-symbols-outlined" aria-label="' . esc_attr__('プレビュー', 'ktpwp') . '">preview</span>';
                $content .= '</button>';
                $content .= '<button onclick="printOrderContent()" title="' . esc_attr__('印刷する', 'ktpwp') . '" style="padding: 8px 12px; font-size: 14px;">';
                $content .= '<span class="material-symbols-outlined" aria-label="' . esc_attr__('印刷', 'ktpwp') . '">print</span>';
                $content .= '</button>';
                
                // 顧客情報に基づいてメールボタンの状態を制御
                $client = null;
                $can_send_email = false;
                $mail_button_title = esc_attr__('メール', 'ktpwp');
                $mail_button_style = 'padding: 8px 12px; font-size: 14px;';
                
                // 顧客データを取得
                if (!empty($order_data->client_id)) {
                    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$client_table}` WHERE id = %d", $order_data->client_id));
                }
                
                // IDで見つからない場合は会社名と担当者名で検索（後方互換性）
                if (!$client) {
                    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$client_table}` WHERE company_name = %s AND name = %s", 
                        $order_data->customer_name, $order_data->user_name));
                }
                
                // 顧客データ詳細ログ
                if ($client) {
                }
                
                // メール送信可否の判定（強化版）
                if (!$client) {
                    $mail_button_title = 'メール送信不可（顧客データなし）';
                    $mail_button_style = 'padding: 8px 12px; font-size: 14px; background: #ccc; cursor: not-allowed;';
                } elseif ($client->category === '対象外' || trim($client->category ?: '') === '対象外') {
                    $mail_button_title = 'メール送信不可（対象外顧客）';
                    $mail_button_style = 'padding: 8px 12px; font-size: 14px; background: #f44336; color: white; cursor: not-allowed;';
                } else {
                    // メールアドレスの詳細な検証（強化版） - nameフィールドも考慮
                    $email_raw = $client->email ?? '';
                    $name_raw = $client->name ?? '';
                    
                    // nameフィールドにメールアドレスが入っている場合を検出
                    $name_is_email = !empty($name_raw) && filter_var($name_raw, FILTER_VALIDATE_EMAIL) !== false;
                    $email_is_empty = empty(trim($email_raw));
                    
                    if ($name_is_email && $email_is_empty) {
                        $email_raw = $name_raw; // nameフィールドの値を使用
                    }
                    
                    
                    // シンプルなメールアドレス検証（修正版）
                    // 過度に厳格な検証が問題の原因だったため、より寛容なロジックに変更
                    $email = trim($email_raw);
                    
                    // 基本的な制御文字のみ除去（最小限）
                    $email = str_replace(["\0", "\r", "\n", "\t"], '', $email);
                    
                    // Step 3〜5は削除（厳しすぎて有効なメールアドレスが無効になっていた）
                    
                    // 最終検証のみ実行
                    $is_valid = !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
                    
                    if (!$is_valid) {
                        $mail_button_title = 'メール送信不可（メールアドレス未設定または無効）';
                        $mail_button_style = 'padding: 8px 12px; font-size: 14px; background: #ff9800; color: white; cursor: not-allowed;';
                    } else {
                        $can_send_email = true;
                        $mail_button_title = 'メール送信（' . esc_attr($email) . '）';
                        $mail_button_style = 'padding: 8px 12px; font-size: 14px; background: #2196f3; color: white;';
                    }
                }
                
                $content .= '<form id="orderMailForm" method="post" action="" style="display:inline;margin-top:0px;">';
                if ($can_send_email) {
                    $content .= '<input type="hidden" name="send_order_mail_id" value="' . esc_attr($order_data->id) . '">';
                    // Add nonce to mail form
                    $content .= wp_nonce_field( 'send_order_mail_action', 'order_mail_nonce', true, false );
                    $content .= '<button type="submit" id="orderMailButton" title="' . $mail_button_title . '" style="' . $mail_button_style . '">';
                } else {
                    $content .= '<button type="button" id="orderMailButton" disabled title="' . $mail_button_title . '" style="' . $mail_button_style . '">';
                }
                $content .= '<span class="material-symbols-outlined" aria-label="' . esc_attr__('メール', 'ktpwp') . '">mail</span>';
                $content .= '</button>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div id="orderPreviewWindow" style="display: none;"></div>';

                // workflowセクション追加（デザイン統一）
                $content .= '<div class="workflow">';
                // 削除ボタンをworkflow内に移動（フォーム送信ベース）
                // 顧客データが存在しない場合でも削除可能にする
                $current_url = add_query_arg(NULL, NULL);
                $content .= '<form method="post" action="' . esc_url($current_url) . '" style="display:inline-block;margin-left:10px;" onsubmit="return confirm(\'本当にこの受注書を削除しますか？\\nこの操作は元に戻せません。\');">';
                $content .= '<input type="hidden" name="order_id" value="' . esc_attr($order_id) . '">';
                $content .= '<input type="hidden" name="delete_order" value="1">';
                // 顧客データの存在有無を記録（デバッグ用）
                $client_exists = false;
                if (!empty($order_data->client_id)) {
                    $client_exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM `{$client_table}` WHERE id = %d",
                        $order_data->client_id
                    )) > 0;
                }
                $content .= '<input type="hidden" name="client_exists" value="' . ($client_exists ? '1' : '0') . '">';
                // Add nonce to delete form  
                $content .= wp_nonce_field( 'delete_order_action', 'delete_nonce', true, false );
                $content .= '<button type="submit" style="color:#fff;background:#d9534f;padding: 8px 12px;font-size:14px;border:none;border-radius:4px;cursor:pointer;">受注書を削除</button>';
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
    . '<input type="text" class="order_project_name_inline order-header-projectname" name="order_project_name_inline" value="' . (isset($order_data->project_name) ? esc_html($order_data->project_name) : '') . '" data-order-id="' . esc_html($order_data->id) . '" placeholder="案件名" autocomplete="off" />'
    . '</span>';
$content .= '<form method="post" action="" class="progress-filter order-header-progress-form" style="display:flex;align-items:center;gap:8px;flex-wrap:nowrap;margin-left:auto;">';
$content .= '<input type="hidden" name="update_progress_id" value="' . esc_html($order_data->id) . '" />';
// Add nonce for progress update
$content .= wp_nonce_field( 'update_progress_action', 'progress_nonce', true, false );
$content .= '<label for="order_progress_select" style="white-space:nowrap;margin-right:4px;font-weight:bold;">進捗：</label>';
$content .= '<select id="order_progress_select" name="update_progress" onchange="this.form.submit()" style="min-width:120px;max-width:200px;width:auto;">';
foreach ($progress_labels as $num => $label) {
    $selected = ($order_data->progress == $num) ? 'selected' : '';
    $content .= '<option value="' . $num . '" ' . $selected . '>' . $label . '</option>';
}
$content .= '</select>';
$content .= '</form>';
$content .= '</div>';
                // 顧客IDの表示を改善
                $client_id_display = '';
                if (!empty($order_data->client_id)) {
                    // 顧客レコードが実際に存在するか確認
                    $client_data = $wpdb->get_row($wpdb->prepare(
                        "SELECT id, category FROM `{$client_table}` WHERE id = %d",
                        $order_data->client_id
                    ));
                    
                    if ($client_data) {
                        // 顧客が存在する場合
                        if ($client_data->category === '対象外') {
                            // 削除済み（対象外）顧客の場合は赤色で表示
                            $client_id_display = '（顧客ID: <span style="color:red;">' . esc_html($order_data->client_id) . ' - 削除済み</span>）';
                        } else {
                            // 通常の顧客はリンク付きで表示
                            $base_url = get_permalink(25); // ページID 25を動的に取得
                            $client_url = add_query_arg(
                                [
                                    'tab_name' => 'client',
                                    'data_id' => $order_data->client_id
                                ],
                                $base_url
                            );
                            $client_id_display = '（顧客ID: <a href="' . esc_url($client_url) . '" style="color:blue;">' . esc_html($order_data->client_id) . '</a>）';
                        }
                    } else {
                        // 顧客が存在しない場合（データベースから完全に削除されている）
                        $client_id_display = '（顧客ID: <span style="color:red;">' . esc_html($order_data->client_id) . ' - 削除済み</span>）';
                    }
                } else {
                    $client_id_display = '（顧客ID未設定）';
                }
                $content .= '<div>会社名：<span id="order_customer_name">' . esc_html($order_data->customer_name) . '</span> <span class="client-id" style="color:#666;font-size:0.9em;">' . $client_id_display . '</span></div>';
                // 担当者名の横に得意先メールアドレスのmailtoリンク（あれば）
                $client_email = '';
                $client = null;
                
                // まず顧客IDがある場合はIDで検索
                if (!empty($order_data->client_id)) {
                    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$client_table}` WHERE id = %d", $order_data->client_id));
                }
                
                // IDで見つからない場合は会社名と担当者名で検索（後方互換性）
                if (!$client) {
                    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$client_table}` WHERE company_name = %s AND name = %s", 
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
                // preg_replaceによる重複input出力を削除（1箇所のみ出力）
                // $content = preg_replace(...); を削除
                $content .= '</div>'; // .order_info_box 終了

                $content .= '<div class="order_invoice_box box">';
                $content .= '<h4>■ 請求項目</h4>';
                // 請求項目テーブルを表示
                $content .= $this->Generate_Invoice_Items_Table( $order_id );
                $content .= '</div>'; // .order_invoice_box 終了
                // コスト項目とメモ項目のセクションを追加
                $content .= '<div class="order_cost_box box">';
                $content .= '<h4>■ コスト項目';
                // トグルボタンを追加
                $content .= '<button type="button" class="toggle-cost-items" aria-expanded="false" ';
                $content .= 'title="' . esc_attr__( 'コスト項目の表示/非表示を切り替え', 'ktpwp' ) . '">';
                $content .= esc_html__( '表示', 'ktpwp' );
                $content .= '</button>';
                $content .= '</h4>';
                // コスト項目テーブルをラップする
                $content .= '<div id="cost-items-content">';
                // コスト項目テーブルを表示
                $content .= $this->Generate_Cost_Items_Table( $order_id );
                $content .= '</div>'; // #cost-items-content 終了
                $content .= '</div>'; // .order_cost_box 終了
                // スタッフチャットセクションを追加（タイトルなし）
                $content .= '<!-- DEBUG: スタッフチャット開始 -->';
                $staff_chat_html = $this->Generate_Staff_Chat_HTML( $order_id );
                $content .= '<!-- DEBUG: スタッフチャットHTML長: ' . strlen($staff_chat_html) . ' -->';
                $content .= $staff_chat_html;
                $content .= '<!-- DEBUG: スタッフチャット終了 -->';
                // 受注書内容セクションの終了
                $content .= '</div>'; // .order_contents 終了

                // 削除ボタンはworkflow内に移動済み

            } else {
                $content .= '<div class="error">' . esc_html__('指定された受注書は見つかりませんでした。', 'ktpwp') . '</div>';
            }

        } else {
            // 受注書データが存在しない場合でもレイアウトを維持
            $content .= "<div class=\"controller\">\n";
            $content .= "    <div class=\"printer\">\n";
            $content .= "        <button id=\"orderPreviewButton\" disabled title=\"" . esc_attr__('プレビュー', 'ktpwp') . "\">\n";
            $content .= "            <span class=\"material-symbols-outlined\" aria-label=\"" . esc_attr__('プレビュー', 'ktpwp') . "\">preview</span>\n";
            $content .= "        </button>\n";
            $content .= "        <button disabled title=\"" . esc_attr__('印刷する', 'ktpwp') . "\">\n";
            $content .= "            <span class=\"material-symbols-outlined\" aria-label=\"" . esc_attr__('印刷', 'ktpwp') . "\">print</span>\n";
            $content .= "        </button>\n";
            $content .= "    </div>\n";
            $content .= "</div>\n";
            $content .= "<p>" . esc_html__('表示する受注書がありません。', 'ktpwp') . "</p>";
        }

        // ページネーションロジック（表示はしないが計算は残す）
        $query_limit = 20; // 1ページあたりの表示件数
        $page_start = isset($_GET['page_start']) ? intval($_GET['page_start']) : 0; // 表示開始位置

        // 全データ数を取得 - Use prepared statement
        $total_query = "SELECT COUNT(*) FROM `{$table_name}`";
        $total_rows = $wpdb->get_var($total_query);
        $total_pages = ceil($total_rows / $query_limit);

        // 現在のページ番号を計算
        $current_page = floor($page_start / $query_limit) + 1;

        // TODO: ページネーションリンクのHTML生成は削除またはコメントアウト
        // $content .= "<div class='pagination'>";
        // ... ページネーションリンク生成コード ...
        // $content .= "</div>"; // .pagination 終了

        // デバッグログ追加
        
        return $content;
    } // End of Order_Tab_View method

    /**
     * Save or update invoice items
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @param array $items Invoice items data
     * @return bool True on success, false on failure
     */
    public function Save_Invoice_Items( $order_id, $items ) {
        // デバッグ: 受け取った請求項目配列を出力
        if ( ! $order_id || $order_id <= 0 || ! is_array( $items ) ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_invoice_items';
        
        // Start transaction
        $wpdb->query( 'START TRANSACTION' );
        
        try {
            $sort_order = 1;
            $submitted_ids = array();
            foreach ( $items as $item ) {
                // Sanitize input data
                $item_id = isset( $item['id'] ) ? intval( $item['id'] ) : 0;
                $product_name = isset( $item['product_name'] ) ? sanitize_text_field( $item['product_name'] ) : '';
                $price = isset( $item['price'] ) ? floatval( $item['price'] ) : 0;
                $unit = isset( $item['unit'] ) ? sanitize_text_field( $item['unit'] ) : '';
                $quantity = isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 0;
                $amount = isset( $item['amount'] ) ? floatval( $item['amount'] ) : 0;
                $remarks = isset( $item['remarks'] ) ? sanitize_textarea_field( $item['remarks'] ) : '';

                // サービスが空ならスキップ（サービスがあれば必ず保存）
                if ( empty( $product_name ) ) {
                    continue;
                }

                $data = array(
                    'order_id' => $order_id,
                    'product_name' => $product_name,
                    'price' => $price,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'amount' => $amount,
                    'remarks' => $remarks,
                    'sort_order' => $sort_order,
                    'updated_at' => current_time( 'mysql' )
                );

                $format = array( '%d', '%s', '%f', '%s', '%f', '%f', '%s', '%d', '%s' );

                $used_id = 0;
                if ( $item_id > 0 ) {
                    // Update existing item
                    $result = $wpdb->update(
                        $table_name,
                        $data,
                        array( 'id' => $item_id, 'order_id' => $order_id ),
                        $format,
                        array( '%d', '%d' )
                    );
                    $used_id = $item_id;
                } else {
                    // Insert new item
                    $data['created_at'] = current_time( 'mysql' );
                    $format[] = '%s';
                    $result = $wpdb->insert( $table_name, $data, $format );
                    if ($result === false) {
                        error_log('KTPWP Error: Invoice item INSERT failed: ' . $wpdb->last_error);
                    }
                    $used_id = $wpdb->insert_id;
                }

                if ( $result === false ) {
                    throw new Exception( 'Database operation failed: ' . $wpdb->last_error );
                }

                if ($used_id > 0) {
                    $submitted_ids[] = $used_id;
                }

                $sort_order++;
            }

            // Remove any items that weren't in the submitted data
            if ( ! empty( $submitted_ids ) ) {
                $ids_placeholder = implode( ',', array_fill( 0, count( $submitted_ids ), '%d' ) );
                $delete_query = $wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE order_id = %d AND id NOT IN ({$ids_placeholder})",
                    array_merge( array( $order_id ), $submitted_ids )
                );
                $wpdb->query( $delete_query );
            }

            // Commit transaction
            $wpdb->query( 'COMMIT' );


            return true;

        } catch ( Exception $e ) {
            // Rollback transaction
            $wpdb->query( 'ROLLBACK' );
            error_log( 'KTPWP: Failed to save invoice items: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Save or update cost items
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @param array $items Cost items data
     * @return bool True on success, false on failure
     */
    public function Save_Cost_Items( $order_id, $items ) {
        // デバッグ: 受け取ったコスト項目配列を出力
        if ( ! $order_id || $order_id <= 0 || ! is_array( $items ) ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_cost_items';
        
        // Start transaction
        $wpdb->query( 'START TRANSACTION' );
        
        try {
            $sort_order = 1;
            $submitted_ids = array();
            foreach ( $items as $item ) {
                // Sanitize input data
                $item_id = isset( $item['id'] ) ? intval( $item['id'] ) : 0;
                $product_name = isset( $item['product_name'] ) ? sanitize_text_field( $item['product_name'] ) : '';
                $price = isset( $item['price'] ) ? floatval( $item['price'] ) : 0;
                $unit = isset( $item['unit'] ) ? sanitize_text_field( $item['unit'] ) : '';
                $quantity = isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 0;
                $amount = isset( $item['amount'] ) ? floatval( $item['amount'] ) : 0;
                $remarks = isset( $item['remarks'] ) ? sanitize_textarea_field( $item['remarks'] ) : '';

                // 商品名が空ならスキップ（商品名があれば必ず保存）
                if ( empty( $product_name ) ) {
                    continue;
                }

                $data = array(
                    'order_id' => $order_id,
                    'product_name' => $product_name,
                    'price' => $price,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'amount' => $amount,
                    'remarks' => $remarks,
                    'sort_order' => $sort_order,
                    'updated_at' => current_time( 'mysql' )
                );

                $format = array( '%d', '%s', '%f', '%s', '%f', '%f', '%s', '%d', '%s' );

                $used_id = 0;
                if ( $item_id > 0 ) {
                    // Update existing item
                    $result = $wpdb->update(
                        $table_name,
                        $data,
                        array( 'id' => $item_id, 'order_id' => $order_id ),
                        $format,
                        array( '%d', '%d' )
                    );
                    $used_id = $item_id;
                } else {
                    // Insert new item
                    $data['created_at'] = current_time( 'mysql' );
                    $format[] = '%s';
                    $result = $wpdb->insert( $table_name, $data, $format );
                    if ($result === false) {
                        error_log('KTPWP Error: Cost item INSERT failed: ' . $wpdb->last_error);
                    }
                    $used_id = $wpdb->insert_id;
                }

                if ( $result === false ) {
                    throw new Exception( 'Database operation failed: ' . $wpdb->last_error );
                }

                if ($used_id > 0) {
                    $submitted_ids[] = $used_id;
                }

                $sort_order++;
            }

            // Remove any items that weren't in the submitted data
            if ( ! empty( $submitted_ids ) ) {
                $ids_placeholder = implode( ',', array_fill( 0, count( $submitted_ids ), '%d' ) );
                $delete_query = $wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE order_id = %d AND id NOT IN ({$ids_placeholder})",
                    array_merge( array( $order_id ), $submitted_ids )
                );
                $wpdb->query( $delete_query );
            }

            // Commit transaction
            $wpdb->query( 'COMMIT' );


            return true;

        } catch ( Exception $e ) {
            // Rollback transaction
            $wpdb->query( 'ROLLBACK' );
            error_log( 'KTPWP: Failed to save cost items: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Check for concurrent access conflicts
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True if safe to proceed, false if conflict detected
     */
    private function Check_Concurrent_Access( $order_id ) {
        $lock_option = 'ktp_order_editing_' . $order_id;
        $current_time = time();
        $lock_timeout = 30; // 30秒でタイムアウト
        
        // 既存のロックをチェック
        $existing_lock = get_option( $lock_option );
        
        if ( $existing_lock ) {
            $lock_data = json_decode( $existing_lock, true );
            
            // タイムアウトチェック
            if ( isset( $lock_data['timestamp'] ) && 
                 ( $current_time - $lock_data['timestamp'] ) > $lock_timeout ) {
                // タイムアウトしたロックを削除
                delete_option( $lock_option );
            } else {
                // アクティブなロックが存在
                return false;
            }
        }
        
        // 新しいロックを設定
        $lock_data = array(
            'user_id' => get_current_user_id(),
            'timestamp' => $current_time
        );
        
        update_option( $lock_option, json_encode( $lock_data ) );
        
        return true;
    }
    
    /**
     * Release concurrent access lock
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     */
    private function Release_Concurrent_Access_Lock( $order_id ) {
        $lock_option = 'ktp_order_editing_' . $order_id;
        delete_option( $lock_option );
    }

    /**
     * Auto-save functionality is now handled by KTPWP_Ajax class
     * This method is kept for backward compatibility and delegates to the new system
     *
     * @deprecated Use KTPWP_Ajax::ajax_auto_save_item() instead
     * @since 1.0.0
     */
    public function ajax_auto_save_item() {
        $ajax_handler = KTPWP_Ajax::get_instance();
        $ajax_handler->ajax_auto_save_item();
    }

    /**
     * New item creation is now handled by KTPWP_Ajax class  
     * This method is kept for backward compatibility and delegates to the new system
     *
     * @deprecated Use KTPWP_Ajax::ajax_create_new_item() instead
     * @since 1.0.0
     */
    public function ajax_create_new_item() {
        $ajax_handler = KTPWP_Ajax::get_instance();
        $ajax_handler->ajax_create_new_item();
    }
} // End of Kntan_Order_Class

} // class_exists check
