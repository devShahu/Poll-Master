<?php
/**
 * Plugin Name: PollMaster
 * Plugin URI: https://webech.com/
 * Description: A comprehensive polling system with shortcode integration for Elementor pop-ups, user-created polls, admin weekly polls, photo contests, and social sharing.
 * Version: 1.1.0
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
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
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
        require_once POLLMASTER_PLUGIN_PATH . 'includes/class-pollmaster-pages.php';
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
        
        // Initialize Elementor integration
        new PollMaster_Elementor();
        
        // Initialize pages
        new PollMaster_Pages();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Include database class for activation
        require_once POLLMASTER_PLUGIN_PATH . 'includes/class-pollmaster-database.php';
        
        // Create database tables
        $database = new PollMaster_Database();
        $database->create_tables();
        
        // Upgrade database if needed
        $database->upgrade_database();
        
        // Set default options
        $default_settings = array(
            'primary_color' => '#3b82f6',
            'secondary_color' => '#1e40af',
            'show_results_after_vote' => true,
            'require_login' => false,
            'popup_enabled' => true,
            'popup_delay' => 3000
        );
        
        add_option('pollmaster_settings', $default_settings);
        add_option('pollmaster_version', POLLMASTER_VERSION);
        
        // Create all necessary pages
        $this->create_default_pages();
        
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
        // Include database class for uninstall
        require_once POLLMASTER_PLUGIN_PATH . 'includes/class-pollmaster-database.php';
        
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
     * Create default pages
     */
    private function create_default_pages() {
        $pages = [
            [
                'title' => __('All Polls', 'pollmaster'),
                'slug' => 'all-polls',
                'content' => $this->get_all_polls_content()
            ],
            [
                'title' => __('Past Polls', 'pollmaster'),
                'slug' => 'past-polls', 
                'content' => $this->get_past_polls_content()
            ],
            [
                'title' => __('Create Poll', 'pollmaster'),
                'slug' => 'create-poll',
                'content' => $this->get_create_poll_content()
            ],
            [
                'title' => __('Poll Dashboard', 'pollmaster'),
                'slug' => 'poll-dashboard',
                'content' => $this->get_dashboard_content()
            ]
        ];
        
        foreach ($pages as $page) {
            $page_exists = get_page_by_path($page['slug']);
            
            if (!$page_exists) {
                $page_data = array(
                    'post_title'    => $page['title'],
                    'post_content'  => $page['content'],
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_name'     => $page['slug']
                );
                
                wp_insert_post($page_data);
            }
        }
    }
    
    /**
     * Get all polls page content
     */
    private function get_all_polls_content() {
        return '
        <style>
        .entry-content, .page-content {
            max-width: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .pollmaster-full-width {
            width: 100vw !important;
            max-width: 100vw !important;
            margin-left: calc(50% - 50vw) !important;
            margin-right: calc(50% - 50vw) !important;
            padding: 0 20px;
            box-sizing: border-box;
        }
        </style>
        <div class="pollmaster-full-width">
            <div class="pollmaster-page-hero" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 60px 40px; border-radius: 16px; margin-bottom: 40px; text-align: center; color: white;">
                <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 16px; color: white;">🗳️ All Polls</h1>
                <p style="font-size: 1.2rem; opacity: 0.9; margin: 0;">Discover and participate in all available polls</p>
            </div>
            [pollmaster_poll_list type="all" show_filters="true" per_page="12"]
        </div>
        ';
    }
    
    /**
     * Get past polls page content
     */
    private function get_past_polls_content() {
        return '
        <div class="pollmaster-page-hero" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 60px 40px; border-radius: 16px; margin-bottom: 40px; text-align: center; color: white;">
            <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 16px; color: white;">📊 Past Polls</h1>
            <p style="font-size: 1.2rem; opacity: 0.9; margin: 0;">View results from completed polls and contests</p>
        </div>
        [pollmaster_poll_list type="past" show_results="true" per_page="12"]
        ';
    }
    
    /**
     * Get create poll page content
     */
    private function get_create_poll_content() {
        return '
        <div class="pollmaster-page-hero" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 60px 40px; border-radius: 16px; margin-bottom: 40px; text-align: center; color: white;">
            <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 16px; color: white;">✨ Create Your Poll</h1>
            <p style="font-size: 1.2rem; opacity: 0.9; margin: 0;">Share your questions with the world and get instant feedback</p>
        </div>
        [pollmaster_create_poll]
        ';
    }
    
    /**
     * Get dashboard page content
     */
    private function get_dashboard_content() {
        return '
        <div class="pollmaster-page-hero" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); padding: 60px 40px; border-radius: 16px; margin-bottom: 40px; text-align: center; color: #333;">
            <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 16px; color: #333;">📈 Your Dashboard</h1>
            <p style="font-size: 1.2rem; opacity: 0.8; margin: 0;">Manage your polls and view detailed analytics</p>
        </div>
        [pollmaster_user_dashboard]
        ';
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=pollmaster-settings') . '">' . __('Settings', 'pollmaster') . '</a>';
        $dashboard_link = '<a href="' . admin_url('admin.php?page=pollmaster') . '">' . __('Dashboard', 'pollmaster') . '</a>';
        $test_link = '<a href="' . admin_url('admin.php?page=pollmaster-test') . '" style="color: #0073aa; font-weight: bold;">' . __('Test Plugin', 'pollmaster') . '</a>';
        
        array_unshift($links, $test_link, $dashboard_link, $settings_link);
        return $links;
    }
    
    /**
     * Add plugin row meta
     */
    public function add_plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $row_meta = array(
                'docs' => '<a href="https://webech.com/pollmaster-docs" target="_blank">' . __('Documentation', 'pollmaster') . '</a>',
                'support' => '<a href="https://webech.com/support" target="_blank">' . __('Support', 'pollmaster') . '</a>',
                'demo' => '<a href="' . home_url('/past-polls') . '" target="_blank">' . __('View Demo', 'pollmaster') . '</a>',
                'status' => '<a href="' . admin_url('admin.php?page=pollmaster-test') . '" style="color: #d63638; font-weight: bold;">' . __('Plugin Status', 'pollmaster') . '</a>'
            );
            return array_merge($links, $row_meta);
        }
        return $links;
    }
}

// Initialize the plugin
new PollMaster();