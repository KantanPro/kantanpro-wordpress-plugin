<?php

class Kantan_List_Class {

    public $name;

    public function __construct($name = '') {
        $this->name = $name;
        // add_action('');
        // add_filter('');
    }

    // Create_Table メソッドを追加
    public function Create_Table($tab_name = '') {
        // 必要に応じてテーブルHTMLを生成
        return '<table><tr><td>サンプルデータ</td></tr></table>';
    }

    // View_Table メソッドを追加
    public function View_Table($tab_name = '') {
        // ここでリストの内容を返す
        return <<<HTML
        <h3>ここは [{$tab_name}] です。</h3>
        仕事のリストを表示してワークフローを管理できます。
        <table>
            <tr><th>仕事名</th><th>担当者</th><th>期限</th></tr>
            <tr><td>サンプル案件A</td><td>山田</td><td>2025-05-10</td></tr>
            <tr><td>サンプル案件B</td><td>佐藤</td><td>2025-05-15</td></tr>
        </table>
        HTML;
    }

}