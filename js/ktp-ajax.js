jQuery(document).ready(function($) {
    // 顧客登録の処理
    $('#ktp-client-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post(ktp_ajax_object.ajax_url, formData, function(response) {
            if (response.success) {
                alert('顧客が登録されました');
                // ここでページの内容を更新する処理を追加する
            } else {
                alert('エラーが発生しました');
            }
        });
    });

    // 顧客削除の処理
    $('.ktp-delete-client').click(function() {
        var clientId = $(this).data('id');
        if (confirm('本当に削除しますか？')) {
            $.post(ktp_ajax_object.ajax_url, {
                action: 'ktp_delete_client',
                id: clientId
            }, function(response) {
                if (response.success) {
                    $('button[data-id="' + clientId + '"]').closest('tr').remove();
                } else {
                    alert('削除に失敗しました');
                }
            });
        }
    });
});
