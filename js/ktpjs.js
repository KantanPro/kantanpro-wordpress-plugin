document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('#ktp-tabs > div.tab');
    var contents = document.querySelectorAll('#tab-content > div.content');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            // すべてのタブとコンテンツを非アクティブに
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            // クリックされたタブと対応するコンテンツをアクティブに
            tab.classList.add('active');
            var activeContent = document.querySelector('#content-' + tab.id.split('-')[1]);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });
});
