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
// プラグインを管理画面に登録
function ktpwp_register_plugin() {
    add_menu_page(
        'Kantan Pro WP Settings', // ページタイトル
        'Kantan Pro', // メニュータイトル
        'manage_options', // 必要な権限
        'ktpwp-settings', // メニュースラッグ
        'ktpwp_settings_page', // 表示する関数
        'dashicons-admin-generic', // アイコン
        6 // メニュー位置
    );
}
add_action('admin_menu', 'ktpwp_register_plugin');


/*
 必要な定数を定義
*/

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
    'class-tab-list.php',
    'class-tab-order.php',
    'class-tab-client.php',
    'class-tab-service.php',
    'class-tab-supplier.php',
    'class-tab-report.php',
    'class-tab-setting.php',
    'class-login-error.php', // ログインエラークラス
    'class-view-tab.php', // タブビュークラス
    'kpw-admin-form.php', // 管理画面に追加
];

foreach ($includes as $file) {
    include 'includes/' . $file;
}

// カンタンProをロード
add_action('plugins_loaded','KTPWP_Index'); // カンタンPro本体

// JavaScriptとスタイルシートを登録
function ktpwp_scripts_and_styles() {
	wp_enqueue_script(
		'ktp-js',
		plugins_url( 'js/ktp-ajax.js' , __FILE__),
		array(),
		'1.0.0',
		true
	);
	wp_enqueue_style( 'ktp-js' );
	
	wp_register_style(
		'ktpwp-css',
		plugins_url( '/css/styles.css' , __FILE__),
		array(),
		'1.0.0',
		'all'
	);
	wp_enqueue_style( 'ktpwp-css' );
}
add_action( 'wp_enqueue_scripts', 'ktpwp_scripts_and_styles' );

// テーブル用の関数を登録
function ktpwp_table_setup() {
    Create_Table(); // テーブル作成
    Update_Table(); // テーブル更新
}
register_activation_hook( __FILE__, 'ktpwp_table_setup' );

// register_activation_hook( __FILE__, 'my_wpcf7_mail_sent' ); // コンタクト７


function KTPWP_Index(){

	//すべてのタブのショートコード[kantanAllTab]
	function kantanAllTab(){

		//ログイン中なら
		if( is_user_logged_in() ){

				// ログインユーザー情報を取得
				global $current_user;

				// ログアウトのリンク
				$logout_link = wp_logout_url();

				// ヘッダー表示ログインユーザー名など
				$login_user = $current_user->nickname;
				$front_message = <<<END
				<div class="ktp_header">
				$login_user さん　<a href="$logout_link">ログアウト</a>　<a href="/">更新</a>　
					<div id="zengo" class="zengo">
					<a href="#" id="zengoBack" class="zengoButton"> < </a>　<a href="#" id="zengoForward" class="zengoButton"> > </a>
					</div>
				</div>
				END;
		
				//仕事リスト
				$list = new Kantan_List_Class();
				$list_content = $list->List_Tab_View( 'list' );

				//受注書
				$tabs = new Kntan_Order_Class();
				$order_content = $tabs->Order_Tab_View( 'order' );
				
				//クライアント				
				$tabs = new Kntan_Client_Class();
				$tab_name = 'client';
				$tabs->Create_Table( $tab_name );
				$tabs->Update_Table( $tab_name );
				$view = $tabs->View_Table( $tab_name );
				$client_content = $view;

				//商品・サービス
				$tabs = new Kntan_Service_Class();
				$service_content = $tabs->Service_Tab_View( 'service' );
				
				//協力会社
				$tabs = new Kantan_Supplier_Class();
				$tab_name = 'supplier';
				$tabs->Create_Table( $tab_name );
				$tabs->Update_Table( $tab_name );
				$view = $tabs->View_Table( $tab_name );
				$supplier_content = $view;

				//レポート
				$tabs = new Kntan_Report_Class();
				$report_content = $tabs->Report_Tab_View( 'report' );
				
				//設定
				$tabs = new Kntan_Setting_Class();
				$setting_content = $tabs->Setting_Tab_View( 'setting' );

				// view
				$view = new view_tabs_Class();
				$tab_view = $view ->TabsView( $list_content, $order_content, $client_content, $service_content, $supplier_content, $report_content, $setting_content );
				return $front_message . $tab_view;


		}

		//ログアウト中なら
		else{
				$login_error = new Kantan_Login_Error();
				$error = $login_error->Error_View();
				return $error;
		}

	}
	add_shortcode('kantanAllTab','kantanAllTab');

}