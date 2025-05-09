<?php

class Kantan_Report_Class {

    // public $name;

    public function __construct() {
        // $this->name = 'report';
    }
    
    function Report_Tab_View( $tab_name ) {

        
        // 表示する内容
        $content = <<<END
        <h3>ここは [$tab_name] です。</h3>
        END;
        // return $content;

        // 有効化を確認
        $activation_key = get_site_option( 'ktp_activation_key' );
        if ( empty( $activation_key ) ) {
            $content .= <<<END
            カンタンProWPは有効化されていません。<br />
            WordPressの管理画面で、設定→カンタンProWPで有効化キーを設定してください。<br />
            売上などのレポートを表示できます。
            END;
            return $content;
        } else {
            $content .= <<<END
            <span style='color:red;'>カンタンProWPの有効化ありがとうございます！</span><br />
            売上などのレポートを表示できます。<br />
            今、開発中なので、しばらくお待ちください。
            END;
            return $content;
        }
    }
}