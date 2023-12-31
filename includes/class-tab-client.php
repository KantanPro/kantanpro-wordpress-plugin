<?php

class KTP_Tab_Client {

    public function __construct() {
        // AJAXアクションの代わりにadmin_postアクションを使用
        add_action('admin_post_ktp_add_client', array($this, 'handle_add_client'));
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
        <form id="ktp-client-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="ktp_add_client">
            <input type="hidden" name="ktp_nonce" value="<?php echo $nonce; ?>">
            <label for="name">名前：</label>
            <input type="text" id="name" name="name" required><br>
            <label for="email">メールアドレス：</label>
            <input type="email" id="email" name="email" required><br>
            <input type="submit" value="登録">
        </form>
        <?php
    }

    // 顧客登録の処理
    public function handle_add_client() {
        // ノンスの検証
        check_admin_referer('ktp_add_client_nonce', 'ktp_nonce');
    
        global $wpdb;
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
    
        $result = $wpdb->insert(
            $wpdb->prefix . 'ktp_client', // テーブル名を確認
            array('name' => $name, 'email' => $email),
            array('%s', '%s')
        );
    
        // 結果に基づいてリダイレクト
        if ($result) {
            $_SESSION['ktp_client_add_success'] = '顧客が正常に登録されました。';
        } else {
            $_SESSION['ktp_client_add_error'] = '顧客の登録に失敗しました。';
        }
    
        wp_redirect(home_url('/?tab=client'));
        exit;
    }
    
    // 顧客削除時の処理
    public function handle_delete_client() {
        // ノンスの検証
        check_admin_referer('ktp_delete_client_nonce', 'ktp_nonce');
    
        global $wpdb;
        $client_id = intval($_POST['client_id']);
    
        $result = $wpdb->delete(
            $wpdb->prefix . 'ktp_client',
            array('id' => $client_id),
            array('%d')
        );
    
        // 結果に基づいてリダイレクト
        if ($result) {
            $_SESSION['ktp_client_delete_success'] = '顧客が正常に削除されました。';
        } else {
            $_SESSION['ktp_client_delete_error'] = '顧客の削除に失敗しました。';
        }
    
        wp_redirect(home_url('/?tab=client'));
        exit;
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
