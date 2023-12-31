<?php

class Kntan_Service_Class{

    public $name;

    public function __construct() {
        $this->$name;
        // add_action('');
        // add_filter('');
    }
    
    function Service_Tab_View( $name ) {

        // 表示する内容
        $content = <<<END
        <h3>ここは [$name] です。</h3>
        自社の商品・サービスを登録します。
        END;
        return $content;
    }

}

?>