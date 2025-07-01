<?php
/**
 * PollMaster Frontend Class
 * 
 * Handles frontend functionality and user interface
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Frontend {
    
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new PollMaster_Database();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'add_popup_html'));
        add_action('init', array($this, 'init'));
        add_action('wp_head', array($this, 'add_custom_styles'));
        add_filter('the_content', array($this, 'add_user_poll_interface'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Add user poll creation to user profile
        add_action('show_user_profile', array($this, 'add_user_polls_section'));
        add_action('edit_user_profile', array($this, 'add_user_polls_section'));
        add_action('personal_options_update', array($this, 'handle_user_poll_submission'));
        add_action('edit_user_profile_update', array($this, 'handle_user_poll_submission'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue Tailwind CSS from CDN
        wp_enqueue_style('tailwindcss', 'https://cdn.tailwindcss.com', array(), POLLMASTER_VERSION);
        
        // Enqueue custom styles
        wp_enqueue_style('pollmaster-frontend', POLLMASTER_PLUGIN_URL . 'assets/css/frontend.css', array(), POLLMASTER_VERSION);
        
        // Enqueue scripts
        wp_enqueue_script('pollmaster-frontend', POLLMASTER_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), POLLMASTER_VERSION, true);
        
        // Localize script
        wp_localize_script('pollmaster-frontend', 'pollmaster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pollmaster_nonce'),
            'user_id' => get_current_user_id(),
            'is_user_logged_in' => is_user_logged_in(),
            'strings' => array(
                'vote_success' => __('Thank you for voting!', 'pollmaster'),
                'vote_error' => __('Error casting vote. Please try again.', 'pollmaster'),
                'already_voted' => __('You have already voted on this poll.', 'pollmaster'),
                'login_required' => __('Please log in to vote.', 'pollmaster'),
                'share_success' => __('Poll shared successfully!', 'pollmaster')
            )
        ));
    }
    
    /**
     * Add custom styles from settings
     */
    public function add_custom_styles() {
        $settings = get_option('pollmaster_settings', array());
        $primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#3b82f6';
        $secondary_color = isset($settings['secondary_color']) ? $settings['secondary_color'] : '#10b981';
        
        echo "<style>
        :root {
            --pollmaster-primary: {$primary_color};
            --pollmaster-secondary: {$secondary_color};
        }
        .pollmaster-primary { color: var(--pollmaster-primary) !important; }
        .pollmaster-bg-primary { background-color: var(--pollmaster-primary) !important; }
        .pollmaster-border-primary { border-color: var(--pollmaster-primary) !important; }
        .pollmaster-secondary { color: var(--pollmaster-secondary) !important; }
        .pollmaster-bg-secondary { background-color: var(--pollmaster-secondary) !important; }
        </style>";
    }
    
    /**
     * Add popup HTML to footer
     */
    public function add_popup_html() {
        if (!is_user_logged_in() || !is_front_page()) {
            return;
        }
        
        $settings = get_option('pollmaster_settings', array());
        if (!isset($settings['auto_popup']) || !$settings['auto_popup']) {
            return;
        }
        
        $latest_poll = $this->database->get_latest_poll();
        if (!$latest_poll) {
            return;
        }
        
        $user_id = get_current_user_id();
        $has_voted = $this->database->has_user_voted($latest_poll->id, $user_id);
        
        // Check if user dismissed popup in last 24 hours
        $dismissed_time = get_user_meta($user_id, 'pollmaster_popup_dismissed_' . $latest_poll->id, true);
        if ($dismissed_time && (time() - $dismissed_time) < 86400) {
            return;
        }
        
        $this->render_poll_popup($latest_poll, $has_voted);
    }
    
    /**
     * Render poll popup
     */
    private function render_poll_popup($poll, $has_voted = false) {
        $results = $this->database->get_poll_results($poll->id);
        $settings = get_option('pollmaster_settings', array());
        $delay = isset($settings['popup_delay']) ? (int)$settings['popup_delay'] : 3;
        
        ?>
        <div id="pollmaster-popup-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="pollmaster-popup">
                <div class="p-6">
                    <!-- Close button -->
                    <button class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors" id="pollmaster-close-popup">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    
                    <!-- Poll content -->
                    <div class="pollmaster-poll-container" data-poll-id="<?php echo $poll->id; ?>">
                        <?php if ($poll->image_url): ?>
                            <div class="mb-4">
                                <img src="<?php echo esc_url($poll->image_url); ?>" alt="Poll Image" class="w-full h-48 object-cover rounded-lg">
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo esc_html($poll->question); ?></h3>
                        
                        <?php if ($poll->is_contest): ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-yellow-800"><?php _e('Contest Poll', 'pollmaster'); ?></span>
                                </div>
                                <?php if ($poll->contest_prize): ?>
                                    <p class="text-sm text-yellow-700 mt-1"><?php echo esc_html($poll->contest_prize); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$has_voted): ?>
                            <!-- Voting interface -->
                            <div class="pollmaster-voting-interface">
                                <div class="space-y-3">
                                    <button class="pollmaster-vote-btn w-full p-3 text-left bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg transition-all duration-200" data-option="option_a">
                                        <span class="font-medium text-gray-800"><?php echo esc_html($poll->option_a); ?></span>
                                    </button>
                                    <button class="pollmaster-vote-btn w-full p-3 text-left bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg transition-all duration-200" data-option="option_b">
                                        <span class="font-medium text-gray-800"><?php echo esc_html($poll->option_b); ?></span>
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Results interface -->
                            <div class="pollmaster-results-interface">
                                <?php $this->render_poll_results($poll, $results); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Social sharing -->
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-600 mb-3"><?php _e('Share this poll:', 'pollmaster'); ?></p>
                            <div class="flex space-x-2">
                                <?php $this->render_social_buttons($poll); ?>
                            </div>
                        </div>
                        
                        <!-- Dismiss options -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <button class="text-sm text-gray-500 hover:text-gray-700" id="pollmaster-dismiss-24h">
                                <?php _e("Don't show for 24 hours", 'pollmaster'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Show popup after delay
            setTimeout(function() {
                $('#pollmaster-popup-overlay').removeClass('hidden').addClass('flex');
                setTimeout(function() {
                    $('#pollmaster-popup').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
                }, 50);
            }, <?php echo $delay * 1000; ?>);
        });
        </script>
        <?php
    }
    
    /**
     * Render poll results
     */
    private function render_poll_results($poll, $results) {
        ?>
        <div class="space-y-3">
            <div class="pollmaster-result-item">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-medium text-gray-800"><?php echo esc_html($poll->option_a); ?></span>
                    <span class="text-sm font-bold pollmaster-primary"><?php echo $results['percentages']['option_a']; ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="pollmaster-bg-primary h-3 rounded-full transition-all duration-500" style="width: <?php echo $results['percentages']['option_a']; ?>%"></div>
                </div>
                <div class="text-xs text-gray-500 mt-1"><?php echo $results['vote_counts']['option_a']; ?> <?php _e('votes', 'pollmaster'); ?></div>
            </div>
            
            <div class="pollmaster-result-item">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-medium text-gray-800"><?php echo esc_html($poll->option_b); ?></span>
                    <span class="text-sm font-bold pollmaster-secondary"><?php echo $results['percentages']['option_b']; ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="pollmaster-bg-secondary h-3 rounded-full transition-all duration-500" style="width: <?php echo $results['percentages']['option_b']; ?>%"></div>
                </div>
                <div class="text-xs text-gray-500 mt-1"><?php echo $results['vote_counts']['option_b']; ?> <?php _e('votes', 'pollmaster'); ?></div>
            </div>
            
            <div class="text-center text-sm text-gray-600 mt-4">
                <?php printf(__('Total votes: %d', 'pollmaster'), $results['total_votes']); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render social sharing buttons
     */
    private function render_social_buttons($poll) {
        $settings = get_option('pollmaster_settings', array());
        $poll_url = home_url('/?poll_id=' . $poll->id);
        $poll_text = urlencode($poll->question);
        
        if (isset($settings['enable_facebook']) && $settings['enable_facebook']) {
            echo '<a href="#" class="pollmaster-share-btn inline-flex items-center px-3 py-2 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors" data-platform="facebook" data-url="' . esc_url($poll_url) . '">';
            echo '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
            echo __('Facebook', 'pollmaster') . '</a>';
        }
        
        if (isset($settings['enable_twitter']) && $settings['enable_twitter']) {
            echo '<a href="#" class="pollmaster-share-btn inline-flex items-center px-3 py-2 bg-gray-800 text-white text-xs rounded hover:bg-gray-900 transition-colors ml-2" data-platform="twitter" data-url="' . esc_url($poll_url) . '" data-text="' . $poll_text . '">';
            echo '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>';
            echo __('Twitter', 'pollmaster') . '</a>';
        }
        
        if (isset($settings['enable_whatsapp']) && $settings['enable_whatsapp']) {
            echo '<a href="#" class="pollmaster-share-btn inline-flex items-center px-3 py-2 bg-green-600 text-white text-xs rounded hover:bg-green-700 transition-colors ml-2" data-platform="whatsapp" data-url="' . esc_url($poll_url) . '" data-text="' . $poll_text . '">';
            echo '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/></svg>';
            echo __('WhatsApp', 'pollmaster') . '</a>';
        }
        
        if (isset($settings['enable_linkedin']) && $settings['enable_linkedin']) {
            echo '<a href="#" class="pollmaster-share-btn inline-flex items-center px-3 py-2 bg-blue-700 text-white text-xs rounded hover:bg-blue-800 transition-colors ml-2" data-platform="linkedin" data-url="' . esc_url($poll_url) . '">';
            echo '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>';
            echo __('LinkedIn', 'pollmaster') . '</a>';
        }
    }
    
    /**
     * Add user polls section to profile
     */
    public function add_user_polls_section($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        
        ?>
        <h3><?php _e('My Polls', 'pollmaster'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="pollmaster_create_poll"><?php _e('Create New Poll', 'pollmaster'); ?></label></th>
                <td>
                    <a href="#" id="pollmaster-show-create-form" class="button"><?php _e('Create Poll', 'pollmaster'); ?></a>
                    
                    <div id="pollmaster-create-form" style="display: none; margin-top: 15px; padding: 15px; border: 1px solid #ddd; background: #f9f9f9;">
                        <form method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('pollmaster_user_poll', 'pollmaster_user_poll_nonce'); ?>
                            
                            <p>
                                <label for="user_poll_question"><?php _e('Question:', 'pollmaster'); ?></label><br>
                                <input type="text" id="user_poll_question" name="user_poll_question" class="regular-text" maxlength="255" required>
                            </p>
                            
                            <p>
                                <label for="user_poll_option_a"><?php _e('Option A:', 'pollmaster'); ?></label><br>
                                <input type="text" id="user_poll_option_a" name="user_poll_option_a" class="regular-text" maxlength="50" required>
                            </p>
                            
                            <p>
                                <label for="user_poll_option_b"><?php _e('Option B:', 'pollmaster'); ?></label><br>
                                <input type="text" id="user_poll_option_b" name="user_poll_option_b" class="regular-text" maxlength="50" required>
                            </p>
                            
                            <p>
                                <label for="user_poll_image"><?php _e('Image (Optional):', 'pollmaster'); ?></label><br>
                                <input type="file" id="user_poll_image" name="user_poll_image" accept="image/jpeg,image/png">
                                <br><small><?php _e('Maximum 5MB, PNG/JPEG only', 'pollmaster'); ?></small>
                            </p>
                            
                            <p>
                                <input type="submit" name="create_user_poll" class="button button-primary" value="<?php _e('Create Poll', 'pollmaster'); ?>">
                                <button type="button" id="pollmaster-cancel-create" class="button"><?php _e('Cancel', 'pollmaster'); ?></button>
                            </p>
                        </form>
                    </div>
                </td>
            </tr>
        </table>
        
        <?php
        // Display user's polls
        $user_polls = $this->database->get_polls(1, 10, $user->ID);
        if (!empty($user_polls)) {
            echo '<h4>' . __('Your Polls', 'pollmaster') . '</h4>';
            echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">';
            echo '<thead><tr><th>' . __('Question', 'pollmaster') . '</th><th>' . __('Votes', 'pollmaster') . '</th><th>' . __('Date', 'pollmaster') . '</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($user_polls as $poll) {
                $results = $this->database->get_poll_results($poll->id);
                echo '<tr>';
                echo '<td>' . esc_html($poll->question) . '</td>';
                echo '<td>' . $results['total_votes'] . '</td>';
                echo '<td>' . date_i18n(get_option('date_format'), strtotime($poll->created_at)) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#pollmaster-show-create-form').click(function(e) {
                e.preventDefault();
                $('#pollmaster-create-form').slideDown();
                $(this).hide();
            });
            
            $('#pollmaster-cancel-create').click(function() {
                $('#pollmaster-create-form').slideUp();
                $('#pollmaster-show-create-form').show();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle user poll submission
     */
    public function handle_user_poll_submission($user_id) {
        if (!isset($_POST['create_user_poll']) || !wp_verify_nonce($_POST['pollmaster_user_poll_nonce'], 'pollmaster_user_poll')) {
            return;
        }
        
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        $data = array(
            'user_id' => $user_id,
            'question' => sanitize_text_field($_POST['user_poll_question']),
            'option_a' => sanitize_text_field($_POST['user_poll_option_a']),
            'option_b' => sanitize_text_field($_POST['user_poll_option_b'])
        );
        
        // Handle image upload
        if (isset($_FILES['user_poll_image']) && $_FILES['user_poll_image']['error'] === UPLOAD_ERR_OK) {
            $image_url = $this->handle_user_image_upload($_FILES['user_poll_image']);
            if ($image_url) {
                $data['image_url'] = $image_url;
            }
        }
        
        $poll_id = $this->database->create_poll($data);
        
        if ($poll_id) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('Poll created successfully!', 'pollmaster') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Failed to create poll. Please try again.', 'pollmaster') . '</p></div>';
            });
        }
    }
    
    /**
     * Handle user image upload
     */
    private function handle_user_image_upload($file) {
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
        
        $filename = uniqid('user_poll_') . '_' . sanitize_file_name($file['name']);
        $filepath = $pollmaster_dir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $upload_dir['baseurl'] . '/pollmaster/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Add user poll interface to specific pages
     */
    public function add_user_poll_interface($content) {
        // This can be used to add poll creation interface to specific pages
        return $content;
    }
    
    /**
     * Get poll HTML for display
     */
    public function get_poll_html($poll_id, $show_results = false) {
        $poll = $this->database->get_poll($poll_id);
        if (!$poll) {
            return '<p>' . __('Poll not found.', 'pollmaster') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $has_voted = $user_id ? $this->database->has_user_voted($poll_id, $user_id) : false;
        $results = $this->database->get_poll_results($poll_id);
        
        ob_start();
        ?>
        <div class="pollmaster-poll-widget bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto" data-poll-id="<?php echo $poll->id; ?>">
            <?php if ($poll->image_url): ?>
                <div class="mb-4">
                    <img src="<?php echo esc_url($poll->image_url); ?>" alt="Poll Image" class="w-full h-48 object-cover rounded-lg">
                </div>
            <?php endif; ?>
            
            <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo esc_html($poll->question); ?></h3>
            
            <?php if ($poll->is_contest): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <span class="text-sm font-medium text-yellow-800"><?php _e('Contest Poll', 'pollmaster'); ?></span>
                    </div>
                    <?php if ($poll->contest_prize): ?>
                        <p class="text-sm text-yellow-700 mt-1"><?php echo esc_html($poll->contest_prize); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$has_voted && !$show_results && is_user_logged_in()): ?>
                <!-- Voting interface -->
                <div class="pollmaster-voting-interface">
                    <div class="space-y-3">
                        <button class="pollmaster-vote-btn w-full p-3 text-left bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg transition-all duration-200" data-option="option_a">
                            <span class="font-medium text-gray-800"><?php echo esc_html($poll->option_a); ?></span>
                        </button>
                        <button class="pollmaster-vote-btn w-full p-3 text-left bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg transition-all duration-200" data-option="option_b">
                            <span class="font-medium text-gray-800"><?php echo esc_html($poll->option_b); ?></span>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Results interface -->
                <div class="pollmaster-results-interface">
                    <?php $this->render_poll_results($poll, $results); ?>
                </div>
            <?php endif; ?>
            
            <!-- Social sharing -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-600 mb-3"><?php _e('Share this poll:', 'pollmaster'); ?></p>
                <div class="flex flex-wrap gap-2">
                    <?php $this->render_social_buttons($poll); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}