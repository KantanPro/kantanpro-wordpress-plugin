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
        

        // CSRF対策: POST時のみnonceチェック
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['_ktp_service_nonce']) || !wp_verify_nonce($_POST['_ktp_service_nonce'], 'ktp_service_action')) {
                $wpdb->query("UNLOCK TABLES;");
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('CSRF/nonce error: nonce=' . (isset($_POST['_ktp_service_nonce']) ? $_POST['_ktp_service_nonce'] : 'NOT SET'));
                    error_log('POST内容: ' . print_r($_POST, true));
                }
                wp_die(__('不正なリクエストです。ページを再読み込みしてください。', 'ktpwp'));
            }
        }

        // POSTデーター受信
        $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;
        $query_post = isset($_POST['query_post']) ? sanitize_text_field($_POST['query_post']) : '';
        $service_name = isset($_POST['service_name']) ? sanitize_text_field($_POST['service_name']) : '';
        $memo = isset($_POST['memo']) ? sanitize_textarea_field($_POST['memo']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

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
            $wpdb->delete(
                $table_name,
                array(
                    'id' => $data_id
                ),
                array(
                    '%d'
                )
            );

            // ロックを解除する
            $wpdb->query("UNLOCK TABLES;");            // データ削除後に表示するデータIDを適切に設定
            $next_id_query = "SELECT id FROM {$table_name} WHERE id > {$data_id} ORDER BY id ASC LIMIT 1";
            $next_id_result = $wpdb->get_row($next_id_query);
            if ($next_id_result) {
                $next_data_id = $next_id_result->id;
            } else {
                $prev_id_query = "SELECT id FROM {$table_name} WHERE id < {$data_id} ORDER BY id DESC LIMIT 1";
                $prev_id_result = $wpdb->get_row($prev_id_query);
                $next_data_id = $prev_id_result ? $prev_id_result->id : 0;
            }
            
            $action = 'update';
            $cookie_name = 'ktp_' . $tab_name . '_id';
            setcookie($cookie_name, $next_data_id, time() + (86400 * 30), "/"); // 30日間有効
            
            // リダイレクトする代わりにJavaScriptでページを更新
            global $wp;
            $current_page_id = get_queried_object_id();
            $base_page_url = add_query_arg( array( 'page_id' => $current_page_id ), home_url( $wp->request ) );
            $url = add_query_arg([
                'tab_name' => $tab_name,
                'data_id' => $next_data_id,
                'query_post' => $action
            ], $base_page_url);
            
            echo '<script>
                // 現在のURLを更新（リダイレクトなし）
                window.history.pushState({}, "", "' . esc_js($url) . '");
                // コンテンツを更新（リロードなし）
                document.addEventListener("DOMContentLoaded", function() {
                    // 既存のフォームやデータを最新の状態にする処理をここに追加
                    // 成功メッセージを表示
                    var message = document.createElement("div");
                    message.className = "notice notice-success";
                    message.innerHTML = "<p>' . esc_js(__('項目が削除されました', 'ktpwp')) . '</p>";
                    message.style.padding = "10px";
                    message.style.backgroundColor = "#d4edda";
                    message.style.color = "#155724";
                    message.style.marginBottom = "15px";
                    message.style.borderRadius = "3px";
                    var firstElement = document.querySelector(".data_contents");
                    if (firstElement) {
                        firstElement.parentNode.insertBefore(message, firstElement);
                        // 3秒後にメッセージを消す
                        setTimeout(function() {
                            message.style.display = "none";
                        }, 3000);
                    }
                });
                
                // 削除後、画面をリフレッシュして最新データを表示（ページ遷移なし）
                location.reload();
            </script>';
            exit;
        }    
        
        // 更新
        elseif( $query_post == 'update' ){
            // nonceを検証
            if (!isset($_POST['_ktp_service_nonce']) || !wp_verify_nonce($_POST['_ktp_service_nonce'], 'ktp_service_action')) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Nonce verification failed for update.'); }
                wp_die(esc_html__('Nonce verification failed.', 'ktpwp'));
            }

            // データのIDを取得
            $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;

            // 更新するデータを準備
            $data = array(
                'service_name' => sanitize_text_field($_POST['service_name']),
                'memo' => sanitize_textarea_field($_POST['memo']),
                'category' => sanitize_text_field($_POST['category']),
                // 'time' は更新しないので含めない
            );

            // search_fieldの値を更新
            $data['search_field'] = implode(', ', [
                $data['service_name'],
                $data['category']
            ]);

            // データを更新
            $update_result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $data_id), // WHERE句
                array('%s', '%s', '%s', '%s'), // dataのフォーマット
                array('%d') // whereのフォーマット
            );

            if($update_result === false) {
                // エラーログに更新エラーを記録
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Update error: ' . $wpdb->last_error . ' for ID: ' . $data_id); }
                // JavaScriptを使用してポップアップエラーメッセージを表示
                echo "<script>alert('" . esc_js(esc_html__('更新に失敗しました。エラーログを確認してください。', 'ktpwp')) . "');</script>";
            } else {
                // 更新成功時の処理
                global $wp;
                $current_page_id = get_queried_object_id();
                $base_page_url = add_query_arg( array( 'page_id' => $current_page_id ), home_url( $wp->request ) );
                $url = add_query_arg([
                    'tab_name' => $tab_name,
                    'data_id' => $data_id,
                    'message' => 'updated' // 更新成功のメッセージパラメータ
                ], $base_page_url);

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
            }

            // ロックを解除し、処理を終了
            $wpdb->query("UNLOCK TABLES;");
            exit;
        }

        // 検索
        elseif( $query_post == 'search' ){

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
                $base_page_url = get_permalink($current_page_id);

                if (!$base_page_url) {
                    $page_slug = !empty($wp->request) ? $wp->request : '';
                    if ($current_page_id) {
                         $base_page_url = add_query_arg( array( 'page_id' => $current_page_id ), home_url( $page_slug ) );
                    } else {
                        $base_page_url = home_url(add_query_arg(array(), $wp->request));
                    }
                }
                
                $url = add_query_arg(array(
                    'tab_name' => $tab_name,
                    'data_id' => $data_id,
                    'query_post' => $action
                ), $base_page_url);

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
                    $search_results_html .= "<li style='text-align:left; width:100%;'><a href='" . add_query_arg(array('tab_name' => $tab_name, 'data_id' => $id, 'query_post' => 'update')) . "' style='text-align:left;'>" . sprintf(esc_html__('ID：%1$s 商品名：%2$s カテゴリー：%3$s', 'ktpwp'), $id, $service_name, $category) . "</a></li>";
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
            if (!isset($_POST['_ktp_service_nonce']) || !wp_verify_nonce($_POST['_ktp_service_nonce'], 'ktp_service_action')) {
                if (defined('WP_DEBUG') && WP_DEBUG) { error_log('Nonce verification failed for insert.'); }
                $wpdb->query("UNLOCK TABLES;");
                wp_die(esc_html__('Nonce verification failed for insert.', 'ktpwp'));
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
                
                $url = add_query_arg([
                    'tab_name' => $tab_name,
                    'data_id' => $new_data_id,
                    'query_post' => $action,
                    'message' => 'inserted'
                ], $base_page_url);
                
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
            // データのIDを取得
            $data_id = isset($_POST['data_id']) ? intval($_POST['data_id']) : 0;

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
                echo "<script>alert('" . esc_js(__('複製に失敗しました。', 'ktpwp')) . "'); if (window.history.length > 1) { window.history.back(); } else { window.location.href = document.referrer || window.location.pathname; }</script>";
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
                    $base_page_url = site_url(add_query_arg(array())); 
                }
                
                $url = add_query_arg(array(
                    'tab_name' => $tab_name,
                    'data_id' => $new_data_id,
                    'query_post' => $action
                ), $base_page_url);

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
            $base_page_url = add_query_arg( array( 'page_id' => $current_page_id ), home_url( $wp->request ) );
            $url = add_query_arg([
                'tab_name' => $tab_name,
                'data_id' => $data_id
            ], $base_page_url);
            header('Location: ' . esc_url_raw($url));
            exit;
        }        // 画像削除：デフォルト画像に戻す
        elseif ($query_post == 'delete_image') {

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
            $base_page_url = add_query_arg( array( 'page_id' => $current_page_id ), home_url( $wp->request ) );
            $url = add_query_arg([
                'tab_name' => $tab_name,
                'data_id' => $data_id
            ], $base_page_url);
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

    function View_Table( $name ) {

        global $wpdb;

        // -----------------------------
        // リスト表示
        // -----------------------------
        
        // テーブル名
        $table_name = $wpdb->prefix . 'ktp_' . $name;        // -----------------------------
        // ページネーションリンク
        // -----------------------------
          // ソート順の取得（デフォルトはIDの降順 - 新しい順）
        $sort_by = 'id';
        $sort_order = 'DESC';
        
        if (isset($_GET['sort_by'])) {
            $sort_by = sanitize_text_field($_GET['sort_by']);
            // 安全なカラム名のみ許可（SQLインジェクション対策）
            $allowed_columns = array('id', 'service_name', 'frequency', 'time', 'category');
            if (!in_array($sort_by, $allowed_columns)) {
                $sort_by = 'id'; // 不正な値の場合はデフォルトに戻す
            }
        }
        
        if (isset($_GET['sort_order'])) {
            $sort_order_param = strtoupper(sanitize_text_field($_GET['sort_order']));
            // ASCかDESCのみ許可
            $sort_order = ($sort_order_param === 'ASC') ? 'ASC' : 'DESC';
        }
        
        // 現在のページのURLを生成
        global $wp;
        $current_page_id = get_queried_object_id();
        // home_url() と $wp->request を使用して、現在のURLを取得し、page_idを追加
        $base_page_url = add_query_arg( array( 'page_id' => $current_page_id ), home_url( $wp->request ) );
        
        // 表示範囲（1ページあたりの表示件数）
        $query_limit = 20; // 明示的に20件に設定
        if (!is_numeric($query_limit) || $query_limit <= 0) {
            $query_limit = 20; // 不正な値の場合はデフォルト値に
        }
        
        // ソートプルダウンを追加
        $sort_url = add_query_arg(array('tab_name' => $name), $base_page_url);
        
        // ソート用プルダウンのHTMLを構築
        $sort_dropdown = '<div class="sort-dropdown" style="float:right;margin-left:10px;">' .
            '<form method="get" action="' . esc_url($sort_url) . '" style="display:flex;align-items:center;">';
        
        // 現在のGETパラメータを維持するための隠しフィールド
        foreach ($_GET as $key => $value) {
            if ($key !== 'sort_by' && $key !== 'sort_order') {
                $sort_dropdown .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
            }
        }
        
        $sort_dropdown .= 
            '<select id="sort-select" name="sort_by" style="margin-right:5px;">' .
            '<option value="id" ' . selected($sort_by, 'id', false) . '>' . esc_html__('ID', 'ktpwp') . '</option>' .
            '<option value="service_name" ' . selected($sort_by, 'service_name', false) . '>' . esc_html__('商品名', 'ktpwp') . '</option>' .
            '<option value="category" ' . selected($sort_by, 'category', false) . '>' . esc_html__('カテゴリー', 'ktpwp') . '</option>' .
            '<option value="frequency" ' . selected($sort_by, 'frequency', false) . '>' . esc_html__('頻度', 'ktpwp') . '</option>' .
            '<option value="time" ' . selected($sort_by, 'time', false) . '>' . esc_html__('登録日', 'ktpwp') . '</option>' .
            '</select>' .
            '<select id="sort-order" name="sort_order">' .
            '<option value="ASC" ' . selected($sort_order, 'ASC', false) . '>' . esc_html__('昇順', 'ktpwp') . '</option>' .
            '<option value="DESC" ' . selected($sort_order, 'DESC', false) . '>' . esc_html__('降順', 'ktpwp') . '</option>' .
            '</select>' .
            '<button type="submit" style="margin-left:5px;padding:4px 8px;background:#f0f0f0;border:1px solid #ccc;border-radius:3px;cursor:pointer;" title="' . esc_attr__('適用', 'ktpwp') . '">' .
            '<span class="material-symbols-outlined" style="font-size:18px;line-height:18px;vertical-align:middle;">check</span>' .
            '</button>' .
            '</form></div>';

        // リスト表示部分の開始
        $results_h = <<<END
        <div class="data_contents">
            <div class="data_list_box">
            <div class="data_list_title">■ 商品リスト {$sort_dropdown}</div>
        END;// スタート位置を決める
        $page_stage = $_GET['page_stage'] ?? '';
        $page_start = $_GET['page_start'] ?? 0;
        $flg = $_GET['flg'] ?? '';
        if ($page_stage == '') {
            $page_start = 0;
        }        $query_range = $page_start . ',' . $query_limit;

        // 全データ数を取得
        $total_query = "SELECT COUNT(*) FROM {$table_name}";
        $total_rows = $wpdb->get_var($total_query);
        
        // ゼロ除算防止のための安全対策
        if ($query_limit <= 0) {
            $query_limit = 20; // デフォルト値の設定
        }
        
        $total_pages = ceil($total_rows / $query_limit);

        // 現在のページ番号を計算
        $current_page = floor($page_start / $query_limit) + 1;

        // データを取得（ソート順を適用）
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY {$sort_by} {$sort_order} LIMIT %d, %d", $page_start, $query_limit);
        $post_row = $wpdb->get_results($query);
        $results = []; // ← 追加：未定義エラー防止
        if( $post_row ){
            foreach ($post_row as $row){
                $id = esc_html($row->id);
                $time = esc_html($row->time);
                $service_name = esc_html($row->service_name);
                $memo = esc_html($row->memo);
                $category = esc_html($row->category);
                $image_url = esc_html($row->image_url);
                $frequency = esc_html($row->frequency);
                  // リスト項目
                $cookie_name = 'ktp_' . $name . '_id';
                $results[] = '<a href="' . add_query_arg(array('tab_name' => $name, 'data_id' => $id, 'page_start' => $page_start, 'page_stage' => $page_stage)) . '">'.
                    '<div class="data_list_item">' . esc_html__('ID', 'ktpwp') . ': ' . $id . ' ' . $service_name . ' : ' . $category . ' : ' . esc_html__('頻度', 'ktpwp') . '(' . $frequency . ')</div>'.
                '</a>';
            }
            $query_max_num = $wpdb->num_rows;
        } else {
            $results[] = '<div class="data_list_item">' . esc_html__('データーがありません。', 'ktpwp') . '</div>';
        }

        $results_f = "<div class=\"pagination\">";

        // 最初へリンク
        if ($current_page > 1) {
            $first_start = 0; // 最初のページ
            $results_f .= ' <a href="' . add_query_arg(array('tab_name' => $name, 'page_start' => $first_start, 'page_stage' => 2, 'flg' => $flg)) . '">|&lt;</a> ';
        }

        // 前へリンク
        if ($current_page > 1) {
            $prev_start = ($current_page - 2) * $query_limit;
            $results_f .= '<a href="' . add_query_arg(array('tab_name' => $name, 'page_start' => $prev_start, 'page_stage' => 2, 'flg' => $flg)) . '">&lt;</a>';
        }

        // 現在のページ範囲表示と総数
        $page_end = min($total_rows, $current_page * $query_limit);
        $page_start_display = ($current_page - 1) * $query_limit + 1;
        $results_f .= "<div class='stage'> $page_start_display ~ $page_end / $total_rows</div>";

        // 次へリンク（現在のページが最後のページより小さい場合のみ表示）
        if ($current_page < $total_pages) {
            $next_start = $current_page * $query_limit;
            $results_f .= ' <a href="' . add_query_arg(array('tab_name' => $name, 'page_start' => $next_start, 'page_stage' => 2, 'flg' => $flg)) . '">&gt;</a>';
        }

        // 最後へリンク
        if ($current_page < $total_pages) {
            $last_start = ($total_pages - 1) * $query_limit; // 最後のページ
            $results_f .= ' <a href="' . add_query_arg(array('tab_name' => $name, 'page_start' => $last_start, 'page_stage' => 2, 'flg' => $flg)) . '">&gt;|</a>';
        }
        
        $results_f .= "</div></div>";

        $data_list = $results_h . implode( $results ) . $results_f;

        // -----------------------------
        // 詳細表示(GET)
        // -----------------------------

        // 現在表示中の詳細
        $cookie_name = 'ktp_' . $name . '_id';
        if (isset($_GET['data_id'])) {
            $query_id = filter_input(INPUT_GET, 'data_id', FILTER_SANITIZE_NUMBER_INT);
        } elseif (isset($_COOKIE[$cookie_name])) {
            $query_id = filter_input(INPUT_COOKIE, $cookie_name, FILTER_SANITIZE_NUMBER_INT);
        } else {
            // 最後のIDを取得して表示
            $query = "SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1";
            $last_id_row = $wpdb->get_row($query);
            $query_id = $last_id_row ? $last_id_row->id : 1;
        }

        
        // データを取得し変数に格納
        $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $query_id);
        $post_row = $wpdb->get_results($query);
        foreach ($post_row as $row){
            $data_id = esc_html($row->id);
            $time = esc_html($row->time);
            $service_name = esc_html($row->service_name);
            $memo = esc_html($row->memo);
            $category = esc_html($row->category);
            $image_url = esc_html($row->image_url);
        }
          // 表示するフォーム要素を定義
        $fields = [
            // 'ID' => ['type' => 'text', 'name' => 'data_id', 'readonly' => true], 
            esc_html__('商品名', 'ktpwp') => ['type' => 'text', 'name' => 'service_name', 'required' => true, 'placeholder' => esc_attr__('必須 商品・サービス名', 'ktpwp')],
            // '画像URL' => ['type' => 'text', 'name' => 'image_url'], // 商品画像のURLフィールドはコメントアウト
            esc_html__('メモ', 'ktpwp') => ['type' => 'textarea', 'name' => 'memo'],
            esc_html__('カテゴリー', 'ktpwp') => [
                'type' => 'text',
                'name' => 'category',
                'options' => esc_html__('一般', 'ktpwp'),
                'suggest' => true,
            ],
        ];
        
        $action = isset($_POST['query_post']) ? $_POST['query_post'] : 'update';// アクションを取得（デフォルトは'update'）
        $data_forms = ''; // フォームのHTMLコードを格納する変数を初期化
        
        // データー量を取得
        $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $query_id);
        $data_num = $wpdb->get_results($query);
        $data_num = count($data_num); // 現在のデータ数を取得し$data_numに格納

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
            if (function_exists('wp_nonce_field')) { $data_title .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); }
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
            if (function_exists('wp_nonce_field')) { $data_forms .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); }
            $data_forms .= "<div class=\"form-group\"><input type=\"text\" name=\"search_query\" placeholder=\"フリーワード\" required></div>";
            // 検索リストを生成
            $data_forms .= $search_results_list;
            // ボタン<div>タグを追加
            $data_forms .= "<div class='button' style='display: flex; gap: 8px;'>";
            // 検索実行ボタン
            $action_search = 'search';
            $nonce_field_search = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : '';
            $data_forms .= "<form method='post' action='' style='display: inline-block;'>";
            $data_forms .= $nonce_field_search;
            $data_forms .= "<input type='hidden' name='query_post' value='" . esc_attr($action_search) . "'>";
            $data_forms .= "<button type='submit' name='send_post' title='" . esc_attr__('検索実行', 'ktpwp') . "'>";
            $data_forms .= "<span class='material-symbols-outlined'>select_check_box</span>";
            $data_forms .= "</button></form>";
            // キャンセルボタン
            $action_cancel = 'update';
            $data_id_cancel = $data_id - 1;
            $nonce_field_cancel = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : '';
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
        elseif ($action !== 'srcmode' || $action !== 'istmode') {

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
            $nonce_field_upload = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : '';
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
            $nonce_field_delete = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : '';
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
            $nonce_field = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : '';
            $action_buttons_html .= <<<END
            <form method="post" action="" style="display: inline-block;">
            $nonce_field
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
            $nonce_field = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : '';
            $action_buttons_html .= <<<END
            <form method="post" action="" style="display: inline-block;">
            $nonce_field
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
            $nonce_field = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : '';
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
            $nonce_field = function_exists('wp_nonce_field') ? wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false) : '';
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
            if (function_exists('wp_nonce_field')) { $data_forms .= wp_nonce_field('ktp_service_action', '_ktp_service_nonce', true, false); }
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