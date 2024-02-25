<?php

class Kntan_Setting_Class {

    public function __construct() {
    }
    
    function Setting_Tab_View( $tab_name ) {

        $content .= <<<END
        <h3>テンプレート設定</h3>
        END;

        // 宛名印刷のテンプレートを読み込んで編集し保存する
        $template_content = file_get_contents( plugin_dir_path( __FILE__ ) . '../template/template.txt' );

        if ( isset( $_POST['template_content'] ) ) {
            $new_template_content = $_POST['template_content'];

            // Remove slashes
            $new_template_content = stripslashes($new_template_content);

            // 全角スペースを半角スペースに変換する処理を追加
            $new_template_content = str_replace("　", " ", $new_template_content);

            $result = file_put_contents( plugin_dir_path( __FILE__ ) . '../template/template.txt', $new_template_content );

            // ファイルの書き込みが成功したかどうかを確認
            if ($result === false) {
                die('Error: Failed to write to file');
            }

            // Update the $template_content variable with the new content
            $template_content = $new_template_content;
            $content .= '<script>alert("保存しました！");</script>';
        }
        
        // ビジュアルエディターを表示
        ob_start();
        wp_editor( $template_content, 'template_content', array(
            'textarea_name' => 'template_content',
            'textarea_rows' => 10,
            'media_buttons' => true, // Enable media buttons
            'tinymce' => array(
                'toolbar1' => 'bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | link unlink', // ショートコードボタン（insert_shortcode）を除外
                'setup' => 'function(editor) {
                    editor.on("change", function() {
                        // Save the content to a hidden input
                        editor.save(); // 一回のみ保存
                        document.getElementById(\'my_saved_content\').value = editor.getContent();
                    });
                }',
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
        _%customer%_　会社名｜屋号｜お名前<br />
        _%user_name%_ 　担当者名<br />
        ※ ショートコードを挿入ボタンは使用できません。
        </div>
        END;
        $content .= '</div>';

        return $content;
    }
}

?>