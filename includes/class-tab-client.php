<?php

class KTP_Tab_Client {
    // コンストラクタ
    public function __construct() {
        add_action('admin_menu', array($this, 'add_client_menu'));
        add_action('admin_post_ktp_add_client', array($this, 'handle_form_submission'));
        add_action('admin_post_ktp_delete_client', array($this, 'handle_delete_submission'));
    }

    // 管理メニューに顧客タブを追加
    public function add_client_menu() {
        add_submenu_page(
            'ktp-main-menu',
            '顧客',
            '顧客',
            'manage_options',
            'ktp-tab-client',
            array($this, 'client_page_content')
        );
    }

    // 顧客タブのページコンテンツを表示
    public function client_page_content() {
        echo '<h1>顧客管理</h1>';
        $this->display_client_form();
        $this->display_clients();
    }

    // 顧客データの送信フォームを表示
    private function display_client_form() {
        $nonce = wp_create_nonce('ktp_add_client_nonce');
        ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
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
            $wpdb->prefix . 'ktp_client',
            array('name' => $name, 'email' => $email),
            array('%s', '%s')
        );

        // 現在のページにリダイレクト
        wp_redirect($_SERVER['HTTP_REFERER']);
        exit;
    }

    // 顧客データの削除処理
    public function handle_delete_submission() {
        global $wpdb;

        // ノンスの検証
        if (!isset($_POST['ktp_nonce']) || !wp_verify_nonce($_POST['ktp_nonce'], 'ktp_delete_client_nonce')) {
            wp_die(__('Invalid nonce specified', 'ktp'), __('Error', 'ktp'), array(
                'response'  => 403,
                'back_link' => 'admin.php?page=ktp-tab-client',
            ));
        }

        // データの削除
        $client_id = intval($_POST['client_id']);
        $wpdb->delete(
            $wpdb->prefix . 'ktp_client',
            array('id' => $client_id),
            array('%d')
        );

        // 現在のページにリダイレクト
        wp_redirect($_SERVER['HTTP_REFERER']);
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
                echo '<td>';
                // 削除ボタンの追加
                $delete_nonce = wp_create_nonce('ktp_delete_client_nonce');
                echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
                echo '<input type="hidden" name="action" value="ktp_delete_client">';
                echo '<input type="hidden" name="ktp_nonce" value="' . $delete_nonce . '">';
                echo '<input type="hidden" name="client_id" value="' . esc_attr($client->id) . '">';
                echo '<input type="submit" value="削除">';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>顧客データはありません。</p>';
        }
    }
}

new KTP_Tab_Client();
