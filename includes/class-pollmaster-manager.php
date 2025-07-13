<?php
/**
 * PollMaster Manager Class
 * 
 * Handles manager invitations, role management, and access control
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_post_pollmaster_invite_manager', array($this, 'handle_invite_manager'));
        add_action('admin_post_nopriv_pollmaster_invite_manager', array($this, 'handle_invite_manager'));
        add_action('admin_post_pollmaster_accept_invite', array($this, 'handle_accept_invite'));
        add_action('admin_post_nopriv_pollmaster_accept_invite', array($this, 'handle_accept_invite'));
        add_action('wp_ajax_pollmaster_remove_manager', array($this, 'handle_remove_manager'));
        add_action('admin_menu', array($this, 'restrict_admin_menu'), 999);
        add_action('admin_init', array($this, 'restrict_admin_access'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Create custom role for PollMaster managers
        $this->create_manager_role();
        
        // Handle invite acceptance from URL
        if (isset($_GET['action']) && $_GET['action'] === 'pollmaster_accept_invite') {
            $this->handle_invite_acceptance();
        }
    }
    
    /**
     * Create manager role
     */
    private function create_manager_role() {
        if (!get_role('pollmaster_manager')) {
            add_role('pollmaster_manager', 'PollMaster Manager', array(
                'read' => true,
                'manage_pollmaster' => true,
                'edit_polls' => true,
                'delete_polls' => true,
                'view_poll_results' => true,
                'manage_poll_settings' => false // Only admin can manage settings
            ));
        }
    }
    
    /**
     * Handle manager invitation
     */
    public function handle_invite_manager() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'pollmaster_invite_manager')) {
            wp_die('Security check failed.');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to invite managers.');
        }
        
        $email = sanitize_email($_POST['manager_email']);
        $name = sanitize_text_field($_POST['manager_name']);
        
        if (!is_email($email)) {
            wp_redirect(add_query_arg('error', 'invalid_email', admin_url('admin.php?page=pollmaster-settings&tab=access')));
            exit;
        }
        
        // Check if user already exists
        $existing_user = get_user_by('email', $email);
        
        if ($existing_user) {
            // User exists, just add the role
            $existing_user->add_role('pollmaster_manager');
            
            // Send notification email
            $this->send_role_assigned_email($existing_user, $name);
            
            wp_redirect(add_query_arg('success', 'manager_added', admin_url('admin.php?page=pollmaster-settings&tab=access')));
        } else {
            // User doesn't exist, create invitation
            $invite_token = wp_generate_password(32, false);
            $invite_data = array(
                'email' => $email,
                'name' => $name,
                'token' => $invite_token,
                'invited_by' => get_current_user_id(),
                'invited_at' => current_time('mysql'),
                'status' => 'pending'
            );
            
            // Store invitation
            $invitations = get_option('pollmaster_pending_invitations', array());
            $invitations[$email] = $invite_data;
            update_option('pollmaster_pending_invitations', $invitations);
            
            // Send invitation email
            $this->send_invitation_email($email, $name, $invite_token);
            
            wp_redirect(add_query_arg('success', 'invitation_sent', admin_url('admin.php?page=pollmaster-settings&tab=access')));
        }
        exit;
    }
    
    /**
     * Handle invite acceptance
     */
    public function handle_accept_invite() {
        $token = sanitize_text_field($_GET['token'] ?? '');
        
        if (!$token) {
            wp_die('Invalid invitation token.');
        }
        
        $invitations = get_option('pollmaster_pending_invitations', array());
        $invitation = null;
        $email = null;
        
        // Find invitation by token
        foreach ($invitations as $inv_email => $inv_data) {
            if ($inv_data['token'] === $token) {
                $invitation = $inv_data;
                $email = $inv_email;
                break;
            }
        }
        
        if (!$invitation) {
            wp_die('Invalid or expired invitation.');
        }
        
        // Check if user is logged in
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            
            if ($current_user->user_email === $email) {
                // Add manager role to current user
                $current_user->add_role('pollmaster_manager');
                
                // Remove invitation
                unset($invitations[$email]);
                update_option('pollmaster_pending_invitations', $invitations);
                
                wp_redirect(admin_url('admin.php?page=pollmaster&welcome=1'));
                exit;
            } else {
                wp_die('This invitation is for a different email address.');
            }
        } else {
            // Redirect to registration/login with invitation token
            $redirect_url = add_query_arg(array(
                'action' => 'register',
                'pollmaster_invite' => $token
            ), wp_login_url());
            
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Handle invite acceptance from URL
     */
    private function handle_invite_acceptance() {
        if (!isset($_GET['token'])) {
            return;
        }
        
        $token = sanitize_text_field($_GET['token']);
        $invitations = get_option('pollmaster_pending_invitations', array());
        
        foreach ($invitations as $email => $invitation) {
            if ($invitation['token'] === $token) {
                // Show acceptance form
                $this->show_invite_acceptance_form($invitation);
                exit;
            }
        }
    }
    
    /**
     * Show invite acceptance form
     */
    private function show_invite_acceptance_form($invitation) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Accept PollMaster Manager Invitation</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    margin: 0;
                    padding: 20px;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .invite-container {
                    background: white;
                    border-radius: 12px;
                    padding: 40px;
                    max-width: 500px;
                    width: 100%;
                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                    text-align: center;
                }
                .invite-header {
                    margin-bottom: 30px;
                }
                .invite-icon {
                    font-size: 4rem;
                    margin-bottom: 20px;
                }
                .invite-title {
                    font-size: 2rem;
                    font-weight: bold;
                    color: #2c3e50;
                    margin-bottom: 10px;
                }
                .invite-message {
                    color: #7f8c8d;
                    font-size: 1.1rem;
                    line-height: 1.6;
                    margin-bottom: 30px;
                }
                .invite-form {
                    text-align: left;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                .form-label {
                    display: block;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 8px;
                }
                .form-input {
                    width: 100%;
                    padding: 12px 16px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 16px;
                    transition: border-color 0.3s ease;
                }
                .form-input:focus {
                    outline: none;
                    border-color: #667eea;
                }
                .btn {
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    border: none;
                    padding: 14px 28px;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    width: 100%;
                    transition: transform 0.2s ease;
                }
                .btn:hover {
                    transform: translateY(-2px);
                }
                .login-link {
                    text-align: center;
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #e9ecef;
                }
                .login-link a {
                    color: #667eea;
                    text-decoration: none;
                    font-weight: 600;
                }
            </style>
        </head>
        <body>
            <div class="invite-container">
                <div class="invite-header">
                    <div class="invite-icon">🎯</div>
                    <h1 class="invite-title">You're Invited!</h1>
                    <p class="invite-message">
                        You've been invited to become a PollMaster Manager for <strong><?php echo get_bloginfo('name'); ?></strong>.
                        Please create your account to get started.
                    </p>
                </div>
                
                <form class="invite-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="pollmaster_complete_registration">
                    <input type="hidden" name="token" value="<?php echo esc_attr($invitation['token']); ?>">
                    <?php wp_nonce_field('pollmaster_complete_registration'); ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="user_name">Full Name</label>
                        <input type="text" id="user_name" name="user_name" class="form-input" 
                               value="<?php echo esc_attr($invitation['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="user_email">Email Address</label>
                        <input type="email" id="user_email" name="user_email" class="form-input" 
                               value="<?php echo esc_attr($invitation['email']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="user_password">Password</label>
                        <input type="password" id="user_password" name="user_password" class="form-input" 
                               minlength="8" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="user_password_confirm">Confirm Password</label>
                        <input type="password" id="user_password_confirm" name="user_password_confirm" 
                               class="form-input" minlength="8" required>
                    </div>
                    
                    <button type="submit" class="btn">Accept Invitation & Create Account</button>
                </form>
                
                <div class="login-link">
                    Already have an account? <a href="<?php echo wp_login_url(add_query_arg('pollmaster_invite', $invitation['token'], admin_url())); ?>">Sign in instead</a>
                </div>
            </div>
            
            <script>
                document.querySelector('.invite-form').addEventListener('submit', function(e) {
                    const password = document.getElementById('user_password').value;
                    const confirm = document.getElementById('user_password_confirm').value;
                    
                    if (password !== confirm) {
                        e.preventDefault();
                        alert('Passwords do not match. Please try again.');
                        return false;
                    }
                });
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Send invitation email
     */
    private function send_invitation_email($email, $name, $token) {
        $site_name = get_bloginfo('name');
        $invite_url = add_query_arg(array(
            'action' => 'pollmaster_accept_invite',
            'token' => $token
        ), home_url());
        
        $subject = sprintf('[%s] You\'re invited to be a PollMaster Manager', $site_name);
        
        $message = sprintf(
            "Hi %s,\n\n" .
            "You've been invited to become a PollMaster Manager for %s.\n\n" .
            "As a manager, you'll be able to:\n" .
            "• Create and manage polls\n" .
            "• View poll results and analytics\n" .
            "• Moderate poll content\n\n" .
            "To accept this invitation and create your account, click the link below:\n" .
            "%s\n\n" .
            "This invitation will expire in 7 days.\n\n" .
            "If you have any questions, please contact the site administrator.\n\n" .
            "Best regards,\n" .
            "The %s Team",
            $name,
            $site_name,
            $invite_url,
            $site_name
        );
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Send role assigned email
     */
    private function send_role_assigned_email($user, $name) {
        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=pollmaster');
        
        $subject = sprintf('[%s] You\'re now a PollMaster Manager', $site_name);
        
        $message = sprintf(
            "Hi %s,\n\n" .
            "Great news! You've been granted PollMaster Manager access for %s.\n\n" .
            "You can now access the PollMaster dashboard at:\n" .
            "%s\n\n" .
            "As a manager, you can:\n" .
            "• Create and manage polls\n" .
            "• View poll results and analytics\n" .
            "• Moderate poll content\n\n" .
            "Welcome to the team!\n\n" .
            "Best regards,\n" .
            "The %s Team",
            $name ?: $user->display_name,
            $site_name,
            $admin_url,
            $site_name
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Handle manager removal
     */
    public function handle_remove_manager() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        $user_id = intval($_POST['user_id']);
        $user = get_userdata($user_id);
        
        if (!$user) {
            wp_send_json_error(array('message' => 'User not found.'));
        }
        
        // Remove manager role
        $user->remove_role('pollmaster_manager');
        
        wp_send_json_success(array(
            'message' => 'Manager removed successfully.',
            'user_id' => $user_id
        ));
    }
    
    /**
     * Get all managers
     */
    public function get_managers() {
        $managers = get_users(array(
            'role' => 'pollmaster_manager',
            'fields' => array('ID', 'display_name', 'user_email', 'user_registered')
        ));
        
        return $managers;
    }
    
    /**
     * Get pending invitations
     */
    public function get_pending_invitations() {
        return get_option('pollmaster_pending_invitations', array());
    }
    
    /**
     * Restrict admin menu for managers
     */
    public function restrict_admin_menu() {
        $user = wp_get_current_user();
        
        if (in_array('pollmaster_manager', $user->roles) && !in_array('administrator', $user->roles)) {
            // Remove all menu items except PollMaster
            remove_menu_page('index.php'); // Dashboard
            remove_menu_page('edit.php'); // Posts
            remove_menu_page('upload.php'); // Media
            remove_menu_page('edit.php?post_type=page'); // Pages
            remove_menu_page('edit-comments.php'); // Comments
            remove_menu_page('themes.php'); // Appearance
            remove_menu_page('plugins.php'); // Plugins
            remove_menu_page('users.php'); // Users
            remove_menu_page('tools.php'); // Tools
            remove_menu_page('options-general.php'); // Settings
            
            // Remove PollMaster settings for managers
            remove_submenu_page('pollmaster', 'pollmaster-settings');
        }
    }
    
    /**
     * Restrict admin access for managers
     */
    public function restrict_admin_access() {
        $user = wp_get_current_user();
        
        if (in_array('pollmaster_manager', $user->roles) && !in_array('administrator', $user->roles)) {
            // Get current page
            $current_page = $_GET['page'] ?? '';
            
            // Allowed pages for managers
            $allowed_pages = array(
                'pollmaster',
                'pollmaster-all-polls',
                'pollmaster-manage-polls',
                'pollmaster-add-poll',
                'pollmaster-edit-poll',
                'pollmaster-poll-results'
            );
            
            // If accessing admin and not on allowed page, redirect to PollMaster dashboard
            if (is_admin() && !in_array($current_page, $allowed_pages) && !wp_doing_ajax()) {
                wp_redirect(admin_url('admin.php?page=pollmaster'));
                exit;
            }
        }
    }
    
    /**
     * Check if current user is a manager
     */
    public function is_manager($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        return $user && in_array('pollmaster_manager', $user->roles);
    }
    
    /**
     * Check if current user can manage polls
     */
    public function can_manage_polls($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return current_user_can('manage_options') || $this->is_manager($user_id);
    }
}