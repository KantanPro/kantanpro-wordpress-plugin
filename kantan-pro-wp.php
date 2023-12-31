<?php
/**
 * Plugin Name: Kantan Pro WP
 * Description: カンタンProWPプラグインの説明。
 * Version: 1.0
 * Author: あなたの名前
 */

// ページがロードされたときにURLのパラメータをチェック
add_action('wp_footer', 'activate_client_tab_in_plugin');
function activate_client_tab_in_plugin() {
    if (isset($_GET['tab']) && $_GET['tab'] == 'clients') {
        echo '<script type="text/javascript">
                jQuery(document).ready(function($) {
                    // 顧客タブをアクティブにする
                    $(".tab").removeClass("active"); // すべてのタブからactiveクラスを削除
                    $("#tab-client").addClass("active"); // 顧客タブにactiveクラスを追加
                    $(".tab-content").hide(); // すべてのタブコンテンツを非表示
                    $("#content-client").show(); // 顧客タブのコンテンツを表示
                });
              </script>';
    }
}
// 定数の定義
define('KTP_VERSION', '1.0');
define('KTP_PATH', plugin_dir_path(__FILE__));
define('KTP_URL', plugins_url('/', __FILE__));

// インクルードステートメント
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

    wp_enqueue_script('ktp-ajax-script', KTP_URL . 'js/ktp-ajax.js', ['jquery'], KTP_VERSION, true);
    wp_localize_script('ktp-ajax-script', 'ktp_ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ktp_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'ktp_enqueue_scripts');

// ショートコードの追加
function kantan_all_tab_shortcode() {
    ob_start();
    ?>
    <div id="ktp-tabs">
        <div class="tab" id="tab-list">仕事リスト</div>
        <div class="tab" id="tab-order">受注書</div>
        <div class="tab" id="tab-client">顧客</div>
        <div class="tab" id="tab-service">商品・サービス</div>
        <div class="tab" id="tab-supplier">協力会社</div>
        <div class="tab" id="tab-report">レポート</div>
        <div class="tab" id="tab-setting">設定</div>
    </div>

    <div id="tab-content">
        <div class="content" id="content-list">
            <!-- 仕事リストのコンテンツ -->
        </div>
        <div class="content" id="content-order">
            <!-- 受注書のコンテンツ -->
        </div>
        <div class="content" id="content-client">
            <?php
            $client_tab = new KTP_Tab_Client();
            $client_tab->client_page_content();
            ?>
        </div>
        <div class="content" id="content-service">
            <!-- 商品・サービスのコンテンツ -->
        </div>
        <div class="content" id="content-supplier">
            <!-- 協力会社のコンテンツ -->
        </div>
        <div class="content" id="content-report">
            <!-- レポートのコンテンツ -->
        </div>
        <div class="content" id="content-setting">
            <!-- 設定のコンテンツ -->
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('kantanAllTab', 'kantan_all_tab_shortcode');

// 顧客追加のAJAXリクエストのハンドリング
function ktp_add_client_ajax() {
    check_ajax_referer('ktp_nonce', 'nonce');
    global $wpdb;

    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);

    // 入力値の検証
    if (empty($name) || empty($email)) {
        wp_send_json_error(array('message' => '名前とメールアドレスは必須です。'));
        return;
    }

    $result = $wpdb->insert(
        $wpdb->prefix . 'ktp_client',
        array('name' => $name, 'email' => $email),
        array('%s', '%s')
    );

    if ($result) {
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => '顧客の追加に失敗しました。'));
    }
}
add_action('wp_ajax_ktp_add_client', 'ktp_add_client_ajax');
add_action('wp_ajax_nopriv_ktp_add_client', 'ktp_add_client_ajax');


// 顧客削除のAJAXリクエストのハンドリング
function ktp_delete_client_ajax() {
    check_ajax_referer('ktp_nonce', 'nonce');
    global $wpdb;
    $client_id = intval($_POST['id']);

    $result = $wpdb->delete(
        $wpdb->prefix . 'ktp_client',
        array('id' => $client_id),
        array('%d')
    );

    if ($result) {
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => '顧客の削除に失敗しました。'));
    }
}
add_action('wp_ajax_ktp_delete_client', 'ktp_delete_client_ajax');
add_action('wp_ajax_nopriv_ktp_delete_client', 'ktp_delete_client_ajax');

// 顧客リストを取得するAjaxリクエストのハンドリング
add_action('wp_ajax_ktp_get_client_list', 'ktp_get_client_list');
function ktp_get_client_list() {
    global $wpdb;

    $clients = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ktp_client");

    if ($clients) {
        $client_list_html = '<ul>';
        foreach ($clients as $client) {
            $client_list_html .= '<li>' . esc_html($client->name) . ' (' . esc_html($client->email) . ')</li>';
        }
        $client_list_html .= '</ul>';

        wp_send_json_success(['data' => $client_list_html]);
    } else {
        wp_send_json_error(['message' => '顧客リストの取得に失敗しました']);
    }
}

// 顧客追加の処理
function ktp_handle_add_client() {
    if (!isset($_POST['ktp_nonce']) || !wp_verify_nonce($_POST['ktp_nonce'], 'ktp_add_client_nonce')) {
        // ノンスの検証に失敗した場合
        wp_die('不正なリクエストです。');
    }

    global $wpdb;
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);

    // 入力値の検証
    if (empty($name) || empty($email)) {
        // エラーメッセージをセッションに保存
        $_SESSION['ktp_client_add_error'] = '名前とメールアドレスは必須です。';
    } else {
        $result = $wpdb->insert(
            $wpdb->prefix . 'ktp_client',
            array('name' => $name, 'email' => $email),
            array('%s', '%s')
        );

        if ($result) {
            // 成功メッセージをセッションに保存
            $_SESSION['ktp_client_add_success'] = '顧客を追加しました。';
        } else {
            // エラーメッセージをセッションに保存
            $_SESSION['ktp_client_add_error'] = '顧客の追加に失敗しました。';
        }
    }

    // 顧客リストページにリダイレクト
    wp_redirect(home_url('/client-list'));
    exit;
}
add_action('admin_post_ktp_add_client', 'ktp_handle_add_client');
add_action('admin_post_nopriv_ktp_add_client', 'ktp_handle_add_client');
