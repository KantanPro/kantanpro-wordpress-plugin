<?php

class Kantan_Login_Error{

    // public $name;

    public function __construct() {
        // $name = "example"; // Assign a value to the variable $name
        // $this->$name;
        // add_action('');
        // add_filter('');
    }
    
    // ログインしていない場合
    function Error_View() {

        // ログインのリンク
        $login_link = wp_login_url();        // 表示する内容
        $content = '<h3>' . __('KTPWPを利用するにはログインしてください。', 'ktpwp') . '</h3>';
        $content .= '<!--ログイン-->';
        $content .= '<p><font size="4"><a href="' . $login_link . '">' . __('ログイン', 'ktpwp') . '</a></font>　';
        $content .= '<font size="4"><a href="/welcome-to-ktpwp/">' . __('ホームへ', 'ktpwp') . '</a></font></p>';
        return $content;
    }
    // function filter() {

    // }
}