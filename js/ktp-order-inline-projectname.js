// 案件名インライン編集・自動保存
jQuery(document).ready(function($) {
  // 保存処理を関数化
  function saveProjectName($input) {
    var newName = $input.val();
    var orderId = $input.data('order-id');
    if (typeof orderId === 'undefined' || orderId === '') return;
    $.ajax({
      url: (typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof ktp_ajax_object !== 'undefined' ? ktp_ajax_object.ajax_url : '')),
      type: 'POST',
      data: {
        action: 'ktp_update_project_name',
        order_id: orderId,
        project_name: newName,
        _wpnonce: (typeof ktpwp_inline_edit_nonce !== 'undefined' ? ktpwp_inline_edit_nonce.nonce : '')
      },
      success: function(res) {
        if (res && res.success) {
          $input.addClass('autosaved');
          setTimeout(function(){ $input.removeClass('autosaved'); }, 800);
        } else {
          $input.addClass('autosave-error');
          setTimeout(function(){ $input.removeClass('autosave-error'); }, 1200);
          if (res && res.data) {
            alert('保存エラー: ' + res.data);
          }
        }
      },
      error: function() {
        $input.addClass('autosave-error');
        setTimeout(function(){ $input.removeClass('autosave-error'); }, 1200);
      }
    });
  }

  // blur時に保存
  $(document).on('blur', '.order_project_name_inline', function() {
    saveProjectName($(this));
  });

  // Enterキー押下時にも保存（重複防止のためblurを発火）
  $(document).on('keydown', '.order_project_name_inline', function(e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
      e.preventDefault();
      $(this).blur();
    }
  });
});
