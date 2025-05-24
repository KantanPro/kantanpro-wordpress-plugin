<?php
/**
 * Kantan Client Class Extended
 *
 * @package Kantan_Pro_WP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-kantan-client-class.php';

/**
 * Class Kantan_Client_Class_Extended
 */
class Kantan_Client_Class_Extended extends Kantan_Client_Class {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Create Table.
	 * このメソッドは親クラスで未定義の場合に備えて明示的に定義しています。
	 */
	public function Create_Table() {
		// テーブル作成処理が未実装の場合でも、エラーを回避するため空実装
		return true;
	}

	// Add your additional methods and properties here.
}