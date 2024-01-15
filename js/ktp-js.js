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

// フォームの表示・非表示を切り替えるボタン
document.querySelectorAll('.toggle-button').forEach(function(button) {
    button.addEventListener('click', function() {
        var form = this.parentNode.nextElementSibling;
        var isFormVisible = form.style.display !== 'none';
        form.style.display = isFormVisible ? 'none' : 'block';
        this.textContent = isFormVisible ? '+' : '-';
    });
});