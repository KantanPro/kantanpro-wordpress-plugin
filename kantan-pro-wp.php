<?php
/**
 * Plugin Name: kantan pro wp
 * Description: カンタンProWP
 * Version: 1.0
 */
//2023/12/23MacBookPro


// バージョン管理
if ( ! defined( 'WPINC' ) ) {
    die;
}

// WordPressが直接アクセスされるのを防ぐ
defined( 'ABSPATH' ) || exit;

// 定数の定義
define( 'KTP_VERSION', '1.0' );
define( 'KTP_PATH', plugin_dir_path( __FILE__ ) );
define( 'KTP_URL', plugins_url( '/', __FILE__ ) );

// インクルードステートメントを確認
include_once KTP_PATH . 'includes/class-tab-list.php';
include_once KTP_PATH . 'includes/class-tab-order.php';
include_once KTP_PATH . 'includes/class-tab-client.php';
include_once KTP_PATH . 'includes/class-tab-service.php';
include_once KTP_PATH . 'includes/class-tab-supplier.php';
include_once KTP_PATH . 'includes/class-tab-report.php';
include_once KTP_PATH . 'includes/class-tab-setting.php';
include_once KTP_PATH . 'includes/class-login-error.php';
include_once KTP_PATH . 'includes/class-view-tab.php';


// スタイルとスクリプトの登録
function ktp_enqueue_scripts() {
    wp_register_style('ktp-style', KTP_URL . 'css/styles.css', [], KTP_VERSION, 'all');
    wp_enqueue_style('ktp-style');

    wp_register_script('ktp-script', KTP_URL . 'js/ktpjs.js', [], KTP_VERSION, true);
    wp_enqueue_script('ktp-script');
}
add_action('wp_enqueue_scripts', 'ktp_enqueue_scripts');

// データベーステーブルの作成と更新
// (ここに以前のデータベーステーブル作成コードを続ける)

// ショートコードの追加
function kantan_all_tab_shortcode() {
    ob_start();
    ?>
<div id="ktp-tabs">
    <div class="tab active" id="tab-list">仕事リスト</div>
    <div class="tab" id="tab-order">受注書</div>
    <div class="tab" id="tab-service">商品・サービス</div>
    <div class="tab" id="tab-client">顧客</div>
    <div class="tab" id="tab-supplier">協力会社</div>
    <div class="tab" id="tab-report">レポート</div>
    <div class="tab" id="tab-setting">設定</div>
</div>

<div id="tab-content">
    <div class="content active" id="content-list">仕事リストのコンテンツ...</div>
    <div class="content" id="content-order">受注書のコンテンツ...</div>
    <div class="content" id="content-service">商品・サービスのコンテンツ...</div>
    <div class="content" id="content-client">顧客のコンテンツ...</div>
    <div class="content" id="content-supplier">協力会社のコンテンツ...</div>
    <div class="content" id="content-report">レポートのコンテンツ...</div>
    <div class="content" id="content-setting">設定のコンテンツ...</div>
</div>

    <?php
    return ob_get_clean();
}
add_shortcode('kantanAllTab', 'kantan_all_tab_shortcode');

// 以下に、各タブのコンテンツを生成するためのクラス定義を続ける
// 例えば、TabListクラスでは、display_listメソッドを定義して、仕事リストの内容を出力する
// また、TabOrderクラスでは、display_orderメソッドを定義して、受注書の内容を出力する
// その他のタブについても、同様に、各タブのコンテンツを出力するメソッドを定義する
// そして、各タブのコンテンツを出力するためのショートコードを定義する
// 例えば、仕事リストのコンテンツを出力するショートコードは、[kantanListTab]とする
// また、受注書のコンテンツを出力するショートコードは、[kantanOrderTab]とする
// その他のタブについても、同様に、各タブのコンテンツを出力するショートコードを定義する
// ここでは、TabListクラスとTabOrderクラスの定義を続ける
// 以下のコードを、includes/class-tab-list.phpに追加
// <?php
//
// class KTP_Tab_List {
//     public function __construct() {
//         add_action('admin_menu', array($this, 'add_list_menu'));
//     }

//     public function display() {
//         // ここに表示する内容を実装します。
//         echo '仕事じゃ';
//     }

//     public function add_list_menu() {
//         add_submenu_page(
//             'ktp-main-menu', // 親メニューのスラッグ
//             '仕事リスト', // ページタイトル
//             '仕事リスト', // メニュータイトル
//             'manage_options', // 権限
//             'ktp-tab-list', // メニュースラッグ
//             array($this, 'list_page_content') // 表示内容を生成するコールバック関数
//         );
//     }

//     public function list_page_content() {
//         echo '<h1>仕事リスト</h1>';
//         echo '<p>ここに仕事リストの管理と表示に関するコンテンツを表示します。</p>';
//         // ここに仕事リストデータを取得し、表示するコードを追加
//         // 例えば、WordPressのデータベースから仕事リストデータを取得し、表形式で表示する
//     }

//     public function display_list() {
//         // ここに仕事リストの内容を実装します。
//         echo '<h3>仕事リスト</h3>';
//     }
// }

// // インスタンス化
// new KTP_Tab_List();
// 以下のコードを、includes/class-tab-order.phpに追加
// <?php
//
// class KTP_Tab_Order {
//     public function __construct() {
//         add_action('admin_menu', array($this, 'add_order_menu'));
//     }

//     public function display() {
//         // ここに表示する内容を実装します。
//         echo '<h3>受注書</h3>';
//     }

//     public function add_order_menu() {
//         add_submenu_page(
//             'ktp-main-menu', // 親メニューのスラッグ
//             '受注書', // ページタイトル
//             '受注書', // メニュータイトル
//             'manage_options', // 権限
//             'ktp-tab-order', // メニュースラッグ
//             array($this, 'order_page_content') // 表示内容を生成するコールバック関数
//         );
//     }

//     public function order_page_content() {
//         echo '<h1>受注書管理</h1>';
//         echo '<p>ここに受注書の管理と表示に関するコンテンツを表示します。</p>';
//         // ここに受注書のデータを取得し、表示するコードを追加
//         // 例えば、WordPressのデータベースから受注データを取得し、表形式で表示する
//     }

//     public function display_order() {
//         // ここに受注書の内容を実装します。
//         echo '<h3>受注書</h3>';
//     }
// }

// // インスタンス化
// new KTP_Tab_Order();
// 以下のコードを、includes/class-tab-client.phpに追加
// <?php
//  class KTP_Tab_Client {
//      public function __construct() {
//          add_action('admin_menu', array($this, 'add_client_menu'));
//      }

//      public function display() {
//          // ここに表示する内容を実装します。
//          echo '<h3>顧客管理</h3>';
//      }

//      public function add_client_menu() {
//          add_submenu_page(
//              'ktp-main-menu', // 親メニューのスラッグ
//              '顧客', // ページタイトル
//              '顧客', // メニュータイトル
//              'manage_options', // 権限
//              'ktp-tab-client', // メニュースラッグ
//              array($this, 'client_page_content') // 表示内容を生成するコールバック関数
//          );
//      }

//      public function client_page_content() {
//          echo '<h1>顧客管理</h1>';
//          echo '<p>ここに顧客の管理と表示に関するコンテンツを表示します。</p>';
//          // ここに顧客データを取得し、表示するコードを追加
//          // 例えば、WordPressのデータベースから顧客データを取得し、表形式で表示する
//      }

//      public function display_client() {
//          // ここに顧客の内容を実装します。
//          echo '<h3>顧客</h3>';
//      }
//  }

//  // インスタンス化
//  new KTP_Tab_Client();


