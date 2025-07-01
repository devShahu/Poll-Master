<?php
/**
 * Plugin Name: PollMaster
 * Plugin URI: https://webech.com/
 * Description: A comprehensive polling system with shortcode integration for Elementor pop-ups, user-created polls, admin weekly polls, photo contests, and social sharing.
 * Version: 1.0.0
 * Author: Shahriar Rahman
 * Author URI: https://webech.com
 * Text Domain: pollmaster
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('POLLMASTER_VERSION', '1.0.0');
define('POLLMASTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('POLLMASTER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('POLLMASTER_PLUGIN_FILE', __FILE__);

/**
 * Main PollMaster Class
 */
class PollMaster {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('PollMaster', 'uninstall'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('pollmaster', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once POLLMASTER_PLUGIN_PATH . 'includes/class-pollmaster-database.php';
        require_once POLLMASTER_PLUGIN_PATH . 'includes/class-pollmaster-admin.php';
        require_once POLLMASTER_PLUGIN_PATH . 'includes/class-pollmaster-frontend.php';
        require_once POLLMASTER_PLUGIN_PATH . 'includes/class-pollmaster-ajax.php';
        require_once POLLMASTER_PLUGIN_PATH . 'includes/class-pollmaster-shortcodes.php';
        require_once POLLMASTER_PLUGIN_PATH . 'includes/class-pollmaster-cron.php';
        require_once POLLMASTER_PLUGIN_PATH . 'includes/class-pollmaster-elementor.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize database
        new PollMaster_Database();
        
        // Initialize admin
        if (is_admin()) {
            new PollMaster_Admin();
        }
        
        // Initialize frontend
        new PollMaster_Frontend();
        
        // Initialize AJAX
        new PollMaster_Ajax();
        
        // Initialize shortcodes
        new PollMaster_Shortcodes();
        
        // Initialize cron jobs
        new PollMaster_Cron();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $database = new PollMaster_Database();
        $database->create_tables();
        
        // Create past polls page
        $this->create_past_polls_page();
        
        // Schedule cron events
        if (!wp_next_scheduled('pollmaster_weekly_poll_check')) {
            wp_schedule_event(time(), 'weekly', 'pollmaster_weekly_poll_check');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('pollmaster_weekly_poll_check');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove database tables
        $database = new PollMaster_Database();
        $database->drop_tables();
        
        // Remove options
        delete_option('pollmaster_settings');
        delete_option('pollmaster_version');
        
        // Remove past polls page
        $page = get_page_by_path('past-polls');
        if ($page) {
            wp_delete_post($page->ID, true);
        }
    }
    
    /**
     * Create past polls page
     */
    private function create_past_polls_page() {
        $page_exists = get_page_by_path('past-polls');
        
        if (!$page_exists) {
            $page_data = array(
                'post_title'    => __('Past Polls', 'pollmaster'),
                'post_content'  => '[pollmaster_past_polls]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_name'     => 'past-polls'
            );
            
            wp_insert_post($page_data);
        }
    }
}

// Initialize the plugin
new PollMaster();