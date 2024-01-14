document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('.tab_item');
    var contents = document.querySelectorAll('.tab_content');

    // 各タブに対してクリックイベントハンドラーを設定［BETA】
    // tabs.forEach(function(tab) {
    //     tab.addEventListener('click', function(e) {
    //         // クリックされた要素がテキストリンクでない場合
    //         if (e.target.tagName !== 'A') {
    //             // 他のすべてのタブとコンテンツから 'active' クラスを削除
    //             tabs.forEach(t => t.classList.remove('active'));
    //             contents.forEach(c => c.classList.remove('active'));

    //             // クリックされたタブと対応するコンテンツに 'active' クラスを追加
    //             tab.classList.add('active');
    //             var activeContent = document.getElementById('content-' + tab.id.split('_content')[0]);
    //             if (activeContent) {
    //                 activeContent.classList.add('active');
    //             }

    //             // // URLにタブ名を追加
    //             // var tabName = tab.id.split('_content')[0];
    //             // window.history.pushState({}, '', '?tab_name=' + tabName);
    //             // イベントをキャンセル
    //             e.preventDefault();
    //             // 以降の処理をスキップ
    //             return;
    //         }
            
    //         // else{

    //         // }
    //     });
    // });

    // ページ読み込み時に「仕事リスト」タブをアクティブにする
    var defaultTab = document.getElementById('tab-list');
    var defaultContent = document.getElementById('content-list');
    if (defaultTab && defaultContent) {
        defaultTab.classList.add('active');
        defaultContent.classList.add('active');
    }
});

// フォームの表示・非表示を切り替えるボタン
document.querySelectorAll('.toggle-button').forEach(function(button) {
    button.addEventListener('click', function() {
        var form = this.parentNode.nextElementSibling;
        var isFormVisible = form.style.display !== 'none';
        form.style.display = isFormVisible ? 'none' : 'block';
        this.textContent = isFormVisible ? '+' : '-';
    });
});