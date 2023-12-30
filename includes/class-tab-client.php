<?php

class KTP_Tab_Client {
    // コンストラクタ
    public function __construct() {
        // フォーム送信の処理を行うためのフック
        add_action('wp_ajax_ktp_add_client', array($this, 'handle_add_client'));
        add_action('wp_ajax_nopriv_ktp_add_client', array($this, 'handle_add_client'));
    }

    // 顧客タブのページコンテンツを表示
    public function client_page_content() {
        echo '<h2>顧客詳細</h2>';
        $this->display_client_form();
        echo '<br>';
        echo '<h2>顧客リスト</h2>';
        $this->display_clients();
    }

    // 顧客データの送信フォームを表示
    private function display_client_form() {
        $nonce = wp_create_nonce('ktp_add_client_nonce');
        ?>
        <form id="ktp-client-form" method="post">
            <input type="hidden" name="action" value="ktp_add_client">
            <input type="hidden" name="ktp_nonce" value="<?php echo $nonce; ?>">
            <label for="name">名前：</label>
            <input type="text" id="name" name="name" required><br>
            <label for="email">メールアドレス：</label>
            <input type="email" id="email" name="email" required><br>
            <br>
            <input type="submit" value="登録">
            <br>
        </form>
        <?php
    }

    // 顧客登録のAJAX処理
    public function handle_add_client() {
        check_ajax_referer('ktp_add_client_nonce', 'ktp_nonce');
        global $wpdb;

        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);

        $result = $wpdb->insert(
            $wpdb->prefix . 'ktp_client',
            array('name' => $name, 'email' => $email),
            array('%s', '%s')
        );

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    // 保存されている顧客データを表示
    private function display_clients() {
        global $wpdb;
        $table_name = 'ktp_client'; // テーブル名を修正

        $clients = $wpdb->get_results("SELECT * FROM $table_name");

        if ($clients) {
            // 顧客データの表示処理
        } else {
            echo '<p>顧客データはありません。</p>';
        }
    }
}
new KTP_Tab_Client();
