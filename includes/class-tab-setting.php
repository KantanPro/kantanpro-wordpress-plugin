<?php

class Kntan_Setting_Class {

    public function __construct() {
    }
    
    // DBにカラムがなければ作成（ロゴマーク、会社名、郵便番号、都道府県、市区町村、番地、建物、電話番号、代表者名、メールアドレス、URL、消費税率、自社締め日、インボイス、振込先口座）
    function Create_Table( $tab_name ) {
        global $wpdb;
        $my_table_version = '1.0.1';
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $charset_collate = $wpdb->get_charset_collate();

        $columns = [
            'id mediumint(9) NOT NULL AUTO_INCREMENT',
            'logo varchar(255) DEFAULT "" NOT NULL',
            'company_name varchar(255) DEFAULT "" NOT NULL',
            'postal_code varchar(255) DEFAULT "" NOT NULL',
            'prefecture varchar(255) DEFAULT "" NOT NULL',
            'city varchar(255) DEFAULT "" NOT NULL',
            'address varchar(255) DEFAULT "" NOT NULL',
            'building varchar(255) DEFAULT "" NOT NULL',
            'phone_number varchar(255) DEFAULT "" NOT NULL',
            'representative_name varchar(255) DEFAULT "" NOT NULL',
            'email_address varchar(255) DEFAULT "" NOT NULL',
            'url varchar(255) DEFAULT "" NOT NULL',
            'tax_rate varchar(255) DEFAULT "" NOT NULL',
            'closing_date varchar(255) DEFAULT "" NOT NULL',
            'invoice varchar(255) DEFAULT "" NOT NULL',
            'bank_account varchar(255) DEFAULT "" NOT NULL',
            'template_content longtext DEFAULT "" NOT NULL',
            'PRIMARY KEY  (id)'
        ];

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (" . implode(", ", $columns) . ") $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            add_option('ktp_' . $tab_name . '_table_version', $my_table_version);

            // 最初に1行を追加
            $wpdb->insert($table_name,
                array(
                    'id' => '1', // 1行目のIDは1で固定
                    'logo' => '',
                    'company_name' => '',
                    'postal_code' => '',
                    'prefecture' => '',
                    'city' => '',
                    'address' => '',
                    'building' => '',
                    'phone_number' => '',
                    'representative_name' => '',
                    'email_address' => '',
                    'url' => '',
                    'tax_rate' => '',
                    'closing_date' => '',
                    'invoice' => '',
                    'bank_account' => '',
                    'template_content' => ''
                )
            );
        } else {
            $existing_columns = $wpdb->get_col("DESCRIBE $table_name", 0);
            $missing_columns = array_diff($columns, $existing_columns);
            foreach ($missing_columns as $missing_column) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN $missing_column");
            }
            update_option('ktp_' . $tab_name . '_table_version', $my_table_version);

            // 最初に1行を追加
            $wpdb->insert($table_name,
                array(
                    'id' => '1', // 1行目のIDは1で固定
                    'logo' => '',
                    'company_name' => '',
                    'postal_code' => '',
                    'prefecture' => '',
                    'city' => '',
                    'address' => '',
                    'building' => '',
                    'phone_number' => '',
                    'representative_name' => '',
                    'email_address' => '',
                    'url' => '',
                    'tax_rate' => '',
                    'closing_date' => '',
                    'invoice' => '',
                    'bank_account' => '',
                    'template_content' => ''
                )
            );
        }
    }

    // Update_Table
    function Update_Table( $tab_name ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $my_table_version = '1.0.1';

        if (get_option('ktp_' . $tab_name . '_table_version') != $my_table_version) {
            $columns = [
                'id mediumint(9) NOT NULL AUTO_INCREMENT',
                'logo varchar(255) DEFAULT "" NOT NULL',
                'company_name varchar(255) DEFAULT "" NOT NULL',
                'postal_code varchar(255) DEFAULT "" NOT NULL',
                'prefecture varchar(255) DEFAULT "" NOT NULL',
                'city varchar(255) DEFAULT "" NOT NULL',
                'address varchar(255) DEFAULT "" NOT NULL',
                'building varchar(255) DEFAULT "" NOT NULL',
                'phone_number varchar(255) DEFAULT "" NOT NULL',
                'representative_name varchar(255) DEFAULT "" NOT NULL',
                'email_address varchar(255) DEFAULT "" NOT NULL',
                'url varchar(255) DEFAULT "" NOT NULL',
                'tax_rate varchar(255) DEFAULT "" NOT NULL',
                'closing_date varchar(255) DEFAULT "" NOT NULL',
                'invoice varchar(255) DEFAULT "" NOT NULL',
                'bank_account varchar(255) DEFAULT "" NOT NULL',
                'template_content longtext DEFAULT "" NOT NULL',
                'PRIMARY KEY  (id)'
            ];

            $existing_columns = $wpdb->get_col("DESCRIBE $table_name", 0);
            $missing_columns = array_diff($columns, $existing_columns);
            foreach ($missing_columns as $missing_column) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN $missing_column");
            }
            update_option('ktp_' . $tab_name . '_table_version', $my_table_version);
        }
    }
    


    function Setting_Tab_View( $tab_name ) {
        
        // $content .= <<<END
        // <h3>テンプレート設定</h3>
        // END;

        // ------------------------------------------------
        // 自社情報
        // ------------------------------------------------

        // 宛名印刷と同じように自社情報を表示

        $my_company_info = '　';

        // DBから自社情報を読み込む
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $my_company = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = 1" );

        if ( isset( $_POST['company_name'] ) ) {
            $new_company_name = $_POST['company_name'];
            $new_postal_code = $_POST['postal_code'];
            $new_prefecture = $_POST['prefecture'];
            $new_city = $_POST['city'];
            $new_address = $_POST['address'];
            $new_building = $_POST['building'];
            $new_phone_number = $_POST['phone_number'];
            $new_representative_name = $_POST['representative_name'];
            $new_email_address = $_POST['email_address'];
            $new_url = $_POST['url'];
            $new_tax_rate = $_POST['tax_rate'];
            $new_closing_date = $_POST['closing_date'];
            $new_invoice = $_POST['invoice'];
            $new_bank_account = $_POST['bank_account'];

            // DBへの保存
            $result = $wpdb->update(
                $table_name,
                array(
                    'company_name' => $new_company_name,
                    'postal_code' => $new_postal_code,
                    'prefecture' => $new_prefecture,
                    'city' => $new_city,
                    'address' => $new_address,
                    'building' => $new_building,
                    'phone_number' => $new_phone_number,
                    'representative_name' => $new_representative_name,
                    'email_address' => $new_email_address,
                    'url' => $new_url,
                    'tax_rate' => $new_tax_rate,
                    'closing_date' => $new_closing_date,
                    'invoice' => $new_invoice,
                    'bank_account' => $new_bank_account
                ),
                array('id' => 1) 
            );

            // データの更新が成功したかどうかを確認
            if ($result === false) {
                die('Error: データーベースの更新に失敗しました。');
            }

            // 自社情報を更新を通知
            $my_company = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = 1" );
            $my_company_info .= '<script>alert("自社情報を保存しました！");</script>';
        }

        // ビジュアルエディターを表示
        ob_start();
        wp_editor( $my_company->company_name, 'company_name', array(
            'textarea_name' => 'company_name',
            'textarea_rows' => 1,
            'media_buttons' => false,
            'tinymce' => array(
                'toolbar1' => 'formatselect bold italic underline | alignleft aligncenter alignright alignjustify | removeformat',
                'toolbar2' => 'styleselect | forecolor backcolor | table | charmap | pastetext | code',
                'toolbar3' => '',
                'wp_adv' => false,
            ),
            'default_editor' => 'tinymce',
        ) );
        $company_name = ob_get_clean();

        // 自社情報のテンプレート
        $my_company_info .= '<h4 id="company_title">自社情報</h4>';
        $my_company_info .= '<div class="company_contents">';
        $my_company_info .= <<<END
        <div class="company_form">
        <form method="post" action="">
        <table>
        <tr>
        <td>会社情報</td>
        <td>$company_name</td>
        </tr>
        <tr>
        </table>
        <button type="submit" title="保存する" style="margin-top: 10px;">保存</button>
        </form>
        </div>
        END;
        $my_company_info .= '</div>';




        
        // ------------------------------------------------
        // 宛名印刷
        // ------------------------------------------------

        $atena = '　';
        
        // DBからテンプレートコンテンツを読み込む
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $template_content = $wpdb->get_var( "SELECT template_content FROM $table_name" );

        if ( isset( $_POST['template_content'] ) ) {
            $new_template_content = $_POST['template_content'];

            // エスケープ処理を追加
            $new_template_content = stripslashes($new_template_content);

            // 全角スペースを半角スペースに変換する処理を追加
            // $new_template_content = str_replace("　", " ", $new_template_content);

            // DBへの保存
            $result = $wpdb->update(
                $table_name,
                array('template_content' => $new_template_content), // data
                array('id' => 1) 
            );

            // データの更新が成功したかどうかを確認
            if ($result === false) {
                die('Error: データーベースの更新に失敗しました。');
            }

            // テンプレートコンテンツを更新を通知
            $template_content = $new_template_content;
            $atena .= '<script>alert("テンプレートを保存しました！");</script>';
        }
        
        // ビジュアルエディターを表示
        ob_start();
        wp_editor( $template_content, 'template_content', array(
            'textarea_name' => 'template_content',
            'textarea_rows' => 20,
            'media_buttons' => true,
            'tinymce' => array(
                'toolbar1' => 'formatselect bold italic underline | alignleft aligncenter alignright alignjustify | removeformat',
                'toolbar2' => 'styleselect | forecolor backcolor | table | charmap | pastetext | code',
                'toolbar3' => '',
                'wp_adv' => false,
            ),
            'default_editor' => 'tinymce',
        ) );
        $visual_editor = ob_get_clean();

        // 宛名印刷のテンプレート

        $atena .= '<h4 id="template_title">宛名印刷</h4>';
        $atena .= '<div class="template_contents">';

        // ビジュアルエディターを表示
        $atena .= <<<END
        <div class="template_form" style="text-align: right;">
        <form method="post" action="">
        $visual_editor
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
        <input type="hidden" id="my_saved_content" name="my_saved_content" value="">
        <button id="previewButton" onclick="togglePreview()" title="保存する" style="margin-top: 10px;">
        <span class="material-symbols-outlined">
        save_as
        </span>
        </button>
        </form></div>
        END;
        
        // 置換ワードの凡例
        $atena .= <<<END
        <div class="template_example">
            <table>
                <tr>
                    <td>_%postal_code%_</td>
                    <td>郵便番号</td>
                </tr>
                <tr>
                    <td>_％prefecture％_</td>
                    <td>都道府県</td>
                </tr>
                <tr>
                    <td>_％city％_</td>
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
        ※ 宛名印刷のテンプレートです。<br />
        ※ 設定タブで編集できます。<br />
        ※ 選択した顧客データに置換されます。<br />
        ※ 画像も追加できます。<br />
        ※ ショートコードを挿入ボタンは使用できません。
        </div>
        END;
        $atena .= '</div>';
        
        // コンテンツを返す
        $content = $atena . $my_company_info;

        return $content;
    }
}

?>