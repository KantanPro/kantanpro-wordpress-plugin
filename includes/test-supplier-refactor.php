<?php
/**
 * Test file for supplier class refactoring
 *
 * This file contains basic tests to verify that the refactored
 * supplier classes work correctly.
 *
 * @package KTPWP
 * @subpackage Includes
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test class for supplier refactoring
 */
class KTPWP_Supplier_Test {

    /**
     * Run all tests
     *
     * @return array Test results
     */
    public static function run_tests() {
        $results = [];
        
        $results['class_exists'] = self::test_classes_exist();
        $results['instantiation'] = self::test_instantiation();
        $results['method_delegation'] = self::test_method_delegation();
        
        return $results;
    }

    /**
     * Test if all classes exist
     *
     * @return bool
     */
    private static function test_classes_exist() {
        return class_exists( 'KTPWP_Supplier_Class' ) &&
               class_exists( 'KTPWP_Supplier_Security' ) &&
               class_exists( 'KTPWP_Supplier_Data' );
    }

    /**
     * Test class instantiation
     *
     * @return bool
     */
    private static function test_instantiation() {
        try {
            $supplier = new KTPWP_Supplier_Class();
            return $supplier instanceof KTPWP_Supplier_Class;
        } catch ( Exception $e ) {
            error_log( 'KTPWP Test Error: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Test method delegation
     *
     * @return bool
     */
    private static function test_method_delegation() {
        try {
            $supplier = new KTPWP_Supplier_Class();
            
            // Test if methods are callable
            return method_exists( $supplier, 'set_cookie' ) &&
                   method_exists( $supplier, 'create_table' ) &&
                   method_exists( $supplier, 'Update_Table' ) &&
                   method_exists( $supplier, 'handle_operations' ) &&
                   method_exists( $supplier, 'View_Table' );
        } catch ( Exception $e ) {
            error_log( 'KTPWP Test Error: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Display test results
     *
     * @param array $results Test results
     */
    public static function display_results( $results ) {
        echo '<div class="ktpwp-test-results">';
        echo '<h3>サプライヤークラス リファクタリング テスト結果</h3>';
        
        foreach ( $results as $test => $result ) {
            $status = $result ? '✅ 成功' : '❌ 失敗';
            $test_name = self::get_test_name( $test );
            echo "<p><strong>{$test_name}:</strong> {$status}</p>";
        }
        
        $all_passed = ! in_array( false, $results, true );
        $overall_status = $all_passed ? '✅ すべてのテストが成功しました' : '❌ 一部のテストが失敗しました';
        echo "<p><strong>総合結果:</strong> {$overall_status}</p>";
        echo '</div>';
    }

    /**
     * Get human-readable test name
     *
     * @param string $test_key Test key
     * @return string
     */
    private static function get_test_name( $test_key ) {
        $names = [
            'class_exists' => 'クラス存在確認',
            'instantiation' => 'インスタンス化テスト',
            'method_delegation' => 'メソッド委譲テスト'
        ];
        
        return $names[ $test_key ] ?? $test_key;
    }
}

// Auto-run tests if this file is included and user has admin capabilities
if ( current_user_can( 'manage_options' ) && isset( $_GET['run_supplier_tests'] ) ) {
    add_action( 'admin_notices', function() {
        $results = KTPWP_Supplier_Test::run_tests();
        KTPWP_Supplier_Test::display_results( $results );
    });
}
