// ブラウザ表示地歴前後
let zengoBack = document.getElementById('zengoBack');
    zengoBack.addEventListener('click', function(){
        history.back();
    });
    
let zengoForward = document.getElementById('zengoForward');
zengoForward.addEventListener('click', function(){
    history.forward();
});
