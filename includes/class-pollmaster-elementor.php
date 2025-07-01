<?php
/**
 * Elementor Widget Integration
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PollMaster Elementor Integration Class
 */
class PollMaster_Elementor {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'register_category'));
    }
    
    /**
     * Register widget category
     */
    public function register_category($elements_manager) {
        $elements_manager->add_category(
            'pollmaster',
            [
                'title' => __('PollMaster', 'pollmaster'),
                'icon' => 'fa fa-chart-bar',
            ]
        );
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        // Include widget files
        require_once POLLMASTER_PLUGIN_PATH . 'includes/elementor/class-pollmaster-poll-widget.php';
        require_once POLLMASTER_PLUGIN_PATH . 'includes/elementor/class-pollmaster-popup-widget.php';
        
        // Register widgets
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new PollMaster_Poll_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new PollMaster_Popup_Widget());
    }
}

/**
 * Initialize Elementor integration if Elementor is active
 */
function pollmaster_init_elementor() {
    if (did_action('elementor/loaded')) {
        new PollMaster_Elementor();
    }
}
add_action('init', 'pollmaster_init_elementor');

/**
 * Check if Elementor is installed and activated
 */
function pollmaster_is_elementor_active() {
    return did_action('elementor/loaded');
}

/**
 * Admin notice if Elementor is not active
 */
function pollmaster_elementor_notice() {
    if (!pollmaster_is_elementor_active()) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p>' . __('PollMaster: Elementor is not active. Some features may not work properly.', 'pollmaster') . '</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'pollmaster_elementor_notice');