<?php
/*
Plugin Name: KTPWP
Description: パーマリンク問題があります。
Version: 0.0.0beta
*/

// プラグイン基本情報を定数として定義（コード内で参照するため）
define('KTPWP_PLUGIN_NAME', 'KTPWP');
define('KTPWP_PLUGIN_DESCRIPTION', '仕事のワークフローを管理するためのプラグインです。');
define('KTPWP_PLUGIN_VERSION', '0.0.0beta');

if (!defined('ABSPATH')) {
	exit;
}

// プラグインファイルの定義
define('KTPWP_PLUGIN_FILE', __FILE__);

// 定数を定義
if (!defined('MY_PLUGIN_VERSION')) {
	define('MY_PLUGIN_VERSION', KTPWP_PLUGIN_VERSION); // プラグインのバージョン
}
if (!defined('MY_PLUGIN_PATH')) {
	define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('MY_PLUGIN_URL')) {
	define('MY_PLUGIN_URL', plugins_url('/', __FILE__));
}

// プラグインのアクティベーション時の処理を登録
register_activation_hook(KTPWP_PLUGIN_FILE, array('KTP_Settings', 'activate'));

// リダイレクト処理クラス
class KTPWP_Redirect {
    
    public function __construct() {
        add_action('template_redirect', array($this, 'handle_redirect'));
        add_filter('post_link', array($this, 'custom_post_link'), 10, 2);
        add_filter('page_link', array($this, 'custom_page_link'), 10, 2);
    }

    public function handle_redirect() {
        // デバッグ用ログ
        error_log("KTPWP Debug: handle_redirect called - URL: {$_SERVER['REQUEST_URI']}");
        
        // ショートコードが含まれるページの場合はリダイレクトしない
        if (isset($_GET['tab_name']) || $this->has_ktpwp_shortcode()) {
            error_log("KTPWP Debug: Redirect skipped - tab_name or shortcode found");
            return;
        }
        
        if (is_single() || is_page()) {
            $post = get_queried_object();
            
            if ($post && $this->should_redirect($post)) {
                $external_url = $this->get_external_url($post);
                if ($external_url) {
                    // クエリパラメータをクリーンアップしてリダイレクト
                    $clean_external_url = strtok($external_url, '?');
                    error_log("KTPWP Debug: Template redirect to: {$clean_external_url}");
                    wp_redirect($clean_external_url, 301);
                    exit;
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
            error_log("KTPWP Debug: Shortcode detected, skipping redirect");
            return false;
        }
        
        // KTPWPのクエリパラメータがある場合はリダイレクトしない
        if (isset($_GET['tab_name']) || isset($_GET['from_client']) || isset($_GET['order_id'])) {
            error_log("KTPWP Debug: KTPWP parameters detected, skipping redirect");
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
        error_log("KTPWP Debug: Converting POST to GET for order creation");
        
        // リダイレクト用のクリーンなURLを構築
        $redirect_params = [
            'tab_name' => sanitize_text_field($_POST['tab_name']),
            'from_client' => sanitize_text_field($_POST['from_client'])
        ];
        
        if (isset($_POST['customer_name'])) {
            $redirect_params['customer_name'] = sanitize_text_field($_POST['customer_name']);
        }
        if (isset($_POST['user_name'])) {
            $redirect_params['user_name'] = sanitize_text_field($_POST['user_name']);
        }
        if (isset($_POST['client_id'])) {
            $redirect_params['client_id'] = sanitize_text_field($_POST['client_id']);
        }
        
        // 現在のURLからKTPWPパラメータを除去してクリーンなベースURLを作成
        $current_url = add_query_arg(NULL, NULL);
        $clean_url = remove_query_arg([
            'tab_name', 'from_client', 'customer_name', 'user_name', 'client_id', 
            'order_id', 'delete_order', 'data_id', 'view_mode', 'query_post'
        ], $current_url);
        
        // 新しいパラメータを追加してリダイレクト
        $redirect_url = add_query_arg($redirect_params, $clean_url);
        
        // リダイレクト実行
        wp_redirect($redirect_url, 302);
        exit;
    }
    
    // 受注書削除処理のPOSTパラメータをGETに変換
    if (isset($_POST['delete_order']) && isset($_POST['order_id'])) {
        error_log("KTPWP Debug: Converting POST to GET for order deletion");
        
        // リダイレクト用のパラメータを構築
        $redirect_params = [
            'tab_name' => 'order',
            'delete_order' => sanitize_text_field($_POST['delete_order']),
            'order_id' => sanitize_text_field($_POST['order_id'])
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
}

// WordPress初期化時に処理を実行（より早いタイミングで実行）
add_action('wp_loaded', 'ktpwp_handle_form_redirect', 1);

// リダイレクト処理を初期化（テスト用に再有効化）
new KTPWP_Redirect();


// ファイルをインクルード
$includes = [
	'class-tab-list.php',
	'class-tab-order.php',
	'class-tab-client.php',
	'class-tab-service.php',
	'class-tab-supplier.php',
	'class-tab-report.php',
	'class-tab-setting.php',
	'class-login-error.php',
	'class-view-tab.php',
	'class-ktp-settings.php',
	'class-ktp-upgrade.php',
	'ktp-admin-form.php',
];

foreach ($includes as $file) {
	include_once 'includes/' . $file;
}

add_action('plugins_loaded', 'KTPWP_Index');

function ktpwp_scripts_and_styles() {
	// 修正前: 'ktp-js', plugins_url('js/ktp-ajax.js', __FILE__), array(), '1.0.0', true
	wp_enqueue_script('ktp-js', plugins_url('js/ktp-js.js', __FILE__), array(), '1.0.0', true); // 修正後
	wp_register_style('ktp-css', plugins_url('css/styles.css', __FILE__), array(), '1.0.0', 'all');
	wp_enqueue_style('ktp-css');
	// 進捗プルダウン用のスタイルシートを追加
	wp_enqueue_style('ktp-progress-select', plugins_url('css/progress-select.css', __FILE__), array('ktp-css'), '1.0.0', 'all');
	// 設定タブ用のスタイルシートを追加
	wp_enqueue_style('ktp-setting-tab', plugins_url('css/ktp-setting-tab.css', __FILE__), array('ktp-css'), '1.0.0', 'all');
	wp_enqueue_style('material-symbols-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0');
	wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js', array(), '3.5.1', true);
	wp_enqueue_script('ktp-order-inline-projectname', plugins_url('js/ktp-order-inline-projectname.js', __FILE__), array('jquery'), '1.0.0', true);
	// 進捗プルダウン用のJavaScriptを追加
	wp_enqueue_script('ktp-progress-select', plugins_url('js/progress-select.js', __FILE__), array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'ktpwp_scripts_and_styles');

function ktp_table_setup() {
	Create_Table();
	Update_Table();
}
register_activation_hook(__FILE__, 'ktp_table_setup');

// --- ここから追加 ---
function Create_Table() {
	// テーブル作成処理（ダミー/本番は各クラスで実装）
}
function Update_Table() {
	// テーブル更新処理（ダミー/本番は各クラスで実装）
}
// --- ここまで追加 ---

function check_activation_key() {
	$activation_key = get_site_option('ktp_activation_key');
	return empty($activation_key) ? '' : '';
}

function add_htmx_to_head() {
	echo '<script src="https://unpkg.com/htmx.org@1.6.1"></script>';
}
add_action('wp_head', 'add_htmx_to_head');

function KTPWP_Index(){

	//すべてのタブのショートコード[kantanAllTab]
	function kantanAllTab(){

		//ログイン中なら
		if (is_user_logged_in()) {

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
			$logout_link = wp_logout_url();

			// ヘッダー表示ログインユーザー名など
			$act_key = check_activation_key();

			// ログイン中の全てのユーザーを取得
			$logged_in_users = get_users(array(
				'meta_key' => 'session_tokens',
				'meta_compare' => 'EXISTS'
			));

			// ログイン中の全てのユーザー名を連結
			$current_user_name = '';
			$other_users_names = array();
			foreach ( $logged_in_users as $user ) {
				// 現在ログインしているユーザーの場合は名前を太字にする
				if ($user->ID == $current_user->ID) {
					$current_user_name = '<strong><span title="' . $user->nickname . '">' . get_avatar($user->ID, 32, '', '', ['class' => 'user_icon user_icon--current']) . '</span></strong>';
				} else {
					$other_users_names[] = '<span title="' . $user->nickname . '">' . get_avatar($user->ID, 32, '', '', ['class' => 'user_icon']) . '</span>';
				}
			}
			$other_users_html = count($other_users_names) > 0 ? '' . implode(' ', $other_users_names)  : '';
			$logged_in_users_html = $current_user_name . $other_users_html;

			// 画像タグをPHP変数で作成（ベースラインを10px上げる）
			$icon_img = '<img src="' . plugins_url('images/default/icon.png', __FILE__) . '" style="height:40px;vertical-align:middle;margin-right:8px;position:relative;top:-5px;">';

			// バージョン番号を定数から取得
			$plugin_version = defined('MY_PLUGIN_VERSION') ? MY_PLUGIN_VERSION : '';

			// プラグイン名とバージョンを定数から取得
			$plugin_name = KTPWP_PLUGIN_NAME;
			$plugin_version = KTPWP_PLUGIN_VERSION;

			// 更新用URLを生成
			$update_url = esc_url(home_url(add_query_arg(null, null)));
			
			$front_message = <<<END
			<div class="ktp_header">
			<div class="parent"><div class="title">{$icon_img}{$plugin_name}</div><div class="version">v{$plugin_version}</div></div>
			$logged_in_users_html
			　<a href="$logout_link">ログアウト</a>　<a href="{$update_url}">更新</a>　$act_key
			</div>
			END;
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

			switch ($tab_name) {
				case 'list':
					$list = new Kantan_List_Class();
					$list_content = $list->List_Tab_View($tab_name);
					break;
				case 'order':
					$order = new Kntan_Order_Class();
					$order_content = $order->Order_Tab_View($tab_name);
					break;
				case 'client':
					$client = new Kntan_Client_Class();
					$client->Create_Table($tab_name);
					$client->Update_Table($tab_name);
					$client_content = $client->View_Table($tab_name);
					break;
				case 'service':
					$service = new kntan_Service_Class();
					$service->Create_Table($tab_name);
					$service->Update_Table($tab_name);
					$service_content = $service->View_Table($tab_name);
					break;
				case 'supplier':
					$supplier = new Kantan_Supplier_Class();
					$supplier->Create_Table($tab_name);
					$supplier->Update_Table($tab_name);
					$supplier_content = $supplier->View_Table($tab_name);
					break;
				case 'report':
					$report = new Kntan_Report_Class();
					$report_content = $report->Report_Tab_View($tab_name);
					break;
				case 'setting':
					$setting = new Kntan_Setting_Class();
					$setting->Create_Table($tab_name);
					$setting->Update_Table($tab_name);
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
		'ページタイトル',
		'メニュータイトル',
		'manage_options',
		'menu_slug',
		'function_name'
		// 第7引数（メニュー位置）は不要なら省略
	);
});

// GitHub Updater
add_filter('pre_set_site_transient_update_plugins', 'kpwp_github_plugin_update');
add_filter('plugins_api', 'kpwp_github_plugin_update_info', 10, 3);

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
        error_log('KTPWP: GitHub API Error - ' . $response->get_error_message());
        return $transient;
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    if (empty($release) || empty($release->tag_name)) {
        error_log('KTPWP: GitHub API Response Invalid - ' . wp_remote_retrieve_body($response));
        return $transient;
    }

    // 現在のバージョンを取得
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug);
    $current_version = $plugin_data['Version'];
    $latest_version = ltrim($release->tag_name, 'v');
    
    error_log("KTPWP: Current version: $current_version, Latest version: $latest_version");

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
        'changelog' => isset($release->body) ? $release->body : 'No changelog provided.',
    ];
    
    return $res;
}


// 案件名インライン編集用Ajaxハンドラ
add_action('wp_ajax_ktp_update_project_name', function() {
	global $wpdb;
	$order_id = intval($_POST['order_id'] ?? 0);
	$project_name = sanitize_text_field($_POST['project_name'] ?? '');
	if ($order_id > 0) {
		$table = $wpdb->prefix . 'ktp_order';
		$wpdb->update($table, ['project_name' => $project_name], ['id' => $order_id]);
		wp_send_json_success();
	} else {
		wp_send_json_error('Invalid order_id');
	}
});

// 非ログイン時もAjaxを許可（必要なら）
add_action('wp_ajax_nopriv_ktp_update_project_name', function() {
	global $wpdb;
	$order_id = intval($_POST['order_id'] ?? 0);
	$project_name = sanitize_text_field($_POST['project_name'] ?? '');
	if ($order_id > 0) {
		$table = $wpdb->prefix . 'ktp_order';
		$wpdb->update($table, ['project_name' => $project_name], ['id' => $order_id]);
		wp_send_json_success();
	} else {
		wp_send_json_error('Invalid order_id');
	}
});

// ajaxurlをフロントにも出力
add_action('wp_head', function() {
	if (!is_admin()) {
		echo '<script>var ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';
	}
});
