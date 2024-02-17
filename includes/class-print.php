<?php

class Print_Class {
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function generateHTML() {
        $html = '<html>';
        $html .= '<head>';
        $html .= '<title>Estimate</title>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<h1>Estimate</h1>';
        $html .= '<p>Customer: ' . $this->data['customer'] . '</p>';
        $html .= '<p>Amount: ' . $this->data['amount'] . '</p>';
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