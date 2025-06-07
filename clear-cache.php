<?php
/**
 * Clear PHP opcache and force plugin reload
 */

// Clear opcache if available
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "Opcache cleared\n";
} else {
    echo "Opcache not available\n";
}

// Check if KTPWP classes are loaded
echo "Current class status:\n";
echo "KTPWP_Ajax: " . (class_exists('KTPWP_Ajax') ? 'loaded' : 'not loaded') . "\n";
echo "KTPWP_Staff_Chat: " . (class_exists('KTPWP_Staff_Chat') ? 'loaded' : 'not loaded') . "\n";

// Force reload KTPWP classes
if (defined('KTPWP_PLUGIN_DIR')) {
    echo "KTPWP_PLUGIN_DIR: " . KTPWP_PLUGIN_DIR . "\n";

    // Force include AJAX class
    require_once KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-ajax.php';
    echo "KTPWP_Ajax class reloaded\n";

    // Force include Staff Chat class
    require_once KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-staff-chat.php';
    echo "KTPWP_Staff_Chat class reloaded\n";
} else {
    echo "KTPWP_PLUGIN_DIR not defined\n";
}

echo "Cache clear complete\n";
?>
