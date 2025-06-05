<?php
/**
 * Supplier Data Class for KTPWP plugin
 *
 * Handles database operations for supplier data.
 *
 * @package KTPWP
 * @subpackage Includes
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'KTPWP_Supplier_Data' ) ) {

/**
 * Class KTPWP_Supplier_Data
 *
 * Handles database operations for supplier data.
 *
 * @since 1.0.0
 */
class KTPWP_Supplier_Data {

    /**
     * Create supplier table
     *
     * @since 1.0.0
     * @param string $tab_name The table name suffix
     * @return bool True on success, false on failure
     */
    public function create_table( $tab_name ) {
        if ( empty( $tab_name ) ) {
            error_log( 'KTPWP: Empty tab_name provided to create_table method' );
            return false;
        }

        global $wpdb;
        $my_table_version = '1.0.0';
        $table_name = $wpdb->prefix . 'ktp_' . sanitize_key( $tab_name );
        $charset_collate = $wpdb->get_charset_collate();

        // Check if table exists using prepared statement
        $table_exists = $wpdb->get_var( $wpdb->prepare( 
            'SHOW TABLES LIKE %s', 
            $table_name 
        ) );

        if ( $table_exists !== $table_name ) {
            $default_company = __( 'Regular Supplier', 'ktpwp' );
            $default_tax = __( 'Tax Included', 'ktpwp' );
            $default_category = __( 'General', 'ktpwp' );
            
            $sql = $wpdb->prepare(
                "CREATE TABLE %i (
                    id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                    time BIGINT(11) DEFAULT '0' NOT NULL,
                    name TINYTEXT NOT NULL,
                    url VARCHAR(55) NOT NULL,
                    company_name VARCHAR(100) NOT NULL DEFAULT %s,
                    email VARCHAR(100) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    postal_code VARCHAR(10) NOT NULL,
                    prefecture TINYTEXT NOT NULL,
                    city TINYTEXT NOT NULL,
                    address TEXT NOT NULL,
                    building TINYTEXT NOT NULL,
                    closing_day TINYTEXT NOT NULL,
                    payment_month TINYTEXT NOT NULL,
                    payment_day TINYTEXT NOT NULL,
                    payment_method TINYTEXT NOT NULL,
                    tax_category VARCHAR(100) NOT NULL DEFAULT %s,
                    memo TEXT NOT NULL,
                    search_field TEXT NOT NULL,
                    frequency INT NOT NULL DEFAULT 0,
                    category VARCHAR(100) NOT NULL DEFAULT %s,
                    UNIQUE KEY id (id)
                ) " . $charset_collate,
                $table_name,
                $default_company,
                $default_tax,
                $default_category
            );

            // Include upgrade functions
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            
            if ( function_exists( 'dbDelta' ) ) {
                $result = dbDelta( $sql );
                
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPWP: Table creation result for ' . $table_name . ': ' . print_r( $result, true ) );
                }
                
                if ( ! empty( $result ) ) {
                    add_option( 'ktp_' . $tab_name . '_table_version', $my_table_version );
                    return true;
                }
                
                error_log( 'KTPWP: Failed to create table ' . $table_name );
                return false;
            }
            
            error_log( 'KTPWP: dbDelta function not available' );
            return false;
        }

        return true;
    }

    /**
     * Update supplier table data
     *
     * @since 1.0.0
     * @param string $tab_name Table name suffix
     * @param array $post_data POST data for the operation
     * @return void
     */
    public function update_table( $tab_name, $post_data ) {
        if ( empty( $tab_name ) ) {
            error_log( 'KTPWP: Empty tab_name provided to update_table method' );
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . sanitize_key( $tab_name );

        // Security: CSRF protection - verify nonce on POST requests
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            if ( ! isset( $post_data['ktp_supplier_nonce'] ) || 
                 ! wp_verify_nonce( $post_data['ktp_supplier_nonce'], 'ktp_supplier_action' ) ) {
                wp_die( __( 'Security check failed. Please refresh the page and try again.', 'ktpwp' ) );
            }
        }

        // Sanitize and validate input data
        $data_id = isset( $post_data['data_id'] ) ? absint( $post_data['data_id'] ) : 0;
        $query_post = isset( $post_data['query_post'] ) ? sanitize_key( $post_data['query_post'] ) : '';

        // Log operation without sensitive data
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'KTPWP: update_table called for tab: ' . $tab_name . ', action: ' . $query_post );
        }

        // Handle different operations (update, delete, insert, etc.)
        switch ( $query_post ) {
            case 'delete':
                // Handle delete operation
                // ...existing delete logic...
                break;

            case 'update':
                // Handle update operation
                // ...existing update logic...
                break;

            case 'insert':
                // Handle insert operation
                // ...existing insert logic...
                break;

            default:
                error_log( 'KTPWP: Invalid query_post action: ' . $query_post );
                break;
        }
    }
}
}
