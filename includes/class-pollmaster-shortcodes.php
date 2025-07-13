<?php
/**
 * PollMaster Shortcodes Class
 * 
 * Handles all shortcodes for the PollMaster plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Shortcodes {
    
    private $database;
    private $frontend;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new PollMaster_Database();
        
        // Register shortcodes
        add_shortcode('pollmaster_popup', array($this, 'popup_shortcode'));
        add_shortcode('pollmaster_poll', array($this, 'poll_shortcode'));
        add_shortcode('pollmaster_results', array($this, 'results_shortcode'));
        add_shortcode('pollmaster_latest', array($this, 'latest_shortcode'));
        add_shortcode('pollmaster_contest', array($this, 'contest_shortcode'));
        add_shortcode('pollmaster_past_polls', array($this, 'past_polls_shortcode'));
        add_shortcode('pollmaster_create', array($this, 'create_poll_shortcode'));
        
        // Handle AJAX for user poll creation
        add_action('wp_ajax_pollmaster_create_user_poll', array($this, 'handle_user_poll_creation'));
        add_action('wp_ajax_nopriv_pollmaster_create_user_poll', array($this, 'handle_user_poll_creation'));
    }
    
    /**
     * Main popup shortcode [pollmaster_popup]
     * This is the primary shortcode for Elementor integration
     */
    public function popup_shortcode($atts) {
        $atts = shortcode_atts(array(
            'poll_id' => '',
            'type' => 'latest', // 'latest', 'weekly', 'contest', 'specific'
            'show_results' => 'false',
            'auto_show' => 'true',
            'delay' => '3000', // milliseconds
            'position' => 'center', // 'center', 'top', 'bottom'
            'width' => '500',
            'height' => 'auto',
            'background' => '',
            'text_color' => '',
            'button_color' => '',
            'show_share' => 'true',
            'show_dismiss' => 'true',
            'dismiss_text' => 'Maybe later',
            'class' => ''
        ), $atts, 'pollmaster_popup');
        
        // Get poll based on type
        $poll = $this->get_poll_by_type($atts['type'], $atts['poll_id']);
        
        if (!$poll) {
            return $this->get_no_poll_message();
        }
        
        // Check if user should see this popup
        if (!$this->should_show_popup($poll->id)) {
            return '';
        }
        
        // Generate unique popup ID
        $popup_id = 'pollmaster-popup-' . $poll->id . '-' . uniqid();
        
        // Get poll settings
        $settings = get_option('pollmaster_settings', array());
        
        // Override settings with shortcode attributes
        $popup_settings = array(
            'auto_show' => $atts['auto_show'] === 'true',
            'delay' => (int) $atts['delay'],
            'position' => $atts['position'],
            'width' => $atts['width'],
            'height' => $atts['height'],
            'background' => $atts['background'] ?: ($settings['popup_background'] ?? '#ffffff'),
            'text_color' => $atts['text_color'] ?: ($settings['popup_text_color'] ?? '#333333'),
            'button_color' => $atts['button_color'] ?: ($settings['popup_button_color'] ?? '#007cba'),
            'show_share' => $atts['show_share'] === 'true',
            'show_dismiss' => $atts['show_dismiss'] === 'true',
            'dismiss_text' => $atts['dismiss_text'],
            'class' => $atts['class']
        );
        
        // Check if user has already voted
        $user_voted = false;
        if (is_user_logged_in()) {
            $user_voted = $this->database->has_user_voted($poll->id, get_current_user_id());
        }
        
        // Generate popup HTML
        ob_start();
        ?>
        <div id="<?php echo esc_attr($popup_id); ?>" class="pollmaster-popup-container <?php echo esc_attr($popup_settings['class']); ?>" 
             data-poll-id="<?php echo esc_attr($poll->id); ?>"
             data-auto-show="<?php echo $popup_settings['auto_show'] ? 'true' : 'false'; ?>"
             data-delay="<?php echo esc_attr($popup_settings['delay']); ?>"
             data-position="<?php echo esc_attr($popup_settings['position']); ?>"
             style="display: none;">
            
            <div class="pollmaster-popup-overlay"></div>
            
            <div class="pollmaster-popup-content" 
                 style="width: <?php echo esc_attr($popup_settings['width']); ?>px; 
                        height: <?php echo $popup_settings['height'] !== 'auto' ? esc_attr($popup_settings['height']) . 'px' : 'auto'; ?>;
                        background-color: <?php echo esc_attr($popup_settings['background']); ?>;
                        color: <?php echo esc_attr($popup_settings['text_color']); ?>;">
                
                <?php if ($popup_settings['show_dismiss']): ?>
                <button class="pollmaster-popup-close" aria-label="<?php esc_attr_e('Close popup', 'pollmaster'); ?>">
                    <span>&times;</span>
                </button>
                <?php endif; ?>
                
                <div class="pollmaster-popup-inner">
                    <?php if ($atts['show_results'] === 'true' || $user_voted): ?>
                        <?php echo $this->get_poll_results_html($poll->id); ?>
                    <?php else: ?>
                        <?php echo $this->get_poll_voting_html($poll); ?>
                    <?php endif; ?>
                    
                    <?php if ($popup_settings['show_share']): ?>
                    <div class="pollmaster-share-section">
                        <p class="pollmaster-share-label"><?php esc_html_e('Share this poll:', 'pollmaster'); ?></p>
                        <?php echo $this->render_social_sharing($poll->id); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($popup_settings['show_dismiss'] && !$user_voted): ?>
                    <div class="pollmaster-dismiss-section">
                        <button class="pollmaster-dismiss-btn" data-dismiss="24h">
                            <?php echo esc_html($popup_settings['dismiss_text']); ?>
                        </button>
                        <button class="pollmaster-dismiss-btn pollmaster-dismiss-permanent" data-dismiss="permanent">
                            <?php esc_html_e('Don\'t show again', 'pollmaster'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        #<?php echo esc_attr($popup_id); ?> .pollmaster-popup-content {
            --button-color: <?php echo esc_attr($popup_settings['button_color']); ?>;
        }
        #<?php echo esc_attr($popup_id); ?> .pollmaster-vote-btn,
        #<?php echo esc_attr($popup_id); ?> .pollmaster-share-btn {
            background-color: var(--button-color);
        }
        #<?php echo esc_attr($popup_id); ?> .pollmaster-vote-btn:hover,
        #<?php echo esc_attr($popup_id); ?> .pollmaster-share-btn:hover {
            background-color: color-mix(in srgb, var(--button-color) 80%, black);
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Poll display shortcode [pollmaster_poll]
     */
    public function poll_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'show_results' => 'false',
            'show_share' => 'true',
            'show_image' => 'true',
            'style' => 'default', // 'default', 'minimal', 'card'
            'width' => '100%',
            'align' => 'left' // 'left', 'center', 'right'
        ), $atts, 'pollmaster_poll');
        
        if (empty($atts['id'])) {
            return '<p class="pollmaster-error">' . __('Poll ID is required.', 'pollmaster') . '</p>';
        }
        
        $poll = $this->database->get_poll((int) $atts['id']);
        
        if (!$poll) {
            return '<p class="pollmaster-error">' . __('Poll not found.', 'pollmaster') . '</p>';
        }
        
        // Check if user has voted
        $user_voted = false;
        if (is_user_logged_in()) {
            $user_voted = $this->database->has_user_voted($poll->id, get_current_user_id());
        }
        
        $show_results = $atts['show_results'] === 'true' || $user_voted;
        
        ob_start();
        ?>
        <div class="pollmaster-poll-embed pollmaster-style-<?php echo esc_attr($atts['style']); ?> pollmaster-align-<?php echo esc_attr($atts['align']); ?>" 
             style="width: <?php echo esc_attr($atts['width']); ?>;" 
             data-poll-id="<?php echo esc_attr($poll->id); ?>">
            
            <?php if ($show_results): ?>
                <?php echo $this->get_poll_results_html($poll->id); ?>
            <?php else: ?>
                <?php echo $this->get_poll_voting_html($poll, $atts); ?>
            <?php endif; ?>
            
            <?php if ($atts['show_share'] === 'true'): ?>
            <div class="pollmaster-share-section">
                <?php echo $this->render_social_sharing($poll->id); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Results display shortcode [pollmaster_results]
     */
    public function results_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'show_votes' => 'true',
            'show_percentages' => 'true',
            'show_chart' => 'true',
            'chart_type' => 'bar', // 'bar', 'pie', 'doughnut'
            'style' => 'default'
        ), $atts, 'pollmaster_results');
        
        if (empty($atts['id'])) {
            return '<p class="pollmaster-error">' . __('Poll ID is required.', 'pollmaster') . '</p>';
        }
        
        $poll = $this->database->get_poll((int) $atts['id']);
        
        if (!$poll) {
            return '<p class="pollmaster-error">' . __('Poll not found.', 'pollmaster') . '</p>';
        }
        
        return $this->get_poll_results_html($poll->id, $atts);
    }
    
    /**
     * Latest poll shortcode [pollmaster_latest]
     */
    public function latest_poll_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'all', // 'all', 'weekly', 'contest'
            'show_results' => 'false',
            'show_share' => 'true',
            'style' => 'default'
        ), $atts, 'pollmaster_latest');
        
        $poll = $this->get_latest_poll_by_type($atts['type']);
        
        if (!$poll) {
            return $this->get_no_poll_message();
        }
        
        // Use poll shortcode with the found poll ID
        return $this->poll_shortcode(array(
            'id' => $poll->id,
            'show_results' => $atts['show_results'],
            'show_share' => $atts['show_share'],
            'style' => $atts['style']
        ));
    }
    
    /**
     * Contest shortcode [pollmaster_contest]
     */
    public function contest_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'show_prize' => 'true',
            'show_end_date' => 'true',
            'show_winner' => 'true',
            'style' => 'contest'
        ), $atts, 'pollmaster_contest');
        
        if (empty($atts['id'])) {
            // Get latest contest
            $poll = $this->get_latest_contest();
        } else {
            $poll = $this->database->get_poll((int) $atts['id']);
        }
        
        if (!$poll || !$poll->is_contest) {
            return '<p class="pollmaster-error">' . __('Contest not found.', 'pollmaster') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="pollmaster-contest-embed" data-poll-id="<?php echo esc_attr($poll->id); ?>">
            
            <?php if ($atts['show_prize'] === 'true' && $poll->contest_prize): ?>
            <div class="pollmaster-contest-prize">
                <h4><?php esc_html_e('Prize:', 'pollmaster'); ?></h4>
                <p><?php echo esc_html($poll->contest_prize); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_end_date'] === 'true' && $poll->contest_end_date): ?>
            <div class="pollmaster-contest-end-date">
                <p><strong><?php esc_html_e('Contest ends:', 'pollmaster'); ?></strong> 
                   <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($poll->contest_end_date))); ?></p>
            </div>
            <?php endif; ?>
            
            <?php
            // Check if contest has ended and show winner
            $contest_ended = $poll->contest_end_date && strtotime($poll->contest_end_date) < time();
            if ($contest_ended && $atts['show_winner'] === 'true'):
                $winner = $this->database->get_contest_winner($poll->id);
                if ($winner):
            ?>
            <div class="pollmaster-contest-winner">
                <h4><?php esc_html_e('Contest Winner:', 'pollmaster'); ?></h4>
                <p><?php echo esc_html($winner->winner_name); ?></p>
                <p><small><?php printf(__('Won with %d votes', 'pollmaster'), $winner->total_votes); ?></small></p>
            </div>
            <?php 
                endif;
            endif;
            ?>
            
            <?php
            // Show poll content
            $user_voted = is_user_logged_in() ? $this->database->has_user_voted($poll->id, get_current_user_id()) : false;
            $show_results = $contest_ended || $user_voted;
            
            if ($show_results):
                echo $this->get_poll_results_html($poll->id);
            else:
                echo $this->get_poll_voting_html($poll);
            endif;
            ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get poll by type
     */
    private function get_poll_by_type($type, $poll_id = '') {
        switch ($type) {
            case 'specific':
                if (empty($poll_id)) {
                    return null;
                }
                return $this->database->get_poll((int) $poll_id);
                
            case 'weekly':
                return $this->database->get_weekly_poll();
                
            case 'contest':
                return $this->get_latest_contest();
                
            case 'latest':
            default:
                return $this->database->get_latest_poll();
        }
    }
    
    /**
     * Get latest poll by type
     */
    private function get_latest_poll_by_type($type) {
        global $wpdb;
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        $where_clause = "WHERE status = 'active'";
        
        switch ($type) {
            case 'weekly':
                $where_clause .= " AND is_weekly = 1";
                break;
            case 'contest':
                $where_clause .= " AND is_contest = 1";
                break;
        }
        
        $query = "SELECT * FROM $table $where_clause ORDER BY created_at DESC LIMIT 1";
        return $wpdb->get_row($query);
    }
    
    /**
     * Get latest contest
     */
    private function get_latest_contest() {
        global $wpdb;
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        return $wpdb->get_row(
            "SELECT * FROM $table 
             WHERE status = 'active' AND is_contest = 1 
             ORDER BY created_at DESC LIMIT 1"
        );
    }
    
    /**
     * Check if popup should be shown to user
     */
    private function should_show_popup($poll_id) {
        if (!is_user_logged_in()) {
            return true; // Show to non-logged-in users
        }
        
        $user_id = get_current_user_id();
        
        // Check if user dismissed this popup
        $dismissed = get_user_meta($user_id, 'pollmaster_popup_dismissed_' . $poll_id, true);
        
        if ($dismissed === 'permanent') {
            return false;
        }
        
        if (is_numeric($dismissed)) {
            // Check if 24 hours have passed
            if (time() - $dismissed < 24 * 60 * 60) {
                return false;
            }
        }
        
        // Check if user already voted
        if ($this->database->has_user_voted($poll_id, $user_id)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get poll voting HTML
     */
    private function get_poll_voting_html($poll, $atts = array()) {
        // Use the poll-display.php template for consistent rendering
        $poll_id = $poll->id;
        $show_share = !isset($atts['show_share']) || $atts['show_share'] === 'true';
        $show_results = false;
        $user_vote = null;
        $can_vote = is_user_logged_in();
        $is_ended = false;
        $is_contest = $poll->is_contest ?? false;
        
        // Get poll options and results
        $options = array($poll->option_a, $poll->option_b);
        $results = $this->database->get_poll_results($poll_id);
        $total_votes = $results['total_votes'] ?? 0;
        
        // Check if user has voted
        if ($can_vote) {
            $user_vote = $this->database->has_user_voted($poll_id, get_current_user_id()) ? 0 : null;
        }
        
        // Format data for template
        $args = array(
            'poll_id' => $poll_id,
            'poll' => array(
                'title' => $poll->question,
                'description' => '',
                'options' => json_encode($options),
                'status' => 'active',
                'is_contest' => $is_contest,
                'is_weekly' => false,
                'end_date' => null
            ),
            'show_results' => $show_results,
            'show_share' => $show_share,
            'user_vote' => $user_vote,
            'results' => array($results['vote_counts']['option_a'] ?? 0, $results['vote_counts']['option_b'] ?? 0),
            'can_vote' => $can_vote,
            'poll_image' => $poll->image_url ?? ''
        );
        
        ob_start();
        include(POLLMASTER_PLUGIN_PATH . '/templates/poll-display.php');
        return ob_get_clean();
    }
    
    /**
     * Get poll results HTML
     */
    private function get_poll_results_html($poll_id, $atts = array()) {
        $poll = $this->database->get_poll($poll_id);
        $results = $this->database->get_poll_results($poll_id);
        
        $show_votes = !isset($atts['show_votes']) || $atts['show_votes'] === 'true';
        $show_percentages = !isset($atts['show_percentages']) || $atts['show_percentages'] === 'true';
        $show_chart = !isset($atts['show_chart']) || $atts['show_chart'] === 'true';
        
        ob_start();
        ?>
        <div class="pollmaster-poll-results" data-poll-id="<?php echo esc_attr($poll_id); ?>">
            
            <?php if ($poll->is_contest): ?>
            <div class="pollmaster-contest-badge">
                <span><?php esc_html_e('Contest Results', 'pollmaster'); ?></span>
            </div>
            <?php endif; ?>
            
            <h3 class="pollmaster-poll-question"><?php echo esc_html($poll->question); ?></h3>
            
            <div class="pollmaster-results-summary">
                <p><?php printf(__('Total votes: %d', 'pollmaster'), $results['total_votes']); ?></p>
            </div>
            
            <div class="pollmaster-results-options">
                <div class="pollmaster-result-option">
                    <div class="pollmaster-option-header">
                        <span class="pollmaster-option-text"><?php echo esc_html($poll->option_a); ?></span>
                        <span class="pollmaster-option-stats">
                            <?php if ($show_votes): ?>
                            <span class="pollmaster-vote-count"><?php echo $results['vote_counts']['option_a']; ?></span>
                            <?php endif; ?>
                            <?php if ($show_percentages): ?>
                            <span class="pollmaster-percentage">(<?php echo $results['percentages']['option_a']; ?>%)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="pollmaster-progress-bar">
                        <div class="pollmaster-progress-fill" style="width: <?php echo $results['percentages']['option_a']; ?>%;"></div>
                    </div>
                </div>
                
                <div class="pollmaster-result-option">
                    <div class="pollmaster-option-header">
                        <span class="pollmaster-option-text"><?php echo esc_html($poll->option_b); ?></span>
                        <span class="pollmaster-option-stats">
                            <?php if ($show_votes): ?>
                            <span class="pollmaster-vote-count"><?php echo $results['vote_counts']['option_b']; ?></span>
                            <?php endif; ?>
                            <?php if ($show_percentages): ?>
                            <span class="pollmaster-percentage">(<?php echo $results['percentages']['option_b']; ?>%)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="pollmaster-progress-bar">
                        <div class="pollmaster-progress-fill" style="width: <?php echo $results['percentages']['option_b']; ?>%;"></div>
                    </div>
                </div>
            </div>
            
            <?php if ($show_chart && isset($atts['chart_type'])): ?>
            <div class="pollmaster-chart-container">
                <canvas id="pollmaster-chart-<?php echo esc_attr($poll_id); ?>" 
                        data-chart-type="<?php echo esc_attr($atts['chart_type']); ?>"
                        data-option-a="<?php echo esc_attr($poll->option_a); ?>"
                        data-option-b="<?php echo esc_attr($poll->option_b); ?>"
                        data-votes-a="<?php echo $results['vote_counts']['option_a']; ?>"
                        data-votes-b="<?php echo $results['vote_counts']['option_b']; ?>"></canvas>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get no poll message
     */
    private function get_no_poll_message() {
        return '<div class="pollmaster-no-poll"><p>' . __('No polls available at the moment.', 'pollmaster') . '</p></div>';
    }
    
    /**
     * Past polls shortcode
     */
    public function past_polls_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'type' => 'all', // all, weekly, contest
            'show_results' => 'true'
        ), $atts);
        
        $polls = $this->database->get_past_polls($atts['limit'], $atts['type']);
        
        if (empty($polls)) {
            return '<div class="pollmaster-no-polls">' . __('No past polls found.', 'pollmaster') . '</div>';
        }
        
        $output = '<div class="pollmaster-past-polls">';
        
        foreach ($polls as $poll) {
            $results = $this->database->get_poll_results($poll->id);
            
            $output .= '<div class="pollmaster-past-poll">';
            $output .= '<h3 class="poll-question">' . esc_html($poll->question) . '</h3>';
            
            if ($poll->image_url) {
                $output .= '<div class="poll-image">';
                $output .= '<img src="' . esc_url($poll->image_url) . '" alt="' . esc_attr($poll->question) . '">';
                $output .= '</div>';
            }
            
            if ($poll->is_contest) {
                $output .= '<div class="contest-info">';
                $output .= '<span class="contest-badge">' . __('Contest', 'pollmaster') . '</span>';
                if ($poll->prize) {
                    $output .= '<span class="prize">' . __('Prize:', 'pollmaster') . ' ' . esc_html($poll->prize) . '</span>';
                }
                $output .= '</div>';
            }
            
            if ($atts['show_results'] === 'true') {
                $output .= $this->get_poll_results_html($poll->id, $atts);
            }
            
            $output .= '<div class="poll-meta">';
            $output .= '<span class="poll-date">' . __('Created:', 'pollmaster') . ' ' . date_i18n(get_option('date_format'), strtotime($poll->created_at)) . '</span>';
            $output .= '<span class="total-votes">' . sprintf(__('Total votes: %d', 'pollmaster'), $results['total_votes']) . '</span>';
            $output .= '</div>';
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render social sharing buttons
     */
    private function render_social_sharing($poll_id) {
        $settings = get_option('pollmaster_settings', array());
        $poll_url = get_permalink() . '?poll=' . $poll_id;
        $poll = $this->database->get_poll($poll_id);
        $share_text = $poll ? urlencode($poll->question) : urlencode(__('Check out this poll!', 'pollmaster'));
        
        ob_start();
        ?>
        <div class="pollmaster-social-share">
            <?php if (isset($settings['enable_facebook']) && $settings['enable_facebook']): ?>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($poll_url); ?>" 
               target="_blank" 
               class="pollmaster-share-btn pollmaster-facebook"
               data-platform="facebook"
               data-poll-id="<?php echo esc_attr($poll_id); ?>">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <?php esc_html_e('Facebook', 'pollmaster'); ?>
            </a>
            <?php endif; ?>
            
            <?php if (isset($settings['enable_twitter']) && $settings['enable_twitter']): ?>
            <a href="https://twitter.com/intent/tweet?text=<?php echo $share_text; ?>&url=<?php echo urlencode($poll_url); ?>" 
               target="_blank" 
               class="pollmaster-share-btn pollmaster-twitter"
               data-platform="twitter"
               data-poll-id="<?php echo esc_attr($poll_id); ?>">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                </svg>
                <?php esc_html_e('Twitter', 'pollmaster'); ?>
            </a>
            <?php endif; ?>
            
            <?php if (isset($settings['enable_whatsapp']) && $settings['enable_whatsapp']): ?>
            <a href="https://wa.me/?text=<?php echo $share_text; ?>%20<?php echo urlencode($poll_url); ?>" 
               target="_blank" 
               class="pollmaster-share-btn pollmaster-whatsapp"
               data-platform="whatsapp"
               data-poll-id="<?php echo esc_attr($poll_id); ?>">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                </svg>
                <?php esc_html_e('WhatsApp', 'pollmaster'); ?>
            </a>
            <?php endif; ?>
            
            <?php if (isset($settings['enable_linkedin']) && $settings['enable_linkedin']): ?>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($poll_url); ?>" 
               target="_blank" 
               class="pollmaster-share-btn pollmaster-linkedin"
               data-platform="linkedin"
               data-poll-id="<?php echo esc_attr($poll_id); ?>">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
                <?php esc_html_e('LinkedIn', 'pollmaster'); ?>
            </a>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Register shortcode UI for page builders
     */
    public function register_shortcode_ui() {
        // Register with popular page builders
        if (function_exists('shortcode_ui_register_for_shortcode')) {
            // Shortcode UI plugin support
            shortcode_ui_register_for_shortcode('pollmaster_popup', array(
                'label' => __('PollMaster Popup', 'pollmaster'),
                'listItemImage' => 'dashicons-chart-bar',
                'attrs' => array(
                    array(
                        'label' => __('Poll Type', 'pollmaster'),
                        'attr' => 'type',
                        'type' => 'select',
                        'options' => array(
                            'latest' => __('Latest Poll', 'pollmaster'),
                            'weekly' => __('Weekly Poll', 'pollmaster'),
                            'contest' => __('Latest Contest', 'pollmaster'),
                            'specific' => __('Specific Poll', 'pollmaster')
                        )
                    ),
                    array(
                        'label' => __('Poll ID (for specific poll)', 'pollmaster'),
                        'attr' => 'poll_id',
                        'type' => 'number'
                    ),
                    array(
                        'label' => __('Auto Show', 'pollmaster'),
                        'attr' => 'auto_show',
                        'type' => 'checkbox'
                    ),
                    array(
                        'label' => __('Show Share Buttons', 'pollmaster'),
                        'attr' => 'show_share',
                        'type' => 'checkbox'
                    )
                )
            ));
        }
    }
    
    /**
     * Create poll shortcode [pollmaster_create]
     * Allows users to create polls from the frontend
     */
    public function create_poll_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_role' => 'subscriber', // minimum user role required
            'redirect_after' => '', // URL to redirect after creation
            'show_image_upload' => 'true',
            'show_contest_option' => 'false',
            'max_polls_per_user' => '5', // 0 for unlimited
            'style' => 'default', // 'default', 'minimal', 'card'
            'title' => __('Create Your Poll', 'pollmaster'),
            'submit_text' => __('Create Poll', 'pollmaster')
        ), $atts, 'pollmaster_create');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="pollmaster-login-required">' . 
                   '<p>' . __('Please log in to create a poll.', 'pollmaster') . '</p>' .
                   '<a href="' . wp_login_url(get_permalink()) . '" class="pollmaster-login-btn">' . __('Login', 'pollmaster') . '</a>' .
                   '</div>';
        }
        
        $current_user = wp_get_current_user();
        
        // Check user role
        if (!current_user_can($atts['user_role'])) {
            return '<div class="pollmaster-permission-denied">' . 
                   '<p>' . __('You do not have permission to create polls.', 'pollmaster') . '</p>' .
                   '</div>';
        }
        
        // Check poll limit
        if ($atts['max_polls_per_user'] > 0) {
            $user_polls = $this->database->get_polls(1, (int)$atts['max_polls_per_user'] + 1, $current_user->ID);
            if (count($user_polls) >= (int)$atts['max_polls_per_user']) {
                return '<div class="pollmaster-limit-reached">' . 
                       '<p>' . sprintf(__('You have reached the maximum limit of %d polls.', 'pollmaster'), (int)$atts['max_polls_per_user']) . '</p>' .
                       '</div>';
            }
        }
        
        // Generate unique form ID
        $form_id = 'pollmaster-create-form-' . uniqid();
        
        ob_start();
        ?>
        <div class="pollmaster-create-poll-container pollmaster-style-<?php echo esc_attr($atts['style']); ?>">
            <div class="pollmaster-create-poll-form">
                <h3 class="pollmaster-form-title"><?php echo esc_html($atts['title']); ?></h3>
                
                <div id="pollmaster-messages-<?php echo esc_attr($form_id); ?>" class="pollmaster-messages" style="display: none;"></div>
                
                <form id="<?php echo esc_attr($form_id); ?>" class="pollmaster-user-poll-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('pollmaster_create_user_poll', 'pollmaster_create_nonce'); ?>
                    
                    <div class="pollmaster-form-group">
                        <label for="poll_question_<?php echo esc_attr($form_id); ?>" class="pollmaster-label">
                            <?php esc_html_e('Poll Question', 'pollmaster'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="poll_question_<?php echo esc_attr($form_id); ?>" 
                               name="poll_question" 
                               class="pollmaster-input" 
                               placeholder="<?php esc_attr_e('Enter your poll question...', 'pollmaster'); ?>" 
                               required 
                               maxlength="200">
                        <small class="pollmaster-help-text"><?php esc_html_e('Maximum 200 characters', 'pollmaster'); ?></small>
                    </div>
                    
                    <div class="pollmaster-form-group">
                        <label for="poll_option_a_<?php echo esc_attr($form_id); ?>" class="pollmaster-label">
                            <?php esc_html_e('Option A', 'pollmaster'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="poll_option_a_<?php echo esc_attr($form_id); ?>" 
                               name="poll_option_a" 
                               class="pollmaster-input" 
                               placeholder="<?php esc_attr_e('First option...', 'pollmaster'); ?>" 
                               required 
                               maxlength="100">
                    </div>
                    
                    <div class="pollmaster-form-group">
                        <label for="poll_option_b_<?php echo esc_attr($form_id); ?>" class="pollmaster-label">
                            <?php esc_html_e('Option B', 'pollmaster'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="poll_option_b_<?php echo esc_attr($form_id); ?>" 
                               name="poll_option_b" 
                               class="pollmaster-input" 
                               placeholder="<?php esc_attr_e('Second option...', 'pollmaster'); ?>" 
                               required 
                               maxlength="100">
                    </div>
                    
                    <?php if ($atts['show_image_upload'] === 'true'): ?>
                    <div class="pollmaster-form-group">
                        <label for="poll_image_<?php echo esc_attr($form_id); ?>" class="pollmaster-label">
                            <?php esc_html_e('Poll Image (Optional)', 'pollmaster'); ?>
                        </label>
                        <input type="file" 
                               id="poll_image_<?php echo esc_attr($form_id); ?>" 
                               name="poll_image" 
                               class="pollmaster-file-input" 
                               accept="image/jpeg,image/png,image/gif" 
                               data-max-size="5242880">
                        <small class="pollmaster-help-text"><?php esc_html_e('Maximum file size: 5MB. Supported formats: JPG, PNG, GIF', 'pollmaster'); ?></small>
                        <div class="pollmaster-image-preview" style="display: none;">
                            <img src="" alt="Preview" class="pollmaster-preview-img">
                            <button type="button" class="pollmaster-remove-image"><?php esc_html_e('Remove', 'pollmaster'); ?></button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($atts['show_contest_option'] === 'true' && current_user_can('edit_posts')): ?>
                    <div class="pollmaster-form-group">
                        <label class="pollmaster-checkbox-label">
                            <input type="checkbox" 
                                   id="poll_is_contest_<?php echo esc_attr($form_id); ?>" 
                                   name="poll_is_contest" 
                                   value="1" 
                                   class="pollmaster-checkbox">
                            <span class="pollmaster-checkbox-text"><?php esc_html_e('This is a contest poll', 'pollmaster'); ?></span>
                        </label>
                    </div>
                    
                    <div class="pollmaster-form-group pollmaster-contest-fields" style="display: none;">
                        <label for="poll_prize_<?php echo esc_attr($form_id); ?>" class="pollmaster-label">
                            <?php esc_html_e('Contest Prize', 'pollmaster'); ?>
                        </label>
                        <input type="text" 
                               id="poll_prize_<?php echo esc_attr($form_id); ?>" 
                               name="poll_prize" 
                               class="pollmaster-input" 
                               placeholder="<?php esc_attr_e('Describe the prize...', 'pollmaster'); ?>" 
                               maxlength="200">
                    </div>
                    <?php endif; ?>
                    
                    <div class="pollmaster-form-group">
                        <label for="poll_description_<?php echo esc_attr($form_id); ?>" class="pollmaster-label">
                            <?php esc_html_e('Description (Optional)', 'pollmaster'); ?>
                        </label>
                        <textarea id="poll_description_<?php echo esc_attr($form_id); ?>" 
                                  name="poll_description" 
                                  class="pollmaster-textarea" 
                                  placeholder="<?php esc_attr_e('Add more details about your poll...', 'pollmaster'); ?>" 
                                  rows="3" 
                                  maxlength="500"></textarea>
                        <small class="pollmaster-help-text"><?php esc_html_e('Maximum 500 characters', 'pollmaster'); ?></small>
                    </div>
                    
                    <div class="pollmaster-form-actions">
                        <button type="submit" class="pollmaster-submit-btn pollmaster-btn-primary">
                            <span class="pollmaster-btn-text"><?php echo esc_html($atts['submit_text']); ?></span>
                            <span class="pollmaster-btn-loading" style="display: none;">
                                <svg class="pollmaster-spinner" width="20" height="20" viewBox="0 0 50 50">
                                    <circle class="path" cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                                        <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                                        <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                                    </circle>
                                </svg>
                                <?php esc_html_e('Creating...', 'pollmaster'); ?>
                            </span>
                        </button>
                        
                        <button type="button" class="pollmaster-reset-btn pollmaster-btn-secondary">
                            <?php esc_html_e('Reset Form', 'pollmaster'); ?>
                        </button>
                    </div>
                    
                    <input type="hidden" name="action" value="pollmaster_create_user_poll">
                    <input type="hidden" name="redirect_after" value="<?php echo esc_attr($atts['redirect_after']); ?>">
                </form>
            </div>
        </div>
        
        <style>
        .pollmaster-create-poll-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .pollmaster-style-card .pollmaster-create-poll-form {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .pollmaster-style-minimal .pollmaster-create-poll-form {
            background: transparent;
            padding: 0;
        }
        .pollmaster-form-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        .pollmaster-form-group {
            margin-bottom: 20px;
        }
        .pollmaster-label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #555;
        }
        .pollmaster-input, .pollmaster-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .pollmaster-input:focus, .pollmaster-textarea:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
        }
        .pollmaster-file-input {
            width: 100%;
            padding: 8px;
            border: 2px dashed #e1e5e9;
            border-radius: 6px;
            background: #f9f9f9;
        }
        .pollmaster-help-text {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 14px;
        }
        .pollmaster-checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .pollmaster-checkbox {
            margin-right: 8px;
        }
        .pollmaster-image-preview {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            background: #f9f9f9;
        }
        .pollmaster-preview-img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 4px;
        }
        .pollmaster-remove-image {
            margin-left: 10px;
            padding: 5px 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .pollmaster-form-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        .pollmaster-submit-btn, .pollmaster-reset-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pollmaster-btn-primary {
            background: #007cba;
            color: white;
        }
        .pollmaster-btn-primary:hover {
            background: #005a87;
        }
        .pollmaster-btn-secondary {
            background: #6c757d;
            color: white;
        }
        .pollmaster-btn-secondary:hover {
            background: #545b62;
        }
        .pollmaster-messages {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 6px;
        }
        .pollmaster-messages.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .pollmaster-messages.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .pollmaster-spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .required {
            color: #dc3545;
        }
        .pollmaster-login-required, .pollmaster-permission-denied, .pollmaster-limit-reached {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
        }
        .pollmaster-login-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s ease;
        }
        .pollmaster-login-btn:hover {
            background: #005a87;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            const formId = '<?php echo esc_js($form_id); ?>';
            const form = $('#' + formId);
            const messagesDiv = $('#pollmaster-messages-' + formId);
            
            // Handle contest checkbox
            $('#poll_is_contest_' + formId).change(function() {
                if ($(this).is(':checked')) {
                    $('.pollmaster-contest-fields').slideDown();
                } else {
                    $('.pollmaster-contest-fields').slideUp();
                    $('#poll_prize_' + formId).val('');
                }
            });
            
            // Handle image upload preview
            $('#poll_image_' + formId).change(function() {
                const file = this.files[0];
                if (file) {
                    // Check file size
                    const maxSize = $(this).data('max-size');
                    if (file.size > maxSize) {
                        showMessage('File size too large. Maximum allowed size is 5MB.', 'error');
                        $(this).val('');
                        return;
                    }
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = $('.pollmaster-image-preview');
                        preview.find('.pollmaster-preview-img').attr('src', e.target.result);
                        preview.show();
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Handle remove image
            $('.pollmaster-remove-image').click(function() {
                $('#poll_image_' + formId).val('');
                $('.pollmaster-image-preview').hide();
            });
            
            // Handle reset button
            $('.pollmaster-reset-btn').click(function() {
                form[0].reset();
                $('.pollmaster-image-preview').hide();
                $('.pollmaster-contest-fields').hide();
                messagesDiv.hide();
            });
            
            // Handle form submission
            form.submit(function(e) {
                e.preventDefault();
                
                const submitBtn = $('.pollmaster-submit-btn');
                const btnText = submitBtn.find('.pollmaster-btn-text');
                const btnLoading = submitBtn.find('.pollmaster-btn-loading');
                
                // Show loading state
                submitBtn.prop('disabled', true);
                btnText.hide();
                btnLoading.show();
                
                // Prepare form data
                const formData = new FormData(this);
                
                // Submit via AJAX
                $.ajax({
                    url: pollmaster.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, 'success');
                            form[0].reset();
                            $('.pollmaster-image-preview').hide();
                            $('.pollmaster-contest-fields').hide();
                            
                            // Redirect if specified
                            const redirectUrl = formData.get('redirect_after');
                            if (redirectUrl) {
                                setTimeout(function() {
                                    window.location.href = redirectUrl;
                                }, 2000);
                            }
                        } else {
                            showMessage(response.data.message || 'An error occurred. Please try again.', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Network error. Please check your connection and try again.', 'error');
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.prop('disabled', false);
                        btnText.show();
                        btnLoading.hide();
                    }
                });
            });
            
            function showMessage(message, type) {
                messagesDiv.removeClass('success error').addClass(type);
                messagesDiv.html('<p>' + message + '</p>').show();
                
                // Auto-hide success messages
                if (type === 'success') {
                    setTimeout(function() {
                        messagesDiv.fadeOut();
                    }, 5000);
                }
                
                // Scroll to message
                $('html, body').animate({
                    scrollTop: messagesDiv.offset().top - 100
                }, 500);
            }
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Handle user poll creation via AJAX
     */
    public function handle_user_poll_creation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['pollmaster_create_nonce'], 'pollmaster_create_user_poll')) {
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'pollmaster')));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to create a poll.', 'pollmaster')));
        }
        
        $current_user = wp_get_current_user();
        
        // Validate required fields
        $question = sanitize_text_field($_POST['poll_question'] ?? '');
        $option_a = sanitize_text_field($_POST['poll_option_a'] ?? '');
        $option_b = sanitize_text_field($_POST['poll_option_b'] ?? '');
        
        if (empty($question) || empty($option_a) || empty($option_b)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'pollmaster')));
        }
        
        // Validate field lengths
        if (strlen($question) > 200) {
            wp_send_json_error(array('message' => __('Poll question is too long. Maximum 200 characters allowed.', 'pollmaster')));
        }
        
        if (strlen($option_a) > 100 || strlen($option_b) > 100) {
            wp_send_json_error(array('message' => __('Poll options are too long. Maximum 100 characters allowed per option.', 'pollmaster')));
        }
        
        // Prepare poll data
        $poll_data = array(
            'user_id' => $current_user->ID,
            'question' => $question,
            'option_a' => $option_a,
            'option_b' => $option_b,
            'description' => sanitize_textarea_field($_POST['poll_description'] ?? ''),
            'is_contest' => isset($_POST['poll_is_contest']) && current_user_can('edit_posts') ? 1 : 0,
            'contest_prize' => isset($_POST['poll_prize']) && current_user_can('edit_posts') ? sanitize_text_field($_POST['poll_prize']) : '',
            'status' => 'pending' // User-created polls need approval by default
        );
        
        // Handle image upload
        if (isset($_FILES['poll_image']) && $_FILES['poll_image']['error'] === UPLOAD_ERR_OK) {
            $image_url = $this->handle_user_image_upload($_FILES['poll_image']);
            if ($image_url) {
                $poll_data['image_url'] = $image_url;
            } else {
                wp_send_json_error(array('message' => __('Failed to upload image. Please try again with a different image.', 'pollmaster')));
            }
        }
        
        // Create the poll
        $poll_id = $this->database->create_poll($poll_data);
        
        if ($poll_id) {
            // Send notification to admin
            $this->send_poll_creation_notification($poll_id, $current_user);
            
            wp_send_json_success(array(
                'message' => __('Your poll has been created successfully! It will be reviewed by an administrator before being published.', 'pollmaster'),
                'poll_id' => $poll_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to create poll. Please try again.', 'pollmaster')));
        }
    }
    
    /**
     * Handle user image upload
     */
    private function handle_user_image_upload($file) {
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            return false;
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $pollmaster_dir = $upload_dir['basedir'] . '/pollmaster';
        
        if (!file_exists($pollmaster_dir)) {
            wp_mkdir_p($pollmaster_dir);
        }
        
        // Generate unique filename
        $filename = uniqid('user_poll_') . '_' . sanitize_file_name($file['name']);
        $filepath = $pollmaster_dir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $upload_dir['baseurl'] . '/pollmaster/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Send poll creation notification to admin
     */
    private function send_poll_creation_notification($poll_id, $user) {
        $poll = $this->database->get_poll($poll_id);
        if (!$poll) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=pollmaster-polls');
        
        $subject = sprintf(__('[%s] New Poll Submitted for Review', 'pollmaster'), $site_name);
        
        $message = sprintf(
            __('A new poll has been submitted by %s and is awaiting your review.\n\n', 'pollmaster'),
            $user->display_name
        );
        
        $message .= sprintf(__('Poll Question: %s\n', 'pollmaster'), $poll->question);
        $message .= sprintf(__('Option A: %s\n', 'pollmaster'), $poll->option_a);
        $message .= sprintf(__('Option B: %s\n', 'pollmaster'), $poll->option_b);
        
        if ($poll->description) {
            $message .= sprintf(__('Description: %s\n', 'pollmaster'), $poll->description);
        }
        
        $message .= sprintf(__('\nReview and manage polls: %s', 'pollmaster'), $admin_url);
        
        wp_mail($admin_email, $subject, $message);
    }
}