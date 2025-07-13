<?php
/**
 * PollMaster AJAX Class
 * 
 * Handles AJAX requests for voting, sharing, and other dynamic interactions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Ajax {
    
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new PollMaster_Database();
        
        // AJAX actions for logged-in users
        add_action('wp_ajax_pollmaster_vote', array($this, 'handle_vote'));
        add_action('wp_ajax_pollmaster_share', array($this, 'handle_share'));
        add_action('wp_ajax_pollmaster_dismiss_popup', array($this, 'handle_dismiss_popup'));
        add_action('wp_ajax_pollmaster_get_results', array($this, 'handle_get_results'));
        add_action('wp_ajax_pollmaster_load_poll', array($this, 'handle_load_poll'));
        add_action('wp_ajax_get_poll_data', array($this, 'handle_get_poll_data'));
        add_action('wp_ajax_submit_vote', array($this, 'handle_submit_vote'));
        
        // AJAX actions for non-logged-in users (public)
        add_action('wp_ajax_nopriv_pollmaster_share', array($this, 'handle_share'));
        add_action('wp_ajax_nopriv_pollmaster_get_results', array($this, 'handle_get_results'));
        add_action('wp_ajax_nopriv_pollmaster_load_poll', array($this, 'handle_load_poll'));
        add_action('wp_ajax_nopriv_get_poll_data', array($this, 'handle_get_poll_data'));
        add_action('wp_ajax_nopriv_submit_vote', array($this, 'handle_submit_vote'));
    }
    
    /**
     * Handle vote submission
     */
    public function handle_vote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'pollmaster')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to vote.', 'pollmaster')
            ));
        }
        
        $poll_id = (int) $_POST['poll_id'];
        $vote_option = sanitize_text_field($_POST['vote_option']);
        $user_id = get_current_user_id();
        
        // Validate inputs
        if (!$poll_id || !in_array($vote_option, array('option_a', 'option_b'))) {
            wp_send_json_error(array(
                'message' => __('Invalid vote data.', 'pollmaster')
            ));
        }
        
        // Check if poll exists
        $poll = $this->database->get_poll($poll_id);
        if (!$poll) {
            wp_send_json_error(array(
                'message' => __('Poll not found.', 'pollmaster')
            ));
        }
        
        // Check if user already voted
        if ($this->database->has_user_voted($poll_id, $user_id)) {
            wp_send_json_error(array(
                'message' => __('You have already voted on this poll.', 'pollmaster')
            ));
        }
        
        // Cast vote
        $vote_result = $this->database->cast_vote($poll_id, $user_id, $vote_option);
        
        if (!$vote_result) {
            wp_send_json_error(array(
                'message' => __('Failed to cast vote. Please try again.', 'pollmaster')
            ));
        }
        
        // Get updated results
        $results = $this->database->get_poll_results($poll_id);
        
        // Prepare response data
        $response_data = array(
            'message' => __('Thank you for voting!', 'pollmaster'),
            'results' => $results,
            'poll' => array(
                'id' => $poll->id,
                'question' => $poll->question,
                'option_a' => $poll->option_a,
                'option_b' => $poll->option_b,
                'image_url' => $poll->image_url,
                'is_contest' => (bool) $poll->is_contest,
                'contest_prize' => $poll->contest_prize
            )
        );
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Handle social share tracking
     */
    public function handle_share() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'pollmaster')
            ));
        }
        
        $poll_id = (int) $_POST['poll_id'];
        $platform = sanitize_text_field($_POST['platform']);
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        
        // Validate inputs
        if (!$poll_id || !in_array($platform, array('facebook', 'twitter', 'whatsapp', 'linkedin'))) {
            wp_send_json_error(array(
                'message' => __('Invalid share data.', 'pollmaster')
            ));
        }
        
        // Check if poll exists
        $poll = $this->database->get_poll($poll_id);
        if (!$poll) {
            wp_send_json_error(array(
                'message' => __('Poll not found.', 'pollmaster')
            ));
        }
        
        // Record share
        $this->database->record_share($poll_id, $platform, $user_id);
        
        // Generate share URL based on platform
        $share_url = $this->generate_share_url($poll, $platform);
        
        wp_send_json_success(array(
            'message' => __('Share recorded successfully.', 'pollmaster'),
            'share_url' => $share_url
        ));
    }
    
    /**
     * Handle popup dismissal
     */
    public function handle_dismiss_popup() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'pollmaster')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'pollmaster')
            ));
        }
        
        $poll_id = (int) $_POST['poll_id'];
        $dismiss_duration = sanitize_text_field($_POST['dismiss_duration']); // '24h' or 'permanent'
        $user_id = get_current_user_id();
        
        if (!$poll_id) {
            wp_send_json_error(array(
                'message' => __('Invalid poll ID.', 'pollmaster')
            ));
        }
        
        // Set dismissal timestamp
        if ($dismiss_duration === '24h') {
            update_user_meta($user_id, 'pollmaster_popup_dismissed_' . $poll_id, time());
        } elseif ($dismiss_duration === 'permanent') {
            update_user_meta($user_id, 'pollmaster_popup_dismissed_' . $poll_id, 'permanent');
        }
        
        wp_send_json_success(array(
            'message' => __('Popup dismissed.', 'pollmaster')
        ));
    }
    
    /**
     * Handle get poll results request
     */
    public function handle_get_results() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'pollmaster')
            ));
        }
        
        $poll_id = (int) $_POST['poll_id'];
        
        if (!$poll_id) {
            wp_send_json_error(array(
                'message' => __('Invalid poll ID.', 'pollmaster')
            ));
        }
        
        // Check if poll exists
        $poll = $this->database->get_poll($poll_id);
        if (!$poll) {
            wp_send_json_error(array(
                'message' => __('Poll not found.', 'pollmaster')
            ));
        }
        
        // Get results
        $results = $this->database->get_poll_results($poll_id);
        
        // Check if user voted (if logged in)
        $user_voted = false;
        if (is_user_logged_in()) {
            $user_voted = $this->database->has_user_voted($poll_id, get_current_user_id());
        }
        
        wp_send_json_success(array(
            'results' => $results,
            'user_voted' => $user_voted,
            'poll' => array(
                'id' => $poll->id,
                'question' => $poll->question,
                'option_a' => $poll->option_a,
                'option_b' => $poll->option_b,
                'image_url' => $poll->image_url,
                'is_contest' => (bool) $poll->is_contest,
                'contest_prize' => $poll->contest_prize
            )
        ));
    }
    
    /**
     * Handle load poll request
     */
    public function handle_load_poll() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'pollmaster')
            ));
        }
        
        $poll_id = isset($_POST['poll_id']) ? (int) $_POST['poll_id'] : 0;
        $poll_type = isset($_POST['poll_type']) ? sanitize_text_field($_POST['poll_type']) : 'latest';
        
        // Get poll based on type
        if ($poll_id) {
            $poll = $this->database->get_poll($poll_id);
        } elseif ($poll_type === 'latest') {
            $poll = $this->database->get_latest_poll();
        } else {
            wp_send_json_error(array(
                'message' => __('Invalid poll request.', 'pollmaster')
            ));
        }
        
        if (!$poll) {
            wp_send_json_error(array(
                'message' => __('Poll not found.', 'pollmaster')
            ));
        }
        
        // Get results
        $results = $this->database->get_poll_results($poll->id);
        
        // Check if user voted (if logged in)
        $user_voted = false;
        if (is_user_logged_in()) {
            $user_voted = $this->database->has_user_voted($poll->id, get_current_user_id());
        }
        
        // Generate poll HTML
        $poll_html = $this->generate_poll_html($poll, $user_voted, $results);
        
        wp_send_json_success(array(
            'poll_html' => $poll_html,
            'results' => $results,
            'user_voted' => $user_voted,
            'poll' => array(
                'id' => $poll->id,
                'question' => $poll->question,
                'option_a' => $poll->option_a,
                'option_b' => $poll->option_b,
                'image_url' => $poll->image_url,
                'is_contest' => (bool) $poll->is_contest,
                'contest_prize' => $poll->contest_prize,
                'is_weekly' => (bool) $poll->is_weekly
            )
        ));
    }
    
    /**
     * Generate share URL for different platforms
     */
    private function generate_share_url($poll, $platform) {
        $poll_url = home_url('/?poll_id=' . $poll->id);
        $poll_text = urlencode($poll->question . ' - Vote now!');
        $site_name = urlencode(get_bloginfo('name'));
        
        switch ($platform) {
            case 'facebook':
                return 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($poll_url);
                
            case 'twitter':
                return 'https://twitter.com/intent/tweet?text=' . $poll_text . '&url=' . urlencode($poll_url) . '&via=' . $site_name;
                
            case 'whatsapp':
                return 'https://wa.me/?text=' . $poll_text . '%20' . urlencode($poll_url);
                
            case 'linkedin':
                return 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($poll_url);
                
            default:
                return $poll_url;
        }
    }
    
    /**
     * Validate poll access
     */
    private function validate_poll_access($poll_id, $user_id = null) {
        $poll = $this->database->get_poll($poll_id);
        
        if (!$poll) {
            return false;
        }
        
        // Check if poll is active
        if ($poll->status !== 'active') {
            return false;
        }
        
        // Check contest end date
        if ($poll->is_contest && $poll->contest_end_date) {
            $end_date = strtotime($poll->contest_end_date);
            if ($end_date && time() > $end_date) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get poll statistics for admin
     */
    public function get_poll_statistics($poll_id) {
        global $wpdb;
        
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        $shares_table = $wpdb->prefix . 'pollmaster_shares';
        
        // Get vote statistics
        $vote_stats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    vote_option,
                    COUNT(*) as count,
                    DATE(voted_at) as vote_date
                 FROM $votes_table 
                 WHERE poll_id = %d 
                 GROUP BY vote_option, DATE(voted_at)
                 ORDER BY vote_date DESC",
                $poll_id
            )
        );
        
        // Get share statistics
        $share_stats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    platform,
                    COUNT(*) as count,
                    DATE(shared_at) as share_date
                 FROM $shares_table 
                 WHERE poll_id = %d 
                 GROUP BY platform, DATE(shared_at)
                 ORDER BY share_date DESC",
                $poll_id
            )
        );
        
        // Get hourly vote distribution for today
        $hourly_votes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    HOUR(voted_at) as hour,
                    COUNT(*) as count
                 FROM $votes_table 
                 WHERE poll_id = %d 
                 AND DATE(voted_at) = CURDATE()
                 GROUP BY HOUR(voted_at)
                 ORDER BY hour",
                $poll_id
            )
        );
        
        return array(
            'vote_stats' => $vote_stats,
            'share_stats' => $share_stats,
            'hourly_votes' => $hourly_votes
        );
    }
    
    /**
     * Handle bulk poll operations (admin only)
     */
    public function handle_bulk_operations() {
        // Verify nonce and admin permissions
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_admin_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'pollmaster')
            ));
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $poll_ids = array_map('intval', $_POST['poll_ids']);
        
        if (empty($poll_ids)) {
            wp_send_json_error(array(
                'message' => __('No polls selected.', 'pollmaster')
            ));
        }
        
        $success_count = 0;
        
        switch ($action) {
            case 'delete':
                foreach ($poll_ids as $poll_id) {
                    if ($this->database->delete_poll($poll_id)) {
                        $success_count++;
                    }
                }
                break;
                
            case 'make_weekly':
                foreach ($poll_ids as $poll_id) {
                    if ($this->database->update_poll($poll_id, array('is_weekly' => 1))) {
                        $success_count++;
                    }
                }
                break;
                
            case 'remove_weekly':
                foreach ($poll_ids as $poll_id) {
                    if ($this->database->update_poll($poll_id, array('is_weekly' => 0))) {
                        $success_count++;
                    }
                }
                break;
                
            default:
                wp_send_json_error(array(
                    'message' => __('Invalid bulk action.', 'pollmaster')
                ));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d polls processed successfully.', 'pollmaster'), $success_count),
            'processed_count' => $success_count
        ));
    }
    
    /**
     * Generate poll HTML for AJAX responses
     */
    private function generate_poll_html($poll, $user_voted, $results) {
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
            
            <?php if (!$user_voted && is_user_logged_in()): ?>
                <!-- Voting interface -->
                <div class="pollmaster-voting-interface">
                    <div class="space-y-3">
                        <button class="pollmaster-vote-btn w-full p-3 text-left bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg transition-all duration-200" data-poll-id="<?php echo $poll->id; ?>" data-option="option_a">
                            <span class="font-medium text-gray-800"><?php echo esc_html($poll->option_a); ?></span>
                        </button>
                        <button class="pollmaster-vote-btn w-full p-3 text-left bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg transition-all duration-200" data-poll-id="<?php echo $poll->id; ?>" data-option="option_b">
                            <span class="font-medium text-gray-800"><?php echo esc_html($poll->option_b); ?></span>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Results interface -->
                <div class="pollmaster-results-interface">
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
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle contest winner announcement
     */
    public function handle_announce_winner() {
        // Verify nonce and admin permissions
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_admin_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'pollmaster')
            ));
        }
        
        $poll_id = (int) $_POST['poll_id'];
        $prize_description = sanitize_textarea_field($_POST['prize_description']);
        
        if (!$poll_id) {
            wp_send_json_error(array(
                'message' => __('Invalid poll ID.', 'pollmaster')
            ));
        }
        
        // Get poll and check if it's a contest
        $poll = $this->database->get_poll($poll_id);
        if (!$poll || !$poll->is_contest) {
            wp_send_json_error(array(
                'message' => __('Poll is not a contest.', 'pollmaster')
            ));
        }
        
        // Get poll results to determine winner
        $results = $this->database->get_poll_results($poll_id);
        
        // Determine winning option
        $winning_option = $results['vote_counts']['option_a'] > $results['vote_counts']['option_b'] ? 'option_a' : 'option_b';
        $winning_votes = max($results['vote_counts']['option_a'], $results['vote_counts']['option_b']);
        
        // Get a random voter from the winning option (simplified winner selection)
        global $wpdb;
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        $winner = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT user_id FROM $votes_table 
                 WHERE poll_id = %d AND vote_option = %s 
                 ORDER BY RAND() LIMIT 1",
                $poll_id,
                $winning_option
            )
        );
        
        if (!$winner) {
            wp_send_json_error(array(
                'message' => __('No votes found for this contest.', 'pollmaster')
            ));
        }
        
        // Announce winner
        $result = $this->database->announce_contest_winner(
            $poll_id,
            $winner->user_id,
            $prize_description,
            $winning_votes
        );
        
        if ($result) {
            $winner_user = get_userdata($winner->user_id);
            wp_send_json_success(array(
                'message' => __('Contest winner announced successfully.', 'pollmaster'),
                'winner' => array(
                    'user_id' => $winner->user_id,
                    'display_name' => $winner_user ? $winner_user->display_name : __('Unknown User', 'pollmaster'),
                    'total_votes' => $winning_votes
                )
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to announce winner.', 'pollmaster')
            ));
        }
    }
    
    /**
     * Handle get poll data request
     */
    public function handle_get_poll_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_pages_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'pollmaster')
            ));
        }
        
        $poll_id = (int) $_POST['poll_id'];
        
        if (!$poll_id) {
            wp_send_json_error(array(
                'message' => __('Invalid poll ID.', 'pollmaster')
            ));
        }
        
        // Get poll data
        $poll = $this->database->get_poll($poll_id);
        if (!$poll) {
            wp_send_json_error(array(
                'message' => __('Poll not found.', 'pollmaster')
            ));
        }
        
        wp_send_json_success(array(
            'id' => $poll->id,
            'question' => $poll->question,
            'option_a' => $poll->option_a,
            'option_b' => $poll->option_b,
            'image_url' => $poll->image_url,
            'is_contest' => (bool) $poll->is_contest,
            'contest_prize' => $poll->contest_prize
        ));
    }
    
    /**
     * Handle submit vote request
     */
    public function handle_submit_vote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_pages_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'pollmaster')
            ));
        }
        
        $poll_id = (int) $_POST['poll_id'];
        $option = sanitize_text_field($_POST['option']);
        
        // Validate inputs
        if (!$poll_id || !in_array($option, array('a', 'b'))) {
            wp_send_json_error(array(
                'message' => __('Invalid vote data.', 'pollmaster')
            ));
        }
        
        // Convert option to database format
        $vote_option = $option === 'a' ? 'option_a' : 'option_b';
        
        // Check if poll exists
        $poll = $this->database->get_poll($poll_id);
        if (!$poll) {
            wp_send_json_error(array(
                'message' => __('Poll not found.', 'pollmaster')
            ));
        }
        
        // For non-logged-in users, use IP-based voting
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $user_ip = $_SERVER['REMOTE_ADDR'];
        
        // Check if user/IP already voted
        if ($user_id && $this->database->has_user_voted($poll_id, $user_id)) {
            wp_send_json_error(array(
                'message' => __('You have already voted on this poll.', 'pollmaster')
            ));
        }
        
        // For non-logged-in users, check IP-based voting
        if (!$user_id && $this->database->has_ip_voted($poll_id, $user_ip)) {
            wp_send_json_error(array(
                'message' => __('You have already voted on this poll.', 'pollmaster')
            ));
        }
        
        // Cast vote
        $vote_result = $this->database->cast_vote($poll_id, $user_id, $vote_option, $user_ip);
        
        if (!$vote_result) {
            wp_send_json_error(array(
                'message' => __('Failed to cast vote. Please try again.', 'pollmaster')
            ));
        }
        
        // Get updated results
        $results = $this->database->get_poll_results($poll_id);
        
        // Prepare response data
        $response_data = array(
            'message' => __('Thank you for voting!', 'pollmaster'),
            'results' => $results,
            'poll' => array(
                'id' => $poll->id,
                'question' => $poll->question,
                'option_a' => $poll->option_a,
                'option_b' => $poll->option_b,
                'image_url' => $poll->image_url,
                'is_contest' => (bool) $poll->is_contest,
                'contest_prize' => $poll->contest_prize
            ),
            'vote_counts' => $results['vote_counts'],
            'percentages' => $results['percentages'],
            'total_votes' => $results['total_votes']
        );
        
        wp_send_json_success($response_data);
    }
}