<?php

class Kantan_Order_Class {

    public $name;

    public function __construct($name = '') {
        $this->name = $name;
    }

    public function Order_Tab_View($tab_name) {
        return "<div>受注書タブ: {$tab_name}</div>";
    }

    // 必要に応じてテーブル作成
    public function Create_Table($tab_name = '') {
        // ここでDBテーブル作成など
        return true;
    }

    // View_Table メソッドを追加
    public function View_Table($tab_name = '') {
        return <<<HTML
        <h3>ここは [{$tab_name}] です。</h3>
        受注書のリストを表示します。
        <table>
            <tr><th>受注番号</th><th>顧客名</th><th>金額</th></tr>
            <tr><td>1001</td><td>株式会社サンプル</td><td>100,000円</td></tr>
            <tr><td>1002</td><td>テスト商事</td><td>250,000円</td></tr>
        </table>
        HTML;
    }
}