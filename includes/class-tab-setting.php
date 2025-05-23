
<?php

if (!class_exists('Kntan_Setting_Class')) {
class Kntan_Setting_Class {

    public function __construct() {
    }
    
    // DBにカラムがなければ作成（会社情報、メールアドレス、消費税率、自社締め日、インボイス、振込先口座）
    function Create_Table( $tab_name ) {
        global $wpdb;
        $my_table_version = '1.0.1';
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $charset_collate = $wpdb->get_charset_collate();        // カラム定義（カラム名 => SQL定義）
        $columns_def = [
            'id' => 'id mediumint(9) NOT NULL AUTO_INCREMENT',
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
                </a>            </div>
        </div>
        
        <div class="workflow">
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
        $my_company_info = '<div id="MyCompany" class="tabcontent" style="display:none;">';        // DBから自社情報を取得
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $my_company_content = $wpdb->get_var( "SELECT my_company_content FROM $table_name" );
        $test_mail_result = '';
        
        // メール設定情報を取得（KTP_Settingsクラスから）
        $smtp_options = get_option('ktp_smtp_settings', array());
        $email_address = isset($smtp_options['email_address']) ? $smtp_options['email_address'] : '';
        $smtp_host = isset($smtp_options['smtp_host']) ? $smtp_options['smtp_host'] : '';
        $smtp_port = isset($smtp_options['smtp_port']) ? $smtp_options['smtp_port'] : '';
        $smtp_user = isset($smtp_options['smtp_user']) ? $smtp_options['smtp_user'] : '';
        $smtp_pass = isset($smtp_options['smtp_pass']) ? $smtp_options['smtp_pass'] : '';
        $smtp_secure = isset($smtp_options['smtp_secure']) ? $smtp_options['smtp_secure'] : '';
        $smtp_from_name = isset($smtp_options['smtp_from_name']) ? $smtp_options['smtp_from_name'] : '';

        // 保存処理
        if (            isset($_POST['my_company_content'])
        ) {
            $new_my_company_content = isset($_POST['my_company_content']) ? stripslashes($_POST['my_company_content']) : $my_company_content;            $result = $wpdb->update(
                $table_name,
                array(
                    'my_company_content' => $new_my_company_content
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
        $my_company_info .= '<p>メール設定は、WordPressダッシュボードの「KTPWP設定」ページで管理できます。</p>';
        $my_company_info .= '</div>';
        $my_company_info .= $visual_editor;
        $my_company_info .= '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />';        $my_company_info .= '<input type="hidden" id="my_saved_content" name="my_saved_content" value="">';
        $my_company_info .= '<button type="submit" name="save_setting" value="1" style="margin-top: 10px;background:#4caf50;color:#fff;padding:8px 18px;border:none;border-radius:4px;font-size:15px;vertical-align:middle;">';
        $my_company_info .= '<span class="material-symbols-outlined" style="vertical-align:middle;">save_as</span> 保存';
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
        <button type="submit" title="保存する" style="margin-top: 10px;background:#4caf50;color:#fff;padding:8px 18px;border:none;border-radius:4px;font-size:15px;vertical-align:middle;" onclick="document.cookie = 'active_tab=Atena';">
        <span class="material-symbols-outlined" style="vertical-align:middle;">save_as</span> 保存
        </button>
        </form>
        <script>
            function togglePreview() { 
                document.cookie = "active_tab=Atena"; 
            }
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
} // class_exists