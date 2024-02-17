<?php

class Print_Class {
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function generateHTML() {
        $html = '<html>';
        $html .= '<head>';
        $html .= '<title>見積書印刷</title>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<p></p>';
        $html .= '＜見積書＞';
        $html .= '<p>' . $this->data['customer'] . ' 様</br>';
        $html .= '価格 ' . $this->data['amount'] . '</p>';
        // Add more HTML generation code here based on your requirements
        $html .= '</body>';
        $html .= '</html>';

        return $html;
    }
}

// $data = array(
//     'customer' => 'John Doe',
//     'amount' => 1000
// );

// $printEstimate = new Print_Class($data);
// $html = $printEstimate->generateHTML();
// echo $html;

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

?>