<?php

class Kntan_Service_Class{

    // public $name;

    public function __construct() {
        // $this->name = 'service';
    }
    
    function Service_Tab_View( $tab_name ) {

        // 表示する内容
        $content = <<<END
        <h3>ここは [$tab_name] です。</h3>
        自社の商品・サービスを登録します。
        END;
        return $content;
    }

}

?>