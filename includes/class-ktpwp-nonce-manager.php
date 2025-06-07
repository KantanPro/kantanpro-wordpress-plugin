<?php
/**
 * ナンス管理クラス
 *
 * プラグイン全体でナンス値を統一管理
 *
 * @package KTPWP
 * @since 1.0.0
 */

// セキュリティ: 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ナンス管理クラス
 */
class KTPWP_Nonce_Manager {

    /**
     * シングルトンインスタンス
     *
     * @var KTPWP_Nonce_Manager
     */
    private static $instance = null;

    /**
     * ナンス値キャッシュ
     *
     * @var array
     */
    private static $nonce_cache = array();

    /**
     * シングルトンインスタンス取得
     *
     * @return KTPWP_Nonce_Manager
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * シングルトンインスタンス取得（エイリアス）
     * 一般的な命名規則との互換性のため
     *
     * @return KTPWP_Nonce_Manager
     */
    public static function getInstance() {
        return self::get_instance();
    }

    /**
     * コンストラクタ
     */
    private function __construct() {
        // シングルトンパターンのため、コンストラクタは非公開
    }

    /**
     * 統一されたstaff_chatナンス値を取得
     *
     * @return string ナンス値
     */
    public function get_staff_chat_nonce() {
        if (!isset(self::$nonce_cache['staff_chat'])) {
            self::$nonce_cache['staff_chat'] = wp_create_nonce('ktpwp_staff_chat_nonce');
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KTPWP Nonce Manager: Created unified staff_chat nonce: ' . self::$nonce_cache['staff_chat']);
            }
        }
        return self::$nonce_cache['staff_chat'];
    }

    /**
     * 統一されたAJAX設定データを取得
     *
     * @return array AJAX設定配列
     */
    public function get_unified_ajax_config() {
        return array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => array(
                'staff_chat' => $this->get_staff_chat_nonce()
            )
        );
    }

    /**
     * ナンスキャッシュをクリア（テスト用）
     */
    public function clear_cache() {
        self::$nonce_cache = array();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP Nonce Manager: Cache cleared');
        }
    }
}
