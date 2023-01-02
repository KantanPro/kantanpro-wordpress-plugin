<?php
/*
全体のタブレイアウト
2022-12-27　baterです。
*/

class view_tabs_Class{

    public $name;
    
    public function __construct() {
      $this->$name;
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
        
        // // クッキーでタブポジション取得
        // $position = $_COOKIE['ktp_tab_position'];

        // GETがあればタブポジション変更
        if( isset( $_GET['tab_name'] ) ){
          $position = $_GET['tab_name'];
        }
        else {
          $position = 'list';
        }

        switch ( $position ) {
          case 'list':
            $view = <<< EOF
            <div class="tabs">
            <input id="list" type="radio" name="tab_item" checked><label class="tab_item" for="list">list</label>
            <input id="order" type="radio" name="tab_item">
            <label class="tab_item" for="order"><a href="?tab_name=order">order</a></label>
            <input id="client" type="radio" name="tab_item">
            <label class="tab_item" for="client"><a href="?tab_name=client">client</a></label>
            <input id="service" type="radio" name="tab_item">
            <label class="tab_item" for="service"><a href="?tab_name=service">service</a></label>
            <input id="supplier" type="radio" name="tab_item">
            <label class="tab_item" for="supplier"><a href="?tab_name=supplier">supplier</a></label>
            <input id="report" type="radio" name="tab_item">
            <label class="tab_item" for="report"><a href="?tab_name=report">report</a></label>
            <input id="setting" type="radio" name="tab_item">
            <label class="tab_item" for="setting"><a href="?tab_name=setting">setting</a></label>
            EOF;
            break;
          case 'order':
            $view = <<< EOF
            <div class="tabs">
            <input id="list" type="radio" name="tab_item">
            <label class="tab_item" for="list"><a href="?tab_name=list">list</a></label>
            <input id="order" type="radio" name="tab_item" checked><label class="tab_item" for="order">order</label>
            <input id="client" type="radio" name="tab_item">
            <label class="tab_item" for="client"><a href="?tab_name=client">client</a></label>
            <input id="service" type="radio" name="tab_item">
            <label class="tab_item" for="service"><a href="?tab_name=service">service</a></label>
            <input id="supplier" type="radio" name="tab_item">
            <label class="tab_item" for="supplier"><a href="?tab_name=supplier">supplier</a></label>
            <input id="report" type="radio" name="tab_item">
            <label class="tab_item" for="report"><a href="?tab_name=report">report</a></label>
            <input id="setting" type="radio" name="tab_item">
            <label class="tab_item" for="setting"><a href="?tab_name=setting">setting</a></label>
            EOF;
            break;
          case 'client':
            $view = <<< EOF
            <div class="tabs">
            <input id="list" type="radio" name="tab_item">
            <label class="tab_item" for="list"><a href="?tab_name=list">list</a></label>
            <input id="order" type="radio" name="tab_item">
            <label class="tab_item" for="order"><a href="?tab_name=order">order</a></label>
            <input id="client" type="radio" name="tab_item" checked><label class="tab_item" for="client">client</label>
            <input id="service" type="radio" name="tab_item">
            <label class="tab_item" for="service"><a href="?tab_name=service">service</a></label>
            <input id="supplier" type="radio" name="tab_item">
            <label class="tab_item" for="supplier"><a href="?tab_name=supplier">supplier</a></label>
            <input id="report" type="radio" name="tab_item">
            <label class="tab_item" for="report"><a href="?tab_name=report">report</a></label>
            <input id="setting" type="radio" name="tab_item">
            <label class="tab_item" for="setting"><a href="?tab_name=setting">setting</a></label>
            EOF;
            break;
          case 'service':
            $view = <<< EOF
            <div class="tabs">
            <input id="list" type="radio" name="tab_item">
            <label class="tab_item" for="list"><a href="?tab_name=list">list</a></label>
            <input id="order" type="radio" name="tab_item">
            <label class="tab_item" for="order"><a href="?tab_name=order">order</a></label>
            <input id="client" type="radio" name="tab_item">
            <label class="tab_item" for="client"><a href="?tab_name=client">client</a></label>
            <input id="service" type="radio" name="tab_item" checked><label class="tab_item" for="service">service</label>
            <input id="supplier" type="radio" name="tab_item">
            <label class="tab_item" for="supplier"><a href="?tab_name=supplier">supplier</a></label>
            <input id="report" type="radio" name="tab_item">
            <label class="tab_item" for="report"><a href="?tab_name=report">report</a></label>
            <input id="setting" type="radio" name="tab_item">
            <label class="tab_item" for="setting"><a href="?tab_name=setting">setting</a></label>
            EOF;
            break;
          case 'supplier':
            $view = <<< EOF
            <div class="tabs">
            <input id="list" type="radio" name="tab_item">
            <label class="tab_item" for="list"><a href="?tab_name=list">list</a></label>
            <input id="order" type="radio" name="tab_item">
            <label class="tab_item" for="order"><a href="?tab_name=order">order</a></label>
            <input id="client" type="radio" name="tab_item">
            <label class="tab_item" for="client"><a href="?tab_name=client">client</a></label>
            <input id="service" type="radio" name="tab_item">
            <label class="tab_item" for="service"><a href="?tab_name=service">service</a></label>
            <input id="supplier" type="radio" name="tab_item" checked><label class="tab_item" for="supplier">supplier</label>
            <input id="report" type="radio" name="tab_item">
            <label class="tab_item" for="report"><a href="?tab_name=report">report</a></label>
            <input id="setting" type="radio" name="tab_item">
            <label class="tab_item" for="setting"><a href="?tab_name=setting">setting</a></label>
            EOF;
            break;
          case 'report':
            $view = <<< EOF
            <div class="tabs">
            <input id="list" type="radio" name="tab_item">
            <label class="tab_item" for="list"><a href="?tab_name=list">list</a></label>
            <input id="order" type="radio" name="tab_item">
            <label class="tab_item" for="order"><a href="?tab_name=order">order</a></label>
            <input id="client" type="radio" name="tab_item">
            <label class="tab_item" for="client"><a href="?tab_name=client">client</a></label>
            <input id="service" type="radio" name="tab_item">
            <label class="tab_item" for="service"><a href="?tab_name=service">service</a></label>
            <input id="supplier" type="radio" name="tab_item">
            <label class="tab_item" for="supplier"><a href="?tab_name=supplier">supplier</a></label>
            <input id="report" type="radio" name="tab_item" checked><label class="tab_item" for="report">report</label>
            <input id="setting" type="radio" name="tab_item">
            <label class="tab_item" for="setting"><a href="?tab_name=setting">setting</a></label>
            EOF;
            break;
          case 'setting':
            $view = <<< EOF
            <div class="tabs">
            <input id="list" type="radio" name="tab_item">
            <label class="tab_item" for="list"><a href="?tab_name=list">list</a></label>
            <input id="order" type="radio" name="tab_item">
            <label class="tab_item" for="order"><a href="?tab_name=order">order</a></label>
            <input id="client" type="radio" name="tab_item">
            <label class="tab_item" for="client"><a href="?tab_name=client">client</a></label>
            <input id="service" type="radio" name="tab_item">
            <label class="tab_item" for="service"><a href="?tab_name=service">service</a></label>
            <input id="supplier" type="radio" name="tab_item">
            <label class="tab_item" for="supplier"><a href="?tab_name=supplier">supplier</a></label>
            <input id="report" type="radio" name="tab_item">
            <label class="tab_item" for="report"><a href="?tab_name=report">report</a></label>
            <input id="setting" type="radio" name="tab_item" checked><label class="tab_item" for="setting">setting</label>
            EOF;
            break;
          default:
            $view = <<< EOF
            <div class="tabs">
            <input id="list" type="radio" name="tab_item" checked><label class="tab_item" for="list">list</label>
            <input id="order" type="radio" name="tab_item">
            <label class="tab_item" for="order"><a href="?tab_name=order">order</a></label>
            <input id="client" type="radio" name="tab_item">
            <label class="tab_item" for="client"><a href="?tab_name=client">client</a></label>
            <input id="service" type="radio" name="tab_item">
            <label class="tab_item" for="service"><a href="?tab_name=service">service</a></label>
            <input id="supplier" type="radio" name="tab_item">
            <label class="tab_item" for="supplier"><a href="?tab_name=supplier">supplier</a></label>
            <input id="report" type="radio" name="tab_item">
            <label class="tab_item" for="report"><a href="?tab_name=report">report</a></label>
            <input id="setting" type="radio" name="tab_item">
            <label class="tab_item" for="setting"><a href="?tab_name=setting">setting</a></label>
            EOF;
        }
            $view .= <<< EOF
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