<?php

class Kntan_Order_Class{

    public $name;

    public function __construct($name) {
        $this->name = 'order';
    }
    
    function Order_Tab_View( $name ) {

        // 表示する内容
        $content = <<<END
        <h3>ここは [$name] です。</h3>
        個別の受注書を作成します。
        END;
        return $content;
    }

}

?>