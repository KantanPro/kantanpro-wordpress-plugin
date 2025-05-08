<?php

class Kantan_Setting_Class {

    public $name;

    public function __construct() {
        // プロパティを初期化
        $this->name = ''; 
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
        // 宛名印刷テンプレート
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
        ※ 選択した顧客データに置換されます。<br />
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