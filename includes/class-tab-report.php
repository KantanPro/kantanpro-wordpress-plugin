<?php

class Kntan_Report_Class {

    public $name;

    public function __construct() {
        $this->$name;
        // add_action('');
        // add_filter('');
    }
    
    function Report_Tab_View( $name ) {

        // 表示する内容
        $content = <<<END
        <h3>ここは [$name] です。</h3>
        売上などのレポートを表示できます。
        END;
        return $content;
    }
}

?>