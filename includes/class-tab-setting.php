
<?php
// SMTP設定をWordPressのメール送信に反映（エラー非表示）
add_action('phpmailer_init', function($phpmailer) {
    try {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_setting';
        if (!$wpdb->get_var("SHOW TABLES LIKE '$table_name'")) return;
        $smtp_host = $wpdb->get_var("SELECT smtp_host FROM $table_name WHERE id = 1");
        $smtp_port = $wpdb->get_var("SELECT smtp_port FROM $table_name WHERE id = 1");
        $smtp_user = $wpdb->get_var("SELECT smtp_user FROM $table_name WHERE id = 1");
        $smtp_pass = $wpdb->get_var("SELECT smtp_pass FROM $table_name WHERE id = 1");
        $smtp_secure = $wpdb->get_var("SELECT smtp_secure FROM $table_name WHERE id = 1");
        $from_name = $wpdb->get_var("SELECT smtp_from_name FROM $table_name WHERE id = 1");
        $from_email = $wpdb->get_var("SELECT email_address FROM $table_name WHERE id = 1");
        if ($smtp_host && $smtp_port && $smtp_user && $smtp_pass) {
            $phpmailer->isSMTP();
            $phpmailer->Host = $smtp_host;
            $phpmailer->Port = $smtp_port;
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $smtp_user;
            $phpmailer->Password = $smtp_pass;
            if ($smtp_secure) {
                $phpmailer->SMTPSecure = $smtp_secure;
            }
            $phpmailer->CharSet = 'UTF-8';
            // 送信者名・送信元アドレスを明示的にセット
            if ($from_email) {
                $phpmailer->setFrom($from_email, $from_name ?: $from_email, false);
            }
        }
    } catch (Throwable $e) {
        error_log($e->getMessage()); // エラー内容をログに出力
    }
});

class Kntan_Setting_Class {

    public function __construct() {
    }
    
    // DBにカラムがなければ作成（会社情報、メールアドレス、消費税率、自社締め日、インボイス、振込先口座）
    function Create_Table( $tab_name ) {
        global $wpdb;
        $my_table_version = '1.0.1';
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $charset_collate = $wpdb->get_charset_collate();

        // カラム定義（カラム名 => SQL定義）
        $columns_def = [
            'id' => 'id mediumint(9) NOT NULL AUTO_INCREMENT',
            'email_address' => 'email_address varchar(255) DEFAULT "" NOT NULL',
            'tax_rate' => 'tax_rate varchar(255) DEFAULT "" NOT NULL',
            'closing_date' => 'closing_date varchar(255) DEFAULT "" NOT NULL',
            'invoice' => 'invoice varchar(255) DEFAULT "" NOT NULL',
            'bank_account' => 'bank_account varchar(255) DEFAULT "" NOT NULL',
            'my_company_content' => 'my_company_content longtext DEFAULT "" NOT NULL',
            'template_content' => 'template_content longtext DEFAULT "" NOT NULL',
        ];
        $columns_sql = array_values($columns_def);
        $columns_names = array_keys($columns_def);

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (" . implode(", ", $columns_sql) . ", PRIMARY KEY  (id)) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            add_option('ktp_' . $tab_name . '_table_version', $my_table_version);

            // 最初に1行を追加（ID=1がなければ）
            $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE id = 1");
            if (!$exists) {
                $wpdb->insert($table_name,
                    array(
                        'id' => '1',
                        'email_address' => '',
                        'tax_rate' => '',
                        'closing_date' => '',
                        'invoice' => '',
                        'bank_account' => '',
                        'my_company_content' => '',
                        'template_content' => ''
                    )
                );
            }
        } else {
            $existing_columns = $wpdb->get_col("DESCRIBE $table_name", 0);
            // 追加が必要なカラムだけ抽出
            foreach ($columns_def as $col_name => $col_def) {
                if (!in_array($col_name, $existing_columns)) {
                    $wpdb->query("ALTER TABLE $table_name ADD COLUMN $col_def");
                }
            }
            update_option('ktp_' . $tab_name . '_table_version', $my_table_version);

            // 最初に1行を追加（ID=1がなければ）
            $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE id = 1");
            if (!$exists) {
                $wpdb->insert($table_name,
                    array(
                        'id' => '1',
                        'email_address' => '',
                        'tax_rate' => '',
                        'closing_date' => '',
                        'invoice' => '',
                        'bank_account' => '',
                        'my_company_content' => '',
                        'template_content' => ''
                    )
                );
            }
        }
    }

    // Update_Table
    function Update_Table( $tab_name ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $my_table_version = '1.0.2';

        // テーブルバージョンが違う場合はテーブルを更新
        if (get_option('ktp_' . $tab_name . '_table_version') != $my_table_version) {
            $columns_def = [
                'id' => 'id mediumint(9) NOT NULL AUTO_INCREMENT',
                'email_address' => 'email_address varchar(255) DEFAULT "" NOT NULL',
                'tax_rate' => 'tax_rate varchar(255) DEFAULT "" NOT NULL',
                'closing_date' => 'closing_date varchar(255) DEFAULT "" NOT NULL',
                'invoice' => 'invoice varchar(255) DEFAULT "" NOT NULL',
                'bank_account' => 'bank_account varchar(255) DEFAULT "" NOT NULL',
                'my_company_content' => 'my_company_content longtext DEFAULT "" NOT NULL',
                'template_content' => 'template_content longtext DEFAULT "" NOT NULL',
            ];
            $existing_columns = $wpdb->get_col("DESCRIBE $table_name", 0);
            foreach ($columns_def as $col_name => $col_def) {
                if (!in_array($col_name, $existing_columns)) {
                    $wpdb->query("ALTER TABLE $table_name ADD COLUMN $col_def");
                }
            }
            update_option('ktp_' . $tab_name . '_table_version', $my_table_version);
        }

    }
    
    function Setting_Tab_View( $tab_name ) {

        
        // ページをリロード
        $reload = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header("Location: ".$_SERVER['REQUEST_URI']);
        }
        
        // リクエストメソッドがPOSTの場合、フォームの送信を処理
        // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //     // active_tabの値をクッキーに保存する
        //     if (isset($_POST['active_tab'])) {
        //         $active_tab = $_POST['active_tab'];
        //         setcookie('active_tab', $active_tab, time() + (86400 * 30), "/"); // 30日間有効
        //     }
        // }
        
        // クッキーからactive_tabを読み出す
        $active_tab = isset($_COOKIE['active_tab']) ? $_COOKIE['active_tab'] : 'MyCompany';

        // タブのボタン
        $myCompanyClass = $active_tab == 'MyCompany' ? 'active' : '';
        $atenaClass = $active_tab == 'Atena' ? 'active' : '';
        $tab_buttons = <<<BUTTONS
        <div class="controller" data-active-tab="$active_tab">
            <div class="in_tab" data-active-tab="$active_tab">
                <a href="javascript:void(0);" class="tablinks {$myCompanyClass}" onclick="switchTab(event, 'MyCompany');" aria-label="My Company">
                <span class="material-symbols-outlined" title="自社情報">
                domain
                </span>
                </a>
                <a href="javascript:void(0);" class="tablinks {$atenaClass}" onclick="switchTab(event, 'Atena');" aria-label="Atena">
                <span class="material-symbols-outlined" title="印刷テンプレート">
                print_add
                </span>
                </a>
            </div>
        </div>
        BUTTONS;
        // echo $active_tab . '-1';

        //
        // タブ切り替えのスクリプト
        //

        // $active_tabをecho HTMLして引数として使う
        echo <<<HTML
        <input type="hidden" id="active_tab" value="$active_tab">
        HTML;

        $tab_script = <<<SCRIPT
        <script>
        // タブコンテンツとタブリンクを取得
        function getTabContentAndLinks() {
            return {
                tabcontent: document.getElementsByClassName("tabcontent"),
                tablinks: document.getElementsByClassName("tablinks"),
            };
        }
        
        // タブを切り替える関数
        function switchTab(evt, tabName) {
            const { tabcontent, tablinks } = getTabContentAndLinks();
        
            // すべてのタブコンテンツを非表示にする
            for (const tab of tabcontent) {
                tab.style.display = "none";
            }
        
            // すべてのタブリンクの active クラスを削除する
            for (const tablink of tablinks) {
                tablink.classList.remove("active");
            }
        
            // 選択されたタブコンテンツを表示する
            document.getElementById(tabName).style.display = "block";
        
            // 選択されたタブリンクに active クラスを追加する
            if (evt) evt.currentTarget.classList.add("active");
        
            // 選択されたタブの名前を隠しフィールドに設定
            document.getElementById('active_tab').value = tabName;
        }
        
        // アクティブなコンテンツを表示
        window.onload = function() {
            const activeTab = document.getElementById('active_tab').value || 'MyCompany';
            switchTab(null, activeTab);
        
            // 表示されているコンテンツに基づいてタブにアクティブなスタイルを適用
            const myCompanyContent = document.getElementById('MyCompanyContent');
            const atenaContent = document.getElementById('AtenaContent');
        };
        </script>
        SCRIPT;
        
        // ------------------------------------------------
        // 自社情報
        // ------------------------------------------------

        // 自社情報を表示
        $my_company_info = '<div id="MyCompany" class="tabcontent" style="display:none;">';


        // DBから自社情報・メールアドレスを取得

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $my_company_content = $wpdb->get_var( "SELECT my_company_content FROM $table_name" );
        $email_address = $wpdb->get_var( "SELECT email_address FROM $table_name" );
        // SMTP設定取得
        $smtp_host = $wpdb->get_var("SELECT smtp_host FROM $table_name");
        $smtp_port = $wpdb->get_var("SELECT smtp_port FROM $table_name");
        $smtp_user = $wpdb->get_var("SELECT smtp_user FROM $table_name");
        $smtp_pass = $wpdb->get_var("SELECT smtp_pass FROM $table_name");
        $smtp_secure = $wpdb->get_var("SELECT smtp_secure FROM $table_name");
        $smtp_from_name = $wpdb->get_var("SELECT smtp_from_name FROM $table_name");

        // テストメール送信処理
        $test_mail_result = '';
        if (isset($_POST['send_test_mail'])) {
            $to = $email_address;
            $subject = '【KTPWP】SMTPテストメール';
            $body = "このメールはKTPWPプラグインのSMTPテスト送信です。\n\n送信元: $email_address";
            $headers = [];
            if ($smtp_from_name) {
                $headers[] = 'From: ' . $smtp_from_name . ' <' . $email_address . '>';
            } else {
                $headers[] = 'From: ' . $email_address;
            }
            $sent = wp_mail($to, $subject, $body, $headers);
            if ($sent) {
                $test_mail_result = '<div class="updated" style="background:#e6ffed;color:#155724;border:1px solid #c3e6cb;padding:12px 20px;margin:20px 0 8px 0;border-radius:6px;font-size:16px;max-width:480px;">テストメールを送信しました。メールボックスをご確認ください。</div>';
            } else {
                // PHPMailerのエラー情報を取得してログ出力
                global $phpmailer;
                if (isset($phpmailer) && is_object($phpmailer)) {
                    error_log('KTPWP SMTPテストメール送信失敗: ' . $phpmailer->ErrorInfo);
                } else {
                    error_log('KTPWP SMTPテストメール送信失敗: PHPMailerインスタンスが取得できませんでした');
                }
                $test_mail_result = '<div class="error" style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:12px 20px;margin:20px 0 8px 0;border-radius:6px;font-size:16px;max-width:480px;">テストメールの送信に失敗しました。SMTP設定をご確認ください。</div>';
            }
        }

        // 保存処理
        if (
            isset($_POST['my_company_content']) || isset($_POST['email_address']) ||
            isset($_POST['smtp_host']) || isset($_POST['smtp_port']) || isset($_POST['smtp_user']) || isset($_POST['smtp_pass']) || isset($_POST['smtp_secure']) || isset($_POST['smtp_from_name'])
        ) {
            $new_my_company_content = isset($_POST['my_company_content']) ? stripslashes($_POST['my_company_content']) : $my_company_content;
            $new_email_address = isset($_POST['email_address']) ? sanitize_email($_POST['email_address']) : $email_address;
            $new_smtp_host = isset($_POST['smtp_host']) ? sanitize_text_field($_POST['smtp_host']) : $smtp_host;
            $new_smtp_port = isset($_POST['smtp_port']) ? sanitize_text_field($_POST['smtp_port']) : $smtp_port;
            $new_smtp_user = isset($_POST['smtp_user']) ? sanitize_text_field($_POST['smtp_user']) : $smtp_user;
            $new_smtp_pass = isset($_POST['smtp_pass']) ? $_POST['smtp_pass'] : $smtp_pass;
            $new_smtp_secure = isset($_POST['smtp_secure']) ? sanitize_text_field($_POST['smtp_secure']) : $smtp_secure;
            $new_smtp_from_name = isset($_POST['smtp_from_name']) ? sanitize_text_field($_POST['smtp_from_name']) : $smtp_from_name;

            $result = $wpdb->update(
                $table_name,
                array(
                    'my_company_content' => $new_my_company_content,
                    'email_address' => $new_email_address,
                    'smtp_host' => $new_smtp_host,
                    'smtp_port' => $new_smtp_port,
                    'smtp_user' => $new_smtp_user,
                    'smtp_pass' => $new_smtp_pass,
                    'smtp_secure' => $new_smtp_secure,
                    'smtp_from_name' => $new_smtp_from_name
                ),
                array('id' => 1)
            );
            if ($result === false) {
                die('Error: データーベースの更新に失敗しました。');
            }
            header("Location: ". $_SERVER['REQUEST_URI']);
            exit;
        }

        // ビジュアルエディターを表示（自社情報）
        ob_start();
        wp_editor( $my_company_content, 'my_company', array(
            'textarea_name' => 'my_company_content',
            'media_buttons' => true,
            'tinymce' => array(
                'height' => 200, // エディタの高さを400pxに設定
                'toolbar1' => 'formatselect bold italic underline | alignleft aligncenter alignright alignjustify | removeformat',
                'toolbar2' => 'styleselect | forecolor backcolor | table | charmap | pastetext | code',
                'toolbar3' => '',
                'wp_adv' => false,
            ),
            'default_editor' => 'tinymce',
        ) );
        $visual_editor = ob_get_clean();

        // 自社情報
        $my_company_info .= '<div class="header_title">自社情報</div>';
        $my_company_info .= '<div class="atena_contents">';
        $my_company_info .= '<div class="data_list_box">';

        // 入力フォーム
        $my_company_info .= $test_mail_result;
        // プルダウンの選択肢をPHPで生成
        $smtp_secure_options = '';
        $smtp_secure_options .= '<option value=""'  . ($smtp_secure==''  ? ' selected' : '') . '>なし</option>';
        $smtp_secure_options .= '<option value="ssl"' . ($smtp_secure=='ssl' ? ' selected' : '') . '>SSL</option>';
        $smtp_secure_options .= '<option value="tls"' . ($smtp_secure=='tls' ? ' selected' : '') . '>TLS</option>';

        $my_company_info .= '<form method="post" action="">';        $my_company_info .= '<div style="margin-bottom:12px;">';
        $my_company_info .= '<label for="email_address"><b>自社メールアドレス</b>：</label>';
        $my_company_info .= '<input type="email" id="email_address" name="email_address" value="'.esc_attr($email_address).'" style="width:320px;max-width:100%;" required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" placeholder="info@example.com">';
        $my_company_info .= '<div style="font-size:12px;color:#555;margin-top:4px;margin-left:10px;">※ サイトから届くメールが迷惑メールと認識されないよう、サイトのドメインと同じメールアドレスをご入力ください。<br>例：サイトのドメインが「example.com」の場合、「yourname@example.com」</div>';
        $my_company_info .= '</div>';
        $my_company_info .= '<fieldset style="margin-bottom:16px;padding:10px 12px;border:1px solid #ccc;">';
        $my_company_info .= '<legend><b>SMTP設定</b></legend>';
        $my_company_info .= '<div style="margin-bottom:8px;">';
        $my_company_info .= '<label for="smtp_host">SMTPホスト：</label>';
        $my_company_info .= '<input type="text" id="smtp_host" name="smtp_host" value="'.esc_attr($smtp_host).'" style="width:220px;max-width:100%;" placeholder="smtp.example.com">';
        $my_company_info .= '</div>';
        $my_company_info .= '<div style="margin-bottom:8px;">';
        $my_company_info .= '<label for="smtp_port">SMTPポート：</label>';
        $my_company_info .= '<input type="text" id="smtp_port" name="smtp_port" value="'.esc_attr($smtp_port).'" style="width:80px;max-width:100%;" placeholder="587">';
        $my_company_info .= '</div>';
        $my_company_info .= '<div style="margin-bottom:8px;">';
        $my_company_info .= '<label for="smtp_user">SMTPユーザー：</label>';
        $my_company_info .= '<input type="text" id="smtp_user" name="smtp_user" value="'.esc_attr($smtp_user).'" style="width:220px;max-width:100%;" placeholder="user@example.com">';
        $my_company_info .= '</div>';
        $my_company_info .= '<div style="margin-bottom:8px;">';
        $my_company_info .= '<label for="smtp_pass">SMTPパスワード：</label>';
        $my_company_info .= '<input type="password" id="smtp_pass" name="smtp_pass" value="'.esc_attr($smtp_pass).'" style="width:220px;max-width:100%;" autocomplete="off">';
        $my_company_info .= '</div>';
        $my_company_info .= '<div style="margin-bottom:8px;">';
        $my_company_info .= '<label for="smtp_secure">暗号化方式：</label>';
        $my_company_info .= '<select id="smtp_secure" name="smtp_secure">'.$smtp_secure_options.'</select>';
        $my_company_info .= '</div>';
        $my_company_info .= '<div style="margin-bottom:8px;">';
        $my_company_info .= '<label for="smtp_from_name">送信者名：</label>';
        $my_company_info .= '<input type="text" id="smtp_from_name" name="smtp_from_name" value="'.esc_attr($smtp_from_name).'" style="width:220px;max-width:100%;" placeholder="会社名や担当者名">';
        $my_company_info .= '</div>';
        $my_company_info .= '<div style="font-size:12px;color:#888;">※ SMTPを利用しない場合は空欄のままにしてください。</div>';
        $my_company_info .= '</fieldset>';
        $my_company_info .= $visual_editor;
        $my_company_info .= '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />';
        $my_company_info .= '<input type="hidden" id="my_saved_content" name="my_saved_content" value="">';
        $my_company_info .= '<button type="submit" name="save_setting" value="1" style="margin-top: 10px;background:#4caf50;color:#fff;padding:8px 18px;border:none;border-radius:4px;font-size:15px;vertical-align:middle;">';
        $my_company_info .= '<span class="material-symbols-outlined" style="vertical-align:middle;">save_as</span> 保存';
        $my_company_info .= '</button>';
        $my_company_info .= '<button type="submit" name="send_test_mail" value="1" style="margin-left:16px;margin-top:10px;background:#2196f3;color:#fff;padding:8px 18px;border:none;border-radius:4px;font-size:15px;vertical-align:middle;">';
        $my_company_info .= '<span class="material-symbols-outlined" style="vertical-align:middle;">mail</span> テストメール送信';
        $my_company_info .= '</button>';
        $my_company_info .= '</form>';
        $my_company_info .= '<script>function togglePreview() { document.cookie = "active_tab=MyCompany"; }</script>';
        $my_company_info .= '</div>';

        // 自社情報のプレビュー
        $my_company_info .= <<<END
        <div class="data_detail_box">
        <div><b>現在の自社メールアドレス：</b> <span style="color:#0073aa;">{$email_address}</span></div>
        <div style="margin-top:8px;">{$my_company_content}</div>
        </div>
        END;
        $my_company_info .= '</div>';
        $my_company_info .= '</div>';
        
        // ------------------------------------------------
        // 宛名印刷テンプレート
        // ------------------------------------------------

        $atena = '<div id="Atena" class="tabcontent" style="display:none;">';
        
        // DBからテンプレートコンテンツを読み込む
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $template_content = $wpdb->get_var( "SELECT template_content FROM $table_name" );

        // 顧客データ例（本来は顧客テーブル等から取得してください）
        // テーブルが存在しない場合はダミーデータを使う
        $customer_data = [
            'customer' => 'ダミー顧客名',
            'postal_code' => '123-4567',
            'prefecture' => '東京都',
            'city' => '千代田区',
            'address' => '1-2-3',
            'building' => 'サンプルビル',
            'user_name' => '担当 太郎',
        ];
        $customer_table = $wpdb->prefix . 'ktp_customer';
        if ($wpdb->get_var("SHOW TABLES LIKE '$customer_table'") == $customer_table) {
            $customer_data = [
                'customer' => $wpdb->get_var("SELECT customer FROM {$customer_table} WHERE id = 1") ?: 'ダミー顧客名',
                'postal_code' => $wpdb->get_var("SELECT postal_code FROM {$customer_table} WHERE id = 1") ?: '123-4567',
                'prefecture' => $wpdb->get_var("SELECT prefecture FROM {$customer_table} WHERE id = 1") ?: '東京都',
                'city' => $wpdb->get_var("SELECT city FROM {$customer_table} WHERE id = 1") ?: '千代田区',
                'address' => $wpdb->get_var("SELECT address FROM {$customer_table} WHERE id = 1") ?: '1-2-3',
                'building' => $wpdb->get_var("SELECT building FROM {$customer_table} WHERE id = 1") ?: 'サンプルビル',
                'user_name' => $wpdb->get_var("SELECT user_name FROM {$customer_table} WHERE id = 1") ?: '担当 太郎',
            ];
        }

        if ( isset( $_POST['template_content'] ) ) {
            $new_template_content = $_POST['template_content'];

            // エスケープ処理を追加
            $new_template_content = stripslashes($new_template_content);

            // 全角スペースを半角スペースに変換する処理を追加
            // $new_template_content = str_replace("　", " ", $new_template_content);

            // DBへの保存
            $result = $wpdb->update(
                $table_name,
                array('template_content' => $new_template_content),
                array('id' => 1) 
            );

            // データの更新が成功したかどうかを確認
            if ($result === false) {
                die('Error: データーベースの更新に失敗しました。');
            }

            // テンプレートコンテンツを更新を通知
            $template_content = $new_template_content;
            // $atena .= '<script>alert("テンプレートを保存しました！");</script>';
        }

        // --- ここから置換プレビュー処理を修正 ---
        $replace_words = [
            '_%customer%_' => $customer_data['customer'] ?: 'ダミー顧客名',
            '_%postal_code%_' => $customer_data['postal_code'] ?: '123-4567',
            '_%prefecture%_' => $customer_data['prefecture'] ?: '東京都',
            '_%city%_' => $customer_data['city'] ?: '千代田区',
            '_%address%_' => $customer_data['address'] ?: '1-2-3',
            '_%building%_' => $customer_data['building'] ?: 'サンプルビル',
            '_%user_name%_' => $customer_data['user_name'] ?: '担当 太郎',
        ];
        $template_preview = strtr($template_content, $replace_words);
        // --- ここまで ---
        
        // ビジュアルエディターを表示（宛名印刷）
        ob_start();
        wp_editor( $template_content, 'template_content', array(
            'textarea_name' => 'template_content',
            'media_buttons' => true,
            'tinymce' => array(
                'height' => 400, // エディタの高さを400pxに設定
                'toolbar1' => 'formatselect bold italic underline | alignleft aligncenter alignright alignjustify | removeformat',
                'toolbar2' => 'styleselect | forecolor backcolor | table | charmap | pastetext | code',
                'toolbar3' => '',
                'wp_adv' => false,
            ),
            'default_editor' => 'tinymce',
        ) );
        $visual_editor = ob_get_clean();

        // 宛名印刷のテンプレート
        $atena .= '<div class="header_title">宛名印刷テンプレート</div>';
        $atena .= '<div class="atena_contents">';
        $atena .= '<div class="data_list_box">';
        
        $atena .= <<<END
        <form method="post" action="">
        $visual_editor
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
        <input type="hidden" id="my_saved_content" name="my_saved_content" value="">
        <button id="previewButton" onclick="togglePreview()" title="保存する" style="margin-top: 10px;">
        <span class="material-symbols-outlined">
        save_as
        </span>
        </button>
        </form>
        <script>
            function setCookie() {
                document.cookie = "active_tab=Atena";
            }
            document.getElementById("previewButton").addEventListener("click", setCookie);
        </script>
        END;
        $atena .= '</div>';
        
        // 置換ワードの凡例
        $atena .= <<<END
        <div class="data_detail_box">
            <table>
                <tr>
                    <td>_%postal_code%_</td>
                    <td>郵便番号</td>
                </tr>
                <tr>
                    <td>_%prefecture%_</td>
                    <td>都道府県</td>
                </tr>
                <tr>
                    <td>_%city%_</td>
                    <td>市区町村</td>
                </tr>
                <tr>
                    <td>_%address%_</td>
                    <td>番地</td>
                </tr>
                <tr>
                    <td>_%building%_</td>
                    <td>建物</td>
                </tr>
                <tr>
                    <td>_%customer%_</td>
                    <td>会社名｜屋号｜お名前</td>
                </tr>
                <tr>
                    <td>_%user_name%_</td>
                    <td>担当者名</td>
                </tr>
            </table>
        ※ 選択した顧客データに置換されます。<br />
        ※ ショートコードを挿入ボタンは使用できません。
        </div>
        END;

        // プレビュー表示を追加
        $atena .= <<<END
        <div class="data_detail_box" style="margin-top:20px;">
            <div style="font-weight:bold;">プレビュー（ダミー顧客データで置換）</div>
            <div style="border:1px solid #ccc; padding:10px; margin-top:5px;">
                {$template_preview}
            </div>
        </div>
        END;

        $atena .= '</div>';
        $atena .= '</div>';
        
        // コンテンツを返す
        $content = $tab_script . $tab_buttons . $atena . $my_company_info;

        return $content;
    }
}