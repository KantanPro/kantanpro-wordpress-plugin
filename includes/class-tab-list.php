<?php

class Kantan_List_Class{

    public $name;

    public function __construct() {
        $this->$name;
        // add_action('');
        // add_filter('');
    }
    
    function List_Tab_View( $name ) {
        
        // 表示する内容
        $content = <<<END
        <h3>ここは [$name] です。</h3>
        仕事のリストを表示してワークフローを管理できます。
        END;
        return $content;
    }

}

?>