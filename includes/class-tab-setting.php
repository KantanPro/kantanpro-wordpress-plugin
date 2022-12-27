<?php

class Kntan_Setting_Class {

    public $name;

    public function __construct() {
        $this->$name;
        // add_action('');
        // add_filter('');
    }
    
    function Setting_Tab_View( $name ) {

        // ログインユーザー情報を取得
        global $current_user;
        $login_user = $current_user->nickname;

        // ログアウトのリンク
        $logout_link = wp_logout_url();

        // 表示する内容
        $content = <<<END
        <h3>ここは [$name] です。</h3>
        各種設定ができます。
        END;
        return $content;
    }
}

?>