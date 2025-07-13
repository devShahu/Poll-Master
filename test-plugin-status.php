<?php
/**
 * Test Plugin Status
 * 
 * This file helps diagnose plugin issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if plugin constants are defined
echo "<h2>Plugin Status Check</h2>";
echo "<ul>";
echo "<li>POLLMASTER_VERSION: " . (defined('POLLMASTER_VERSION') ? POLLMASTER_VERSION : 'NOT DEFINED') . "</li>";
echo "<li>POLLMASTER_PLUGIN_URL: " . (defined('POLLMASTER_PLUGIN_URL') ? POLLMASTER_PLUGIN_URL : 'NOT DEFINED') . "</li>";
echo "<li>POLLMASTER_PLUGIN_PATH: " . (defined('POLLMASTER_PLUGIN_PATH') ? POLLMASTER_PLUGIN_PATH : 'NOT DEFINED') . "</li>";
echo "</ul>";

// Check if classes exist
echo "<h3>Class Status</h3>";
echo "<ul>";
echo "<li>PollMaster: " . (class_exists('PollMaster') ? 'EXISTS' : 'NOT FOUND') . "</li>";
echo "<li>PollMaster_Database: " . (class_exists('PollMaster_Database') ? 'EXISTS' : 'NOT FOUND') . "</li>";
echo "<li>PollMaster_Admin: " . (class_exists('PollMaster_Admin') ? 'EXISTS' : 'NOT FOUND') . "</li>";
echo "<li>PollMaster_Elementor: " . (class_exists('PollMaster_Elementor') ? 'EXISTS' : 'NOT FOUND') . "</li>";
echo "<li>PollMaster_Poll_Widget: " . (class_exists('PollMaster_Poll_Widget') ? 'EXISTS' : 'NOT FOUND') . "</li>";
echo "<li>PollMaster_Popup_Widget: " . (class_exists('PollMaster_Popup_Widget') ? 'EXISTS' : 'NOT FOUND') . "</li>";
echo "</ul>";

// Check if Elementor is active
echo "<h3>Elementor Status</h3>";
echo "<ul>";
echo "<li>Elementor Active: " . (did_action('elementor/loaded') ? 'YES' : 'NO') . "</li>";
echo "<li>Elementor Plugin Class: " . (class_exists('\\Elementor\\Plugin') ? 'EXISTS' : 'NOT FOUND') . "</li>";
echo "</ul>";

// Check database tables
if (class_exists('PollMaster_Database')) {
    global $wpdb;
    echo "<h3>Database Tables</h3>";
    echo "<ul>";
    
    $tables = [
        'pollmaster_polls',
        'pollmaster_votes',
        'pollmaster_contest_winners',
        'pollmaster_shares'
    ];
    
    foreach ($tables as $table) {
        $full_table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
        echo "<li>$full_table_name: " . ($exists ? 'EXISTS' : 'NOT FOUND') . "</li>";
    }
    echo "</ul>";
}

// Check for PHP errors
echo "<h3>PHP Error Check</h3>";
echo "<ul>";
echo "<li>PHP Version: " . PHP_VERSION . "</li>";
echo "<li>WordPress Version: " . get_bloginfo('version') . "</li>";
echo "<li>Error Reporting: " . (error_reporting() ? 'ON' : 'OFF') . "</li>";
echo "</ul>";

// Test shortcode registration
echo "<h3>Shortcode Status</h3>";
echo "<ul>";
$shortcodes = ['pollmaster_popup', 'pollmaster_poll', 'pollmaster_results', 'pollmaster_past_polls', 'pollmaster_create_poll'];
foreach ($shortcodes as $shortcode) {
    echo "<li>[$shortcode]: " . (shortcode_exists($shortcode) ? 'REGISTERED' : 'NOT REGISTERED') . "</li>";
}
echo "</ul>";
?>