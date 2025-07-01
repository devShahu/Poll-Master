<?php
/**
 * PollMaster Admin Class
 * 
 * Handles admin interface and functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Admin {
    
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new PollMaster_Database();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_pollmaster_admin_action', array($this, 'handle_admin_ajax'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('PollMaster', 'pollmaster'),
            __('PollMaster', 'pollmaster'),
            'manage_options',
            'pollmaster',
            array($this, 'admin_page_polls'),
            'dashicons-chart-pie',
            30
        );
        
        add_submenu_page(
            'pollmaster',
            __('Manage Polls', 'pollmaster'),
            __('Manage Polls', 'pollmaster'),
            'manage_options',
            'pollmaster',
            array($this, 'admin_page_polls')
        );
        
        add_submenu_page(
            'pollmaster',
            __('Weekly Poll', 'pollmaster'),
            __('Weekly Poll', 'pollmaster'),
            'manage_options',
            'pollmaster-weekly',
            array($this, 'admin_page_weekly')
        );
        
        add_submenu_page(
            'pollmaster',
            __('Contests', 'pollmaster'),
            __('Contests', 'pollmaster'),
            'manage_options',
            'pollmaster-contests',
            array($this, 'admin_page_contests')
        );
        
        add_submenu_page(
            'pollmaster',
            __('Settings', 'pollmaster'),
            __('Settings', 'pollmaster'),
            'manage_options',
            'pollmaster-settings',
            array($this, 'admin_page_settings')
        );
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        register_setting('pollmaster_settings', 'pollmaster_settings', array($this, 'sanitize_settings'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'pollmaster') === false) {
            return;
        }
        
        wp_enqueue_script('pollmaster-admin', POLLMASTER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), POLLMASTER_VERSION, true);
        wp_enqueue_style('pollmaster-admin', POLLMASTER_PLUGIN_URL . 'assets/css/admin.css', array(), POLLMASTER_VERSION);
        
        wp_localize_script('pollmaster-admin', 'pollmaster_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pollmaster_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this poll?', 'pollmaster'),
                'error' => __('An error occurred. Please try again.', 'pollmaster'),
                'success' => __('Action completed successfully.', 'pollmaster')
            )
        ));
    }
    
    /**
     * Manage Polls admin page
     */
    public function admin_page_polls() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $poll_id = isset($_GET['poll_id']) ? (int)$_GET['poll_id'] : 0;
        
        switch ($action) {
            case 'edit':
                $this->render_edit_poll_form($poll_id);
                break;
            case 'add':
                $this->render_add_poll_form();
                break;
            default:
                $this->render_polls_list();
                break;
        }
    }
    
    /**
     * Weekly Poll admin page
     */
    public function admin_page_weekly() {
        $this->render_weekly_poll_form();
    }
    
    /**
     * Contests admin page
     */
    public function admin_page_contests() {
        $this->render_contests_page();
    }
    
    /**
     * Settings admin page
     */
    public function admin_page_settings() {
        $this->render_settings_page();
    }
    
    /**
     * Render polls list
     */
    private function render_polls_list() {
        $page = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
        $per_page = 20;
        $polls = $this->database->get_polls($page, $per_page);
        $total_polls = $this->database->get_polls_count();
        $total_pages = ceil($total_polls / $per_page);
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Manage Polls', 'pollmaster'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=pollmaster&action=add'); ?>" class="page-title-action"><?php _e('Add New Poll', 'pollmaster'); ?></a>
            <hr class="wp-header-end">
            
            <?php if (empty($polls)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No polls found. Create your first poll!', 'pollmaster'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Question', 'pollmaster'); ?></th>
                            <th scope="col"><?php _e('Options', 'pollmaster'); ?></th>
                            <th scope="col"><?php _e('Votes', 'pollmaster'); ?></th>
                            <th scope="col"><?php _e('Type', 'pollmaster'); ?></th>
                            <th scope="col"><?php _e('Author', 'pollmaster'); ?></th>
                            <th scope="col"><?php _e('Date', 'pollmaster'); ?></th>
                            <th scope="col"><?php _e('Actions', 'pollmaster'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($polls as $poll): 
                            $results = $this->database->get_poll_results($poll->id);
                            $author = get_userdata($poll->user_id);
                            $poll_type = array();
                            if ($poll->is_weekly) $poll_type[] = __('Weekly', 'pollmaster');
                            if ($poll->is_contest) $poll_type[] = __('Contest', 'pollmaster');
                            if (empty($poll_type)) $poll_type[] = __('Regular', 'pollmaster');
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($poll->question); ?></strong>
                                    <?php if ($poll->image_url): ?>
                                        <br><small><?php _e('Has image', 'pollmaster'); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($poll->option_a); ?> / <?php echo esc_html($poll->option_b); ?>
                                </td>
                                <td>
                                    <?php echo $results['total_votes']; ?>
                                    <br><small>
                                        <?php echo $poll->option_a; ?>: <?php echo $results['percentages']['option_a']; ?>%<br>
                                        <?php echo $poll->option_b; ?>: <?php echo $results['percentages']['option_b']; ?>%
                                    </small>
                                </td>
                                <td><?php echo implode(', ', $poll_type); ?></td>
                                <td><?php echo $author ? esc_html($author->display_name) : __('Unknown', 'pollmaster'); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($poll->created_at)); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=pollmaster&action=edit&poll_id=' . $poll->id); ?>" class="button button-small"><?php _e('Edit', 'pollmaster'); ?></a>
                                    <button class="button button-small button-link-delete pollmaster-delete-poll" data-poll-id="<?php echo $poll->id; ?>"><?php _e('Delete', 'pollmaster'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $total_pages,
                                'current' => $page
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render add poll form
     */
    private function render_add_poll_form() {
        if (isset($_POST['submit_poll'])) {
            $this->handle_poll_submission();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Add New Poll', 'pollmaster'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=pollmaster'); ?>" class="page-title-action"><?php _e('Back to Polls', 'pollmaster'); ?></a>
            
            <form method="post" enctype="multipart/form-data" class="pollmaster-poll-form">
                <?php wp_nonce_field('pollmaster_poll_action', 'pollmaster_poll_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="question"><?php _e('Question', 'pollmaster'); ?></label></th>
                        <td>
                            <input type="text" id="question" name="question" class="regular-text" maxlength="255" required>
                            <p class="description"><?php _e('Maximum 255 characters', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="option_a"><?php _e('Option A', 'pollmaster'); ?></label></th>
                        <td>
                            <input type="text" id="option_a" name="option_a" class="regular-text" maxlength="50" required>
                            <p class="description"><?php _e('Maximum 50 characters', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="option_b"><?php _e('Option B', 'pollmaster'); ?></label></th>
                        <td>
                            <input type="text" id="option_b" name="option_b" class="regular-text" maxlength="50" required>
                            <p class="description"><?php _e('Maximum 50 characters', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="poll_image"><?php _e('Image (Optional)', 'pollmaster'); ?></label></th>
                        <td>
                            <input type="file" id="poll_image" name="poll_image" accept="image/jpeg,image/png">
                            <p class="description"><?php _e('Upload an image for photo-based polls. Maximum 5MB, PNG/JPEG only.', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Poll Type', 'pollmaster'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="is_weekly" value="1">
                                    <?php _e('Weekly Poll', 'pollmaster'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="is_contest" value="1">
                                    <?php _e('Contest Poll', 'pollmaster'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr class="contest-fields" style="display: none;">
                        <th scope="row"><label for="contest_prize"><?php _e('Contest Prize', 'pollmaster'); ?></label></th>
                        <td>
                            <textarea id="contest_prize" name="contest_prize" class="large-text" rows="3"></textarea>
                            <p class="description"><?php _e('Describe the prize for this contest.', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                    <tr class="contest-fields" style="display: none;">
                        <th scope="row"><label for="contest_end_date"><?php _e('Contest End Date', 'pollmaster'); ?></label></th>
                        <td>
                            <input type="datetime-local" id="contest_end_date" name="contest_end_date">
                            <p class="description"><?php _e('When should this contest end?', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Create Poll', 'pollmaster'), 'primary', 'submit_poll'); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('input[name="is_contest"]').change(function() {
                if ($(this).is(':checked')) {
                    $('.contest-fields').show();
                } else {
                    $('.contest-fields').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render edit poll form
     */
    private function render_edit_poll_form($poll_id) {
        $poll = $this->database->get_poll($poll_id);
        
        if (!$poll) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . __('Poll not found.', 'pollmaster') . '</p></div></div>';
            return;
        }
        
        if (isset($_POST['submit_poll'])) {
            $this->handle_poll_update($poll_id);
            $poll = $this->database->get_poll($poll_id); // Refresh data
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Edit Poll', 'pollmaster'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=pollmaster'); ?>" class="page-title-action"><?php _e('Back to Polls', 'pollmaster'); ?></a>
            
            <form method="post" enctype="multipart/form-data" class="pollmaster-poll-form">
                <?php wp_nonce_field('pollmaster_poll_action', 'pollmaster_poll_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="question"><?php _e('Question', 'pollmaster'); ?></label></th>
                        <td>
                            <input type="text" id="question" name="question" class="regular-text" maxlength="255" value="<?php echo esc_attr($poll->question); ?>" required>
                            <p class="description"><?php _e('Maximum 255 characters', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="option_a"><?php _e('Option A', 'pollmaster'); ?></label></th>
                        <td>
                            <input type="text" id="option_a" name="option_a" class="regular-text" maxlength="50" value="<?php echo esc_attr($poll->option_a); ?>" required>
                            <p class="description"><?php _e('Maximum 50 characters', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="option_b"><?php _e('Option B', 'pollmaster'); ?></label></th>
                        <td>
                            <input type="text" id="option_b" name="option_b" class="regular-text" maxlength="50" value="<?php echo esc_attr($poll->option_b); ?>" required>
                            <p class="description"><?php _e('Maximum 50 characters', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="poll_image"><?php _e('Image (Optional)', 'pollmaster'); ?></label></th>
                        <td>
                            <?php if ($poll->image_url): ?>
                                <div class="current-image">
                                    <img src="<?php echo esc_url($poll->image_url); ?>" style="max-width: 200px; height: auto;">
                                    <p><small><?php _e('Current image', 'pollmaster'); ?></small></p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="poll_image" name="poll_image" accept="image/jpeg,image/png">
                            <p class="description"><?php _e('Upload a new image to replace the current one. Maximum 5MB, PNG/JPEG only.', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Poll Type', 'pollmaster'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="is_weekly" value="1" <?php checked($poll->is_weekly, 1); ?>>
                                    <?php _e('Weekly Poll', 'pollmaster'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="is_contest" value="1" <?php checked($poll->is_contest, 1); ?>>
                                    <?php _e('Contest Poll', 'pollmaster'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr class="contest-fields" <?php echo $poll->is_contest ? '' : 'style="display: none;"'; ?>>
                        <th scope="row"><label for="contest_prize"><?php _e('Contest Prize', 'pollmaster'); ?></label></th>
                        <td>
                            <textarea id="contest_prize" name="contest_prize" class="large-text" rows="3"><?php echo esc_textarea($poll->contest_prize); ?></textarea>
                            <p class="description"><?php _e('Describe the prize for this contest.', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                    <tr class="contest-fields" <?php echo $poll->is_contest ? '' : 'style="display: none;"'; ?>>
                        <th scope="row"><label for="contest_end_date"><?php _e('Contest End Date', 'pollmaster'); ?></label></th>
                        <td>
                            <input type="datetime-local" id="contest_end_date" name="contest_end_date" value="<?php echo $poll->contest_end_date ? date('Y-m-d\TH:i', strtotime($poll->contest_end_date)) : ''; ?>">
                            <p class="description"><?php _e('When should this contest end?', 'pollmaster'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Update Poll', 'pollmaster'), 'primary', 'submit_poll'); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('input[name="is_contest"]').change(function() {
                if ($(this).is(':checked')) {
                    $('.contest-fields').show();
                } else {
                    $('.contest-fields').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle poll submission
     */
    private function handle_poll_submission() {
        if (!wp_verify_nonce($_POST['pollmaster_poll_nonce'], 'pollmaster_poll_action')) {
            wp_die(__('Security check failed.', 'pollmaster'));
        }
        
        $data = array(
            'user_id' => get_current_user_id(),
            'question' => sanitize_text_field($_POST['question']),
            'option_a' => sanitize_text_field($_POST['option_a']),
            'option_b' => sanitize_text_field($_POST['option_b']),
            'is_weekly' => isset($_POST['is_weekly']) ? 1 : 0,
            'is_contest' => isset($_POST['is_contest']) ? 1 : 0
        );
        
        if (isset($_POST['contest_prize'])) {
            $data['contest_prize'] = sanitize_textarea_field($_POST['contest_prize']);
        }
        
        if (isset($_POST['contest_end_date']) && !empty($_POST['contest_end_date'])) {
            $data['contest_end_date'] = sanitize_text_field($_POST['contest_end_date']);
        }
        
        // Handle image upload
        if (isset($_FILES['poll_image']) && $_FILES['poll_image']['error'] === UPLOAD_ERR_OK) {
            $image_url = $this->handle_image_upload($_FILES['poll_image']);
            if ($image_url) {
                $data['image_url'] = $image_url;
            }
        }
        
        $poll_id = $this->database->create_poll($data);
        
        if ($poll_id) {
            echo '<div class="notice notice-success"><p>' . __('Poll created successfully!', 'pollmaster') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to create poll. Please try again.', 'pollmaster') . '</p></div>';
        }
    }
    
    /**
     * Handle poll update
     */
    private function handle_poll_update($poll_id) {
        if (!wp_verify_nonce($_POST['pollmaster_poll_nonce'], 'pollmaster_poll_action')) {
            wp_die(__('Security check failed.', 'pollmaster'));
        }
        
        $data = array(
            'question' => sanitize_text_field($_POST['question']),
            'option_a' => sanitize_text_field($_POST['option_a']),
            'option_b' => sanitize_text_field($_POST['option_b']),
            'is_weekly' => isset($_POST['is_weekly']) ? 1 : 0,
            'is_contest' => isset($_POST['is_contest']) ? 1 : 0
        );
        
        if (isset($_POST['contest_prize'])) {
            $data['contest_prize'] = sanitize_textarea_field($_POST['contest_prize']);
        }
        
        if (isset($_POST['contest_end_date']) && !empty($_POST['contest_end_date'])) {
            $data['contest_end_date'] = sanitize_text_field($_POST['contest_end_date']);
        }
        
        // Handle image upload
        if (isset($_FILES['poll_image']) && $_FILES['poll_image']['error'] === UPLOAD_ERR_OK) {
            $image_url = $this->handle_image_upload($_FILES['poll_image']);
            if ($image_url) {
                $data['image_url'] = $image_url;
            }
        }
        
        $result = $this->database->update_poll($poll_id, $data);
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>' . __('Poll updated successfully!', 'pollmaster') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to update poll. Please try again.', 'pollmaster') . '</p></div>';
        }
    }
    
    /**
     * Handle image upload
     */
    private function handle_image_upload($file) {
        $allowed_types = array('image/jpeg', 'image/png');
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        if ($file['size'] > $max_size) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $pollmaster_dir = $upload_dir['basedir'] . '/pollmaster';
        
        if (!file_exists($pollmaster_dir)) {
            wp_mkdir_p($pollmaster_dir);
        }
        
        $filename = uniqid('poll_') . '_' . sanitize_file_name($file['name']);
        $filepath = $pollmaster_dir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $upload_dir['baseurl'] . '/pollmaster/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Render weekly poll form
     */
    private function render_weekly_poll_form() {
        // Implementation for weekly poll management
        echo '<div class="wrap"><h1>' . __('Weekly Poll Management', 'pollmaster') . '</h1>';
        echo '<p>' . __('Weekly poll management interface will be implemented here.', 'pollmaster') . '</p></div>';
    }
    
    /**
     * Render contests page
     */
    private function render_contests_page() {
        // Implementation for contest management
        echo '<div class="wrap"><h1>' . __('Contest Management', 'pollmaster') . '</h1>';
        echo '<p>' . __('Contest management interface will be implemented here.', 'pollmaster') . '</p></div>';
    }
    
    /**
     * Render settings page
     */
    private function render_settings_page() {
        if (isset($_POST['submit'])) {
            update_option('pollmaster_settings', $_POST['pollmaster_settings']);
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'pollmaster') . '</p></div>';
        }
        
        $settings = get_option('pollmaster_settings', array());
        
        ?>
        <div class="wrap">
            <h1><?php _e('PollMaster Settings', 'pollmaster'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('pollmaster_settings_action', 'pollmaster_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Pop-up Settings', 'pollmaster'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="pollmaster_settings[auto_popup]" value="1" <?php checked(isset($settings['auto_popup']) ? $settings['auto_popup'] : 0, 1); ?>>
                                    <?php _e('Enable automatic pop-up on homepage', 'pollmaster'); ?>
                                </label><br>
                                <label>
                                    <input type="number" name="pollmaster_settings[popup_delay]" value="<?php echo isset($settings['popup_delay']) ? esc_attr($settings['popup_delay']) : 3; ?>" min="0" max="60">
                                    <?php _e('Pop-up delay (seconds)', 'pollmaster'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Colors', 'pollmaster'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <?php _e('Primary Color:', 'pollmaster'); ?>
                                    <input type="color" name="pollmaster_settings[primary_color]" value="<?php echo isset($settings['primary_color']) ? esc_attr($settings['primary_color']) : '#3b82f6'; ?>">
                                </label><br>
                                <label>
                                    <?php _e('Secondary Color:', 'pollmaster'); ?>
                                    <input type="color" name="pollmaster_settings[secondary_color]" value="<?php echo isset($settings['secondary_color']) ? esc_attr($settings['secondary_color']) : '#10b981'; ?>">
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Social Sharing', 'pollmaster'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="pollmaster_settings[enable_facebook]" value="1" <?php checked(isset($settings['enable_facebook']) ? $settings['enable_facebook'] : 1, 1); ?>>
                                    <?php _e('Enable Facebook sharing', 'pollmaster'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="pollmaster_settings[enable_twitter]" value="1" <?php checked(isset($settings['enable_twitter']) ? $settings['enable_twitter'] : 1, 1); ?>>
                                    <?php _e('Enable Twitter/X sharing', 'pollmaster'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="pollmaster_settings[enable_whatsapp]" value="1" <?php checked(isset($settings['enable_whatsapp']) ? $settings['enable_whatsapp'] : 1, 1); ?>>
                                    <?php _e('Enable WhatsApp sharing', 'pollmaster'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="pollmaster_settings[enable_linkedin]" value="1" <?php checked(isset($settings['enable_linkedin']) ? $settings['enable_linkedin'] : 1, 1); ?>>
                                    <?php _e('Enable LinkedIn sharing', 'pollmaster'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle admin AJAX requests
     */
    public function handle_admin_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        $action = sanitize_text_field($_POST['admin_action']);
        
        switch ($action) {
            case 'delete_poll':
                $poll_id = (int)$_POST['poll_id'];
                $result = $this->database->delete_poll($poll_id);
                wp_send_json_success(array('deleted' => $result !== false));
                break;
                
            default:
                wp_send_json_error('Invalid action');
                break;
        }
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['auto_popup'])) {
            $sanitized['auto_popup'] = 1;
        }
        
        if (isset($input['popup_delay'])) {
            $sanitized['popup_delay'] = max(0, min(60, (int)$input['popup_delay']));
        }
        
        if (isset($input['primary_color'])) {
            $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        }
        
        if (isset($input['secondary_color'])) {
            $sanitized['secondary_color'] = sanitize_hex_color($input['secondary_color']);
        }
        
        $social_platforms = array('enable_facebook', 'enable_twitter', 'enable_whatsapp', 'enable_linkedin');
        foreach ($social_platforms as $platform) {
            if (isset($input[$platform])) {
                $sanitized[$platform] = 1;
            }
        }
        
        return $sanitized;
    }
}