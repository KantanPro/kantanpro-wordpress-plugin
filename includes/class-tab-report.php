<?php

if (!class_exists('Kntan_Report_Class')) {
class Kntan_Report_Class {



    public function __construct() {
        // $this->name = 'report';
    }
    
    function Report_Tab_View( $tab_name ) {

        
        // 表示する内容
        // プレビュー用HTML（ダミー）
        $preview_html = "<div><strong>レポートプレビュー（ダミー）</strong><br>ここは [{$tab_name}] です。</div>";
        $preview_html_json = json_encode($preview_html);

        $content = <<<END
        <script>
        var isReportPreviewOpen = false;
        function printReportContent() {
            var printContent = $preview_html_json;
            var printWindow = window.open('', '_blank');
            printWindow.document.open();
            printWindow.document.write('<html><head><title>印刷</title></head><body>');
            printWindow.document.write(printContent);
            printWindow.document.write('<script>window.onafterprint = function(){ window.close(); }<\/script>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
        function toggleReportPreview() {
            var previewWindow = document.getElementById('reportPreviewWindow');
            var previewButton = document.getElementById('reportPreviewButton');
            if (isReportPreviewOpen) {
                previewWindow.style.display = 'none';
                previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="プレビュー">preview</span>';
                isReportPreviewOpen = false;
            } else {
                var printContent = $preview_html_json;
                previewWindow.innerHTML = printContent;
                previewWindow.style.display = 'block';
                previewButton.innerHTML = '<span class="material-symbols-outlined" aria-label="閉じる">close</span>';
                isReportPreviewOpen = true;
            }
        }
        </script>
        <div class="controller">
            <div class="printer">
                <div class="up-title">レポート：</div>
                <button id="reportPreviewButton" onclick="toggleReportPreview()" title="プレビュー">
                    <span class="material-symbols-outlined" aria-label="プレビュー">preview</span>
                </button>
                <button onclick="printReportContent()" title="印刷する">
                    <span class="material-symbols-outlined" aria-label="印刷">print</span>
                </button>
            </div>
        </div>
        <div id="reportPreviewWindow" style="display: none;"></div>
        END;

        // 有効化を確認
        $activation_key = get_site_option( 'ktp_activation_key' );
        if ( empty( $activation_key ) ) {
            $content .= <<<END
            各種統計データをレポートします。<br />
            END;
            return $content;
        } else {
            $content .= <<<END
            <span style='color:red;'>KTPWPの有効化ありがとうございます！</span><br />
            売上などのレポートを表示できます。<br />
            今、開発中なので、しばらくお待ちください。
            END;
            return $content;
        }
    }
}
} // class_exists