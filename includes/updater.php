<?php
// ...existing code...

add_filter('site_transient_update_plugins', 'ktpwp_check_for_plugin_update');
function ktpwp_check_for_plugin_update($transient) {
    if (empty($transient->checked)) return $transient;

    $license_key = ktpwp_get_license_key();
    if (!$license_key) return $transient;

    $api_url = 'https://ktpwp.com/blog'; // EDD設置先
    $params = array(
        'edd_action' => 'get_version',
        'license'    => $license_key,
        'item_name'  => urlencode('Kantan Pro WP'),
        'slug'       => 'kantan-pro-wp',
        'url'        => home_url(),
    );
    $response = wp_remote_post($api_url, array(
        'body'      => $params,
        'sslverify' => true,
        'timeout'   => 15,
    ));

    if (is_wp_error($response)) return $transient;

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($data) || empty($data['new_version'])) return $transient;

    // ハッシュ値もAPIで返すことを推奨（例: $data['package_hash']）
    $plugin_file = 'kantan-pro-wp/kantan-pro-wp.php';
    $transient->response[$plugin_file] = (object) array(
        'slug'        => 'kantan-pro-wp',
        'plugin'      => $plugin_file,
        'new_version' => $data['new_version'],
        'url'         => $data['homepage'] ?? '',
        'package'     => $data['package'], // zipファイルURL
        'package_hash'=> $data['package_hash'] ?? '', // オプション
    );
    return $transient;
}

// アップデート後にハッシュ検証
add_action('upgrader_process_complete', function($upgrader, $hook_extra) {
    if (empty($hook_extra['plugins'])) return;
    foreach ($hook_extra['plugins'] as $plugin) {
        if ($plugin === 'kantan-pro-wp/kantan-pro-wp.php') {
            // zipファイルのハッシュ検証（例: SHA256）
            $expected_hash = get_transient('ktpwp_update_package_hash');
            if ($expected_hash) {
                $plugin_dir = WP_PLUGIN_DIR . '/kantan-pro-wp/';
                $zip_path = $plugin_dir . 'kantan-pro-wp.zip';
                if (file_exists($zip_path)) {
                    $actual_hash = hash_file('sha256', $zip_path);
                    if ($actual_hash !== $expected_hash) {
                        // ハッシュ不一致時の処理
                        error_log('KTPWPアップデート: ハッシュ不一致');
                        // 必要に応じてプラグインを無効化等
                    }
                    unlink($zip_path);
                }
            }
        }
    }
}, 10, 2);

// ...existing code...