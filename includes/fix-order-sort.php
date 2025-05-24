<?php
/**
 * クエリ修正用のフィルター例
 */
add_filter('ktp_order_history_query', function($query, $client_id, $page_start, $query_limit) {
    global $wpdb;
    $order_table = $wpdb->prefix . 'ktp_order';
    
    // 時間を明示的に日時としてソート
    $query = $wpdb->prepare(
        "SELECT * FROM {$order_table} WHERE client_id = %d ORDER BY STR_TO_DATE(time, '%Y-%m-%d %H:%i:%s') DESC LIMIT %d, %d", 
        $client_id, $page_start, $query_limit
    );
    
    return $query;
}, 10, 3);
