<?php

class Kntan_Setting_Class {

    public function __construct() {
    }
    
    // DBにカラムがなければ作成（会社情報、メールアドレス、消費税率、自社締め日、インボイス、振込先口座）
    function Create_Table( $tab_name ) {
        global $wpdb;
        $my_table_version = '1.0.1';
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $charset_collate = $wpdb->get_charset_collate();

        $columns = [
            'id mediumint(9) NOT NULL AUTO_INCREMENT',

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
                    'email_address' => '',
                    'tax_rate' => '',
                    'closing_date' => '',
                    'invoice' => '',
                    'bank_account' => '',
                    'my_company_content' => '',
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

    // Update_Table
    function Update_Table( $tab_name ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $my_table_version = '1.0.2';

        // テーブルバージョンが違う場合はテーブルを更新
        if (get_option('ktp_' . $tab_name . '_table_version') != $my_table_version) {
            $columns = [
                'id mediumint(9) NOT NULL AUTO_INCREMENT',
                'email_address varchar(255) DEFAULT "" NOT NULL',
                'tax_rate varchar(255) DEFAULT "" NOT NULL',
                'closing_date varchar(255) DEFAULT "" NOT NULL',
                'invoice varchar(255) DEFAULT "" NOT NULL',
                'bank_account varchar(255) DEFAULT "" NOT NULL',
                'my_company_content longtext DEFAULT "" NOT NULL',
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

        
        // // ページをリロード
        $reload = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header("Location: ".$_SERVER['REQUEST_URI']);
            $reload++;
        }
        
        // リクエストメソッドがPOSTの場合、フォームの送信を処理
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // ここでフォームのデータを処理する
            // ...

            // active_tabの値をクッキーに保存する
            if (isset($_POST['active_tab'])) {
                $active_tab = $_POST['active_tab'];
                setcookie('active_tab', $active_tab, time() + (86400 * 30), "/"); // 30日間有効
            }
        }
        
        // クッキーからactive_tabを読み出す
        $active_tab = isset($_COOKIE['active_tab']) ? $_COOKIE['active_tab'] : 'MyCompany';

        // タブのボタン
        $myCompanyClass = $active_tab == 'MyCompany' ? 'active' : '';
        $atenaClass = $active_tab == 'Atena' ? 'active' : '';

        $tab_buttons = <<<BUTTONS
        <div class="in_tab" data-active-tab="$active_tab">
            <button class="tablinks {$myCompanyClass}" onclick="switchTab(event, 'MyCompany')" aria-label="My Company">自社情報</button>
            <button class="tablinks {$atenaClass}" onclick="switchTab(event, 'Atena')" aria-label="宛名印刷">宛名印刷</button>
        </div>
        BUTTONS;
        echo $active_tab . '-1';

        // active_tabが設定されていない場合、デフォルトで'MyCompany'をアクティブにする
        // if (empty($active_tab)) {
        //     $active_tab = 'MyCompany';
        // }

        // // 
        // if( !$reload ){
        //     $active_tab = $active_tab;
        // } else {
        //     $active_tab = '';
        // }

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
        
        // タブを切り替える
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
        
        // ページ読み込み時に前回選択されたタブまたはデフォルトのタブを表示
        window.onload = function() {
            const activeTab = document.getElementById('active_tab').value || 'MyCompany';
            switchTab(null, activeTab);
        }
        </script>
        SCRIPT;


        // タブのボタン
        // $tab_buttons = <<<BUTTONS
        // <div class="in_tab" data-active-tab="$active_tab">
        //     <button class="tablinks {$myCompanyClass}" onclick="switchTab(event, 'MyCompany')" aria-label="My Company">自社情報</button>
        //     <button class="tablinks {$atenaClass}" onclick="switchTab(event, 'Atena')" aria-label="宛名印刷">宛名印刷</button>
        // </div>
        // BUTTONS;
        // echo $active_tab . '-2';
                

        // $content .= <<<END
        // <h3>テンプレート設定</h3>
        // END;

        // ------------------------------------------------
        // 自社情報
        // ------------------------------------------------

        // 自社情報を表示
        $my_company_info = '<div id="MyCompany" class="tabcontent" style="display:none;">';

        // DBから自社コンテンツを読み込む
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $my_company_content = $wpdb->get_var( "SELECT my_company_content FROM $table_name" );


        if ( isset( $_POST['my_company_content'] ) ) {
            $new_my_company_content = $_POST['my_company_content'];

            // エスケープ処理を追加
            $new_my_company_content = stripslashes($new_my_company_content);

            // 全角スペースを半角スペースに変換する処理を追加
            // $new_my_company_content = str_replace("　", " ", $new_my_company_content);

            // DBへの保存
            $result = $wpdb->update(
                $table_name,
                array('my_company_content' => $new_my_company_content),
                array('id' => 1) 
            );

            // データの更新が成功したかどうかを確認
            if ($result === false) {
                die('Error: データーベースの更新に失敗しました。');
            }

            // 自社コンテンツを更新を通知
            // $my_company_content = $new_my_company_content;
            // $my_company_info .= '<script>alert("自社情報を保存しました！");</script>';

        }
        // // クッキーからactive_tabを読み出す
        // $active_tab = isset($_COOKIE['active_tab']) ? $_COOKIE['active_tab'] : 'MyCompany';
        // echo $active_tab . '-2';

        // ビジュアルエディターを表示（自社情報）
        ob_start();
        wp_editor( $my_company_content, 'my_company', array(
            'textarea_name' => 'my_company_content',
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

        // 自社情報
        $my_company_info .= '<h4 id="template_title">自社情報</h4>';
        $my_company_info .= '<div class="atena_contents">';
        $my_company_info .= '<div class="data_list_box">';

        // // フォームの出現前にactive_tab＝MyCompanyをクッキーに保存
        // setcookie('active_tab', 'MyCompany');
        // $active_tab = 'MyCompany';
        
        // ビジュアルエディターを表示（自社情報）
        $my_company_info .= <<<END
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
            function togglePreview() {
                // Set the cookie 'active_tab' to 'MyCompany'
                document.cookie = "active_tab=MyCompany";
            }
        </script>
        END;
        $my_company_info .= '</div>';
        
        // 自社情報のプレビュー
        $my_company_info .= <<<END
        <div class="data_detail_box">
        $my_company_content
        </div>
        END;
        $my_company_info .= '</div>';
        $my_company_info .= '</div>';
        
        // ------------------------------------------------
        // 宛名印刷
        // ------------------------------------------------

        $atena = '<div id="Atena" class="tabcontent" style="display:none;">';
        
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
        $atena .= '<h4 id="template_title">宛名印刷</h4>';
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
        $atena .= '</div>';
        
        // コンテンツを返す
        $content = $tab_script . $tab_buttons . $atena . $my_company_info;

        return $content;
    }
}

?>