<script type="text/javascript">
window.onload = initialize;
function initialize() {
    document.getElementById( 'zengoBack' ).onclick = zengoBack;
    document.getElementById( 'zengoForward' ).onclick = zengoForward;
}
function zengoBack() {
    window.history.back();
}
function zengoForward() {
    window.history.forward();
}
</script>