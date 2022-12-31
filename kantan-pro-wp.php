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
include 'includes/class-tab-list.php';
include 'includes/class-tab-order.php';
include 'includes/class-tab-client.php';
include 'includes/class-tab-service.php';
include 'includes/class-tab-supplier.php';
include 'includes/class-tab-report.php';
include 'includes/class-tab-setting.php';
include 'includes/class-login-error.php'; // ログインエラークラス
include 'includes/class-form-client.php'; // クライアントフォームクラス
include "includes/class-view-tab.php"; // タブビュークラス
// include "js/view.js"; // JS
include "includes/kpw-admin-form.php"; // 管理画面に追加


// 関数をロード
add_action('plugins_loaded','KTPWP_Index'); // カンタンPro本体

// スタイルシートを登録
function register_ktpwp_styles() {
	wp_register_style(
		'ktpwp-css',
		plugins_url( '/css/styles.css' , __FILE__),
		array(),
		'1.0.0',
		'all'
	);
	wp_enqueue_style( 'ktpwp-css' );
}
add_action( 'wp_enqueue_scripts', 'register_ktpwp_styles' );

// テーブル用の関数を登録
register_activation_hook( __FILE__, 'Client_Table_Create' ); // テーブル作成用
register_activation_hook( __FILE__, 'Client_Table_Data' ); // デフォルト
register_activation_hook( __FILE__, 'my_wpcf7_mail_sent' ); // コンタクト７

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
				<div class="ktp_header">ログイン中：$login_user さん　<a href="$logout_link">ログアウト</a>　<a href="/">更新</a></div>
				END;
		
				//仕事リスト
				$list = new Kantan_List_Class();
				$list_content = $list->List_Tab_View( 'list' );

				//受注書
				$tabs = new Kntan_Order_Class();
				$order_content = $tabs->Order_Tab_View( 'order' );
				
				//クライアント				
				$tabs = new Kntan_Client_Class();
				$tabs->Client_Table_Create();
				$tabs->Client_Table_Data();
				$view = $tabs->Client_Table_View( 'client' );
				$client_content = $view;
				
				//商品・サービス
				$tabs = new Kntan_Service_Class();
				$service_content = $tabs->Service_Tab_View( 'service' );
				
				//協力会社
				$tabs = new Kantan_Supplier_Class();
				$supplier_content = $tabs->Supplier_Tab_View( 'supplier' );
				
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