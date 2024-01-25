document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('.tab_item');
    var contents = document.querySelectorAll('.tab_content');
    
    // ページ読み込み時に「仕事リスト」タブをアクティブにする
    var defaultTab = document.getElementById('tab-list');
    var defaultContent = document.getElementById('content-list');
    if (defaultTab && defaultContent) {
        defaultTab.classList.add('active');
        defaultContent.classList.add('active');
    }
});