window.onload = function() {
    document.getElementById( 'zengoBack' ).onclick = zengoBack;
    document.getElementById( 'zengoForward' ).onclick = zengoForward;
};

function zengoBack() {
    // 履歴を1つ前に戻る
    window.history.back();
}

function zengoForward() {
    // 履歴を1つ進める
    window.history.forward();
}
