<?php
/**
 * このファイルは、注文履歴のソート問題を修正するためのパッチです。
 * もしテスト後に修正が必要と判断された場合、このファイルの内容を検討してください。
 */

// 以下の対処を行う前に、まずはクエリに問題がないか確認し、問題が確認されたらコメントアウトを外してください。

/*
// クラスTab_Clientに直接パッチを当てる
add_action('init', function() {
    class Tab_Client_Order_Sort_Fix extends Tab_Client {
        public function get_client_orders_query($client_id, $page_start, $query_limit) {
            global $wpdb;
            $order_table = $wpdb->prefix . 'ktp_order';
            
            // time列を明示的に日時型として扱う
            return $wpdb->prepare(
                "SELECT * FROM {$order_table} WHERE client_id = %d ORDER BY STR_TO_DATE(time, '%Y-%m-%d %H:%i:%s') DESC LIMIT %d, %d", 
                $client_id, $page_start, $query_limit
            );
        }
    }
    
    // オリジナルのクラスメソッドをオーバーライド
    add_filter('ktp_client_tab_instance', function($instance) {
        if ($instance instanceof Tab_Client) {
            return new Tab_Client_Order_Sort_Fix();
        }
        return $instance;
    });
});
*/
