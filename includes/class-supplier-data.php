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
        global $wpdb;

        if ( empty( $tab_name ) ) {
            error_log( 'KTPWP: Empty tab_name provided to create_table method' );
            return false;
        }

        $table_name = $wpdb->prefix . 'ktp_' . sanitize_key( $tab_name );
        $my_table_version = '1.1'; // Increment version for representative_name field
        $option_name = 'ktp_' . $tab_name . '_table_version';

        // Check if table needs to be created or updated
        $installed_version = get_option( $option_name );

        if ( $installed_version !== $my_table_version ) {
            $default_company = __( 'Regular Supplier', 'ktpwp' );
            $default_tax = __( 'Tax Included', 'ktpwp' );
            $default_category = __( 'General', 'ktpwp' );

            // Get charset collate
            $charset_collate = $wpdb->get_charset_collate();

            $sql = $wpdb->prepare(
                "CREATE TABLE %i (
                    id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                    time BIGINT(11) DEFAULT '0' NOT NULL,
                    name TINYTEXT NOT NULL,
                    url VARCHAR(55) NOT NULL,
                    company_name VARCHAR(100) NOT NULL DEFAULT %s,
                    email VARCHAR(100) NOT NULL,
                    representative_name VARCHAR(100) NOT NULL DEFAULT '',
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
                error_log( 'KTPWP: Nonce verification failed' );
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
                if ( $data_id > 0 ) {
                    $delete_result = $wpdb->delete( $table_name, array( 'id' => $data_id ), array( '%d' ) );

                    if ( $delete_result === false ) {
                        echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            showErrorNotification("' . esc_js( __( '削除に失敗しました。SQLエラー: ', 'ktpwp' ) ) . esc_js( $wpdb->last_error ) . '");
                        });
                        </script>';
                    } else {
                        $cookie_name = 'ktp_' . $tab_name . '_id';
                        setcookie( $cookie_name, '', time() - 3600, "/" );

                        // Prepare redirect URL
                        global $wp;
                        $current_page_id = get_queried_object_id();
                        $base_page_url = get_permalink( $current_page_id );
                        if ( ! $base_page_url ) {
                            $base_page_url = home_url( add_query_arg( array(), $wp->request ) );
                        }
                        $redirect_url = add_query_arg( array(
                            'tab_name' => $tab_name,
                            'message' => 'deleted'
                        ), $base_page_url );

                        echo '<script>
                            document.addEventListener("DOMContentLoaded", function() {
                                showSuccessNotification("' . esc_js( esc_html__( '協力会社を削除しました。', 'ktpwp' ) ) . '");
                                setTimeout(function() {
                                    window.location.href = "' . esc_js( $redirect_url ) . '";
                                }, 1000);
                            });
                        </script>';
                        return;
                    }
                }
                break;

            case 'update':
                // Handle update operation
                if ( $data_id > 0 ) {
                    // Sanitize all POST data for update operation
                    $sanitized_data = $this->sanitize_supplier_data( $post_data );

                    // Build search_field value
                    $search_field_value = implode(', ', [
                        current_time( 'timestamp' ),
                        $sanitized_data['company_name'],
                        $sanitized_data['user_name'],
                        $sanitized_data['email'],
                        $sanitized_data['url'],
                        $sanitized_data['representative_name'],
                        $sanitized_data['phone'],
                        $sanitized_data['postal_code'],
                        $sanitized_data['prefecture'],
                        $sanitized_data['city'],
                        $sanitized_data['address'],
                        $sanitized_data['building'],
                        $sanitized_data['closing_day'],
                        $sanitized_data['payment_month'],
                        $sanitized_data['payment_day'],
                        $sanitized_data['payment_method'],
                        $sanitized_data['tax_category'],
                        $sanitized_data['memo'],
                        $sanitized_data['category']
                    ]);

                    // Perform database update
                    $update_result = $wpdb->update(
                        $table_name,
                        array(
                            'time' => current_time( 'timestamp' ),
                            'company_name' => $sanitized_data['company_name'],
                            'name' => $sanitized_data['user_name'],
                            'email' => $sanitized_data['email'],
                            'url' => $sanitized_data['url'],
                            'representative_name' => $sanitized_data['representative_name'],
                            'phone' => $sanitized_data['phone'],
                            'postal_code' => $sanitized_data['postal_code'],
                            'prefecture' => $sanitized_data['prefecture'],
                            'city' => $sanitized_data['city'],
                            'address' => $sanitized_data['address'],
                            'building' => $sanitized_data['building'],
                            'closing_day' => $sanitized_data['closing_day'],
                            'payment_month' => $sanitized_data['payment_month'],
                            'payment_day' => $sanitized_data['payment_day'],
                            'payment_method' => $sanitized_data['payment_method'],
                            'tax_category' => $sanitized_data['tax_category'],
                            'memo' => $sanitized_data['memo'],
                            'category' => $sanitized_data['category'],
                            'search_field' => $search_field_value
                        ),
                        array( 'id' => $data_id ),
                        array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
                        array( '%d' )
                    );

                    if ( $update_result === false ) {
                        echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            showErrorNotification("' . esc_js( __( '更新に失敗しました。SQLエラー: ', 'ktpwp' ) ) . esc_js( $wpdb->last_error ) . '");
                        });
                        </script>';
                    } else {
                        $cookie_name = 'ktp_' . $tab_name . '_id';
                        setcookie( $cookie_name, $data_id, time() + (86400 * 30), "/" );

                        // Prepare redirect URL
                        global $wp;
                        $current_page_id = get_queried_object_id();
                        $base_page_url = get_permalink( $current_page_id );
                        if ( ! $base_page_url ) {
                            $base_page_url = home_url( add_query_arg( array(), $wp->request ) );
                        }
                        $redirect_url = add_query_arg( array(
                            'tab_name' => $tab_name,
                            'data_id' => $data_id,
                            'message' => 'updated'
                        ), $base_page_url );

                        echo '<script>
                            document.addEventListener("DOMContentLoaded", function() {
                                showSuccessNotification("' . esc_js( esc_html__( '協力会社情報を更新しました。', 'ktpwp' ) ) . '");
                                setTimeout(function() {
                                    window.location.href = "' . esc_js( $redirect_url ) . '";
                                }, 1000);
                            });
                        </script>';
                        return;
                    }
                }
                break;

            case 'insert':
                // Handle insert operation

                // Check if table exists before attempting insert
                $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;
                if ( ! $table_exists ) {
                    error_log( 'KTPWP ERROR: Table does not exist: ' . $table_name );
                    wp_die( __( 'Database table does not exist. Please contact the administrator.', 'ktpwp' ) );
                }

                // Sanitize all POST data for insert operation
                $sanitized_data = $this->sanitize_supplier_data( $post_data );

                // Build search_field value
                $search_field_value = implode(', ', [
                    current_time( 'timestamp' ),
                    $sanitized_data['company_name'],
                    $sanitized_data['user_name'],
                    $sanitized_data['email'],
                    $sanitized_data['url'],
                    $sanitized_data['representative_name'],
                    $sanitized_data['phone'],
                    $sanitized_data['postal_code'],
                    $sanitized_data['prefecture'],
                    $sanitized_data['city'],
                    $sanitized_data['address'],
                    $sanitized_data['building'],
                    $sanitized_data['closing_day'],
                    $sanitized_data['payment_month'],
                    $sanitized_data['payment_day'],
                    $sanitized_data['payment_method'],
                    $sanitized_data['tax_category'],
                    $sanitized_data['memo'],
                    $sanitized_data['category']
                ]);

                // Perform database insert
                $insert_result = $wpdb->insert(
                    $table_name,
                    array(
                        'time' => current_time( 'timestamp' ),
                        'company_name' => $sanitized_data['company_name'],
                        'name' => $sanitized_data['user_name'],
                        'email' => $sanitized_data['email'],
                        'url' => $sanitized_data['url'],
                        'representative_name' => $sanitized_data['representative_name'],
                        'phone' => $sanitized_data['phone'],
                        'postal_code' => $sanitized_data['postal_code'],
                        'prefecture' => $sanitized_data['prefecture'],
                        'city' => $sanitized_data['city'],
                        'address' => $sanitized_data['address'],
                        'building' => $sanitized_data['building'],
                        'closing_day' => $sanitized_data['closing_day'],
                        'payment_month' => $sanitized_data['payment_month'],
                        'payment_day' => $sanitized_data['payment_day'],
                        'payment_method' => $sanitized_data['payment_method'],
                        'tax_category' => $sanitized_data['tax_category'],
                        'memo' => $sanitized_data['memo'],
                        'category' => $sanitized_data['category'],
                        'search_field' => $search_field_value
                    )
                );

                if ( $insert_result === false ) {
                    echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        showErrorNotification("' . esc_js( __( '追加に失敗しました。SQLエラー: ', 'ktpwp' ) ) . esc_js( $wpdb->last_error ) . '");
                    });
                    </script>';
                } else {
                    // Get the ID of the inserted record
                    $new_data_id = $wpdb->insert_id;

                    // Set cookie for the new record
                    $cookie_name = 'ktp_' . $tab_name . '_id';
                    setcookie( $cookie_name, $new_data_id, time() + (86400 * 30), "/" );

                    // Prepare redirect URL
                    global $wp;
                    $current_page_id = get_queried_object_id();
                    $base_page_url = get_permalink( $current_page_id );
                    if ( ! $base_page_url ) {
                        $base_page_url = home_url( add_query_arg( array(), $wp->request ) );
                    }
                    $redirect_url = add_query_arg( array(
                        'tab_name' => $tab_name,
                        'data_id' => $new_data_id,
                        'message' => 'added'
                    ), $base_page_url );

                    // Use WordPress redirect function
                    wp_safe_redirect( $redirect_url );
                    exit;
                }
                break;

            default:
                error_log( 'KTPWP: Invalid query_post action: ' . $query_post );
                break;
        }
    }

    /**
     * Sanitize supplier data for database operations
     *
     * @since 1.0.0
     * @param array $post_data Raw POST data
     * @return array Sanitized data
     */
    private function sanitize_supplier_data( $post_data ) {
        return array(
            'company_name' => isset( $post_data['company_name'] ) ? sanitize_text_field( $post_data['company_name'] ) : '',
            'user_name' => isset( $post_data['user_name'] ) ? sanitize_text_field( $post_data['user_name'] ) : '',
            'email' => isset( $post_data['email'] ) ? sanitize_email( $post_data['email'] ) : '',
            'url' => isset( $post_data['url'] ) ? esc_url_raw( $post_data['url'] ) : '',
            'representative_name' => isset( $post_data['representative_name'] ) ? sanitize_text_field( $post_data['representative_name'] ) : '',
            'phone' => isset( $post_data['phone'] ) ? sanitize_text_field( $post_data['phone'] ) : '',
            'postal_code' => isset( $post_data['postal_code'] ) ? sanitize_text_field( $post_data['postal_code'] ) : '',
            'prefecture' => isset( $post_data['prefecture'] ) ? sanitize_text_field( $post_data['prefecture'] ) : '',
            'city' => isset( $post_data['city'] ) ? sanitize_text_field( $post_data['city'] ) : '',
            'address' => isset( $post_data['address'] ) ? sanitize_text_field( $post_data['address'] ) : '',
            'building' => isset( $post_data['building'] ) ? sanitize_text_field( $post_data['building'] ) : '',
            'closing_day' => isset( $post_data['closing_day'] ) ? sanitize_text_field( $post_data['closing_day'] ) : '',
            'payment_month' => isset( $post_data['payment_month'] ) ? sanitize_text_field( $post_data['payment_month'] ) : '',
            'payment_day' => isset( $post_data['payment_day'] ) ? sanitize_text_field( $post_data['payment_day'] ) : '',
            'payment_method' => isset( $post_data['payment_method'] ) ? sanitize_text_field( $post_data['payment_method'] ) : '',
            'tax_category' => isset( $post_data['tax_category'] ) ? sanitize_text_field( $post_data['tax_category'] ) : '',
            'memo' => isset( $post_data['memo'] ) ? sanitize_textarea_field( $post_data['memo'] ) : '',
            'category' => isset( $post_data['category'] ) ? sanitize_text_field( $post_data['category'] ) : '',
        );
    }
}
}
