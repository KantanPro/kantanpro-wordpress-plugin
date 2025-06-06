<?php
/**
 * Staff chat management class for KTPWP plugin
 *
 * Handles staff chat functionality for orders.
 *
 * @package KTPWP
 * @subpackage Includes
 * @since 1.0.0
 * @author Kantan Pro
 * @copyright 2024 Kantan Pro
 * @license GPL-2.0+
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'KTPWP_Staff_Chat' ) ) {

/**
 * Staff chat management class
 *
 * @since 1.0.0
 */
class KTPWP_Staff_Chat {

    /**
     * Singleton instance
     *
     * @since 1.0.0
     * @var KTPWP_Staff_Chat
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return KTPWP_Staff_Chat
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Private constructor for singleton
    }

    /**
     * Create staff chat table
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function create_table() {
        global $wpdb;
        $my_table_version = '1.0';
        $table_name = $wpdb->prefix . 'ktp_order_staff_chat';
        $charset_collate = $wpdb->get_charset_collate();

        $current_version = get_option( 'ktp_staff_chat_table_version', '0' );

        $columns_def = array(
            'id MEDIUMINT(9) NOT NULL AUTO_INCREMENT',
            'order_id MEDIUMINT(9) NOT NULL',
            'user_id BIGINT(20) UNSIGNED NOT NULL',
            'user_display_name VARCHAR(255) NOT NULL DEFAULT ""',
            'message TEXT NOT NULL',
            'is_initial TINYINT(1) NOT NULL DEFAULT 0',
            'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'PRIMARY KEY (id)',
            'KEY order_id (order_id)',
            'KEY user_id (user_id)',
            'KEY created_at (created_at)'
        );

        if ( version_compare( $current_version, $my_table_version, '<' ) ) {
            // Table needs to be created or updated
            $sql = "CREATE TABLE `{$table_name}` (" . implode( ', ', $columns_def ) . ") {$charset_collate};";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            
            if ( function_exists( 'dbDelta' ) ) {
                $result = dbDelta( $sql );
                
                if ( ! empty( $result ) ) {
                    add_option( 'ktp_staff_chat_table_version', $my_table_version );
                    return true;
                }
                
                error_log( 'KTPWP: Failed to create staff chat table' );
                return false;
            }
            
            error_log( 'KTPWP: dbDelta function not available' );
            return false;
        } else {
            // Table exists, check for missing columns
            $existing_columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$table_name}`", 0 );
            $def_column_names = array();
            
            foreach ( $columns_def as $def ) {
                if ( preg_match( '/^([a-zA-Z0-9_]+)/', $def, $m ) ) {
                    $def_column_names[] = $m[1];
                }
            }
            
            $missing_columns = array_diff( $def_column_names, $existing_columns );
            
            foreach ( $missing_columns as $column ) {
                foreach ( $columns_def as $def ) {
                    if ( strpos( $def, $column . ' ' ) === 0 || strpos( $def, $column . '(' ) === 0 ) {
                        // Skip adding PRIMARY KEY and INDEX through ALTER TABLE to avoid syntax errors
                        if ( strpos( $def, 'PRIMARY KEY' ) !== false || strpos( $def, 'KEY ' ) !== false ) {
                            continue;
                        }
                        
                        $result = $wpdb->query( "ALTER TABLE `{$table_name}` ADD COLUMN {$def}" );
                        if ( $result === false ) {
                            error_log( 'KTPWP: Failed to add column: ' . $def . ' - ' . $wpdb->last_error );
                        }
                        break;
                    }
                }
            }
            
            update_option( 'ktp_staff_chat_table_version', $my_table_version );
        }
        
        return true;
    }

    /**
     * Create initial staff chat entry when order is created
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @param int $user_id User ID (optional, uses current user if not provided)
     * @return bool True on success, false on failure
     */
    public function create_initial_chat( $order_id, $user_id = null ) {
        if ( ! $order_id || $order_id <= 0 ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_staff_chat';
        
        // Use current user if user_id not provided
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        if ( ! $user_id ) {
            return false;
        }
        
        // Check if initial chat already exists
        $existing_chat = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$table_name}` WHERE order_id = %d AND is_initial = 1",
            $order_id
        ) );
        
        if ( $existing_chat > 0 ) {
            return true; // Already exists
        }
        
        // Get user display name
        $user_info = get_userdata( $user_id );
        if ( ! $user_info ) {
            return false;
        }
        
        $display_name = $user_info->display_name ? $user_info->display_name : $user_info->user_login;
        
        // Insert initial chat entry
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'user_id' => $user_id,
                'user_display_name' => $display_name,
                'message' => '受注書を作成しました。',
                'is_initial' => 1,
                'created_at' => current_time( 'mysql' )
            ),
            array(
                '%d', // order_id
                '%d', // user_id
                '%s', // user_display_name
                '%s', // message
                '%d', // is_initial
                '%s'  // created_at
            )
        );
        
        if ( $inserted ) {
            return true;
        } else {
            error_log( 'KTPWP: Failed to create initial staff chat: ' . $wpdb->last_error );
            return false;
        }
    }

    /**
     * Get staff chat messages for a specific order
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return array|false Array of chat messages or false on failure
     */
    public function get_messages( $order_id ) {
        if ( ! $order_id || $order_id <= 0 ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_staff_chat';
        
        $messages = $wpdb->get_results( 
            $wpdb->prepare( 
                "SELECT * FROM {$table_name} WHERE order_id = %d ORDER BY created_at ASC",
                $order_id 
            ),
            ARRAY_A
        );
        
        if ( $messages === false ) {
            error_log( 'KTPWP: Error getting staff chat messages: ' . $wpdb->last_error );
            return false;
        }
        
        return $messages ? $messages : array();
    }

    /**
     * Add staff chat message
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @param string $message Message content
     * @return bool True on success, false on failure
     */
    public function add_message( $order_id, $message ) {
        if ( ! $order_id || $order_id <= 0 || empty( trim( $message ) ) ) {
            return false;
        }
        
        // Check user permissions
        $current_user_id = get_current_user_id();
        if ( ! $current_user_id || ! current_user_can( 'edit_posts' ) ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_staff_chat';
        
        // Get user info
        $user_info = get_userdata( $current_user_id );
        if ( ! $user_info ) {
            return false;
        }
        
        $display_name = $user_info->display_name ? $user_info->display_name : $user_info->user_login;
        
        // デバッグログを追加
        error_log('KTPWP: add_message - order_id: ' . $order_id . ', message: ' . $message);
        error_log('KTPWP: add_message - table_name: ' . $table_name);
        error_log('KTPWP: add_message - user_id: ' . $current_user_id . ', display_name: ' . $display_name);

        // Start transaction for concurrent access
        $wpdb->query( 'START TRANSACTION' );
        
        try {
            // Verify order exists
            $order_table = $wpdb->prefix . 'ktp_order';
            $order_exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$order_table}` WHERE id = %d",
                $order_id
            ) );
            
            if ( ! $order_exists ) {
                throw new Exception( 'Order does not exist' );
            }
            
            // Insert message
            $inserted = $wpdb->insert(
                $table_name,
                array(
                    'order_id' => $order_id,
                    'user_id' => $current_user_id,
                    'user_display_name' => sanitize_text_field( $display_name ),
                    'message' => sanitize_textarea_field( $message ),
                    'is_initial' => 0,
                    'created_at' => current_time( 'mysql' )
                ),
                array(
                    '%d', // order_id
                    '%d', // user_id
                    '%s', // user_display_name
                    '%s', // message
                    '%d', // is_initial
                    '%s'  // created_at
                )
            );
            
            if ( $inserted ) {
                $wpdb->query( 'COMMIT' );
                return true;
            } else {
                throw new Exception( 'Failed to insert message: ' . $wpdb->last_error );
            }
            
        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            error_log( 'KTPWP: Exception in add_message: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Generate staff chat HTML
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return string HTML content for staff chat
     */
    public function generate_html( $order_id ) {
        global $wpdb;
        
        // Initialize variables
        $header_html = '';
        $scrollable_messages = array();
        
        // Check if order_id is valid
        if ( ! $order_id || $order_id <= 0 ) {
            return '<div class="order_memo_box box"><h4>■ スタッフチャット</h4><p>注文IDが無効です。</p></div>';
        }
        
        // Ensure table exists
        if ( ! $this->create_table() ) {
            return '<div class="order_memo_box box"><h4>■ スタッフチャット</h4><p>データベーステーブルの作成に失敗しました。</p></div>';
        }
        
        // Get order creation time
        $order_table = $wpdb->prefix . 'ktp_order';
        $order = $wpdb->get_row( $wpdb->prepare( 
            "SELECT time FROM `{$order_table}` WHERE id = %d", 
            $order_id 
        ) );
        
        // Get chat messages
        $messages = $this->get_messages( $order_id );
        
        // Create initial chat message if none exist
        if ( empty( $messages ) ) {
            // For orders without existing chat messages, create initial message with current user
            $current_user_id = get_current_user_id();
            
            if ( $current_user_id && current_user_can( 'edit_posts' ) ) {
                $this->create_initial_chat( $order_id, $current_user_id );
                $messages = $this->get_messages( $order_id );
            }
        }
        
        // Build HTML structure
        $html = '<div class="order_memo_box box">';
        $html .= '<h4>■ スタッフチャット';
        
        // Check URL parameter for chat open state
        // デフォルトでは表示状態にする（chat_open=0が明示的に指定された場合のみ非表示）
        $chat_should_be_open = !isset( $_GET['chat_open'] ) || $_GET['chat_open'] !== '0';
        $aria_expanded = $chat_should_be_open ? 'true' : 'false';
        $button_text = $chat_should_be_open ? esc_html__( '非表示', 'ktpwp' ) : esc_html__( '表示', 'ktpwp' );
        
        // Add toggle button
        $html .= '<button type="button" class="toggle-staff-chat" aria-expanded="' . $aria_expanded . '" ';
        $html .= 'title="' . esc_attr__( 'スタッフチャットの表示/非表示を切り替え', 'ktpwp' ) . '">';
        $html .= $button_text;
        $html .= '</button>';
        $html .= '</h4>';
        
        // Chat content div
        $display_style = $chat_should_be_open ? 'block' : 'none';
        $html .= '<div id="staff-chat-content" class="staff-chat-content" style="display: ' . $display_style . ';">';
        
        if ( empty( $messages ) ) {
            $html .= '<div class="staff-chat-empty">' . esc_html__( 'メッセージはありません。', 'ktpwp' ) . '</div>';
        } else {
            // Separate fixed header from scrollable messages
            
            foreach ( $messages as $index => $message ) {
                if ( $index === 0 && intval( $message['is_initial'] ) === 1 ) {
                    // First message: fixed header display
                    $user_display_name = esc_html( $message['user_display_name'] );
                    $order_created_time = '';
                    if ( $order && ! empty( $order->time ) ) {
                        $order_created_time = date( 'Y/n/j H:i', $order->time );
                    }
                    
                    // Get WordPress avatar
                    $user_id = intval( $message['user_id'] );
                    $avatar = get_avatar( $user_id, 32, '', $user_display_name, array( 'class' => 'staff-chat-wp-avatar' ) );
                    
                    $header_html .= '<div class="staff-chat-header-fixed">';
                    $header_html .= '<div class="staff-chat-message initial first-line">';
                    $header_html .= '<div class="staff-chat-header-line">';
                    $header_html .= '<span class="staff-chat-avatar-wrapper">' . $avatar . '</span>';
                    $header_html .= '<span class="staff-chat-user-name">' . $user_display_name . '</span>';
                    $header_html .= '<span class="staff-chat-order-time">受注書作成：' . esc_html( $order_created_time ) . '</span>';
                    $header_html .= '</div>';
                    $header_html .= '</div>';
                    $header_html .= '</div>';
                } else {
                    // Subsequent messages: save for scrollable area
                    $scrollable_messages[] = $message;
                }
            }
        }
        
        // Add fixed header
        $html .= $header_html;
        
        // Scrollable message display area
        $html .= '<div class="staff-chat-messages" id="staff-chat-messages">';
        
        if ( ! empty( $scrollable_messages ) ) {
            foreach ( $scrollable_messages as $message ) {
                $created_at = $message['created_at'];
                $user_display_name = esc_html( $message['user_display_name'] );
                $message_content = esc_html( $message['message'] );
                
                // Format time
                $formatted_time = '';
                if ( ! empty( $created_at ) ) {
                    $dt = new DateTime( $created_at );
                    $formatted_time = $dt->format( 'Y/n/j H:i' );
                }
                
                // Get WordPress avatar
                $user_id = intval( $message['user_id'] );
                $avatar = get_avatar( $user_id, 24, '', $user_display_name, array( 'class' => 'staff-chat-wp-avatar' ) );
                
                $html .= '<div class="staff-chat-message scrollable">';
                $html .= '<div class="staff-chat-message-header">';
                $html .= '<span class="staff-chat-avatar-wrapper">' . $avatar . '</span>';
                $html .= '<span class="staff-chat-user-name">' . $user_display_name . '</span>';
                $html .= '<span class="staff-chat-timestamp" data-timestamp="' . esc_attr( $created_at ) . '">' . esc_html( $formatted_time ) . '</span>';
                $html .= '</div>';
                $html .= '<div class="staff-chat-message-content">' . nl2br( $message_content ) . '</div>';
                $html .= '</div>';
            }
        }
        
        $html .= '</div>'; // .staff-chat-messages
        
        // Message input form (for users with edit permissions only)
        $can_edit = current_user_can( 'edit_posts' );
        
        if ( $can_edit ) {
            $html .= '<form class="staff-chat-form" method="post" action="" id="staff-chat-form">';
            $html .= '<input type="hidden" name="staff_chat_order_id" value="' . esc_attr( $order_id ) . '">';
            $html .= wp_nonce_field( 'staff_chat_action', 'staff_chat_nonce', true, false );
            $html .= '<div class="staff-chat-input-wrapper">';
            $html .= '<textarea name="staff_chat_message" id="staff-chat-input" class="staff-chat-input" placeholder="' . esc_attr__( 'メッセージを入力してください...', 'ktpwp' ) . '" required></textarea>';
            $html .= '<button type="submit" id="staff-chat-submit" class="staff-chat-submit">' . esc_html__( '送信', 'ktpwp' ) . '</button>';
            $html .= '</div>';
            $html .= '</form>';
        }
        
        $html .= '</div>'; // .staff-chat-content
        $html .= '</div>'; // .order_memo_box
        
        return $html;
    }

    /**
     * Get messages after specified timestamp
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @param string $last_time Last message timestamp
     * @return array Array of messages
     */
    public function get_messages_after( $order_id, $last_time = '' ) {
        if ( ! $order_id || $order_id <= 0 ) {
            return array();
        }
        
        // Permission check
        if ( ! current_user_can( 'read' ) ) {
            return array();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_staff_chat';
        
        // Build query with proper escaping
        $sql = "SELECT * FROM `{$table_name}` WHERE order_id = %d";
        $params = array( $order_id );
        
        if ( ! empty( $last_time ) ) {
            $sql .= ' AND created_at > %s';
            $params[] = sanitize_text_field( $last_time );
        }
        
        $sql .= ' ORDER BY created_at ASC';
        
        $messages = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
        
        if ( ! $messages ) {
            return array();
        }
        
        // Format messages for AJAX response
        $formatted_messages = array();
        foreach ( $messages as $message ) {
            $formatted_messages[] = array(
                'id'                => intval( $message['id'] ),
                'user_display_name' => esc_html( $message['user_display_name'] ),
                'message'           => esc_html( $message['message'] ),
                'created_at'        => $message['created_at'],
                'timestamp'         => strtotime( $message['created_at'] ),
                'is_initial'        => intval( $message['is_initial'] ),
            );
        }
        
        return $formatted_messages;
    }

} // End of KTPWP_Staff_Chat class

} // class_exists check
