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
        // ... ここに $service_name, $category などの他の初期化が存在する場合 ...

        // 現在のアクションを決定しサニタイズする
        $action = 'update'; // デフォルトアクション
        $is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');

        if ($is_post_request && isset($_POST['query_post'])) {
            $action = sanitize_text_field(wp_unslash($_POST['query_post']));

            // View_Table内で処理されるPOSTアクションによるビューモード変更のNonce検証
            // (例: 「追加モード」または「検索モード」ビューへの切り替え、または「検索キャンセル」)
            // データ変更アクション (insert, update, delete など) のNonceは
            // Update_Table メソッドで検証されるべきです。
            $nonce_value = null;
            $nonce_action_expected = null;
            $nonce_field_name = null;

            if ($action === 'istmode') { // 「追加モード」ボタン
                $nonce_field_name = '_ktp_add_mode_nonce';
                $nonce_action_expected = 'ktp_add_mode_service';
            } elseif ($action === 'srcmode' && isset($_POST['_ktp_search_mode_nonce'])) {
                // これは検索モードに *入る* ボタンのためのものです。
                // 実際の検索 *実行* (query_post=search) は Update_Table によって処理されます。
                $nonce_field_name = '_ktp_search_mode_nonce';
                $nonce_action_expected = 'ktp_search_mode_service';
            } elseif ($action === 'update' && isset($_POST['_ktp_cancel_search_nonce'])) {
                // これは「検索キャンセル」ボタン用で、action=update をPOSTしますが、
                // 固有のNonceフィールドの存在によって識別されます。
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
        } elseif (!$is_post_request && isset($_GET['action'])) {
            // GETによってアクションがトリガーされる場合もここでサニタイズ
            // 通常、GETによる状態変更アクションやモード切り替えはNonceなしでは非推奨
            $action = sanitize_text_field(wp_unslash($_GET['action']));
            // これらのGETアクションがNonce保護を必要とする場合 (例: リンク用)、URLにNonceが必要になり、
            // ここで検証が必要。現時点ではアクション値のサニタイズのみ。
        }
        
        // $action の元の行:
        // $action = isset($_POST['query_post']) ? $_POST['query_post'] : 'update';
        // これは上記のより堅牢なブロックに置き換えられました。
        
        $cookie_name = 'ktp_'. $name . '_id'; // $cookie_name の定義を確認 (例)

        // ... filter_input を使用した $query_id の決定などの既存コード (これは良い) ...

        // 空のフォームを表示(追加モードの場合)
        if ($action === 'istmode') {

            $data_id = 0; // 新規追加なのでIDは0
            $service_name = '';
            $memo = '';
            $category = '';

            // 詳細表示部分の開始とフォームを詳細ボックス内に含める
            $data_title = '<div class="data_detail_box">' .
                          '<div class="data_detail_title">■ ' . esc_html__('商品追加中', 'ktpwp') . '</div>';
            
            // 郵便番号自動入力JSをdata_titleに追加
            $data_title .= <<<END
<script>
document.addEventListener('DOMContentLoaded', function() {
    var postalCode = document.querySelector('input[name="postal_code"]');
    var prefecture = document.querySelector('input[name="prefecture"]');
    var city = document.querySelector('input[name="city"]');
    var address = document.querySelector('input[name="address"]');
    if(postalCode){
        postalCode.addEventListener('blur', function() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'https://zipcloud.ibsnet.co.jp/api/search?zipcode=' + postalCode.value);
            xhr.addEventListener('load', function() {
                var response = JSON.parse(xhr.responseText);
                if (response.results) {
                    var data = response.results[0];
                    prefecture.value = data.address1;
                    city.value = data.address2 + data.address3;
                    address.value = '';
                }
            });
            xhr.send();
        });
    }
});
</script>
END;
            // 1フォームでまとめる - これもdata_titleに追加
            $data_title .= '<form method="post" action="">';
            // if (function_exists('wp_nonce_field')) { $data_title .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); } // Old nonce
            if (function_exists('wp_nonce_field')) { $data_title .= wp_nonce_field('ktp_insert_service', '_ktp_insert_nonce', true, false); } // New specific nonce for insert
            foreach ($fields as $label => $field) {
                $value = $action === 'update' ? ${$field['name']} : '';
                $pattern = isset($field['pattern']) ? " pattern=\"" . esc_attr($field['pattern']) . "\"" : '';
                $required = isset($field['required']) && $field['required'] ? ' required' : ''; // ここを修正
                $fieldName = esc_attr($field['name']);
                $placeholder = isset($field['placeholder']) ? " placeholder=\"" . esc_attr__($field['placeholder'], 'ktpwp') . "\"" : '';
                $label_i18n = esc_html__($label, 'ktpwp');
                if ($field['type'] === 'textarea') {
                    $data_title .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <textarea name=\"{$fieldName}\"{$pattern}{$required}>" . esc_textarea($value) . "</textarea></div>";
                } elseif ($field['type'] === 'select') {
                    $options = '';
                    foreach ((array)$field['options'] as $option) {
                        $selected = $value === $option ? ' selected' : '';
                        $options .= "<option value=\"" . esc_attr($option) . "\"{$selected}>" . esc_html__($option, 'ktpwp') . "</option>";
                    }
                    $default = isset($field['default']) ? esc_html__($field['default'], 'ktpwp') : '';
                    $data_title .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <select name=\"{$fieldName}\"{$required}><option value=\"\">{$default}</option>{$options}</select></div>";
                } else {
                    $data_title .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <input type=\"{$field['type']}\" name=\"{$fieldName}\" value=\"" . esc_attr($value) . "\"{$pattern}{$required}{$placeholder}></div>";
                }
            }
            $data_title .= '<div class="button" style="display: flex; gap: 8px;">';
            // 追加実行ボタン
            $data_title .= '<button type="submit" name="query_post" value="insert" title="' . esc_attr__('追加実行', 'ktpwp') . '"><span class="material-symbols-outlined">select_check_box</span></button>';
            // キャンセルボタン
            $data_title .= '<button type="submit" name="query_post" value="update" title="' . esc_attr__('キャンセル', 'ktpwp') . '"><span class="material-symbols-outlined">disabled_by_default</span></button>';
            $data_title .= '</div></form><div class="add"></div>';
            
            // data_detail_boxの閉じタグを追加
            $data_title .= '</div>';
        }

        // 空のフォームを表示(検索モードの場合)
        elseif ($action === 'srcmode') {

            // 表題
            $data_title = <<<END
            <div class="data_detail_box">
                <div class="data_detail_title">■ <?php echo esc_html__('商品の詳細（検索モード）', 'ktpwp'); ?></div>
            END;

            // 検索フォームを生成
            $data_forms = '<form method="post" action="">';
            // nonceフィールド追加
            // if (function_exists('wp_nonce_field')) { $data_forms .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); } // Old nonce
            if (function_exists('wp_nonce_field')) { $data_forms .= wp_nonce_field('ktp_search_service', '_ktp_search_nonce', true, false); } // New specific nonce for search
            $data_forms .= "<div class=\"form-group\"><input type=\"text\" name=\"search_query\" placeholder=\"フリーワード\" required></div>";
            // 検索リストを生成
            $data_forms .= $search_results_list;
            // ボタン<div>タグを追加
            $data_forms .= "<div class='button' style='display: flex; gap: 8px;'>";
            // 検索実行ボタン
            $action_search = 'search';
            // $nonce_field_search = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : ''; // Old nonce
            $nonce_field_search = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_search_service', '_ktp_search_nonce', true, false) : ''; // New specific nonce for search
            $data_forms .= "<form method='post' action='' style='display: inline-block;'>";
            $data_forms .= $nonce_field_search;
            $data_forms .= "<input type='hidden' name='query_post' value='" . esc_attr($action_search) . "'>";
            $data_forms .= "<button type='submit' name='send_post' title='" . esc_attr__('検索実行', 'ktpwp') . "'>";
            $data_forms .= "<span class='material-symbols-outlined'>select_check_box</span>";
            $data_forms .= "</button></form>";
            // キャンセルボタン
            $action_cancel = 'update';
            $data_id_cancel = $data_id - 1;
            // $nonce_field_cancel = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : ''; // Old nonce, not strictly necessary for cancel but good for consistency if it were a state-changing cancel
            $nonce_field_cancel = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_cancel_search_service', '_ktp_cancel_search_nonce', true, false) : ''; // New specific nonce for cancel search (though likely just a redirect)
            $data_forms .= "<form method='post' action='' style='display: inline-block;'>";
            $data_forms .= $nonce_field_cancel;
            $data_forms .= "<input type='hidden' name='data_id' value=''>";
            $data_forms .= "<input type='hidden' name='query_post' value='" . esc_attr($action_cancel) . "'>";
            $data_forms .= "<input type='hidden' name='data_id' value='" . esc_attr($data_id_cancel) . "'>";
            $data_forms .= "<button type='submit' name='send_post' title='" . esc_attr__('キャンセル', 'ktpwp') . "'>";
            $data_forms .= "<span class='material-symbols-outlined'>disabled_by_default</span>";
            $data_forms .= "</button></form>";
            $data_forms .= "<div class=\"add\">";
            $data_forms .= '</div>';
        }            

        // 追加・検索 以外なら更新フォームを表示
        // elseif ($action !== 'srcmode' || $action !== 'istmode') { // 元の誤った条件
        elseif ($action !== 'srcmode' && $action !== 'istmode') { // 修正された論理条件

            // 郵便番号から住所を自動入力するためのJavaScriptコードを追加（日本郵政のAPIを利用）
            $data_forms .= <<<END
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var postalCode = document.querySelector('input[name="postal_code"]');
                var prefecture = document.querySelector('input[name="prefecture"]');
                var city = document.querySelector('input[name="city"]');
                var address = document.querySelector('input[name="address"]');
                postalCode.addEventListener('blur', function() {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'https://zipcloud.ibsnet.co.jp/api/search?zipcode=' + postalCode.value);
                    xhr.addEventListener('load', function() {
                        var response = JSON.parse(xhr.responseText);
                        if (response.results) {
                            var data = response.results[0];
                            prefecture.value = data.address1;
                            city.value = data.address2 + data.address3; // 市区町村と町名を結合
                            address.value = ''; // 番地は空欄に
                        }
                    });
                    xhr.send();
                });
            });
            </script>
            END;
            
            // データを取得
            global $wpdb;
            $table_name = $wpdb->prefix . 'ktp_' . $name;
            
            // データを取得
            $query = "SELECT * FROM {$table_name} WHERE id = %d";
            $post_row = $wpdb->get_results($wpdb->prepare($query, $data_id));            $image_url = '';
            foreach ($post_row as $row) {
                $image_url = esc_html($row->image_url);
            }
            
            // 画像URLが空または無効な場合、デフォルト画像を使用
            if (empty($image_url)) {
                $image_url = plugin_dir_url(dirname(__FILE__)) . 'images/default/no-image-icon.jpg';
            }
            
            // アップロード画像が存在するか確認
            $upload_dir = dirname(__FILE__) . '/../images/default/upload/';
            $upload_file = $upload_dir . $data_id . '.jpeg';
            if (file_exists($upload_file)) {
                $plugin_url = plugin_dir_url(dirname(__FILE__));
                $image_url = $plugin_url . 'images/default/upload/' . $data_id . '.jpeg';
            }
            
            $data_forms .= "<div class=\"image\"><img src=\"{$image_url}\" alt=\"" . esc_attr__('商品画像', 'ktpwp') . "\" class=\"product-image\" onerror=\"this.src='" . plugin_dir_url(dirname(__FILE__)) . "images/default/no-image-icon.jpg'\"></div>";            $data_forms .= '<div class=image_upload_form>';            // 商品画像アップロードフォームを追加
            // 商品画像アップロードフォーム
            // $nonce_field_upload = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : ''; // Old nonce
            $nonce_field_upload = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_upload_image_service_' . $data_id, '_ktp_upload_image_nonce', true, false) : ''; // New specific nonce for image upload
            $data_forms .= '<form action="" method="post" enctype="multipart/form-data" onsubmit="return checkImageUpload(this);">';
            $data_forms .= $nonce_field_upload;
            $data_forms .= '<div class="file-upload-container">';
            $data_forms .= '<input type="file" name="image" class="file-input">';
            $data_forms .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
            $data_forms .= '<input type="hidden" name="query_post" value="upload_image">';
            $data_forms .= '<button type="submit" class="upload-btn" title="画像をアップロード">';
            $data_forms .= '<span class="material-symbols-outlined">upload</span>';
            $data_forms .= '</button>';
            $data_forms .= '</div>';
            $data_forms .= '</form>';
            $data_forms .= '<script>function checkImageUpload(form) { if (!form.image.value) { alert("画像が選択されていません。アップロードする画像を選択してください。"); return false; } return true; }</script>';

            // 商品画像削除ボタン
            // $nonce_field_delete = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : ''; // Old nonce
            $nonce_field_delete = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_delete_image_service_' . $data_id, '_ktp_delete_image_nonce', true, false) : ''; // New specific nonce for image delete
            $data_forms .= '<form method="post" action="">';
            $data_forms .= $nonce_field_delete;
            $data_forms .= '<input type="hidden" name="data_id" value="' . esc_attr($data_id) . '">';
            $data_forms .= '<input type="hidden" name="query_post" value="delete_image">';
            $data_forms .= '<button type="submit" name="send_post" title="削除する" onclick="return confirm(\'本当に削除しますか？\')">';
            $data_forms .= '<span class="material-symbols-outlined">delete</span>';
            $data_forms .= '</button>';
            $data_forms .= '</form>';

            $data_forms .= '</div>';
            
            $data_forms .= "<div class=\"add\">";
            // ここで不要な空フォームは出力しない
            // cookieに保存されたIDを取得
            $cookie_name = 'ktp_'. $name . '_id';
            if (isset($_GET['data_id'])) {
                $data_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
            } elseif (isset($_COOKIE[$cookie_name])) {
                $data_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
            } else {
                $data_id = $last_id_row ? $last_id_row->id : Null;
            }

            // ボタンHTMLを格納する変数
            $action_buttons_html = '<div class="button-group" style="display: flex; gap: 8px;">';

            // 削除ボタン
            // $nonce_field = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : ''; // 修正前
            $delete_nonce_html = function_exists('wp_nonce_field') ? wp_nonce_field( 'ktp_delete_service_' . $data_id, '_ktp_delete_nonce', true, false ) : ''; // 修正後：削除アクション専用のNonce
            $action_buttons_html .= <<<END
            <form method="post" action="" style="display: inline-block;">
            {$delete_nonce_html}
                <input type="hidden" name="data_id" value="{$data_id}">
                <input type="hidden" name="query_post" value="delete">
                <button type="submit" name="send_post" title="削除する" onclick="return confirm(\\\'本当に削除しますか？\\\')">
                    <span class="material-symbols-outlined">
                        delete
                    </span>
                </button>
            </form>
            END;

            // 複製ボタン
            // $duplicate_nonce_html = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : ''; // 複製や他のアクションは共通のNonceを使用 (Old)
            $duplicate_nonce_html = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_duplicate_service_' . $data_id, '_ktp_duplicate_nonce', true, false) : ''; // New specific nonce for duplication
            $action_buttons_html .= <<<END
            <form method="post" action="" style="display: inline-block;">
            {$duplicate_nonce_html}
                <input type="hidden" name="data_id" value="{$data_id}">
                <input type="hidden" name="query_post" value="duplication">
                <button type="submit" name="send_post" title="複製する">
                    <span class="material-symbols-outlined">
                    content_copy
                    </span>
                </button>
            </form>
            END;

            // 追加モードボタン
            $add_action = 'istmode';
            $next_data_id = $data_id + 1; // 複製や新規追加時のIDとして利用する想定
            // $nonce_field = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : ''; // Old nonce
            $nonce_field = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_add_mode_service', '_ktp_add_mode_nonce', true, false) : ''; // New specific nonce for add mode button
            $action_buttons_html .= <<<END
            <form method='post' action='' style="display: inline-block;">
            $nonce_field
                <input type='hidden' name='data_id' value=''>
                <input type='hidden' name='query_post' value='$add_action'>
                <input type='hidden' name='data_id' value='$next_data_id'>
                <button type='submit' name='send_post' title="追加する">
                    <span class="material-symbols-outlined">
                    add
                    </span>
                </button>
            </form>
            END;

            // 検索モードボタン
            $search_action = 'srcmode';
            // $nonce_field = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : ''; // Old nonce
            $nonce_field = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_search_mode_service', '_ktp_search_mode_nonce', true, false) : ''; // New specific nonce for search mode button
            $action_buttons_html .= <<<END
            <form method='post' action='' style="display: inline-block;">
            $nonce_field
                <input type='hidden' name='query_post' value='$search_action'>
                <button type='submit' name='send_post' title="検索する">
                    <span class="material-symbols-outlined">
                    search
                    </span>
                </button>
            </form>
            END;
            $action_buttons_html .= '</div>'; // button-group の閉じタグ

            // 表題
            $data_title = <<<END
            <div class="data_detail_box">
                <div class="data_detail_title">■ 商品の詳細（ ID： $data_id ） $action_buttons_html</div>
            END;
            
            // 更新フォームの開始
            $data_forms .= "<form name='service_form' method='post' action=''>";
            // if (function_exists('wp_nonce_field')) { $data_forms .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); } // Old nonce
            if (function_exists('wp_nonce_field')) { $data_forms .= wp_nonce_field('ktp_update_service_' . $data_id, '_ktp_update_nonce', true, false); } // New specific nonce for update
            foreach ($fields as $label => $field) {
                $value = $action === 'update' ? ${$field['name']} : '';
                $pattern = isset($field['pattern']) ? " pattern=\"" . esc_attr($field['pattern']) . "\"" : '';
                $required = isset($field['required']) && $field['required'] ? ' required' : '';
                $fieldName = esc_attr($field['name']);
                $placeholder = isset($field['placeholder']) ? " placeholder=\"" . esc_attr__($field['placeholder'], 'ktpwp') . "\"" : '';
                $label_i18n = esc_html__($label, 'ktpwp');
                if ($field['type'] === 'textarea') {
                    $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <textarea name=\"{$fieldName}\"{$pattern}{$required}>" . esc_textarea($value) . "</textarea></div>";
                } elseif ($field['type'] === 'select') {
                    $options = '';
                    foreach ((array)$field['options'] as $option) {
                        $selected = $value === $option ? ' selected' : '';
                        $options .= "<option value=\"" . esc_attr($option) . "\"{$selected}>" . esc_html__($option, 'ktpwp') . "</option>";
                    }
                    $default = isset($field['default']) ? esc_html__($field['default'], 'ktpwp') : '';
                    $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <select name=\"{$fieldName}\"{$required}><option value=\"\">{$default}</option>{$options}</select></div>";
                } else {
                    $data_forms .= "<div class=\"form-group\"><label>{$label_i18n}：</label> <input type=\"{$field['type']}\" name=\"{$fieldName}\" value=\"" . esc_attr($value) . "\"{$pattern}{$required}{$placeholder}></div>";
                }
            }
            $data_forms .= "<input type=\"hidden\" name=\"query_post\" value=\"update\">";
            $data_forms .= "<input type=\"hidden\" name=\"data_id\" value=\"{$data_id}\">";
            $data_forms .= "<div class='button'>";
            $data_forms .= "<button type=\"submit\" name=\"send_post\" title=\"更新する\"><span class=\"material-symbols-outlined\">cached</span></button>";
            $data_forms .= "</div>";
            $data_forms .= "</form>";

            // 検索リストを生成
            if (!isset($search_results_list)) {
                $search_results_list = '';
            }
            // $data_forms .= $search_results_list; // ボタンを移動したので、ここでは追加しない

            // 削除・複製・追加・検索は個別フォーム＋nonceで出力していた箇所を削除
            // $data_forms .= <<<END
            // ... (削除されるボタンのHTML) ...
            // END;
            // ... (削除されるボタンのHTML) ...
            // END;
            // ... (削除されるボタンのHTML) ...
            // END;
            // ... (削除されるボタンのHTML) ...
            // END;
        }
                            
        $data_forms .= '</div>'; // フォームを囲む<div>タグの終了
        
        // 詳細表示部分の終了
        $div_end = <<<END
            </div> <!-- data_detail_boxの終了 -->
        </div> <!-- data_contentsの終了 -->
        END;

        // -----------------------------
        // テンプレート印刷
        // -----------------------------

        // Print_Classのパスを指定
        require_once( dirname( __FILE__ ) . '/class-print.php' );

        // データを指定
        $data_src = [
            'service_name' => $service_name,
            'category' => $category,
            'image_url' => $image_url
        ];

        $customer = $data_src['service_name'];
        $data = [
            'service_name' => $service_name,
            'category' => $category,
            'image_url' => $image_url
        ];

        $print_html = new Print_Class($data);
        $print_html = $print_html->generateHTML();

        // PHP
        $print_html = json_encode($print_html);  // JSON形式にエンコード

        // JavaScript
        $print = <<<END
        <script>
            var isPreviewOpen = false;            function printContent() {
                var printContent = $print_html;
                var printWindow = window.open('', '_blank');
                printWindow.document.open();
                printWindow.document.write('<html><head><title>印刷</title></head><body>');
                printWindow.document.write(printContent);
                printWindow.document.write('<script>window.onafterprint = function(){ window.close(); }<\/script>');
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();  // Add this line
                
                // 印刷後、プレビューが開いていれば閉じる
                if (isPreviewOpen) {
                    togglePreview();
                }
            }

            function togglePreview() {
                var previewWindow = document.getElementById('previewWindow');
                var previewButton = document.getElementById('previewButton');
                if (isPreviewOpen) {
                    previewWindow.style.display = 'none';
                    previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="プレビュー">preview</span>';
                    isPreviewOpen = false;
                } else {
                    var printContent = $print_html;
                    previewWindow.innerHTML = printContent;
                    previewWindow.style.display = 'block';
                    previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="閉じる">close</span>';
                    isPreviewOpen = true;
                }
            }

            // about:blankを閉じる
            // window.onafterprint = function() {
            //     window.close();
            // }

        </script>        <div class="controller">
            <div class="printer">
                <button id="previewButton" onclick="togglePreview()" title="プレビュー">
                    <span class="material-symbols-outlined" aria-label="プレビュー">preview</span>
                </button>
                <button onclick="printContent()" title="印刷する">
                    <span class="material-symbols-outlined" aria-label="印刷">print</span>
                </button>
            </div>        </div>
        <div class="workflow">
        </div>
        <div id="previewWindow" style="display: none;"></div>
        END;

        // コンテンツを返す
        $content = $print . $data_list . $data_title . $data_forms . $search_results_list . $div_end;
        return $content;
        
    }

}
} // class_exists