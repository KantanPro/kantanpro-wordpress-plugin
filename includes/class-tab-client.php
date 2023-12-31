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
        // 顧客データの送信フォームを表示
        $this->display_client_form();
        echo '<br>';
        echo '<h2>顧客リスト</h2>';
        
        // 保存されている顧客データを表示
        $this->display_clients();

        // フィードバックメッセージの表示
        if (isset($_SESSION['ktp_client_add_success'])) {
            echo '<div class="notice notice-success">' . esc_html($_SESSION['ktp_client_add_success']) . '</div>';
            unset($_SESSION['ktp_client_add_success']);
        }

        if (isset($_SESSION['ktp_client_add_error'])) {
            echo '<div class="notice notice-error">' . esc_html($_SESSION['ktp_client_add_error']) . '</div>';
            unset($_SESSION['ktp_client_add_error']);
        }

    }

    // 顧客データの送信フォームを表示
    private function display_client_form() {
        $nonce = wp_create_nonce('ktp_add_client_nonce');
        ?>
        <form id="ktp-client-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="ktp_add_client">
            <input type="hidden" name="ktp_nonce" value="<?php echo $nonce; ?>">
            <!-- その他のフォームフィールド -->
            <input type="submit" value="登録">
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
        $table_name = $wpdb->prefix . 'ktp_client';

        $clients = $wpdb->get_results("SELECT * FROM $table_name");

        if ($clients) {
            echo '<table>';
            echo '<tr><th>ID</th><th>名前</th><th>メールアドレス</th><th>操作</th></tr>';
            foreach ($clients as $client) {
                echo '<tr>';
                echo '<td>' . esc_html($client->id) . '</td>';
                echo '<td>' . esc_html($client->name) . '</td>';
                echo '<td>' . esc_html($client->email) . '</td>';
                echo '<td><button class="ktp-delete-client" data-id="' . esc_attr($client->id) . '">削除</button></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>顧客データはありません。</p>';
        }
    }
}

new KTP_Tab_Client();
