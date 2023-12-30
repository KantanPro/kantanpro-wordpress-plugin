jQuery(document).ready(function($) {
    // 顧客登録フォームの送信処理
    $('#ktp-client-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=ktp_add_client&nonce=' + ktp_ajax_object.nonce;

        $.post(ktp_ajax_object.ajax_url, formData, function(response) {
            if (response.success) {
                alert('顧客が登録されました');
                // 顧客リストを更新する処理を追加
                updateClientList();
            } else {
                var errorMessage = response.data && response.data.message ? response.data.message : '不明なエラーが発生しました';
                alert('エラーが発生しました: ' + errorMessage);
            }
        });
    });

    // 顧客リストを更新する関数
    function updateClientList() {
        $.get(ktp_ajax_object.ajax_url, { action: 'ktp_get_client_list' }, function(response) {
            if (response.success) {
                $('#client-list').html(response.data);
            } else {
                alert('顧客リストの取得に失敗しました');
            }
        });
    }

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
