<?php

class Kantan_List_Class {

    public $name;

    public function __construct($name = '') {
        $this->name = $name;
        // add_action('');
        // add_filter('');
    }

    // Create_Table メソッドを追加
    public function Create_Table() {
        // 必要に応じてテーブルHTMLを生成
        return '<table><tr><td>サンプルデータ</td></tr></table>';
    }
    
    function List_Tab_View( $name ) {
        
        // 表示する内容
        $content = <<<END
        <h3>ここは [$tab_name] です。</h3>
        仕事のリストを表示してワークフローを管理できます。
        END;
        return $content;
    }

}