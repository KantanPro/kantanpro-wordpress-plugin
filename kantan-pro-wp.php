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
include "includes/class-view-tab.php"; // タブビュークラス
include "includes/kpw-admin-form.php"; // 管理画面に追加


// カンタンProをロード
add_action('plugins_loaded','KTPWP_Index'); // カンタンPro本体

// // Ajaxによるタブインターフェイス実験
// add_action('wp_ajax_ktp_fetch_tab_content', 'ktp_fetch_tab_content');
// add_action('wp_ajax_nopriv_ktp_fetch_tab_content', 'ktp_fetch_tab_content');

// function ktp_fetch_tab_content() {
//     $tab = $_POST['tab'];
//     $content = '';

//     switch ($tab) {
//         case 'client':
//             $clientClass = new Kntan_Client_Class();
//             $content = $clientClass->View_Table('client');
//             break;
//         case 'list':
//             $listClass = new Kantan_List_Class();
//             $content = $listClass->List_Tab_View('list');
//             break;
//         case 'order':
//             $orderClass = new Kntan_Order_Class();
//             $content = $orderClass->Order_Tab_View('order');
//             break;
//         case 'report':
//             $reportClass = new Kntan_Report_Class();
//             $content = $reportClass->Report_Tab_View('report');
//             break;
//         case 'service':
//             $serviceClass = new Kntan_Service_Class();
//             $content = $serviceClass->Service_Tab_View('service');
//             break;
//         case 'setting':
//             $settingClass = new Kntan_Setting_Class();
//             $content = $settingClass->Setting_Tab_View('setting');
//             break;
//         // 他のタブについても同様に処理を追加
//         default:
//             $content = '指定されたタブは存在しません。';
//     }

//     echo $content;
//     wp_die();
// }


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
register_activation_hook( __FILE__, 'Create_Table' ); // テーブル作成
register_activation_hook( __FILE__, 'Update_Table' ); // テーブル更新
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
				<div class="ktp_header">
				ログイン中：$login_user さん　<a href="$logout_link">ログアウト</a>　<a href="/">更新</a>　
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