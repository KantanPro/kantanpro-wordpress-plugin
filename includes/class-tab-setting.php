<?php
/**
 * Setting class for KTPWP plugin
 *
 * Handles plugin settings including company information,
 * tax rates, and configuration management.
 *
 * @package KTPWP
 * @subpackage Includes
 * @since 1.0.0
 * @author Kantan Pro
 * @copyright 2024 Kantan Pro
 * @license GPL-2.0+
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'class-setting-db.php';
require_once plugin_dir_path( __FILE__ ) . 'class-setting-ui.php';
require_once plugin_dir_path( __FILE__ ) . 'class-setting-template.php';

if ( ! class_exists( 'KTPWP_Setting_Class' ) ) {

/**
 * Setting class for managing plugin settings
 *
 * @since 1.0.0
 */
class KTPWP_Setting_Class {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Constructor initialization
    }
    
    /**
     * Create settings table
     *
     * @since 1.0.0
     * @param string $tab_name Table name suffix
     * @return bool True on success, false on failure
     */
    public function Create_Table( $tab_name ) {
        return KTPWP_Setting_DB::create_table( $tab_name );
    }

    // Update_Table
    function Update_Table( $tab_name ) {
        KTPWP_Setting_DB::update_table( $tab_name );
    }
    
    function Setting_Tab_View( $tab_name ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ktp_' . $tab_name;

        // Fetch template content from the database
        $template_content = $wpdb->get_var( "SELECT template_content FROM $table_name" );

        // Handle form submission
        if ( isset( $_POST['template_content'] ) ) {
            $new_template_content = stripslashes( $_POST['template_content'] );
            $success = KTPWP_Setting_Template::save_template( $table_name, $new_template_content );

            if ( $success ) {
                $template_content = $new_template_content;
            } else {
                die('Error: データーベースの更新に失敗しました。');
            }
        }

        // Generate customer data (dummy or from database)
        $customer_data = [
            'customer' => 'ダミー顧客名',
            'postal_code' => '123-4567',
            'prefecture' => '東京都',
            'city' => '千代田区',
            'address' => '1-2-3',
            'building' => 'サンプルビル',
            'user_name' => '担当 太郎',
        ];

        $customer_table = $wpdb->prefix . 'ktp_customer';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$customer_table'" ) == $customer_table ) {
            $customer_data = [
                'customer' => $wpdb->get_var( "SELECT customer FROM {$customer_table} WHERE id = 1" ) ?: 'ダミー顧客名',
                'postal_code' => $wpdb->get_var( "SELECT postal_code FROM {$customer_table} WHERE id = 1" ) ?: '123-4567',
                'prefecture' => $wpdb->get_var( "SELECT prefecture FROM {$customer_table} WHERE id = 1" ) ?: '東京都',
                'city' => $wpdb->get_var( "SELECT city FROM {$customer_table} WHERE id = 1" ) ?: '千代田区',
                'address' => $wpdb->get_var( "SELECT address FROM {$customer_table} WHERE id = 1" ) ?: '1-2-3',
                'building' => $wpdb->get_var( "SELECT building FROM {$customer_table} WHERE id = 1" ) ?: 'サンプルビル',
                'user_name' => $wpdb->get_var( "SELECT user_name FROM {$customer_table} WHERE id = 1" ) ?: '担当 太郎',
            ];
        }

        // Generate template preview
        $template_preview = KTPWP_Setting_Template::generate_preview( $template_content, $customer_data );

        // Render UI
        $ui_content = KTPWP_Setting_UI::render_tab_view( $tab_name );

        // Add preview window right after controller (like order tab)
        $preview_content = !empty($template_preview) ? $template_preview : '<p>テンプレートが設定されていません。エディターでテンプレートを作成してください。</p>';
        $ui_content .= '<div id="settingPreviewWindow" style="display: none; margin-top: 20px; padding: 20px; border: 1px solid #ddd; background: #fff;">';
        $ui_content .= '<h3>テンプレートプレビュー</h3>';
        $ui_content .= '<div style="border: 1px solid #ccc; padding: 15px; background: #fafafa; min-height: 200px;">';
        $ui_content .= $preview_content;
        $ui_content .= '</div>';
        $ui_content .= '</div>';

        // Add template editor and preview
        ob_start();
        wp_editor( $template_content, 'template_content', [
            'textarea_name' => 'template_content',
            'media_buttons' => true,
            'tinymce' => [
                'height' => 400,
                'toolbar1' => 'formatselect bold italic underline | alignleft aligncenter alignright alignjustify | removeformat',
                'toolbar2' => 'styleselect | forecolor backcolor | table | charmap | pastetext | code',
                'wp_adv' => false,
            ],
        ] );
        $editor_content = ob_get_clean();

        $ui_content .= '<div id="Atena" class="tabcontent" style="display:none;">';
        $ui_content .= '<div class="setting_contents">';
        $ui_content .= '<div class="order_info_box box">';
        $ui_content .= '<div class="header_title">■ 宛名印刷テンプレート</div>';
        $ui_content .= '<form method="post" action="">';
        $ui_content .= '<div style="display: flex; align-items: flex-start; gap: 20px;">';
        $ui_content .= '<div class="data_editor_box" style="flex: 1;">';
        $ui_content .= $editor_content;
        $ui_content .= '<button type="submit" style="margin-top: 10px; display: flex; align-items: center; justify-content: center; padding: 8px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; width: 40px; height: 40px;">';
        $ui_content .= '<span class="material-symbols-outlined" style="font-size: 20px;">sync</span>';
        $ui_content .= '</button>';
        $ui_content .= '</div>';
        $ui_content .= '<div class="data_detail_box" style="flex: 1;">';
        $ui_content .= '<h4>置換ワード一覧</h4>';
        $ui_content .= '<table style="width: 100%; border-collapse: collapse;">';
        $ui_content .= '<tr><th style="border: 1px solid #ddd; padding: 8px; background: #f5f5f5;">置換ワード</th><th style="border: 1px solid #ddd; padding: 8px; background: #f5f5f5;">説明</th></tr>';
        $ui_content .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">_%postal_code%_</td><td style="border: 1px solid #ddd; padding: 8px;">郵便番号</td></tr>';
        $ui_content .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">_%prefecture%_</td><td style="border: 1px solid #ddd; padding: 8px;">都道府県</td></tr>';
        $ui_content .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">_%city%_</td><td style="border: 1px solid #ddd; padding: 8px;">市区町村</td></tr>';
        $ui_content .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">_%address%_</td><td style="border: 1px solid #ddd; padding: 8px;">番地</td></tr>';
        $ui_content .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">_%building%_</td><td style="border: 1px solid #ddd; padding: 8px;">建物</td></tr>';
        $ui_content .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">_%customer%_</td><td style="border: 1px solid #ddd; padding: 8px;">会社名｜屋号｜お名前</td></tr>';
        $ui_content .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">_%user_name%_</td><td style="border: 1px solid #ddd; padding: 8px;">担当者名</td></tr>';
        $ui_content .= '</table>';
        $ui_content .= '<p style="margin-top: 15px; font-size: 12px; color: #666;">※ 選択した顧客データに置換されます。<br />※ ショートコードを挿入ボタンは使用できません。</p>';
        $ui_content .= '</div>';
        $ui_content .= '</div>';
        $ui_content .= '</form>';
        $ui_content .= '</div>'; // .order_info_box 終了
        $ui_content .= '</div>'; // .setting_contents 終了
        $ui_content .= '</div>'; // #Atena 終了

        return $ui_content;
    }
}
} // class_exists