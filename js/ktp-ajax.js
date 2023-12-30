jQuery(document).ready(function($) {
    $('#ktp-client-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&nonce=' + ktp_ajax_object.nonce; // ノンスを追加

        $.post(ktp_ajax_object.ajax_url, formData, function(response) {
            if (response.success) {
                alert('顧客が登録されました');
                // ここでページの内容を更新する処理を追加する
            } else {
                var errorMessage = response.data && response.data.message ? response.data.message : '不明なエラーが発生しました';
                alert('エラーが発生しました: ' + errorMessage);
            }
        });
    });

    // 顧客削除の処理
    $(document).on('click', '.ktp-delete-client', function() {
        var clientId = $(this).data('id');
        if (confirm('本当に削除しますか？')) {
            $.post(ktp_ajax_object.ajax_url, {
                action: 'ktp_delete_client',
                id: clientId,
                nonce: ktp_ajax_object.nonce
            }, function(response) {
                if (response.success) {
                    $('button[data-id="' + clientId + '"]').closest('tr').remove();
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : '不明なエラーが発生しました';
                    alert('削除に失敗しました: ' + errorMessage);
                }
            });
        }
    });
});
