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
        $this->frontend = new PollMaster_Frontend();
        
        // Register shortcodes
        add_shortcode('pollmaster_popup', array($this, 'popup_shortcode'));
        add_shortcode('pollmaster_poll', array($this, 'poll_shortcode'));
        add_shortcode('pollmaster_results', array($this, 'results_shortcode'));
        add_shortcode('pollmaster_latest', array($this, 'latest_poll_shortcode'));
        add_shortcode('pollmaster_contest', array($this, 'contest_shortcode'));
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
                        <?php echo $this->frontend->render_social_sharing($poll->id); ?>
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
                <?php echo $this->frontend->render_social_sharing($poll->id); ?>
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
        $show_image = !isset($atts['show_image']) || $atts['show_image'] === 'true';
        
        ob_start();
        ?>
        <div class="pollmaster-poll-voting" data-poll-id="<?php echo esc_attr($poll->id); ?>">
            
            <?php if ($poll->is_contest): ?>
            <div class="pollmaster-contest-badge">
                <span><?php esc_html_e('Contest', 'pollmaster'); ?></span>
            </div>
            <?php endif; ?>
            
            <h3 class="pollmaster-poll-question"><?php echo esc_html($poll->question); ?></h3>
            
            <?php if ($show_image && $poll->image_url): ?>
            <div class="pollmaster-poll-image">
                <img src="<?php echo esc_url($poll->image_url); ?>" alt="<?php echo esc_attr($poll->question); ?>" />
            </div>
            <?php endif; ?>
            
            <?php if (!is_user_logged_in()): ?>
            <div class="pollmaster-login-notice">
                <p><?php esc_html_e('Please log in to vote.', 'pollmaster'); ?></p>
                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pollmaster-login-btn">
                    <?php esc_html_e('Log In', 'pollmaster'); ?>
                </a>
            </div>
            <?php else: ?>
            <div class="pollmaster-vote-options">
                <button class="pollmaster-vote-btn" data-option="option_a" data-poll-id="<?php echo esc_attr($poll->id); ?>">
                    <span class="pollmaster-option-text"><?php echo esc_html($poll->option_a); ?></span>
                </button>
                
                <button class="pollmaster-vote-btn" data-option="option_b" data-poll-id="<?php echo esc_attr($poll->id); ?>">
                    <span class="pollmaster-option-text"><?php echo esc_html($poll->option_b); ?></span>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if ($poll->is_contest && $poll->contest_prize): ?>
            <div class="pollmaster-contest-info">
                <p><strong><?php esc_html_e('Prize:', 'pollmaster'); ?></strong> <?php echo esc_html($poll->contest_prize); ?></p>
                <?php if ($poll->contest_end_date): ?>
                <p><strong><?php esc_html_e('Ends:', 'pollmaster'); ?></strong> 
                   <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($poll->contest_end_date))); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
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
}