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

// ログアウト時にログイン中のユーザーを表示する
jQuery(document).ready(function($) {
    $('#logout_link').click(function(e) {
        e.preventDefault();

        $.post(ajaxurl, { action: 'get_logged_in_users' }, function(response) {
            var users = JSON.parse(response);
            var users_html = users.join('、');
            $('.ktp_header').html(users_html);
        });

        window.location.href = $(this).attr('href');
    });
});

// ポップアップのスタイルを設定する
popupElement.style.position = 'fixed';
popupElement.style.top = '50%';
popupElement.style.left = '50%';
popupElement.style.transform = 'translate(-50%, -50%)';
popupElement.style.backgroundColor = 'rgba(0,0,0,0.8)';
popupElement.style.color = '#fff';
popupElement.style.padding = '40px';
popupElement.style.zIndex = '1500';
popupElement.style.width = '90%';
popupElement.style.maxWidth = '650px';
popupElement.style.border = '2px solid #444';
popupElement.style.borderRadius = '10px';
popupElement.style.boxShadow = '0 8px 16px rgba(0,0,0,0.2)';
popupElement.style.textAlign = 'center';
popupElement.style.fontFamily = '"Helvetica Neue", Helvetica, Arial, sans-serif';
