<?php

if (!class_exists('Kntan_Client_Class')) {
    class Kntan_Client_Class {

        public function __construct() {
            // constructor logic if any
        }
    
        // -----------------------------
        // テーブル作成
        // -----------------------------
    function Create_Table($tab_name) {
        global $wpdb;
        // translators: Version number for the client data table.
        $my_table_version = '1.0.1';
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $charset_collate = $wpdb->get_charset_collate();
    
        $columns_def = [
            "id MEDIUMINT(9) NOT NULL AUTO_INCREMENT",
            "time BIGINT(11) DEFAULT '0' NOT NULL",
            // translators: Database column comment: Client's name or contact person's name.
            "name TINYTEXT",
            // translators: Database column comment: Client's website URL.
            "url VARCHAR(55)",
            // translators: Database column comment: Client's company name. Default is '初めてのお客様' (New Customer).
            "company_name VARCHAR(100) NOT NULL DEFAULT '初めてのお客様'",
            // translators: Database column comment: Client's representative name.
            "representative_name TINYTEXT",
            // translators: Database column comment: Client's email address.
            "email VARCHAR(100)",
            // translators: Database column comment: Client's phone number.
            "phone VARCHAR(20)",
            // translators: Database column comment: Client's postal code.
            "postal_code VARCHAR(10)",
            // translators: Database column comment: Client's prefecture.
            "prefecture TINYTEXT",
            // translators: Database column comment: Client's city.
            "city TINYTEXT",
            // translators: Database column comment: Client's street address.
            "address TEXT",
            // translators: Database column comment: Client's building name.
            "building TINYTEXT",
            // translators: Database column comment: Client's closing day for billing.
            "closing_day TINYTEXT",
            // translators: Database column comment: Client's payment month for billing.
            "payment_month TINYTEXT",
            // translators: Database column comment: Client's payment day for billing.
            "payment_day TINYTEXT",
            // translators: Database column comment: Client's preferred payment method.
            "payment_method TINYTEXT",
            // translators: Database column comment: Client's tax category (e.g., tax included, tax excluded). Default is '税込' (Tax Included).
            "tax_category VARCHAR(100) NOT NULL DEFAULT '税込'",
            // translators: Database column comment: Memo for client.
            "memo TEXT",
            // translators: Database column comment: Concatenated field for searching purposes.
            "search_field TEXT",
            // translators: Database column comment: Frequency of interaction or access.
            "frequency INT NOT NULL DEFAULT 0",
            // translators: Database column comment: Client category. Default is '一般' (General).
            "category VARCHAR(100) NOT NULL DEFAULT '一般'",
            "UNIQUE KEY id (id)"
        ];
    
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (" . implode(", ", $columns_def) . ") $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            add_option('ktp_' . $tab_name . '_table_version', $my_table_version);
        } else {
            $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name", 0);
            $def_column_names = [];
            foreach ($columns_def as $def) {
                if (preg_match('/^([a-zA-Z0-9_]+)/', $def, $m)) {
                    $def_column_names[] = $m[1];
                }
            }
            foreach ($def_column_names as $i => $col_name) {
                if (!in_array($col_name, $existing_columns)) {
                    if ($col_name === 'UNIQUE') continue;
                    $def = $columns_def[$i];
                    $wpdb->query("ALTER TABLE $table_name ADD COLUMN $def");
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
            $wpdb->query("ALTER TABLE {$table_name} ADD UNIQUE (id)");
        }
    }

    // -----------------------------
    // テーブルの操作（更新・追加・削除・検索）
    // -----------------------------
    function Update_Table($tab_name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;

        // Sanitize all expected POST data first
        $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : '';
        $query_post = isset($_POST['query_post']) ? sanitize_text_field(wp_unslash($_POST['query_post'])) : '';
        $company_name = isset($_POST['company_name']) ? sanitize_text_field(wp_unslash($_POST['company_name'])) : '';
        $user_name = isset($_POST['user_name']) ? sanitize_text_field(wp_unslash($_POST['user_name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
        $representative_name = isset($_POST['representative_name']) ? sanitize_text_field(wp_unslash($_POST['representative_name'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $postal_code = isset($_POST['postal_code']) ? sanitize_text_field(wp_unslash($_POST['postal_code'])) : '';
        $prefecture = isset($_POST['prefecture']) ? sanitize_text_field(wp_unslash($_POST['prefecture'])) : '';
        $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
        $address = isset($_POST['address']) ? sanitize_text_field(wp_unslash($_POST['address'])) : '';
        $building = isset($_POST['building']) ? sanitize_text_field(wp_unslash($_POST['building'])) : '';
        $closing_day = isset($_POST['closing_day']) ? sanitize_text_field(wp_unslash($_POST['closing_day'])) : '';
        $payment_month = isset($_POST['payment_month']) ? sanitize_text_field(wp_unslash($_POST['payment_month'])) : '';
        $payment_day = isset($_POST['payment_day']) ? sanitize_text_field(wp_unslash($_POST['payment_day'])) : '';
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field(wp_unslash($_POST['payment_method'])) : '';
        $tax_category = isset($_POST['tax_category']) ? sanitize_text_field(wp_unslash($_POST['tax_category'])) : '';
        $memo = isset($_POST['memo']) ? sanitize_textarea_field(wp_unslash($_POST['memo'])) : '';
        $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';

        // Nonce verification
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nonce_action = '';
            $nonce_name = '';
            $verified = false;

            if ($query_post === 'delete' && !empty($data_id) && $data_id > 0) {
                $nonce_action = 'ktp_delete_client_' . $data_id;
                $nonce_name = '_ktp_delete_client_nonce';
            } elseif ($query_post === 'update' && !empty($data_id)) {
                $nonce_action = 'ktp_update_client_' . $data_id;
                $nonce_name = '_ktp_update_client_nonce';
            } elseif ($query_post === 'search') {
                $nonce_action = 'ktp_search_client';
                $nonce_name = '_ktp_search_client_nonce';
            } elseif ($query_post === 'insert') {
                $nonce_action = 'ktp_insert_client';
                $nonce_name = '_ktp_insert_client_nonce';
            }

            if (!empty($nonce_action) && !empty($nonce_name) && isset($_POST[$nonce_name])) {
                if (wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_name])), $nonce_action)) {
                    $verified = true;
                }
            }

            if (!empty($nonce_action) && !$verified) {
                // translators: Error message displayed when a security check (nonce verification) fails.
                wp_die(esc_html__('Security check failed. Please try again.', 'ktpwp'));
            }
        }

        $wpdb->query("LOCK TABLES {$table_name} WRITE;"); // Reinstated

        $search_field_value = implode(', ', array_filter([
            // $data_id, // Avoid including $data_id directly in search_field if it's for a new record or can change
            // current_time('mysql'), // Time is usually not part of a keyword search field
            $company_name,
            $user_name,
            $email,
            $url,
            $representative_name,
            $phone,
            $postal_code,
            $prefecture,
            $city,
            $address,
            $building,
            // $closing_day, $payment_month, $payment_day, $payment_method, // Usually not search terms
            // $tax_category, // Usually not a search term
            $memo,
            $category
        ]));

        // 削除
        if ($query_post == 'delete' && $data_id > 0) {
            $wpdb->delete($table_name, ['id' => $data_id], ['%d']);
            $wpdb->query("UNLOCK TABLES;"); // Reinstated

            $next_id_query = "SELECT id FROM {$table_name} WHERE id > %d ORDER BY id ASC LIMIT 1";
            $next_id_result = $wpdb->get_row($wpdb->prepare($next_id_query, $data_id));
            if ($next_id_result) {
                $next_data_id = $next_id_result->id;
            } else {
                $prev_id_query = "SELECT id FROM {$table_name} WHERE id < %d ORDER BY id DESC LIMIT 1";
                $prev_id_result = $wpdb->get_row($wpdb->prepare($prev_id_query, $data_id));
                $next_data_id = $prev_id_result ? $prev_id_result->id : 0;
            }
            $cookie_name = 'ktp_' . $tab_name . '_id';
            setcookie($cookie_name, $next_data_id, time() + (86400 * 30), "/");

            $redirect_url = add_query_arg([
                'tab_name' => $tab_name,
                'data_id' => $next_data_id,
                'query_post' => 'update' // Show next/prev record in update mode
            ], admin_url('admin.php?page=ktpwp-plugin')); // Assuming a fixed admin page slug
            wp_safe_redirect($redirect_url);
            exit;
        }
        // 更新
        elseif ($query_post == 'update' && $data_id > 0) {
            $wpdb->update(
                $table_name,
                [
                    'company_name' => $company_name, 'name' => $user_name, 'email' => $email, 'url' => $url,
                    'representative_name' => $representative_name, 'phone' => $phone, 'postal_code' => $postal_code,
                    'prefecture' => $prefecture, 'city' => $city, 'address' => $address, 'building' => $building,
                    'closing_day' => $closing_day, 'payment_month' => $payment_month, 'payment_day' => $payment_day,
                    'payment_method' => $payment_method, 'tax_category' => $tax_category, 'memo' => $memo,
                    'category' => $category, 'search_field' => $search_field_value
                ],
                ['id' => $data_id],
                [
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
                ],
                ['%d']
            );
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET frequency = frequency + 1 WHERE id = %d", $data_id));
            $wpdb->query("UNLOCK TABLES;"); // Reinstated

            $redirect_url = add_query_arg([
                'tab_name' => $tab_name,
                'data_id' => $data_id,
                'query_post' => 'update'
            ], admin_url('admin.php?page=ktpwp-plugin'));
            wp_safe_redirect($redirect_url);
            exit;
        }
        // 検索
        elseif ($query_post == 'search') {
            $search_query_term = isset($_POST['search_query']) ? sanitize_text_field(wp_unslash($_POST['search_query'])) : '';
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE search_field LIKE %s", '%' . $wpdb->esc_like($search_query_term) . '%'));

            if (count($results) == 1) {
                $id = $results[0]->id;
                $wpdb->query($wpdb->prepare("UPDATE $table_name SET frequency = frequency + 1 WHERE ID = %d", $id));
                $redirect_url = add_query_arg([
                    'tab_name' => $tab_name,
                    'data_id' => $id,
                    'query_post' => 'update'
                ], admin_url('admin.php?page=ktpwp-plugin'));
                wp_safe_redirect($redirect_url);
                exit;
            } elseif (count($results) > 1) {
                // The existing JS popup for multiple results. This part needs to be carefully managed.
                // For now, we assume it's handled on the client-side after this POST request finishes and View_Table re-renders.
                // To pass results to View_Table, use a session or transient.
                if (!session_id()) { session_start(); }
                $_SESSION['ktp_search_results_client'] = $results; // Pass full results
                $_SESSION['ktp_search_query_client'] = $search_query_term;

                $redirect_url = add_query_arg([
                    'tab_name' => $tab_name,
                    // 'data_id' => $data_id, // Keep current context if any, or clear
                    // translators: URL parameter value indicating search mode.
                    'query_post' => 'srcmode', // Stay in search mode to display multiple results message/list
                    // translators: URL parameter value indicating that search results are stored in the session for the 'client' tab.
                    'search_results_key' => 'client' // Indicate that results are in session
                ], admin_url('admin.php?page=ktpwp-plugin'));
                wp_safe_redirect($redirect_url);
                exit;
            } else {
                $wpdb->query("UNLOCK TABLES;"); // Reinstated
                if (!session_id()) { session_start(); }
                // translators: Message displayed when a search yields no results.
                $_SESSION['ktp_search_message_client'] = __('検索結果はありませんでした。', 'ktpwp');
                $redirect_url = add_query_arg([
                    'tab_name' => $tab_name,
                    // 'data_id' => $data_id, // Keep current context
                    // translators: URL parameter value indicating search mode. (Duplicate, consider if needed in this context too)
                    'query_post' => 'srcmode',
                    'search_query' => $search_query_term,
                    // translators: URL parameter value indicating that no search results were found.
                    'no_results' => '1'
                ], admin_url('admin.php?page=ktpwp-plugin'));
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
        // 追加
        elseif ($query_post == 'insert') {
            $insert_result = $wpdb->insert(
                $table_name,
                [
                    'time' => current_time('mysql'), 'company_name' => $company_name, 'name' => $user_name,
                    'email' => $email, 'url' => $url, 'representative_name' => $representative_name,
                    'phone' => $phone, 'postal_code' => $postal_code, 'prefecture' => $prefecture,
                    'city' => $city, 'address' => $address, 'building' => $building,
                    'closing_day' => $closing_day, 'payment_month' => $payment_month, 'payment_day' => $payment_day,
                    'payment_method' => $payment_method, 'tax_category' => $tax_category, 'memo' => $memo,
                    'category' => $category, 'search_field' => $search_field_value
                ],
                [
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
                ]
            );
            if ($insert_result === false) {
                error_log('KTPWP Insert error: ' . $wpdb->last_error);
                $wpdb->query("UNLOCK TABLES;"); // Reinstated
                // Redirect with error message or handle error display
                // translators: URL parameter value indicating an error occurred during data insertion.
                $redirect_url = add_query_arg(['tab_name' => $tab_name, 'query_post' => 'istmode', 'insert_error' => '1'], admin_url('admin.php?page=ktpwp-plugin'));
                wp_safe_redirect($redirect_url);
                exit;
            } else {
                $new_data_id = $wpdb->insert_id;
                $wpdb->query("UNLOCK TABLES;"); // Reinstated
                $redirect_url = add_query_arg([
                    'tab_name' => $tab_name,
                    'data_id' => $new_data_id,
                    'query_post' => 'update' // Go to update view for the new record
                ], admin_url('admin.php?page=ktpwp-plugin'));
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
        $wpdb->query("UNLOCK TABLES;"); // Fallback unlock, reinstated
    }

    // -----------------------------
    // テーブルの表示
    // -----------------------------
    function View_Table($name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $name;
        // translators: Base admin URL for the plugin page. This is used to construct various links.
        $base_admin_url = admin_url('admin.php?page=ktpwp-plugin'); // Define base URL for plugin page

        if (!session_id() && (isset($_GET['search_results_key']) || isset($_GET['no_results']) || isset($_GET['insert_error']))) {
            session_start(); // Start session if we expect to read session messages
        }

        // Handle POST actions for view state changes (add mode, search mode, cancel search)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $posted_action = isset($_POST['query_post']) ? sanitize_text_field(wp_unslash($_POST['query_post'])) : '';
            $nonce_name = '';
            $nonce_action_key = '';
            $nonce_valid = false;

            if ($posted_action === 'istmode') {
                $nonce_name = '_ktp_add_mode_client_nonce';
                $nonce_action_key = 'ktp_add_mode_client';
            } elseif ($posted_action === 'srcmode') {
                $nonce_name = '_ktp_search_mode_client_nonce';
                $nonce_action_key = 'ktp_search_mode_client';
            } elseif ($posted_action === 'cancel_search') {
                $nonce_name = '_ktp_cancel_search_client_nonce';
                $nonce_action_key = 'ktp_cancel_search_client';
            }

            if (!empty($nonce_action_key) && !empty($nonce_name) && isset($_POST[$nonce_name])) {
                if (wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_name])), $nonce_action_key)) {
                    $nonce_valid = true;
                }
            }

            if (!empty($nonce_action_key) && !$nonce_valid) {
                // translators: Error message displayed when a security check for view state change fails.
                wp_die(esc_html__('Security check failed for view state change. Please try again.', 'ktpwp'));
            }
            
            // If nonce is valid for a view state change, redirect to GET to reflect the new state
            if ($nonce_valid) {
                $redirect_params = ['tab_name' => $name, 'query_post' => $posted_action];
                if (isset($_POST['data_id'])) { // Preserve data_id context if provided
                    $redirect_params['data_id'] = intval($_POST['data_id']);
                }
                $redirect_url = add_query_arg($redirect_params, $base_admin_url);
                wp_safe_redirect($redirect_url);
                exit;
            }
        }

        // Determine current action/view based on GET parameters
        $action = 'update'; // Default view
        if (isset($_GET['query_post'])) {
            $action = sanitize_text_field(wp_unslash($_GET['query_post']));
        }
        if ($action === 'cancel_search') {
            $action = 'update'; // Revert to default view, data_id context should be from URL or cookie
        }

        // Data ID determination (from GET, then cookie, then max ID)
        $data_id = '';
        $cookie_name = 'ktp_' . $name . '_id';
        if (isset($_GET['data_id']) && $_GET['data_id'] !== '') {
            $data_id = intval($_GET['data_id']);
        } elseif (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] !== '') {
            $cookie_id_val = intval($_COOKIE[$cookie_name]);
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE id = %d", $cookie_id_val));
            if ($exists) {
                $data_id = $cookie_id_val;
            }
        }
        if (empty($data_id) && $action !== 'istmode') { // If no ID and not in add mode, try to get max ID
             $max_id_row = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1");
             if ($max_id_row) {
                 $data_id = $max_id_row->id;
             }
        }
        if (!empty($data_id)) {
             setcookie($cookie_name, $data_id, time() + (86400 * 30), "/");
        }
        
        // Fetch current record data if $data_id is available and not in 'istmode'
        $current_record = null;
        if (!empty($data_id) && $action !== 'istmode') {
            $current_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $data_id));
            if (!$current_record) {
                // If ID from param/cookie is invalid, try to get the max ID again, or clear $data_id
                $max_id_row = $wpdb->get_row("SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1");
                if ($max_id_row) {
                    $data_id = $max_id_row->id;
                    $current_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $data_id));
                    setcookie($cookie_name, $data_id, time() + (86400 * 30), "/");
                } else {
                    $data_id = ''; // No records found
                }
            }
        }

        // If after all checks, no valid $data_id for 'update' mode, and not 'istmode' or 'srcmode', it implies no data.
        $no_data_to_display = (empty($data_id) && $action === 'update' && !$current_record);

        // List display part (pagination, sorting, etc.)
        $data_list_html = '';
        $items_per_page = 20; // Number of items per page
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $items_per_page;

        // $view_mode is already defined earlier in the function from $_GET['view_mode']
        // $list_title_text is also already defined based on $view_mode
        // translators: Title for the data list box. Content varies based on view_mode (e.g., "Client List", "Order History"). This string is dynamically populated.
        $data_list_html .= '<div class="data_contents"><div class="data_list_box"><div class="data_list_title">' . esc_html($list_title_text) . '</div>';
        $data_list_html .= '<div class="data_list_items">'; // Wrapper for items

        if ($view_mode === 'customer_list') {
            $total_items_query = "SELECT COUNT(*) FROM {$table_name}";
            $total_items = $wpdb->get_var($total_items_query);

            $clients_query = $wpdb->prepare(
                "SELECT id, company_name, name, category FROM {$table_name} ORDER BY company_name ASC LIMIT %d OFFSET %d",
                $items_per_page,
                $offset
            );
            $clients = $wpdb->get_results($clients_query);

            if ($clients) {
                $data_list_html .= '<ul>';
                foreach ($clients as $client) {
                    $link_url_params = ['tab_name' => $name, 'data_id' => $client->id, 'query_post' => 'update'];
                    // Ensure view_mode is customer_list when clicking a client from the list, to show details alongside the customer list
                    $link_url_params['view_mode'] = 'customer_list';
                    $link_url = esc_url(add_query_arg($link_url_params, $base_admin_url));
                    $link_url = remove_query_arg('paged', $link_url); // Remove paged from individual item links

                    $data_list_html .= '<li>';
                    $data_list_html .= '<a href="' . $link_url . '">';
                    $data_list_html .= esc_html($client->company_name);
                    if (!empty($client->name)) {
                        $data_list_html .= ' (' . esc_html($client->name) . ')';
                    }
                    if (!empty($client->category)) {
                        $data_list_html .= ' - <em>' . esc_html($client->category) . '</em>';
                    }
                    $data_list_html .= '</a>';
                    $data_list_html .= '</li>';
                }
                $data_list_html .= '</ul>';

                // Pagination
                $total_pages = ceil($total_items / $items_per_page);
                if ($total_pages > 1) {
                    $pagination_base_url = add_query_arg('paged', '%#%', $base_admin_url);
                    // Ensure pagination links are for the customer list view
                    $pagination_base_url = add_query_arg(['tab_name' => $name, 'view_mode' => 'customer_list'], $pagination_base_url);
                    // Remove params that are not relevant for pagination itself
                    $pagination_base_url = remove_query_arg(['query_post', 'data_id', 'search_results_key', 'no_results', 'search_query', 'insert_error'], $pagination_base_url);

                    $pagination_args = [
                        'base' => $pagination_base_url,
                        'format' => '', // Already part of base due to paged=%#%
                        'total' => $total_pages,
                        'current' => $current_page,
                        /* translators: Pagination: Previous page link. */
                        'prev_text' => esc_html__('&laquo; 前へ', 'ktpwp'),
                        /* translators: Pagination: Next page link. */
                        'next_text' => esc_html__('次へ &raquo;', 'ktpwp'),
                    ];
                    $data_list_html .= '<div class="tablenav"><div class="tablenav-pages">' . paginate_links($pagination_args) . '</div></div>';
                }
            } else {
                /* translators: Message shown when no client data is registered. */
                $data_list_html .= '<p>' . esc_html__('顧客データは登録されていません。', 'ktpwp') . '</p>';
            }
        } elseif ($view_mode === 'order_history') {
            if (!empty($data_id) && $current_record) {
                $data_list_html .= '<p>' . sprintf(
                        /* translators: 1: Client ID, 2: Client company name. Message explaining order history is viewed elsewhere. */
                        esc_html__('顧客 ID %1$s (%2$s) の注文履歴は、注文管理機能を通じて表示されます。この画面では現在表示されません。', 'ktpwp'),
                        esc_html($data_id),
                        esc_html($current_record->company_name)
                    ) . '</p>';
                /* translators: Instruction for users on how to return to client details or navigate to order management. */
                $data_list_html .= '<p>' . esc_html__('「顧客詳細に戻る」ボタンを使用して顧客情報表示に戻るか、上部ナビゲーションから注文管理タブに移動してください。', 'ktpwp') . '</p>';
            } else {
                /* translators: Message shown when no client is selected to view order history. */
                 $data_list_html .= '<p>' . esc_html__('注文履歴を表示する顧客が選択されていません。まず顧客リストから顧客を選択し、「この顧客の注文履歴を見る」ボタンを使用してください。', 'ktpwp') . '</p>';
            }
        }

        $data_list_html .= '</div>'; // Close data_list_items
        $data_list_html .= '</div></div>'; // Close data_list_box and data_contents


        // Form fields definition (as in original)
        $fields = [
            /* translators: Form field label: Company name. */
            '会社名' => ['type' => 'text', 'name' => 'company_name', 'required' => true, 'placeholder' => /* translators: Placeholder text for company name input. Indicates it is a required field for corporate name or trade name. */ __('必須 法人名または屋号', 'ktpwp')],
            /* translators: Form field label: User name. */
            '名前' => ['type' => 'text', 'name' => 'user_name', 'placeholder' => /* translators: Placeholder text for user name input (contact person). */ __('担当者名', 'ktpwp')],
            /* translators: Form field label: Email. */
            'メール' => ['type' => 'email', 'name' => 'email'],
            /* translators: Form field label: URL. */
            'URL' => ['type' => 'text', 'name' => 'url', 'placeholder' => /* translators: Placeholder text for URL input. Example: https://.... */ 'https://....'],
            /* translators: Form field label: Representative name. */
            '代表者名' => ['type' => 'text', 'name' => 'representative_name', 'placeholder' => /* translators: Placeholder text for representative name input. */ __('代表者名', 'ktpwp')],
            /* translators: Form field label: Phone number. */
            '電話番号' => ['type' => 'text', 'name' => 'phone', 'pattern' => '\\\\d*', 'placeholder' => /* translators: Placeholder text for phone number input. Instructs to use half-width numbers without hyphens. */ __('半角数字 ハイフン不要', 'ktpwp')],
            /* translators: Form field label: Postal code. */
            '郵便番号' => ['type' => 'text', 'name' => 'postal_code', 'pattern' => '[0-9]*', 'placeholder' => /* translators: Placeholder text for postal code input. Instructs to use half-width numbers without hyphens. Note: This string is shared with phone number. */ __('半角数字 ハイフン不要', 'ktpwp')],
            /* translators: Form field label: Prefecture. */
            '都道府県' => ['type' => 'text', 'name' => 'prefecture'],
            /* translators: Form field label: City. */
            '市区町村' => ['type' => 'text', 'name' => 'city'],
            /* translators: Form field label: Address. */
            '番地' => ['type' => 'text', 'name' => 'address'],
            /* translators: Form field label: Building name. */
            '建物名' => ['type' => 'text', 'name' => 'building'],
            /* translators: Form field label: Closing day. */
            '締め日' => ['type' => 'select', 'name' => 'closing_day', 'options' => [/* translators: Closing day option: 5th of the month */ '5日', /* translators: Closing day option: 10th of the month */ '10日', /* translators: Closing day option: 15th of the month */ '15日', /* translators: Closing day option: 20th of the month */ '20日', /* translators: Closing day option: 25th of the month */ '25日', /* translators: Closing day option: End of month */ '末日', /* translators: Closing day option: None */ 'なし'], 'default' => /* translators: Default closing day option: None */ 'なし'],
            /* translators: Form field label: Payment month. */
            '支払月' => ['type' => 'select', 'name' => 'payment_month', 'options' => [/* translators: Payment month option: This month */ '今月', /* translators: Payment month option: Next month */ '翌月', /* translators: Payment month option: Month after next */ '翌々月', /* translators: Payment month option: Other */ 'その他'], 'default' => /* translators: Default payment month option: Other */ 'その他'],
            /* translators: Form field label: Payment day. */
            '支払日' => ['type' => 'select', 'name' => 'payment_day', 'options' => [/* translators: Payment day option: Same day */ '即日', /* translators: Payment day option: 5th of the month */ '5日', /* translators: Payment day option: 10th of the month */ '10日', /* translators: Payment day option: 15th of the month */ '15日', /* translators: Payment day option: 20th of the month */ '20日', /* translators: Payment day option: 25th of the month */ '25日', /* translators: Payment day option: End of month */ '末日'], 'default' => /* translators: Default payment day option: Same day */ '即日'],
            /* translators: Form field label: Payment method. */
            '支払方法' => ['type' => 'select', 'name' => 'payment_method', 'options' => [/* translators: Payment method option: Bank transfer (postpayment) */ '銀行振込（後）',/* translators: Payment method option: Bank transfer (prepayment) */'銀行振込（前）', /* translators: Payment method option: Credit card */ 'クレジットカード', /* translators: Payment method option: Cash collection */ '現金集金'], 'default' => /* translators: Default payment method option: Bank transfer (prepayment) */ '銀行振込（前）'],
            /* translators: Form field label: Tax category. */
            '税区分' => ['type' => 'select', 'name' => 'tax_category', 'options' => [/* translators: Tax category option: Tax excluded */ '外税', /* translators: Tax category option: Tax included */ '内税'], 'default' => /* translators: Default tax category option: Tax included */ '内税'],
            /* translators: Form field label: Memo. */
            'メモ' => ['type' => 'textarea', 'name' => 'memo'],
            /* translators: Form field label: Category. */
            'カテゴリー' => ['type' => 'text', 'name' => 'category', 'default' => /* translators: Default category for a client. */ '一般', 'suggest' => true],
        ];

        // Main form (for Insert or Update)
        $main_form_html = '';
        if ($action === 'istmode' || ($action === 'update' && $current_record)) {
            $form_action_url = esc_url(add_query_arg(['tab_name' => $name], $base_admin_url)); // Action is Update_Table
            $main_form_html .= '<form method="post" action="' . $form_action_url . '">';
            if ($action === 'istmode') {
                $main_form_html .= wp_nonce_field('ktp_insert_client', '_ktp_insert_client_nonce', true, false);
                $main_form_html .= '<input type="hidden" name="query_post" value="insert">';
            } elseif ($action === 'update' && $current_record) {
                $main_form_html .= wp_nonce_field('ktp_update_client_' . $data_id, '_ktp_update_client_nonce', true, false);
                $main_form_html .= '<input type="hidden" name="query_post" value="update">';
                $main_form_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
            }
            $main_form_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($name) . '">';

            $main_form_html .= '<table class="form-table">';
            foreach ($fields as $label => $field_args) {
                $main_form_html .= '<tr><th scope="row"><label for="' . esc_attr($field_args['name']) . '">' . esc_html($label) . '</label></th><td>';
                $field_name = $field_args['name'];
                $value = ($action === 'update' && $current_record && isset($current_record->$field_name)) ? $current_record->$field_name : ($field_args['default'] ?? '');
                if ($field_args['type'] === 'textarea') {
                    $main_form_html .= '<textarea name="' . esc_attr($field_name) . '" id="' . esc_attr($field_name) . '" rows="3" cols="50">' . esc_textarea($value) . '</textarea>';
                } elseif ($field_args['type'] === 'select') {
                    $main_form_html .= '<select name="' . esc_attr($field_name) . '" id="' . esc_attr($field_name) . '">';
                    foreach ($field_args['options'] as $opt_val) {
                        $main_form_html .= '<option value="' . esc_attr($opt_val) . '" ' . selected($value, $opt_val, false) . '>' . esc_html($opt_val) . '</option>';
                    }
                    $main_form_html .= '</select>';
                } else { // text, email, etc.
                    $main_form_html .= '<input type="' . esc_attr($field_args['type']) . '" name="' . esc_attr($field_name) . '" id="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '"';
                    if (isset($field_args['placeholder'])) $main_form_html .= ' placeholder="' . esc_attr($field_args['placeholder']) . '"';
                    if (isset($field_args['required']) && $field_args['required']) $main_form_html .= ' required';
                    if (isset($field_args['pattern'])) $main_form_html .= ' pattern="' . esc_attr($field_args['pattern']) . '"';
                    $main_form_html .= '>';
                }
                $main_form_html .= '</td></tr>';
            }
            $main_form_html .= '</table>';

            $main_form_html .= '<div class="button_box">';
            if ($action === 'istmode') {
                /* translators: Button text: Add. */
                $main_form_html .= '<button type="submit" class="button-primary">' . esc_html__('追加', 'ktpwp') . '</button>';
            } elseif ($action === 'update') {
                /* translators: Button text: Update. */
                $main_form_html .= '<button type="submit" class="button-primary">' . esc_html__('更新', 'ktpwp') . '</button>';
            }
            $main_form_html .= '</div>';
            $main_form_html .= '</form>';
        }

        // Delete button form (only in update mode with a valid record)
        $delete_form_html = '';
        if ($action === 'update' && $current_record) {
            $delete_form_action_url = esc_url(add_query_arg(['tab_name' => $name], $base_admin_url));
            $delete_form_html .= '<form method="post" action="' . $delete_form_action_url . '" style="display:inline; margin-left:10px;">';
            $delete_form_html .= wp_nonce_field('ktp_delete_client_' . $data_id, '_ktp_delete_client_nonce', true, false);
            $delete_form_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
            $delete_form_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($name) . '">';
            $delete_form_html .= '<input type="hidden" name="query_post" value="delete">';
            /* translators: Confirmation message when deleting a client. Warns that related order information might also be deleted. */
            $delete_form_html .= '<button type="submit" class="button-delete" onclick="return confirm(\"' . esc_js(__('この顧客情報を削除してもよろしいですか？関連する受注情報も削除される可能性があります。', 'ktpwp')) . '\");">' . /* translators: Button text: Delete. */ esc_html__('削除', 'ktpwp') . '</button>';
            $delete_form_html .= '</form>';
        }

        // Search form
        $search_form_html = '';
        if ($action === 'srcmode') {
            $search_form_action_url = esc_url(add_query_arg(['tab_name' => $name], $base_admin_url)); // Submits to Update_Table
            $search_form_html .= '<div class="search_form_box">';
            /* translators: Title for client search section. */
            $search_form_html .= '<h4>' . esc_html__('顧客検索', 'ktpwp') . '</h4>';
            $search_form_html .= '<form method="post" action="' . $search_form_action_url . '">';
            $search_form_html .= wp_nonce_field('ktp_search_client', '_ktp_search_client_nonce', true, false);
            $search_form_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($name) . '">';
            $search_form_html .= '<input type="hidden" name="query_post" value="search">';
            $search_query_val = (isset($_GET['search_query'])) ? esc_attr(wp_unslash($_GET['search_query'])) : ''; // From GET if redirected back
            
            if (isset($_SESSION['ktp_search_message_client'])) {
                $search_form_html .= '<p style="color:red;">' . esc_html($_SESSION['ktp_search_message_client']) . '</p>';
                unset($_SESSION['ktp_search_message_client']);
            }
            if (isset($_GET['insert_error'])) {
                /* translators: Error message shown when data insertion fails. */
                 $search_form_html .= '<p style="color:red;">' . esc_html__('データの追加に失敗しました。', 'ktpwp') . '</p>';
            }

            /* translators: Placeholder text for search keyword input. */
            $search_form_html .= '<input type="text" name="search_query" value="' . $search_query_val . '" placeholder="' . esc_attr__('検索キーワード', 'ktpwp') . '">';
            /* translators: Button text: Execute search. */
            $search_form_html .= '<button type="submit" class="button-primary">' . esc_html__('検索実行', 'ktpwp') . '</button>';
            $search_form_html .= '</form>';

            // Display multiple search results if they are in session
            if (isset($_SESSION['ktp_search_results_client'])) {
                $s_results = $_SESSION['ktp_search_results_client'];
                $s_query = $_SESSION['ktp_search_query_client'];
                /* translators: %s: Search query. Title for search results. */
                $search_form_html .= '<h5>' . sprintf(esc_html__('%s の検索結果:', 'ktpwp'), esc_html($s_query)) . '</h5>';
                if (!empty($s_results)) {
                    $search_form_html .= '<ul>';
                    foreach ($s_results as $row) {
                        $link = esc_url(add_query_arg(['tab_name' => $name, 'data_id' => $row->id, 'query_post' => 'update'], $base_admin_url));
                        $search_form_html .= '<li><a href="' . $link . '">ID: ' . esc_html($row->id) . ' - ' . esc_html($row->company_name) . ' (' . esc_html($row->category) . ')</a></li>';
                    }
                    $search_form_html .= '</ul>';
                } else {
                    // This case should be handled by 'no_results' normally
                    /* translators: Message shown when no search results are found. */
                    $search_form_html .= '<p>' . esc_html__('該当する結果は見つかりませんでした。', 'ktpwp') . '</p>';
                }
                unset($_SESSION['ktp_search_results_client']);
                unset($_SESSION['ktp_search_query_client']);
            }

            // Cancel Search Button (POSTs to View_Table to change view state via GET redirect)
            $cancel_search_form_action = esc_url(add_query_arg(['tab_name' => $name, 'data_id' => $data_id], $base_admin_url)); // data_id for context
            $search_form_html .= '<form method="post" action="' . $cancel_search_form_action . '" style="display:inline-block; margin-top:10px;">';
            $search_form_html .= wp_nonce_field('ktp_cancel_search_client', '_ktp_cancel_search_client_nonce', true, false);
            $search_form_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($name) . '">';
            $search_form_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
            $search_form_html .= '<input type="hidden" name="query_post" value="cancel_search">';
            /* translators: Button text: Cancel search. */
            $search_form_html .= '<button type="submit" class="button">' . esc_html__('検索解除', 'ktpwp') . '</button>';
            $search_form_html .= '</form>';
            $search_form_html .= '</div>'; // close search_form_box
        }

        // Mode switching buttons (POST to View_Table, then redirect to GET)
        $mode_buttons_html = '<div class="mode_button_box">';
        $add_mode_form_action = esc_url(add_query_arg(['tab_name' => $name, 'data_id' => $data_id], $base_admin_url));
        $mode_buttons_html .= '<form method="post" action="' . $add_mode_form_action . '" style="display:inline-block; margin-right:10px;">';
        $mode_buttons_html .= wp_nonce_field('ktp_add_mode_client', '_ktp_add_mode_client_nonce', true, false);
        $mode_buttons_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($name) . '">';
        $mode_buttons_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">'; // Context for return if needed
        $mode_buttons_html .= '<input type="hidden" name="query_post" value="istmode">';
        /* translators: Button text: Add mode. Switches the form to client creation mode. */
        $mode_buttons_html .= '<button type="submit" class="button">' . esc_html__('追加モード', 'ktpwp') . '</button>';
        $mode_buttons_html .= '</form>';

        $search_mode_form_action = esc_url(add_query_arg(['tab_name' => $name, 'data_id' => $data_id], $base_admin_url));
        $mode_buttons_html .= '<form method="post" action="' . $search_mode_form_action . '" style="display:inline-block;">';
        $mode_buttons_html .= wp_nonce_field('ktp_search_mode_client', '_ktp_search_mode_client_nonce', true, false);
        $mode_buttons_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($name) . '">';
        $mode_buttons_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
        $mode_buttons_html .= '<input type="hidden" name="query_post" value="srcmode">';
        /* translators: Button text: Search mode. Switches the view to client search mode. */
        $mode_buttons_html .= '<button type="submit" class="button">' . esc_html__('検索モード', 'ktpwp') . '</button>';
        $mode_buttons_html .= '</form>';
        $mode_buttons_html .= '</div>';

        // Assemble the output
        $output = '<div class="wrap ktpwp-client-tab">'; // Wrap everything for styling/admin page structure
        /* translators: Page title for client management. %s is the tab name (e.g., client). */
        $output .= '<h1>' . esc_html__('顧客管理', 'ktpwp') . ' (' . esc_html($name) . ')</h1>';
        
        $output .= $data_list_html; // The list of clients/orders

        $output .= '<div class="data_detail_box">';
        $detail_title = '';
        if ($action === 'istmode') {
            /* translators: Section title for adding a new client. */
            $detail_title = esc_html__('■ 顧客情報追加', 'ktpwp');
        } elseif ($action === 'update' && $current_record) {
            /* translators: Section title for client details when data is available. Followed by (ID: %s). */
            $detail_title = esc_html__('■ 顧客詳細', 'ktpwp') . ' (ID: ' . esc_html($data_id) . ')';
        } elseif ($action === 'srcmode') {
            /* translators: Section title for client search. */
            $detail_title = esc_html__('■ 顧客検索', 'ktpwp');
        } elseif ($no_data_to_display) {
            /* translators: Section title for client details when no data is available to display. */
            $detail_title = esc_html__('■ 顧客詳細', 'ktpwp');
        }

        $output .= '<div class="data_detail_title_area">';
        if (!empty($detail_title)) {
            $output .= '<div class="data_detail_title">' . $detail_title . '</div>';
        }
        $output .= $mode_buttons_html;
        $output .= '</div>'; // data_detail_title_area

        if ($action === 'srcmode') {
            $output .= $search_form_html;
        } elseif ($action === 'istmode') {
            if (isset($_GET['insert_error'])) {
                /* translators: Error message shown when data insertion fails, advising to check required fields. */
                 $output .= '<div class="error"><p>' . esc_html__('データの追加に失敗しました。必須項目を確認してください。', 'ktpwp') . '</p></div>';
            }
            $output .= $main_form_html; // Add form
        } elseif ($action === 'update') {
            if ($current_record) {
                $output .= $main_form_html; // Update form
                $output .= $delete_form_html; // Delete button form
            } elseif ($no_data_to_display) {
                /* translators: Message shown when there is no client data to display, prompting to add a new client. */
                 $output .= '<p>' . esc_html__('表示する顧客データがありません。追加モードで新しい顧客を登録してください。', 'ktpwp') . '</p>';
            }
        }
        $output .= '</div>'; // data_detail_box

        // Order history and back buttons (logic from original, adapt URLs)
        if ($action === 'update' && $current_record) {
            $order_history_url = add_query_arg(['tab_name' => $name, 'data_id' => $data_id, 'view_mode' => 'order_history'], $base_admin_url);
            $output .= '<div class="order_history_button_box" style="margin-top:15px; text-align:right;">';
            /* translators: Button text: View this client's order history. */
            $output .= '<a href="' . esc_url($order_history_url) . '" class="button">' . esc_html__('この顧客の注文履歴を見る', 'ktpwp') . '</a>';
            $output .= '</div>';
        } elseif ($view_mode === 'order_history') {
            // This part of logic for displaying order history list itself needs to be integrated into $data_list_html generation
            // Button to go back to customer details:
            $customer_detail_url = add_query_arg(['tab_name' => $name, 'data_id' => $data_id, 'query_post' => 'update'], $base_admin_url);
            // remove view_mode if it's in $base_admin_url by mistake, or ensure $base_admin_url is clean
            $customer_detail_url = remove_query_arg('view_mode', $customer_detail_url);
            $output .= '<div class="back_to_customer_list_button_box" style="margin-top:15px; text-align:right;">';
            /* translators: Button text: Return to client details. */
            $output .= '<a href="' . esc_url($customer_detail_url) . '" class="button">' . esc_html__('顧客詳細に戻る', 'ktpwp') . '</a>';
            $output .= '</div>';
        }

        $output .= '</div>'; // close wrap
        echo $output;
    }

  } // End of Kntan_Client_Class
}