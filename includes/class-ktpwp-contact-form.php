<?php
/**
 * KTPWP Contact Form 7連携クラス
 *
 * @package KTPWP
 * @since 0.1.0
 */

// セキュリティ: 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Contact Form 7連携クラス
 */
class KTPWP_Contact_Form {
    
    /**
     * シングルトンインスタンス
     *
     * @var KTPWP_Contact_Form|null
     */
    private static $instance = null;
    
    /**
     * フィールドマッピング設定
     *
     * @var array
     */
    private $field_mapping = array();
    
    /**
     * デフォルト値設定
     *
     * @var array
     */
    private $default_values = array();
    
    /**
     * シングルトンインスタンス取得
     *
     * @return KTPWP_Contact_Form
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
        $this->init_config();
        $this->init_hooks();
    }
    
    /**
     * 設定初期化
     */
    private function init_config() {
        // フィールドマッピング設定
        $this->field_mapping = array(
            'company_name' => array('your_company_name', 'company-name'),
            'name' => array('your-name'),
            'email' => array('your-email'),
            'subject' => array('your-subject'),
            'message' => array('your-message'),
            'category' => array('select-996'),
        );
        
        // デフォルト値設定
        $this->default_values = array(
            'client_status' => '対象',
            'project_name' => __('お問い合わせの件', 'ktpwp'),
            'progress' => 1, // "受付中"
            'user_name' => '',
        );
    }
    
    /**
     * フック初期化
     */
    private function init_hooks() {
        // Contact Form 7が有効な場合のみフックを追加
        if (class_exists('WPCF7_ContactForm')) {
            add_action('wpcf7_mail_sent', array($this, 'capture_contact_form_data'));
        }
    }
    
    /**
     * Contact Form 7送信データをキャプチャ
     *
     * @param WPCF7_ContactForm $contact_form Contact Form 7のフォームオブジェクト
     */
    public function capture_contact_form_data($contact_form) {
        $submission = WPCF7_Submission::get_instance();
        
        if (!$submission) {
            return;
        }
        
        $posted_data = $submission->get_posted_data();
        
        if (empty($posted_data)) {
            return;
        }
        
        // データを処理してデータベースに保存
        $client_data = $this->prepare_client_data($posted_data);
        $client_id = $this->save_client_data($client_data);
        
        if ($client_id) {
            // 受注データも作成
            $order_data = $this->prepare_order_data($posted_data, $client_id);
            $this->save_order_data($order_data);
            
            // クッキー設定
            $this->set_client_cookie($client_id);
        }
    }
    
    /**
     * 顧客データの準備
     *
     * @param array $posted_data 送信されたデータ
     * @return array 準備された顧客データ
     */
    private function prepare_client_data($posted_data) {
        // フィールドマッピングに基づいてデータを取得
        $data = array();
        
        foreach ($this->field_mapping as $key => $field_names) {
            $value = $this->get_field_value($posted_data, $field_names);
            
            switch ($key) {
                case 'company_name':
                case 'name':
                case 'subject':
                case 'category':
                    $data[$key] = sanitize_text_field($value);
                    break;
                    
                case 'email':
                    $data[$key] = sanitize_email($value);
                    break;
                    
                case 'message':
                    $data[$key] = sanitize_textarea_field($value);
                    break;
            }
        }
        
        // メモの作成
        $memo = $this->create_memo($data['subject'] ?? '', $data['message'] ?? '');
        
        return array(
            'company_name' => $data['company_name'] ?? '',
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'memo' => $memo,
            'time' => current_time('mysql'),
            'client_status' => $this->default_values['client_status'],
        );
    }
    
    /**
     * 受注データの準備
     *
     * @param array $posted_data 送信されたデータ
     * @param int $client_id 顧客ID
     * @return array 準備された受注データ
     */
    private function prepare_order_data($posted_data, $client_id) {
        $customer_name = $this->get_field_value($posted_data, $this->field_mapping['name']);
        
        return array(
            'client_id' => $client_id,
            'customer_name' => sanitize_text_field($customer_name),
            'project_name' => $this->default_values['project_name'],
            'progress' => $this->default_values['progress'],
            'user_name' => $this->default_values['user_name'],
            'time' => time(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );
    }
    
    /**
     * フィールド値の取得
     *
     * @param array $posted_data 送信されたデータ
     * @param array $field_names フィールド名配列
     * @return string
     */
    private function get_field_value($posted_data, $field_names) {
        foreach ($field_names as $field_name) {
            if (isset($posted_data[$field_name])) {
                return $posted_data[$field_name];
            }
        }
        return '';
    }
    
    /**
     * メモの作成
     *
     * @param string $subject 件名
     * @param string $message メッセージ
     * @return string
     */
    private function create_memo($subject, $message) {
        $memo = '';
        
        if (!empty($subject)) {
            $memo .= __('件名:', 'ktpwp') . ' ' . $subject;
        }
        
        if (!empty($message)) {
            if (!empty($memo)) {
                $memo .= "\n";
            }
            $memo .= __('メッセージ本文:', 'ktpwp') . ' ' . $message;
        }
        
        return $memo;
    }
    
    /**
     * 顧客データの保存
     *
     * @param array $client_data 顧客データ
     * @return int|false 挿入された顧客ID、失敗時はfalse
     */
    private function save_client_data($client_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ktp_client';
        
        $format = array(
            '%s', // company_name
            '%s', // name
            '%s', // email
            '%s', // memo
            '%s', // time
            '%s', // client_status
        );
        
        $result = $wpdb->insert($table_name, $client_data, $format);
        
        if ($result === false) {
            $this->log_error('Failed to insert client data', array(
                'query' => $wpdb->last_query,
                'error' => $wpdb->last_error,
                'data' => $client_data
            ));
            return false;
        }
        
        $client_id = $wpdb->insert_id;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP Contact Form: Client data saved with ID ' . $client_id);
        }
        
        return $client_id;
    }
    
    /**
     * 受注データの保存
     *
     * @param array $order_data 受注データ
     * @return int|false 挿入された受注ID、失敗時はfalse
     */
    private function save_order_data($order_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ktp_order';
        
        $format = array(
            '%d', // client_id
            '%s', // customer_name
            '%s', // project_name
            '%d', // progress
            '%s', // user_name
            '%d', // time
            '%s', // created_at
            '%s', // updated_at
        );
        
        $result = $wpdb->insert($table_name, $order_data, $format);
        
        if ($result === false) {
            $this->log_error('Failed to insert order data', array(
                'query' => $wpdb->last_query,
                'error' => $wpdb->last_error,
                'data' => $order_data
            ));
            return false;
        }
        
        $order_id = $wpdb->insert_id;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP Contact Form: Order data saved with ID ' . $order_id);
        }
        
        return $order_id;
    }
    
    /**
     * クライアントクッキーの設定
     *
     * @param int $client_id 顧客ID
     */
    private function set_client_cookie($client_id) {
        $cookie_name = 'ktp_client_id';
        
        if (!headers_sent()) {
            setcookie($cookie_name, $client_id, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KTPWP Contact Form: Client cookie set for ID ' . $client_id);
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KTPWP Contact Form: Failed to set cookie - headers already sent');
            }
        }
    }
    
    /**
     * フィールドマッピングの更新
     *
     * @param array $mapping 新しいマッピング設定
     */
    public function update_field_mapping($mapping) {
        $this->field_mapping = array_merge($this->field_mapping, $mapping);
    }
    
    /**
     * デフォルト値の更新
     *
     * @param array $defaults 新しいデフォルト値
     */
    public function update_default_values($defaults) {
        $this->default_values = array_merge($this->default_values, $defaults);
    }
    
    /**
     * フィールドマッピング取得
     *
     * @return array
     */
    public function get_field_mapping() {
        return $this->field_mapping;
    }
    
    /**
     * デフォルト値取得
     *
     * @return array
     */
    public function get_default_values() {
        return $this->default_values;
    }
    
    /**
     * Contact Form 7の有効性確認
     *
     * @return bool
     */
    public function is_contact_form_7_active() {
        return class_exists('WPCF7_ContactForm');
    }
    
    /**
     * エラーログ記録
     *
     * @param string $message エラーメッセージ
     * @param array $context 追加コンテキスト
     */
    private function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = 'KTPWP Contact Form Error: ' . $message;
            
            if (!empty($context)) {
                $log_message .= ' | Context: ' . wp_json_encode($context);
            }
            
            error_log($log_message);
        }
    }
    
    /**
     * データベーステーブルの存在確認
     *
     * @param string $table_name テーブル名
     * @return bool
     */
    private function table_exists($table_name) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        );
        
        return $wpdb->get_var($query) === $table_name;
    }
    
    /**
     * 必要なテーブルの存在確認
     *
     * @return bool
     */
    public function check_required_tables() {
        global $wpdb;
        
        $required_tables = array(
            $wpdb->prefix . 'ktp_client',
            $wpdb->prefix . 'ktp_order'
        );
        
        foreach ($required_tables as $table) {
            if (!$this->table_exists($table)) {
                $this->log_error('Required table not found: ' . $table);
                return false;
            }
        }
        
        return true;
    }
}
