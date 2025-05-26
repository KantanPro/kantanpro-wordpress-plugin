<?php

class Image_Processor {

    /**
     * 画像のアップロードと削除を行う
     * 
     * @param string $tab_name テーブル名
     * @param int $data_id データID
     * @param string $default_image_url デフォルト画像のURL
     * @return string $image_url 画像のURL
     */    public function handle_image($tab_name, $data_id, $default_image_url) {
        global $wpdb;
        // 保存先ディレクトリを images/upload/ に変更
        $upload_dir = dirname(__FILE__) . '/../images/upload/';
        // ディレクトリがなければ作成
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        // 保存ファイル名
        $file_path = $upload_dir . $data_id . '.jpeg';
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $file      = $_FILES['image'];
            $mime      = mime_content_type($file['tmp_name']);
            $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $size      = filesize($file['tmp_name']);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
            $allowed_exts  = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 2 * 1024 * 1024; // 2MB

            // MIME,拡張子,サイズチェック
            if (!in_array($mime, $allowed_mimes, true) || !in_array($ext, $allowed_exts, true) || $size > $max_size) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('KTPWP: 不正なファイルアップロード検出 (MIME: ' . $mime . ', EXT: ' . $ext . ', SIZE: ' . $size . ')');
                }
                return $default_image_url;
            }

            // jpeg以外はjpegに変換して保存
            if ($mime !== 'image/jpeg') {
                $image = null;
                if ($mime === 'image/png') {
                    $image = imagecreatefrompng($file['tmp_name']);
                } elseif ($mime === 'image/gif') {
                    $image = imagecreatefromgif($file['tmp_name']);
                }
                if ($image) {
                    imagejpeg($image, $file_path, 90);
                    imagedestroy($image);
                } else {
                    return $default_image_url;
                }
            } else {
                move_uploaded_file($file['tmp_name'], $file_path);
            }
            $plugin_url = plugin_dir_url(dirname(__FILE__));
            $image_url = $plugin_url . 'images/upload/' . $data_id . '.jpeg';
            return $image_url;
        }
          // カスタム画像ファイルが存在するか確認
        if (file_exists($upload_dir . $data_id . '.jpeg')) {
            $plugin_url = plugin_dir_url(dirname(__FILE__));
            return $plugin_url . 'images/upload/' . $data_id . '.jpeg';
        }
        
        // それ以外はデフォルト画像を返す
        return $default_image_url;
    }
}