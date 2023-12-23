document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('#ktp-tabs > div.tab');
    var contents = document.querySelectorAll('#tab-content > div.content');

    // タブのクリックイベントハンドラー
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            // 他のすべてのタブとコンテンツから 'active' クラスを削除
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            // クリックされたタブと対応するコンテンツに 'active' クラスを追加
            tab.classList.add('active');
            var activeContent = document.getElementById('content-' + tab.id.split('-')[1]);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });

    // ページ読み込み時に「仕事リスト」タブをアクティブにする
    var defaultTab = document.getElementById('tab-list');
    var defaultContent = document.getElementById('content-list');
    if (defaultTab && defaultContent) {
        defaultTab.classList.add('active');
        defaultContent.classList.add('active');
    }
});
