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
        
        // ------------------------------------------------
        // 宛名印刷
        // ------------------------------------------------
        
        $content .= <<<END
        <h3>テンプレート設定</h3>
        END;
        
        // DBからテンプレートコンテンツを読み込む
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $template_content = $wpdb->get_var( "SELECT template_content FROM $table_name" );

        if ( isset( $_POST['template_content'] ) ) {
            $new_template_content = $_POST['template_content'];

            // Remove slashes
            $new_template_content = stripslashes($new_template_content);

            // 全角スペースを半角スペースに変換する処理を追加
            $new_template_content = str_replace("　", " ", $new_template_content);

            // ファイルへの書き込みをDBへの保存に変更
            $result = $wpdb->update(
                $table_name,
                array('template_content' => $new_template_content), // data
                array('id' => 1) // where
            );

            // データの更新が成功したかどうかを確認
            if ($result === false) {
                die('Error: Failed to update the database');
            }

            // Update the $template_content variable with the new content
            $template_content = $new_template_content;
            $content .= '<script>alert("保存しました！");</script>';
        }
        
        // ビジュアルエディターを表示
        ob_start();
        wp_editor( $template_content, 'template_content', array(
            'textarea_name' => 'template_content',
            'textarea_rows' => 15,
            'media_buttons' => true, // Enable media buttons
            'tinymce' => array(
                'toolbar1' => 'formatselect bold italic underline | alignleft aligncenter alignright alignjustify | removeformat',
                'toolbar2' => 'styleselect | forecolor backcolor | table | charmap | pastetext | code',
                'toolbar3' => '',
                'wp_adv' => false, // Disable "Add shortcode" button
            ),
            'default_editor' => 'tinymce', // Display visual editor by default
        ) );
        $visual_editor = ob_get_clean();



        // ------------------------------------------------
        // 宛名印刷のテンプレート
        // ------------------------------------------------

        $content .= '<h4 id="template_title">宛名印刷</h4>';
        $content .= '<div class="template_contents">';
        // ビジュアルエディターを表示
        $content .= <<<END
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

        $content .= <<<END
        <div class="template_example">
        <h5>テンプレートの置換例</h5>
        _%postal_code%_　郵便番号<br />
        _％prefecture％_　都道府県<br />
        _％city％_　市区町村<br />
        _%address%_　番地<br />
        _%building%_　建物<br />
        _%customer%_　会社名｜屋号｜お名前<br />
        _%user_name%_ 　担当者名<br /><br />
        ※ 宛名印刷のテンプレートです。<br />
        ※ 設定タブで編集できます。<br />
        ※ 選択した顧客データに置換されます。<br />
        ※ 画像も追加できます。<br />
        ※ ショートコードを挿入ボタンは使用できません。
        </div>
        END;
        $content .= '</div>';

        return $content;
    }
}

?>