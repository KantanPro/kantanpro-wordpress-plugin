<?php

class Kntan_Report_Class {

    // public $name;

    public function __construct() {
        // $this->name = 'report';
    }
    
    function Report_Tab_View( $tab_name ) {

        // 表示する内容
        $content = <<<END
        <h3>ここは [$tab_name] です。</h3>
        売上などのレポートを表示できます。
        END;
        return $content;
    }
}

?>