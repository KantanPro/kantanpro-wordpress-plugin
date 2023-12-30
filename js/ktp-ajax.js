jQuery(document).ready(function($) {
    // 顧客登録フォームの送信処理
    $('#ktp-client-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&nonce=' + ktp_ajax_object.nonce; // ノンスを追加

        // Ajaxリクエストを送信
        $.post(ktp_ajax_object.ajax_url, formData, function(response) {
            if (response.success) {
                alert('顧客が登録されました');
                // 顧客リストを更新する
                updateCustomerList();
                // 顧客タブをアクティブにする
                activateCustomerTab();
            } else {
                var errorMessage = response.data && response.data.message ? response.data.message : '不明なエラーが発生しました';
                alert('エラーが発生しました: ' + errorMessage);
            }
        });
    });

    // 顧客リストを更新する関数
    function updateCustomerList() {
        // Ajaxリクエストを使用して顧客リストを取得し、ページに表示する
        $.get(ktp_ajax_object.ajax_url, { action: 'ktp_get_customer_list' }, function(response) {
            if (response.success) {
                $('#customer-list').html(response.data);
            } else {
                alert('顧客リストの取得に失敗しました');
            }
        });
    }

    // 顧客タブをアクティブにする関数
    function activateCustomerTab() {
        $('.tab').removeClass('active'); // 他のタブのアクティブ状態を解除
        $('#tab-client').addClass('active'); // 顧客タブをアクティブにする
        $('.content').hide(); // 他のコンテンツを非表示にする
        $('#content-client').show(); // 顧客コンテンツを表示する
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
