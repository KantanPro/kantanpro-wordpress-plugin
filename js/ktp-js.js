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

// 削除ボタンを押したときの確認ダイアログ
function confirmDelete(id) {
    var tab_name = "your_tab_name"; // Replace "your_tab_name" with the actual tab name
    var query_post = "your_query_post"; // Replace "your_query_post" with the actual query post
    if (confirm("Are you sure you want to delete this item?")) {
        window.location.href = "?tab_name=" + tab_name + "&data_id=" + id + "&query_post=" + query_post;
    }
}