<?php

class Kantan_Order_Class{

    // public $name;

    public function __construct() {
        // $this->name = 'order';
    }
    
    public function Order_Tab_View($tab_name) {
        return "<div>受注書タブ: {$tab_name}</div>";
    }

}

?>