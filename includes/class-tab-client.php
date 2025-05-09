<?php

class Kantan_Client_Class {

    public $name;

    public function __construct($name = '') {
        $this->name = $name;
    }

    // 必要に応じてテーブル作成
    public function Create_Table($tab_name = '') {
        // ここでDBテーブル作成など
        return true;
    }

    // View_Table メソッドを追加
    public function View_Table($tab_name = '') {
        // サンプル顧客データ
        $clients = [
            ['id' => 1, 'name' => '田中太郎', 'email' => 'tanaka@example.com', 'tel' => '090-1111-2222'],
            ['id' => 2, 'name' => '鈴木花子', 'email' => 'suzuki@example.com', 'tel' => '080-3333-4444'],
        ];
        $html = <<<HTML
        <h3>ここは [{$tab_name}] です。</h3>
        顧客リストを表示します。
        <table>
            <tr><th>ID</th><th>顧客名</th><th>メール</th><th>電話番号</th></tr>
        HTML;
        foreach ($clients as $c) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($c['id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($c['name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($c['email']) . '</td>';
            $html .= '<td>' . htmlspecialchars($c['tel']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }
}