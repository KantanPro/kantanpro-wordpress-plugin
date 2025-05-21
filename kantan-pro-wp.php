

<?php
/*
Plugin Name: KTPWP
Description: 仕事のワークフローを管理するためのプラグインです。
Version: beta
*/

if (!defined('ABSPATH')) {
	exit;
}

// 定数を定義
if (!defined('MY_PLUGIN_VERSION')) {
	define('MY_PLUGIN_VERSION', 'beta'); // プラグインのバージョン
}
if (!defined('MY_PLUGIN_PATH')) {
	define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('MY_PLUGIN_URL')) {
	define('MY_PLUGIN_URL', plugins_url('/', __FILE__));
}

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
	'ktp-admin-form.php',
];

foreach ($includes as $file) {
	include 'includes/' . $file;
}

add_action('plugins_loaded', 'KTPWP_Index');

function ktpwp_scripts_and_styles() {
	// 修正前: 'ktp-js', plugins_url('js/ktp-ajax.js', __FILE__), array(), '1.0.0', true
	wp_enqueue_script('ktp-js', plugins_url('js/ktp-js.js', __FILE__), array(), '1.0.0', true); // 修正後
	wp_register_style('ktp-css', plugins_url('css/styles.css', __FILE__), array(), '1.0.0', 'all');
	wp_enqueue_style('ktp-css');
	wp_enqueue_style('material-symbols-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0');
	wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js', array(), '3.5.1', true);
	wp_enqueue_script('ktp-order-inline-projectname', plugins_url('js/ktp-order-inline-projectname.js', __FILE__), array('jquery'), '1.0.0', true);
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

			$front_message = <<<END
			<div class="ktp_header">
			<div class="parent"><div class="title">{$icon_img}KTPWP</div><div class="version">v{$plugin_version}</div></div>
			$logged_in_users_html
			　<a href="$logout_link">ログアウト</a>　<a href="/">更新</a>　$act_key
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
	// プラグイン情報
	$plugin_slug = 'kantan-pro-wp/kantan-pro-wp.php';
	$github_user = 'nonaka'; 
	$github_repo = 'kantan-pro-wp';

	// GitHubの最新リリース情報を取得
	$response = wp_remote_get("https://api.github.com/repos/$github_user/$github_repo/releases/latest", [
		'headers' => ['Accept' => 'application/vnd.github.v3+json', 'User-Agent' => 'WordPress']
	]);
	if (is_wp_error($response)) return $transient;

	$release = json_decode(wp_remote_retrieve_body($response));
	if (empty($release->tag_name)) return $transient;

	// 現在のバージョンを取得
	$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug);
	$current_version = $plugin_data['Version'];

	// 新しいバージョンがあればアップデート情報をセット
	if (version_compare($current_version, ltrim($release->tag_name, 'v'), '<')) {
		$transient->response[$plugin_slug] = (object)[
			'slug' => $plugin_slug,
			'plugin' => $plugin_slug,
			'new_version' => ltrim($release->tag_name, 'v'),
			'url' => $release->html_url,
			'package' => $release->zipball_url,
		];
	}
	return $transient;
}

// プラグイン情報を取得するフィルター
add_filter('plugins_api', 'kpwp_github_plugin_update_info', 10, 3);
// プラグイン情報を取得する関数
function kpwp_github_plugin_update_info($res, $action, $args) {
	// ...existing code...
	if ($action !== 'plugin_information' || $args->slug !== 'kantan-pro-wp') {
		return $res;
	}
	// ここに必要な処理を追加する場合は記述
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
