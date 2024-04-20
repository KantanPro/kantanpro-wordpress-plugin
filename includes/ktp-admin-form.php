<?php

add_action( 'admin_menu', 'add_general_custom_fields' );

function add_general_custom_fields() {
    add_options_page(
        'カンタンProWPの設定', // page_title
        'カンタンProWP', // menu_title
        'administrator', // capability
        'ktp-admin', // menu_slug
        'display_plugin_admin_page', // function
        '', // icon_url
        3 // position
    );
    register_setting(
        'ktp-group', // option_group
        'ktp_activation_key' // option_name
    );
}

function active_ktp_validation( $input ) {
    $input = (int) $input;
    $activation_key = get_site_option( 'ktp_activation_key' );

    // 有効化キーが正しい場合のみ、入力を受け付ける
    if ( $activation_key === 'your_activation_key' && ( $input === 0 || $input === 1 ) ) {
        return $input;
    } else {
        add_settings_error(
            'active_ktp',
            'active-ktp-validation_error',
            __( 'illegal data', 'Hello_World' ),
            'error'
        );
    }
}

function display_plugin_admin_page() {
    $activation_key = get_site_option( 'ktp_activation_key' );
    $checked = get_site_option( 'active_ktp' );
    if( empty( $checked ) ){
        $checked = '';
    } else {
        $checked = 'checked="checked"';
    }
?>

<div class="wrap">

<h2>カンタンProWP設定</h2>

<form method="post" action="options.php">

<?php
settings_fields( 'ktp-group' );
do_settings_sections( 'default' );
?>

<table class="form-table">
<tbody>
<tr>
<th scope="row"><label for="ktp_activation_key">有効化キー</label></th>
<td>
<input type="text" id="ktp_activation_key" name="ktp_activation_key" value="<?php echo esc_attr( $activation_key ); ?>" />
</td>
</tr>
<!-- データインポートフォームの追加 -->
<tr>
<th scope="row"><label for="ktp_data_import">データインポート</label></th>
<td>
<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="ktp_import_data">
    <input type="hidden" name="file_path" value="<?php echo WP_PLUGIN_DIR . '/kantan-pro-wp/data'; ?>">
    <input type="file" id="ktp_data_import" name="ktp_data_import" accept=".csv" />
    <input type="submit" value="インポート" name="submit">
    <p class="description">インポートするデータファイルを選択してください。ファイルは <code><?php echo WP_PLUGIN_DIR . '/kantan-pro-wp/data'; ?></code> に格納されます。</p>
</form>
</td>
</tr>
<!-- データインポートフォームの追加ここまで -->
</tbody>
</table>

<?php submit_button(); // 設定を保存 ?>

</form>

</div><!-- .wrap -->

<?php
}

?>