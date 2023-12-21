document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('#ktp-tabs > div.tab');
    var contents = document.querySelectorAll('#tab-content > div.content');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            // すべてのタブとコンテンツを非アクティブに
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            // クリックされたタブをアクティブに
            tab.classList.add('active');
            var activeContentId = 'content-' + tab.id;
            var activeContent = document.getElementById(activeContentId);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });
});
