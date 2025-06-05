<?php
/**
 * Plugin Reference class for KTPWP plugin
 *
 * Handles plugin reference/help documentation display
 * with real-time updates and user-friendly interface.
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

if ( ! class_exists( 'KTPWP_Plugin_Reference' ) ) {

/**
 * Plugin Reference class for managing help documentation
 *
 * @since 1.0.0
 */
class KTPWP_Plugin_Reference {

    /**
     * Single instance of the class
     *
     * @var KTPWP_Plugin_Reference
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return KTPWP_Plugin_Reference
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        add_action( 'wp_ajax_ktpwp_get_reference', array( $this, 'ajax_get_reference' ) );
        add_action( 'wp_ajax_nopriv_ktpwp_get_reference', array( $this, 'ajax_get_reference' ) );
        add_action( 'wp_ajax_ktpwp_clear_reference_cache', array( $this, 'ajax_clear_reference_cache' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_reference_scripts' ) );
    }

    /**
     * Enqueue scripts and styles for reference modal
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_reference_scripts() {
        if ( is_user_logged_in() ) {
            wp_enqueue_script(
                'ktpwp-reference',
                plugins_url( 'js/plugin-reference.js', dirname( __FILE__ ) ),
                array( 'jquery' ),
                '1.0.0',
                true
            );

            wp_add_inline_script(
                'ktpwp-reference',
                'var ktpwp_reference = ' . json_encode(array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'ktpwp_reference_nonce' ),
                    'strings'  => array(
                        'modal_title'         => esc_html__( 'プラグインリファレンス', 'ktpwp' ),
                        'loading'             => esc_html__( '読み込み中...', 'ktpwp' ),
                        'error_loading'       => esc_html__( 'コンテンツの読み込みに失敗しました。', 'ktpwp' ),
                        'close'               => esc_html__( '閉じる', 'ktpwp' ),
                        'nav_overview'        => esc_html__( '概要', 'ktpwp' ),
                        'nav_tabs'            => esc_html__( 'タブ機能', 'ktpwp' ),
                        'nav_shortcodes'      => esc_html__( 'ショートコード', 'ktpwp' ),
                        'nav_settings'        => esc_html__( '設定', 'ktpwp' ),
                        'nav_security'        => esc_html__( 'セキュリティ', 'ktpwp' ),
                        'nav_troubleshooting' => esc_html__( 'トラブルシューティング', 'ktpwp' ),
                    )
                )) . ';'
            );
        }
    }

    /**
     * Generate reference link for header
     *
     * @since 1.0.0
     * @return string HTML for reference link
     */
    public function get_reference_link() {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        $reference_icon = '<span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">help</span>';
        
        return '<a href="#" id="ktpwp-reference-trigger" class="ktpwp-reference-link" '
            . 'title="' . esc_attr__( 'プラグインの使い方を確認', 'ktpwp' ) . '" '
            . 'style="color: #0073aa; text-decoration: none; margin-left: 8px; display: inline-flex; align-items: center; gap: 4px;">'
            . $reference_icon
            . '<span>' . esc_html__( 'ヘルプ', 'ktpwp' ) . '</span>'
            . '</a>';
    }

    /**
     * Ajax handler for getting reference content
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_get_reference() {
        // Security check
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ktpwp_reference_nonce' ) ) {
            wp_die( esc_html__( 'セキュリティチェックに失敗しました。', 'ktpwp' ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'ログインが必要です。', 'ktpwp' ) );
        }

        $section = isset( $_POST['section'] ) ? sanitize_text_field( $_POST['section'] ) : 'overview';
        
        // Check if reference needs refresh after activation
        if ( get_option( 'ktpwp_reference_needs_refresh', false ) ) {
            delete_option( 'ktpwp_reference_needs_refresh' );
            delete_transient( 'ktpwp_reference_cache' );
        }
        
        $content = $this->get_reference_content( $section );
        
        wp_send_json_success( array(
            'content' => $content,
            'section' => $section,
            'last_updated' => get_option( 'ktpwp_reference_last_updated', time() ),
            'version' => get_option( 'ktpwp_reference_version', KTPWP_PLUGIN_VERSION )
        ) );
    }

    /**
     * Get reference content by section
     *
     * @since 1.0.0
     * @param string $section Reference section
     * @return string HTML content
     */
    private function get_reference_content( $section ) {
        // Check cache first (unless refresh is needed)
        $cache_key = "ktpwp_reference_content_{$section}";
        $cached_content = get_transient( $cache_key );
        
        if ( $cached_content !== false && ! get_option( 'ktpwp_reference_needs_refresh', false ) ) {
            return $cached_content;
        }
        
        $content = '';
        
        switch ( $section ) {
            case 'overview':
                $content = $this->get_overview_content();
                break;
            case 'tabs':
                $content = $this->get_tabs_content();
                break;
            case 'shortcode':
                $content = $this->get_shortcode_content();
                break;
            case 'settings':
                $content = $this->get_settings_content();
                break;
            case 'security':
                $content = $this->get_security_content();
                break;
            case 'troubleshooting':
                $content = $this->get_troubleshooting_content();
                break;
            default:
                $content = $this->get_overview_content();
                break;
        }
        
        // Cache the content for 1 hour
        if ( ! empty( $content ) ) {
            set_transient( $cache_key, $content, HOUR_IN_SECONDS );
        }
        
        return $content;
    }

    /**
     * Get overview content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_overview_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>' . esc_html__( 'KTPWPプラグイン概要', 'ktpwp' ) . '</h3>'
            . '<p>' . esc_html__( 'KTPWPは、WordPressでワークフロー管理を行うための包括的なプラグインです。', 'ktpwp' ) . '</p>'
            . '<div class="ktpwp-reference-features">'
            . '<h4>' . esc_html__( '主な機能', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( '受注・案件管理', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '顧客情報管理', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'サービス・商品管理', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '仕入れ先管理', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'レポート機能', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '設定管理', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-reference-getting-started">'
            . '<h4>' . esc_html__( '使い始める', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( 'プラグインを有効化したら、任意の固定ページに以下のショートコードを挿入してください：', 'ktpwp' ) . '</p>'
            . '<code style="background: #f5f5f5; padding: 8px; border-radius: 4px; display: inline-block; margin: 8px 0;">[ktpwp_all_tab]</code>'
            . '</div>'
            . '<div class="ktpwp-reference-updates">'
            . '<h4>' . esc_html__( 'リファレンス更新について', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( 'このリファレンスドキュメントは、プラグインの有効化時に自動的に更新されます。新機能を追加した場合、有効化または再有効化することで最新情報が反映されます。', 'ktpwp' ) . '</p>'
            . '<ul>'
            . '<li>' . esc_html__( 'プラグイン有効化時：リファレンスが自動更新', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'キャッシュシステム：高速な表示のためコンテンツをキャッシュ', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'バージョン管理：更新日時とバージョン情報を記録', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '</div>';
    }

    /**
     * Get tabs content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_tabs_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>' . esc_html__( 'タブ機能説明', 'ktpwp' ) . '</h3>'
            . '<div class="ktpwp-tabs-explanation">'
            . '<div class="ktpwp-tab-item">'
            . '<h4>' . esc_html__( '案件一覧タブ', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( '受注案件の一覧表示、進捗管理、検索・絞り込み機能を提供します。', 'ktpwp' ) . '</p>'
            . '</div>'
            . '<div class="ktpwp-tab-item">'
            . '<h4>' . esc_html__( '受注タブ', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( '新規受注の登録、案件詳細の編集、見積・請求書作成機能を提供します。', 'ktpwp' ) . '</p>'
            . '</div>'
            . '<div class="ktpwp-tab-item">'
            . '<h4>' . esc_html__( '顧客タブ', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( '顧客情報の登録・編集・検索、連絡先管理機能を提供します。', 'ktpwp' ) . '</p>'
            . '</div>'
            . '<div class="ktpwp-tab-item">'
            . '<h4>' . esc_html__( 'サービスタブ', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( '提供サービス・商品の登録・編集、価格設定機能を提供します。', 'ktpwp' ) . '</p>'
            . '</div>'
            . '<div class="ktpwp-tab-item">'
            . '<h4>' . esc_html__( '仕入れ先タブ', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( '仕入れ先・パートナー企業の管理機能を提供します。', 'ktpwp' ) . '</p>'
            . '</div>'
            . '<div class="ktpwp-tab-item">'
            . '<h4>' . esc_html__( 'レポートタブ', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( '売上分析、進捗レポート、グラフ表示機能を提供します。', 'ktpwp' ) . '</p>'
            . '</div>'
            . '<div class="ktpwp-tab-item">'
            . '<h4>' . esc_html__( '設定タブ', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( '税率設定、会社情報、印刷テンプレート設定機能を提供します。', 'ktpwp' ) . '</p>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Get shortcode content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_shortcode_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>' . esc_html__( 'ショートコード使用方法', 'ktpwp' ) . '</h3>'
            . '<div class="ktpwp-shortcode-explanation">'
            . '<h4>' . esc_html__( '基本ショートコード', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( 'メインのプラグイン機能を表示するには、以下のショートコードを使用してください：', 'ktpwp' ) . '</p>'
            . '<code style="background: #f5f5f5; padding: 12px; border-radius: 4px; display: block; margin: 12px 0; font-size: 16px;">[ktpwp_all_tab]</code>'
            . '<h4>' . esc_html__( '設置方法', 'ktpwp' ) . '</h4>'
            . '<ol>'
            . '<li>' . esc_html__( 'WordPress管理画面で「固定ページ」→「新規追加」をクリック', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ページタイトルを入力（例：「ワークフロー管理」）', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'エディタに [ktpwp_all_tab] を挿入', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '「公開」または「更新」をクリック', 'ktpwp' ) . '</li>'
            . '</ol>'
            . '<h4>' . esc_html__( '注意事項', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'ログインユーザーのみがプラグイン機能にアクセス可能です', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ページのパーマリンクは覚えやすいものに設定することをお勧めします', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ページのテンプレートは「デフォルト」または「全幅」を推奨します', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '</div>';
    }

    /**
     * Get settings content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_settings_content() {
        $settings_url = admin_url( 'admin.php?page=ktp-settings' );
        
        return '<div class="ktpwp-reference-content">'
            . '<h3>' . esc_html__( '設定ガイド', 'ktpwp' ) . '</h3>'
            . '<div class="ktpwp-settings-guide">'
            . '<p>' . esc_html__( 'プラグインの設定は管理画面から行えます。', 'ktpwp' ) . '</p>'
            . '<p><a href="' . esc_url( $settings_url ) . '" target="_blank" style="color: #0073aa;">' . esc_html__( '→ 設定ページを開く', 'ktpwp' ) . '</a></p>'
            . '<h4>' . esc_html__( '一般設定', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( '会社情報の登録', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '表示件数の設定', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '<h4>' . esc_html__( 'メール・SMTP設定', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'メール送信者アドレス設定', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'SMTP サーバー設定', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'テストメール送信機能', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '<h4>' . esc_html__( 'ライセンス設定', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'アクティベーションキーの入力', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '有償機能の有効化', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '</div>';
    }

    /**
     * Get security content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_security_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>' . esc_html__( 'セキュリティ機能', 'ktpwp' ) . '</h3>'
            . '<div class="ktpwp-security-features">'
            . '<p>' . esc_html__( 'KTPWPプラグインは以下のセキュリティ対策を実装しています：', 'ktpwp' ) . '</p>'
            . '<h4>' . esc_html__( '実装済みセキュリティ機能', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'SQLインジェクション防止（準備文・バインド変数使用）', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'XSS攻撃防止（データサニタイズ・エスケープ処理）', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'CSRF攻撃防止（WordPressノンス検証）', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ファイルアップロード検証', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ログインユーザー限定アクセス', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'REST API アクセス制限', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'HTTPセキュリティヘッダー設定', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '<h4>' . esc_html__( 'セキュリティのベストプラクティス', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'WordPress本体を最新版に保つ', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '強固なパスワードを使用する', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '不要なユーザーアカウントを削除する', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '定期的にバックアップを取る', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'SSL証明書を導入する', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '</div>';
    }

    /**
     * Get troubleshooting content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_troubleshooting_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>' . esc_html__( 'トラブルシューティング', 'ktpwp' ) . '</h3>'
            . '<div class="ktpwp-troubleshooting">'
            . '<h4>' . esc_html__( 'よくある問題と解決方法', 'ktpwp' ) . '</h4>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: ショートコードを挿入してもプラグインが表示されない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( 'ログインしているかを確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'プラグインが有効化されているかを確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ショートコードが正しく記述されているかを確認してください：[ktpwp_all_tab]', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: データが保存されない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( 'データベースの権限を確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'PHPのメモリ制限を確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'プラグインを一度無効化して再有効化してください', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: メール送信ができない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( 'SMTP設定を確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'テストメール送信機能を使用してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'レンタルサーバーのメール送信制限を確認してください', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<h4>' . esc_html__( 'デバッグモード', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( '問題が解決しない場合は、wp-config.phpでデバッグモードを有効にしてエラーログを確認してください：', 'ktpwp' ) . '</p>'
            . '<code style="background: #f5f5f5; padding: 8px; border-radius: 4px; display: block; margin: 8px 0;">define(\'WP_DEBUG\', true);<br>define(\'WP_DEBUG_LOG\', true);</code>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render reference modal HTML
     *
     * @since 1.0.0
     * @return string Modal HTML
     */
    public static function render_modal() {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        return '<div id="ktpwp-reference-modal" class="ktpwp-modal" style="display: none;">'
            . '<div class="ktpwp-modal-content">'
            . '<div class="ktpwp-modal-header">'
            . '<h2>' . esc_html__( 'KTPWPプラグインリファレンス', 'ktpwp' ) . '</h2>'
            . '<button class="ktpwp-modal-close" type="button">&times;</button>'
            . '</div>'
            . '<div class="ktpwp-modal-body">'
            . '<div class="ktpwp-reference-nav">'
            . '<ul>'
            . '<li><a href="#" data-section="overview" class="active">' . esc_html__( '概要', 'ktpwp' ) . '</a></li>'
            . '<li><a href="#" data-section="tabs">' . esc_html__( 'タブ機能', 'ktpwp' ) . '</a></li>'
            . '<li><a href="#" data-section="shortcode">' . esc_html__( 'ショートコード', 'ktpwp' ) . '</a></li>'
            . '<li><a href="#" data-section="settings">' . esc_html__( '設定', 'ktpwp' ) . '</a></li>'
            . '<li><a href="#" data-section="security">' . esc_html__( 'セキュリティ', 'ktpwp' ) . '</a></li>'
            . '<li><a href="#" data-section="troubleshooting">' . esc_html__( 'トラブルシューティング', 'ktpwp' ) . '</a></li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-reference-content-area">'
            . '<div id="ktpwp-reference-loading">' . esc_html__( '読み込み中...', 'ktpwp' ) . '</div>'
            . '<div id="ktpwp-reference-content"></div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Plugin activation hook for reference updates
     *
     * This method is called during plugin activation to ensure
     * reference documentation is properly initialized and updated.
     *
     * @since 1.0.0
     * @return void
     */
    public static function on_plugin_activation() {
        // Clear any cached reference data
        delete_transient( 'ktpwp_reference_cache' );
        
        // Update plugin reference metadata
        update_option( 'ktpwp_reference_last_updated', current_time( 'timestamp' ) );
        update_option( 'ktpwp_reference_version', KTPWP_PLUGIN_VERSION );
        
        // Log activation event for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'KTPWP: プラグインリファレンスが有効化時に更新されました。' );
        }
        
        // Force regeneration of reference content on next load
        update_option( 'ktpwp_reference_needs_refresh', true );
    }
}

// Initialize the plugin reference
KTPWP_Plugin_Reference::get_instance();

} // End if class_exists
