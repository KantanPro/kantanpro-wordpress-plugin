<?php

class Kntan_Setting_Class {

    // public $name;

    public function __construct() {
        // $this->name = 'setting';
    }
    
    function Setting_Tab_View( $tab_name ) {

        // // 表示する内容
        // $content = <<<END
        // <h3>ここは [$tab_name] です。</h3>
        // 各種設定ができます。
        // END;

        // 宛名印刷のテンプレートを読み込んで編集し保存する
        $template_content = file_get_contents( plugin_dir_path( __FILE__ ) . '../template/template.txt' );

        if ( isset( $_POST['template_content'] ) ) {
            $new_template_content = $_POST['template_content'];

            // 全角スペースを半角スペースに変換する処理を追加
            $new_template_content = str_replace("　", " ", $new_template_content);

            $result = file_put_contents( plugin_dir_path( __FILE__ ) . '../template/template.txt', $new_template_content );

            // ファイルの書き込みが成功したかどうかを確認
            if ($result === false) {
                die('Error: Failed to write to file');
            }

            // Update the $template_content variable with the new content
            $template_content = $new_template_content;
            $content .= '<script>alert("更新しました！");</script>';
        }
        $content .= <<<END
        <h3>宛名印刷のテンプレート</h3>
        <form method="post" action="">
        <textarea name="template_content" id="template_content" style="height: 300px;" wrap="off">$template_content</textarea>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
        <input type="submit" value="保存" style="font-family: Roboto, sans-serif;" />
        </form>

        <script>
        // Update the textarea content immediately after submission
        document.querySelector('form').addEventListener('submit', function(event) {
            event.preventDefault();
            var textarea = document.getElementById('template_content');
            textarea.value = textarea.value.trim();
        });
        </script>
        END;

        return $content;
    }
}

?>