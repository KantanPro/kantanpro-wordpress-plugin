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
        // 保存先ディレクトリを images/default/upload/ に固定
        $upload_dir = dirname(__FILE__) . '/../images/default/upload/';
        // ディレクトリがなければ作成
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        // 保存ファイル名
        $file_path = $upload_dir . $data_id . '.jpeg';
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            move_uploaded_file($_FILES['image']['tmp_name'], $file_path);
            // URL生成
            $plugin_url = plugin_dir_url(dirname(__FILE__));
            $image_url = $plugin_url . 'images/default/upload/' . $data_id . '.jpeg';
            return $image_url;
        }
        
        // カスタム画像ファイルが存在するか確認
        if (file_exists($upload_dir . $data_id . '.jpeg')) {
            $plugin_url = plugin_dir_url(dirname(__FILE__));
            return $plugin_url . 'images/default/upload/' . $data_id . '.jpeg';
        }
        
        // それ以外はデフォルト画像を返す
        return $default_image_url;
    }
}