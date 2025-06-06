<?php
/**
 * URL Path Debug Test
 */

define('ABSPATH', '/Users/kantanpro/Local Sites/kantanpro-local-site/app/public/');
require_once ABSPATH . 'wp-load.php';

// Get current URL path
global $wp;
$current_url_path = home_url($wp->request);

// Output the URL path
echo "Current URL Path: " . $current_url_path . "\n";

// Log the URL path
error_log("DEBUG: Current URL Path: " . $current_url_path);
