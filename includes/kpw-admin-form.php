<?php
/*
WordPressの管理画面にプラグインメニューを追加するコードです。
↓ 以下のサイトを参考にしました。
OXY NOTES
https://oxynotes.com/?p=9384#4
*/

add_action( 'admin_menu', 'add_general_custom_fields' );

function add_general_custom_fields() {
    add_options_page(
        'カンタンProWPのタイトル', // page_title
        'カンタンProWP', // menu_title
        'administrator', // capability
        'kpw-admin', // menu_slug
        'display_plugin_admin_page' // function
    );
    register_setting(
        'kpw-group', // option_group
        'active_kpw', // option_name
        'active_kpw_validation' // sanitize_callback
    );
}

function active_kpw_validation( $input ) {
    $input = (int) $input;
    if ( $input === 0 || $input === 1 ) {
        return $input;
    } else {
        add_settings_error(
            'active_kpw',
            'active-kpw-validation_error',
            __( 'illegal data', 'Hello_World' ),
            'error'
        );
    }
}

function display_plugin_admin_page() {
    $checked = get_site_option( 'active_kpw' );
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
settings_fields( 'hkpw-group' );
do_settings_sections( 'default' );
?>

<table class="form-table">
<tbody>
<tr>
<th scope="row"><label for="active_kpw">使用する機能</label></th>
<td>
<input type="hidden" name="active_kpw" value="0">
<label for="active_kpw"><input type="checkbox" id="active_kpw" name="active_kpw" size="30" value="1"<?php echo $checked; ?>/>顧客</input></label>
</td>
</tr>
</tbody>
</table>

<?php submit_button(); // 設定を保存 ?>

</form>

</div><!-- .wrap -->

<?php
}
?>