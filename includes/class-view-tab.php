<?php

class view_tabs_Class{
    
    public function __construct() {

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
        
        // タブの位置を取得
        $position = $_GET['tab_name'] ?? 'list';

        // タブの内容を配列で定義
        $tabs = [
          'list' => '仕事リスト',
          'order' => '伝票処理',
          'client' => '得意先',
          'service' => '商品・サービス',
          'supplier' => '協力会社',
          'report' => 'レポート',
          'setting' => '設定'
        ];

        // タブの内容を作成
        $view = "<div class=\"tabs\">";
        foreach ($tabs as $key => $value) {
          $checked = $position === $key ? ' checked' : '';
          $view .= "<input id=\"$key\" type=\"radio\" name=\"tab_item\"$checked>";
          $view .= "<label class=\"tab_item\"><a href=\"?tab_name=$key\">$value</a></label>";
        }

        $view .= <<<EOF
              <div class="tab_content" id="list_content">
              <br />
              </div>
EOF;
        // タブ外に各タブ本体を出す
        $view .= $list_content;
        $view .= $order_content;
        $view .= $client_content;
        $view .= $service_content;
        $view .= $supplier_content;
        $view .= $report_content;
        $view .= $setting_content;
        $view .= <<<EOF
              <div class="tab_content" id="order_content">
              <br />
              </div>
              <div class="tab_content" id="client_content">
              <br />
              </div>
              <div class="tab_content" id="service_content">
              <br />
              </div>
              <div class="tab_content" id="supplier_content">
              <br />
              </div>
              <div class="tab_content" id="report_content">
              <br />
              </div>
              <div class="tab_content" id="setting_content">
              <br />
              </div>
            </div>
            EOF;

    return $view;
    }
    
}