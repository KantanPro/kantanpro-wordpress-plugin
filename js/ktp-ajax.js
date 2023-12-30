jQuery(document).ready(function($) {
    // 顧客登録フォームの送信処理
    $('#ktp-client-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&nonce=' + ktp_ajax_object.nonce;

        $.post(ktp_ajax_object.ajax_url, formData, function(response) {
            if (response.success) {
                alert('顧客が登録されました');
                updateClientList();
                activateClientTab();
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

    function updateClientList() {
        $.get(ktp_ajax_object.ajax_url, { action: 'ktp_get_client_list' }, function(response) {
            if (response.success) {
                $('#client-list').html(response.data);
            } else {
                alert('顧客リストの取得に失敗しました');
            }
        });
    }

    function activateClientTab() {
        $('.tab').removeClass('active');
        $('#tab-client').addClass('active');
        $('.content').hide();
        $('#content-client').show();
    }
});
