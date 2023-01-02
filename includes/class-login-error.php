<?php

class Kantan_Login_Error{

    public $name;

    public function __construct() {
        $this->$name;
        // add_action('');
        // add_filter('');
    }
    
    // ログインしていない場合
    function Error_View() {

        // ログインのリンク
        $login_link = wp_login_url(); 

        // 表示する内容
        $content = <<<END
        <h3>カンタンProを利用するにはログインしてください。</h3>
        <!--ログイン-->
        <p><font size="4"><a href="$login_link">ログイン</a></font>　
        <font size="4"><a href="/welcome-to-kantanprowp/">ホームへ</a></font></p>
        END;
        return $content;
    }
    // function filter() {

    // }
}

?>