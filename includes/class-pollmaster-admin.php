<?php
/**
 * Admin functionality for PollMaster
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_post_pollmaster_invite_manager', [$this, 'invite_manager']);
        add_action('admin_post_nopriv_pollmaster_invite_manager', [$this, 'invite_manager']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Handle manager invitation request
     */
    public function invite_manager() {
        check_admin_referer('pollmaster_invite_manager_nonce');

        $email = sanitize_email($_POST['invite_manager_email']);
        $existing_user = email_exists($email);

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Invalid email address']);
        }

        $manager_token = wp_generate_password(20, false);
        $invite_url = add_query_arg([
            'action' => 'pollmaster_manager_invite',
            'token' => $manager_token
        ], admin_url('admin.php'));


        $subject = 'You are invited to manage PollMaster';
        $message = sprintf(
            'You have been invited to manage polls. Click here to accept: %s',
            esc_url($invite_url)
        );

        wp_mail($email, $subject, $message);
        $pending_invites = get_option('pollmaster_pending_invites', []);
        $pending_invites[$email] = [
            'token' => $manager_token,
            'date' => current_time('mysql')
        ];
        update_option('pollmaster_pending_invites', $pending_invites);

        wp_send_json_success([
            'message' => 'Invitation sent successfully',
            'redirect' => admin_url('admin.php?page=pollmaster_settings')
        ]);
    }

    /**
     * Enqueue admin scripts/styles and localize AJAX data
     */
    public function enqueue_admin_assets($hook) {
        wp_enqueue_script(
            'pollmaster-admin',
            plugins_url('/assets/js/pollmaster-admin.js', POLLMASTER_PLUGIN_FILE),
            ['jquery'],
            POLLMASTER_VERSION,
            true
        );

        wp_localize_script('pollmaster-admin', 'pollmaster_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pollmaster_admin_nonce')
        ]);

        wp_enqueue_style(
            'pollmaster-admin',
            plugins_url('/assets/css/pollmaster-admin.css', POLLMASTER_PLUGIN_FILE),
            [],
            POLLMASTER_VERSION
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('PollMaster', 'pollmaster'),
            __('PollMaster', 'pollmaster'),
            'manage_options', // Changed to manage_options for proper access
            'pollmaster',
            array($this, 'admin_page_dashboard'),
            'dashicons-chart-bar',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'pollmaster',
            __('Dashboard', 'pollmaster'),
            __('Dashboard', 'pollmaster'),
            'manage_options',
            'pollmaster',
            array($this, 'admin_page_dashboard')
        );
        
        // All Polls submenu
        add_submenu_page(
            'pollmaster',
            __('All Polls', 'pollmaster'),
            __('All Polls', 'pollmaster'),
            'manage_options',
            'pollmaster-all-polls',
            array($this, 'admin_page_all_polls')
        );
        
        // Manage Polls submenu (hidden from menu but accessible)
        add_submenu_page(
            null, // Hidden from menu
            __('Manage Polls', 'pollmaster'),
            __('Manage Polls', 'pollmaster'),
            'manage_options',
            'pollmaster-manage-polls',
            array($this, 'admin_page_manage_polls')
        );
        
        // Add Poll submenu (hidden from menu but accessible)
        add_submenu_page(
            null, // Hidden from menu
            __('Add Poll', 'pollmaster'),
            __('Add Poll', 'pollmaster'),
            'manage_options',
            'pollmaster-add-poll',
            array($this, 'admin_page_add_poll')
        );
        
        // Edit Poll submenu (hidden from menu but accessible)
        add_submenu_page(
            null, // Hidden from menu
            __('Edit Poll', 'pollmaster'),
            __('Edit Poll', 'pollmaster'),
            'manage_options',
            'pollmaster-edit-poll',
            array($this, 'admin_page_edit_poll')
        );
        
        // Poll Results submenu (hidden from menu but accessible)
        add_submenu_page(
            null, // Hidden from menu
            __('Poll Results', 'pollmaster'),
            __('Poll Results', 'pollmaster'),
            'manage_options',
            'pollmaster-poll-results',
            array($this, 'admin_page_poll_results')
        );
        
        // Settings submenu
        add_submenu_page(
            'pollmaster',
            __('Settings', 'pollmaster'),
            __('Settings', 'pollmaster'),
            'manage_options',
            'pollmaster-settings',
            array($this, 'admin_page_settings')
        );
        
        // Test submenu
        add_submenu_page(
            'pollmaster',
            __('Plugin Test', 'pollmaster'),
            __('Plugin Test', 'pollmaster'),
            'manage_options',
            'pollmaster-test',
            array($this, 'admin_page_test')
        );
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Register settings
        register_setting('pollmaster_settings', 'pollmaster_settings');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on PollMaster pages
        if (strpos($hook, 'pollmaster') === false) {
            return;
        }
        
        // Enqueue WordPress media scripts for image uploader
        if (strpos($hook, 'pollmaster-add-poll') !== false || strpos($hook, 'pollmaster-edit-poll') !== false) {
            wp_enqueue_media();
        }
        
        // Enqueue Tailwind CSS
        wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), POLLMASTER_VERSION, false);
        
        // Enqueue DaisyUI
        wp_enqueue_style('daisyui', 'https://cdn.jsdelivr.net/npm/daisyui@4.4.24/dist/full.min.css', array(), POLLMASTER_VERSION);
        
        // Enqueue admin CSS
        wp_enqueue_style('pollmaster-admin', POLLMASTER_PLUGIN_URL . 'assets/css/pollmaster-admin.css', array(), POLLMASTER_VERSION);
        
        // Enqueue modern manage polls CSS for specific page
        if (strpos($hook, 'pollmaster-manage-polls') !== false || strpos($hook, 'pollmaster-all-polls') !== false) {
            wp_enqueue_style('pollmaster-modern-manage', POLLMASTER_PLUGIN_URL . 'assets/css/modern-manage-polls.css', array(), POLLMASTER_VERSION);
            wp_enqueue_script('pollmaster-manage-polls', POLLMASTER_PLUGIN_URL . 'assets/js/manage-polls.js', array('jquery'), POLLMASTER_VERSION, true);
        }
        
        // Enqueue admin JS
        wp_enqueue_script('pollmaster-admin', POLLMASTER_PLUGIN_URL . 'assets/js/pollmaster-admin.js', array('jquery'), POLLMASTER_VERSION, true);
        
        // Localize script
        wp_localize_script('pollmaster-admin', 'pollmaster_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pollmaster_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this poll?', 'pollmaster'),
                'error' => __('An error occurred. Please try again.', 'pollmaster'),
                'success' => __('Operation completed successfully.', 'pollmaster')
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function admin_page_dashboard() {
        include POLLMASTER_PLUGIN_PATH . 'templates/admin/dashboard.php';
    }
    
    /**
     * All Polls page
     */
    public function admin_page_all_polls() {
        // Handle actions
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $poll_id = isset($_GET['poll_id']) ? intval($_GET['poll_id']) : 0;
        
        switch ($action) {
            case 'add':
            case 'edit':
                include POLLMASTER_PLUGIN_PATH . 'templates/admin/add-edit-poll.php';
                break;
            case 'results':
                include POLLMASTER_PLUGIN_PATH . 'templates/admin/poll-results.php';
                break;
            case 'delete':
                $this->handle_delete_poll($poll_id);
                include POLLMASTER_PLUGIN_PATH . 'templates/admin/manage-polls.php';
                break;
            default:
                include POLLMASTER_PLUGIN_PATH . 'templates/admin/manage-polls.php';
                break;
        }
    }
    
    /**
     * Settings page
     */
    public function admin_page_settings() {
        include POLLMASTER_PLUGIN_PATH . 'templates/admin/settings.php';
    }
    
    /**
     * Test page
     */
    public function admin_page_test() {
        include POLLMASTER_PLUGIN_PATH . 'templates/admin/test.php';
    }
    
    /**
     * Manage Polls page
     */
    public function admin_page_manage_polls() {
        include POLLMASTER_PLUGIN_PATH . 'templates/admin/manage-polls.php';
    }
    
    /**
     * Add Poll page
     */
    public function admin_page_add_poll() {
        include POLLMASTER_PLUGIN_PATH . 'templates/admin/add-edit-poll.php';
    }
    
    /**
     * Edit Poll page
     */
    public function admin_page_edit_poll() {
        include POLLMASTER_PLUGIN_PATH . 'templates/admin/add-edit-poll.php';
    }
    
    /**
     * Poll Results page
     */
    public function admin_page_poll_results() {
        include POLLMASTER_PLUGIN_PATH . 'templates/admin/poll-results.php';
    }
    
    /**
     * Handle poll deletion
     */
    private function handle_delete_poll($poll_id) {
        if (!$poll_id || !current_user_can('manage_options')) {
            return;
        }
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_poll_' . $poll_id)) {
            wp_die(__('Security check failed.', 'pollmaster'));
        }
        
        $database = new PollMaster_Database();
        $result = $database->delete_poll($poll_id);
        
        if ($result) {
            // Use JavaScript redirect to avoid header issues
            echo '<script>window.location.href = "' . add_query_arg(array('message' => 'deleted'), admin_url('admin.php?page=pollmaster-all-polls')) . '";</script>';
            exit;
        } else {
            // Use JavaScript redirect to avoid header issues
            echo '<script>window.location.href = "' . add_query_arg(array('error' => 'delete_failed'), admin_url('admin.php?page=pollmaster-all-polls')) . '";</script>';
            exit;
        }
    }
    
    /**
     * Render modern polls interface
     */
    public function render_modern_polls_interface() {
        $database = new PollMaster_Database();
        $polls = $database->get_polls();
        
        ob_start();
        ?>
        <div class="pollmaster-modern-interface">
            <div class="polls-header">
                <h2><?php echo esc_html__('All Polls', 'pollmaster'); ?></h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-all-polls&action=add')); ?>" class="btn btn-primary"><?php echo esc_html__('Add New Poll', 'pollmaster'); ?></a>
            </div>
            
            <?php if (empty($polls)) : ?>
                <div class="no-polls-message">
                    <p><?php echo esc_html__('No polls found. Create your first poll!', 'pollmaster'); ?></p>
                </div>
            <?php else : ?>
                <div class="polls-grid">
                    <?php foreach ($polls as $poll) : ?>
                        <div class="poll-card">
                            <h3><?php echo esc_html($poll->title); ?></h3>
                            <p><?php echo esc_html($poll->description); ?></p>
                            <div class="poll-actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-all-polls&action=edit&poll_id=' . $poll->id)); ?>" class="btn btn-sm"><?php echo esc_html__('Edit', 'pollmaster'); ?></a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-all-polls&action=results&poll_id=' . $poll->id)); ?>" class="btn btn-sm"><?php echo esc_html__('Results', 'pollmaster'); ?></a>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pollmaster-all-polls&action=delete&poll_id=' . $poll->id), 'delete_poll_' . $poll->id)); ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo esc_js__('Are you sure?', 'pollmaster'); ?>')"><?php echo esc_html__('Delete', 'pollmaster'); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}