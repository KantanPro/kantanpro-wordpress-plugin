<?php

class KTP_Login_Error {
    public function __construct() {
        add_filter('login_errors', array($this, 'customize_login_errors'));
    }

    public function customize_login_errors() {
        // ここでカスタムのログインエラーメッセージを設定します
        return "ログインに関するエラーが発生しました。もう一度お試しください。";
    }
}

// インスタンス化
new KTP_Login_Error();
