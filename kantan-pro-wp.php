<?php
/*
Plugin Name: kantan pro wp
Description: カンタンProWP
Version: 1.0
*/

// wp-config.phpが存在しているか？
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 定数を定義
if ( ! defined( 'MY_PLUGIN_VERSION' ) ) {
	define( 'MY_PLUGIN_VERSION', '1.0' );
}
if ( ! defined( 'MY_PLUGIN_PATH' ) ) {
	define( 'MY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'MY_PLUGIN_URL' ) ) {
	define( 'MY_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
}

// ファイルをインクルード
$includes = [
    'class-tab-list.php', // リストタブクラス
    'class-tab-order.php', // 注文タブクラス
    'class-tab-client.php', // 顧客タブクラス
    'class-tab-service.php', // サービスタブクラス
    'class-tab-supplier.php', // 仕入先タブクラス
    'class-tab-report.php', // 報告タブクラス
    'class-tab-setting.php', // 設定タブクラス
    'class-login-error.php', // ログインエラークラス
    'class-view-tab.php', // タブビュークラス
    'ktp-admin-form.php', // 管理画面に追加
];

foreach ($includes as $file) {
    include 'includes/' . $file;
}

// カンタンProWPをロード
add_action('plugins_loaded','KTPWP_Index'); // カンタンPro本体

// JavaScriptとスタイルシートを登録
function ktpwp_scripts_and_styles() {
	// js
	wp_enqueue_script(
		'ktp-js',
		plugins_url( 'js/ktp-ajax.js' , __FILE__),
		array(),
		'1.0.0',
		true
	);
	// css
	wp_register_style(
		'ktp-css',
		plugins_url( '/css/styles.css' , __FILE__),
		array(),
		'1.0.0',
		'all'
	);
	wp_enqueue_style( 'ktp-css' );
	// Google Fonts
	wp_enqueue_style(
		'material-symbols-outlined',
		'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0'
	);
	// jQuery
	wp_enqueue_script(
		'jquery',
		'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js',
		array(),
		'3.5.1',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ktpwp_scripts_and_styles' );

// テーブル用の関数を登録
function ktp_table_setup() {
    Create_Table(); // テーブル作成
    Update_Table(); // テーブル更新
}
register_activation_hook( __FILE__, 'ktp_table_setup' );

// 有効化キーが設定されているかチェック
function check_activation_key() {
	$activation_key = get_site_option( 'ktp_activation_key' );

	if ( empty( $activation_key ) ) {
		// 有効化キーが設定されていない場合の処理
		$act_key = '';
	} else {
		// 有効化キーが設定されている場合の処理
		$act_key = '';
	}

	return $act_key;
}

// ヘッダーにhtmxを追加
function add_htmx_to_head() {
	echo '<script src="https://unpkg.com/htmx.org@1.6.1"></script>';
}
add_action('wp_head', 'add_htmx_to_head');

function KTPWP_Index(){

	//すべてのタブのショートコード[kantanAllTab]
	function kantanAllTab(){

		//ログイン中なら
		if (is_user_logged_in()) {

			// ログインユーザー情報を取得
			global $current_user;

			// ログアウトのリンク
			$logout_link = wp_logout_url();

			// ヘッダー表示ログインユーザー名など
			$login_user = $current_user->nickname;
			$act_key = check_activation_key();
			$front_message = <<<END
			<div class="ktp_header">
			$login_user さん　<a href="$logout_link">ログアウト</a>　<a href="/">更新</a>　$act_key
				<div id="zengo" class="zengo">
				<a href="#" id="zengoBack" class="zengoButton"> < </a>　<a href="#" id="zengoForward" class="zengoButton"> > </a>
				</div>
			</div>
			END;

			// // 関数を呼び出して結果を表示
			// $act_key = check_activation_key();
			// $flont_message .= $act_key;

			$tab_name = $_GET['tab_name']; // URLパラメータからtab_nameを取得

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
					$service_content = $service->Service_Tab_View($tab_name);
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
