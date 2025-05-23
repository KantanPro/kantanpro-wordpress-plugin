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
            $content .= '</div>';
        } else {
            // グラフ表示（ダミーデータ）
            $content .= '<div id="report_content" style="background:#fff;padding:32px 12px 32px 12px;max-width:900px;margin:32px auto 0 auto;border-radius:10px;box-shadow:0 2px 8px #eee;">';
            $content .= '<h3 style="margin-bottom:24px;">レポートグラフ（ダミーデータ）</h3>';
            $content .= '<div style="display:flex;flex-wrap:wrap;gap:32px;justify-content:center;">';
            $content .= '<div><canvas id="barChart" width="320" height="240"></canvas><div style="text-align:center;">棒グラフ</div></div>';
            $content .= '<div><canvas id="pieChart" width="320" height="240"></canvas><div style="text-align:center;">円グラフ</div></div>';
            $content .= '<div><canvas id="lineChart" width="320" height="240"></canvas><div style="text-align:center;">折れ線グラフ</div></div>';
            $content .= '<div><canvas id="stackedBarChart" width="320" height="240"></canvas><div style="text-align:center;">帯グラフ</div></div>';
            $content .= '</div>';
            $content .= '</div>';
            $content .= '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
            $content .= '<script>
// 色設定
const colors = ["#e74c3c", "#3498db", "#f1c40f", "#bdc3c7"];
// 棒グラフ
new Chart(document.getElementById("barChart"), {
    type: "bar",
    data: {
        labels: ["A", "B", "C", "D"],
        datasets: [{
            label: "売上",
            data: [120, 190, 300, 250],
            backgroundColor: colors,
            borderColor: "#fff",
            borderWidth: 1
        }]
    },
    options: {
        plugins: { legend: { labels: { color: "#333" } } },
        scales: {
            x: { grid: { color: "#eee" }, ticks: { color: "#333" } },
            y: { grid: { color: "#eee" }, ticks: { color: "#333" } }
        }
    }
});
// 円グラフ
new Chart(document.getElementById("pieChart"), {
    type: "pie",
    data: {
        labels: ["赤", "青", "黄", "グレー"],
        datasets: [{
            data: [30, 40, 20, 10],
            backgroundColor: colors,
            borderColor: "#fff",
            borderWidth: 2
        }]
    },
    options: {
        plugins: { legend: { labels: { color: "#333" } } }
    }
});
// 折れ線グラフ
new Chart(document.getElementById("lineChart"), {
    type: "line",
    data: {
        labels: ["1月", "2月", "3月", "4月"],
        datasets: [{
            label: "推移",
            data: [10, 25, 18, 32],
            borderColor: colors[0],
            backgroundColor: "rgba(231,76,60,0.1)",
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        plugins: { legend: { labels: { color: "#333" } } },
        scales: {
            x: { grid: { color: "#eee" }, ticks: { color: "#333" } },
            y: { grid: { color: "#eee" }, ticks: { color: "#333" } }
        }
    }
});
// 帯グラフ（積み上げ棒グラフ）
new Chart(document.getElementById("stackedBarChart"), {
    type: "bar",
    data: {
        labels: ["Q1", "Q2", "Q3", "Q4"],
        datasets: [
            { label: "赤", data: [10, 20, 30, 40], backgroundColor: colors[0] },
            { label: "青", data: [20, 10, 15, 25], backgroundColor: colors[1] },
            { label: "黄", data: [5, 15, 10, 20], backgroundColor: colors[2] },
            { label: "グレー", data: [8, 12, 6, 10], backgroundColor: colors[3] }
        ]
    },
    options: {
        plugins: { legend: { labels: { color: "#333" } } },
        scales: {
            x: { stacked: true, grid: { color: "#eee" }, ticks: { color: "#333" } },
            y: { stacked: true, grid: { color: "#eee" }, ticks: { color: "#333" } }
        }
    }
});
            </script>';
        }
        return $content;
    }
}
} // class_exists