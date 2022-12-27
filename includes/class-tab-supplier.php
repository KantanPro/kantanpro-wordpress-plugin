<?php

class Kantan_Supplier_Class{

    public $name;

    public function __construct() {
        $this->$name;
        // add_action('');
        // add_filter('');
    }
    
    function Supplier_Tab_View( $name ) {

        // 表示する内容
        $content = <<<END
        <h3>ここは [$name] です。</h3>
        協力会社を管理できます。
        END;
        return $content;

    }
}

?>