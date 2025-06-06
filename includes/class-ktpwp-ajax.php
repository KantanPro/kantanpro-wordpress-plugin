<?php
/**
 * KTPWP Ajax処理管理クラス
 *
 * @package KTPWP
 * @since 0.1.0
 */

// セキュリティ: 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax処理管理クラス
 */
class KTPWP_Ajax {
    
    /**
     * シングルトンインスタンス
     *
     * @var KTPWP_Ajax|null
     */
    private static $instance = null;
    
    /**
     * 登録されたAjaxハンドラー一覧
     *
     * @var array
     */
    private $registered_handlers = array();
    
    /**
     * nonce名の設定
     *
     * @var array
     */
    private $nonce_names = array(
        'auto_save' => 'ktp_ajax_nonce',
        'project_name' => 'ktp_update_project_name',
        'inline_edit' => 'ktpwp_inline_edit_nonce',
        'general' => 'ktpwp_ajax_nonce',
        'staff_chat' => 'ktpwp_staff_chat_nonce'
    );
    
    /**
     * シングルトンインスタンス取得
     *
     * @return KTPWP_Ajax
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * フック初期化
     */
    private function init_hooks() {
        // 初期化処理
        add_action('init', array($this, 'register_ajax_handlers'), 10);
        
        // WordPress管理画面でのスクリプト読み込み時にnonce設定
        add_action('wp_enqueue_scripts', array($this, 'localize_ajax_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'localize_ajax_scripts'));
    }
    
    /**
     * Ajaxハンドラー登録
     */
    public function register_ajax_handlers() {
        // プロジェクト名インライン編集（管理者のみ）
        add_action('wp_ajax_ktp_update_project_name', array($this, 'ajax_update_project_name'));
        add_action('wp_ajax_nopriv_ktp_update_project_name', array($this, 'ajax_require_login'));
        $this->registered_handlers[] = 'ktp_update_project_name';
        
        // 受注関連のAjax処理を初期化
        $this->init_order_ajax_handlers();
        
        // スタッフチャット関連のAjax処理を初期化
        $this->init_staff_chat_ajax_handlers();
        
        // ログイン中ユーザー取得
        add_action('wp_ajax_get_logged_in_users', array($this, 'ajax_get_logged_in_users'));
        add_action('wp_ajax_nopriv_get_logged_in_users', array($this, 'ajax_get_logged_in_users'));
        $this->registered_handlers[] = 'get_logged_in_users';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP Ajax: Registered handlers - ' . implode(', ', $this->registered_handlers));
        }
    }
    
    /**
     * 受注関連Ajaxハンドラー初期化
     */
    private function init_order_ajax_handlers() {
        // 受注クラスファイルの読み込み
        $order_class_file = KTPWP_PLUGIN_DIR . 'includes/class-tab-order.php';
        
        if (file_exists($order_class_file)) {
            require_once $order_class_file;
            
            if (class_exists('Kntan_Order_Class')) {
                $order_instance = new Kntan_Order_Class();
                
                // 自動保存
                add_action('wp_ajax_ktp_auto_save_item', array($order_instance, 'ajax_auto_save_item'));
                add_action('wp_ajax_nopriv_ktp_auto_save_item', array($order_instance, 'ajax_auto_save_item'));
                $this->registered_handlers[] = 'ktp_auto_save_item';
                
                // 新規アイテム作成
                add_action('wp_ajax_ktp_create_new_item', array($order_instance, 'ajax_create_new_item'));
                add_action('wp_ajax_nopriv_ktp_create_new_item', array($order_instance, 'ajax_create_new_item'));
                $this->registered_handlers[] = 'ktp_create_new_item';
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('KTPWP Ajax Error: Kntan_Order_Class not found');
                }
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KTPWP Ajax Error: class-tab-order.php not found');
            }
        }
    }
    
    /**
     * スタッフチャット関連Ajaxハンドラー初期化
     */
    private function init_staff_chat_ajax_handlers() {
        // スタッフチャットクラスファイルの読み込み
        $staff_chat_class_file = KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-staff-chat.php';
        
        if (file_exists($staff_chat_class_file)) {
            require_once $staff_chat_class_file;
            
            if (class_exists('KTPWP_Staff_Chat')) {
                // 最新チャットメッセージ取得
                add_action('wp_ajax_get_latest_staff_chat', array($this, 'ajax_get_latest_staff_chat'));
                add_action('wp_ajax_nopriv_get_latest_staff_chat', array($this, 'ajax_require_login'));
                $this->registered_handlers[] = 'get_latest_staff_chat';
                
                // チャットメッセージ送信
                add_action('wp_ajax_send_staff_chat_message', array($this, 'ajax_send_staff_chat_message'));
                add_action('wp_ajax_nopriv_send_staff_chat_message', array($this, 'ajax_require_login'));
                $this->registered_handlers[] = 'send_staff_chat_message';
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('KTPWP Ajax Error: KTPWP_Staff_Chat class not found');
                }
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KTPWP Ajax Error: class-ktpwp-staff-chat.php not found');
            }
        }
    }
    
    /**
     * Ajaxスクリプトの設定
     */
    public function localize_ajax_scripts() {
        // 基本的なAjax URL設定
        $ajax_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => array()
        );

        // 各種nonceの設定
        foreach ($this->nonce_names as $action => $nonce_name) {
            if ($action === 'project_name' && current_user_can('manage_options')) {
                $ajax_data['nonces'][$action] = wp_create_nonce($nonce_name);
            } elseif ($action !== 'project_name') {
                $ajax_data['nonces'][$action] = wp_create_nonce($nonce_name);
            }
        }

        // JavaScriptファイルがエンキューされている場合のみlocalizeを実行
        global $wp_scripts;

        if (isset($wp_scripts->registered['ktp-js'])) {
            wp_add_inline_script('ktp-js', 'var ktp_ajax_object = ' . json_encode($ajax_data) . ';');
            wp_add_inline_script('ktp-js', 'var ktpwp_ajax = ' . json_encode($ajax_data) . ';');
        }

        if (isset($wp_scripts->registered['ktp-invoice-items'])) {
            wp_add_inline_script('ktp-invoice-items', 'var ktp_ajax_nonce = ' . json_encode($ajax_data['nonces']['auto_save']) . ';');
            wp_add_inline_script('ktp-invoice-items', 'var ajaxurl = ' . json_encode($ajax_data['ajax_url']) . ';');
        }

        if (isset($wp_scripts->registered['ktp-cost-items'])) {
            wp_add_inline_script('ktp-cost-items', 'var ktp_ajax_nonce = ' . json_encode($ajax_data['nonces']['auto_save']) . ';');
            wp_add_inline_script('ktp-cost-items', 'var ajaxurl = ' . json_encode($ajax_data['ajax_url']) . ';');
        }

        if (isset($wp_scripts->registered['ktp-order-inline-projectname']) && current_user_can('manage_options')) {
            wp_add_inline_script('ktp-order-inline-projectname', 'var ktpwp_inline_edit_nonce = ' . json_encode(array(
                'nonce' => $ajax_data['nonces']['project_name']
            )) . ';');
        }
    }
    
    /**
     * Ajax: プロジェクト名更新（管理者のみ）
     */
    public function ajax_update_project_name() {
        // 共通バリデーション（管理者権限必須）
        if (!$this->validate_ajax_request('ktp_update_project_name', true)) {
            return; // エラーレスポンスは既に送信済み
        }

        // POSTデータの取得とサニタイズ
        $order_id = $this->sanitize_ajax_input('order_id', 'int');
        $project_name = $this->sanitize_ajax_input('project_name', 'text');

        // バリデーション
        if ($order_id <= 0) {
            $this->log_ajax_error('Invalid order ID for project name update', array('order_id' => $order_id));
            wp_send_json_error(__('無効な受注IDです', 'ktpwp'));
        }

        // 新しいクラス構造を使用してプロジェクト名を更新
        $order_manager = KTPWP_Order::get_instance();
        
        try {
            $result = $order_manager->update_order($order_id, array(
                'project_name' => $project_name
            ));
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('プロジェクト名を更新しました', 'ktpwp'),
                    'project_name' => $project_name
                ));
            } else {
                $this->log_ajax_error('Failed to update project name', array(
                    'order_id' => $order_id,
                    'project_name' => $project_name
                ));
                wp_send_json_error(__('更新に失敗しました', 'ktpwp'));
            }
        } catch (Exception $e) {
            $this->log_ajax_error('Exception during project name update', array(
                'message' => $e->getMessage(),
                'order_id' => $order_id
            ));
            wp_send_json_error(__('更新中にエラーが発生しました', 'ktpwp'));
        }
    }
    
    /**
     * Ajax: ログイン中ユーザー取得
     */
    public function ajax_get_logged_in_users() {
        $logged_in_users = get_users(array(
            'meta_key' => 'session_tokens',
            'meta_compare' => 'EXISTS'
        ));
        
        $users_names = array();
        foreach ($logged_in_users as $user) {
            $users_names[] = esc_html($user->nickname) . 'さん';
        }
        
        wp_send_json($users_names);
    }
    
    /**
     * Ajax: ログイン要求（非ログインユーザー用）
     */
    public function ajax_require_login() {
        wp_send_json_error(__('ログインが必要です', 'ktpwp'));
    }
    
    /**
     * Ajax: 自動保存アイテム処理
     */
    public function ajax_auto_save_item() {
        // セキュリティチェック
        if (!check_ajax_referer('ktp_ajax_nonce', 'nonce', false)) {
            $this->log_ajax_error('Auto-save security check failed');
            wp_send_json_error(__('セキュリティ検証に失敗しました', 'ktpwp'));
        }

        // POSTデータの取得とサニタイズ
        $item_type = $this->sanitize_ajax_input('item_type', 'text');
        $item_id = $this->sanitize_ajax_input('item_id', 'int');
        $field_name = $this->sanitize_ajax_input('field_name', 'text');
        $field_value = $this->sanitize_ajax_input('field_value', 'text');
        $order_id = $this->sanitize_ajax_input('order_id', 'int');

        // バリデーション
        if (!in_array($item_type, array('invoice', 'cost'), true)) {
            $this->log_ajax_error('Invalid item type', array('type' => $item_type));
            wp_send_json_error(__('無効なアイテムタイプです', 'ktpwp'));
        }

        if ($item_id <= 0 || $order_id <= 0) {
            $this->log_ajax_error('Invalid ID values', array('item_id' => $item_id, 'order_id' => $order_id));
            wp_send_json_error(__('無効なIDです', 'ktpwp'));
        }

        // 新しいクラス構造を使用してアイテムを更新
        $order_items = KTPWP_Order_Items::get_instance();
        
        try {
            $update_data = array($field_name => $field_value);
            
            if ($item_type === 'invoice') {
                $result = $order_items->update_invoice_item($item_id, $update_data);
            } else {
                $result = $order_items->update_cost_item($item_id, $update_data);
            }
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('正常に保存されました', 'ktpwp')
                ));
            } else {
                $this->log_ajax_error('Failed to update item', array(
                    'type' => $item_type,
                    'item_id' => $item_id,
                    'field' => $field_name
                ));
                wp_send_json_error(__('保存に失敗しました', 'ktpwp'));
            }
        } catch (Exception $e) {
            $this->log_ajax_error('Exception during auto-save', array(
                'message' => $e->getMessage(),
                'type' => $item_type,
                'item_id' => $item_id
            ));
            wp_send_json_error(__('保存中にエラーが発生しました', 'ktpwp'));
        }
    }

    /**
     * Ajax: 新規アイテム作成処理
     */
    public function ajax_create_new_item() {
        // セキュリティチェック
        if (!check_ajax_referer('ktp_ajax_nonce', 'nonce', false)) {
            $this->log_ajax_error('Create new item security check failed');
            wp_send_json_error(__('セキュリティ検証に失敗しました', 'ktpwp'));
        }

        // POSTデータの取得とサニタイズ
        $item_type = $this->sanitize_ajax_input('item_type', 'text');
        $field_name = $this->sanitize_ajax_input('field_name', 'text');
        $field_value = $this->sanitize_ajax_input('field_value', 'text');
        $order_id = $this->sanitize_ajax_input('order_id', 'int');

        // バリデーション
        if (!in_array($item_type, array('invoice', 'cost'), true)) {
            $this->log_ajax_error('Invalid item type for creation', array('type' => $item_type));
            wp_send_json_error(__('無効なアイテムタイプです', 'ktpwp'));
        }

        if ($order_id <= 0) {
            $this->log_ajax_error('Invalid order ID for creation', array('order_id' => $order_id));
            wp_send_json_error(__('無効な受注IDです', 'ktpwp'));
        }

        // 新しいクラス構造を使用してアイテムを作成
        $order_items = KTPWP_Order_Items::get_instance();
        
        try {
            // 初期データを作成
            $initial_data = array(
                'order_id' => $order_id,
                'product_name' => '',
                'price' => 0,
                'quantity' => 1,
                'unit' => '式',
                'amount' => 0,
                'remarks' => '',
                'sort_order' => 999
            );

            // 指定されたフィールド値を設定
            if (!empty($field_name) && isset($initial_data[$field_name])) {
                $initial_data[$field_name] = $field_value;
            }

            if ($item_type === 'invoice') {
                $new_item_id = $order_items->create_invoice_item($initial_data);
            } else {
                $new_item_id = $order_items->create_cost_item($initial_data);
            }
            
            if ($new_item_id) {
                wp_send_json_success(array(
                    'item_id' => $new_item_id,
                    'message' => __('新しいアイテムが作成されました', 'ktpwp')
                ));
            } else {
                $this->log_ajax_error('Failed to create new item', array(
                    'type' => $item_type,
                    'order_id' => $order_id
                ));
                wp_send_json_error(__('アイテムの作成に失敗しました', 'ktpwp'));
            }
        } catch (Exception $e) {
            $this->log_ajax_error('Exception during item creation', array(
                'message' => $e->getMessage(),
                'type' => $item_type,
                'order_id' => $order_id
            ));
            wp_send_json_error(__('作成中にエラーが発生しました', 'ktpwp'));
        }
    }

    /**
     * Ajaxリクエストの共通バリデーション
     *
     * @param string $action アクション名
     * @param bool $require_admin 管理者権限必須かどうか
     * @return bool バリデーション結果
     */
    public function validate_ajax_request($action, $require_admin = false) {
        // ログインチェック
        if (!is_user_logged_in()) {
            wp_send_json_error(__('ログインが必要です', 'ktpwp'));
            return false;
        }
        
        // 管理者権限チェック
        if ($require_admin && !current_user_can('manage_options')) {
            wp_send_json_error(__('管理者権限が必要です', 'ktpwp'));
            return false;
        }
        
        // nonceチェック
        $nonce_key = $this->get_nonce_key_for_action($action);
        if ($nonce_key && isset($_POST['_wpnonce'])) {
            if (!wp_verify_nonce($_POST['_wpnonce'], $nonce_key)) {
                wp_send_json_error(__('セキュリティ検証に失敗しました', 'ktpwp'));
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * アクションに対応するnonce名を取得
     *
     * @param string $action アクション名
     * @return string|false nonce名またはfalse
     */
    private function get_nonce_key_for_action($action) {
        $action_nonce_map = array(
            'ktp_update_project_name' => $this->nonce_names['project_name'],
            'ktp_auto_save_item' => $this->nonce_names['auto_save'],
            'ktp_create_new_item' => $this->nonce_names['auto_save'],
        );
        
        return isset($action_nonce_map[$action]) ? $action_nonce_map[$action] : false;
    }
    
    /**
     * 安全なAjaxレスポンス送信
     *
     * @param mixed $data レスポンスデータ
     * @param bool $success 成功かどうか
     * @param string $message メッセージ
     */
    public function send_ajax_response($data = null, $success = true, $message = '') {
        if ($success) {
            $response = array();
            
            if (!empty($message)) {
                $response['message'] = $message;
            }
            
            if ($data !== null) {
                $response['data'] = $data;
            }
            
            wp_send_json_success($response);
        } else {
            wp_send_json_error($message);
        }
    }
    
    /**
     * Ajax入力データのサニタイズ
     *
     * @param string $key POST配列のキー
     * @param string $type データタイプ（text, email, int, float, textarea, html）
     * @param mixed $default デフォルト値
     * @return mixed サニタイズされた値
     */
    public function sanitize_ajax_input($key, $type = 'text', $default = '') {
        if (!isset($_POST[$key])) {
            return $default;
        }
        
        $value = wp_unslash($_POST[$key]);
        
        switch ($type) {
            case 'int':
                return intval($value);
                
            case 'float':
                return floatval($value);
                
            case 'email':
                return sanitize_email($value);
                
            case 'textarea':
                return sanitize_textarea_field($value);
                
            case 'html':
                return wp_kses_post($value);
                
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * 登録されたハンドラー一覧取得
     *
     * @return array ハンドラー名配列
     */
    public function get_registered_handlers() {
        return $this->registered_handlers;
    }
    
    /**
     * Ajaxハンドラー存在チェック
     *
     * @param string $handler_name ハンドラー名
     * @return bool 存在するかどうか
     */
    public function handler_exists($handler_name) {
        return in_array($handler_name, $this->registered_handlers, true);
    }
    
    /**
     * nonce名設定の取得
     *
     * @param string $type nonce種別
     * @return string|false nonce名またはfalse
     */
    public function get_nonce_name($type) {
        return isset($this->nonce_names[$type]) ? $this->nonce_names[$type] : false;
    }
    
    /**
     * Ajaxエラーログ記録
     *
     * @param string $message エラーメッセージ
     * @param array $context 追加コンテキスト
     */
    public function log_ajax_error( $message, $context = array() ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $log_message = 'KTPWP Ajax Error: ' . $message;
            
            if ( ! empty( $context ) ) {
                $log_message .= ' | Context: ' . wp_json_encode( $context );
            }
            
            error_log( $log_message );
        }
    }
    
    /**
     * Ajax: 最新スタッフチャットメッセージ取得
     */
    public function ajax_get_latest_staff_chat() {
        try {
            // ログインチェック
            if (!is_user_logged_in()) {
                wp_send_json_error(__('ログインが必要です', 'ktpwp'));
                return;
            }
            
            // Nonce検証（_ajax_nonceパラメータで送信される）
            $nonce = $_POST['_ajax_nonce'] ?? '';
            if (!wp_verify_nonce($nonce, $this->nonce_names['staff_chat'])) {
                $this->log_ajax_error('Staff chat get messages nonce verification failed', array(
                    'received_nonce' => $nonce,
                    'expected_action' => $this->nonce_names['staff_chat']
                ));
                wp_send_json_error(__('セキュリティトークンが無効です', 'ktpwp'));
                return;
            }
            
            // パラメータの取得とサニタイズ
            $order_id = $this->sanitize_ajax_input('order_id', 'int');
            $last_time = $this->sanitize_ajax_input('last_time', 'text');
            
            if (empty($order_id)) {
                wp_send_json_error(__('注文IDが必要です', 'ktpwp'));
                return;
            }
            
            // 権限チェック
            if (!current_user_can('read')) {
                wp_send_json_error(__('権限がありません', 'ktpwp'));
                return;
            }
            
            // スタッフチャットクラスのインスタンス化
            if (!class_exists('KTPWP_Staff_Chat')) {
                require_once KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-staff-chat.php';
            }
            
            $staff_chat = new KTPWP_Staff_Chat();
            
            // 最新メッセージを取得
            $messages = $staff_chat->get_messages_after($order_id, $last_time);
            
            wp_send_json_success($messages);
            
        } catch (Exception $e) {
            $this->log_ajax_error('Exception during get latest staff chat', array(
                'message' => $e->getMessage(),
                'order_id' => $_POST['order_id'] ?? 'unknown',
            ));
            wp_send_json_error(__('メッセージの取得中にエラーが発生しました', 'ktpwp'));
        }
    }
    
    /**
     * Ajax: スタッフチャットメッセージ送信
     */
    public function ajax_send_staff_chat_message() {
        // デバッグログ開始
        error_log('KTPWP: ajax_send_staff_chat_message called');
        error_log('KTPWP: POST data: ' . print_r($_POST, true));
        error_log('KTPWP: User logged in: ' . (is_user_logged_in() ? 'yes' : 'no'));
        
        try {
            // ログインチェック
            if (!is_user_logged_in()) {
                error_log('KTPWP: User not logged in');
                wp_send_json_error(__('ログインが必要です', 'ktpwp'));
                return;
            }
            
            // Nonce検証（_ajax_nonceパラメータで送信される）
            $nonce = $_POST['_ajax_nonce'] ?? '';
            error_log('KTPWP: Received nonce: ' . $nonce);
            error_log('KTPWP: Expected nonce action: ' . $this->nonce_names['staff_chat']);
            
            if (!wp_verify_nonce($nonce, $this->nonce_names['staff_chat'])) {
                error_log('KTPWP: Nonce verification failed');
                $this->log_ajax_error('Staff chat nonce verification failed', array(
                    'received_nonce' => $nonce,
                    'expected_action' => $this->nonce_names['staff_chat']
                ));
                wp_send_json_error(__('セキュリティトークンが無効です', 'ktpwp'));
                return;
            }
            
            error_log('KTPWP: Nonce verification passed');
            
            // パラメータの取得とサニタイズ
            $order_id = $this->sanitize_ajax_input('order_id', 'int');
            $message = $this->sanitize_ajax_input('message', 'text');
            
            if (empty($order_id) || empty($message)) {
                wp_send_json_error(__('注文IDとメッセージが必要です', 'ktpwp'));
                return;
            }
            
            // 権限チェック
            if (!current_user_can('read')) {
                wp_send_json_error(__('権限がありません', 'ktpwp'));
                return;
            }
            
            // スタッフチャットクラスのインスタンス化
            if (!class_exists('KTPWP_Staff_Chat')) {
                require_once KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-staff-chat.php';
            }
            
            $staff_chat = new KTPWP_Staff_Chat();
            
            // メッセージを送信
            $result = $staff_chat->add_message($order_id, $message);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('メッセージを送信しました', 'ktpwp'),
                ));
            } else {
                wp_send_json_error(__('メッセージの送信に失敗しました', 'ktpwp'));
            }
            
        } catch (Exception $e) {
            $this->log_ajax_error('Exception during send staff chat message', array(
                'message' => $e->getMessage(),
                'order_id' => $_POST['order_id'] ?? 'unknown',
            ));
            wp_send_json_error(__('メッセージの送信中にエラーが発生しました', 'ktpwp'));
        }
    }
}
