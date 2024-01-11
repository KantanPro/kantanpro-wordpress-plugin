<?php

class Kantan_List_Class{

    // public $name;

    public function __construct() {
        // $this->name = 'list';
    }
    
    function List_Tab_View( $tab_name ) {
        
        // 表示する内容
        $content = <<<END
        <h3>ここは [$tab_name] です。</h3>
        仕事のリストを表示してワークフローを管理できます。
        END;
        return $content;
    }

}

?>