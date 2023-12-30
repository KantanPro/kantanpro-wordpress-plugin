jQuery(document).ready(function($) {
    // 顧客登録の処理
    $('#ktp-client-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post(ktp_ajax_object.ajax_url, formData, function(response) {
            if (response.success) {
                alert('顧客が登録されました');
                // ここでページの内容を更新する処理を追加する
                // 例えば、顧客リストを再読み込みするなど
            } else {
                alert('エラーが発生しました: ' + response.data.message);
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
                    alert('削除に失敗しました');
                }
            });
        }
    });
});
