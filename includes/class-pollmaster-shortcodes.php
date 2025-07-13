<?php
/**
 * PollMaster Shortcodes Class
 * 
 * Handles all shortcode functionality for the PollMaster plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Shortcodes {
    
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new PollMaster_Database();
        $this->init_shortcodes();
    }
    
    /**
     * Initialize shortcodes
     */
    private function init_shortcodes() {
        add_shortcode('pollmaster_poll', array($this, 'poll_shortcode'));
        add_shortcode('pollmaster_popup', array($this, 'popup_shortcode'));
        add_shortcode('pollmaster_results', array($this, 'results_shortcode'));
        add_shortcode('pollmaster_latest', array($this, 'latest_shortcode'));
        add_shortcode('pollmaster_contest', array($this, 'contest_shortcode'));
        add_shortcode('pollmaster_weekly', array($this, 'weekly_shortcode'));
        add_shortcode('pollmaster_past_polls', array($this, 'past_polls_shortcode'));
        add_shortcode('pollmaster_create_poll', array($this, 'create_poll_shortcode'));
        add_shortcode('pollmaster_user_dashboard', array($this, 'user_dashboard_shortcode'));
        add_shortcode('pollmaster_poll_list', array($this, 'poll_list_shortcode'));
    }
    
    /**
     * Poll display shortcode
     */
    public function poll_shortcode($atts) {
        $atts = shortcode_atts(array(
            'poll_id' => '',
            'type' => 'latest',
            'show_results' => 'false',
            'show_share' => 'true',
            'style' => 'default',
            'width' => '100%',
            'align' => 'none'
        ), $atts, 'pollmaster_poll');
        
        // Get poll based on type or ID
        if (!empty($atts['poll_id'])) {
            $poll = $this->database->get_poll($atts['poll_id']);
        } else {
            switch ($atts['type']) {
                case 'latest':
                    $poll = $this->database->get_latest_poll();
                    break;
                case 'weekly':
                    $poll = $this->database->get_weekly_poll();
                    break;
                case 'random':
                    $poll = $this->database->get_random_poll();
                    break;
                default:
                    $poll = $this->database->get_latest_poll();
                    break;
            }
        }
        
        if (!$poll) {
            return '<div class="pollmaster-error">No poll found.</div>';
        }
        
        // Get poll results
        $results = $this->database->get_poll_results($poll->id);
        
        // Check if user voted
        $user_vote = null;
        $can_vote = true;
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            if ($this->database->has_user_voted($poll->id, $user_id)) {
                $user_vote = $this->get_user_vote($poll->id, $user_id);
            }
        } else {
            $can_vote = false;
        }
        
        // Prepare template args
        $args = array(
            'poll_id' => $poll->id,
            'poll' => (array) $poll,
            'show_results' => $atts['show_results'] === 'true',
            'show_share' => $atts['show_share'] === 'true',
            'user_vote' => $user_vote,
            'results' => $results['vote_counts'],
            'can_vote' => $can_vote,
            'poll_image' => $poll->image_url
        );
        
        return $this->render_template('poll-display', $args);
    }
    
    /**
     * Popup shortcode
     */
    public function popup_shortcode($atts) {
        $atts = shortcode_atts(array(
            'poll_id' => '',
            'type' => 'latest',
            'auto_show' => 'false',
            'show_delay' => '3',
            'trigger_text' => 'Take Poll',
            'show_once' => 'true',
            'close_on_vote' => 'true',
            'dismissible' => 'true',
            'show_share' => 'true'
        ), $atts, 'pollmaster_popup');
        
        // Get poll
        if (!empty($atts['poll_id'])) {
            $poll = $this->database->get_poll($atts['poll_id']);
        } else {
            switch ($atts['type']) {
                case 'latest':
                    $poll = $this->database->get_latest_poll();
                    break;
                case 'weekly':
                    $poll = $this->database->get_weekly_poll();
                    break;
                default:
                    $poll = $this->database->get_latest_poll();
                    break;
            }
        }
        
        if (!$poll) {
            return '';
        }
        
        // Check if user already voted
        $user_vote = null;
        $can_vote = true;
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            if ($this->database->has_user_voted($poll->id, $user_id)) {
                $user_vote = $this->get_user_vote($poll->id, $user_id);
            }
        } else {
            $can_vote = false;
        }
        
        // Get results
        $results = $this->database->get_poll_results($poll->id);
        
        // Prepare template args
        $args = array(
            'poll_id' => $poll->id,
            'poll' => (array) $poll,
            'auto_show' => $atts['auto_show'] === 'true',
            'dismissible' => $atts['dismissible'] === 'true',
            'show_share' => $atts['show_share'] === 'true',
            'user_vote' => $user_vote,
            'results' => $results['vote_counts'],
            'can_vote' => $can_vote,
            'poll_image' => $poll->image_url
        );
        
        return $this->render_template('poll-popup', $args);
    }
    
    /**
     * Results shortcode
     */
    public function results_shortcode($atts) {
        $atts = shortcode_atts(array(
            'poll_id' => '',
            'type' => 'latest',
            'show_votes' => 'true',
            'show_percentages' => 'true',
            'show_chart' => 'false',
            'chart_type' => 'bar'
        ), $atts, 'pollmaster_results');
        
        // Get poll
        if (!empty($atts['poll_id'])) {
            $poll = $this->database->get_poll($atts['poll_id']);
        } else {
            switch ($atts['type']) {
                case 'latest':
                    $poll = $this->database->get_latest_poll();
                    break;
                case 'weekly':
                    $poll = $this->database->get_weekly_poll();
                    break;
                default:
                    $poll = $this->database->get_latest_poll();
                    break;
            }
        }
        
        if (!$poll) {
            return '<div class="pollmaster-error">No poll found.</div>';
        }
        
        $results = $this->database->get_poll_results($poll->id);
        
        ob_start();
        ?>
        <div class="pollmaster-results-widget" data-poll-id="<?php echo esc_attr($poll->id); ?>">
            <h3 class="pollmaster-results-title"><?php echo esc_html($poll->question); ?></h3>
            
            <div class="pollmaster-results-content">
                <?php if ($results['total_votes'] > 0): ?>
                    <div class="pollmaster-options-results">
                        <div class="pollmaster-result-item">
                            <div class="result-header">
                                <span class="option-text"><?php echo esc_html($poll->option_a); ?></span>
                                <span class="result-stats">
                                    <?php if ($atts['show_votes'] === 'true'): ?>
                                        <span class="vote-count"><?php echo esc_html($results['vote_counts']['option_a']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($atts['show_percentages'] === 'true'): ?>
                                        <span class="percentage">(<?php echo esc_html($results['percentages']['option_a']); ?>%)</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="result-bar-container">
                                <div class="result-bar" style="width: <?php echo esc_attr($results['percentages']['option_a']); ?>%;"></div>
                            </div>
                        </div>
                        
                        <div class="pollmaster-result-item">
                            <div class="result-header">
                                <span class="option-text"><?php echo esc_html($poll->option_b); ?></span>
                                <span class="result-stats">
                                    <?php if ($atts['show_votes'] === 'true'): ?>
                                        <span class="vote-count"><?php echo esc_html($results['vote_counts']['option_b']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($atts['show_percentages'] === 'true'): ?>
                                        <span class="percentage">(<?php echo esc_html($results['percentages']['option_b']); ?>%)</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="result-bar-container">
                                <div class="result-bar" style="width: <?php echo esc_attr($results['percentages']['option_b']); %>%;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pollmaster-total-votes">
                        Total votes: <strong><?php echo esc_html($results['total_votes']); ?></strong>
                    </div>
                <?php else: ?>
                    <p class="pollmaster-no-votes">No votes yet. Be the first to vote!</p>
                <?php endif; ?>
            </div>
            
            <?php if ($atts['show_chart'] === 'true' && $results['total_votes'] > 0): ?>
                <div class="pollmaster-chart-container">
                    <canvas id="pollmaster-chart-<?php echo esc_attr($poll->id); ?>" 
                            data-chart-type="<?php echo esc_attr($atts['chart_type']); ?>"
                            data-option-a="<?php echo esc_attr($poll->option_a); ?>"
                            data-option-b="<?php echo esc_attr($poll->option_b); ?>"
                            data-votes-a="<?php echo esc_attr($results['vote_counts']['option_a']); ?>"
                            data-votes-b="<?php echo esc_attr($results['vote_counts']['option_b']); ?>">
                    </canvas>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Latest poll shortcode
     */
    public function latest_shortcode($atts) {
        $atts['type'] = 'latest';
        return $this->poll_shortcode($atts);
    }
    
    /**
     * Contest shortcode
     */
    public function contest_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_prize' => 'true',
            'show_end_date' => 'true',
            'show_winner' => 'true'
        ), $atts, 'pollmaster_contest');
        
        // Get latest contest
        $contests = $this->database->get_polls(array('type' => 'contest', 'limit' => 1));
        
        if (empty($contests)) {
            return '<div class="pollmaster-error">No contests found.</div>';
        }
        
        $contest = $contests[0];
        $results = $this->database->get_poll_results($contest->id);
        $winner = $this->database->get_contest_winner($contest->id);
        
        ob_start();
        ?>
        <div class="pollmaster-contest-widget" data-poll-id="<?php echo esc_attr($contest->id); ?>">
            <div class="contest-header">
                <div class="contest-badge">
                    <span class="contest-icon">🏆</span>
                    <span class="contest-text">Contest</span>
                </div>
                <h3 class="contest-title"><?php echo esc_html($contest->question); ?></h3>
            </div>
            
            <?php if ($contest->image_url): ?>
                <div class="contest-image">
                    <img src="<?php echo esc_url($contest->image_url); ?>" alt="Contest Image" />
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_prize'] === 'true' && $contest->contest_prize): ?>
                <div class="contest-prize">
                    <h4>Prize</h4>
                    <p><?php echo esc_html($contest->contest_prize); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_end_date'] === 'true' && $contest->contest_end_date): ?>
                <div class="contest-end-date">
                    <strong>Ends:</strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($contest->contest_end_date))); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_winner'] === 'true' && $winner): ?>
                <div class="contest-winner">
                    <h4>Winner</h4>
                    <p><?php echo esc_html($winner->winner_name ?: 'Anonymous'); ?></p>
                    <p class="winner-date">Announced: <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($winner->announced_at))); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="contest-results">
                <?php echo $this->results_shortcode(array('poll_id' => $contest->id)); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Weekly poll shortcode
     */
    public function weekly_shortcode($atts) {
        $atts['type'] = 'weekly';
        return $this->poll_shortcode($atts);
    }
    
    /**
     * Past polls shortcode
     */
    public function past_polls_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
            'type' => 'all',
            'show_results' => 'true',
            'show_images' => 'true',
            'columns' => '3'
        ), $atts, 'pollmaster_past_polls');
        
        $polls = $this->database->get_past_polls(intval($atts['limit']), $atts['type']);
        
        if (empty($polls)) {
            return '<div class="pollmaster-no-polls">No past polls found.</div>';
        }
        
        ob_start();
        ?>
        <div class="pollmaster-past-polls-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <?php foreach ($polls as $poll): ?>
                <div class="pollmaster-past-poll-item">
                    <?php if ($atts['show_images'] === 'true' && $poll->image_url): ?>
                        <div class="past-poll-image">
                            <img src="<?php echo esc_url($poll->image_url); ?>" alt="<?php echo esc_attr($poll->question); ?>" />
                        </div>
                    <?php endif; ?>
                    
                    <div class="past-poll-content">
                        <h4 class="past-poll-title"><?php echo esc_html($poll->question); ?></h4>
                        
                        <?php if ($poll->is_contest): ?>
                            <div class="past-poll-badge contest">Contest</div>
                        <?php endif; ?>
                        
                        <?php if ($poll->is_weekly): ?>
                            <div class="past-poll-badge weekly">Weekly</div>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_results'] === 'true'): ?>
                            <div class="past-poll-results">
                                <?php echo $this->results_shortcode(array('poll_id' => $poll->id, 'show_chart' => 'false')); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="past-poll-date">
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($poll->created_at))); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Create poll shortcode
     */
    public function create_poll_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="pollmaster-login-required">Please log in to create polls.</div>';
        }
        
        ob_start();
        ?>
        <div class="pollmaster-create-poll-form">
            <form id="pollmaster-user-poll-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('pollmaster_create_poll', 'pollmaster_create_poll_nonce'); ?>
                
                <div class="form-group">
                    <label for="poll_question">Poll Question *</label>
                    <input type="text" id="poll_question" name="poll_question" required maxlength="255" />
                </div>
                
                <div class="form-group">
                    <label for="poll_option_a">Option A *</label>
                    <input type="text" id="poll_option_a" name="poll_option_a" required maxlength="100" />
                </div>
                
                <div class="form-group">
                    <label for="poll_option_b">Option B *</label>
                    <input type="text" id="poll_option_b" name="poll_option_b" required maxlength="100" />
                </div>
                
                <div class="form-group">
                    <label for="poll_description">Description (Optional)</label>
                    <textarea id="poll_description" name="poll_description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="poll_image">Image (Optional)</label>
                    <input type="file" id="poll_image" name="poll_image" accept="image/*" />
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_contest" value="1" />
                        Submit as contest entry
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="pollmaster-submit-btn">Create Poll</button>
                </div>
            </form>
            
            <div id="pollmaster-form-messages"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * User dashboard shortcode
     */
    public function user_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="pollmaster-login-required">Please log in to view your dashboard.</div>';
        }
        
        $user_id = get_current_user_id();
        $user_polls = $this->database->get_polls(array('user_id' => $user_id, 'limit' => 10));
        
        ob_start();
        ?>
        <div class="pollmaster-user-dashboard">
            <div class="dashboard-header">
                <h3>Your Polls Dashboard</h3>
                <p>Manage and track your poll submissions</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($user_polls); ?></span>
                    <span class="stat-label">Total Polls</span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-number">
                        <?php 
                        $total_votes = 0;
                        foreach ($user_polls as $poll) {
                            $results = $this->database->get_poll_results($poll->id);
                            $total_votes += $results['total_votes'];
                        }
                        echo $total_votes;
                        ?>
                    </span>
                    <span class="stat-label">Total Votes</span>
                </div>
            </div>
            
            <?php if (!empty($user_polls)): ?>
                <div class="dashboard-polls">
                    <h4>Your Recent Polls</h4>
                    <div class="polls-list">
                        <?php foreach ($user_polls as $poll): ?>
                            <?php $results = $this->database->get_poll_results($poll->id); ?>
                            <div class="poll-item">
                                <div class="poll-info">
                                    <h5><?php echo esc_html($poll->question); ?></h5>
                                    <div class="poll-meta">
                                        <span class="poll-votes"><?php echo $results['total_votes']; ?> votes</span>
                                        <span class="poll-date"><?php echo date_i18n(get_option('date_format'), strtotime($poll->created_at)); ?></span>
                                        <span class="poll-status status-<?php echo esc_attr($poll->status); ?>"><?php echo esc_html(ucfirst($poll->status)); ?></span>
                                    </div>
                                </div>
                                <div class="poll-actions">
                                    <button class="view-results-btn" data-poll-id="<?php echo esc_attr($poll->id); ?>">View Results</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-polls">
                    <p>You haven't created any polls yet.</p>
                    <a href="#" class="create-poll-btn">Create Your First Poll</a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Poll list shortcode
     */
    public function poll_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'all',
            'limit' => '12',
            'show_filters' => 'false',
            'show_search' => 'false',
            'per_page' => '12',
            'show_results' => 'false',
            'columns' => '3'
        ), $atts, 'pollmaster_poll_list');
        
        $filters = array();
        if ($atts['type'] !== 'all') {
            $filters['type'] = $atts['type'];
        }
        
        $polls = $this->database->get_polls($filters);
        
        ob_start();
        ?>
        <div class="pollmaster-poll-list" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <?php if ($atts['show_filters'] === 'true' || $atts['show_search'] === 'true'): ?>
                <div class="poll-list-filters">
                    <?php if ($atts['show_search'] === 'true'): ?>
                        <div class="search-box">
                            <input type="text" placeholder="Search polls..." class="poll-search-input" />
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($atts['show_filters'] === 'true'): ?>
                        <div class="filter-box">
                            <select class="poll-filter-select">
                                <option value="all">All Polls</option>
                                <option value="contest">Contests</option>
                                <option value="weekly">Weekly Polls</option>
                                <option value="regular">Regular Polls</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="polls-grid">
                <?php if (empty($polls)): ?>
                    <div class="no-polls-message">
                        <p>No polls found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($polls as $poll): ?>
                        <div class="poll-list-item">
                            <?php if ($poll->image_url): ?>
                                <div class="poll-item-image">
                                    <img src="<?php echo esc_url($poll->image_url); ?>" alt="<?php echo esc_attr($poll->question); ?>" />
                                </div>
                            <?php endif; ?>
                            
                            <div class="poll-item-content">
                                <h4 class="poll-item-title"><?php echo esc_html($poll->question); ?></h4>
                                
                                <div class="poll-item-meta">
                                    <?php if ($poll->is_contest): ?>
                                        <span class="poll-badge contest">Contest</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($poll->is_weekly): ?>
                                        <span class="poll-badge weekly">Weekly</span>
                                    <?php endif; ?>
                                    
                                    <span class="poll-date"><?php echo date_i18n(get_option('date_format'), strtotime($poll->created_at)); ?></span>
                                </div>
                                
                                <?php if ($atts['show_results'] === 'true'): ?>
                                    <div class="poll-item-results">
                                        <?php echo $this->results_shortcode(array('poll_id' => $poll->id, 'show_chart' => 'false')); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="poll-item-actions">
                                        <button class="vote-now-btn" data-poll-id="<?php echo esc_attr($poll->id); ?>">Vote Now</button>
                                        <button class="view-results-btn" data-poll-id="<?php echo esc_attr($poll->id); ?>">View Results</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get user's vote for a poll
     */
    private function get_user_vote($poll_id, $user_id) {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        
        $vote = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT vote_option FROM $votes_table WHERE poll_id = %d AND user_id = %d",
                $poll_id,
                $user_id
            )
        );
        
        return $vote === 'option_a' ? 0 : ($vote === 'option_b' ? 1 : null);
    }
    
    /**
     * Render template
     */
    private function render_template($template, $args = array()) {
        $template_path = POLLMASTER_PLUGIN_PATH . 'templates/' . $template . '.php';
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '<div class="pollmaster-error">Template not found: ' . esc_html($template) . '</div>';
    }
}