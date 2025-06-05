<?php
/**
 * Plugin Name: KTPWP
 * Plugin URI: https://ktpwp.com/
 * Description: ショートコード[ktpwp_all_tab]を固定ページに入れてください。商品・顧客・案件・仕入れ先・各種設定・レポートのタブが使えます。
 * Version: beta
 * Author: Kantan Pro
 * Author URI: https://ktpwp.com/blog/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ktpwp
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 *
 * @package KTPWP
 */

// セキュリティ: 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// プラグイン定数定義
if ( ! defined( 'KTPWP_PLUGIN_VERSION' ) ) {
    define( 'KTPWP_PLUGIN_VERSION', 'beta' );
}
if ( ! defined( 'KTPWP_PLUGIN_NAME' ) ) {
    define( 'KTPWP_PLUGIN_NAME', 'KTPWP' );
}
if ( ! defined( 'KTPWP_PLUGIN_DESCRIPTION' ) ) {
    // 翻訳読み込み警告を回避するため、initアクションで設定
    define( 'KTPWP_PLUGIN_DESCRIPTION', 'ショートコード[ktpwp_all_tab]を固定ページに入れてください。商品・顧客・案件・仕入れ先・各種設定・レポートのタブが使えます。' );
}
if ( ! defined( 'KTPWP_PLUGIN_FILE' ) ) {
    define( 'KTPWP_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'MY_PLUGIN_VERSION' ) ) {
    define( 'MY_PLUGIN_VERSION', KTPWP_PLUGIN_VERSION );
}
if ( ! defined( 'MY_PLUGIN_PATH' ) ) {
    define( 'MY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'MY_PLUGIN_URL' ) ) {
    define( 'MY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * プラグインクラスの自動読み込み
 */
function ktpwp_autoload_classes() {
    $classes = array(
        'Kntan_Client_Class'    => 'includes/class-tab-client.php',
        'Kntan_Service_Class'   => 'includes/class-tab-service.php',
        'KTPWP_Supplier_Class'  => 'includes/class-tab-supplier.php',
        'KTPWP_Supplier_Security' => 'includes/class-supplier-security.php',
        'KTPWP_Supplier_Data'   => 'includes/class-supplier-data.php',
        'KTPWP_Report_Class'    => 'includes/class-tab-report.php',
        'Kntan_Order_Class'     => 'includes/class-tab-order.php',
        'KTPWP_Setting_Class'   => 'includes/class-tab-setting.php',
        'KTPWP_Plugin_Reference' => 'includes/class-plugin-reference.php',
        // 新しいクラス構造
        'KTPWP_Order'           => 'includes/class-ktpwp-order.php',
        'KTPWP_Order_Items'     => 'includes/class-ktpwp-order-items.php',
        'KTPWP_Order_UI'        => 'includes/class-ktpwp-order-ui.php',
        'KTPWP_Staff_Chat'      => 'includes/class-ktpwp-staff-chat.php',
        // クライアント管理の新クラス
        'KTPWP_Client_DB'       => 'includes/class-ktpwp-client-db.php',
        'KTPWP_Client_UI'       => 'includes/class-ktpwp-client-ui.php',
    );

    foreach ( $classes as $class_name => $file_path ) {
        if ( ! class_exists( $class_name ) ) {
            $full_path = MY_PLUGIN_PATH . $file_path;
            if ( file_exists( $full_path ) ) {
                require_once $full_path;
            }
        }
    }
}

// クラスの読み込み実行
ktpwp_autoload_classes();

// プラグインリファレンス機能の初期化
if ( class_exists( 'KTPWP_Plugin_Reference' ) ) {
    KTPWP_Plugin_Reference::get_instance();
}

/**
 * セキュリティ強化: REST API制限 & HTTPヘッダー追加
 */

/**
 * 未認証ユーザーのREST APIアクセス制限
 *
 * @param WP_Error|null|true $result Authentication result.
 * @return WP_Error|null|true
 */
function ktpwp_restrict_rest_api( $result ) {
    if ( ! empty( $result ) ) {
        return $result;
    }

    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_forbidden',
            __( 'REST APIはログインユーザーのみ利用可能です。', 'ktpwp' ),
            array( 'status' => 403 )
        );
    }

    return $result;
}
add_filter( 'rest_authentication_errors', 'ktpwp_restrict_rest_api' );

/**
 * HTTPセキュリティヘッダー追加
 */
function ktpwp_add_security_headers() {
    // 管理画面でのみ適用
    if ( is_admin() && ! wp_doing_ajax() ) {
        // クリックジャッキング防止
        if ( ! headers_sent() ) {
            header( 'X-Frame-Options: SAMEORIGIN' );
            // XSS対策
            header( 'X-Content-Type-Options: nosniff' );
            // Referrer情報制御
            header( 'Referrer-Policy: no-referrer-when-downgrade' );
        }
    }
}
add_action( 'admin_init', 'ktpwp_add_security_headers' );

// プラグインのアクティベーション時の処理を登録
// register_activation_hook(KTPWP_PLUGIN_FILE, 'ktp_table_setup'); // テーブル作成処理
register_activation_hook(KTPWP_PLUGIN_FILE, array('KTP_Settings', 'activate')); // 設定クラスのアクティベート処理

// プラグインの有効化時にデバッグログを追加
add_action( 'init', function() {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    }
} );

// 翻訳ファイルの読み込み（WordPressガイドラインに準拠）
function ktpwp_load_textdomain() {
    load_plugin_textdomain( 'ktpwp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'ktpwp_load_textdomain' );

// リダイレクト処理クラス
class KTPWP_Redirect {
    
    public function __construct() {
        add_action('template_redirect', array($this, 'handle_redirect'));
        add_filter('post_link', array($this, 'custom_post_link'), 10, 2);
        add_filter('page_link', array($this, 'custom_page_link'), 10, 2);
    }

    public function handle_redirect() {
        // デバッグ用ログ
        
        // ショートコードが含まれるページの場合はリダイレクトしない
        if (isset($_GET['tab_name']) || $this->has_ktpwp_shortcode()) {
            return;
        }
        
        if (is_single() || is_page()) {
            $post = get_queried_object();
            
            if ($post && $this->should_redirect($post)) {
                $external_url = $this->get_external_url($post);
                if ($external_url) {
                    // 外部リダイレクト先の安全性を検証（ホワイトリスト方式）
                    $allowed_hosts = [
                        'ktpwp.com',
                        parse_url(home_url(), PHP_URL_HOST)
                    ];
                    $parsed = wp_parse_url($external_url);
                    $host = isset($parsed['host']) ? $parsed['host'] : '';
                    if (in_array($host, $allowed_hosts, true)) {
                        $clean_external_url = $parsed['scheme'] . '://' . $host . (isset($parsed['path']) ? $parsed['path'] : '');
                        wp_redirect($clean_external_url, 301);
                        exit;
                    } else {
                    }
                }
            }
        }
    }

    /**
     * 現在のページにKTPWPショートコードが含まれているかチェック
     */
    private function has_ktpwp_shortcode() {
        $post = get_queried_object();
        if (!$post || !isset($post->post_content)) {
            return false;
        }
        
        return (
            has_shortcode($post->post_content, 'kantanAllTab') ||
            has_shortcode($post->post_content, 'ktpwp_all_tab')
        );
    }

    /**
     * リダイレクト対象かどうかを判定
     */
    private function should_redirect($post) {
        if (!$post) {
            return false;
        }

        // ショートコードが含まれるページの場合はリダイレクトしない
        if ($this->has_ktpwp_shortcode()) {
            return false;
        }
        
        // KTPWPのクエリパラメータがある場合はリダイレクトしない
        if (isset($_GET['tab_name']) || isset($_GET['from_client']) || isset($_GET['order_id'])) {
            return false;
        }

        // external_urlが設定されている投稿のみリダイレクト対象とする
        $external_url = get_post_meta($post->ID, 'external_url', true);
        if (!empty($external_url)) {
            return true;
        }

        // カスタム投稿タイプ「blog」で、特定の条件を満たす場合のみ
        if ($post->post_type === 'blog') {
            // 特定のスラッグやタイトルの場合のみリダイレクト
            $redirect_slugs = array('redirect-to-ktpwp', 'external-link');
            return in_array($post->post_name, $redirect_slugs);
        }

        return false;
    }

    /**
     * 外部URLを取得（クエリパラメータなし）
     */
    private function get_external_url($post) {
        if (!$post) {
            return false;
        }

        $external_url = get_post_meta($post->ID, 'external_url', true);
        
        if (empty($external_url)) {
            // デフォルトのベースURL
            $base_url = 'https://ktpwp.com/blog/';
            
            if ($post->post_type === 'blog') {
                $external_url = $base_url;
            } elseif ($post->post_type === 'post') {
                $categories = wp_get_post_categories($post->ID, array('fields' => 'slugs'));
                
                if (in_array('blog', $categories)) {
                    $external_url = $base_url;
                } elseif (in_array('news', $categories)) {
                    $external_url = $base_url . 'news/';
                } elseif (in_array('column', $categories)) {
                    $external_url = $base_url . 'column/';
                }
            }
        }
        
        // URLからクエリパラメータを除去
        if ($external_url) {
            $external_url = strtok($external_url, '?');
        }
        
        return $external_url;
    }

    public function custom_post_link($permalink, $post) {
        if ($post->post_type === 'blog') {
            $external_url = $this->get_external_url($post);
            if ($external_url) {
                return $external_url;
            }
        }

        if ($post->post_type === 'post') {
            $categories = wp_get_post_categories($post->ID, array('fields' => 'slugs'));
            $redirect_categories = array('blog', 'news', 'column');
            
            if (!empty(array_intersect($categories, $redirect_categories))) {
                $external_url = $this->get_external_url($post);
                if ($external_url) {
                    return $external_url;
                }
            }
        }

        return $permalink;
    }

    public function custom_page_link($permalink, $post_id) {
        $post = get_post($post_id);
        
        if ($post && $this->should_redirect($post)) {
            $external_url = $this->get_external_url($post);
            if ($external_url) {
                return $external_url;
            }
        }

        return $permalink;
    }
}

// POSTパラメータをGETパラメータに変換する処理
function ktpwp_handle_form_redirect() {
    // 特定のPOSTパラメータがある場合、GETパラメータに変換
    if (isset($_POST['tab_name']) && $_POST['tab_name'] === 'order' && isset($_POST['from_client'])) {

        // 厳格なサニタイズ
        $tab_name = sanitize_text_field($_POST['tab_name']);
        $from_client = sanitize_text_field($_POST['from_client']);
        $redirect_params = [
            'tab_name' => $tab_name,
            'from_client' => $from_client
        ];

        if (isset($_POST['customer_name'])) {
            $redirect_params['customer_name'] = sanitize_text_field($_POST['customer_name']);
        }
        if (isset($_POST['user_name'])) {
            $redirect_params['user_name'] = sanitize_text_field($_POST['user_name']);
        }

        // クライアントIDの取得（数値のみ許可）
        $client_id = 0;
        if (isset($_POST['client_id'])) {
            $client_id = intval($_POST['client_id']);
            if ($client_id > 0) {
                $redirect_params['client_id'] = $client_id;
            }
        }

        // 現在のURLからKTPWPパラメータを除去してクリーンなベースURLを作成
        $current_url = add_query_arg(NULL, NULL);
        $clean_url = remove_query_arg([
            'tab_name', 'from_client', 'customer_name', 'user_name', 'client_id',
            'order_id', 'delete_order', 'data_id', 'view_mode', 'query_post'
        ], $current_url);

        // 新しいパラメータを追加してリダイレクト
        $redirect_url = add_query_arg($redirect_params, $clean_url);

        // デバッグ用：リダイレクトURLをログに記録

        // リダイレクト実行
        wp_redirect($redirect_url, 302);
        exit;
    }
    
    // 受注書削除処理のPOSTパラメータをGETに変換 - 削除処理の問題を修正するため無効化
    // 削除処理はクラス内で直接POSTで処理する
    /*
    if (isset($_POST['delete_order']) && isset($_POST['order_id'])) {

        // 厳格なサニタイズ
        $delete_order = sanitize_text_field($_POST['delete_order']);
        $order_id = intval($_POST['order_id']);
        $redirect_params = [
            'tab_name' => 'order',
            'delete_order' => $delete_order,
            'order_id' => $order_id > 0 ? $order_id : ''
        ];

        // クリーンなベースURLを作成
        $current_url = add_query_arg(NULL, NULL);
        $clean_url = remove_query_arg([
            'tab_name', 'from_client', 'customer_name', 'user_name', 'client_id',
            'order_id', 'delete_order', 'data_id', 'view_mode', 'query_post'
        ], $current_url);

        // リダイレクト実行
        $redirect_url = add_query_arg($redirect_params, $clean_url);
        wp_redirect($redirect_url, 302);
        exit;
    }
    */
}

// WordPress初期化時に処理を実行（より早いタイミングで実行）
add_action('wp_loaded', 'ktpwp_handle_form_redirect', 1); // コメントアウト解除

// リダイレクト処理を初期化
// new KTPWP_Redirect(); // This line is duplicated, already commented out above.


// ファイルをインクルード
// アクティベーションフックのために class-ktp-settings.php は常にインクルード
if (file_exists(MY_PLUGIN_PATH . 'includes/class-ktp-settings.php')) {
    include_once MY_PLUGIN_PATH . 'includes/class-ktp-settings.php';
} else {
    add_action( 'admin_notices', function() {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'KTPWP Critical Error: includes/class-ktp-settings.php not found.' );
        }
    } );
}

add_action( 'plugins_loaded', 'KTPWP_Index' );

function ktpwp_scripts_and_styles() {
    wp_enqueue_script( 'ktp-js', plugins_url( 'js/ktp-js.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );

    // デバッグモードの設定（WP_DEBUGまたは開発環境でのみ有効）
    $debug_mode = (defined('WP_DEBUG') && WP_DEBUG) || (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);
    wp_add_inline_script('ktp-js', 'var ktpwpDebugMode = ' . json_encode($debug_mode) . ';');

    // コスト項目トグル用の国際化ラベルをJSに渡す
    wp_add_inline_script('ktp-js', 'var ktpwpCostShowLabel = ' . json_encode(esc_html__('表示', 'ktpwp')) . ';');
    wp_add_inline_script('ktp-js', 'var ktpwpCostHideLabel = ' . json_encode(esc_html__('非表示', 'ktpwp')) . ';');
    wp_add_inline_script('ktp-js', 'var ktpwpStaffChatShowLabel = ' . json_encode(esc_html__('表示', 'ktpwp')) . ';');
    wp_add_inline_script('ktp-js', 'var ktpwpStaffChatHideLabel = ' . json_encode(esc_html__('非表示', 'ktpwp')) . ';');

    wp_register_style('ktp-css', plugins_url('css/styles.css', __FILE__), array(), '1.0.3', 'all');
    wp_enqueue_style('ktp-css');
    // 進捗プルダウン用のスタイルシートを追加
    wp_enqueue_style('ktp-progress-select', plugins_url('css/progress-select.css', __FILE__), array('ktp-css'), '1.0.0', 'all');
    // 設定タブ用のスタイルシートを追加
    wp_enqueue_style('ktp-setting-tab', plugins_url('css/ktp-setting-tab.css', __FILE__), array('ktp-css'), '1.0.0', 'all');
    
    // Material Symbols アイコンフォントをプリロードとして読み込み
    wp_enqueue_style('material-symbols-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0', array(), '1.0.0', 'all');
    
    // Google Fontsのプリロード設定
    add_action('wp_head', function() {
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
    }, 1);
    wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js', array(), '3.5.1', true);
    wp_enqueue_script('ktp-order-inline-projectname', plugins_url('js/ktp-order-inline-projectname.js', __FILE__), array('jquery'), '1.0.0', true);
    // Nonceをjsに渡す（案件名インライン編集用）
    if (current_user_can('manage_options')) {
        wp_add_inline_script('ktp-order-inline-projectname', 'var ktpwp_inline_edit_nonce = ' . json_encode(array(
            'nonce' => wp_create_nonce('ktp_update_project_name')
        )) . ';');
    }

    // ajaxurl をフロントエンドに渡す
    wp_add_inline_script('ktp-js', 'var ktp_ajax_object = ' . json_encode(array('ajax_url' => admin_url('admin-ajax.php'))) . ';');

    // Ajax nonceを追加
    wp_add_inline_script('ktp-invoice-items', 'var ktp_ajax_nonce = ' . json_encode(wp_create_nonce('ktp_ajax_nonce')) . ';');
    wp_add_inline_script('ktp-cost-items', 'var ktp_ajax_nonce = ' . json_encode(wp_create_nonce('ktp_ajax_nonce')) . ';');

    // ajaxurlをJavaScriptで利用可能にする
    wp_add_inline_script('ktp-invoice-items', 'var ajaxurl = ' . json_encode(admin_url('admin-ajax.php')) . ';');
    wp_add_inline_script('ktp-cost-items', 'var ajaxurl = ' . json_encode(admin_url('admin-ajax.php')) . ';');
}
add_action( 'wp_enqueue_scripts', 'ktpwp_scripts_and_styles' );
add_action( 'admin_enqueue_scripts', 'ktpwp_scripts_and_styles' );

/**
 * Ajax ハンドラーを初期化
 */
function ktpwp_init_ajax_handlers() {
    // クラスファイルを読み込み
    require_once plugin_dir_path(__FILE__) . 'includes/class-tab-order.php';
    
    // インスタンスを作成（Ajaxハンドラー登録のため）
    $order_instance = new Kntan_Order_Class();
    
    // Ajaxハンドラーを登録
    add_action('wp_ajax_ktp_auto_save_item', array($order_instance, 'ajax_auto_save_item'));
    add_action('wp_ajax_nopriv_ktp_auto_save_item', array($order_instance, 'ajax_auto_save_item'));
    
    // 新規アイテム作成用Ajaxハンドラーを登録
    add_action('wp_ajax_ktp_create_new_item', array($order_instance, 'ajax_create_new_item'));
    add_action('wp_ajax_nopriv_ktp_create_new_item', array($order_instance, 'ajax_create_new_item'));
}
add_action('init', 'ktpwp_init_ajax_handlers');

function ktp_table_setup() {
    // プラグイン有効化時にテーブルセットアップを実行
    // まず必要なクラスファイルを読み込む
    $class_files = [
        'class-tab-client.php',
        'class-tab-service.php',
        'class-tab-supplier.php',
        'class-tab-setting.php',
        'class-login-error.php'
    ];
    
    foreach ($class_files as $file) {
        $file_path = plugin_dir_path(__FILE__) . 'includes/' . $file;
        if (file_exists($file_path)) {
            if ($file === 'class-tab-service.php') { // スキップ対象を class-tab-service.php に変更
                // class-tab-service.php の読み込みをスキップ
            } else {
                require_once $file_path;
            }
        } else {
        }
    }
    
    // 各クラスでテーブル作成/更新処理を行う
    if (class_exists('Kntan_Client_Class')) {
        $client = new Kntan_Client_Class();
        $client->Create_Table('client');
        // $client->Update_Table('client');
    }
    if (class_exists('Kntan_Service_Class')) {
        $service = new Kntan_Service_Class();
        $service->Create_Table('service');
        // $service->Update_Table('service');
    }
    if (class_exists('Kantan_Supplier_Class')) {
        $supplier = new Kantan_Supplier_Class();
        $supplier->Create_Table('supplier');
        // $supplier->Update_Table('supplier');
    }
    if (class_exists('KTPWP_Setting_Class')) {
        $setting = new KTPWP_Setting_Class();
        $setting->Create_Table('setting');
        // $setting->Update_Table('setting');
    }
}
register_activation_hook(KTPWP_PLUGIN_FILE, 'ktp_table_setup'); // テーブル作成処理
register_activation_hook(KTPWP_PLUGIN_FILE, array('KTP_Settings', 'activate')); // 設定クラスのアクティベート処理
register_activation_hook(KTPWP_PLUGIN_FILE, array('KTPWP_Plugin_Reference', 'on_plugin_activation')); // プラグインリファレンス更新処理

function check_activation_key() {
    $activation_key = get_site_option('ktp_activation_key');
    return empty($activation_key) ? '' : '';
}

function add_htmx_to_head() {
    // echo '<script src="https://unpkg.com/htmx.org@1.6.1"></script>';
}
// add_action('wp_head', 'add_htmx_to_head');

function KTPWP_Index(){

    //すべてのタブのショートコード[kantanAllTab]
    function kantanAllTab(){

        //ログイン中なら
        if (is_user_logged_in()) {
            // XSS対策: 画面に出力する変数は必ずエスケープ

            // ユーザーのログインログアウト状況を取得するためのAjaxを登録
            add_action('wp_ajax_get_logged_in_users', 'get_logged_in_users');
            add_action('wp_ajax_nopriv_get_logged_in_users', 'get_logged_in_users');

            // get_logged_in_users の再宣言防止
            if (!function_exists('get_logged_in_users')) {
                function get_logged_in_users() {
                    $logged_in_users = get_users(array(
                        'meta_key' => 'session_tokens',
                        'meta_compare' => 'EXISTS'
                    ));

                    $users_names = array();
                    foreach ( $logged_in_users as $user ) {
                        $users_names[] = $user->nickname . 'さん';
                    }

                    echo json_encode($users_names);
                    wp_die();
                }
            }

            // 現在メインのログインユーザー情報を取得
            global $current_user;

            // ログアウトのリンク
            $logout_link = esc_url(wp_logout_url());

            // ヘッダー表示ログインユーザー名など
            $act_key = esc_html(check_activation_key());

            // ログイン中のユーザー情報を取得（ログインしている場合のみ）
            $logged_in_users_html = '';
            
            // より厳密なログイン状態の確認
            if ( is_user_logged_in() && current_user_can( 'edit_posts' ) && $current_user && $current_user->ID > 0 ) {
                // セッションの有効性も確認
                $user_sessions = WP_Session_Tokens::get_instance( $current_user->ID );
                if ( $user_sessions && ! empty( $user_sessions->get_all() ) ) {
                    $nickname_esc = esc_attr($current_user->nickname);
                    $logged_in_users_html = '<strong><span title="' . $nickname_esc . '">' . get_avatar($current_user->ID, 32, '', '', ['class' => 'user_icon user_icon--current']) . '</span></strong>';
                }
            }

            // 画像タグをPHP変数で作成（ベースラインを10px上げる）
            $icon_img = '<img src="' . esc_url(plugins_url('images/default/icon.png', __FILE__)) . '" style="height:40px;vertical-align:middle;margin-right:8px;position:relative;top:-5px;">';

            // バージョン番号を定数から取得
            $plugin_version = defined('MY_PLUGIN_VERSION') ? esc_html(MY_PLUGIN_VERSION) : '';

            // プラグイン名とバージョンを定数から取得
            $plugin_name = esc_html(KTPWP_PLUGIN_NAME);
            $plugin_version = esc_html(KTPWP_PLUGIN_VERSION);
            $current_page_id = get_queried_object_id();
            $update_link_url = esc_url(get_permalink($current_page_id));

            // ログインしているユーザーのみにナビゲーションリンクを表示
            $navigation_links = '';
            if ( is_user_logged_in() && current_user_can( 'edit_posts' ) && $current_user && $current_user->ID > 0 ) {
                // セッションの有効性も確認
                $user_sessions = WP_Session_Tokens::get_instance( $current_user->ID );
                if ( $user_sessions && ! empty( $user_sessions->get_all() ) ) {
                    $navigation_links = '　<a href="' . $logout_link . '">' . esc_html__('ログアウト', 'ktpwp') . '</a>'
                        . '　<a href="' . $update_link_url . '">' . esc_html__('更新', 'ktpwp') . '</a>'
                        . '　' . $act_key
                        . '<a href="#" class="ktpwp-reference-link">' . esc_html__('リファレンス', 'ktpwp') . '</a>';
                }
            }

            $front_message = '<div class="ktp_header">'
                . '<div class="parent"><div class="title">' . $icon_img . $plugin_name . '</div><div class="version">v' . $plugin_version . '</div></div>'
                . '<div style="margin-left: auto; display: flex; align-items: center;">'
                . $logged_in_users_html
                . $navigation_links
                . '</div>'
                . '</div>';
            $tab_name = isset($_GET['tab_name']) ? $_GET['tab_name'] : 'default_tab'; // URLパラメータからtab_nameを取得

            // $order_content など未定義変数の初期化
            $order_content    = isset($order_content) ? $order_content : '';
            $client_content   = isset($client_content) ? $client_content : '';
            $service_content  = isset($service_content) ? $service_content : '';
            $supplier_content = isset($supplier_content) ? $supplier_content : '';
            $report_content   = isset($report_content) ? $report_content : '';
            $setting_content  = isset($setting_content) ? $setting_content : '';

            if (!isset($list_content)) {
                $list_content = '';
            }

            // デバッグ：タブ処理開始
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            }
            
            switch ($tab_name) {
                case 'list':
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    }
                    $list = new Kantan_List_Class();
                    $list_content = $list->List_Tab_View($tab_name);
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    }
                    break;
                case 'order':
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    }
                    $order = new Kntan_Order_Class();
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    }
                    $order_content = $order->Order_Tab_View($tab_name);
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    }
                    // Nullチェックを追加して空文字列で初期化
                    $order_content = $order_content ?? '';
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    }
                    break;
                case 'client':
                    $client = new Kntan_Client_Class();
                    if (current_user_can('manage_options')) {
                        $client->Create_Table($tab_name);
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        }
                        $client->Update_Table($tab_name);
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        }
                    }
                    $client_content = $client->View_Table($tab_name);
                    break;
                case 'service':
                    $service = new Kntan_Service_Class();
                    if (current_user_can('manage_options')) {
                        $service->Create_Table($tab_name);
                        $service->Update_Table($tab_name);
                    }
                    $service_content = $service->View_Table($tab_name);
                    break;
                case 'supplier':
                    $supplier = new KTPWP_Supplier_Class();
                    if (current_user_can('manage_options')) {
                        $supplier->Create_Table($tab_name);
                        $supplier->Update_Table($tab_name);
                    }
                    $supplier_content = $supplier->View_Table($tab_name);
                    break;
                case 'report':
                    $report = new KTPWP_Report_Class();
                    $report_content = $report->Report_Tab_View($tab_name);
                    break;
                case 'setting':
                    $setting = new KTPWP_Setting_Class();
                    if (current_user_can('manage_options')) {
                        $setting->Create_Table($tab_name);
                        // $setting->Update_Table($tab_name);
                    }
                    $setting_content = $setting->Setting_Tab_View($tab_name);
                    break;
                default:
                    // デフォルトの処理
                    $list = new Kantan_List_Class();
                    $tab_name = 'list';
                    $list_content = $list->List_Tab_View($tab_name);
                    break;
            }
            // view
            $view = new view_tabs_Class();
            $tab_view = $view->TabsView($list_content, $order_content, $client_content, $service_content, $supplier_content, $report_content, $setting_content);
            $return_value = $front_message . $tab_view;
            return $return_value;

        } else {
            $login_error = new Kantan_Login_Error();
            $error = $login_error->Error_View();
            return $error;
        }
    }
    add_shortcode('kantanAllTab','kantanAllTab');
    // ktpwp_all_tab ショートコードを追加（同じ機能を別名で提供）
    add_shortcode('ktpwp_all_tab', 'kantanAllTab');
}

// add_submenu_page の第7引数修正
// 例: add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
// 直接呼び出しを削除し、admin_menuフックで登録
add_action('admin_menu', function() {
    add_submenu_page(
        'parent_slug',
        __('ページタイトル', 'ktpwp'),
        __('メニュータイトル', 'ktpwp'),
        'manage_options',
        'menu_slug',
        'function_name'
        // 第7引数（メニュー位置）は不要なら省略
    );
});

// GitHub Updater
// add_filter('pre_set_site_transient_update_plugins', 'kpwp_github_plugin_update');
// add_filter('plugins_api', 'kpwp_github_plugin_update_info', 10, 3);

function kpwp_github_plugin_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }
    
    // プラグイン情報
    $plugin_slug = 'KTPWP/ktpwp.php';
    $github_user = 'aiojiipg'; // 正しいGitHubユーザー名
    $github_repo = 'ktpwp'; // 正しいリポジトリ名（大文字小文字に注意）

    // GitHubの最新リリース情報を取得
    $response = wp_remote_get("https://api.github.com/repos/$github_user/$github_repo/releases/latest", [
        'headers' => [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version')
        ]
    ]);
    
    if (is_wp_error($response)) {
        return $transient;
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    if (empty($release) || empty($release->tag_name)) {
        return $transient;
    }

    // 現在のバージョンを取得
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug);
    $current_version = $plugin_data['Version'];
    $latest_version = ltrim($release->tag_name, 'v');
    

    // 新しいバージョンがあればアップデート情報をセット
    if (version_compare($current_version, $latest_version, '<')) {
        // ZIPファイルのURLを見つける
        $package_url = '';
        if (isset($release->assets) && is_array($release->assets)) {
            foreach ($release->assets as $asset) {
                if (isset($asset->browser_download_url) && 
                    strpos($asset->browser_download_url, '.zip') !== false) {
                    $package_url = $asset->browser_download_url;
                    break;
                }
            }
        }
        
        // アセットがなければzipballを使用
        if (empty($package_url) && isset($release->zipball_url)) {
            $package_url = $release->zipball_url;
        }
        
        if (!empty($package_url)) {
            $transient->response[$plugin_slug] = (object)[
                'slug' => dirname($plugin_slug),
                'plugin' => $plugin_slug,
                'new_version' => $latest_version,
                'url' => $release->html_url,
                'package' => $package_url,
            ];
        }
    }
    return $transient;
}

// プラグイン情報を取得する関数
function kpwp_github_plugin_update_info($res, $action, $args) {
    if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== 'KTPWP') {
        return $res;
    }
    
    $github_user = 'aiojiipg';
    $github_repo = 'ktpwp';
    
    $response = wp_remote_get("https://api.github.com/repos/$github_user/$github_repo/releases/latest", [
        'headers' => [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version')
        ]
    ]);
    
    if (is_wp_error($response)) {
        return $res;
    }
    
    $release = json_decode(wp_remote_retrieve_body($response));
    if (empty($release)) {
        return $res;
    }
    
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/KTPWP/ktpwp.php');
    
    $res = new stdClass();
    $res->name = $plugin_data['Name'];
    $res->slug = 'KTPWP';
    $res->version = ltrim($release->tag_name, 'v');
    $res->tested = get_bloginfo('version');
    $res->requires = '5.0'; // 必要なWordPressのバージョン
    $res->author = $plugin_data['Author'];
    $res->author_profile = ''; // 作者プロフィールURL
    $res->download_link = isset($release->zipball_url) ? $release->zipball_url : '';
    $res->trunk = isset($release->zipball_url) ? $release->zipball_url : '';
    $res->last_updated = isset($release->published_at) ? $release->published_at : '';
    $res->sections = [
        'description' => $plugin_data['Description'],
        'changelog' => isset($release->body) ? $release->body : __('No changelog provided.', 'ktpwp'),
    ];
    
    return $res;
}


// 案件名インライン編集用Ajaxハンドラ

// 案件名インライン編集用Ajaxハンドラ（管理者のみ許可＆nonce検証）
add_action('wp_ajax_ktp_update_project_name', function() {
    // 権限チェック
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('権限がありません', 'ktpwp'));
    }
    // nonceチェック
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ktp_update_project_name')) {
        wp_send_json_error(__('セキュリティ検証に失敗しました', 'ktpwp'));
    }
    global $wpdb;
    $order_id = intval($_POST['order_id'] ?? 0);
    // wp_unslash()でスラッシュを削除し、wp_strip_all_tags()でタグのみ削除（HTMLエンティティは保持）
    $project_name = wp_strip_all_tags(wp_unslash($_POST['project_name'] ?? ''));
    if ($order_id > 0) {
        $table = $wpdb->prefix . 'ktp_order';
        $wpdb->update(
            $table,
            ['project_name' => $project_name],
            ['id' => $order_id],
            ['%s'],
            ['%d']
        );
        wp_send_json_success();
    } else {
        wp_send_json_error(__('Invalid order_id', 'ktpwp'));
    }
});

// 非ログイン時もAjaxを許可（必要なら）
// 非ログイン時はAjaxで案件名編集不可（セキュリティのため）
add_action('wp_ajax_nopriv_ktp_update_project_name', function() {
    wp_send_json_error(__('ログインが必要です', 'ktpwp'));
});

// ajaxurlをフロントにも出力 //この処理は wp_localize_script に置き換えたためコメントアウト
/* add_action('wp_head', function() {
    if (!is_admin()) {
        echo '<script>var ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';
    }
}); */


// 必要なら includes/class-tab-list.php, class-view-tab.php を明示的に読み込む（自動読み込みされていない場合のみ）
if (!class_exists('Kantan_List_Class')) {
    include_once(MY_PLUGIN_PATH . 'includes/class-tab-list.php');
}
if (!class_exists('view_tabs_Class')) {
    include_once(MY_PLUGIN_PATH . 'includes/class-view-tab.php');
}
if (!class_exists('Kantan_Login_Error')) {
    include_once(MY_PLUGIN_PATH . 'includes/class-login-error.php');
}
if (!class_exists('Kntan_Report_Class')) {
    include_once(MY_PLUGIN_PATH . 'includes/class-tab-report.php');
}

if (defined('WP_DEBUG') && WP_DEBUG) {
}

/**
 * Contact Form 7の送信データをwp_ktp_clientテーブルに登録する
 *
 * @param WPCF7_ContactForm $contact_form Contact Form 7のフォームオブジェクト.
 */
function ktp_capture_contact_form_data( $contact_form ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ktp_client';

    $submission = WPCF7_Submission::get_instance();

    if ( $submission ) {
        $posted_data = $submission->get_posted_data();

        global $wpdb;
        $table_name_client = $wpdb->prefix . 'ktp_client';
        $table_name_order = $wpdb->prefix . 'ktp_order'; // 受注テーブル名

        // データのサニタイズとマッピング
        $company_name = isset( $posted_data['your_company_name'] ) ? sanitize_text_field( $posted_data['your_company_name'] ) : '';
        $name         = isset( $posted_data['your-name'] ) ? sanitize_text_field( $posted_data['your-name'] ) : '';
        $email        = isset( $posted_data['your-email'] ) ? sanitize_email( $posted_data['your-email'] ) : '';
        $subject      = isset( $posted_data['your-subject'] ) ? sanitize_text_field( $posted_data['your-subject'] ) : '';
        $message      = isset( $posted_data['your-message'] ) ? sanitize_textarea_field( $posted_data['your-message'] ) : '';
        $category     = isset( $posted_data['select-996'] ) ? sanitize_text_field( $posted_data['select-996'] ) : '';

        $memo_for_client = ''; // 顧客メモ用の変数
        if ( ! empty( $subject ) ) {
            $memo_for_client .= __( '件名:', 'ktpwp' ) . ' ' . $subject;
        }
        if ( ! empty( $message ) ) {
            if ( ! empty( $memo_for_client ) ) {
                $memo_for_client .= "\n"; // 改行で区切る
            }
            $memo_for_client .= __( 'メッセージ本文:', 'ktpwp' ) . ' ' . $message;
        }

        $current_time = current_time( 'mysql' );

        // 顧客データを挿入
        $client_data = array(
            'company_name' => $company_name,
            'name'         => $name,
            'email'        => $email,
            'memo'         => $memo_for_client, // 件名とメッセージ本文を結合したメモ
            'time'         => $current_time,
            'client_status'     => '対象', // デフォルト値を '対象' に設定
            // 'category'     => '対象', // 旧コード
        );

        $client_format = array(
            '%s', // company_name
            '%s', // name
            '%s', // email
            '%s', // memo
            '%s', // client_status
            '%s', // time
        );

        // データベースにクライアント情報を挿入
        $client_insert_result = $wpdb->insert($table_name_client, $client_data, $client_format); // table_name_client を使用

        if ($client_insert_result === false) {
            error_log('KTPWP Error: Failed to insert client data. Query: ' . $wpdb->last_query . ' Error: ' . $wpdb->last_error);
        } else { // この else に対する if は $client_insert_result === false
            $new_client_id = $wpdb->insert_id;
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }

            // クライアントタブ表示用のクッキーを新しいクライアントIDで更新
            // class-tab-client.php で使用されるクッキー名は 'ktp_' . $tab_name . '_id' の形式なので、
            // ここでは $tab_name が 'client' であると想定して 'ktp_client_id' を使用します。
            $client_cookie_name = 'ktp_client_id'; 
            if (!headers_sent()) { // この if に対する else
                setcookie($client_cookie_name, $new_client_id, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                }
            } else { // この else は if (!headers_sent()) に対応
                if (defined('WP_DEBUG') && WP_DEBUG) {
                }
            } // ここは if (!headers_sent()) の else の閉じ括弧 (または if のみの場合は不要) -> if のみの場合はこの else ブロック全体が不要になるが、ログは残したいのでこのまま

            // 受注データの準備
            $order_table_name = $wpdb->prefix . 'ktp_order';
            $current_time_mysql = current_time('mysql');
            $current_unix_time = time();

            $order_data = array(
                'client_id' => $new_client_id,
                'customer_name' => $name, // フォームから取得した顧客名
                'project_name' => __( 'お問い合わせの件', 'ktpwp' ),
                'progress' => 1, // "受付中"
                'user_name' => '', // 初期状態では空
                'time' => $current_unix_time, 
                'created_at' => $current_time_mysql,
                'updated_at' => $current_time_mysql,
            );

            $order_format = array(
                '%d', // client_id
                '%s', // customer_name
                '%s', // project_name
                '%d', // progress
                '%s', // user_name
                '%d', // time
                '%s', // created_at
                '%s', // updated_at
            );

            $order_insert_result = $wpdb->insert($order_table_name, $order_data, $order_format);

            if ($order_insert_result === false) {
                error_log('KTPWP Error: Failed to insert order data for client ID ' . $new_client_id . '. Query: ' . $wpdb->last_query . ' Error: ' . $wpdb->last_error);
            }
        } // ここが $client_insert_result === false の else の閉じ括弧
    }
}
add_action( 'wpcf7_mail_sent', 'ktp_capture_contact_form_data' );
