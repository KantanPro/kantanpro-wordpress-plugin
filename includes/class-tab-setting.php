<?php

class Kntan_Setting_Class {

    // public $name;

    public function __construct() {
        // $this->name = 'setting';
    }
    
    function Setting_Tab_View( $tab_name ) {

        // 表示する内容
        $content = <<<END
        <h3>ここは [$tab_name] です。</h3>
        各種設定ができます。
        END;
        return $content;
    }
}

?>