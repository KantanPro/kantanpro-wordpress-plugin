document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('#ktp-tabs > div.tab');
    var contents = document.querySelectorAll('#tab-content > div.content');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            tab.classList.add('active');
            var activeContent = document.getElementById('content-' + tab.id.split('-')[1]);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });
});

