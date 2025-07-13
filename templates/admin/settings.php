<?php
/**
 * Settings Admin Template
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$settings = get_option('pollmaster_settings', array());

// Default settings
$default_settings = array(
    // Appearance
    'primary_color' => '#3b82f6',
    'secondary_color' => '#1e40af',
    'accent_color' => '#f59e0b',
    'text_color' => '#1f2937',
    'background_color' => '#ffffff',
    'border_color' => '#e5e7eb',
    'font_family' => 'Inter',
    'font_size' => '16',
    'border_radius' => '8',
    'custom_css' => '',
    
    // Popup Behavior
    'popup_enabled' => true,
    'popup_delay' => 3000,
    'popup_frequency' => 'once_per_session',
    'popup_position' => 'center',
    'popup_animation' => 'fadeIn',
    'popup_auto_close' => false,
    'popup_auto_close_delay' => 10000,
    
    // Poll Defaults
    'show_results_after_vote' => true,
    'show_vote_count' => true,
    'show_percentage' => true,
    'require_login' => false,
    'allow_multiple_votes' => false,
    'vote_limit_per_user' => 1,
    'data_retention_days' => 365,
    
    // Social Sharing
    'enable_social_sharing' => true,
    'social_platforms' => array('facebook', 'twitter', 'linkedin'),
    
    // Access Control
    'poll_managers' => array(),
    'manager_permissions' => array(
        'create_polls' => true,
        'edit_polls' => true,
        'delete_polls' => false,
        'view_results' => true,
        'export_data' => false
    )
);

// Merge with defaults
$settings = wp_parse_args($settings, $default_settings);

/**
 * Send manager invitation email
 */
function send_manager_invitation($email, $permissions) {
    $token = wp_generate_password(32, false);
    $pending_invites = get_option('pollmaster_pending_invites', array());
    
    $pending_invites[$email] = array(
        'token' => $token,
        'permissions' => $permissions,
        'expires' => time() + (7 * 24 * 60 * 60), // 7 days
        'invited_at' => current_time('mysql')
    );
    
    update_option('pollmaster_pending_invites', $pending_invites);
    
    // Check if user exists
    $user = get_user_by('email', $email);
    $invite_url = admin_url('admin.php?page=pollmaster-accept-invite&token=' . $token);
    
    if ($user) {
        // User exists, send role assignment email
        $subject = 'Poll Manager Role Assignment';
        $message = sprintf(
            'Hello %s,\n\nYou have been assigned as a Poll Manager. Click the link below to accept your role:\n\n%s\n\nThis link will expire in 7 days.\n\nBest regards,\nPollMaster Team',
            $user->display_name,
            $invite_url
        );
    } else {
        // User doesn't exist, send account creation email
        $subject = 'Poll Manager Invitation - Account Setup Required';
        $message = sprintf(
            'Hello,\n\nYou have been invited to be a Poll Manager. To get started, you need to create an account first.\n\nClick the link below to set up your account and accept the invitation:\n\n%s\n\nThis link will expire in 7 days.\n\nBest regards,\nPollMaster Team',
            $invite_url
        );
    }
    
    wp_mail($email, $subject, $message);
}

/**
 * Get current managers with details
 */
function get_managers_list() {
    $managers = get_option('pollmaster_managers', array());
    $pending_invites = get_option('pollmaster_pending_invites', array());
    $managers_list = array();
    
    // Active managers
    foreach ($managers as $email => $data) {
        $user = get_user_by('email', $email);
        $managers_list[] = array(
            'email' => $email,
            'name' => $user ? $user->display_name : 'Unknown',
            'status' => 'Active',
            'permissions' => $data['permissions'],
            'joined_at' => $data['joined_at'] ?? 'Unknown'
        );
    }
    
    // Pending invitations
    foreach ($pending_invites as $email => $data) {
        if ($data['expires'] > time()) {
            $managers_list[] = array(
                'email' => $email,
                'name' => 'Pending',
                'status' => 'Pending Invitation',
                'permissions' => $data['permissions'],
                'invited_at' => $data['invited_at']
            );
        }
    }
    
    return $managers_list;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'pollmaster_settings')) {
    $validation_errors = array();
    
    // Validate and sanitize settings
    $new_settings = array();
    
    // Appearance settings
    $new_settings['primary_color'] = sanitize_hex_color($_POST['primary_color'] ?? $default_settings['primary_color']);
    if (!$new_settings['primary_color']) {
        $validation_errors[] = 'Invalid primary color format. Please use a valid hex color.';
    }
    
    $new_settings['secondary_color'] = sanitize_hex_color($_POST['secondary_color'] ?? $default_settings['secondary_color']);
    $new_settings['accent_color'] = sanitize_hex_color($_POST['accent_color'] ?? $default_settings['accent_color']);
    $new_settings['text_color'] = sanitize_hex_color($_POST['text_color'] ?? $default_settings['text_color']);
    $new_settings['background_color'] = sanitize_hex_color($_POST['background_color'] ?? $default_settings['background_color']);
    $new_settings['border_color'] = sanitize_hex_color($_POST['border_color'] ?? $default_settings['border_color']);
    $new_settings['font_family'] = sanitize_text_field($_POST['font_family'] ?? $default_settings['font_family']);
    $new_settings['font_size'] = intval($_POST['font_size'] ?? $default_settings['font_size']);
    $new_settings['border_radius'] = intval($_POST['border_radius'] ?? $default_settings['border_radius']);
    $new_settings['custom_css'] = wp_strip_all_tags($_POST['custom_css'] ?? '');
    
    // Popup settings
    $new_settings['popup_enabled'] = isset($_POST['popup_enabled']);
    $popup_delay = intval($_POST['popup_delay'] ?? $default_settings['popup_delay']);
    if ($popup_delay < 0) {
        $validation_errors[] = 'Popup delay must be a positive number.';
    } else {
        $new_settings['popup_delay'] = $popup_delay;
    }
    
    $new_settings['popup_frequency'] = sanitize_text_field($_POST['popup_frequency'] ?? $default_settings['popup_frequency']);
    $new_settings['popup_position'] = sanitize_text_field($_POST['popup_position'] ?? $default_settings['popup_position']);
    $new_settings['popup_animation'] = sanitize_text_field($_POST['popup_animation'] ?? $default_settings['popup_animation']);
    $new_settings['popup_auto_close'] = isset($_POST['popup_auto_close']);
    $new_settings['popup_auto_close_delay'] = intval($_POST['popup_auto_close_delay'] ?? $default_settings['popup_auto_close_delay']);
    
    // Poll defaults
    $new_settings['show_results_after_vote'] = isset($_POST['show_results_after_vote']);
    $new_settings['show_vote_count'] = isset($_POST['show_vote_count']);
    $new_settings['show_percentage'] = isset($_POST['show_percentage']);
    $new_settings['require_login'] = isset($_POST['require_login']);
    $new_settings['allow_multiple_votes'] = isset($_POST['allow_multiple_votes']);
    
    $vote_limit = intval($_POST['vote_limit_per_user'] ?? $default_settings['vote_limit_per_user']);
    if ($vote_limit < 1) {
        $validation_errors[] = 'Vote limit per user must be at least 1.';
    } else {
        $new_settings['vote_limit_per_user'] = $vote_limit;
    }
    
    $retention_days = intval($_POST['data_retention_days'] ?? $default_settings['data_retention_days']);
    if ($retention_days < 1) {
        $validation_errors[] = 'Data retention days must be at least 1.';
    } else {
        $new_settings['data_retention_days'] = $retention_days;
    }
    
    // Social sharing
    $new_settings['enable_social_sharing'] = isset($_POST['enable_social_sharing']);
    $new_settings['social_platforms'] = isset($_POST['social_platforms']) ? array_map('sanitize_text_field', $_POST['social_platforms']) : array();
    
    // Access control
    $new_settings['poll_managers'] = isset($_POST['poll_managers']) ? array_map('sanitize_text_field', $_POST['poll_managers']) : array();
    $new_settings['manager_permissions'] = array(
        'create_polls' => isset($_POST['manager_create_polls']),
        'edit_polls' => isset($_POST['manager_edit_polls']),
        'delete_polls' => isset($_POST['manager_delete_polls']),
        'view_results' => isset($_POST['manager_view_results']),
        'export_data' => isset($_POST['manager_export_data'])
    );
    
    // Handle manager invitation
    if (isset($_POST['invite_manager_email']) && !empty($_POST['invite_manager_email'])) {
        $invite_email = sanitize_email($_POST['invite_manager_email']);
        if (is_email($invite_email)) {
            $this->send_manager_invitation($invite_email, $new_settings['manager_permissions']);
            $success_message = 'Manager invitation sent to ' . $invite_email;
        } else {
            $validation_errors[] = 'Invalid email address for manager invitation.';
        }
    }
    
    // Handle manager removal
    if (isset($_POST['remove_manager']) && !empty($_POST['remove_manager'])) {
        $remove_email = sanitize_email($_POST['remove_manager']);
        $managers = get_option('pollmaster_managers', array());
        if (isset($managers[$remove_email])) {
            unset($managers[$remove_email]);
            update_option('pollmaster_managers', $managers);
            $success_message = 'Manager removed successfully.';
        }
    }
    
    // Handle manager invitations
    if (isset($_POST['invite_manager_email']) && !empty($_POST['invite_manager_email'])) {
        $invite_email = sanitize_email($_POST['invite_manager_email']);
        if (is_email($invite_email)) {
            $this->send_manager_invitation($invite_email, $new_settings['manager_permissions']);
            $success_message = 'Manager invitation sent to ' . $invite_email;
        } else {
            $validation_errors[] = 'Invalid email address for manager invitation.';
        }
    }
    
    // Handle manager removal
    if (isset($_POST['remove_manager']) && !empty($_POST['remove_manager'])) {
        $remove_email = sanitize_email($_POST['remove_manager']);
        $managers = get_option('pollmaster_managers', array());
        if (isset($managers[$remove_email])) {
            unset($managers[$remove_email]);
            update_option('pollmaster_managers', $managers);
            $success_message = 'Manager removed successfully.';
        }
    }
    
    if (empty($validation_errors)) {
        // Save settings
        update_option('pollmaster_settings', $new_settings);
        $settings = $new_settings;
        $success_message = true;
    }
}

?>

<?php if (isset($success_message)): ?>
<div id="success-message" class="fixed top-4 right-4 z-50 transform translate-x-full transition-transform duration-500 ease-out">
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-2xl border border-green-400 backdrop-blur-sm">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-green-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <div class="font-bold text-lg">Success!</div>
                <div class="text-green-100">Settings saved successfully!</div>
            </div>
            <button onclick="this.parentElement.parentElement.parentElement.remove()" class="ml-4 text-green-100 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
<script>
    setTimeout(() => {
        const message = document.getElementById('success-message');
        if (message) {
            message.classList.remove('translate-x-full');
            setTimeout(() => {
                message.classList.add('translate-x-full');
                setTimeout(() => message.remove(), 500);
            }, 5000);
        }
    }, 100);
</script>
<?php endif; ?>

<?php if (!empty($validation_errors)): ?>
<div id="error-message" class="fixed top-4 right-4 z-50 transform translate-x-full transition-transform duration-500 ease-out">
    <div class="bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-4 rounded-xl shadow-2xl border border-red-400 backdrop-blur-sm max-w-md">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-red-100 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1">
                <div class="font-bold text-lg mb-2">Validation Errors</div>
                <ul class="text-red-100 space-y-1">
                    <?php foreach ($validation_errors as $error): ?>
                        <li class="text-sm">• <?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-red-100 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
<script>
    setTimeout(() => {
        const message = document.getElementById('error-message');
        if (message) {
            message.classList.remove('translate-x-full');
        }
    }, 100);
</script>
<?php endif; ?>

<div class="pollmaster-admin-page">
    <!-- Animated Background -->
    <div class="animated-background">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Stunning Header with Advanced Gradients and Animations -->
    <div class="hero-section">
        <div class="hero-content">
            <div class="hero-icon">
                <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <h1 class="hero-title">PollMaster Settings</h1>
            <p class="hero-subtitle">Configure your polling system with advanced customization options</p>
            
            <!-- Feature Highlights -->
            <div class="feature-highlights">
                <div class="feature-item">
                    <span class="feature-icon">🎨</span>
                    <span>Visual Customization</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">⚡</span>
                    <span>Advanced Behavior</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🔒</span>
                    <span>Access Control</span>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn-primary" onclick="exportSettings()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export Settings
                </button>
                <button class="btn-secondary" onclick="importSettings()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                    </svg>
                    Import Settings
                </button>
                <button class="btn-danger" onclick="resetSettings()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset to Defaults
                </button>
            </div>
        </div>
    </div>

    <!-- Revolutionary Settings Form with Glass Morphism -->
    <div class="settings-container">
        <form method="post" class="settings-form">
            <?php wp_nonce_field('pollmaster_settings'); ?>
            
            <!-- Enhanced Tab Navigation with Gradient Backgrounds -->
            <div class="tab-navigation">
                <button type="button" class="tab-btn active" data-tab="appearance">
                    <span class="tab-icon">🎨</span>
                    <span class="tab-text">Appearance</span>
                    <span class="tab-indicator"></span>
                </button>
                <button type="button" class="tab-btn" data-tab="behavior">
                    <span class="tab-icon">⚙️</span>
                    <span class="tab-text">Behavior</span>
                    <span class="tab-indicator"></span>
                </button>
                <button type="button" class="tab-btn" data-tab="polls">
                    <span class="tab-icon">📊</span>
                    <span class="tab-text">Poll Defaults</span>
                    <span class="tab-indicator"></span>
                </button>
                <button type="button" class="tab-btn" data-tab="social">
                    <span class="tab-icon">🌐</span>
                    <span class="tab-text">Social</span>
                    <span class="tab-indicator"></span>
                </button>
                <button type="button" class="tab-btn" data-tab="access">
                    <span class="tab-icon">🔒</span>
                    <span class="tab-text">Access Control</span>
                    <span class="tab-indicator"></span>
                </button>
            </div>

            <!-- Appearance Tab -->
            <div class="tab-content active" id="appearance">
                <div class="section-header">
                    <h2>🎨 Appearance Settings</h2>
                    <p>Customize the visual appearance of your polls</p>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="primary_color">Primary Color</label>
                        <div class="color-input-group">
                            <input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr($settings['primary_color']); ?>" class="color-picker">
                            <input type="text" value="<?php echo esc_attr($settings['primary_color']); ?>" class="color-text" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="secondary_color">Secondary Color</label>
                        <div class="color-input-group">
                            <input type="color" id="secondary_color" name="secondary_color" value="<?php echo esc_attr($settings['secondary_color']); ?>" class="color-picker">
                            <input type="text" value="<?php echo esc_attr($settings['secondary_color']); ?>" class="color-text" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="accent_color">Accent Color</label>
                        <div class="color-input-group">
                            <input type="color" id="accent_color" name="accent_color" value="<?php echo esc_attr($settings['accent_color']); ?>" class="color-picker">
                            <input type="text" value="<?php echo esc_attr($settings['accent_color']); ?>" class="color-text" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="text_color">Text Color</label>
                        <div class="color-input-group">
                            <input type="color" id="text_color" name="text_color" value="<?php echo esc_attr($settings['text_color']); ?>" class="color-picker">
                            <input type="text" value="<?php echo esc_attr($settings['text_color']); ?>" class="color-text" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="background_color">Background Color</label>
                        <div class="color-input-group">
                            <input type="color" id="background_color" name="background_color" value="<?php echo esc_attr($settings['background_color']); ?>" class="color-picker">
                            <input type="text" value="<?php echo esc_attr($settings['background_color']); ?>" class="color-text" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="border_color">Border Color</label>
                        <div class="color-input-group">
                            <input type="color" id="border_color" name="border_color" value="<?php echo esc_attr($settings['border_color']); ?>" class="color-picker">
                            <input type="text" value="<?php echo esc_attr($settings['border_color']); ?>" class="color-text" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="font_family">Font Family</label>
                        <select id="font_family" name="font_family" class="form-select">
                            <option value="Inter" <?php selected($settings['font_family'], 'Inter'); ?>>Inter</option>
                            <option value="Roboto" <?php selected($settings['font_family'], 'Roboto'); ?>>Roboto</option>
                            <option value="Open Sans" <?php selected($settings['font_family'], 'Open Sans'); ?>>Open Sans</option>
                            <option value="Lato" <?php selected($settings['font_family'], 'Lato'); ?>>Lato</option>
                            <option value="Montserrat" <?php selected($settings['font_family'], 'Montserrat'); ?>>Montserrat</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="font_size">Font Size (px)</label>
                        <input type="number" id="font_size" name="font_size" value="<?php echo esc_attr($settings['font_size']); ?>" min="12" max="24" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="border_radius">Border Radius (px)</label>
                        <input type="number" id="border_radius" name="border_radius" value="<?php echo esc_attr($settings['border_radius']); ?>" min="0" max="50" class="form-input">
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="custom_css">Custom CSS</label>
                    <textarea id="custom_css" name="custom_css" rows="6" class="form-textarea" placeholder="Add your custom CSS here..."><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                </div>
            </div>

            <!-- Behavior Tab -->
            <div class="tab-content" id="behavior">
                <div class="section-header">
                    <h2>⚙️ Popup Behavior</h2>
                    <p>Configure how your polls behave and display</p>
                </div>
                
                <div class="form-grid">
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="popup_enabled" <?php checked($settings['popup_enabled']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Enable Popup Polls</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="popup_delay">Popup Delay (ms)</label>
                        <input type="number" id="popup_delay" name="popup_delay" value="<?php echo esc_attr($settings['popup_delay']); ?>" min="0" step="100" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="popup_frequency">Popup Frequency</label>
                        <select id="popup_frequency" name="popup_frequency" class="form-select">
                            <option value="once_per_session" <?php selected($settings['popup_frequency'], 'once_per_session'); ?>>Once per session</option>
                            <option value="once_per_day" <?php selected($settings['popup_frequency'], 'once_per_day'); ?>>Once per day</option>
                            <option value="once_per_week" <?php selected($settings['popup_frequency'], 'once_per_week'); ?>>Once per week</option>
                            <option value="always" <?php selected($settings['popup_frequency'], 'always'); ?>>Always show</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="popup_position">Popup Position</label>
                        <select id="popup_position" name="popup_position" class="form-select">
                            <option value="center" <?php selected($settings['popup_position'], 'center'); ?>>Center</option>
                            <option value="top-left" <?php selected($settings['popup_position'], 'top-left'); ?>>Top Left</option>
                            <option value="top-right" <?php selected($settings['popup_position'], 'top-right'); ?>>Top Right</option>
                            <option value="bottom-left" <?php selected($settings['popup_position'], 'bottom-left'); ?>>Bottom Left</option>
                            <option value="bottom-right" <?php selected($settings['popup_position'], 'bottom-right'); ?>>Bottom Right</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="popup_animation">Popup Animation</label>
                        <select id="popup_animation" name="popup_animation" class="form-select">
                            <option value="fadeIn" <?php selected($settings['popup_animation'], 'fadeIn'); ?>>Fade In</option>
                            <option value="slideInUp" <?php selected($settings['popup_animation'], 'slideInUp'); ?>>Slide In Up</option>
                            <option value="slideInDown" <?php selected($settings['popup_animation'], 'slideInDown'); ?>>Slide In Down</option>
                            <option value="zoomIn" <?php selected($settings['popup_animation'], 'zoomIn'); ?>>Zoom In</option>
                            <option value="bounceIn" <?php selected($settings['popup_animation'], 'bounceIn'); ?>>Bounce In</option>
                        </select>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="popup_auto_close" <?php checked($settings['popup_auto_close']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Auto-close Popup</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="popup_auto_close_delay">Auto-close Delay (ms)</label>
                        <input type="number" id="popup_auto_close_delay" name="popup_auto_close_delay" value="<?php echo esc_attr($settings['popup_auto_close_delay']); ?>" min="1000" step="1000" class="form-input">
                    </div>
                </div>
            </div>

            <!-- Poll Defaults Tab -->
            <div class="tab-content" id="polls">
                <div class="section-header">
                    <h2>📊 Poll Default Settings</h2>
                    <p>Set default behavior for new polls</p>
                </div>
                
                <div class="form-grid">
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_results_after_vote" <?php checked($settings['show_results_after_vote']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Show Results After Vote</span>
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_vote_count" <?php checked($settings['show_vote_count']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Show Vote Count</span>
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_percentage" <?php checked($settings['show_percentage']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Show Percentage</span>
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="require_login" <?php checked($settings['require_login']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Require Login to Vote</span>
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="allow_multiple_votes" <?php checked($settings['allow_multiple_votes']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Allow Multiple Votes</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="vote_limit_per_user">Vote Limit Per User</label>
                        <input type="number" id="vote_limit_per_user" name="vote_limit_per_user" value="<?php echo esc_attr($settings['vote_limit_per_user']); ?>" min="1" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_retention_days">Data Retention (Days)</label>
                        <input type="number" id="data_retention_days" name="data_retention_days" value="<?php echo esc_attr($settings['data_retention_days']); ?>" min="1" class="form-input">
                    </div>
                </div>
            </div>

            <!-- Social Tab -->
            <div class="tab-content" id="social">
                <div class="section-header">
                    <h2>🌐 Social Sharing</h2>
                    <p>Configure social media integration</p>
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="enable_social_sharing" <?php checked($settings['enable_social_sharing']); ?> class="form-checkbox">
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-text">Enable Social Sharing</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Social Platforms</label>
                    <div class="checkbox-grid">
                        <label class="checkbox-label">
                            <input type="checkbox" name="social_platforms[]" value="facebook" <?php checked(in_array('facebook', $settings['social_platforms'])); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Facebook</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="social_platforms[]" value="twitter" <?php checked(in_array('twitter', $settings['social_platforms'])); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Twitter</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="social_platforms[]" value="linkedin" <?php checked(in_array('linkedin', $settings['social_platforms'])); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">LinkedIn</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="social_platforms[]" value="reddit" <?php checked(in_array('reddit', $settings['social_platforms'])); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Reddit</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Access Control Tab -->
            <div class="tab-content" id="access">
                <div class="section-header">
                    <h2>🔒 Access Control & Poll Managers</h2>
                    <p>Manage who can create and edit polls</p>
                </div>
                
                <div class="manager-section">
                    <div class="manager-header">
                        <h3>Poll Managers</h3>
                        <div class="manager-actions">
                            <div class="invite-form ajax-form" data-action="pollmaster_invite_manager">
    <input type="hidden" name="action" value="pollmaster_invite_manager">
    <input type="email" name="invite_manager_email" placeholder="Enter email to invite" class="form-input invite-input">
    <button type="submit" name="invite_manager" class="btn-invite-manager" data-ajax="true">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                    Send Invitation
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Managers Table -->
                    <div class="managers-table-container">
                        <table class="managers-table">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Permissions</th>
                                    <th>Joined Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $managers = get_option('pollmaster_managers', array());
                                $pending_invites = get_option('pollmaster_pending_invites', array());
                                
                                // Display active managers
                                foreach ($managers as $email => $data): 
                                    $permissions = isset($data['permissions']) ? $data['permissions'] : array();
                                    $joined_date = isset($data['joined_date']) ? $data['joined_date'] : 'Unknown';
                                ?>
                                    <tr>
                                        <td class="manager-email"><?php echo esc_html($email); ?></td>
                                        <td><span class="status-badge status-active">Active</span></td>
                                        <td class="permissions-cell">
                                            <?php 
                                            $perm_labels = array(
                                                'create_polls' => 'Create',
                                                'edit_polls' => 'Edit', 
                                                'delete_polls' => 'Delete',
                                                'view_results' => 'View Results',
                                                'export_data' => 'Export'
                                            );
                                            $active_perms = array();
                                            foreach ($permissions as $perm => $enabled) {
                                                if ($enabled && isset($perm_labels[$perm])) {
                                                    $active_perms[] = $perm_labels[$perm];
                                                }
                                            }
                                            echo esc_html(implode(', ', $active_perms));
                                            ?>
                                        </td>
                                        <td><?php echo esc_html(date('M j, Y', strtotime($joined_date))); ?></td>
                                        <td>
                                            <button type="submit" name="remove_manager" value="<?php echo esc_attr($email); ?>" class="btn-remove-manager" onclick="return confirm('Are you sure you want to remove this manager?')">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <!-- Display pending invitations -->
                                <?php foreach ($pending_invites as $email => $invite_data): 
                                    $expires = isset($invite_data['expires']) ? $invite_data['expires'] : 0;
                                    $is_expired = time() > $expires;
                                ?>
                                    <tr class="pending-invite">
                                        <td class="manager-email"><?php echo esc_html($email); ?></td>
                                        <td><span class="status-badge <?php echo $is_expired ? 'status-expired' : 'status-pending'; ?>"><?php echo $is_expired ? 'Expired' : 'Pending'; ?></span></td>
                                        <td class="permissions-cell">Invitation sent</td>
                                        <td><?php echo esc_html(date('M j, Y', $expires - (7 * 24 * 60 * 60))); ?></td>
                                        <td>
                                            <button type="button" class="btn-resend-invite" onclick="resendInvite('<?php echo esc_js($email); ?>')">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Resend
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($managers) && empty($pending_invites)): ?>
                                    <tr>
                                        <td colspan="5" class="no-managers">No managers added yet. Send an invitation to get started.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="permissions-section">
                    <h3>Manager Permissions</h3>
                    <div class="permissions-grid">
                        <p class="permissions-note">These permissions will be applied to all new managers by default.</p>
                        <label class="checkbox-label">
                            <input type="checkbox" name="manager_create_polls" <?php checked($settings['manager_permissions']['create_polls']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Create Polls</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="manager_edit_polls" <?php checked($settings['manager_permissions']['edit_polls']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Edit Polls</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="manager_delete_polls" <?php checked($settings['manager_permissions']['delete_polls']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Delete Polls</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="manager_view_results" <?php checked($settings['manager_permissions']['view_results']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">View Results</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="manager_export_data" <?php checked($settings['manager_permissions']['export_data']); ?> class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Export Data</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="form-actions">
                <button type="submit" name="submit" class="btn-save">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Modern Settings Page Styles */
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    --glass-bg: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.2);
    --shadow-light: 0 8px 32px rgba(0, 0, 0, 0.1);
    --shadow-heavy: 0 20px 60px rgba(0, 0, 0, 0.15);
    --border-radius: 16px;
    --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.pollmaster-admin-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    position: relative;
}

/* Animated Background */
.animated-background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    overflow: hidden;
}

.particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: linear-gradient(45deg, #667eea, #764ba2);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}

.particle:nth-child(1) { top: 20%; left: 20%; animation-delay: 0s; }
.particle:nth-child(2) { top: 60%; left: 80%; animation-delay: 2s; }
.particle:nth-child(3) { top: 80%; left: 40%; animation-delay: 4s; }
.particle:nth-child(4) { top: 40%; left: 60%; animation-delay: 1s; }
.particle:nth-child(5) { top: 10%; left: 90%; animation-delay: 3s; }

@keyframes float {
    0%, 100% { transform: translateY(0px) scale(1); opacity: 0.7; }
    50% { transform: translateY(-20px) scale(1.2); opacity: 1; }
}

/* Hero Section */
.hero-section {
    background: var(--primary-gradient);
    border-radius: var(--border-radius);
    padding: 60px 40px;
    margin-bottom: 40px;
    box-shadow: var(--shadow-heavy);
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    color: white;
}

.hero-icon {
    margin: 0 auto 20px;
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 16px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.hero-subtitle {
    font-size: 1.25rem;
    opacity: 0.9;
    margin-bottom: 40px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.feature-highlights {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.1);
    padding: 12px 20px;
    border-radius: 25px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: var(--transition);
}

.feature-item:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.15);
}

.feature-icon {
    font-size: 1.2rem;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
}

.btn-primary, .btn-secondary, .btn-danger {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-primary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.btn-danger {
    background: rgba(255, 107, 107, 0.2);
    color: white;
}

.btn-primary:hover, .btn-secondary:hover, .btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

/* Settings Container */
.settings-container {
    background: rgba(255, 255, 255, 0.95);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    overflow: hidden;
}

/* Tab Navigation */
.tab-navigation {
    display: flex;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    overflow-x: auto;
}

.tab-btn {
    flex: 1;
    min-width: 150px;
    padding: 20px 16px;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-weight: 500;
}

.tab-btn.active {
    color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.tab-btn:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    color: #667eea;
}

.tab-icon {
    font-size: 1.5rem;
}

.tab-text {
    font-size: 0.9rem;
}

.tab-indicator {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--primary-gradient);
    transform: scaleX(0);
    transition: var(--transition);
}

.tab-btn.active .tab-indicator {
    transform: scaleX(1);
}

/* Tab Content */
.tab-content {
    display: none;
    padding: 40px;
    animation: fadeInUp 0.5s ease-out;
}

.tab-content.active {
    display: block;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section-header {
    text-align: center;
    margin-bottom: 40px;
}

.section-header h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.section-header p {
    color: #64748b;
    font-size: 1.1rem;
}

/* Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
}

.form-input, .form-select, .form-textarea {
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
    background: white;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.color-input-group {
    display: flex;
    gap: 12px;
    align-items: center;
}

.color-picker {
    width: 60px;
    height: 40px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.color-text {
    flex: 1;
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 8px 12px;
    font-family: monospace;
    font-size: 0.9rem;
}

/* Checkbox Styles */
.checkbox-group {
    flex-direction: row;
    align-items: center;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.form-checkbox {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    position: relative;
    transition: var(--transition);
    background: white;
    flex-shrink: 0;
    display: inline-block;
}

.checkbox-label:hover .checkbox-custom {
    border-color: #667eea;
    transform: scale(1.05);
}

.form-checkbox:checked + .checkbox-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.form-checkbox:checked + .checkbox-custom::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.checkbox-text {
    user-select: none;
    font-size: 14px;
    line-height: 1.4;
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-top: 12px;
}

/* Manager Section */
.manager-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 32px;
}

.manager-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.manager-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
}

.manager-actions {
    display: flex;
    gap: 16px;
    align-items: center;
}

.invite-form {
    display: flex;
    gap: 12px;
    align-items: center;
}

.invite-input {
    min-width: 250px;
}

.btn-invite-manager {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: var(--transition);
    white-space: nowrap;
}

.btn-invite-manager:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Managers Table */
.managers-table-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.managers-table {
    width: 100%;
    border-collapse: collapse;
}

.managers-table th {
    background: #f8fafc;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.managers-table td {
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.managers-table tr:hover {
    background: #f9fafb;
}

.manager-email {
    font-weight: 500;
    color: #1f2937;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: #d1fae5;
    color: #065f46;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-expired {
    background: #fee2e2;
    color: #991b1b;
}

.permissions-cell {
    font-size: 14px;
    color: #6b7280;
}

.btn-remove-manager {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: var(--transition);
}

.btn-remove-manager:hover {
    background: #dc2626;
    transform: scale(1.05);
}

.btn-resend-invite {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: var(--transition);
}

.btn-resend-invite:hover {
    background: #2563eb;
    transform: scale(1.05);
}

.no-managers {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    padding: 32px;
}

.pending-invite {
    opacity: 0.8;
}

.permissions-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 24px;
}

.permissions-section h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 20px;
}

.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

/* Form Actions */
.form-actions {
    text-align: center;
    padding: 32px 40px;
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 16px 32px;
    background: var(--success-gradient);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 4px 20px rgba(79, 172, 254, 0.3);
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(79, 172, 254, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
    .pollmaster-admin-page {
        padding: 16px;
    }
    
    .hero-section {
        padding: 40px 24px;
    }
    
    .hero-title {
        font-size: 2.5rem;
    }
    
    .feature-highlights {
        flex-direction: column;
        align-items: center;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .tab-navigation {
        flex-direction: column;
    }
    
    .tab-btn {
        min-width: auto;
        flex-direction: row;
        justify-content: center;
    }
    
    .tab-content {
        padding: 24px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .manager-header {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
}
</style>

<script>
// Enhanced Tab Functionality with Animations
function initializeTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabBtns.forEach(b => {
                b.classList.remove('active');
                b.style.transform = 'scale(1)';
            });
            tabContents.forEach(content => {
                content.classList.remove('active');
                content.style.opacity = '0';
                content.style.transform = 'translateY(20px)';
            });
            
            // Add active class to clicked tab
            btn.classList.add('active');
            btn.style.transform = 'scale(1.02)';
            
            // Show target content with animation
            const targetContent = document.getElementById(targetTab);
            if (targetContent) {
                setTimeout(() => {
                    targetContent.classList.add('active');
                    targetContent.style.opacity = '1';
                    targetContent.style.transform = 'translateY(0)';
                }, 50);
            }
        });
    });
}

// Color picker functionality
function initializeColorPickers() {
    const colorPickers = document.querySelectorAll('.color-picker');
    colorPickers.forEach(picker => {
        picker.addEventListener('input', function() {
            const textInput = this.parentElement.querySelector('.color-text');
            if (textInput) {
                textInput.value = this.value;
            }
        });
    });
}

// Manager functionality
function resendInvite(email) {
    if (confirm('Resend invitation to ' + email + '?')) {
        // Create a hidden form to submit the resend request
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const emailInput = document.createElement('input');
        emailInput.type = 'hidden';
        emailInput.name = 'resend_invitation';
        emailInput.value = email;
        
        form.appendChild(emailInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function validateInviteForm() {
    const emailInput = document.querySelector('input[name="invite_manager_email"]');
    const email = emailInput.value.trim();
    
    if (!email) {
        alert('Please enter an email address.');
        return false;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }
    
    return true;
}

// Add event listener for invite form validation
document.addEventListener('DOMContentLoaded', function() {
    const inviteButton = document.querySelector('.btn-invite-manager');
    if (inviteButton) {
        inviteButton.addEventListener('click', function(e) {
            if (!validateInviteForm()) {
                e.preventDefault();
            }
        });
    }
});

// Manager removal is now handled by form submission to the server

// Settings export/import functionality
function exportSettings() {
    const settings = {};
    const form = document.querySelector('.settings-form');
    const formData = new FormData(form);
    
    for (let [key, value] of formData.entries()) {
        if (key.endsWith('[]')) {
            const arrayKey = key.slice(0, -2);
            if (!settings[arrayKey]) settings[arrayKey] = [];
            settings[arrayKey].push(value);
        } else {
            settings[key] = value;
        }
    }
    
    const dataStr = JSON.stringify(settings, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'pollmaster-settings.json';
    link.click();
    URL.revokeObjectURL(url);
}

function importSettings() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const settings = JSON.parse(e.target.result);
                    applyImportedSettings(settings);
                    alert('Settings imported successfully!');
                } catch (error) {
                    alert('Error importing settings: Invalid JSON file');
                }
            };
            reader.readAsText(file);
        }
    };
    input.click();
}

function applyImportedSettings(settings) {
    Object.keys(settings).forEach(key => {
        const element = document.querySelector(`[name="${key}"]`);
        if (element) {
            if (element.type === 'checkbox') {
                element.checked = settings[key] === 'on' || settings[key] === true;
            } else {
                element.value = settings[key];
            }
        }
    });
}

function resetSettings() {
    if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
        // Reset form to default values
        const form = document.querySelector('.settings-form');
        form.reset();
        
        // Note: Manager data will be reset on server side
        
        alert('Settings have been reset to defaults. Please save to apply changes.');
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    initializeColorPickers();
    
    // Add smooth scrolling for form sections
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            setTimeout(() => {
                const activeContent = document.querySelector('.tab-content.active');
                if (activeContent) {
                    activeContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 100);
        });
    });
});
</script>