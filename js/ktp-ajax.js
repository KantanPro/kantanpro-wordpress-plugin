jQuery(document).ready(function($) {
    $('.ktp-delete-client').click(function() {
        var clientId = $(this).data('id');
        if (confirm('本当に削除しますか？')) {
            $.post(ktp_ajax_object.ajax_url, {
                action: 'ktp_delete_client',
                id: clientId
            }, function(response) {
                if (response.success) {
                    // 削除された要素をページから削除
                    $('button[data-id="' + clientId + '"]').closest('tr').remove();
                }
            });
        }
    });
});
