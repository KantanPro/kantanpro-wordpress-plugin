<?php
/*
Plugin Name: kantan pro wp
Description: カンタンProWP
Version: 1.0
*/

if (!defined('ABSPATH')) {
    exit;
}

// 定数を定義
if (!defined('MY_PLUGIN_VERSION')) {
    define('MY_PLUGIN_VERSION', '1.0');
}
if (!defined('MY_PLUGIN_PATH')) {
    define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('MY_PLUGIN_URL')) {
    define('MY_PLUGIN_URL', plugins_url('/', __FILE__));
}

// クラスファイルをインクルード
include 'includes/class-tab-list.php';
include 'includes/class-tab-order.php'; // 受注書クラス
include 'includes/class-tab-client.php';
include 'includes/class-tab-service.php';
include 'includes/class-tab-supplier.php';
include 'includes/class-tab-report.php';
include 'includes/class-tab-setting.php';
include 'includes/class-login-error.php';
include 'includes/class-view-tab.php';
// include "js/view.js"; // JS
// include "includes/kpw-admin-form.php"; // 管理画面に追加


// カンタンProをロード
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
add_action('wp_enqueue_scripts', 'ktpwp_scripts_and_styles');

// テーブル用の関数を登録
register_activation_hook( __FILE__, 'Create_Table' ); // テーブル作成
register_activation_hook( __FILE__, 'Update_Table' ); // テーブル更新

# テーブル作成関数
function Create_Table() {
    global $wpdb;

    // テーブル名を定義
    $table_name = $wpdb->prefix . 'example_table';

    // テーブル作成用のSQL
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // テーブルを作成
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

# テーブル更新関数
function Update_Table() {
    global $wpdb;

    // 更新するテーブル名を定義
    $table_name = $wpdb->prefix . 'example_table';

    // テーブル更新用のSQL（例: 新しいカラムを追加）
    $sql = "ALTER TABLE $table_name ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL AFTER created_at;";

    // テーブルを更新
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $wpdb->query( $sql );
}

function KTPWP_Index(){

	//すべてのタブのショートコード[kantanAllTab]
	function kantanAllTab(){

		//ログイン中なら
		if (is_user_logged_in()) {

			// ユーザーのログインログアウト状況を取得するためのAjaxを登録
			add_action('wp_ajax_get_logged_in_users', 'get_logged_in_users');
			add_action('wp_ajax_nopriv_get_logged_in_users', 'get_logged_in_users');

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

			// 現在メインのログインユーザー情報を取得
			global $current_user;

			// ログアウトのリンク
			$logout_link = wp_logout_url();

				// ヘッダー表示ログインユーザー名など
				$login_user = $current_user->nickname;
				$front_message = <<<END
				<div class="ktp_header">
				ログイン中：$login_user さん&emsp;<a href="$logout_link">ログアウト</a>&emsp;<a href="/">更新</a>&emsp;
				</div>
				END;
				// $front_message = <<<END
				// <div class="ktp_header">
				// ログイン中：$login_user さん&emsp;<a href="$logout_link">ログアウト</a>&emsp;<a href="/">更新</a>&emsp;
				// 	<div id="zengo" class="zengo">
				// 	<a href="#" id="zengoBack" class="zengoButton"> < </a>&emsp;<a href="#" id="zengoForward" class="zengoButton"> > </a>
				// 	</div>
				// </div>
				// END;
		
				//仕事リスト
				$list = new Kantan_List_Class();
				$tab_name = 'list';
				$list->Create_Table( $tab_name );
				$list->Update_Table( $tab_name );
				$view = $list->View_Table( $tab_name );
				$list_content = $view;
				// $list_content = $list->List_Tab_View( 'list' );

				//受注書
				$tabs = new Kantan_Order_Class();
				$tab_name = 'order';
				$tabs->Create_Table( $tab_name );
				$tabs->Update_Table( $tab_name );
				$view = $tabs->View_Table( $tab_name );
				$order_content = $view;
				// $order_content = $tabs->Order_Tab_View( 'order' );
				
				//クライアント				
				$tabs = new Kantan_Client_Class();
				$tab_name = 'client';
				$tabs->Create_Table( $tab_name );
				$tabs->Update_Table( $tab_name );
				$view = $tabs->View_Table( $tab_name );
				$client_content = $view;
				
				//商品・サービス
				$tabs = new Kantan_Service_Class();
				$service_content = $tabs->Service_Tab_View( 'service' );
				
				//協力会社
				$tabs = new Kantan_Supplier_Class();
				$tab_name = 'supplier';
				$tabs->Create_Table( $tab_name );
				$tabs->Update_Table( $tab_name );
				$view = $tabs->View_Table( $tab_name );
				$supplier_content = $view;

				//レポート
				$tabs = new Kantan_Report_Class();
				$report_content = $tabs->Report_Tab_View( 'report' );
				
				//設定
				$tabs = new Kantan_Setting_Class();
				$setting_content = $tabs->Setting_Tab_View( 'setting' );

				// view
				$view = new view_tabs_Class();
				$tab_view = $view ->TabsView( $list_content, $order_content, $client_content, $service_content, $supplier_content, $report_content, $setting_content );
				return $front_message . $tab_view;


		} else {
			$login_error = new Kantan_Login_Error();
			$error = $login_error->Error_View();
			return $error;
		}
	}
	add_shortcode('kantanAllTab','kantanAllTab');

}
