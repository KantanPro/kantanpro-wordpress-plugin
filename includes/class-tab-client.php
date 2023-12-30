<?php

class KTP_Tab_Client {
    // コンストラクタ
    public function __construct() {
        // フォーム送信の処理を行うためのフック
        add_action('admin_post_ktp_add_client', array($this, 'handle_form_submission'));
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
    }

    // 顧客データの送信フォームを表示
    private function display_client_form() {
        // フォームのセキュリティ強化のためのノンスを生成
        $nonce = wp_create_nonce('ktp_add_client_nonce');
        ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
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

    // フォーム送信時の処理
    public function handle_form_submission() {
        global $wpdb;

        // ノンスの検証
        if (!isset($_POST['ktp_nonce']) || !wp_verify_nonce($_POST['ktp_nonce'], 'ktp_add_client_nonce')) {
            wp_die(__('Invalid nonce specified', 'ktp'), __('Error', 'ktp'), array(
                'response'  => 403,
                'back_link' => 'admin.php?page=ktp-tab-client',
            ));
        }

        // フォームデータのサニタイズ
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);

        // データベースにデータを挿入
        $wpdb->insert(
            $wpdb->prefix . 'ktp_client', // テーブル名
            array('name' => $name, 'email' => $email),
            array('%s', '%s')
        );

        // // 前のページにリダイレクト
        // wp_redirect($_SERVER['HTTP_REFERER']);
        // exit;

        // データベースにデータを挿入後、顧客タブにリダイレクト
        $redirect_url = admin_url('/page=ktp-tab-client');
        wp_redirect($redirect_url);
        exit;

    }

    // 保存されている顧客データを表示
    private function display_clients() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_client'; // テーブル名

        // データベースから顧客データを取得
        $clients = $wpdb->get_results("SELECT * FROM $table_name");

        // 顧客データがあればテーブル形式で表示
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
