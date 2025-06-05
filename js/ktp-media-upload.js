jQuery(document).ready(function($) {
    
    // メディアアップロードボタンのクリックイベント
    $(document).on('click', '.ktp-upload-image', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var field = button.closest('.ktp-image-upload-field');
        var hiddenInput = field.find('input[type="hidden"]');
        var preview = field.find('.ktp-image-preview');
        var previewImg = field.find('#header_bg_image_preview');
        var removeBtn = field.find('.ktp-remove-image');
        
        // WordPress Media Library を開く
        var mediaUploader = wp.media({
            title: 'ヘッダー背景画像を選択',
            button: {
                text: '画像を選択'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // 画像が選択された時の処理
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // フィールドに値をセット
            hiddenInput.val(attachment.id);
            
            // プレビュー画像を更新
            previewImg.attr('src', attachment.url);
            preview.show();
            removeBtn.show();
            
            // ボタンのテキストを変更
            button.text('画像を変更');
        });
        
        // Media Library を開く
        mediaUploader.open();
    });
    
    // 画像削除ボタンのクリックイベント
    $(document).on('click', '.ktp-remove-image', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var field = button.closest('.ktp-image-upload-field');
        var hiddenInput = field.find('input[type="hidden"]');
        var preview = field.find('.ktp-image-preview');
        var uploadBtn = field.find('.ktp-upload-image');
        
        // 確認ダイアログ
        if (confirm('画像を削除しますか？')) {
            // フィールドをクリア
            hiddenInput.val('');
            
            // プレビューを非表示
            preview.hide();
            
            // ボタンのテキストを変更
            uploadBtn.text('画像をアップロード');
        }
    });
});
