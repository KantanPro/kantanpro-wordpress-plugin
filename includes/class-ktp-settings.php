<?php
if (!defined('ABSPATH')) exit;

class KTP_Settings {
    private static $instance = null;
    private $options_group = 'ktp_settings';
    private $option_name = 'ktp_smtp_settings';
    private $test_mail_message = '';
    private $test_mail_status = '';

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('phpmailer_init', array($this, 'setup_smtp_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }

    /**
     * 管理画面のスタイルシートを読み込み
     */
    public function enqueue_admin_styles($hook) {
        // KTPWPの設定ページでのみCSSを読み込み
        if (strpos($hook, 'ktp-') !== false) {
            wp_enqueue_style(
                'ktp-admin-settings',
                plugin_dir_url(dirname(__FILE__)) . 'css/ktp-admin-settings.css',
                array(),
                '1.0.1'
            );
            // 設定タブ用のCSSも読み込み
            wp_enqueue_style(
                'ktp-setting-tab',
                plugin_dir_url(dirname(__FILE__)) . 'css/ktp-setting-tab.css',
                array(),
                '1.0.1'
            );
        }
    }

    public static function activate() {
        $option_name = 'ktp_smtp_settings';
        if (false === get_option($option_name)) {
            add_option($option_name, array(
                'email_address' => '',
                'smtp_host' => '',
                'smtp_port' => '',
                'smtp_user' => '',
                'smtp_pass' => '',
                'smtp_secure' => '',
                'smtp_from_name' => ''
            ));
        }
    }

    public function add_plugin_page() {
        // メインメニュー
        add_menu_page(
            'KTPWP設定', // ページタイトル
            'KTPWP設定', // メニュータイトル
            'manage_options', // 権限
            'ktp-settings', // メニューのスラッグ
            array($this, 'create_admin_page'), // 表示を処理する関数
            'dashicons-admin-generic', // アイコン
            80 // メニューの位置
        );
        
        // サブメニュー - メール・SMTP設定（メインと同じ表示）
        add_submenu_page(
            'ktp-settings', // 親メニューのスラッグ
            'メール・SMTP設定', // ページタイトル
            'メール・SMTP設定', // メニュータイトル
            'manage_options', // 権限
            'ktp-settings', // メニューのスラッグ（親と同じにすると選択時にハイライト）
            array($this, 'create_admin_page') // 表示を処理する関数
        );
        
        // サブメニュー - ライセンス設定
        add_submenu_page(
            'ktp-settings', // 親メニューのスラッグ
            'ライセンス設定', // ページタイトル
            'ライセンス設定', // メニュータイトル
            'manage_options', // 権限
            'ktp-license', // メニューのスラッグ
            array($this, 'create_license_page') // 表示を処理する関数
        );
    }

    public function create_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('この設定ページにアクセスする権限がありません。'));
        }

        // 初期設定値がない場合は作成
        if (false === get_option($this->option_name)) {
            add_option($this->option_name, array(
                'email_address' => '',
                'smtp_host' => '',
                'smtp_port' => '',
                'smtp_user' => '',
                'smtp_pass' => '',
                'smtp_secure' => '',
                'smtp_from_name' => ''
            ));
        }

        $options = get_option($this->option_name);
        ?>
        <div class="wrap ktp-admin-wrap">
            <h1><span class="dashicons dashicons-email-alt"></span> メール・SMTP設定</h1>
            
            <?php 
            // タブナビゲーション
            $this->display_settings_tabs('mail');
            
            // 通知表示
            settings_errors('ktp_settings');
            
            if (isset($_POST['test_email'])) {
                $this->send_test_email();
            }

            // スタイリングされたコンテナ
            echo '<div class="ktp-settings-container">';
            
            // メール設定フォーム
            echo '<div class="ktp-settings-section">';
            echo '<form method="post" action="options.php">';
            settings_fields($this->options_group);
            
            global $wp_settings_sections, $wp_settings_fields;
            
            // メール設定セクションの出力
            if (isset($wp_settings_sections['ktp-settings']['email_setting_section'])) {
                $section = $wp_settings_sections['ktp-settings']['email_setting_section'];
                echo '<h2>' . esc_html($section['title']) . '</h2>';
                if ($section['callback']) call_user_func($section['callback'], $section);
                if (isset($wp_settings_fields['ktp-settings']['email_setting_section'])) {
                    echo '<table class="form-table">';
                    foreach ($wp_settings_fields['ktp-settings']['email_setting_section'] as $field) {
                        echo '<tr><th scope="row">' . esc_html($field['title']) . '</th><td>';
                        call_user_func($field['callback'], $field['args']);
                        echo '</td></tr>';
                    }
                    echo '</table>';
                }
            }
            
            // SMTP設定セクションの出力
            if (isset($wp_settings_sections['ktp-settings']['smtp_setting_section'])) {
                $section = $wp_settings_sections['ktp-settings']['smtp_setting_section'];
                echo '<h2>' . esc_html($section['title']) . '</h2>';
                if ($section['callback']) call_user_func($section['callback'], $section);
                if (isset($wp_settings_fields['ktp-settings']['smtp_setting_section'])) {
                    echo '<table class="form-table">';
                    foreach ($wp_settings_fields['ktp-settings']['smtp_setting_section'] as $field) {
                        echo '<tr><th scope="row">' . esc_html($field['title']) . '</th><td>';
                        call_user_func($field['callback'], $field['args']);
                        echo '</td></tr>';
                    }
                    echo '</table>';
                }
            }
            
            echo '<div class="ktp-submit-button">';
            submit_button('設定を保存', 'primary', 'submit', false);
            echo '</div>';
            echo '</form>';
            
            // テストメール送信フォーム
            echo '<div class="ktp-test-mail-form">';
            echo '<h3>テストメール送信</h3>';
            echo '<p>SMTPの設定が正しく機能しているか確認するためのテストメールを送信します。</p>';
            echo '<form method="post">';
            echo '<input type="hidden" name="test_email" value="1">';
            submit_button('テストメール送信', 'secondary', 'submit', false);
            echo '</form>';
            echo '</div>';
            
            // 印刷ボタンセクション
            // 印刷機能は削除されました
            
            echo '</div>'; // .ktp-settings-section
            echo '</div>'; // .ktp-settings-container
            ?>
        </div>
        <?php
    }
    
    /**
     * ライセンス設定ページの表示
     */
    public function create_license_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('この設定ページにアクセスする権限がありません。'));
        }
        ?>
        <div class="wrap ktp-admin-wrap">
            <h1><span class="dashicons dashicons-admin-network"></span> ライセンス設定</h1>
            
            <?php 
            // タブナビゲーション
            $this->display_settings_tabs('license');
            
            // 通知表示
            settings_errors('ktp_activation_key');
            ?>
            
            <div class="ktp-settings-container">
                <div class="ktp-settings-section">
                    <?php
                    // ライセンス設定（アクティベーションキー）フォーム
                    echo '<form method="post" action="options.php">';
                    settings_fields('ktp-group');
                    
                    // ライセンス設定セクションのみ出力
                    global $wp_settings_sections, $wp_settings_fields;
                    if (isset($wp_settings_sections['ktp-settings']['license_setting_section'])) {
                        $section = $wp_settings_sections['ktp-settings']['license_setting_section'];
                        if ($section['callback']) call_user_func($section['callback'], $section);
                        if (isset($wp_settings_fields['ktp-settings']['license_setting_section'])) {
                            echo '<table class="form-table">';
                            foreach ($wp_settings_fields['ktp-settings']['license_setting_section'] as $field) {
                                echo '<tr><th scope="row">' . esc_html($field['title']) . '</th><td>';
                                call_user_func($field['callback'], $field['args']);
                                echo '</td></tr>';
                            }
                            echo '</table>';
                        }
                    }
                    
                    echo '<div class="ktp-submit-button">';
                    submit_button('ライセンスを認証', 'primary', 'submit', false);
                    echo '</div>';
                    echo '</form>';
                    ?>
                    
                    <div class="ktp-license-info">
                        <h3>ライセンスについて</h3>
                        <p>KTPWPプラグインを利用するには有効なライセンスキーが必要です。ライセンスキーに関する問題がございましたら、サポートまでお問い合わせください。</p>
                        <p><a href="mailto:support@example.com" class="button button-secondary">サポートに問い合わせる</a></p>
                    </div>
                    
                    <!-- 印刷ボタンセクション -->
                    <!-- 印刷機能は削除されました -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 設定ページのタブナビゲーションを表示
     *
     * @param string $current_tab 現在選択されているタブ
     */
    private function display_settings_tabs($current_tab) {
        $tabs = array(
            'mail' => array(
                'name' => 'メール・SMTP設定',
                'url' => admin_url('?page=ktp-settings'),
                'icon' => 'dashicons-email-alt'
            ),
            'license' => array(
                'name' => 'ライセンス設定',
                'url' => admin_url('admin.php?page=ktp-license'),
                'icon' => 'dashicons-admin-network'
            )
        );
        
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $tab_id => $tab) {
            $active = ($current_tab === $tab_id) ? 'nav-tab-active' : '';
            echo '<a href="' . esc_url($tab['url']) . '" class="nav-tab ' . $active . '">';
            echo '<span class="dashicons ' . esc_attr($tab['icon']) . '"></span> ';
            echo esc_html($tab['name']);
            echo '</a>';
        }
        echo '</h2>';
    }

    public function page_init() {

        // アクティベーションキー保存時の通知
        if (isset($_POST['ktp_activation_key'])) {
            $old = get_option('ktp_activation_key');
            $new = sanitize_text_field($_POST['ktp_activation_key']);
            if ($old !== $new) {
                update_option('ktp_activation_key', $new);
                if (method_exists($this, 'show_notification')) {
                    $this->show_notification('アクティベーションキーを保存しました。', true);
                }
                add_settings_error('ktp_activation_key', 'activation_key_saved', 'アクティベーションキーを保存しました。', 'updated');
            }
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        register_setting(
            $this->options_group,
            $this->option_name,
            array($this, 'sanitize')
        );
        
        // 以前の設定ページから移行したアクティベーションキー設定
        register_setting(
            'ktp-group',
            'ktp_activation_key'
        );

        // メール設定セクション
        add_settings_section(
            'email_setting_section',
            'メール設定',
            array($this, 'print_section_info'),
            'ktp-settings'
        );

        // 自社メールアドレス
        add_settings_field(
            'email_address',
            '自社メールアドレス',
            array($this, 'email_address_callback'),
            'ktp-settings',
            'email_setting_section'
        );

        // SMTP設定セクション
        add_settings_section(
            'smtp_setting_section',
            'SMTP設定',
            array($this, 'print_smtp_section_info'),
            'ktp-settings'
        );

        // ライセンス設定セクション
        add_settings_section(
            'license_setting_section',
            'ライセンス設定',
            array($this, 'print_license_section_info'),
            'ktp-settings'
        );

        // アクティベーションキー
        add_settings_field(
            'activation_key',
            'アクティベーションキー',
            array($this, 'activation_key_callback'),
            'ktp-settings',
            'license_setting_section'
        );

        // SMTPホスト
        add_settings_field(
            'smtp_host',
            'SMTPホスト',
            array($this, 'smtp_host_callback'),
            'ktp-settings',
            'smtp_setting_section'
        );

        // SMTPポート
        add_settings_field(
            'smtp_port',
            'SMTPポート',
            array($this, 'smtp_port_callback'),
            'ktp-settings',
            'smtp_setting_section'
        );

        // SMTPユーザー
        add_settings_field(
            'smtp_user',
            'SMTPユーザー',
            array($this, 'smtp_user_callback'),
            'ktp-settings',
            'smtp_setting_section'
        );

        // SMTPパスワード
        add_settings_field(
            'smtp_pass',
            'SMTPパスワード',
            array($this, 'smtp_pass_callback'),
            'ktp-settings',
            'smtp_setting_section'
        );

        // 暗号化方式
        add_settings_field(
            'smtp_secure',
            '暗号化方式',
            array($this, 'smtp_secure_callback'),
            'ktp-settings',
            'smtp_setting_section'
        );

        // 送信者名
        add_settings_field(
            'smtp_from_name',
            '送信者名',
            array($this, 'smtp_from_name_callback'),
            'ktp-settings',
            'smtp_setting_section'
        );
    }

    public function sanitize($input) {
        $new_input = array();
        
        if (isset($input['email_address']))
            $new_input['email_address'] = sanitize_email($input['email_address']);
            
        if (isset($input['smtp_host']))
            $new_input['smtp_host'] = sanitize_text_field($input['smtp_host']);
            
        if (isset($input['smtp_port']))
            $new_input['smtp_port'] = sanitize_text_field($input['smtp_port']);
            
        if (isset($input['smtp_user']))
            $new_input['smtp_user'] = sanitize_text_field($input['smtp_user']);
            
        if (isset($input['smtp_pass']))
            $new_input['smtp_pass'] = $input['smtp_pass'];
            
        if (isset($input['smtp_secure']))
            $new_input['smtp_secure'] = sanitize_text_field($input['smtp_secure']);
            
        if (isset($input['smtp_from_name']))
            $new_input['smtp_from_name'] = sanitize_text_field($input['smtp_from_name']);

        return $new_input;
    }

    public function print_section_info() {
        print 'メール送信に関する基本設定を行います。';
    }

    public function print_smtp_section_info() {
        print 'SMTPサーバーを使用したメール送信の設定を行います。SMTPを利用しない場合は空欄のままにしてください。';
    }
    
    public function print_license_section_info() {
        print 'プラグインのライセンス情報を設定します。';
    }

    public function activation_key_callback() {
        $activation_key = get_option('ktp_activation_key');
        $has_license = !empty($activation_key);
        ?>
        <input type="text" id="ktp_activation_key" name="ktp_activation_key" 
               value="<?php echo esc_attr($activation_key); ?>" 
               style="width:320px;max-width:100%;"
               placeholder="XXXX-XXXX-XXXX-XXXX">
        <div class="ktp-license-status <?php echo $has_license ? 'active' : 'inactive'; ?>">
            <?php if ($has_license): ?>
                <span class="dashicons dashicons-yes-alt"></span> ライセンスキーが登録されています
            <?php else: ?>
                <span class="dashicons dashicons-warning"></span> ライセンスキーが未登録です
            <?php endif; ?>
        </div>
        <div style="font-size:12px;color:#555;margin-top:8px;">※ プラグインのライセンスキーを入力して、機能を有効化してください。</div>
        <?php
    }

    public function email_address_callback() {
        $options = get_option($this->option_name);
        ?>
        <input type="email" id="email_address" name="<?php echo esc_attr($this->option_name); ?>[email_address]" 
               value="<?php echo isset($options['email_address']) ? esc_attr($options['email_address']) : ''; ?>" 
               style="width:320px;max-width:100%;" required 
               pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" 
               placeholder="info@example.com">
        <div style="font-size:12px;color:#555;margin-top:4px;">※ サイトから届くメールが迷惑メールと認識されないよう、サイトのドメインと同じメールアドレスをご入力ください。</div>
        <?php
    }

    public function smtp_host_callback() {
        $options = get_option($this->option_name);
        ?>
        <input type="text" id="smtp_host" name="<?php echo esc_attr($this->option_name); ?>[smtp_host]" 
               value="<?php echo isset($options['smtp_host']) ? esc_attr($options['smtp_host']) : ''; ?>" 
               style="width:220px;max-width:100%;" 
               placeholder="smtp.example.com">
        <?php
    }

    public function smtp_port_callback() {
        $options = get_option($this->option_name);
        ?>
        <input type="text" id="smtp_port" name="<?php echo esc_attr($this->option_name); ?>[smtp_port]" 
               value="<?php echo isset($options['smtp_port']) ? esc_attr($options['smtp_port']) : ''; ?>" 
               style="width:80px;max-width:100%;" 
               placeholder="587">
        <?php
    }

    public function smtp_user_callback() {
        $options = get_option($this->option_name);
        ?>
        <input type="text" id="smtp_user" name="<?php echo esc_attr($this->option_name); ?>[smtp_user]" 
               value="<?php echo isset($options['smtp_user']) ? esc_attr($options['smtp_user']) : ''; ?>" 
               style="width:220px;max-width:100%;" 
               placeholder="user@example.com">
        <?php
    }

    public function smtp_pass_callback() {
        $options = get_option($this->option_name);
        ?>
        <input type="password" id="smtp_pass" name="<?php echo esc_attr($this->option_name); ?>[smtp_pass]" 
               value="<?php echo isset($options['smtp_pass']) ? esc_attr($options['smtp_pass']) : ''; ?>" 
               style="width:220px;max-width:100%;" 
               autocomplete="off">
        <?php
    }

    public function smtp_secure_callback() {
        $options = get_option($this->option_name);
        $selected = isset($options['smtp_secure']) ? $options['smtp_secure'] : '';
        ?>
        <select id="smtp_secure" name="<?php echo $this->option_name; ?>[smtp_secure]">
            <option value="" <?php selected($selected, ''); ?>>なし</option>
            <option value="ssl" <?php selected($selected, 'ssl'); ?>>SSL</option>
            <option value="tls" <?php selected($selected, 'tls'); ?>>TLS</option>
        </select>
        <?php
    }

    public function smtp_from_name_callback() {
        $options = get_option($this->option_name);
        ?>
        <input type="text" id="smtp_from_name" name="<?php echo esc_attr($this->option_name); ?>[smtp_from_name]" 
               value="<?php echo isset($options['smtp_from_name']) ? esc_attr($options['smtp_from_name']) : ''; ?>" 
               style="width:220px;max-width:100%;" 
               placeholder="会社名や担当者名">
        <?php
    }

    public function setup_smtp_settings($phpmailer) {
        try {
            $options = get_option($this->option_name);
            
            if (!empty($options['smtp_host']) && !empty($options['smtp_port']) && !empty($options['smtp_user']) && !empty($options['smtp_pass'])) {
                $phpmailer->isSMTP();
                $phpmailer->Host = $options['smtp_host'];
                $phpmailer->Port = $options['smtp_port'];
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = $options['smtp_user'];
                $phpmailer->Password = $options['smtp_pass'];
                
                if (!empty($options['smtp_secure'])) {
                    $phpmailer->SMTPSecure = $options['smtp_secure'];
                }
                
                $phpmailer->CharSet = 'UTF-8';
                
                if (!empty($options['email_address'])) {
                    $phpmailer->setFrom(
                        $options['email_address'],
                        !empty($options['smtp_from_name']) ? $options['smtp_from_name'] : $options['email_address'],
                        false
                    );
                }
            }
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) { error_log($e->getMessage()); }
        }
    }

    private function send_test_email() {
        $options = get_option($this->option_name);
        $to = $options['email_address'];
        $subject = '【KTPWP】SMTPテストメール';
        $body = "このメールはKTPWPプラグインのSMTPテスト送信です。\n\n送信元: {$options['email_address']}";
        $headers = array();
        
        if (!empty($options['smtp_from_name'])) {
            $headers[] = 'From: ' . $options['smtp_from_name'] . ' <' . $options['email_address'] . '>';
        } else {
            $headers[] = 'From: ' . $options['email_address'];
        }

        $sent = wp_mail($to, $subject, $body, $headers);
        
        if ($sent) {
            $this->test_mail_message = 'テストメールを送信しました。メールボックスをご確認ください。';
            $this->test_mail_status = 'success';
            
            // 成功通知を表示
            $this->show_notification('✉️ テストメールを送信しました。メールボックスをご確認ください。', true);
            
            add_settings_error(
                'ktp_settings',
                'test_mail_success',
                'テストメールを送信しました。メールボックスをご確認ください。',
                'updated'
            );
        } else {
            global $phpmailer;
            $error_message = '';
            if (isset($phpmailer) && is_object($phpmailer)) {
                $error_message = $phpmailer->ErrorInfo;
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('KTPWP SMTPテストメール送信失敗: ' . $error_message); }
            } else {
                $error_message = 'PHPMailerインスタンスが取得できませんでした';
                error_log('KTPWP SMTPテストメール送信失敗: ' . $error_message);
            }
            
            $this->test_mail_message = 'テストメールの送信に失敗しました。SMTP設定をご確認ください。';
            $this->test_mail_status = 'error';
            
            // エラー通知を表示
            $this->show_notification('⚠️ テストメールの送信に失敗しました。SMTP設定をご確認ください。', false);
            
            add_settings_error(
                'ktp_settings',
                'test_mail_error',
                'テストメールの送信に失敗しました。SMTP設定をご確認ください。',
                'error'
            );
        }
    }
    
    /**
     * 画面上部に一時的な通知を表示する
     *
     * @param string $message 表示するメッセージ
     * @param bool $success 成功メッセージかどうか（true=成功、false=エラー）
     */
    private function show_notification($message, $success = true) {
        $backgroundColor = $success ? '#4CAF50' : '#F44336';
        $icon = $success ? 'dashicons-yes-alt' : 'dashicons-warning';
        
        echo '<script>
            (function() {
                // 既存の通知があれば削除
                var existingNotification = document.getElementById("ktp-mail-notification");
                if (existingNotification && existingNotification.parentNode) {
                    existingNotification.parentNode.removeChild(existingNotification);
                }
                
                // 通知要素の作成
                var notification = document.createElement("div");
                notification.id = "ktp-mail-notification";
                notification.innerHTML = "<span class=\"dashicons ' . esc_js($icon) . '\"></span><p>' . esc_js($message) . '</p>";
                notification.style.position = "fixed";
                notification.style.top = "32px";
                notification.style.left = "50%";
                notification.style.transform = "translateX(-50%)";
                notification.style.backgroundColor = "' . $backgroundColor . '";
                notification.style.color = "white";
                notification.style.padding = "12px 20px";
                notification.style.borderRadius = "4px";
                notification.style.boxShadow = "0 3px 10px rgba(0,0,0,0.23)";
                notification.style.zIndex = "9999";
                notification.style.fontWeight = "500";
                notification.style.display = "flex";
                notification.style.alignItems = "center";
                
                // アイコンのスタイル
                var iconElement = notification.querySelector(".dashicons");
                iconElement.style.marginRight = "10px";
                iconElement.style.fontSize = "20px";
                
                // 閉じるボタンを追加
                var closeBtn = document.createElement("span");
                closeBtn.innerHTML = "×";
                closeBtn.style.marginLeft = "15px";
                closeBtn.style.cursor = "pointer";
                closeBtn.style.fontSize = "20px";
                closeBtn.style.opacity = "0.7";
                closeBtn.onmouseover = function() { this.style.opacity = "1"; };
                closeBtn.onmouseout = function() { this.style.opacity = "0.7"; };
                closeBtn.onclick = function() {
                    notification.style.opacity = "0";
                    setTimeout(function() {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                };
                notification.appendChild(closeBtn);
                
                // ページに追加
                document.body.appendChild(notification);
                
                // 数秒後に通知を消す
                setTimeout(function() {
                    notification.style.opacity = "1";
                    notification.style.transition = "opacity 0.5s ease-out";
                    
                    setTimeout(function() {
                        notification.style.opacity = "0";
                        setTimeout(function() {
                            if (notification.parentNode) {
                                notification.parentNode.removeChild(notification);
                            }
                        }, 500);
                    }, 5000); // 5秒後にフェードアウト開始
                }, 100);
            })();
        </script>';
    }
}

// インスタンスを初期化
KTP_Settings::get_instance();
