// 案件名インライン編集・自動保存
jQuery(document).ready(function($) {
  $(document).on('blur', '#order_project_name_inline', function() {
    var $input = $(this);
    var newName = $input.val();
    var orderId = $input.data('order-id');
    if (typeof orderId === 'undefined' || orderId === '') return;
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'ktp_update_project_name',
        order_id: orderId,
        project_name: newName
      },
      success: function(res) {
        // 必要ならフィードバックを表示
        $input.addClass('autosaved');
        setTimeout(function(){ $input.removeClass('autosaved'); }, 800);
      },
      error: function() {
        $input.addClass('autosave-error');
        setTimeout(function(){ $input.removeClass('autosave-error'); }, 1200);
      }
    });
  });
});
