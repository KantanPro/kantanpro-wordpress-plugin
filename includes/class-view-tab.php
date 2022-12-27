<?php
/*
全体のタブレイアウト
2022-12-27　baterです。
*/

class view_tabs_Class{

    // public $name;
    
    public function __construct() {
      // $list_content;
      // $order_content
      // $client_content;
      // $service_content;
      // $supplier_content;
      // $report_content;
      // $setting_content;
      // $this->$name;
      // add_action('');
      // add_filter('');
    }
    
    //タブの表示
    function TabsView(
      $list_content,
      $order_content,
      $client_content,
      $service_content,
      $supplier_content,
      $report_content,
      $setting_content
    ) {
$view = <<< EOF
<div class="tabs">
<input id="list" type="radio" name="tab_item" checked>
<label class="tab_item" for="list">list</label>
<input id="order" type="radio" name="tab_item">
<label class="tab_item" for="order">order</label>
<input id="client" type="radio" name="tab_item">
<label class="tab_item" for="client">client</label>
<input id="service" type="radio" name="tab_item">
<label class="tab_item" for="service">service</label>
<input id="supplier" type="radio" name="tab_item">
<label class="tab_item" for="supplier">supplier</label>
<input id="report" type="radio" name="tab_item">
<label class="tab_item" for="report">report</label>
<input id="setting" type="radio" name="tab_item">
<label class="tab_item" for="setting">setting</label>

<div class="tab_content" id="list_content">
$list_content
</div>
<div class="tab_content" id="order_content">
$order_content
</div>
<div class="tab_content" id="client_content">
$client_content
</div>
<div class="tab_content" id="service_content">
$service_content
</div>
<div class="tab_content" id="supplier_content">
$supplier_content
</div>
<div class="tab_content" id="report_content">
$report_content
</div>
<div class="tab_content" id="setting_content">
$setting_content
</div>
</div>

EOF;

    
    return $view;
    
    }
    
}

?>