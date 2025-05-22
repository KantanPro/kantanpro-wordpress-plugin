<?php

if (!class_exists('Kntan_Report_Class')) {
class Kntan_Report_Class {

    public function __construct() {
        // $this->name = 'report';
    }
    
    function Report_Tab_View( $tab_name ) {
        // アクティベーションキー取得
        $activation_key = get_option( 'ktp_activation_key' );
        
        // コントローラー/プリンターセクションの共通部分
        $content = '<div class="controller">';
        $content .= '<div class="printer">';
        $content .= '<div class="up-title">レポート：</div>';
        $content .= '<button title="印刷する">';
        $content .= '<span class="material-symbols-outlined" aria-label="印刷">print</span>';
        $content .= '</button>';
        $content .= '<button title="PDF出力">';
        $content .= '<span class="material-symbols-outlined" aria-label="PDF">description</span>';
        $content .= '</button>';
        $content .= '</div>'; // .printer 終了
        $content .= '</div>'; // .controller 終了
        
        if ( empty( $activation_key ) ) {
            // キー未入力時のメッセージを表示
            $content .= '<div class="ktp-license-message">';
            $content .= '<span class="dashicons dashicons-warning"></span>';
            $content .= 'アクティベーションキーを入力してください。';
            $content .= '<p>レポート機能を利用するには、<a href="' . admin_url('admin.php?page=ktp-license') . '">ライセンス設定</a>からアクティベーションキーを設定してください。</p>';
            $content .= '</div>';        } else {
            // ライセンスキー確認メッセージ表示エリア
            $content .= '<div class="ktp-license-display ktp-license-success">';
            $content .= '<div class="ktp-license-key">';
            $content .= '<span class="dashicons dashicons-yes-alt"></span>';
            $content .= '<span class="ktp-license-thank-you">ライセンスキーを確認しました。ありがとうございます！</span>';
            $content .= '<span class="ktp-license-activated">アクティベーション済み</span>';
            $content .= '</div>';
            $content .= '</div>';
        }
          // スタイル
        $content .= '<style>
            .ktp-license-display {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-top: 100px;
                padding: 20px;
            }
            .ktp-license-key {
                text-align: center;
                padding: 20px 30px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .ktp-license-label {
                display: block;
                margin-bottom: 10px;
                font-size: 14px;
                font-weight: bold;
                color: #555;
            }
            .ktp-license-value {
                display: block;
                font-size: 18px;
                font-family: monospace;
                letter-spacing: 1px;
                color: #0073aa;
                background: #f5f5f5;
                padding: 10px 15px;
                border-radius: 3px;
                border: 1px dashed #ccc;
            }
            .ktp-license-success {
                margin-top: 50px;
            }
            .ktp-license-success .ktp-license-key {
                padding: 30px 40px;
                border: 1px solid #c3e6cb;
                background-color: #d4edda;
            }
            .ktp-license-success .dashicons {
                font-size: 48px;
                width: 48px;
                height: 48px;
                margin-bottom: 15px;
                color: #28a745;
            }
            .ktp-license-thank-you {
                display: block;
                font-size: 18px;
                font-weight: bold;
                color: #28a745;
                margin-bottom: 10px;
            }
            .ktp-license-activated {
                display: inline-block;
                background: #28a745;
                color: white;
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 12px;
                letter-spacing: 1px;
                margin-top: 10px;
            }
            .ktp-license-message {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 50px 20px;
                margin-top: 30px;
                font-size: 16px;
                color: #dc3232;
                background: #fef7f7;
                border: 1px solid #f9c9c9;
                border-radius: 5px;
            }
            .ktp-license-message .dashicons {
                font-size: 48px;
                width: 48px;
                height: 48px;
                margin-bottom: 15px;
            }
            .ktp-license-message p {
                margin-top: 15px;
                color: #666;
                font-size: 14px;
            }
            .ktp-license-message a {
                color: #0073aa;
                text-decoration: none;
                font-weight: bold;
            }
            .ktp-license-message a:hover {
                text-decoration: underline;
            }
        </style>';
        
        return $content;
    }
}
} // class_exists