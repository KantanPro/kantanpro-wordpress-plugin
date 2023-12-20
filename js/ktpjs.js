document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('#ktp-tabs > div.tab');

    // タブのクリックイベント
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            // すべてのタブを非アクティブに
            tabs.forEach(t => t.classList.remove('active'));

            // クリックされたタブをアクティブに
            tab.classList.add('active');
        });
    });
});
