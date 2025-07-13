<?php
/**
 * Simple test file to verify PollMaster plugin functionality
 */

// Simulate WordPress environment
define('ABSPATH', __DIR__ . '/');
define('WPINC', 'wp-includes');

// Define plugin constants
define('POLLMASTER_VERSION', '1.0.0');
define('POLLMASTER_PLUGIN_DIR', __DIR__ . '/');
define('POLLMASTER_PLUGIN_URL', 'http://localhost:8000/');

// Mock WordPress functions
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        echo "Action added: $hook\n";
    }
}

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null) {
        echo "Menu page added: $menu_title\n";
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '') {
        echo "Submenu page added: $menu_title\n";
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $function) {
        echo "Activation hook registered\n";
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $function) {
        echo "Deactivation hook registered\n";
    }
}

if (!function_exists('register_uninstall_hook')) {
    function register_uninstall_hook($file, $function) {
        echo "Uninstall hook registered\n";
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        echo "Script enqueued: $handle\n";
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        echo "Style enqueued: $handle\n";
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $func) {
        echo "Shortcode registered: $tag\n";
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'test_nonce_' . md5($action);
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://localhost:8000/wp-admin/' . $path;
    }
}

echo "=== PollMaster Plugin Test ===\n\n";

try {
    // Test plugin file inclusion
    if (file_exists(__DIR__ . '/pollmaster.php')) {
        echo "✓ Main plugin file exists\n";
        
        // Include the main plugin file
        include_once __DIR__ . '/pollmaster.php';
        
        echo "✓ Plugin loaded successfully\n";
        
        // Test database class
        if (class_exists('PollMaster_Database')) {
            echo "✓ Database class exists\n";
        } else {
            echo "✗ Database class missing\n";
        }
        
        // Test admin class
        if (class_exists('PollMaster_Admin')) {
            echo "✓ Admin class exists\n";
        } else {
            echo "✗ Admin class missing\n";
        }
        
        // Test frontend class
        if (class_exists('PollMaster_Frontend')) {
            echo "✓ Frontend class exists\n";
        } else {
            echo "✗ Frontend class missing\n";
        }
        
        // Test AJAX class
        if (class_exists('PollMaster_Ajax')) {
            echo "✓ AJAX class exists\n";
        } else {
            echo "✗ AJAX class missing\n";
        }
        
        // Test shortcodes class
        if (class_exists('PollMaster_Shortcodes')) {
            echo "✓ Shortcodes class exists\n";
        } else {
            echo "✗ Shortcodes class missing\n";
        }
        
        echo "\n=== Plugin Test Complete ===\n";
        
    } else {
        echo "✗ Main plugin file not found\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
}

echo "\n=== CSS and JS Files Check ===\n";

$assets = [
    'assets/css/pollmaster-frontend.css',
    'assets/css/pollmaster-admin.css',
    'assets/js/pollmaster-frontend.js',
    'assets/js/pollmaster-admin.js'
];

foreach ($assets as $asset) {
    if (file_exists(__DIR__ . '/' . $asset)) {
        echo "✓ $asset exists\n";
    } else {
        echo "✗ $asset missing\n";
    }
}

echo "\n=== Test Complete ===\n";
?>