<?php
/**
 * Test file to verify PollMaster admin access
 * Place this in your WordPress root directory and access via browser
 */

// Include WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Check if user is logged in and has admin capabilities
if (!is_user_logged_in()) {
    echo '<h1>❌ Not Logged In</h1>';
    echo '<p>Please log in to WordPress admin first.</p>';
    echo '<a href="' . admin_url() . '">Go to Admin</a>';
    exit;
}

if (!current_user_can('manage_options')) {
    echo '<h1>❌ Insufficient Permissions</h1>';
    echo '<p>You need administrator privileges to access PollMaster.</p>';
    echo '<p>Current user capabilities:</p>';
    echo '<ul>';
    $user = wp_get_current_user();
    foreach ($user->allcaps as $cap => $has) {
        if ($has) {
            echo '<li>' . esc_html($cap) . '</li>';
        }
    }
    echo '</ul>';
    exit;
}

echo '<h1>✅ PollMaster Admin Access Test</h1>';
echo '<p><strong>User:</strong> ' . wp_get_current_user()->display_name . '</p>';
echo '<p><strong>User ID:</strong> ' . get_current_user_id() . '</p>';
echo '<p><strong>Has manage_options:</strong> ' . (current_user_can('manage_options') ? 'Yes' : 'No') . '</p>';

// Check if plugin is active
if (!is_plugin_active('pollmaster/pollmaster.php') && !is_plugin_active('Poll Plugin/pollmaster.php')) {
    echo '<h2>❌ Plugin Not Active</h2>';
    echo '<p>PollMaster plugin is not activated. Please activate it first.</p>';
    echo '<a href="' . admin_url('plugins.php') . '">Go to Plugins</a>';
} else {
    echo '<h2>✅ Plugin Active</h2>';
    
    // Check if classes exist
    echo '<h3>Class Availability:</h3>';
    echo '<ul>';
    $classes = ['PollMaster', 'PollMaster_Admin', 'PollMaster_Database'];
    foreach ($classes as $class) {
        echo '<li>' . $class . ': ' . (class_exists($class) ? '✅ Available' : '❌ Missing') . '</li>';
    }
    echo '</ul>';
    
    // Test admin menu links
    echo '<h3>Admin Menu Links:</h3>';
    echo '<ul>';
    $links = [
        'Dashboard' => admin_url('admin.php?page=pollmaster'),
        'All Polls' => admin_url('admin.php?page=pollmaster-all-polls'),
        'Add Poll' => admin_url('admin.php?page=pollmaster-all-polls&action=add'),
        'Settings' => admin_url('admin.php?page=pollmaster-settings'),
        'Test Page' => admin_url('admin.php?page=pollmaster-test')
    ];
    
    foreach ($links as $name => $url) {
        echo '<li><a href="' . esc_url($url) . '" target="_blank">' . esc_html($name) . '</a></li>';
    }
    echo '</ul>';
    
    // Test database connection
    if (class_exists('PollMaster_Database')) {
        echo '<h3>Database Test:</h3>';
        try {
            $database = new PollMaster_Database();
            $stats = $database->get_dashboard_stats();
            echo '<p>✅ Database connection successful</p>';
            echo '<p>Stats: ' . json_encode($stats) . '</p>';
        } catch (Exception $e) {
            echo '<p>❌ Database error: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
}

echo '<hr>';
echo '<p><a href="' . admin_url() . '">← Back to WordPress Admin</a></p>';
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
ul {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
}
li {
    margin: 5px 0;
}
a {
    color: #0073aa;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>