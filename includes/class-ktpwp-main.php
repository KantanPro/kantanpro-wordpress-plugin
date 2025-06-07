<?php
/**
 * メインプラグインクラス
 *
 * プラグインの初期化とコーディネーション機能を提供
 *
 * @package KTPWP
 * @since 1.0.0
 */

// セキュリティ: 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * メインプラグインクラス
 *
 * 各専門クラスを統合してプラグイン全体をコーディネート
 */
class KTPWP_Main {

    /**
     * シングルトンインスタンス
     *
     * @var KTPWP_Main
     */
    private static $instance = null;

    /**
     * ローダークラスインスタンス
     *
     * @var KTPWP_Loader
     */
    private $loader;

    /**
     * セキュリティクラスインスタンス
     *
     * @var KTPWP_Security
     */
    private $security;

    /**
     * アセット管理クラスインスタンス
     *
     * @var KTPWP_Assets
     */
    private $assets;

    /**
     * ショートコード管理クラスインスタンス
     *
     * @var KTPWP_Shortcodes
     */
    private $shortcodes;

    /**
     * Ajax管理クラスインスタンス
     *
     * @var KTPWP_Ajax
     */
    private $ajax;

    /**
     * リダイレクト管理クラスインスタンス
     *
     * @var KTPWP_Redirect
     */
    private $redirect;

    /**
     * Contact Form 7連携クラスインスタンス
     *
     * @var KTPWP_Contact_Form
     */
    private $contact_form;

    /**
     * GitHub Updaterクラスインスタンス
     *
     * @var KTPWP_GitHub_Updater
     */
    private $github_updater;

    /**
     * データベース管理クラスインスタンス
     *
     * @var KTPWP_Database
     */
    private $database;

    /**
     * インスタンス取得
     *
     * @return KTPWP_Main
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * フック初期化
     */
    private function init_hooks() {
        // プラグイン初期化
        add_action( 'plugins_loaded', array( $this, 'init' ) );

        // 翻訳ファイル読み込み
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    /**
     * プラグイン初期化
     */
    public function init() {
        // 専門クラスの初期化
        $this->init_components();

        // 初期化されたクラスのみ init() を呼び出す
        if ( $this->loader && method_exists( $this->loader, 'init' ) ) {
            $this->loader->init();
        }

        if ( $this->security && method_exists( $this->security, 'init' ) ) {
            $this->security->init();
        }

        if ( $this->assets && method_exists( $this->assets, 'init' ) ) {
            $this->assets->init();
        }

        // AJAX機能は自動的に初期化される（シングルトンパターン）
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'KTPWP_Main: init() completed successfully' );
        }

        // その他の機能初期化
        $this->init_additional_features();
    }

    /**
     * 専門クラスコンポーネントの初期化
     */
    private function init_components() {
        // スタッフチャットAJAX機能に必要な最小限のクラスのみ初期化
        try {
            // AJAXハンドラーの初期化（最優先）
            if ( class_exists( 'KTPWP_Ajax' ) ) {
                $this->ajax = KTPWP_Ajax::get_instance();
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPWP_Main: KTPWP_Ajax initialized successfully' );
                }
            }

            // アセット管理（シングルトンパターンでない場合は通常のインスタンス化）
            if ( class_exists( 'KTPWP_Assets' ) ) {
                if ( method_exists( 'KTPWP_Assets', 'get_instance' ) ) {
                    $this->assets = KTPWP_Assets::get_instance();
                } else {
                    $this->assets = new KTPWP_Assets();
                    $this->assets->init();
                }
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPWP_Main: KTPWP_Assets initialized successfully' );
                }
            }

            // 他のクラスは後で段階的に追加
            /*
            $this->loader = KTPWP_Loader::get_instance();
            $this->security = KTPWP_Security::get_instance();
            $this->shortcodes = KTPWP_Shortcodes::get_instance();
            $this->redirect = KTPWP_Redirect::get_instance();
            $this->contact_form = KTPWP_Contact_Form::get_instance();
            $this->github_updater = KTPWP_GitHub_Updater::get_instance();
            $this->database = KTPWP_Database::get_instance();
            */
        } catch ( Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'KTPWP_Main init_components error: ' . $e->getMessage() );
            }
        }
    }

    /**
     * 追加機能の初期化
     */
    private function init_additional_features() {
        // プラグインリファレンス機能
        if ( class_exists( 'KTPWP_Plugin_Reference' ) ) {
            KTPWP_Plugin_Reference::get_instance();
        }

        // Contact Form 7連携はKTPWP_Contact_Formクラスで自動初期化される
    }

    /**
     * 翻訳ファイル読み込み
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'ktpwp', false, dirname( plugin_basename( KTPWP_PLUGIN_FILE ) ) . '/languages/' );
    }

    /**
     * プラグイン有効化時の処理
     */
    public function activate() {
        // データベースクラスを使用してテーブル作成
        if ($this->database) {
            $this->database->setup_tables();
        } else {
            // フォールバック: 従来の方法
            $this->create_tables();
        }

        // 設定クラスのアクティベート処理
        if ( class_exists( 'KTP_Settings' ) ) {
            KTP_Settings::activate();
        }

        // プラグインリファレンス更新処理
        if ( class_exists( 'KTPWP_Plugin_Reference' ) ) {
            KTPWP_Plugin_Reference::on_plugin_activation();
        }
    }

    /**
     * テーブル作成処理
     */
    private function create_tables() {
        // 各クラスでテーブル作成
        if ( class_exists( 'Kntan_Client_Class' ) ) {
            $client = new Kntan_Client_Class();
            $client->Create_Table( 'client' );
        }

        if ( class_exists( 'Kntan_Service_Class' ) ) {
            $service = new Kntan_Service_Class();
            $service->Create_Table( 'service' );
        }

        if ( class_exists( 'Kantan_Supplier_Class' ) ) {
            $supplier = new Kantan_Supplier_Class();
            $supplier->Create_Table( 'supplier' );
        }

        if ( class_exists( 'KTPWP_Setting_Class' ) ) {
            $setting = new KTPWP_Setting_Class();
            $setting->Create_Table( 'setting' );
        }
    }

    /**
     * プラグイン無効化時の処理
     */
    public function deactivate() {
        // 必要に応じて無効化処理を追加
    }

    /**
     * ローダーインスタンスを取得
     *
     * @return KTPWP_Loader
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * セキュリティインスタンスを取得
     *
     * @return KTPWP_Security
     */
    public function get_security() {
        return $this->security;
    }

    /**
     * アセットインスタンスを取得
     *
     * @return KTPWP_Assets
     */
    public function get_assets() {
        return $this->assets;
    }

    /**
     * ショートコードインスタンスを取得
     *
     * @return KTPWP_Shortcodes
     */
    public function get_shortcodes() {
        return $this->shortcodes;
    }

    /**
     * Ajaxインスタンスを取得
     *
     * @return KTPWP_Ajax
     */
    public function get_ajax() {
        return $this->ajax;
    }

    /**
     * リダイレクトインスタンスを取得
     *
     * @return KTPWP_Redirect
     */
    public function get_redirect() {
        return $this->redirect;
    }

    /**
     * Contact Formインスタンスを取得
     *
     * @return KTPWP_Contact_Form
     */
    public function get_contact_form() {
        return $this->contact_form;
    }

    /**
     * GitHub Updaterインスタンスを取得
     *
     * @return KTPWP_GitHub_Updater
     */
    public function get_github_updater() {
        return $this->github_updater;
    }

    /**
     * データベースインスタンスを取得
     *
     * @return KTPWP_Database
     */
    public function get_database() {
        return $this->database;
    }
}
