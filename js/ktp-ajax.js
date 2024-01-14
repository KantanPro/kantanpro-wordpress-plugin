// ブラウザ表示地歴前後
let zengoBack = document.getElementById('zengoBack');
    zengoBack.addEventListener('click', function(){
        history.back();
    });

let zengoForward = document.getElementById('zengoForward');
zengoForward.addEventListener('click', function(){
    history.forward();
});

// 非同期通信
jQuery(document).ready(function($) {
    $('#button').click(function(){
        $.ajax({
            url: ajaxurl, // WordPressが自動的に定義するAjax URL
            type: 'POST',
            data: {
                action: 'my_action', // サーバー側で処理を行う関数にマッピングされるアクション名
                // 他のデータ...
            },
            success: function(response) {
                // レスポンスを処理...
            }
        });
    });
});