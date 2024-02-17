<?php

class Print_Class {
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function generateHTML() {
        $html = <<<HTML
        </br>
        <p>
        〒{$this->data['postal_code']}</br>
        {$this->data['prefecture']}{$this->data['city']}{$this->data['address']}</br>
        {$this->data['customer']}</br>
        {$this->data['user_name']} 様</br>
        </br></br></br></br></br>
        <hr>
        KanTanProWP</br>
        </p>
        HTML;

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