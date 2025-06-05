<?php
/**
 * KTPWP ショートコード管理クラス
 *
 * @package KTPWP
 * @since 0.1.0
 */

// セキュリティ: 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ショートコード管理クラス
 */
class KTPWP_Shortcodes {
    
    /**
     * シングルトンインスタンス
     *
     * @var KTPWP_Shortcodes|null
     */
    private static $instance = null;
    
    /**
     * ユーザーログイン状況キャッシュ
     *
     * @var array
     */
    private $logged_in_users_cache = null;
    
    /**
     * 登録されたショートコード一覧
     *
     * @var array
     */
    private $registered_shortcodes = array();
    
    /**
     * シングルトンインスタンス取得
     *
     * @return KTPWP_Shortcodes
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
        // WordPressがプラグインを読み込んだ後にショートコードを登録
        add_action('plugins_loaded', array($this, 'register_shortcodes'), 20);
        
        // Ajax処理用フックの登録
        add_action('wp_ajax_get_logged_in_users', array($this, 'ajax_get_logged_in_users'));
        add_action('wp_ajax_nopriv_get_logged_in_users', array($this, 'ajax_get_logged_in_users'));
    }
    
    /**
     * ショートコード登録
     */
    public function register_shortcodes() {
        // メインショートコード（旧名）
        add_shortcode('kantanAllTab', array($this, 'render_all_tabs'));
        $this->registered_shortcodes[] = 'kantanAllTab';
        
        // メインショートコード（新名）
        add_shortcode('ktpwp_all_tab', array($this, 'render_all_tabs'));
        $this->registered_shortcodes[] = 'ktpwp_all_tab';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP Shortcodes: Registered shortcodes - ' . implode(', ', $this->registered_shortcodes));
        }
    }
    
    /**
     * 全タブショートコードの描画
     *
     * @param array $atts ショートコード属性
     * @return string 描画されたHTML
     */
    public function render_all_tabs($atts = array()) {
        // ログイン状態チェック
        if (!is_user_logged_in()) {
            return $this->render_login_error();
        }
        
        // 属性のデフォルト値設定
        $atts = shortcode_atts(array(
            'debug' => 'false',
            'cache' => 'true',
        ), $atts, 'ktpwp_all_tab');
        
        try {
            // 各種コンテンツの取得
            $header_content = $this->get_header_content();
            $tab_content = $this->get_tab_content();
            
            return $header_content . $tab_content;
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KTPWP Shortcode Error: ' . $e->getMessage());
            }
            return '<div class="ktpwp-error">' . esc_html__('エラーが発生しました。', 'ktpwp') . '</div>';
        }
    }
    
    /**
     * ヘッダーコンテンツ取得
     *
     * @return string ヘッダーHTML
     */
    private function get_header_content() {
        global $current_user;
        
        // 基本情報の取得
        $plugin_name = esc_html(KTPWP_PLUGIN_NAME);
        $plugin_version = esc_html(KTPWP_PLUGIN_VERSION);
        $icon_img = $this->get_plugin_icon();
        
        // ナビゲーション要素の生成
        $logged_in_users_html = $this->get_logged_in_users_display();
        $navigation_links = $this->get_navigation_links();
        
        // ヘッダーHTML構築
        $header_html = '<div class="ktp_header">';
        $header_html .= '<div class="parent">';
        $header_html .= '<div class="title">' . $icon_img . $plugin_name . '</div>';
        $header_html .= '<div class="version">v' . $plugin_version . '</div>';
        $header_html .= '</div>';
        $header_html .= '<div style="margin-left: auto; display: flex; align-items: center;">';
        $header_html .= $logged_in_users_html;
        $header_html .= $navigation_links;
        $header_html .= '</div>';
        $header_html .= '</div>';
        
        return $header_html;
    }
    
    /**
     * プラグインアイコン取得
     *
     * @return string アイコンIMGタグ
     */
    private function get_plugin_icon() {
        $icon_url = plugins_url('images/default/icon.png', KTPWP_PLUGIN_FILE);
        return '<img src="' . esc_url($icon_url) . '" style="height:40px;vertical-align:middle;margin-right:8px;position:relative;top:-5px;">';
    }
    
    /**
     * ログイン中ユーザー表示の取得
     *
     * @return string ユーザー表示HTML
     */
    private function get_logged_in_users_display() {
        global $current_user;
        
        // 厳密なログイン状態確認
        if (!is_user_logged_in() || !current_user_can('edit_posts') || !$current_user || $current_user->ID <= 0) {
            return '';
        }
        
        // セッション有効性確認
        $user_sessions = WP_Session_Tokens::get_instance($current_user->ID);
        if (!$user_sessions || empty($user_sessions->get_all())) {
            return '';
        }
        
        $nickname_esc = esc_attr($current_user->nickname);
        $avatar = get_avatar($current_user->ID, 32, '', '', array('class' => 'user_icon user_icon--current'));
        
        return '<strong><span title="' . $nickname_esc . '">' . $avatar . '</span></strong>';
    }
    
    /**
     * ナビゲーションリンク取得
     *
     * @return string ナビゲーションHTML
     */
    private function get_navigation_links() {
        global $current_user;
        
        // ログイン状態とセッション確認
        if (!is_user_logged_in() || !current_user_can('edit_posts') || !$current_user || $current_user->ID <= 0) {
            return '';
        }
        
        $user_sessions = WP_Session_Tokens::get_instance($current_user->ID);
        if (!$user_sessions || empty($user_sessions->get_all())) {
            return '';
        }
        
        // 各種リンクの生成
        $logout_url = esc_url(wp_logout_url());
        $current_page_id = get_queried_object_id();
        $update_url = esc_url(get_permalink($current_page_id));
        $activation_key = esc_html($this->check_activation_key());
        
        $links = array();
        $links[] = '<a href="' . $logout_url . '">' . esc_html__('ログアウト', 'ktpwp') . '</a>';
        $links[] = '<a href="' . $update_url . '">' . esc_html__('更新', 'ktpwp') . '</a>';
        
        if (!empty($activation_key)) {
            $links[] = $activation_key;
        }
        
        $reference_instance = KTPWP_Plugin_Reference::get_instance();
        $links[] = $reference_instance->get_reference_link();
        
        return '　' . implode('　', $links);
    }
    
    /**
     * アクティベーションキー確認
     *
     * @return string アクティベーションキー状態
     */
    private function check_activation_key() {
        $activation_key = get_site_option('ktp_activation_key');
        return empty($activation_key) ? '' : '';
    }
    
    /**
     * タブコンテンツ取得
     *
     * @return string タブHTML
     */
    private function get_tab_content() {
        $tab_name = $this->get_current_tab();
        
        // 各タブコンテンツの初期化
        $tab_contents = array(
            'list' => '',
            'order' => '',
            'client' => '',
            'service' => '',
            'supplier' => '',
            'report' => '',
            'setting' => ''
        );
        
        // 現在のタブに応じてコンテンツを生成
        switch ($tab_name) {
            case 'list':
                $tab_contents['list'] = $this->get_list_content($tab_name);
                break;
                
            case 'order':
                $tab_contents['order'] = $this->get_order_content($tab_name);
                break;
                
            case 'client':
                $tab_contents['client'] = $this->get_client_content($tab_name);
                break;
                
            case 'service':
                $tab_contents['service'] = $this->get_service_content($tab_name);
                break;
                
            case 'supplier':
                $tab_contents['supplier'] = $this->get_supplier_content($tab_name);
                break;
                
            case 'report':
                $tab_contents['report'] = $this->get_report_content($tab_name);
                break;
                
            case 'setting':
                $tab_contents['setting'] = $this->get_setting_content($tab_name);
                break;
                
            default:
                // デフォルトでリストタブを表示
                $tab_name = 'list';
                $tab_contents['list'] = $this->get_list_content($tab_name);
                break;
        }
        
        // タブビューの生成
        return $this->render_tabs_view(
            $tab_contents['list'],
            $tab_contents['order'],
            $tab_contents['client'],
            $tab_contents['service'],
            $tab_contents['supplier'],
            $tab_contents['report'],
            $tab_contents['setting']
        );
    }
    
    /**
     * 現在のタブ名取得
     *
     * @return string タブ名
     */
    private function get_current_tab() {
        $tab_name = isset($_GET['tab_name']) ? sanitize_text_field($_GET['tab_name']) : 'list';
        
        // 許可されたタブ名のホワイトリスト
        $allowed_tabs = array('list', 'order', 'client', 'service', 'supplier', 'report', 'setting');
        
        if (!in_array($tab_name, $allowed_tabs, true)) {
            $tab_name = 'list';
        }
        
        return $tab_name;
    }
    
    /**
     * リストコンテンツ取得
     *
     * @param string $tab_name タブ名
     * @return string コンテンツHTML
     */
    private function get_list_content($tab_name) {
        if (!class_exists('Kantan_List_Class')) {
            $this->load_required_class('class-tab-list.php');
        }
        
        if (class_exists('Kantan_List_Class')) {
            $list = new Kantan_List_Class();
            return $list->List_Tab_View($tab_name);
        }
        
        return $this->get_error_content('Kantan_List_Class');
    }
    
    /**
     * 受注コンテンツ取得
     *
     * @param string $tab_name タブ名
     * @return string コンテンツHTML
     */
    private function get_order_content($tab_name) {
        if (!class_exists('Kntan_Order_Class')) {
            $this->load_required_class('class-tab-order.php');
        }
        
        if (class_exists('Kntan_Order_Class')) {
            $order = new Kntan_Order_Class();
            $content = $order->Order_Tab_View($tab_name);
            return $content ?? '';
        }
        
        return $this->get_error_content('Kntan_Order_Class');
    }
    
    /**
     * 顧客コンテンツ取得
     *
     * @param string $tab_name タブ名
     * @return string コンテンツHTML
     */
    private function get_client_content($tab_name) {
        if (!class_exists('Kntan_Client_Class')) {
            $this->load_required_class('class-tab-client.php');
        }
        
        if (class_exists('Kntan_Client_Class')) {
            $client = new Kntan_Client_Class();
            
            // 管理者権限がある場合のみテーブル操作
            if (current_user_can('manage_options')) {
                $client->Create_Table($tab_name);
                $client->Update_Table($tab_name);
            }
            
            return $client->View_Table($tab_name);
        }
        
        return $this->get_error_content('Kntan_Client_Class');
    }
    
    /**
     * サービスコンテンツ取得
     *
     * @param string $tab_name タブ名
     * @return string コンテンツHTML
     */
    private function get_service_content($tab_name) {
        if (!class_exists('Kntan_Service_Class')) {
            $this->load_required_class('class-tab-service.php');
        }
        
        if (class_exists('Kntan_Service_Class')) {
            $service = new Kntan_Service_Class();
            
            // 管理者権限がある場合のみテーブル操作
            if (current_user_can('manage_options')) {
                $service->Create_Table($tab_name);
                $service->Update_Table($tab_name);
            }
            
            return $service->View_Table($tab_name);
        }
        
        return $this->get_error_content('Kntan_Service_Class');
    }
    
    /**
     * 仕入先コンテンツ取得
     *
     * @param string $tab_name タブ名
     * @return string コンテンツHTML
     */
    private function get_supplier_content($tab_name) {
        if (!class_exists('Kantan_Supplier_Class')) {
            $this->load_required_class('class-tab-supplier.php');
        }
        
        if (class_exists('Kantan_Supplier_Class')) {
            $supplier = new Kantan_Supplier_Class();
            
            // 管理者権限がある場合のみテーブル操作
            if (current_user_can('manage_options')) {
                $supplier->Create_Table($tab_name);
                $supplier->Update_Table($tab_name);
            }
            
            return $supplier->View_Table($tab_name);
        }
        
        return $this->get_error_content('Kantan_Supplier_Class');
    }
    
    /**
     * レポートコンテンツ取得
     *
     * @param string $tab_name タブ名
     * @return string コンテンツHTML
     */
    private function get_report_content($tab_name) {
        if (!class_exists('KTPWP_Report_Class')) {
            $this->load_required_class('class-tab-report.php');
        }
        
        if (class_exists('KTPWP_Report_Class')) {
            $report = new KTPWP_Report_Class();
            return $report->Report_Tab_View($tab_name);
        }
        
        return $this->get_error_content('KTPWP_Report_Class');
    }
    
    /**
     * 設定コンテンツ取得
     *
     * @param string $tab_name タブ名
     * @return string コンテンツHTML
     */
    private function get_setting_content($tab_name) {
        if (!class_exists('KTPWP_Setting_Class')) {
            $this->load_required_class('class-tab-setting.php');
        }
        
        if (class_exists('KTPWP_Setting_Class')) {
            $setting = new KTPWP_Setting_Class();
            
            // 管理者権限がある場合のみテーブル操作
            if (current_user_can('manage_options')) {
                $setting->Create_Table($tab_name);
            }
            
            return $setting->Setting_Tab_View($tab_name);
        }
        
        return $this->get_error_content('KTPWP_Setting_Class');
    }
    
    /**
     * 必要なクラスファイルを読み込み
     *
     * @param string $filename ファイル名
     */
    private function load_required_class($filename) {
        $file_path = KTPWP_PLUGIN_DIR . 'includes/' . $filename;
        
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KTPWP Error: Required class file not found - ' . $filename);
            }
        }
    }
    
    /**
     * エラーコンテンツ取得
     *
     * @param string $class_name クラス名
     * @return string エラーHTML
     */
    private function get_error_content($class_name) {
        $message = sprintf(
            esc_html__('クラス %s が見つかりません。', 'ktpwp'),
            esc_html($class_name)
        );
        
        return '<div class="ktpwp-error">' . $message . '</div>';
    }
    
    /**
     * タブビューレンダリング
     *
     * @param string $list_content リストコンテンツ
     * @param string $order_content 受注コンテンツ
     * @param string $client_content 顧客コンテンツ
     * @param string $service_content サービスコンテンツ
     * @param string $supplier_content 仕入先コンテンツ
     * @param string $report_content レポートコンテンツ
     * @param string $setting_content 設定コンテンツ
     * @return string タブビューHTML
     */
    private function render_tabs_view($list_content, $order_content, $client_content, $service_content, $supplier_content, $report_content, $setting_content) {
        if (!class_exists('view_tabs_Class')) {
            $this->load_required_class('class-view-tab.php');
        }
        
        if (class_exists('view_tabs_Class')) {
            $view = new view_tabs_Class();
            return $view->TabsView($list_content, $order_content, $client_content, $service_content, $supplier_content, $report_content, $setting_content);
        }
        
        return $this->get_error_content('view_tabs_Class');
    }
    
    /**
     * ログインエラー表示
     *
     * @return string ログインエラーHTML
     */
    private function render_login_error() {
        if (!class_exists('Kantan_Login_Error')) {
            $this->load_required_class('class-login-error.php');
        }
        
        if (class_exists('Kantan_Login_Error')) {
            $login_error = new Kantan_Login_Error();
            return $login_error->Error_View();
        }
        
        return '<div class="ktpwp-login-error">' . esc_html__('ログインが必要です。', 'ktpwp') . '</div>';
    }
    
    /**
     * Ajax: ログイン中ユーザー取得
     */
    public function ajax_get_logged_in_users() {
        // キャッシュがある場合は使用
        if ($this->logged_in_users_cache !== null) {
            wp_send_json($this->logged_in_users_cache);
        }
        
        $logged_in_users = get_users(array(
            'meta_key' => 'session_tokens',
            'meta_compare' => 'EXISTS'
        ));
        
        $users_names = array();
        foreach ($logged_in_users as $user) {
            $users_names[] = esc_html($user->nickname) . 'さん';
        }
        
        // キャッシュに保存
        $this->logged_in_users_cache = $users_names;
        
        wp_send_json($users_names);
    }
    
    /**
     * 登録済みショートコード一覧取得
     *
     * @return array ショートコード名配列
     */
    public function get_registered_shortcodes() {
        return $this->registered_shortcodes;
    }
    
    /**
     * ショートコード存在チェック
     *
     * @param string $shortcode_name ショートコード名
     * @return bool 存在するかどうか
     */
    public function shortcode_exists($shortcode_name) {
        return in_array($shortcode_name, $this->registered_shortcodes, true);
    }
    
    /**
     * デストラクタ
     */
    public function __destruct() {
        // キャッシュクリア（必要に応じて）
        $this->logged_in_users_cache = null;
    }
}
