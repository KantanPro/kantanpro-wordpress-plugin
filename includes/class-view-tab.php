<?php

class view_tabs_Class{

    // public $name;
    
    public function __construct() {
        // $this->name = 'tabs';
    }
    
    // 指定された内容でタブを表示するメソッド
    function TabsView(
      $list_content,
      $order_content,
      $client_content,
      $service_content,
      $supplier_content,
      $report_content,
      $setting_content
      ) {
        
        $position = $_GET['tab_name'] ?? 'list';

        $tabs = [
          'list' => 'list',
          'order' => 'order',
          'client' => 'client',
          'service' => 'service',
          'supplier' => 'supplier',
          'report' => 'report',
          'setting' => 'setting'
        ];

        $view = "<div class=\"tabs\">";
        foreach ($tabs as $key => $value) {
          $checked = $position === $key ? ' checked' : '';
          $view .= "<input id=\"$key\" type=\"radio\" name=\"tab_item\"$checked>";
          $view .= "<label class=\"tab_item\"><a href=\"?tab_name=$key\">$value</a></label>";
          // $view .= "<label class=\"tab_item\" for=\"$key\"><a href=\"?tab_name=$key\">$value</a></label>";
        }
        $view .= <<<EOF
              <div class="tab_content" id="list_content">
              $list_content
              <br />
              </div>
              <div class="tab_content" id="order_content">
              $order_content
              <br />
              </div>
              <div class="tab_content" id="client_content">
              $client_content
              <br />
              </div>
              <div class="tab_content" id="service_content">
              $service_content
              <br />
              </div>
              <div class="tab_content" id="supplier_content">
              $supplier_content
              <br />
              </div>
              <div class="tab_content" id="report_content">
              $report_content
              <br />
              </div>
              <div class="tab_content" id="setting_content">
              $setting_content
              <br />
              </div>
            </div>
            EOF;

    return $view;
    }
    
}

?>
