<?php

class Kantan_List_Class{

    // public $name;

    public function __construct() {
        // $this->name = 'list';
    }
    
    function List_Tab_View( $tab_name ) {
        global $wpdb; // $wpdbオブジェクトを使用可能にする
        $table_name = $wpdb->prefix . 'ktp_order'; // 受注書テーブル名

        $content = ''; // 表示するHTMLコンテンツ

        // controllerコンテナを上部に表示
        $content .= '<div class="controller">';
        $content .= '<div class="printer">';
        $content .= '<div class="up-title">仕事リスト：</div>';
        // 印刷ボタン（ダミー）
        $content .= '<button title="印刷する" onclick="alert(\'印刷ダミー\')">';
        $content .= '<span class="material-symbols-outlined" aria-label="印刷">print</span>';
        $content .= '</button>';
        $content .= '</div>'; // .printer 終了
        $content .= '</div>'; // .controller 終了

        // 受注書リスト表示
        // $content .= '<h3>■ 受注書リスト</h3>';

        // 受注書データを取得（例: 最新20件）
        $query = "SELECT * FROM {$table_name} ORDER BY time DESC LIMIT 20";
        $order_list = $wpdb->get_results($query);

        // --- ここからラッパー追加 ---
        $content .= '<div class="work_list_box">';
        if ($order_list) {
            $content .= '<ul>';
            foreach ($order_list as $order) {
                $order_id = esc_html($order->id);
                $customer_name = esc_html($order->customer_name);
                $user_name = esc_html($order->user_name);
                $time = esc_html($order->time);

                // 受注書詳細（伝票処理タブ）へのリンク
                $detail_url = add_query_arg('order_id', $order_id, '?tab_name=order');

                $content .= "<li><a href='{$detail_url}'>ID: {$order_id} - {$customer_name} ({$user_name}) - {$time}</a></li>";
            }
            $content .= '</ul>';
        } else {
            $content .= '<p>受注書データがありません。</p>';
        }
        $content .= '</div>'; // .work_list_box 終了
        // --- ここまでラッパー追加 ---

        return $content;
    }

}