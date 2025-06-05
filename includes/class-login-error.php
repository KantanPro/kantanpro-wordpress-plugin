<?php

class Kantan_Login_Error {

    public function __construct() {
        // 必要なアクションやフィルターを追加
        add_shortcode('ktpwp_login_error', array($this, 'Error_View'));
    }

    // ログインしていない場合のエラービュー
    public function Error_View() {
        // ログインのリンク
        $login_link = esc_url(wp_login_url());

        // 表示する内容
        $content  = '<h3>' . esc_html__('KTPWPを利用するにはログインしてください。', 'ktpwp') . '</h3>';
        $content .= '<!--ログイン-->';
        $content .= '<p><font size="4"><a href="' . $login_link . '">' . esc_html__('ログイン', 'ktpwp') . '</a></font>　';
        $content .= '<font size="4"><a href="' . esc_url(home_url('/welcome-to-ktpwp/')) . '">' . esc_html__('ホームへ', 'ktpwp') . '</a></font></p>';

        return $content;
    }
}