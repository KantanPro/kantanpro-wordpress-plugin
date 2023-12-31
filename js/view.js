window.onload = function() {
    document.getElementById( 'zengoBack' ).onclick = zengoBack;
    document.getElementById( 'zengoForward' ).onclick = zengoForward;
};

function zengoBack() {
    window.history.back();
}

function zengoForward() {
    window.history.forward();
}
