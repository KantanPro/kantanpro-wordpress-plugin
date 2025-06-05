<?php
/**
 * Order items management class for KTPWP plugin
 *
 * Handles invoice items and cost items management.
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

if ( ! class_exists( 'KTPWP_Order_Items' ) ) {

/**
 * Order items management class
 *
 * @since 1.0.0
 */
class KTPWP_Order_Items {

    /**
     * Singleton instance
     *
     * @since 1.0.0
     * @var KTPWP_Order_Items
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return KTPWP_Order_Items
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
        $this->init();
    }

    /**
     * Initialize the class
     *
     * @since 1.0.0
     */
    private function init() {
        // フックの登録など初期化処理
    }

    /**
     * Create invoice items table
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function create_invoice_items_table() {
        global $wpdb;
        $my_table_version = '2.0';
        $table_name = $wpdb->prefix . 'ktp_order_invoice_items';
        $charset_collate = $wpdb->get_charset_collate();

        $columns_def = array(
            'id MEDIUMINT(9) NOT NULL AUTO_INCREMENT',
            'order_id MEDIUMINT(9) NOT NULL',
            'product_name VARCHAR(255) NOT NULL DEFAULT ""',
            'price INT(11) NOT NULL DEFAULT 0',
            'unit VARCHAR(50) NOT NULL DEFAULT ""', 
            'quantity INT(11) NOT NULL DEFAULT 0',
            'amount INT(11) NOT NULL DEFAULT 0',
            'remarks TEXT',
            'sort_order INT NOT NULL DEFAULT 0',
            'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'UNIQUE KEY id (id)',
            'KEY order_id (order_id)',
            'KEY sort_order (sort_order)'
        );

        // Check if table exists using prepared statement
        $table_exists = $wpdb->get_var( $wpdb->prepare( 
            'SHOW TABLES LIKE %s', 
            $table_name 
        ) );

        if ( $table_exists !== $table_name ) {
            $sql = "CREATE TABLE `{$table_name}` (" . implode( ', ', $columns_def ) . ") {$charset_collate};";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            
            if ( function_exists( 'dbDelta' ) ) {
                $result = dbDelta( $sql );
                
                if ( ! empty( $result ) ) {
                    add_option( 'ktp_invoice_items_table_version', $my_table_version );
                    return true;
                }
                
                error_log( 'KTPWP: Failed to create invoice items table' );
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
            
            foreach ( $def_column_names as $i => $col_name ) {
                if ( ! in_array( $col_name, $existing_columns, true ) ) {
                    if ( $col_name === 'UNIQUE' || $col_name === 'KEY' ) {
                        continue;
                    }
                    $def = $columns_def[ $i ];
                    $alter_query = "ALTER TABLE `{$table_name}` ADD COLUMN {$def}";
                    $result = $wpdb->query( $alter_query );
                    
                    if ( $result === false ) {
                        error_log( 'KTPWP: Failed to add column ' . $col_name . ' to invoice items table' );
                    }
                }
            }
            
            // Check and add UNIQUE KEY if not exists
            $indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table_name}`" );
            $has_unique_id = false;
            foreach ( $indexes as $idx ) {
                if ( $idx->Key_name === 'id' && $idx->Non_unique == 0 ) {
                    $has_unique_id = true;
                    break;
                }
            }
            if ( ! $has_unique_id ) {
                $wpdb->query( "ALTER TABLE `{$table_name}` ADD UNIQUE (id)" );
            }
            
            // Force migration of DECIMAL columns to INT for version 2.0
            $current_version = get_option( 'ktp_invoice_items_table_version', '1.0' );
            
            if ( version_compare( $current_version, '2.0', '<' ) ) {
                // Check current column types and migrate if needed
                $column_info = $wpdb->get_results( "SHOW COLUMNS FROM `{$table_name}` WHERE Field IN ('price', 'quantity', 'amount')" );
                
                foreach ( $column_info as $column ) {
                    // Always attempt to convert to INT regardless of current type
                    $alter_query = "ALTER TABLE `{$table_name}` MODIFY `{$column->Field}` INT(11) NOT NULL DEFAULT 0";
                    $result = $wpdb->query( $alter_query );
                    
                    if ( $result === false ) {
                        error_log( "KTPWP: Failed to migrate column {$column->Field} to INT in invoice items table. Error: " . $wpdb->last_error );
                    }
                }
            }
            
            update_option( 'ktp_invoice_items_table_version', $my_table_version );
        }
        
        return true;
    }

    /**
     * Create cost items table
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function create_cost_items_table() {
        global $wpdb;
        $my_table_version = '2.1';
        $table_name = $wpdb->prefix . 'ktp_order_cost_items';
        $charset_collate = $wpdb->get_charset_collate();

        $columns_def = array(
            'id MEDIUMINT(9) NOT NULL AUTO_INCREMENT',
            'order_id MEDIUMINT(9) NOT NULL',
            'product_name VARCHAR(255) NOT NULL DEFAULT ""',
            'price INT(11) NOT NULL DEFAULT 0',
            'unit VARCHAR(50) NOT NULL DEFAULT ""', 
            'quantity INT(11) NOT NULL DEFAULT 0',
            'amount INT(11) NOT NULL DEFAULT 0',
            'remarks TEXT',
            'sort_order INT NOT NULL DEFAULT 0',
            'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'UNIQUE KEY id (id)',
            'KEY order_id (order_id)',
            'KEY sort_order (sort_order)'
        );

        // Check if table exists using prepared statement
        $table_exists = $wpdb->get_var( $wpdb->prepare( 
            'SHOW TABLES LIKE %s', 
            $table_name 
        ) );

        if ( $table_exists !== $table_name ) {
            $sql = "CREATE TABLE `{$table_name}` (" . implode( ', ', $columns_def ) . ") {$charset_collate};";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            
            if ( function_exists( 'dbDelta' ) ) {
                $result = dbDelta( $sql );
                
                if ( ! empty( $result ) ) {
                    add_option( 'ktp_cost_items_table_version', $my_table_version );
                    return true;
                }
                
                error_log( 'KTPWP: Failed to create cost items table' );
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
            
            foreach ( $def_column_names as $i => $col_name ) {
                if ( ! in_array( $col_name, $existing_columns, true ) ) {
                    if ( $col_name === 'UNIQUE' || $col_name === 'KEY' ) {
                        continue;
                    }
                    $def = $columns_def[ $i ];
                    $alter_query = "ALTER TABLE `{$table_name}` ADD COLUMN {$def}";
                    $result = $wpdb->query( $alter_query );
                    
                    if ( $result === false ) {
                        error_log( 'KTPWP: Failed to add column ' . $col_name . ' to cost items table' );
                    }
                }
            }
            
            // Check and add UNIQUE KEY if not exists
            $indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table_name}`" );
            $has_unique_id = false;
            foreach ( $indexes as $idx ) {
                if ( $idx->Key_name === 'id' && $idx->Non_unique == 0 ) {
                    $has_unique_id = true;
                    break;
                }
            }
            if ( ! $has_unique_id ) {
                $wpdb->query( "ALTER TABLE `{$table_name}` ADD UNIQUE (id)" );
            }
            
            // Version upgrade migrations
            $current_version = get_option( 'ktp_cost_items_table_version', '1.0' );
            
            if ( version_compare( $current_version, '2.1', '<' ) ) {
                // Migrate columns to INT
                $column_info = $wpdb->get_results( "SHOW COLUMNS FROM `{$table_name}` WHERE Field IN ('price', 'quantity', 'amount')" );
                
                foreach ( $column_info as $column ) {
                    $alter_query = "ALTER TABLE `{$table_name}` MODIFY `{$column->Field}` INT(11) NOT NULL DEFAULT 0";
                    $result = $wpdb->query( $alter_query );
                    
                    if ( $result === false ) {
                        error_log( "KTPWP: Failed to migrate column {$column->Field} to INT in cost items table. Error: " . $wpdb->last_error );
                    }
                }
            }
            
            update_option( 'ktp_cost_items_table_version', $my_table_version );
        }
        
        return true;
    }

    /**
     * Get invoice items for an order
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return array Invoice items
     */
    public function get_invoice_items( $order_id ) {
        if ( ! $order_id || $order_id <= 0 ) {
            return array();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_invoice_items';

        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$table_name}` WHERE order_id = %d ORDER BY sort_order ASC, id ASC",
            $order_id
        ), ARRAY_A );

        return $items ? $items : array();
    }

    /**
     * Get cost items for an order
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return array Cost items
     */
    public function get_cost_items( $order_id ) {
        if ( ! $order_id || $order_id <= 0 ) {
            return array();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_cost_items';

        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$table_name}` WHERE order_id = %d ORDER BY sort_order ASC, id ASC",
            $order_id
        ), ARRAY_A );

        return $items ? $items : array();
    }

    /**
     * Save invoice items
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @param array $items Invoice items data
     * @return bool True on success, false on failure
     */
    public function save_invoice_items( $order_id, $items ) {
        if ( ! $order_id || $order_id <= 0 || ! is_array( $items ) ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_invoice_items';
        
        // Start transaction
        $wpdb->query( 'START TRANSACTION' );
        
        try {
            $sort_order = 1;
            $submitted_ids = array();
            foreach ( $items as $item ) {
                // Sanitize input data
                $item_id = isset( $item['id'] ) ? intval( $item['id'] ) : 0;
                $product_name = isset( $item['product_name'] ) ? sanitize_text_field( $item['product_name'] ) : '';
                $price = isset( $item['price'] ) ? floatval( $item['price'] ) : 0;
                $unit = isset( $item['unit'] ) ? sanitize_text_field( $item['unit'] ) : '';
                $quantity = isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 0;
                $amount = isset( $item['amount'] ) ? floatval( $item['amount'] ) : 0;
                $remarks = isset( $item['remarks'] ) ? sanitize_textarea_field( $item['remarks'] ) : '';

                // 商品名が空ならスキップ（商品名があれば必ず保存）
                if ( empty( $product_name ) ) {
                    continue;
                }

                $data = array(
                    'order_id' => $order_id,
                    'product_name' => $product_name,
                    'price' => $price,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'amount' => $amount,
                    'remarks' => $remarks,
                    'sort_order' => $sort_order,
                    'updated_at' => current_time( 'mysql' )
                );

                $format = array( '%d', '%s', '%f', '%s', '%f', '%f', '%s', '%d', '%s' );

                $used_id = 0;
                if ( $item_id > 0 ) {
                    // Update existing item
                    $result = $wpdb->update(
                        $table_name,
                        $data,
                        array( 'id' => $item_id, 'order_id' => $order_id ),
                        $format,
                        array( '%d', '%d' )
                    );
                    $used_id = $item_id;
                } else {
                    // Insert new item
                    $data['created_at'] = current_time( 'mysql' );
                    $format[] = '%s';
                    $result = $wpdb->insert( $table_name, $data, $format );
                    if ($result === false) {
                        error_log('KTPWP Error: Invoice item INSERT failed: ' . $wpdb->last_error);
                    }
                    $used_id = $wpdb->insert_id;
                }

                if ( $result === false ) {
                    throw new Exception( 'Database operation failed: ' . $wpdb->last_error );
                }

                if ($used_id > 0) {
                    $submitted_ids[] = $used_id;
                }

                $sort_order++;
            }

            // Remove any items that weren't in the submitted data
            if ( ! empty( $submitted_ids ) ) {
                $ids_placeholder = implode( ',', array_fill( 0, count( $submitted_ids ), '%d' ) );
                $delete_query = $wpdb->prepare(
                    "DELETE FROM `{$table_name}` WHERE order_id = %d AND id NOT IN ({$ids_placeholder})",
                    array_merge( array( $order_id ), $submitted_ids )
                );
                $wpdb->query( $delete_query );
            } else {
                // Delete all items if no valid items were submitted
                $wpdb->delete( $table_name, array( 'order_id' => $order_id ), array( '%d' ) );
            }

            // Commit transaction
            $wpdb->query( 'COMMIT' );
            return true;

        } catch ( Exception $e ) {
            // Rollback transaction
            $wpdb->query( 'ROLLBACK' );
            error_log( 'KTPWP: Failed to save invoice items: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Save cost items
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @param array $items Cost items data
     * @return bool True on success, false on failure
     */
    public function save_cost_items( $order_id, $items ) {
        if ( ! $order_id || $order_id <= 0 || ! is_array( $items ) ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_cost_items';
        
        // Start transaction
        $wpdb->query( 'START TRANSACTION' );
        
        try {
            $sort_order = 1;
            $submitted_ids = array();
            foreach ( $items as $item ) {
                // Sanitize input data
                $item_id = isset( $item['id'] ) ? intval( $item['id'] ) : 0;
                $product_name = isset( $item['product_name'] ) ? sanitize_text_field( $item['product_name'] ) : '';
                $price = isset( $item['price'] ) ? floatval( $item['price'] ) : 0;
                $unit = isset( $item['unit'] ) ? sanitize_text_field( $item['unit'] ) : '';
                $quantity = isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 0;
                $amount = isset( $item['amount'] ) ? floatval( $item['amount'] ) : 0;
                $remarks = isset( $item['remarks'] ) ? sanitize_textarea_field( $item['remarks'] ) : '';

                // 商品名が空ならスキップ（商品名があれば必ず保存）
                if ( empty( $product_name ) ) {
                    continue;
                }

                $data = array(
                    'order_id' => $order_id,
                    'product_name' => $product_name,
                    'price' => $price,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'amount' => $amount,
                    'remarks' => $remarks,
                    'sort_order' => $sort_order,
                    'updated_at' => current_time( 'mysql' )
                );

                $format = array( '%d', '%s', '%f', '%s', '%f', '%f', '%s', '%d', '%s' );

                $used_id = 0;
                if ( $item_id > 0 ) {
                    // Update existing item
                    $result = $wpdb->update(
                        $table_name,
                        $data,
                        array( 'id' => $item_id, 'order_id' => $order_id ),
                        $format,
                        array( '%d', '%d' )
                    );
                    $used_id = $item_id;
                } else {
                    // Insert new item
                    $data['created_at'] = current_time( 'mysql' );
                    $format[] = '%s';
                    $result = $wpdb->insert( $table_name, $data, $format );
                    if ($result === false) {
                        error_log('KTPWP Error: Cost item INSERT failed: ' . $wpdb->last_error);
                    }
                    $used_id = $wpdb->insert_id;
                }

                if ( $result === false ) {
                    throw new Exception( 'Database operation failed: ' . $wpdb->last_error );
                }

                if ($used_id > 0) {
                    $submitted_ids[] = $used_id;
                }

                $sort_order++;
            }

            // Remove any items that weren't in the submitted data
            if ( ! empty( $submitted_ids ) ) {
                $ids_placeholder = implode( ',', array_fill( 0, count( $submitted_ids ), '%d' ) );
                $delete_query = $wpdb->prepare(
                    "DELETE FROM `{$table_name}` WHERE order_id = %d AND id NOT IN ({$ids_placeholder})",
                    array_merge( array( $order_id ), $submitted_ids )
                );
                $wpdb->query( $delete_query );
            } else {
                // Delete all items if no valid items were submitted
                $wpdb->delete( $table_name, array( 'order_id' => $order_id ), array( '%d' ) );
            }

            // Commit transaction
            $wpdb->query( 'COMMIT' );
            return true;

        } catch ( Exception $e ) {
            // Rollback transaction
            $wpdb->query( 'ROLLBACK' );
            error_log( 'KTPWP: Failed to save cost items: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Create initial invoice item for new order
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    public function create_initial_invoice_item( $order_id ) {
        if ( ! $order_id || $order_id <= 0 ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_invoice_items';

        $data = array(
            'order_id' => $order_id,
            'product_name' => '',
            'price' => 0,
            'unit' => '式',
            'quantity' => 1,
            'amount' => 0,
            'remarks' => '',
            'sort_order' => 1,
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' )
        );

        $result = $wpdb->insert(
            $table_name,
            $data,
            array( '%d', '%s', '%d', '%s', '%d', '%d', '%s', '%d', '%s', '%s' )
        );

        if ( $result === false ) {
            error_log( 'KTPWP: Failed to create initial invoice item: ' . $wpdb->last_error );
            return false;
        }

        return true;
    }

    /**
     * Create initial cost item for new order
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    public function create_initial_cost_item( $order_id ) {
        if ( ! $order_id || $order_id <= 0 ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_cost_items';

        $data = array(
            'order_id' => $order_id,
            'product_name' => '',
            'price' => 0,
            'unit' => '式',
            'quantity' => 1,
            'amount' => 0,
            'remarks' => '',
            'sort_order' => 1,
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' )
        );

        $result = $wpdb->insert(
            $table_name,
            $data,
            array( '%d', '%s', '%d', '%s', '%d', '%d', '%s', '%d', '%s', '%s' )
        );

        if ( $result === false ) {
            error_log( 'KTPWP: Failed to create initial cost item: ' . $wpdb->last_error );
            return false;
        }

        return true;
    }

    /**
     * Delete all invoice items for an order
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    public function delete_invoice_items( $order_id ) {
        if ( ! $order_id || $order_id <= 0 ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_invoice_items';

        $result = $wpdb->delete(
            $table_name,
            array( 'order_id' => $order_id ),
            array( '%d' )
        );

        if ( $result === false ) {
            error_log( 'KTPWP: Failed to delete invoice items: ' . $wpdb->last_error );
            return false;
        }

        return true;
    }

    /**
     * Delete all cost items for an order
     *
     * @since 1.0.0
     * @param int $order_id Order ID
     * @return bool True on success, false on failure
     */
    public function delete_cost_items( $order_id ) {
        if ( ! $order_id || $order_id <= 0 ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_cost_items';

        $result = $wpdb->delete(
            $table_name,
            array( 'order_id' => $order_id ),
            array( '%d' )
        );

        if ( $result === false ) {
            error_log( 'KTPWP: Failed to delete cost items: ' . $wpdb->last_error );
            return false;
        }

        return true;
    }

    /**
     * Update single item field (for Ajax auto-save)
     *
     * @since 1.0.0
     * @param string $item_type Item type ('invoice' or 'cost')
     * @param int $item_id Item ID
     * @param string $field_name Field name
     * @param mixed $field_value Field value
     * @return bool True on success, false on failure
     */
    public function update_item_field( $item_type, $item_id, $field_name, $field_value ) {
        if ( ! in_array( $item_type, array( 'invoice', 'cost' ) ) || ! $item_id || $item_id <= 0 ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_' . $item_type . '_items';

        // Determine field update data based on field name
        $update_data = array();
        $format = array();
        
        switch ( $field_name ) {
            case 'product_name':
                $update_data['product_name'] = sanitize_text_field( $field_value );
                $format[] = '%s';
                break;
            case 'price':
                $update_data['price'] = floatval( $field_value );
                $format[] = '%f';
                break;
            case 'quantity':
                $update_data['quantity'] = floatval( $field_value );
                $format[] = '%f';
                break;
            case 'unit':
                $update_data['unit'] = sanitize_text_field( $field_value );
                $format[] = '%s';
                break;
            case 'amount':
                $update_data['amount'] = floatval( $field_value );
                $format[] = '%f';
                break;
            case 'remarks':
                $update_data['remarks'] = sanitize_textarea_field( $field_value );
                $format[] = '%s';
                break;
            default:
                return false;
        }

        // Always update the updated_at timestamp
        $update_data['updated_at'] = current_time( 'mysql' );
        $format[] = '%s';

        $result = $wpdb->update(
            $table_name,
            $update_data,
            array( 'id' => $item_id ),
            $format,
            array( '%d' )
        );

        if ( $result === false ) {
            error_log( 'KTPWP: Failed to update item field: ' . $wpdb->last_error );
            return false;
        }

        return true;
    }

    /**
     * Create new item (for Ajax)
     *
     * @since 1.0.0
     * @param string $item_type Item type ('invoice' or 'cost')
     * @param int $order_id Order ID
     * @return int|false Item ID on success, false on failure
     */
    public function create_new_item( $item_type, $order_id ) {
        if ( ! in_array( $item_type, array( 'invoice', 'cost' ) ) || ! $order_id || $order_id <= 0 ) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_order_' . $item_type . '_items';

        $data = array(
            'order_id' => $order_id,
            'product_name' => '',
            'price' => 0,
            'quantity' => 1,
            'unit' => '式',
            'amount' => 0,
            'remarks' => '',
            'sort_order' => 999,
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' )
        );

        $result = $wpdb->insert(
            $table_name,
            $data,
            array( '%d', '%s', '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s' )
        );

        if ( $result === false ) {
            error_log( 'KTPWP: Failed to create new item: ' . $wpdb->last_error );
            return false;
        }

        return $wpdb->insert_id;
    }

} // End of KTPWP_Order_Items class

} // class_exists check
