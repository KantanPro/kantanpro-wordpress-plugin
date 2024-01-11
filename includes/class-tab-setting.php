<?php

class Kntan_Setting_Class {

    public $name;

    public function __construct($name) {
        $this->name = 'setting';
    }
    
    function Setting_Tab_View() {

        // ログインユーザー情報を取得
        global $current_user;
        $login_user = $current_user->nickname;

        // ログアウトのリンク
        $logout_link = wp_logout_url();

        // 表示する内容
        $content = <<<END
        <h3>ここは [{$this->name}] です。</h3>
        各種設定ができます。
        END;
        return $content;
    }
}

?>