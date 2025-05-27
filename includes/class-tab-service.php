<?php
if (!defined('ABSPATH')) exit;
require_once 'class-image_processor.php';

if (!class_exists('Kntan_Service_Class')) {
class Kntan_Service_Class {

    public function __construct($tab_name = '') {

    }
    
    
    // -----------------------------
    // テーブル作成
    // -----------------------------
    

    // クッキーの設定
    function Set_Cookie($name) {
        $cookie_name = 'ktp_' . $name . '_id';
        if (isset($_COOKIE[$cookie_name])) {
            $query_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
        } elseif (isset($_GET['data_id'])) {
            $query_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
        } else {
            $query_id = 1;
        }
    }

    function Create_Table($tab_name) {
        global $wpdb;
        $my_table_version = '1.0.0';
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $charset_collate = $wpdb->get_charset_collate();

        // カラム名リスト
        $columns = [
            "id" => "MEDIUMINT(9) NOT NULL AUTO_INCREMENT",
            "time" => "BIGINT(11) DEFAULT '0' NOT NULL",
            "service_name" => "TINYTEXT",
            "image_url" => "VARCHAR(255)",
            "memo" => "TEXT",
            "search_field" => "TEXT",
            "frequency" => "INT NOT NULL DEFAULT 0",
            "category" => "VARCHAR(100) NOT NULL DEFAULT '一般'"
        ];

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // CREATE TABLE時はUNIQUE KEYも含めてOK
            $columns_sql = [];
            foreach ($columns as $col => $def) {
                $columns_sql[] = "$col $def";
            }
            $columns_sql[] = "UNIQUE KEY id (id)";
            $sql = "CREATE TABLE $table_name (" . implode(", ", $columns_sql) . ") $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            add_option('ktp_' . $tab_name . '_table_version', $my_table_version);
        } else {
            // 既存カラム取得
            $existing_columns = $wpdb->get_col("DESCRIBE $table_name", 0);
            // カラム追加
            foreach ($columns as $col => $def) {
                if (!in_array($col, $existing_columns)) {
                    $wpdb->query("ALTER TABLE $table_name ADD $col $def");
                }
            }
            // UNIQUE KEY追加（idにUNIQUE INDEXがなければ追加）
            $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'id'");
            if (empty($indexes)) {
                $wpdb->query("ALTER TABLE $table_name ADD UNIQUE (id)");
            }
            update_option('ktp_' . $tab_name . '_table_version', $my_table_version);
        }
    }

    // -----------------------------
    // テーブルの操作（更新・追加・削除・検索）
    // -----------------------------

    function Update_Table( $tab_name) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;

        // テーブル名にロックをかける
        $wpdb->query("LOCK TABLES {$table_name} WRITE;");

        // POSTデーター受信
        $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
        $query_post = isset($_POST['query_post']) ? sanitize_text_field($_POST['query_post']) : '';
        $service_name = isset($_POST['service_name']) ? sanitize_text_field($_POST['service_name']) : '';
        $memo = isset($_POST['memo']) ? sanitize_textarea_field($_POST['memo']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

        // Nonce検証を各アクションの最初で行うように変更
        // CSRF対策: POST時のみnonceチェック - これは削除し、各アクションで個別に行う
        // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //     if (!isset($_POST['_ktp_service_nonce']) || !wp_verify_nonce($_POST['_ktp_service_nonce'], 'ktp_service_action')) {
        //         // $wpdb->query("UNLOCK TABLES;"); // DEBUG: 一時的にコメントアウト
        //         if (defined('WP_DEBUG') && WP_DEBUG) {
        //             error_log('CSRF/nonce error: nonce=' . (isset($_POST['_ktp_service_nonce']) ? $_POST['_ktp_service_nonce'] : 'NOT SET'));
        //             error_log('POST内容: ' . print_r($_POST, true));
        //         }
        //         wp_die(__('不正なリクエストです。ページを再読み込みしてください。', 'ktpwp'));
        //     }
        // }


        // search_fieldの値を設定
        $search_field_value = implode(', ', [
            current_time('mysql'),
            $service_name,
            $memo,
            $category
        ]);

        if ($data_id === 0) {
            $last_id_query = "SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1";
            $last_id_result = $wpdb->get_row($last_id_query);
            if ($last_id_result) {
                $data_id = $last_id_result->id;
            }
        }

        // 削除
        if ($query_post == 'delete' && $data_id > 0) {
            // Nonce check for delete
            if (!isset($_POST['_ktp_delete_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_ktp_delete_nonce']), 'ktp_delete_service_' . $data_id)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nonce verification failed for delete. Action: ktp_delete_service_' . $data_id . ' Nonce field: ' . (isset($_POST['_ktp_delete_nonce']) ? $_POST['_ktp_delete_nonce'] : 'not set'));
                }
                $wpdb->query("UNLOCK TABLES;");
                wp_die(esc_html__('セキュリティチェックに失敗しました。操作を続行できません。(delete)', 'ktpwp'));
            }

            $delete_result = $wpdb->delete($table_name, array('id' => $data_id), array('%d'));

            if ($delete_result === false) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Delete error on table ' . $table_name . ' for ID ' . $data_id . ': ' . $wpdb->last_error); }
                $wpdb->query("UNLOCK TABLES;");
                $error_message_detail = (defined('WP_DEBUG') && WP_DEBUG && $wpdb->last_error) ? ' (' . esc_js($wpdb->last_error) . ')' : '';

                echo "<script>alert('" . esc_js(__( 'データの削除に失敗しました。', 'ktpwp')) . $error_message_detail . "'); if (window.history.length > 1) { window.history.back(); } else { window.location.href = document.referrer || window.location.pathname; }</script>";
                exit;
            } else {
                $wpdb->query("UNLOCK TABLES;"); // Unlock after successful delete

                // 現在のURLからアクション関連のパラメータを削除し、成功メッセージパラメータを追加してリダイレクトURLを構築
                $redirect_url = remove_query_arg( array( 'query_post', 'data_id', '_ktp_delete_nonce', 'action', '_wpnonce' ), wp_get_referer() ?: $_SERVER['REQUEST_URI'] );
                $redirect_url = add_query_arg( array( 'message' => '1', 'tab_name' => $tab_name ), $redirect_url ); // message=1 は削除成功を示す

                echo "<script>
                    alert('" . esc_js(__('商品が削除されました。', 'ktpwp')) . "');
                    window.location.href = '" . esc_url_raw($redirect_url) . "';
                </script>";
                exit;
            }
        }

        // 更新
        elseif ($query_post == 'update' && $data_id > 0) {
            // Nonce check for update
            if (!isset($_POST['_ktp_update_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_ktp_update_nonce']), 'ktp_update_service_' . $data_id)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nonce verification failed for update. Action: ktp_update_service_' . $data_id . ' Nonce field: ' . (isset($_POST['_ktp_update_nonce']) ? $_POST['_ktp_update_nonce'] : 'not set'));
                }
                $wpdb->query("UNLOCK TABLES;");
                wp_die(esc_html__('セキュリティチェックに失敗しました。操作を続行できません。(update)', 'ktpwp'));
            }

            // データの存在チェック
            $existing_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $data_id));
            if (!$existing_data) {
                $wpdb->query("UNLOCK TABLES;");
                wp_die(__( '指定されたデータが見つかりません。', 'ktpwp'));
            }

            // データ更新処理
            $update_data = array(
                'service_name' => $service_name,
                'memo' => $memo,
                'category' => $category,
                'search_field' => $search_field_value
            );

            $wpdb->update(
                $table_name,
                $update_data,
                array('id' => $data_id),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );

            // ロックを解除し、処理を終了
            $wpdb->query("UNLOCK TABLES;");

            // 更新後のリダイレクト
            $action = 'update';
            global $wp;
            $current_page_id = get_queried_object_id();
            $base_page_url = home_url( $wp->request );
            $url_params = [
                'page_id' => $current_page_id,
                'tab_name' => $tab_name,
                'data_id' => $data_id,
                'message' => 'updated' // 更新成功のメッセージパラメータ
            ];
            $url = add_query_arg($url_params, $base_page_url);

            echo '<script>
                alert("' . esc_js(esc_html__('更新しました。', 'ktpwp')) . '");
                if (window.history.pushState) {
                    var newUrl = "' . esc_js($url) . '";
                    window.history.pushState({path: newUrl}, "", newUrl);
                    location.reload();
                } else {
                    window.location.href = "' . esc_js($url) . '"; // フォールバック
                }
            </script>';
            exit;
        }

        // 検索
        elseif( $query_post == 'search' ){
            // Nonce check for search
            if (!isset($_POST['_ktp_search_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_ktp_search_nonce']), 'ktp_search_service')) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nonce verification failed for search. Action: ktp_search_service Nonce field: ' . (isset($_POST['_ktp_search_nonce']) ? $_POST['_ktp_search_nonce'] : 'not set'));
                }
                $wpdb->query("UNLOCK TABLES;");
                wp_die(esc_html__('セキュリティチェックに失敗しました。操作を続行できません。(search)', 'ktpwp'));
            }

            // SQLクエリを準備（search_fieldを検索対象にする）
            $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE search_field LIKE %s", '%' . $wpdb->esc_like($search_query) . '%'));

            // 検索結果が1件の場合
            if (count($results) == 1) {
                $result = $results[0];
                $data_id = $result->id;
                $action = 'update';
                global $wp;
                $current_page_id = get_queried_object_id();
                $base_page_url = home_url( $wp->request );
                $url_params = [
                    'page_id' => $current_page_id,
                    'tab_name' => $tab_name,
                    'data_id' => $data_id,
                    'query_post' => $action
                ];
                $url = add_query_arg($url_params, $base_page_url);

                $cookie_name = 'ktp_' . $tab_name . '_id';
                $cookie_path = defined('COOKIEPATH') ? COOKIEPATH : '/';
                $cookie_domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
                setcookie($cookie_name, $data_id, time() + (86400 * 30), $cookie_path, $cookie_domain);

                echo '<script>
                    (function() {
                        var targetUrl = \\\'' . esc_js($url) . '\\\';
                        window.history.pushState({}, "", targetUrl);
                        
                        var successMessageText = \\\'' . esc_js(__('検索結果が見つかり、フォームが更新されました。', 'ktpwp')) . '\\\';

                        var xhr = new XMLHttpRequest();
                        var requestUrl = targetUrl;
                        requestUrl += (requestUrl.includes("?") ? "&" : "?") + "cache_bust=" + new Date().getTime() + "&action=get_tab_content";

                        xhr.open("GET", requestUrl, true);
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4) {
                                if (xhr.status === 200) {
                                    var parser = new DOMParser();
                                    var doc = parser.parseFromString(xhr.responseText, "text/html");
                                    var newTabContent = doc.querySelector(".tabs");
                                    var currentTabContainer = document.querySelector(".tabs");

                                    if (newTabContent && currentTabContainer) {
                                        var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
                                        
                                        currentTabContainer.innerHTML = newTabContent.innerHTML;
                                        window.scrollTo(0, scrollTop); // スクロール位置を復元
                                        
                                        var messageDiv = document.createElement("div");
                                        messageDiv.className = "notice notice-success is-dismissible ktpwp-notice";
                                        messageDiv.style.cssText = "padding:10px; background-color:#d4edda; color:#155724; margin-bottom:15px; border-radius:3px; border:1px solid #c3e6cb;";
                                        messageDiv.innerHTML = "<p>" + successMessageText + "</p>";
                                        
                                        var formElement = currentTabContainer.querySelector("form[name=\'service_form\']");
                                        var insertTarget = formElement ? formElement : (currentTabContainer.querySelector(".data_contents") || currentTabContainer);
                                        
                                        if (insertTarget.parentNode) {
                                            insertTarget.parentNode.insertBefore(messageDiv, insertTarget);
                                        } else {
                                            currentTabContainer.insertBefore(messageDiv, currentTabContainer.firstChild);
                                        }

                                        setTimeout(function() {
                                            if (messageDiv.parentNode) {
                                                messageDiv.parentNode.removeChild(messageDiv);
                                            }
                                        }, 3000);
                                        
                                        var firstInput = currentTabContainer.querySelector(\'input:not([type="hidden"]):not([type="submit"]):not([disabled]), select:not([disabled]), textarea:not([disabled])\');
                                        if (firstInput && typeof firstInput.focus === "function") {
                                            firstInput.focus();
                                        }
                                        if (typeof refreshServiceTab === "function") {
                                            // refreshServiceTab(); 
                                        }

                                    } else {
                                        // console.error("Could not find .tabs element for duplication update. Fallback to reload.");
                                        location.reload(); // 安全策としてリロード
                                    }
                                } else {
                                    // console.error("XHR request failed for duplication update. Status: " + xhr.status + ". Fallback to reload.");
                                    location.reload(); // 安全策としてリロード
                                }
                            }
                        };
                        xhr.send();
                    })();
                </script>';
                $wpdb->query("UNLOCK TABLES;"); // Unlock before exiting
                exit;
            }
            // 検索結果が複数件の場合
            elseif (count($results) > 1) {
                $search_results_html = "<div class='data_contents'><div class='search_list_box'><div class='data_list_title'>■ " . esc_html__('検索結果が複数あります！', 'ktpwp') . "</div><ul>";
                foreach ($results as $row) {
                    $id = esc_html($row->id);
                    $service_name = esc_html($row->service_name);
                    $category = esc_html($row->category);
                    $link_url_params = array('tab_name' => $tab_name, 'data_id' => $id, 'query_post' => 'update');
                    if ($current_page_id) {
                        $link_url_params['page_id'] = $current_page_id;
                    }
                    $link_url = add_query_arg($link_url_params, $base_page_url); // $base_page_url を使用
                    /* translators: Search result item. 1: ID, 2: Service name, 3: Category. */
                    $search_results_html .= "<li style='text-align:left; width:100%;'><a href='" . esc_url($link_url) . "' style='text-align:left;'>" . sprintf(esc_html__('ID：%1$s 商品名：%2$s カテゴリー：%3$s', 'ktpwp'), $id, $service_name, $category) . "</a></li>";
                }
                $search_results_html .= "</ul></div></div>";
                $search_results_html_js = json_encode($search_results_html);

                echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    var searchResultsHtml = $search_results_html_js;
                    var popup = document.createElement('div');
                    popup.innerHTML = searchResultsHtml;
                    popup.style.position = 'fixed';
                    popup.style.top = '50%';
                    popup.style.left = '50%';
                    popup.style.transform = 'translate(-50%, -50%)';
                    popup.style.backgroundColor = '#fff';
                    popup.style.padding = '20px';
                    popup.style.zIndex = '1000';
                    popup.style.width = '80%';
                    popup.style.maxWidth = '600px';
                    popup.style.border = '1px solid #ccc';
                    popup.style.borderRadius = '5px';
                    popup.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
                    
                    var closeButton = document.createElement('button');
                    closeButton.textContent = '" . esc_js(__('閉じる', 'ktpwp')) . "';
                    closeButton.style.fontSize = '0.8em';
                    closeButton.style.color = 'black';
                    closeButton.style.display = 'block';
                    closeButton.style.margin = '10px auto 0';
                    closeButton.style.padding = '10px';
                    closeButton.style.backgroundColor = '#cdcccc';
                    closeButton.style.borderRadius = '5px';
                    closeButton.style.borderColor = '#999';
                    closeButton.onclick = function() {
                        document.body.removeChild(popup);
                    };
                    popup.appendChild(closeButton);
                    document.body.appendChild(popup);
                });
                </script>";
                $wpdb->query("UNLOCK TABLES;"); // Unlock before exiting
                exit;
            } else { // 検索結果が0件の場合
                echo "<script>alert('" . esc_js(__('検索条件に一致する商品が見つかりませんでした。', 'ktpwp')) . "'); if (window.history.length > 1) { window.history.back(); } else { window.location.href = document.referrer || window.location.pathname; }</script>";
                $wpdb->query("UNLOCK TABLES;");
                exit;
            }
        } // End of elseif( $query_post == 'search' )

        // 商品追加 (新規)
        elseif ($query_post == 'insert') {
            // Nonce check for insert
            // if (!isset($_POST['_ktp_service_nonce']) || !wp_verify_nonce($_POST['_ktp_service_nonce'], 'ktp_service_action')) { // Old generic nonce
            if (!isset($_POST['_ktp_insert_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_ktp_insert_nonce']), 'ktp_insert_service')) { // New specific nonce
                if (defined('WP_DEBUG') && WP_DEBUG) {
                     error_log('Nonce verification failed for insert. Action: ktp_insert_service Nonce field: ' . (isset($_POST['_ktp_insert_nonce']) ? $_POST['_ktp_insert_nonce'] : 'not set'));
                }
                $wpdb->query("UNLOCK TABLES;");
                wp_die(esc_html__('セキュリティチェックに失敗しました。操作を続行できません。(insert)', 'ktpwp'));
            }

            $current_time = current_time('mysql');
            // $service_name, $memo, $category are already sanitized from the top of the function.
            $search_field_content = implode(', ', [
                $current_time,
                $service_name,
                $memo,
                $category
            ]);

            $default_image_url = plugin_dir_url(dirname(dirname(__FILE__))) . 'images/default/no-image-icon.jpg';

            $insert_data = array(
                'time' => $current_time,
                'service_name' => $service_name,
                'memo' => $memo,
                'category' => $category,
                'search_field' => $search_field_content,
                'frequency' => 0,
                'image_url' => $default_image_url 
            );

            $insert_result = $wpdb->insert($table_name, $insert_data);

            if($insert_result === false) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Insert error: ' . $wpdb->last_error . ' Data: ' . print_r($insert_data, true)); }
                $wpdb->query("UNLOCK TABLES;");
                echo "<script>alert('" . esc_js(__('データの追加に失敗しました。詳細: ', 'ktpwp') . $wpdb->last_error) . "'); if (window.history.length > 1) { window.history.back(); } else { window.location.href = document.referrer || window.location.pathname; }</script>";
                exit;
            } else {
                $new_data_id = $wpdb->insert_id;
                $wpdb->query("UNLOCK TABLES;");

                $action = 'update'; 
                
                $cookie_name = 'ktp_' . $tab_name . '_id';
                $cookie_path = defined('COOKIEPATH') ? COOKIEPATH : '/';
                $cookie_domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
                setcookie($cookie_name, $new_data_id, time() + (86400 * 30), $cookie_path, $cookie_domain);

                global $wp;
                $current_page_id = get_queried_object_id();
                $base_page_url = '';
                if ($current_page_id) {
                    $base_page_url = get_permalink($current_page_id);
                }
                if (!$base_page_url) { // Fallback if get_permalink fails or no page_id
                     $base_page_url = home_url(add_query_arg(array(), $wp->request));
                }
                
                $url_params = [
                    'tab_name' => $tab_name,
                    'data_id' => $new_data_id,
                    'query_post' => $action,
                    'message' => 'inserted'
                ];
                if ($current_page_id) {
                    $url_params['page_id'] = $current_page_id;
                }
                $url = add_query_arg($url_params, $base_page_url);
                
                echo '<script>
                    if (window.history.pushState) {
                        window.history.pushState({}, "", "' . esc_js($url) . '");
                        location.reload();
                    } else {
                        window.location.href = "' . esc_js($url) . '";
                    }
                </script>';
                exit;
            }
        } // End of elseif ($query_post == 'insert')
        
        // 複製
        elseif( $query_post == 'duplication' ) {
            // Nonce check for duplication
            if (!isset($_POST['_ktp_duplicate_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_ktp_duplicate_nonce']), 'ktp_duplicate_service_' . $data_id)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nonce verification failed for duplication. Action: ktp_duplicate_service_' . $data_id . ' Nonce field: ' . (isset($_POST['_ktp_duplicate_nonce']) ? $_POST['_ktp_duplicate_nonce'] : 'not set'));
                }
                $wpdb->query("UNLOCK TABLES;");
                wp_die(esc_html__('セキュリティチェックに失敗しました。操作を続行できません。(duplication)', 'ktpwp'));
            }

            // データのIDを取得
            // $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0; // Already defined

            // データを取得（SQLインジェクション対策でprepareを使用）
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $data_id), ARRAY_A);
    
            // 商品名の末尾に連番（#2, #3, ...）を付与
            $original_name = $data['service_name'];
            // すでに #数字 が付いていれば元の名前を抽出
            if (preg_match('/^(.*)#(\\d+)$/', $original_name, $matches)) {
                $base_name = $matches[1];
            } else {
                $base_name = $original_name;
            }
            // 同じベース名の件数をカウント
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE service_name REGEXP %s",
                '^' . preg_quote($base_name, '/') . '#[0-9]+$'
            ));
            $next_num = $count ? ((int)$count + 2) : 2; // 2から開始
            $data['service_name'] = $base_name . '#' . $next_num;
    
            // IDを削除
            unset($data['id']);
    
            // 頻度を0に設定
            $data['frequency'] = 0;
    
            // search_fieldの値を更新
            $data['search_field'] = implode(', ', [
                $data['time'],
                $data['service_name'],
                $data['memo'],
                $data['category']
            ]);
    
            // データを挿入
            $insert_result = $wpdb->insert($table_name, $data);
            if($insert_result === false) {
                // エラーログに挿入エラーを記録
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Duplication error: ' . $wpdb->last_error); }
                $wpdb->query("UNLOCK TABLES;"); // Ensure unlock in error case
                echo "<script>alert('" . esc_js(__( '複製に失敗しました。', 'ktpwp')) . "'); if (window.history.length > 1) { window.history.back(); } else { window.location.href = document.referrer || window.location.pathname; }</script>";
                exit; // Added exit on error
            } else {
                // 挿入成功後の処理
                $new_data_id = $wpdb->insert_id;
                $wpdb->query("UNLOCK TABLES;");

                // 追加後に更新モードにする
                $action = 'update';

                global $wp;
                $current_page_id = get_queried_object_id();
                $base_page_url = get_permalink($current_page_id);

                if (!$base_page_url && !empty($wp->request)) {
                    $base_page_url = home_url( $wp->request );
                } elseif (!$base_page_url) {
                    // $base_page_url = site_url(add_query_arg(array())); 
                    $base_page_url = home_url(add_query_arg(array(), $wp->request)); 
                }
                
                $url_params = array(
                    'tab_name' => $tab_name,
                    'data_id' => $new_data_id,
                    'query_post' => $action
                );
                if ($current_page_id) {
                    $url_params['page_id'] = $current_page_id;
                }
                $url = add_query_arg($url_params, $base_page_url);

                $cookie_name = 'ktp_' . $tab_name . '_id';
                $cookie_path = defined('COOKIEPATH') ? COOKIEPATH : '/';
                $cookie_domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
                setcookie($cookie_name, $new_data_id, time() + (86400 * 30), $cookie_path, $cookie_domain);
                
                echo '<script>
                    (function() {
                        var targetUrl = \'' . esc_js($url) . '\';
                        window.history.pushState({}, "", targetUrl);
                        
                        var successMessageText = \'' . esc_js(__('商品が複製され、フォームが更新されました。', 'ktpwp')) . '\';
                        
                        var xhr = new XMLHttpRequest();
                        var requestUrl = targetUrl;
                        requestUrl += (requestUrl.includes("?") ? "&" : "?") + "cache_bust=" + new Date().getTime() + "&action=get_tab_content";

                        xhr.open("GET", requestUrl, true);
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4) {
                                if (xhr.status === 200) {
                                    var parser = new DOMParser();
                                    var doc = parser.parseFromString(xhr.responseText, "text/html");
                                    var newTabContent = doc.querySelector(".tabs");
                                    var currentTabContainer = document.querySelector(".tabs");

                                    if (newTabContent && currentTabContainer) {
                                        var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
                                        
                                        currentTabContainer.innerHTML = newTabContent.innerHTML;
                                        window.scrollTo(0, scrollTop); 
                                        
                                        var messageDiv = document.createElement("div");
                                        messageDiv.className = "notice notice-success is-dismissible ktpwp-notice";
                                        messageDiv.style.cssText = "padding:10px; background-color:#d4edda; color:#155724; margin-bottom:15px; border-radius:3px; border:1px solid #c3e6cb;";
                                        messageDiv.innerHTML = "<p>" + successMessageText + "</p>";
                                        
                                        var formElement = currentTabContainer.querySelector("form[name=\'service_form\']");
                                        var insertTarget = formElement ? formElement : (currentTabContainer.querySelector(".data_contents") || currentTabContainer);
                                        
                                        if (insertTarget.parentNode) {
                                            insertTarget.parentNode.insertBefore(messageDiv, insertTarget);
                                        } else {
                                            currentTabContainer.insertBefore(messageDiv, currentTabContainer.firstChild);
                                        }

                                        setTimeout(function() {
                                            if (messageDiv.parentNode) {
                                                messageDiv.parentNode.removeChild(messageDiv);
                                            }
                                        }, 3000);
                                        
                                        var firstInput = currentTabContainer.querySelector(\'input:not([type="hidden"]):not([type="submit"]):not([disabled]), select:not([disabled]), textarea:not([disabled])\');
                                        if (firstInput && typeof firstInput.focus === "function") {
                                            firstInput.focus();
                                        }
                                        // リスト更新のトリガー (もしあれば)
                                        if (typeof refreshServiceTab === "function") {
                                            // refreshServiceTab(); // これはリスト部分のみを更新する関数であるべき
                                        }

                                    } else {
                                        // console.error("Could not find .tabs element for duplication update. Fallback to reload.");
                                        location.reload(); // 安全策としてリロード
                                    }
                                } else {
                                    // console.error("XHR request failed for duplication update. Status: " + xhr.status + ". Fallback to reload.");
                                    location.reload(); // 安全策としてリロード
                                }
                            }
                        };
                        xhr.send();
                    })();
                </script>';
                exit;
            }
        }
        
        // 
        // 商品画像処理
        //        // 画像をアップロード
        elseif ($query_post == 'upload_image') {
            // Nonce check for upload_image
            if (!isset($_POST['_ktp_upload_image_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_ktp_upload_image_nonce']), 'ktp_upload_image_service_' . $data_id)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nonce verification failed for upload_image. Action: ktp_upload_image_service_' . $data_id . ' Nonce field: ' . (isset($_POST['_ktp_upload_image_nonce']) ? $_POST['_ktp_upload_image_nonce'] : 'not set'));
                }
                $wpdb->query("UNLOCK TABLES;");
                wp_die(esc_html__('セキュリティチェックに失敗しました。操作を続行できません。(upload_image)', 'ktpwp'));
            }

            // 先にImage_Processorクラスが存在するか確認
            if (!class_exists('Image_Processor')) {
                require_once(dirname(__FILE__) . '/class-image_processor.php');
            }
            
            // 画像URLを取得
            $image_processor = new Image_Processor();
            $default_image_url = plugin_dir_url(dirname(__FILE__)) . 'images/default/no-image-icon.jpg';
            
            // デフォルト画像のパスが正しいか確認
            $default_image_path = dirname(__FILE__) . '/../images/default/no-image-icon.jpg';
            if (!file_exists($default_image_path)) {
                // デフォルト画像が存在しない場合、エラーログに記録
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Default image not found: ' . $default_image_path); }
            }
            
            $image_url = $image_processor->handle_image($tab_name, $data_id, $default_image_url);

            $wpdb->update(
                $table_name,
                array(
                    'image_url' => $image_url
                ),
                array(
                    'id' => $data_id
                ),
                array(
                    '%s'
                ),
                array(
                    '%d'
                )
            );            // echo $image_url;
            // exit;
            $wpdb->query("UNLOCK TABLES;"); // Unlock before redirect
            // リダイレクト（class-tab-client.phpの方針に準拠）
            global $wp;
            $current_page_id = get_queried_object_id();
            $base_page_url = home_url( $wp->request );
            $url_params = [
                'page_id' => $current_page_id,
                'tab_name' => $tab_name,
                'data_id' => $data_id
            ];
            $url = add_query_arg($url_params, $base_page_url);
            header('Location: ' . esc_url_raw($url));
            exit;
        }        // 画像削除：デフォルト画像に戻す
        elseif ($query_post == 'delete_image') {
            // Nonce check for delete_image
            if (!isset($_POST['_ktp_delete_image_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_ktp_delete_image_nonce']), 'ktp_delete_image_service_' . $data_id)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Nonce verification failed for delete_image. Action: ktp_delete_image_service_' . $data_id . ' Nonce field: ' . (isset($_POST['_ktp_delete_image_nonce']) ? $_POST['_ktp_delete_image_nonce'] : 'not set'));
                }
                $wpdb->query("UNLOCK TABLES;");
                wp_die(esc_html__('セキュリティチェックに失敗しました。操作を続行できません。(delete_image)', 'ktpwp'));
            }

            // デフォルト画像のURLを設定
            $default_image_url = plugin_dir_url(dirname(__FILE__)) . 'images/default/no-image-icon.jpg';
            
            // 既存の画像ファイルを削除する処理を追加
            $upload_dir = dirname(__FILE__) . '/../images/default/upload/';
            $file_path = $upload_dir . $data_id . '.jpeg';
            
            // ファイルが存在する場合は削除する
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            $wpdb->update(
                $table_name,
                array(
                    'image_url' => $default_image_url
                ),
                array(
                    'id' => $data_id
                ),
                array(
                    '%s'
                ),
                array(
                    '%d'
                )
            );
            $wpdb->query("UNLOCK TABLES;"); // Unlock before redirect
            // リダイレクト（class-tab-client.phpの方針に準拠）
            global $wp;
            $current_page_id = get_queried_object_id();
            $base_page_url = home_url( $wp->request );
            $url_params = [
                'page_id' => $current_page_id,
                'tab_name' => $tab_name,
                'data_id' => $data_id
            ];
            $url = add_query_arg($url_params, $base_page_url);
            header('Location: ' . esc_url_raw($url));
            exit;
        }

        // どの処理にも当てはまらない場合はロック解除
        else {
            // ロックを解除する
            $wpdb->query("UNLOCK TABLES;");
        }

    }


    // -----------------------------
    // テーブルの表示
    // -----------------------------

    function View_Table( $name ) { // $name が信頼できる内部値であると仮定
        global $wpdb;
        $content = ''; // Initialize content string
        $table_name_full = $wpdb->prefix . 'ktp_' . $name;

        // Define fields for this specific tab (service)
        // This should ideally be a class property or passed to the method
        $this->fields_definition = [
            // Translators: Label for the service name input field.\r\n            __('商品名', 'ktpwp') => ['name' => 'service_name', 'type' => 'text', 'required' => true, 'placeholder' => __('商品名を入力', 'ktpwp')],
            // Translators: Label for the category select field.\r\n            __('カテゴリー', 'ktpwp') => ['name' => 'category', 'type' => 'select', 'options' => $this->get_category_options($table_name_full), 'default' => __('カテゴリーを選択', 'ktpwp'), 'required' => true],
            // Translators: Label for the memo textarea field.\r\n            __('メモ', 'ktpwp') => ['name' => 'memo', 'type' => 'textarea', 'placeholder' => __('メモを入力', 'ktpwp')]
        ];

        // Determine current action and sanitize
        $action = 'update'; // Default action
        $is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');

        if ($is_post_request && isset($_POST['query_post'])) {
            $action = sanitize_text_field(wp_unslash($_POST['query_post']));
            // Nonce verification for view mode changes
            $this->verify_view_mode_nonce($action);
        } elseif (!$is_post_request && isset($_GET['action'])) {
            $action = sanitize_text_field(wp_unslash($_GET['action']));
            // Consider nonce for GET actions if they change state
        }
        
        // Get data_id from cookie or GET parameter
        $data_id = $this->get_current_data_id($name);

        // Handle different actions
        if ($action === 'istmode') {
            $content .= $this->render_insert_mode_form($name);
        } elseif ($action === 'srcmode') {
            $content .= $this->render_search_mode_form($name, $data_id);
        } else { // Default to 'update' mode (displaying existing data or empty form if no data_id)
            $content .= $this->render_update_mode_form($name, $data_id);
        }

        // Common elements: Data list and navigation buttons
        $content .= $this->render_data_list($name, $data_id);
        $content .= $this->render_navigation_buttons($name, $data_id, $action);

        // Add messages if any (e.g., after update, delete)
        if (isset($_GET['message'])) {
            $message_type = sanitize_text_field($_GET['message']);
            $message_text = '';
            if ($message_type === 'updated') {
                // Translators: Message displayed after a successful update.\r\n                $message_text = __('更新しました。', 'ktpwp');
            } elseif ($message_type === 'inserted') {
                // Translators: Message displayed after a successful insertion.\r\n                $message_text = __('追加しました。', 'ktpwp');
            } elseif ($message_type === '1') { // Corresponds to delete success
                // Translators: Message displayed after a successful deletion.\r
                $message_text = __('商品が削除されました。', 'ktpwp');
            }
            if ($message_text) {
                $content = '<div class="notice notice-success is-dismissible ktpwp-notice"><p>' . esc_html($message_text) . '</p></div>' . $content;
            }
        }

        return $content;
    }

    private function verify_view_mode_nonce($action) {
        $nonce_value = null;
        $nonce_action_expected = null;
        $nonce_field_name = null;

        if ($action === 'istmode') {
            $nonce_field_name = '_ktp_add_mode_nonce';
            $nonce_action_expected = 'ktp_add_mode_service';
        } elseif ($action === 'srcmode' && isset($_POST['_ktp_search_mode_nonce'])) {
            $nonce_field_name = '_ktp_search_mode_nonce';
            $nonce_action_expected = 'ktp_search_mode_service';
        } elseif ($action === 'update' && isset($_POST['_ktp_cancel_search_nonce'])) {
            $nonce_field_name = '_ktp_cancel_search_nonce';
            $nonce_action_expected = 'ktp_cancel_search_service';
        }

        if ($nonce_field_name && $nonce_action_expected) {
            if (!isset($_POST[$nonce_field_name])) {
                wp_die(esc_html__('Security check failed: Nonce field missing for view state change.', 'ktpwp'));
            }
            $nonce_value = sanitize_text_field(wp_unslash($_POST[$nonce_field_name]));
            if (!wp_verify_nonce($nonce_value, $nonce_action_expected)) {
                wp_die(esc_html__('Security check failed: Invalid nonce for view state change.', 'ktpwp'));
            }
        }
    }

    private function get_current_data_id($tab_name) {
        $cookie_name = 'ktp_' . $tab_name . '_id';
        $data_id = 1; // Default
        if (isset($_COOKIE[$cookie_name])) {
            $data_id = intval($_COOKIE[$cookie_name]);
        } elseif (isset($_GET['data_id'])) {
            $data_id = intval($_GET['data_id']);
        }
        return max(1, $data_id); // Ensure it's at least 1
    }

    private function render_insert_mode_form($tab_name) {
        $form_html = '<div class="data_detail_box">';
        // Translators: Title for the section when adding a new service item.\r\n        $form_html .= '<div class="data_detail_title">■ ' . esc_html__('商品追加中', 'ktpwp') . '</div>';
        $form_html .= $this->get_postal_code_script(); // Assuming this is still relevant for service tab
        $form_html .= '<form method="post" action="">';
        if (function_exists('wp_nonce_field')) {
            $form_html .= wp_nonce_field('ktp_insert_service', '_ktp_insert_nonce', true, false);
        }
        $form_html .= $this->generate_form_fields_html($this->fields_definition, null, false); // false for is_update_mode
        $form_html .= '<div class="button" style="display: flex; gap: 8px;">';
        // Translators: Button text for submitting a new service item.\r\n        $form_html .= '<button type="submit" name="query_post" value="insert" title="' . esc_attr__('追加実行', 'ktpwp') . '"><span class="material-symbols-outlined">select_check_box</span></button>';
        // Translators: Button text for cancelling the add operation.\r\n        $form_html .= '<button type="submit" name="query_post" value="update" title="' . esc_attr__('キャンセル', 'ktpwp') . '"><span class="material-symbols-outlined">disabled_by_default</span></button>';
        $form_html .= '</div></form><div class="add"></div></div>'; // Closing data_detail_box
        return $form_html;
    }

    private function render_search_mode_form($tab_name, $current_data_id) {
        $form_html = '<div class="data_detail_box">';
        // Translators: Title for the section when in search mode for services.\r\n        $form_html .= '<div class="data_detail_title">■ ' . esc_html__('商品の詳細（検索モード）', 'ktpwp') . '</div>';
        $form_html .= '<form method="post" action="">';
        if (function_exists('wp_nonce_field')) {
            $form_html .= wp_nonce_field('ktp_search_service', '_ktp_search_nonce', true, false);
        }
        // Translators: Placeholder text for the search input field.\r\n        $form_html .= '<div class="form-group"><input type="text" name="search_query" placeholder="' . esc_attr__('フリーワード', 'ktpwp') . '" required></div>';
        // $form_html .= $search_results_list; // This needs to be handled by Update_Table or AJAX
        $form_html .= '<div class="button" style="display: flex; gap: 8px;">';
        // Search execute button
        // Translators: Button text for executing a search.\r\n        $form_html .= '<button type="submit" name="query_post" value="search" title="' . esc_attr__('検索実行', 'ktpwp') . '"><span class="material-symbols-outlined">select_check_box</span></button>';
        // Cancel button (form inside form is not ideal, consider restructuring or JS for cancel)
        $form_html .= '</form>'; // Close search form

        // Cancel button - separate form or JS to change query_post and submit main form
        $form_html .= '<form method="post" action="" style="display: inline-block;">';
        if (function_exists('wp_nonce_field')) {
            // Translators: Nonce for cancelling search action.\r\n            $form_html .= wp_nonce_field('ktp_cancel_search_service', '_ktp_cancel_search_nonce', true, false);
        }
        $form_html .= '<input type="hidden" name="data_id" value="' . esc_attr($current_data_id) . '">'; // Or a sensible default
        $form_html .= '<input type="hidden" name="query_post" value="update">'; // Go back to update view
        // Translators: Button text for cancelling search.\r\n        $form_html .= '<button type="submit" title="' . esc_attr__('キャンセル', 'ktpwp') . '"><span class="material-symbols-outlined">disabled_by_default</span></button>';
        $form_html .= '</form>';

        $form_html .= '</div><div class="add"></div></div>'; // Closing data_detail_box
        return $form_html;
    }

    private function render_update_mode_form($tab_name, $data_id) {
        global $wpdb;
        $table_name_full = $wpdb->prefix . 'ktp_' . $tab_name;
        $form_html = '';

        $current_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_full} WHERE id = %d", $data_id));

        if (!$current_data && $data_id > 0) { // Data ID was specified but not found
            // Try to load the first available record if the current ID is invalid
            $first_data = $wpdb->get_row("SELECT * FROM {$table_name_full} ORDER BY id ASC LIMIT 1");
            if ($first_data) {
                $data_id = $first_data->id;
                $current_data = $first_data;
                // Update cookie with the valid ID
                $cookie_name = 'ktp_' . $tab_name . '_id';
                $cookie_path = defined('COOKIEPATH') ? COOKIEPATH : '/';
                $cookie_domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
                setcookie($cookie_name, $data_id, time() + (86400 * 30), $cookie_path, $cookie_domain);
            } else {
                // No data in the table at all, display a message or an empty "insert" like form
                // Translators: Message shown when no service data is found for display.\r\n                return '<div class="data_detail_box"><p>' . esc_html__('表示できる商品データがありません。最初の商品を追加してください。', 'ktpwp') . '</p>' . $this->render_insert_mode_form($tab_name) . '</div>';
            }
        } elseif (!$current_data && $data_id === 0) { // Should be handled by insert_mode, but as a fallback
             // Translators: Message shown when no service data is available and trying to update (should not happen often).\r\n            return '<div class="data_detail_box"><p>' . esc_html__('商品データが見つかりません。商品を追加してください。', 'ktpwp') . '</p>' . $this->render_insert_mode_form($tab_name) . '</div>';
        }

        // If $current_data is still null here, it means no data exists at all.
        // The above block tries to load first data or shows insert form.
        // If we reach here with $current_data, we proceed to display it.

        $form_html .= '<div class="data_detail_box">';
        // Translators: Title for the section displaying service details. %s is the service name.\r\n        $detail_title = $current_data ? sprintf(esc_html__('商品の詳細: %s', 'ktpwp'), esc_html($current_data->service_name)) : esc_html__('商品の詳細', 'ktpwp');
        $form_html .= '<div class="data_detail_title">■ ' . $detail_title . ' (ID: ' . esc_html($data_id) . ')</div>';

        // Image display and upload/delete forms
        $db_image_url = $current_data ? $current_data->image_url : '';
        $image_url = $this->get_effective_image_url($data_id, $db_image_url);

        // Translators: Alt text for the service image.\r\n        $form_html .= '<div class="image"><img src="' . esc_url($image_url) . '" alt="' . esc_attr__('商品画像', 'ktpwp') . '" class="product-image" onerror="this.src=\\'' . esc_url(plugin_dir_url(dirname(__FILE__)) . 'images/default/no-image-icon.jpg') . '\\'"></div>';
        $form_html .= '<div class="image_upload_form">';
        // Image Upload Form
        $form_html .= '<form action="" method="post" enctype="multipart/form-data" onsubmit="return checkImageUpload(this);">';
        if (function_exists('wp_nonce_field')) {
            // Translators: Nonce for uploading service image.\r\n            $form_html .= wp_nonce_field('ktp_upload_image_service_' . $data_id, '_ktp_upload_image_nonce', true, false);
        }
        $form_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
        $form_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($tab_name) . '">';
        $form_html .= '<input type="file" name="image_file" accept="image/jpeg, image/png, image/gif" required>';
        // Translators: Button text to upload an image.\r\n        $form_html .= '<button type="submit" name="query_post" value="upload_image" title="' . esc_attr__('画像アップロード', 'ktpwp') . '"><span class="material-symbols-outlined">file_upload</span></button>';
        $form_html .= '</form>';
        // Image Delete Form
        $form_html .= '<form action="" method="post" style="display: inline-block;">';
        if (function_exists('wp_nonce_field')) {
            // Translators: Nonce for deleting service image.\r\n            $form_html .= wp_nonce_field('ktp_delete_image_service_' . $data_id, '_ktp_delete_image_nonce', true, false);
        }
        $form_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
        $form_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($tab_name) . '">';
        // Translators: Button text to delete an image.\r\n        $form_html .= '<button type="submit" name="query_post" value="delete_image" title="' . esc_attr__('画像削除', 'ktpwp') . '"><span class="material-symbols-outlined">delete</span></button>';
        $form_html .= '</form>';
        $form_html .= '</div>'; // close image_upload_form

        // Main data form
        $form_html .= '<form method="post" action="" name="service_form">';
        if (function_exists('wp_nonce_field')) {
            // Translators: Nonce for updating service data.\r\n            $form_html .= wp_nonce_field('ktp_update_service_' . $data_id, '_ktp_update_nonce', true, false);
        }
        $form_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
        $form_html .= $this->generate_form_fields_html($this->fields_definition, $current_data, true); // true for is_update_mode

        $form_html .= '<div class="button" style="display: flex; gap: 8px;">';
        // Update button
        // Translators: Button text to update existing service data.\r\n        $form_html .= '<button type="submit" name="query_post" value="update" title="' . esc_attr__('更新実行', 'ktpwp') . '"><span class="material-symbols-outlined">save</span></button>';

        // Delete button (form-in-form, consider JS or separate form)
        $form_html .= '</form>'; // Close main data form

        // Delete Form (separate for clarity and proper nonce)
        $form_html .= '<form method="post" action="" style="display:inline-block;" onsubmit="return confirm(\'' . esc_js(__('本当にこの商品を削除しますか？', 'ktpwp')) . '\');">';
        if (function_exists('wp_nonce_field')) {
            // Translators: Nonce for deleting a service item.\r\n            $form_html .= wp_nonce_field('ktp_delete_service_' . $data_id, '_ktp_delete_nonce', true, false);
        }
        $form_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
        $form_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($tab_name) . '">';
        // Translators: Button text to delete a service item.\r\n        $form_html .= '<button type="submit" name="query_post" value="delete" title="' . esc_attr__('削除実行', 'ktpwp') . '"><span class="material-symbols-outlined">delete_forever</span></button>';
        $form_html .= '</form>';

        // Duplicate button (separate form)
        $form_html .= '<form method="post" action="" style="display:inline-block;">';
        if (function_exists('wp_nonce_field')) {
            // Translators: Nonce for duplicating a service item.\r\n            $form_html .= wp_nonce_field('ktp_duplicate_service_' . $data_id, '_ktp_duplicate_nonce', true, false);
        }
        $form_html .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
        $form_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($tab_name) . '">';
        // Translators: Button text to duplicate a service item.\r\n        $form_html .= '<button type="submit" name="query_post" value="duplication" title="' . esc_attr__('複製実行', 'ktpwp') . '"><span class="material-symbols-outlined">content_copy</span></button>';
        $form_html .= '</form>';

        $form_html .= '</div>'; // close .button div
        $form_html .= '<div class="add"></div></div>'; // Closing data_detail_box
        return $form_html;
    }

    private function render_data_list($tab_name, $current_data_id) {
        global $wpdb;
        $table_name_full = $wpdb->prefix . 'ktp_' . $tab_name;
        $list_html = '<div class="data_list_box">';
        // Translators: Title for the list of services.\r\n        $list_html .= '<div class="data_list_title">■ ' . esc_html__('商品一覧', 'ktpwp') . '</div>';
        $list_html .= '<form method="get" action="" id="service_list_filter_form">';
        // Keep existing query parameters
        if (isset($_GET['page'])) { // Assuming it's within WP admin or a page with 'page' query var
            $list_html .= '<input type="hidden" name="page" value="' . esc_attr(sanitize_text_field($_GET['page'])) . '">';
        }
        $list_html .= '<input type="hidden" name="tab_name" value="' . esc_attr($tab_name) . '">';
        
        // Category filter
        $categories = $this->get_category_options($table_name_full, true); // true to get all distinct categories
        $current_category_filter = isset($_GET['category_filter']) ? sanitize_text_field($_GET['category_filter']) : '';
        // Translators: Label for the category filter dropdown.
        $list_html .= '<label for="category_filter">' . esc_html__('カテゴリーで絞り込み:', 'ktpwp') . '</label>';
        $list_html .= '<select name="category_filter" id="category_filter" onchange="document.getElementById(\'service_list_filter_form\').submit();">';
        // Translators: Default option for category filter, showing all categories.\r\n        $list_html .= '<option value="">' . esc_html__('すべてのカテゴリー', 'ktpwp') . '</option>';
        foreach ($categories as $category) {
            $cat_val = esc_attr($category);
            $selected = ($current_category_filter === $category) ? ' selected' : '';
            $list_html .= '<option value="' . $cat_val . '"' . $selected . '>' . esc_html($category) . '</option>';
        }
        $list_html .= '</select>';
        // $list_html .= '<input type="submit" value="' . esc_attr__('絞り込み', 'ktpwp') . '">'; // Submit on change instead
        $list_html .= '</form>';

        // Pagination variables
        $items_per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $items_per_page;

        // Build query for data list
        $query_args = [];
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$table_name_full}";
        if (!empty($current_category_filter)) {
            $sql .= " WHERE category = %s";
            $query_args[] = $current_category_filter;
        }
        $sql .= " ORDER BY id DESC LIMIT %d OFFSET %d";
        $query_args[] = $items_per_page;
        $query_args[] = $offset;

        $results = $wpdb->get_results($wpdb->prepare($sql, $query_args));
        $total_items = $wpdb->get_var("SELECT FOUND_ROWS()");
        $total_pages = ceil($total_items / $items_per_page);

        if ($results) {
            $list_html .= '<ul>';
            foreach ($results as $row) {
                $id = intval($row->id);
                $service_name = esc_html($row->service_name);
                $category = esc_html($row->category);
                $class = ($id === $current_data_id) ? ' class="current"' : '';

                $link_url_params = ['tab_name' => $tab_name, 'data_id' => $id];
                if (isset($_GET['page'])) { $link_url_params['page'] = sanitize_text_field($_GET['page']); }
                if (!empty($current_category_filter)) { $link_url_params['category_filter'] = $current_category_filter; }
                if ($current_page > 1) { $link_url_params['paged'] = $current_page; }

                $base_url = remove_query_arg(['message', 'query_post', '_ktp_nonce']); // Clean base URL for links
                $link_url = add_query_arg($link_url_params, $base_url);

                // Translators: List item in service list. 1: Service Name, 2: Category.\r\n                $list_html .= "<li{$class}><a href=\"" . esc_url($link_url) . "\">" . sprintf(esc_html__('%1$s (%2$s)', 'ktpwp'), $service_name, $category) . "</a></li>";
            }
            $list_html .= '</ul>';

            // Pagination
            if ($total_pages > 1) {
                $list_html .= '<div class="pagination">';
                $page_links = paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page
                ]);
                $list_html .= $page_links;
                $list_html .= '</div>';
            }
        } else {
            // Translators: Message shown when no services are found in the list (possibly after filtering).\r\n            $list_html .= '<p>' . esc_html__('商品が見つかりません。', 'ktpwp') . '</p>';
        }
        $list_html .= '</div>'; // close data_list_box
        return $list_html;
    }

    private function render_navigation_buttons($tab_name, $current_data_id, $current_action) {
        global $wpdb;
        $table_name_full = $wpdb->prefix . 'ktp_' . $tab_name;
        $nav_html = '<div class="data_navi_box">';

        // Previous button
        $prev_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(id) FROM {$table_name_full} WHERE id < %d", $current_data_id));
        if ($prev_id) {
            $link_url = add_query_arg(['tab_name' => $tab_name, 'data_id' => $prev_id]);
            // Translators: Navigation button to go to the previous service item.\r\n            $nav_html .= '<a href="' . esc_url($link_url) . '" title="' . esc_attr__('前へ', 'ktpwp') . '"><span class="material-symbols-outlined">arrow_back_ios</span></a>';
        } else {
            // Translators: Disabled navigation button (no previous item).\r\n            $nav_html .= '<span class="material-symbols-outlined disabled" title="' . esc_attr__('前へ', 'ktpwp') . '">arrow_back_ios</span>';
        }

        // Next button
        $next_id = $wpdb->get_var($wpdb->prepare("SELECT MIN(id) FROM {$table_name_full} WHERE id > %d", $current_data_id));
        if ($next_id) {
            $link_url = add_query_arg(['tab_name' => $tab_name, 'data_id' => $next_id]);
            // Translators: Navigation button to go to the next service item.\r\n            $nav_html .= '<a href="' . esc_url($link_url) . '" title="' . esc_attr__('次へ', 'ktpwp') . '"><span class="material-symbols-outlined">arrow_forward_ios</span></a>';
        } else {
            // Translators: Disabled navigation button (no next item).\r\n            $nav_html .= '<span class="material-symbols-outlined disabled" title="' . esc_attr__('次へ', 'ktpwp') . '">arrow_forward_ios</span></a>';
        }

        // Add New button (as a form post to change mode)
        $nav_html .= '<form method="post" action="" style="display:inline-block;">';
        if (function_exists('wp_nonce_field')) {
            // Translators: Nonce for switching to add new service mode.\r\n            $nav_html .= wp_nonce_field('ktp_add_mode_service', '_ktp_add_mode_nonce', true, false);
        }
        $nav_html .= '<input type="hidden" name="query_post" value="istmode">';
        // Translators: Navigation button to add a new service item.\r\n        $nav_html .= '<button type="submit" title="' . esc_attr__('商品追加', 'ktpwp') . '"><span class="material-symbols-outlined">add_box</span></button>';
        $nav_html .= '</form>';

        // Search button (as a form post to change mode)
        $nav_html .= '<form method="post" action="" style="display:inline-block;">';
        if (function_exists('wp_nonce_field')) {
            // Translators: Nonce for switching to search mode for services.\r\n            $nav_html .= wp_nonce_field('ktp_search_mode_service', '_ktp_search_mode_nonce', true, false);
        }
        $nav_html .= '<input type="hidden" name="query_post" value="srcmode">';
        // Translators: Navigation button to search for service items.\r\n        $nav_html .= '<button type="submit" title="' . esc_attr__('商品検索', 'ktpwp') . '"><span class="material-symbols-outlined">search</span></button>';
        $nav_html .= '</form>';

        $nav_html .= '</div>'; // close data_navi_box
        return $nav_html;
    }

    private function get_category_options($table_name, $distinct = false) {
        global $wpdb;
        if ($distinct) {
            $results = $wpdb->get_col("SELECT DISTINCT category FROM {$table_name} WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
            return $results ? $results : [];
        }
        // Predefined options or fetch from a settings page if dynamic
        // For now, let's assume a fixed list or that they are created on-the-fly
        // This part might need adjustment based on how categories are managed.
        // If categories are purely dynamic from existing entries, the $distinct=true path is better.
        // For a dropdown in the form, you might want a predefined list + an "other" option.
        // For simplicity, let's use the distinct values from the table for now.
        $categories = $wpdb->get_col("SELECT DISTINCT category FROM {$table_name} WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        $options = [];
        if ($categories) {
            foreach ($categories as $cat) {
                $options[$cat] = $cat; // value => label
            }
        }
        // Add some default/common categories if the list is empty or to ensure they exist
        $default_categories = [__('一般', 'ktpwp'), __('おすすめ', 'ktpwp'), __('新商品', 'ktpwp')];
        foreach ($default_categories as $def_cat) {
            if (!isset($options[$def_cat])) {
                $options[$def_cat] = $def_cat;
            }
        }
        return $options;
    }

    // Helper methods for View_Table
    private function get_postal_code_script() {
        return <<<END
<script>
document.addEventListener('DOMContentLoaded', function() {
    var postalCode = document.querySelector('input[name="postal_code"]');
    var prefecture = document.querySelector('input[name="prefecture"]');
    var city = document.querySelector('input[name="city"]');
    var address = document.querySelector('input[name="address"]');
    if(postalCode && prefecture && city && address){
        postalCode.addEventListener('blur', function() {
            if (!postalCode.value) return;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'https://zipcloud.ibsnet.co.jp/api/search?zipcode=' + encodeURIComponent(postalCode.value));
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.results && response.results.length > 0) {
                            var result = response.results[0];
                            prefecture.value = result.address1 || '';
                            city.value = result.address2 || '';
                            address.value = result.address3 || '';
                        }
                    } catch (e) {
                        // console.error('Error parsing JSON response from ZipCloud API:', e);
                    }
                }
            };
            xhr.onerror = function() {
                // console.error('Network error when trying to fetch address from ZipCloud API.');
            };
            xhr.send();
        });
    }
});
</script>
END;
    }

    private function get_effective_image_url($data_id, $db_image_url) {
        $image_url = $db_image_url ? esc_url($db_image_url) : '';

        $upload_dir_path_base = plugin_dir_path(dirname(__FILE__)) . 'images/upload/';
        $upload_url_base = plugin_dir_url(dirname(__FILE__)) . 'images/upload/';
        
        $potential_image_filename = $data_id . '.jpeg'; // Assuming jpeg, adjust if other types are possible

        if ($data_id && file_exists($upload_dir_path_base . $potential_image_filename)) {
            $image_url = $upload_url_base . $potential_image_filename;
        } elseif (empty($image_url)) {
            $image_url = plugin_dir_url(dirname(__FILE__)) . 'images/default/no-image-icon.jpg';
        }
        return $image_url;
    }

    private function generate_form_fields_html($fields_definition, $current_data, $is_update_mode) {
        $form_html = '';
        foreach ($fields_definition as $label => $field) {
            $value = '';
            if ($is_update_mode) {
                $value = isset($current_data->{$field['name']}) ? $current_data->{$field['name']} : '';
            }
            // For insert mode, $value remains empty or uses a default if specified in $field definition

            $pattern_attr = isset($field['pattern']) ? " pattern=\"" . esc_attr($field['pattern']) . "\"" : '';
            $required_attr = isset($field['required']) && $field['required'] ? ' required' : '';
            $field_name_attr = esc_attr($field['name']);
            $placeholder_attr = isset($field['placeholder']) ? " placeholder=\"" . esc_attr__($field['placeholder'], 'ktpwp') . "\"" : '';
            $label_i18n = esc_html__($label, 'ktpwp');

            $form_html .= "<div class=\"form-group\"><label>{$label_i18n}：</label> ";
            if ($field['type'] === 'textarea') {
                $form_html .= "<textarea name=\"{$field_name_attr}\"{$pattern_attr}{$required_attr}>" . esc_textarea($value) . "</textarea>";
            } elseif ($field['type'] === 'select') {
                $options_html = '';
                if (!empty($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $option_value => $option_label) {
                        // If options is simple array ['val1', 'val2'], use value as label too
                        $opt_val = is_string($option_value) || is_numeric($option_value) ? $option_value : $option_label;
                        $selected_attr = ($value === $opt_val) ? ' selected' : '';
                        $options_html .= "<option value=\"" . esc_attr($opt_val) . "\"{$selected_attr}>" . esc_html__($option_label, 'ktpwp') . "</option>";
                    }
                }
                $default_option_label = isset($field['default']) ? esc_html__($field['default'], 'ktpwp') : esc_html__('選択してください', 'ktpwp');
                $form_html .= "<select name=\"{$field_name_attr}\"{$required_attr}><option value=\"\">{$default_option_label}</option>{$options_html}</select>";
            } else { // text, email, number, etc.
                $form_html .= "<input type=\"" . esc_attr($field['type']) . "\" name=\"{$field_name_attr}\" value=\"" . esc_attr($value) . "\"{$pattern_attr}{$required_attr}{$placeholder_attr}>";
            }
            $form_html .= "</div>";
        }
        return $form_html;
    }
    
    // Placeholder for $this->fields_definition, assuming it's a class property
    // protected $fields_definition = []; // Initialize in constructor or elsewhere

} // End of Kntan_Service_Class
}