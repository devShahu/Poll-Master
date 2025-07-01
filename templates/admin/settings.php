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

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['pollmaster_settings_nonce'], 'pollmaster_settings')) {
    $settings = [
        'primary_color' => sanitize_hex_color($_POST['primary_color'] ?? '#3498db'),
        'secondary_color' => sanitize_hex_color($_POST['secondary_color'] ?? '#2c3e50'),
        'popup_auto_show' => isset($_POST['popup_auto_show']) ? 1 : 0,
        'popup_delay' => max(0, intval($_POST['popup_delay'] ?? 3)),
        'show_results_before_voting' => isset($_POST['show_results_before_voting']) ? 1 : 0,
        'require_login_default' => isset($_POST['require_login_default']) ? 1 : 0,
        'allow_multiple_votes_default' => isset($_POST['allow_multiple_votes_default']) ? 1 : 0,
        'enable_social_sharing' => isset($_POST['enable_social_sharing']) ? 1 : 0,
        'social_platforms' => array_map('sanitize_text_field', $_POST['social_platforms'] ?? []),
        'weekly_poll_day' => sanitize_text_field($_POST['weekly_poll_day'] ?? 'monday'),
        'weekly_poll_time' => sanitize_text_field($_POST['weekly_poll_time'] ?? '09:00'),
        'contest_duration_default' => max(1, intval($_POST['contest_duration_default'] ?? 7)),
        'enable_notifications' => isset($_POST['enable_notifications']) ? 1 : 0,
        'notification_email' => sanitize_email($_POST['notification_email'] ?? get_option('admin_email')),
        'data_retention_days' => max(30, intval($_POST['data_retention_days'] ?? 365)),
        'enable_analytics' => isset($_POST['enable_analytics']) ? 1 : 0,
        'cache_results' => isset($_POST['cache_results']) ? 1 : 0,
        'cache_duration' => max(1, intval($_POST['cache_duration'] ?? 60)),
        'enable_recaptcha' => isset($_POST['enable_recaptcha']) ? 1 : 0,
        'recaptcha_site_key' => sanitize_text_field($_POST['recaptcha_site_key'] ?? ''),
        'recaptcha_secret_key' => sanitize_text_field($_POST['recaptcha_secret_key'] ?? ''),
        'custom_css' => wp_kses_post($_POST['custom_css'] ?? ''),
        'enable_export' => isset($_POST['enable_export']) ? 1 : 0,
        'export_formats' => array_map('sanitize_text_field', $_POST['export_formats'] ?? ['csv']),
    ];
    
    update_option('pollmaster_settings', $settings);
    $success_message = 'Settings saved successfully!';
}

// Get current settings
$settings = get_option('pollmaster_settings', []);
$defaults = [
    'primary_color' => '#3498db',
    'secondary_color' => '#2c3e50',
    'popup_auto_show' => 1,
    'popup_delay' => 3,
    'show_results_before_voting' => 0,
    'require_login_default' => 0,
    'allow_multiple_votes_default' => 0,
    'enable_social_sharing' => 1,
    'social_platforms' => ['facebook', 'twitter', 'whatsapp', 'linkedin'],
    'weekly_poll_day' => 'monday',
    'weekly_poll_time' => '09:00',
    'contest_duration_default' => 7,
    'enable_notifications' => 1,
    'notification_email' => get_option('admin_email'),
    'data_retention_days' => 365,
    'enable_analytics' => 1,
    'cache_results' => 1,
    'cache_duration' => 60,
    'enable_recaptcha' => 0,
    'recaptcha_site_key' => '',
    'recaptcha_secret_key' => '',
    'custom_css' => '',
    'enable_export' => 1,
    'export_formats' => ['csv', 'json'],
];

$settings = array_merge($defaults, $settings);

?>

<div class="wrap pollmaster-settings">
    <div class="page-header">
        <h1 class="page-title">
            <span class="title-icon">⚙️</span>
            PollMaster Settings
        </h1>
        
        <div class="page-actions">
            <button type="button" class="button button-secondary" data-action="reset-settings">
                <span class="button-icon">🔄</span>
                Reset to Defaults
            </button>
            
            <button type="button" class="button button-secondary" data-action="export-settings">
                <span class="button-icon">📤</span>
                Export Settings
            </button>
            
            <button type="button" class="button button-secondary" data-action="import-settings">
                <span class="button-icon">📥</span>
                Import Settings
            </button>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="settings-form">
        <?php wp_nonce_field('pollmaster_settings', 'pollmaster_settings_nonce'); ?>
        
        <div class="settings-container">
            <!-- Appearance Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">🎨</span>
                        Appearance
                    </h2>
                    <p class="section-description">Customize the visual appearance of your polls</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label for="primary_color" class="setting-label">
                            Primary Color
                            <span class="setting-description">Main color used for buttons and highlights</span>
                        </label>
                        <div class="color-picker-wrapper">
                            <input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr($settings['primary_color']); ?>" class="color-picker">
                            <input type="text" class="color-text" value="<?php echo esc_attr($settings['primary_color']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <label for="secondary_color" class="setting-label">
                            Secondary Color
                            <span class="setting-description">Secondary color used for text and borders</span>
                        </label>
                        <div class="color-picker-wrapper">
                            <input type="color" id="secondary_color" name="secondary_color" value="<?php echo esc_attr($settings['secondary_color']); ?>" class="color-picker">
                            <input type="text" class="color-text" value="<?php echo esc_attr($settings['secondary_color']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="setting-item full-width">
                        <label for="custom_css" class="setting-label">
                            Custom CSS
                            <span class="setting-description">Add custom CSS to further customize the appearance</span>
                        </label>
                        <textarea id="custom_css" name="custom_css" rows="8" class="large-text code" placeholder="/* Add your custom CSS here */"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Popup Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">🪟</span>
                        Popup Behavior
                    </h2>
                    <p class="section-description">Configure how popups behave on your site</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label class="setting-label checkbox-label">
                            <input type="checkbox" name="popup_auto_show" value="1" <?php checked($settings['popup_auto_show'], 1); ?>>
                            <span class="checkmark"></span>
                            Auto-show Popups
                            <span class="setting-description">Automatically show popups when pages load</span>
                        </label>
                    </div>
                    
                    <div class="setting-item">
                        <label for="popup_delay" class="setting-label">
                            Popup Delay (seconds)
                            <span class="setting-description">Delay before showing auto-popup</span>
                        </label>
                        <input type="number" id="popup_delay" name="popup_delay" value="<?php echo esc_attr($settings['popup_delay']); ?>" min="0" max="60" class="small-text">
                    </div>
                </div>
            </div>

            <!-- Poll Defaults -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">📋</span>
                        Poll Defaults
                    </h2>
                    <p class="section-description">Default settings for new polls</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label class="setting-label checkbox-label">
                            <input type="checkbox" name="show_results_before_voting" value="1" <?php checked($settings['show_results_before_voting'], 1); ?>>
                            <span class="checkmark"></span>
                            Show Results Before Voting
                            <span class="setting-description">Allow users to see results without voting first</span>
                        </label>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label checkbox-label">
                            <input type="checkbox" name="require_login_default" value="1" <?php checked($settings['require_login_default'], 1); ?>>
                            <span class="checkmark"></span>
                            Require Login by Default
                            <span class="setting-description">New polls will require user login to vote</span>
                        </label>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label checkbox-label">
                            <input type="checkbox" name="allow_multiple_votes_default" value="1" <?php checked($settings['allow_multiple_votes_default'], 1); ?>>
                            <span class="checkmark"></span>
                            Allow Multiple Votes by Default
                            <span class="setting-description">Users can change their vote in new polls</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Social Sharing -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">📤</span>
                        Social Sharing
                    </h2>
                    <p class="section-description">Configure social media sharing options</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label class="setting-label checkbox-label">
                            <input type="checkbox" name="enable_social_sharing" value="1" <?php checked($settings['enable_social_sharing'], 1); ?>>
                            <span class="checkmark"></span>
                            Enable Social Sharing
                            <span class="setting-description">Show social sharing buttons on polls</span>
                        </label>
                    </div>
                    
                    <div class="setting-item full-width">
                        <label class="setting-label">
                            Social Platforms
                            <span class="setting-description">Select which platforms to show</span>
                        </label>
                        <div class="checkbox-group">
                            <?php
                            $platforms = [
                                'facebook' => 'Facebook',
                                'twitter' => 'Twitter',
                                'whatsapp' => 'WhatsApp',
                                'linkedin' => 'LinkedIn',
                                'telegram' => 'Telegram',
                                'reddit' => 'Reddit'
                            ];
                            
                            foreach ($platforms as $key => $label):
                            ?>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="social_platforms[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $settings['social_platforms'])); ?>>
                                    <span class="checkmark"></span>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Polls -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">📅</span>
                        Weekly Polls
                    </h2>
                    <p class="section-description">Configure automatic weekly poll scheduling</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label for="weekly_poll_day" class="setting-label">
                            Weekly Poll Day
                            <span class="setting-description">Day of the week to create new weekly polls</span>
                        </label>
                        <select id="weekly_poll_day" name="weekly_poll_day">
                            <?php
                            $days = [
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday'
                            ];
                            
                            foreach ($days as $key => $label):
                            ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['weekly_poll_day'], $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="setting-item">
                        <label for="weekly_poll_time" class="setting-label">
                            Weekly Poll Time
                            <span class="setting-description">Time to create new weekly polls</span>
                        </label>
                        <input type="time" id="weekly_poll_time" name="weekly_poll_time" value="<?php echo esc_attr($settings['weekly_poll_time']); ?>">
                    </div>
                </div>
            </div>

            <!-- Contest Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">🏆</span>
                        Contest Settings
                    </h2>
                    <p class="section-description">Default settings for contest polls</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label for="contest_duration_default" class="setting-label">
                            Default Contest Duration (days)
                            <span class="setting-description">How long contests run by default</span>
                        </label>
                        <input type="number" id="contest_duration_default" name="contest_duration_default" value="<?php echo esc_attr($settings['contest_duration_default']); ?>" min="1" max="365" class="small-text">
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">📧</span>
                        Notifications
                    </h2>
                    <p class="section-description">Configure email notifications</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label class="setting-label checkbox-label">
                            <input type="checkbox" name="enable_notifications" value="1" <?php checked($settings['enable_notifications'], 1); ?>>
                            <span class="checkmark"></span>
                            Enable Email Notifications
                            <span class="setting-description">Send email notifications for important events</span>
                        </label>
                    </div>
                    
                    <div class="setting-item">
                        <label for="notification_email" class="setting-label">
                            Notification Email
                            <span class="setting-description">Email address to receive notifications</span>
                        </label>
                        <input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr($settings['notification_email']); ?>" class="regular-text">
                    </div>
                </div>
            </div>

            <!-- Data Management -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">🗄️</span>
                        Data Management
                    </h2>
                    <p class="section-description">Configure data retention and cleanup</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label for="data_retention_days" class="setting-label">
                            Data Retention (days)
                            <span class="setting-description">How long to keep old poll data</span>
                        </label>
                        <input type="number" id="data_retention_days" name="data_retention_days" value="<?php echo esc_attr($settings['data_retention_days']); ?>" min="30" max="3650" class="small-text">
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label checkbox-label">
                            <input type="checkbox" name="enable_analytics" value="1" <?php checked($settings['enable_analytics'], 1); ?>>
                            <span class="checkmark"></span>
                            Enable Analytics
                            <span class="setting-description">Track detailed poll statistics</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Performance -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">⚡</span>
                        Performance
                    </h2>
                    <p class="section-description">Optimize plugin performance</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label class="setting-label checkbox-label">
                            <input type="checkbox" name="cache_results" value="1" <?php checked($settings['cache_results'], 1); ?>>
                            <span class="checkmark"></span>
                            Cache Poll Results
                            <span class="setting-description">Cache results to improve loading speed</span>
                        </label>
                    </div>
                    
                    <div class="setting-item">
                        <label for="cache_duration" class="setting-label">
                            Cache Duration (minutes)
                            <span class="setting-description">How long to cache results</span>
                        </label>
                        <input type="number" id="cache_duration" name="cache_duration" value="<?php echo esc_attr($settings['cache_duration']); ?>" min="1" max="1440" class="small-text">
                    </div>
                </div>
            </div>

            <!-- Security -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">🔒</span>
                        Security
                    </h2>
                    <p class="section-description">Configure security features</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label class="setting-label checkbox-label">
                            <input type="checkbox" name="enable_recaptcha" value="1" <?php checked($settings['enable_recaptcha'], 1); ?>>
                            <span class="checkmark"></span>
                            Enable reCAPTCHA
                            <span class="setting-description">Protect against spam and bots</span>
                        </label>
                    </div>
                    
                    <div class="setting-item recaptcha-field">
                        <label for="recaptcha_site_key" class="setting-label">
                            reCAPTCHA Site Key
                            <span class="setting-description">Your reCAPTCHA site key from Google</span>
                        </label>
                        <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?php echo esc_attr($settings['recaptcha_site_key']); ?>" class="regular-text">
                    </div>
                    
                    <div class="setting-item recaptcha-field">
                        <label for="recaptcha_secret_key" class="setting-label">
                            reCAPTCHA Secret Key
                            <span class="setting-description">Your reCAPTCHA secret key from Google</span>
                        </label>
                        <input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?php echo esc_attr($settings['recaptcha_secret_key']); ?>" class="regular-text">
                    </div>
                </div>
            </div>

            <!-- Export/Import -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">📊</span>
                        Export/Import
                    </h2>
                    <p class="section-description">Configure data export options</p>
                </div>
                
                <div class="settings-grid">
                    <div class="setting-item">
                        <label class="setting-label checkbox-label">
                            <input type="checkbox" name="enable_export" value="1" <?php checked($settings['enable_export'], 1); ?>>
                            <span class="checkmark"></span>
                            Enable Data Export
                            <span class="setting-description">Allow exporting poll data</span>
                        </label>
                    </div>
                    
                    <div class="setting-item full-width">
                        <label class="setting-label">
                            Export Formats
                            <span class="setting-description">Available export formats</span>
                        </label>
                        <div class="checkbox-group">
                            <?php
                            $formats = [
                                'csv' => 'CSV',
                                'json' => 'JSON',
                                'xml' => 'XML',
                                'pdf' => 'PDF'
                            ];
                            
                            foreach ($formats as $key => $label):
                            ?>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="export_formats[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $settings['export_formats'])); ?>>
                                    <span class="checkmark"></span>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="settings-footer">
            <button type="submit" name="submit" class="button button-primary button-large">
                <span class="button-icon">💾</span>
                Save Settings
            </button>
            
            <button type="button" class="button button-secondary" data-action="preview-changes">
                <span class="button-icon">👁️</span>
                Preview Changes
            </button>
        </div>
    </form>
</div>

<!-- Import Settings Modal -->
<div id="import-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Settings</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        
        <div class="modal-body">
            <p>Upload a settings file to import configuration:</p>
            <input type="file" id="settings-file" accept=".json" class="file-input">
            
            <div class="import-options">
                <label class="checkbox-label">
                    <input type="checkbox" id="merge-settings" checked>
                    <span class="checkmark"></span>
                    Merge with existing settings
                    <span class="setting-description">Keep current settings and only update imported ones</span>
                </label>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="button button-secondary" data-action="close-modal">Cancel</button>
            <button type="button" class="button button-primary" data-action="import-file">Import</button>
        </div>
    </div>
</div>

<style>
/* Settings Page Styles */
.pollmaster-settings {
    padding: 20px 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.page-title {
    font-size: 1.8rem;
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-actions {
    display: flex;
    gap: 10px;
}

.button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.settings-form {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.settings-container {
    padding: 0;
}

/* Settings Sections */
.settings-section {
    border-bottom: 1px solid #e9ecef;
}

.settings-section:last-child {
    border-bottom: none;
}

.section-header {
    padding: 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.section-title {
    font-size: 1.3rem;
    margin: 0 0 8px 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-description {
    margin: 0;
    color: #7f8c8d;
    font-size: 0.95rem;
}

.settings-grid {
    padding: 25px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

/* Setting Items */
.setting-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.setting-item.full-width {
    grid-column: 1 / -1;
}

.setting-label {
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.setting-description {
    font-weight: 400;
    color: #7f8c8d;
    font-size: 0.85rem;
    line-height: 1.4;
}

/* Form Controls */
input[type="text"],
input[type="email"],
input[type="number"],
input[type="time"],
select,
textarea {
    padding: 10px 12px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

input[type="text"]:focus,
input[type="email"]:focus,
input[type="number"]:focus,
input[type="time"]:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
}

.small-text {
    width: 80px;
}

.regular-text {
    width: 300px;
}

.large-text {
    width: 100%;
    resize: vertical;
}

/* Color Picker */
.color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.color-picker {
    width: 50px;
    height: 40px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.color-text {
    width: 100px;
    font-family: monospace;
    text-transform: uppercase;
}

/* Checkboxes */
.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    flex-direction: row !important;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #e9ecef;
    border-radius: 4px;
    position: relative;
    transition: all 0.3s ease;
    flex-shrink: 0;
    margin-top: 2px;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
    background: #3498db;
    border-color: #3498db;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-item input[type="checkbox"] {
    display: none;
}

.checkbox-item .checkmark {
    width: 16px;
    height: 16px;
    margin-top: 0;
}

.checkbox-item input[type="checkbox"]:checked + .checkmark::after {
    font-size: 10px;
}

/* Conditional Fields */
.recaptcha-field {
    opacity: 0.5;
    pointer-events: none;
    transition: opacity 0.3s ease;
}

.recaptcha-field.enabled {
    opacity: 1;
    pointer-events: auto;
}

/* Settings Footer */
.settings-footer {
    padding: 25px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 15px;
    align-items: center;
}

.button-large {
    padding: 12px 24px;
    font-size: 16px;
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #7f8c8d;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.file-input {
    width: 100%;
    padding: 10px;
    border: 2px dashed #e9ecef;
    border-radius: 6px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.3s ease;
}

.file-input:hover {
    border-color: #3498db;
}

.import-options {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .page-actions {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .settings-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .checkbox-group {
        grid-template-columns: 1fr;
    }
    
    .color-picker-wrapper {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .settings-footer {
        flex-direction: column;
        gap: 10px;
    }
    
    .modal-content {
        width: 95%;
        margin: 20px;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1.4rem;
        flex-direction: column;
        gap: 8px;
    }
    
    .section-title {
        font-size: 1.1rem;
        flex-direction: column;
        gap: 5px;
    }
    
    .regular-text {
        width: 100%;
    }
}
</style>

<script>
// Settings Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeSettings();
    
    function initializeSettings() {
        bindEventHandlers();
        initializeColorPickers();
        toggleConditionalFields();
    }
    
    function bindEventHandlers() {
        // Color picker changes
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('color-picker')) {
                const textInput = e.target.parentNode.querySelector('.color-text');
                if (textInput) {
                    textInput.value = e.target.value;
                }
            }
        });
        
        // reCAPTCHA toggle
        document.addEventListener('change', function(e) {
            if (e.target.name === 'enable_recaptcha') {
                toggleRecaptchaFields(e.target.checked);
            }
        });
        
        // Action buttons
        document.addEventListener('click', function(e) {
            const action = e.target.closest('[data-action]')?.dataset.action;
            
            switch (action) {
                case 'reset-settings':
                    e.preventDefault();
                    resetSettings();
                    break;
                    
                case 'export-settings':
                    e.preventDefault();
                    exportSettings();
                    break;
                    
                case 'import-settings':
                    e.preventDefault();
                    showImportModal();
                    break;
                    
                case 'preview-changes':
                    e.preventDefault();
                    previewChanges();
                    break;
                    
                case 'close-modal':
                    e.preventDefault();
                    closeModal();
                    break;
                    
                case 'import-file':
                    e.preventDefault();
                    importSettings();
                    break;
            }
        });
        
        // Modal close on backdrop click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });
        
        // Modal close button
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-close')) {
                closeModal();
            }
        });
    }
    
    function initializeColorPickers() {
        const colorPickers = document.querySelectorAll('.color-picker');
        colorPickers.forEach(picker => {
            const textInput = picker.parentNode.querySelector('.color-text');
            if (textInput) {
                textInput.value = picker.value;
            }
        });
    }
    
    function toggleConditionalFields() {
        // reCAPTCHA fields
        const recaptchaEnabled = document.querySelector('input[name="enable_recaptcha"]').checked;
        toggleRecaptchaFields(recaptchaEnabled);
    }
    
    function toggleRecaptchaFields(enabled) {
        const fields = document.querySelectorAll('.recaptcha-field');
        fields.forEach(field => {
            if (enabled) {
                field.classList.add('enabled');
            } else {
                field.classList.remove('enabled');
            }
        });
    }
    
    function resetSettings() {
        if (confirm('Are you sure you want to reset all settings to their default values? This action cannot be undone.')) {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'pollmaster_reset_settings',
                    nonce: pollmaster_admin.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settings reset successfully!');
                    location.reload();
                } else {
                    alert('Error resetting settings: ' + data.data);
                }
            })
            .catch(error => {
                alert('Error resetting settings');
            });
        }
    }
    
    function exportSettings() {
        const form = document.querySelector('.settings-form');
        const formData = new FormData(form);
        
        const settings = {};
        for (let [key, value] of formData.entries()) {
            if (key.endsWith('[]')) {
                const arrayKey = key.slice(0, -2);
                if (!settings[arrayKey]) {
                    settings[arrayKey] = [];
                }
                settings[arrayKey].push(value);
            } else {
                settings[key] = value;
            }
        }
        
        const blob = new Blob([JSON.stringify(settings, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'pollmaster-settings.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    function showImportModal() {
        document.getElementById('import-modal').style.display = 'flex';
    }
    
    function closeModal() {
        document.getElementById('import-modal').style.display = 'none';
    }
    
    function importSettings() {
        const fileInput = document.getElementById('settings-file');
        const mergeSettings = document.getElementById('merge-settings').checked;
        
        if (!fileInput.files[0]) {
            alert('Please select a file to import.');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const importedSettings = JSON.parse(e.target.result);
                applyImportedSettings(importedSettings, mergeSettings);
                closeModal();
            } catch (error) {
                alert('Invalid settings file. Please check the file format.');
            }
        };
        reader.readAsText(fileInput.files[0]);
    }
    
    function applyImportedSettings(settings, merge) {
        const form = document.querySelector('.settings-form');
        
        if (!merge) {
            // Reset form first
            form.reset();
        }
        
        // Apply imported settings
        Object.keys(settings).forEach(key => {
            const value = settings[key];
            
            if (Array.isArray(value)) {
                // Handle array values (checkboxes)
                const checkboxes = form.querySelectorAll(`input[name="${key}[]"]`);
                checkboxes.forEach(checkbox => {
                    checkbox.checked = value.includes(checkbox.value);
                });
            } else {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = value == 1;
                    } else {
                        input.value = value;
                        
                        // Update color picker text
                        if (input.classList.contains('color-picker')) {
                            const textInput = input.parentNode.querySelector('.color-text');
                            if (textInput) {
                                textInput.value = value;
                            }
                        }
                    }
                }
            }
        });
        
        // Update conditional fields
        toggleConditionalFields();
        
        alert('Settings imported successfully!');
    }
    
    function previewChanges() {
        // Get current form values
        const form = document.querySelector('.settings-form');
        const formData = new FormData(form);
        
        const primaryColor = formData.get('primary_color') || '#3498db';
        const secondaryColor = formData.get('secondary_color') || '#2c3e50';
        
        // Create preview styles
        let previewStyle = document.getElementById('preview-styles');
        if (!previewStyle) {
            previewStyle = document.createElement('style');
            previewStyle.id = 'preview-styles';
            document.head.appendChild(previewStyle);
        }
        
        previewStyle.textContent = `
            .button-primary {
                background-color: ${primaryColor} !important;
                border-color: ${primaryColor} !important;
            }
            .checkmark {
                border-color: ${secondaryColor} !important;
            }
            input[type="checkbox"]:checked + .checkmark {
                background-color: ${primaryColor} !important;
                border-color: ${primaryColor} !important;
            }
            .section-title {
                color: ${secondaryColor} !important;
            }
        `;
        
        alert('Preview applied! The colors have been temporarily updated to show how they will look.');
    }
});
</script>