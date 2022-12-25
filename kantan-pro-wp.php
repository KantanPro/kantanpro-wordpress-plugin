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
include "includes/kpw-admin-form.php"; // 管理画面に追加

// 関数をロード
add_action('plugins_loaded','KTPWP_Index'); // カンタンPro本体
// add_action('wpcf7_mail_sent', 'my_wpcf7_mail_sent'); //ContactForm７から送信された情報を取得
// add_action('kpw_client_form', 'kpw_client_form'); //ContactForm７のフォームを生成

// スタイルシートを登録
function register_ktpwp_styles() {
	// $url = plugins_url( '/css/ktpwp.css' , __FILE__);
	wp_register_style(
		'ktpwp.css',
		plugins_url( '/css/ktpwp.css' , __FILE__),
		array(),
		'1.0.0',
		'all'
	);
	wp_enqueue_style( 'ktpwp.css' );
}
add_action( 'wp_enqueue_scripts', 'register_ktpwp_styles' );

// テーブル用の関数を登録
register_activation_hook( __FILE__, 'Client_Table_Create' ); // テーブル作成用
register_activation_hook( __FILE__, 'Client_Table_Data' ); // デフォルト
register_activation_hook( __FILE__, 'my_wpcf7_mail_sent' ); // コンタクト７

function KTPWP_Index(){

	//ログイン中なら
	if( is_user_logged_in() ){

		//仕事リスト
		function TabList(){
			$list = new Kantan_List_Class();
			return $list->List_Tab_View( 'list' );
		}
		add_shortcode('list','TabList');

		//受注書
		function shortcodeorder(){
			$tabs = new Kntan_Order_Class();
			return $tabs->Order_Tab_View( 'order' );
		}
		add_shortcode('order','shortcodeorder');
		
		//クライアント
		function shortcodeclient(){
			
			$tabs = new Kntan_Client_Class();
			$tabs->Client_Table_Create();
			$tabs->Client_Table_Data();
			$view = $tabs->Client_Table_View( 'client' );
			return $client_form . $view;

		}
		add_shortcode('client','shortcodeclient');
		
		//商品・サービス
		function shortcodeservice(){
			$tabs = new Kntan_Service_Class();
			return $tabs->Service_Tab_View( 'service' );
		}
		add_shortcode('service','shortcodeservice');
		
		//協力会社
		function shortcodesupplier(){
			$tabs = new Kantan_Supplier_Class();
			return $tabs->Supplier_Tab_View( 'supplier' );
		}
		add_shortcode('supplier','shortcodesupplier');
		
		//レポート
		function shortcodereport(){
			$tabs = new Kntan_Report_Class();
			return $tabs->Report_Tab_View( 'report' );
		}
		add_shortcode('report','shortcodereport');
		
		//設定
		function shortcodesetting(){
			$tabs = new Kntan_Setting_Class();
			return $tabs->Setting_Tab_View( 'setting' );
		}
		add_shortcode('setting','shortcodesetting');
	
	//ログアウト中なら
	}else{

		//仕事リスト
		function shortcodelist(){
			$login_error = new Kantan_Login_Error();
			$error = $login_error->Error_View();
			return $error;
		}
		add_shortcode('list','shortcodelist');

		//受注書
		function shortcodeorder(){
			$login_error = new Kantan_Login_Error();
			$error = $login_error->Error_View();
			return $error;
		}
		add_shortcode('order','shortcodeorder');
		
		//クライアント
		function shortcodeclient(){
			$login_error = new Kantan_Login_Error();
			$error = $login_error->Error_View();
			return $error;
		}
		add_shortcode('client','shortcodeclient');
		
		//商品・サービス
		function shortcodeservice(){
			$login_error = new Kantan_Login_Error();
			$error = $login_error->Error_View();
			return $error;
		}
		add_shortcode('service','shortcodeservice');
		
		//協力会社
		function shortcodesupplier(){
			$login_error = new Kantan_Login_Error();
			$error = $login_error->Error_View();
			return $error;
		}
		add_shortcode('supplier','shortcodesupplier');
		
		//レポート
		function shortcodereport(){
			$login_error = new Kantan_Login_Error();
			$error = $login_error->Error_View();
			return $error;
		}
		add_shortcode('report','shortcodereport');
		
		//設定
		function shortcodesetting(){
			$login_error = new Kantan_Login_Error();
			$error = $login_error->Error_View();
			return $error;
		}
		add_shortcode('setting','shortcodesetting');
	}
}