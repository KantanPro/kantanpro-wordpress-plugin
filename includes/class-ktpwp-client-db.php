<?php
/**
 * Client DB management class for KTPWP plugin
 *
 * Handles client table creation, update, delete, and search.
 *
 * @package KTPWP
 * @subpackage Includes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'KTPWP_Client_DB' ) ) {
class KTPWP_Client_DB {
    public static function get_instance() {
        static $instance = null;
        if ( $instance === null ) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * Create client table
     *
     * @param string $tab_name Table name suffix (sanitized)
     * @return bool Success status
     */
    public function create_table($tab_name) {
        global $wpdb;
        $tab_name = sanitize_key($tab_name);
        if (empty($tab_name)) {
            return false;
        }
        $my_table_version = '1.0.2';
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $charset_collate = $wpdb->get_charset_collate();
        $columns_def = [
            "id MEDIUMINT(9) NOT NULL AUTO_INCREMENT",
            "time BIGINT(11) DEFAULT '0' NOT NULL",
            "name TINYTEXT",
            "url VARCHAR(55)",
            "company_name VARCHAR(100) NOT NULL DEFAULT '" . __('初めてのお客様', 'ktpwp') . "'",
            "representative_name TINYTEXT",
            "email VARCHAR(100)",
            "phone VARCHAR(20)",
            "postal_code VARCHAR(10)",
            "prefecture TINYTEXT",
            "city TINYTEXT",
            "address TEXT",
            "building TINYTEXT",
            "closing_day TINYTEXT",
            "payment_month TINYTEXT",
            "payment_day TINYTEXT",
            "payment_method TINYTEXT",
            "tax_category VARCHAR(100) NOT NULL DEFAULT '" . __('税込', 'ktpwp') . "'",
            "memo TEXT",
            "search_field TEXT",
            "frequency INT NOT NULL DEFAULT 0",
            "client_status VARCHAR(100) NOT NULL DEFAULT '" . __('対象', 'ktpwp') . "'",
            "category VARCHAR(255) NULL",
            "UNIQUE KEY id (id)"
        ];
        $existing_table = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        if ($existing_table !== $table_name) {
            $sql = "CREATE TABLE {$table_name} (" . implode(", ", $columns_def) . ") {$charset_collate};";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $result = dbDelta($sql);
            if (!empty($result)) {
                add_option('ktp_' . $tab_name . '_table_version', $my_table_version);
                return true;
            }
            return false;
        } else {
            $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM {$table_name}", 0);
            $def_column_names = [];
            foreach ($columns_def as $def) {
                if (preg_match('/^([a-zA-Z0-9_]+)/', $def, $m)) {
                    $def_column_names[] = $m[1];
                }
            }
            foreach ($def_column_names as $i => $col_name) {
                if (!in_array($col_name, $existing_columns)) {
                    if ($col_name === 'UNIQUE') continue;
                    if ($col_name === 'category' && version_compare(get_option('ktp_' . $tab_name . '_table_version', '1.0.0'), '1.0.2', '<')) {
                        $def = $columns_def[$i];
                        $result = $wpdb->query($wpdb->prepare("ALTER TABLE {$table_name} ADD COLUMN {$def}"));
                        if ($result === false) {
                            error_log("KTPWP: Failed to add column {$col_name} to table {$table_name}");
                        }
                    } elseif ($col_name !== 'category') {
                        $def = $columns_def[$i];
                        $result = $wpdb->query($wpdb->prepare("ALTER TABLE {$table_name} ADD COLUMN {$def}"));
                        if ($result === false) {
                            error_log("KTPWP: Failed to add column {$col_name} to table {$table_name}");
                        }
                    }
                }
            }
            update_option('ktp_' . $tab_name . '_table_version', $my_table_version);
        }
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name}");
        $has_unique_id = false;
        foreach ($indexes as $idx) {
            if ($idx->Key_name === 'id' && $idx->Non_unique == 0) {
                $has_unique_id = true;
                break;
            }
        }
        if (!$has_unique_id) {
            $result = $wpdb->query($wpdb->prepare("ALTER TABLE {$table_name} ADD UNIQUE (id)"));
            if ($result === false) {
                error_log("KTPWP: Failed to add unique key to table {$table_name}");
            }
        }
        return true;
    }

    /**
     * Update table and handle POST operations
     *
     * @param string $tab_name Table name suffix
     * @return void
     */
    public function update_table($tab_name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;

        // 'category' カラムが存在するか確認し、なければ追加する (マイグレーション)
        $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM %i LIKE %s", $table_name, 'category'));
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN category VARCHAR(255) NULL");
        }

        // 空のカテゴリーフィールドを「対象」に更新（一度だけ実行）
        $migration_option = 'ktp_client_category_migration_done';
        if (!get_option($migration_option)) {
            $update_result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table_name} SET client_status = %s WHERE client_status = '' OR client_status IS NULL",
                    '対象'
                )
            );
            
            if ($update_result !== false) {
                update_option($migration_option, true);
            }
        }

        // POST処理の場合のみ実行
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // nonce検証
            if (!isset($_POST['ktp_client_nonce']) || !wp_verify_nonce($_POST['ktp_client_nonce'], 'ktp_client_action')) {
                wp_die(__('不正なリクエストです。', 'ktpwp'));
            }

            // POST データの取得とサニタイズ
            $query_post = isset($_POST['query_post']) ? sanitize_text_field($_POST['query_post']) : '';
            $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
            
            // フィールドデータの取得
            $fields_data = $this->sanitize_post_data($_POST);

            // 検索フィールドの更新
            $search_field_value = implode(' ', [
                $fields_data['company_name'], $fields_data['user_name'], $fields_data['email'], 
                $fields_data['representative_name'], $fields_data['phone'], $fields_data['prefecture'], 
                $fields_data['city'], $fields_data['address'], $fields_data['client_status'], $fields_data['category']
            ]);

            // 操作に応じた処理
            switch ($query_post) {
                case 'delete':
                    return $this->handle_delete($table_name, $tab_name, $data_id);
                case 'insert':
                    return $this->handle_insert($table_name, $tab_name, $fields_data, $search_field_value);
                case 'update':
                    return $this->handle_update($table_name, $tab_name, $data_id, $fields_data, $search_field_value);
                case 'search':
                    return $this->handle_search($table_name, $tab_name, $_POST);
            }
        }
    }

    /**
     * Sanitize POST data
     *
     * @param array $post_data POST data
     * @return array Sanitized data
     */
    private function sanitize_post_data($post_data) {
        return array(
            'company_name' => isset($post_data['company_name']) ? sanitize_text_field($post_data['company_name']) : '',
            'user_name' => isset($post_data['user_name']) ? sanitize_text_field($post_data['user_name']) : '',
            'email' => isset($post_data['email']) ? sanitize_email($post_data['email']) : '',
            'url' => isset($post_data['url']) ? esc_url_raw($post_data['url']) : '',
            'representative_name' => isset($post_data['representative_name']) ? sanitize_text_field($post_data['representative_name']) : '',
            'phone' => isset($post_data['phone']) ? sanitize_text_field($post_data['phone']) : '',
            'postal_code' => isset($post_data['postal_code']) ? sanitize_text_field($post_data['postal_code']) : '',
            'prefecture' => isset($post_data['prefecture']) ? sanitize_text_field($post_data['prefecture']) : '',
            'city' => isset($post_data['city']) ? sanitize_text_field($post_data['city']) : '',
            'address' => isset($post_data['address']) ? sanitize_text_field($post_data['address']) : '',
            'building' => isset($post_data['building']) ? sanitize_text_field($post_data['building']) : '',
            'closing_day' => isset($post_data['closing_day']) ? sanitize_text_field($post_data['closing_day']) : '',
            'payment_month' => isset($post_data['payment_month']) ? sanitize_text_field($post_data['payment_month']) : '',
            'payment_day' => isset($post_data['payment_day']) ? sanitize_text_field($post_data['payment_day']) : '',
            'payment_method' => isset($post_data['payment_method']) ? sanitize_text_field($post_data['payment_method']) : '',
            'tax_category' => isset($post_data['tax_category']) ? sanitize_text_field($post_data['tax_category']) : '',
            'memo' => isset($post_data['memo']) ? sanitize_textarea_field($post_data['memo']) : '',
            'client_status' => isset($post_data['client_status']) ? sanitize_text_field($post_data['client_status']) : '',
            'category' => isset($post_data['category']) ? sanitize_text_field($post_data['category']) : ''
        );
    }

    /**
     * Handle delete operation (soft delete)
     *
     * @param string $table_name Table name
     * @param string $tab_name Tab name
     * @param int $data_id Data ID
     * @return void
     */
    private function handle_delete($table_name, $tab_name, $data_id) {
        global $wpdb;
        
        if ($data_id > 0) {
            $result = $wpdb->update(
                $table_name,
                array('client_status' => '対象外'),
                array('id' => $data_id),
                array('%s'),
                array('%d')
            );

            if ($result !== false) {
                $next_id = $this->get_next_display_id($table_name, $data_id);
                
                $cookie_name = 'ktp_' . $tab_name . '_id';
                setcookie($cookie_name, $next_id, time() + (86400 * 30), "/");
                
                $redirect_url = add_query_arg(array(
                    'tab_name' => $tab_name,
                    'data_id' => $next_id,
                    'message' => 'deleted'
                ), wp_get_referer());
                
                wp_redirect($redirect_url);
                exit;
            }
        }
    }

    /**
     * Handle insert operation
     *
     * @param string $table_name Table name
     * @param string $tab_name Tab name
     * @param array $fields_data Field data
     * @param string $search_field_value Search field value
     * @return void
     */
    private function handle_insert($table_name, $tab_name, $fields_data, $search_field_value) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'time' => current_time('mysql'),
                'company_name' => $fields_data['company_name'],
                'name' => $fields_data['user_name'],
                'email' => $fields_data['email'],
                'url' => $fields_data['url'],
                'representative_name' => $fields_data['representative_name'],
                'phone' => $fields_data['phone'],
                'postal_code' => $fields_data['postal_code'],
                'prefecture' => $fields_data['prefecture'],
                'city' => $fields_data['city'],
                'address' => $fields_data['address'],
                'building' => $fields_data['building'],
                'closing_day' => $fields_data['closing_day'],
                'payment_month' => $fields_data['payment_month'],
                'payment_day' => $fields_data['payment_day'],
                'payment_method' => $fields_data['payment_method'],
                'tax_category' => $fields_data['tax_category'],
                'memo' => $fields_data['memo'],
                'client_status' => $fields_data['client_status'],
                'category' => $fields_data['category'],
                'search_field' => $search_field_value
            ),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', 
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );

        if ($result !== false) {
            $new_id = $wpdb->insert_id;
            $cookie_name = 'ktp_' . $tab_name . '_id';
            setcookie($cookie_name, $new_id, time() + (86400 * 30), "/");
            
            $redirect_url = add_query_arg(array(
                'tab_name' => $tab_name,
                'data_id' => $new_id,
                'message' => 'added'
            ), wp_get_referer());
            
            wp_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Handle update operation
     *
     * @param string $table_name Table name
     * @param string $tab_name Tab name
     * @param int $data_id Data ID
     * @param array $fields_data Field data
     * @param string $search_field_value Search field value
     * @return void
     */
    private function handle_update($table_name, $tab_name, $data_id, $fields_data, $search_field_value) {
        global $wpdb;
        
        if ($data_id > 0) {
            $result = $wpdb->update(
                $table_name,
                array(
                    'company_name' => $fields_data['company_name'],
                    'name' => $fields_data['user_name'],
                    'email' => $fields_data['email'],
                    'url' => $fields_data['url'],
                    'representative_name' => $fields_data['representative_name'],
                    'phone' => $fields_data['phone'],
                    'postal_code' => $fields_data['postal_code'],
                    'prefecture' => $fields_data['prefecture'],
                    'city' => $fields_data['city'],
                    'address' => $fields_data['address'],
                    'building' => $fields_data['building'],
                    'closing_day' => $fields_data['closing_day'],
                    'payment_month' => $fields_data['payment_month'],
                    'payment_day' => $fields_data['payment_day'],
                    'payment_method' => $fields_data['payment_method'],
                    'tax_category' => $fields_data['tax_category'],
                    'memo' => $fields_data['memo'],
                    'client_status' => $fields_data['client_status'],
                    'category' => $fields_data['category'],
                    'search_field' => $search_field_value
                ),
                array('id' => $data_id),
                array(
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', 
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
                ),
                array('%d')
            );

            if ($result !== false) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table_name SET frequency = frequency + 1 WHERE id = %d",
                    $data_id
                ));
                
                if (isset($_GET['sort_by']) || isset($_GET['sort_order'])) {
                    wp_redirect(remove_query_arg('message', wp_get_referer()));
                } else {
                    $redirect_url = add_query_arg(array(
                        'tab_name' => $tab_name,
                        'data_id' => $data_id,
                        'message' => 'updated'
                    ), wp_get_referer());
                    wp_redirect($redirect_url);
                }
                exit;
            }
        }
    }

    /**
     * Handle search operation
     *
     * @param string $table_name Table name
     * @param string $tab_name Tab name
     * @param array $post_data POST data
     * @return void
     */
    private function handle_search($table_name, $tab_name, $post_data) {
        global $wpdb;
        
        $search_query = isset($post_data['search_query']) ? sanitize_text_field($post_data['search_query']) : '';
        
        if (!empty($search_query)) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE search_field LIKE %s",
                '%' . $wpdb->esc_like($search_query) . '%'
            ));

            if (count($results) === 1) {
                $found_id = $results[0]->id;
                
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table_name SET frequency = frequency + 1 WHERE id = %d",
                    $found_id
                ));
                
                $cookie_name = 'ktp_' . $tab_name . '_id';
                setcookie($cookie_name, $found_id, time() + (86400 * 30), "/");
                
                $redirect_url = add_query_arg(array(
                    'tab_name' => $tab_name,
                    'data_id' => $found_id,
                    'message' => 'found'
                ), wp_get_referer());
                
                wp_redirect($redirect_url);
                exit;
            } elseif (count($results) > 1) {
                $redirect_url = add_query_arg(array(
                    'tab_name' => $tab_name,
                    'search_query' => $search_query,
                    'multiple_results' => '1'
                ), wp_get_referer());
                
                wp_redirect($redirect_url);
                exit;
            } else {
                $redirect_url = add_query_arg(array(
                    'tab_name' => $tab_name,
                    'search_query' => $search_query,
                    'message' => 'not_found'
                ), wp_get_referer());
                
                wp_redirect($redirect_url);
                exit;
            }
        }
    }

    /**
     * Get next display ID for pagination
     *
     * @param string $table_name Table name
     * @param int $deleted_id Deleted ID
     * @return int Next ID
     */
    public function get_next_display_id($table_name, $deleted_id) {
        global $wpdb;
        
        $next_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id > %d ORDER BY id ASC LIMIT 1",
            $deleted_id
        ));
        
        if ($next_id) {
            return $next_id;
        }
        
        $prev_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id < %d ORDER BY id DESC LIMIT 1",
            $deleted_id
        ));
        
        return $prev_id ? $prev_id : 1;
    }
}
}
