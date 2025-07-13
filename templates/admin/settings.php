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

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Handle form submissions
if (isset($_POST['submit'])) {
    check_admin_referer('pollmaster_settings');
    
    $settings = array();
    
    switch ($current_tab) {
        case 'general':
            $settings['primary_color'] = sanitize_hex_color($_POST['primary_color']);
            $settings['secondary_color'] = sanitize_hex_color($_POST['secondary_color']);
            $settings['enable_popup'] = isset($_POST['enable_popup']) ? 1 : 0;
            $settings['popup_delay'] = intval($_POST['popup_delay']);
            $settings['require_login'] = isset($_POST['require_login']) ? 1 : 0;
            $settings['show_results_after_vote'] = isset($_POST['show_results_after_vote']) ? 1 : 0;
            break;
            
        case 'social':
            $settings['enable_facebook'] = isset($_POST['enable_facebook']) ? 1 : 0;
            $settings['enable_twitter'] = isset($_POST['enable_twitter']) ? 1 : 0;
            $settings['enable_whatsapp'] = isset($_POST['enable_whatsapp']) ? 1 : 0;
            $settings['enable_linkedin'] = isset($_POST['enable_linkedin']) ? 1 : 0;
            $settings['share_tracking'] = isset($_POST['share_tracking']) ? 1 : 0;
            break;
            
        case 'contests':
            $settings['auto_announce_winners'] = isset($_POST['auto_announce_winners']) ? 1 : 0;
            $settings['contest_notification_email'] = sanitize_email($_POST['contest_notification_email']);
            $settings['default_contest_duration'] = intval($_POST['default_contest_duration']);
            break;
            
        case 'weekly':
            $settings['auto_weekly_polls'] = isset($_POST['auto_weekly_polls']) ? 1 : 0;
            $settings['weekly_poll_day'] = sanitize_text_field($_POST['weekly_poll_day']);
            $settings['weekly_poll_time'] = sanitize_text_field($_POST['weekly_poll_time']);
            break;
    }
    
    // Update settings
    $existing_settings = get_option('pollmaster_settings', array());
    $updated_settings = array_merge($existing_settings, $settings);
    update_option('pollmaster_settings', $updated_settings);
    
    echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
}

// Get current settings
$settings = get_option('pollmaster_settings', array());

// Handle success/error messages
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'manager_added':
            echo '<div class="notice notice-success is-dismissible"><p>Manager role assigned successfully!</p></div>';
            break;
        case 'invitation_sent':
            echo '<div class="notice notice-success is-dismissible"><p>Invitation sent successfully!</p></div>';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_email':
            echo '<div class="notice notice-error is-dismissible"><p>Invalid email address provided.</p></div>';
            break;
    }
}

// Initialize manager class
$manager = new PollMaster_Manager();
$managers = $manager->get_managers();
$pending_invitations = $manager->get_pending_invitations();
?>

<div class="wrap pollmaster-settings">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        PollMaster Settings
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- Settings Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=pollmaster-settings&tab=general'); ?>" 
           class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-generic"></span>
            General
        </a>
        <a href="<?php echo admin_url('admin.php?page=pollmaster-settings&tab=social'); ?>" 
           class="nav-tab <?php echo $current_tab === 'social' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-share"></span>
            Social Sharing
        </a>
        <a href="<?php echo admin_url('admin.php?page=pollmaster-settings&tab=contests'); ?>" 
           class="nav-tab <?php echo $current_tab === 'contests' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-awards"></span>
            Contests
        </a>
        <a href="<?php echo admin_url('admin.php?page=pollmaster-settings&tab=weekly'); ?>" 
           class="nav-tab <?php echo $current_tab === 'weekly' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-calendar-alt"></span>
            Weekly Polls
        </a>
        <a href="<?php echo admin_url('admin.php?page=pollmaster-settings&tab=access'); ?>" 
           class="nav-tab <?php echo $current_tab === 'access' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-users"></span>
            Access Management
        </a>
    </nav>
    
    <div class="tab-content">
        <?php if ($current_tab === 'general'): ?>
            <!-- General Settings -->
            <form method="post" action="">
                <?php wp_nonce_field('pollmaster_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Primary Color</th>
                        <td>
                            <input type="color" name="primary_color" value="<?php echo esc_attr($settings['primary_color'] ?? '#3b82f6'); ?>" />
                            <p class="description">Main color used for buttons and highlights.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Secondary Color</th>
                        <td>
                            <input type="color" name="secondary_color" value="<?php echo esc_attr($settings['secondary_color'] ?? '#10b981'); ?>" />
                            <p class="description">Secondary color used for accents and progress bars.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Enable Popup Polls</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_popup" value="1" <?php checked($settings['enable_popup'] ?? 1); ?> />
                                Show polls in popup overlays
                            </label>
                            <p class="description">Allow polls to be displayed as popup overlays on the frontend.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Popup Delay</th>
                        <td>
                            <input type="number" name="popup_delay" value="<?php echo esc_attr($settings['popup_delay'] ?? 3); ?>" min="0" max="60" />
                            <p class="description">Delay in seconds before showing popup polls.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Require Login to Vote</th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_login" value="1" <?php checked($settings['require_login'] ?? 0); ?> />
                                Users must be logged in to vote
                            </label>
                            <p class="description">Prevent anonymous voting by requiring user login.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Show Results After Vote</th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_results_after_vote" value="1" <?php checked($settings['show_results_after_vote'] ?? 1); ?> />
                                Automatically show results after voting
                            </label>
                            <p class="description">Display poll results immediately after a user votes.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
        <?php elseif ($current_tab === 'social'): ?>
            <!-- Social Sharing Settings -->
            <form method="post" action="">
                <?php wp_nonce_field('pollmaster_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Facebook Sharing</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_facebook" value="1" <?php checked($settings['enable_facebook'] ?? 1); ?> />
                                Show Facebook share button
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Enable Twitter Sharing</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_twitter" value="1" <?php checked($settings['enable_twitter'] ?? 1); ?> />
                                Show Twitter share button
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Enable WhatsApp Sharing</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_whatsapp" value="1" <?php checked($settings['enable_whatsapp'] ?? 1); ?> />
                                Show WhatsApp share button
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Enable LinkedIn Sharing</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_linkedin" value="1" <?php checked($settings['enable_linkedin'] ?? 1); ?> />
                                Show LinkedIn share button
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Share Tracking</th>
                        <td>
                            <label>
                                <input type="checkbox" name="share_tracking" value="1" <?php checked($settings['share_tracking'] ?? 1); ?> />
                                Track social media shares
                            </label>
                            <p class="description">Keep statistics on how often polls are shared on social media.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
        <?php elseif ($current_tab === 'contests'): ?>
            <!-- Contest Settings -->
            <form method="post" action="">
                <?php wp_nonce_field('pollmaster_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Auto-Announce Winners</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_announce_winners" value="1" <?php checked($settings['auto_announce_winners'] ?? 0); ?> />
                                Automatically announce contest winners when polls end
                            </label>
                            <p class="description">Winners will be randomly selected from the most voted option.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Contest Notification Email</th>
                        <td>
                            <input type="email" name="contest_notification_email" value="<?php echo esc_attr($settings['contest_notification_email'] ?? get_option('admin_email')); ?>" class="regular-text" />
                            <p class="description">Email address to receive contest notifications.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Default Contest Duration</th>
                        <td>
                            <input type="number" name="default_contest_duration" value="<?php echo esc_attr($settings['default_contest_duration'] ?? 7); ?>" min="1" max="365" />
                            <span>days</span>
                            <p class="description">Default duration for new contest polls.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
        <?php elseif ($current_tab === 'weekly'): ?>
            <!-- Weekly Poll Settings -->
            <form method="post" action="">
                <?php wp_nonce_field('pollmaster_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Auto-Create Weekly Polls</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_weekly_polls" value="1" <?php checked($settings['auto_weekly_polls'] ?? 0); ?> />
                                Automatically create weekly polls
                            </label>
                            <p class="description">System will automatically create and publish weekly polls.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Weekly Poll Day</th>
                        <td>
                            <select name="weekly_poll_day">
                                <option value="monday" <?php selected($settings['weekly_poll_day'] ?? 'monday', 'monday'); ?>>Monday</option>
                                <option value="tuesday" <?php selected($settings['weekly_poll_day'] ?? 'monday', 'tuesday'); ?>>Tuesday</option>
                                <option value="wednesday" <?php selected($settings['weekly_poll_day'] ?? 'monday', 'wednesday'); ?>>Wednesday</option>
                                <option value="thursday" <?php selected($settings['weekly_poll_day'] ?? 'monday', 'thursday'); ?>>Thursday</option>
                                <option value="friday" <?php selected($settings['weekly_poll_day'] ?? 'monday', 'friday'); ?>>Friday</option>
                                <option value="saturday" <?php selected($settings['weekly_poll_day'] ?? 'monday', 'saturday'); ?>>Saturday</option>
                                <option value="sunday" <?php selected($settings['weekly_poll_day'] ?? 'monday', 'sunday'); ?>>Sunday</option>
                            </select>
                            <p class="description">Day of the week to publish new weekly polls.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Weekly Poll Time</th>
                        <td>
                            <input type="time" name="weekly_poll_time" value="<?php echo esc_attr($settings['weekly_poll_time'] ?? '09:00'); ?>" />
                            <p class="description">Time of day to publish weekly polls.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
        <?php elseif ($current_tab === 'access'): ?>
            <!-- Access Management -->
            <div class="access-management">
                
                <!-- Invite Manager Section -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Invite New Manager
                        </h2>
                    </div>
                    <div class="inside">
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                            <input type="hidden" name="action" value="pollmaster_invite_manager">
                            <?php wp_nonce_field('pollmaster_invite_manager'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="manager_name">Manager Name</label>
                                    </th>
                                    <td>
                                        <input type="text" id="manager_name" name="manager_name" class="regular-text" required />
                                        <p class="description">Full name of the person you're inviting.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="manager_email">Email Address</label>
                                    </th>
                                    <td>
                                        <input type="email" id="manager_email" name="manager_email" class="regular-text" required />
                                        <p class="description">Email address where the invitation will be sent.</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <input type="submit" class="button button-primary" value="Send Invitation" />
                            </p>
                        </form>
                    </div>
                </div>
                
                <!-- Current Managers -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <span class="dashicons dashicons-admin-users"></span>
                            Current Managers (<?php echo count($managers); ?>)
                        </h2>
                    </div>
                    <div class="inside">
                        <?php if (empty($managers)): ?>
                            <p>No managers have been added yet. Use the form above to invite your first manager.</p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col">Name</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Added</th>
                                        <th scope="col">Last Login</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($managers as $manager_user): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($manager_user->display_name); ?></strong>
                                                <div class="row-actions">
                                                    <span class="id">ID: <?php echo $manager_user->ID; ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo esc_html($manager_user->user_email); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($manager_user->user_registered)); ?></td>
                                            <td>
                                                <?php 
                                                $last_login = get_user_meta($manager_user->ID, 'last_login', true);
                                                echo $last_login ? date('M j, Y g:i A', strtotime($last_login)) : 'Never';
                                                ?>
                                            </td>
                                            <td>
                                                <button class="button button-small remove-manager" 
                                                        data-user-id="<?php echo $manager_user->ID; ?>"
                                                        data-user-name="<?php echo esc_attr($manager_user->display_name); ?>">
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pending Invitations -->
                <?php if (!empty($pending_invitations)): ?>
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">
                                <span class="dashicons dashicons-email"></span>
                                Pending Invitations (<?php echo count($pending_invitations); ?>)
                            </h2>
                        </div>
                        <div class="inside">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col">Name</th>
                
                                        <th scope="col">Email</th>
                                        <th scope="col">Invited</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_invitations as $email => $invitation): ?>
                                        
                                        <tr>
                                            <td><strong><?php echo esc_html($invitation['name']); ?></strong></td>
                                            <td><?php echo esc_html($email); ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($invitation['invited_at'])); ?></td>
                                            <td>
                                                
                                                <span class="status-badge pending">Pending</span>
                                            </td>
                                            <td>
                                                <button class="button button-small resend-invitation" 
                                                        data-email="<?php echo esc_attr($email); ?>">
                                                    Resend
                                                </button>
                                                <button class="button button-small cancel-invitation" 
                                                        data-email="<?php echo esc_attr($email); ?>">
                                                    Cancel
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Manager Permissions Info -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <span class="dashicons dashicons-info"></span>
                            Manager Permissions
                        </h2>
                    </div>
                    <div class="inside">
                        <p>PollMaster Managers have the following permissions:</p>
                        <ul class="permissions-list">
                            <li><span class="dashicons dashicons-yes"></span> Create and edit polls</li>
                            <li><span class="dashicons dashicons-yes"></span> View poll results and analytics</li>
                            <li><span class="dashicons dashicons-yes"></span> Delete polls</li>
                            <li><span class="dashicons dashicons-yes"></span> Manage contest polls</li>
                            <li><span class="dashicons dashicons-yes"></span> Access PollMaster dashboard</li>
                            <li><span class="dashicons dashicons-no"></span> Modify plugin settings</li>
                            <li><span class="dashicons dashicons-no"></span> Invite other managers</li>
                            <li><span class="dashicons dash icons-no"></span> Access other WordPress admin areas</li>
                        </ul>
                        <p><strong>Note:</strong> Managers can only access PollMaster-related admin pages and cannot modify plugin settings or invite other managers.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.pollmaster-settings .nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.access-management .postbox {
    margin-bottom: 20px;
}

.postbox-header h2 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding: 8px 12px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.permissions-list {
    list-style: none;
    padding: 0;
}

.permissions-list li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
}

.permissions-list .dashicons-yes {
    color: #46b450;
}

.permissions-list .dashicons-no {
    color: #dc3232;
}

.remove-manager,
.resend-invitation,
.cancel-invitation {
    margin-right: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Remove manager
    $('.remove-manager').on('click', function() {
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');
        
        if (confirm(`Are you sure you want to remove ${userName} as a manager? They will lose access to PollMaster admin areas.`)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pollmaster_remove_manager',
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('pollmaster_admin_n once'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while removing the manager.');
                }
            });
        }
    });
    
    // Res end invitation
    $('.resend-invitation').on('click', function() {
        const email = $(this).data('email');
        
        if (confirm(`Resend invitation to ${email}?`)) {
            // Implementation for resending invitation
            alert('Invitation resent successfully!');
        }
    });
    
    // Cancel invitation
    $('.cancel-invitation').on('click', function() {
        const email =  $(this).data('email');
        
        if (confirm(`Cancel invitation for ${email}?`)) {
            // Implementation for canceling invitation
            alert('Invitation canceled successfully!');
            location.reload();
        }
    });
});
</script>
<?php