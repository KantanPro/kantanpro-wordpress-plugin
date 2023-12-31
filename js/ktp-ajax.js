jQuery(document).ready(function($) {

    // タブのクリックイベントを処理
    $('.tab').click(function() {
        var tabId = $(this).attr('id'); // クリックされたタブのIDを取得

        // すべてのタブから 'active' クラスを削除し、クリックされたタブに 'active' クラスを追加
        $('.tab').removeClass('active');
        $('#' + tabId).addClass('active');

        // すべてのタブコンテンツを非表示にし、クリックされたタブに対応するコンテンツを表示
        $('.tab-content').hide();
        $('#content-' + tabId).show();

        // URLにタブのパラメーターを追加
        window.history.pushState(null, null, '?tab=' + tabId);
    });
    
    // 顧客登録フォームの送信処理
    $('#ktp-client-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=ktp_add_client&nonce=' + ktp_ajax_object.nonce;

        $.post(ktp_ajax_object.ajax_url, formData, function(response) {
            if (response.success) {
                // 顧客リストを更新する関数を呼び出す
                updateClientList();
                // 顧客タブをアクティブにし、そのコンテンツを表示する
                activateTab('tab-client');
                // URLに顧客タブのパラメーターを追加
                window.history.pushState(null, null, '?tab=tab-client');
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
                // 顧客リストのHTMLを更新
                $('#content-client').html(response.data);
            } else {
                alert('顧客リストの取得に失敗しました');
            }
        });
    }

    // タブをアクティブにする関数
    function activateTab(tabId) {
        $('.tab').removeClass('active');
        $('#' + tabId).addClass('active');
        $('.tab-content').hide();
        $('#content-' + tabId).show();
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
                    // URLにタブのパラメーターを更新
                    window.history.pushState(null, null, '?tab=tab-client');
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : '不明なエラーが発生しました';
                    alert('削除に失敗しました: ' + errorMessage);
                }
            });
        }
    });
});
