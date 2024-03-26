<?php

class Image_Processor {

    /**
     * 画像のアップロードと削除を行う
     * 
     * @param string $tab_name テーブル名
     * @param int $data_id データID
     * @param string $default_image_url デフォルト画像のURL
     * @return string $image_url 画像のURL
     */
    public function handle_image($tab_name, $data_id, $default_image_url) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;
        $upload_dir = plugin_dir_url(__FILE__) . '../images/service/';
        $image_url = '';

        // 画像のアップロード処理
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $file = $_FILES['image'];
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $data_id . '.' . $extension;
            $upload_file = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $upload_file)) {
                $image_url = $upload_file;
            }
        }

        // 画像の削除（デフォルト画像に戻す）
        if (isset($_POST['delete_image']) && $_POST['delete_image'] == 'true') {
            $image_url = $default_image_url;
        }

        // 画像URLを返す
        return $image_url;
    }
}

?>