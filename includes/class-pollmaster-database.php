<?php
/**
 * PollMaster Database Class
 * 
 * Handles database operations for polls, votes, and contests
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Database {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Database initialization is handled in activation hook
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Polls table
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        $polls_sql = "CREATE TABLE $polls_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            question varchar(255) NOT NULL,
            option_a varchar(50) NOT NULL,
            option_b varchar(50) NOT NULL,
            image_url varchar(500) DEFAULT NULL,
            is_weekly tinyint(1) DEFAULT 0,
            is_contest tinyint(1) DEFAULT 0,
            contest_prize text DEFAULT NULL,
            contest_end_date datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_weekly (is_weekly),
            KEY is_contest (is_contest),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Votes table
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        $votes_sql = "CREATE TABLE $votes_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            poll_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            vote_option varchar(10) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            voted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_vote (poll_id, user_id),
            KEY poll_id (poll_id),
            KEY user_id (user_id),
            KEY vote_option (vote_option),
            KEY voted_at (voted_at)
        ) $charset_collate;";
        
        // Contest winners table
        $winners_table = $wpdb->prefix . 'pollmaster_contest_winners';
        $winners_sql = "CREATE TABLE $winners_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            poll_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            prize_description text DEFAULT NULL,
            total_votes int(11) DEFAULT 0,
            announced_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'announced',
            PRIMARY KEY (id),
            KEY poll_id (poll_id),
            KEY user_id (user_id),
            KEY announced_at (announced_at)
        ) $charset_collate;";
        
        // Poll shares table (for tracking social shares)
        $shares_table = $wpdb->prefix . 'pollmaster_shares';
        $shares_sql = "CREATE TABLE $shares_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            poll_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            platform varchar(20) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            shared_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY poll_id (poll_id),
            KEY user_id (user_id),
            KEY platform (platform),
            KEY shared_at (shared_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($polls_sql);
        dbDelta($votes_sql);
        dbDelta($winners_sql);
        dbDelta($shares_sql);
        
        // Update version
        update_option('pollmaster_db_version', POLLMASTER_VERSION);
    }
    
    /**
     * Drop database tables
     */
    public function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'pollmaster_polls',
            $wpdb->prefix . 'pollmaster_votes',
            $wpdb->prefix . 'pollmaster_contest_winners',
            $wpdb->prefix . 'pollmaster_shares'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('pollmaster_db_version');
    }
    
    /**
     * Get poll by ID
     */
    public function get_poll($poll_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND status = 'active'",
                $poll_id
            )
        );
    }
    
    /**
     * Get latest poll (weekly poll has priority)
     */
    public function get_latest_poll() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        // First try to get weekly poll
        $weekly_poll = $wpdb->get_row(
            "SELECT * FROM $table WHERE is_weekly = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1"
        );
        
        if ($weekly_poll) {
            return $weekly_poll;
        }
        
        // If no weekly poll, get latest regular poll
        return $wpdb->get_row(
            "SELECT * FROM $table WHERE status = 'active' ORDER BY created_at DESC LIMIT 1"
        );
    }
    
    /**
     * Get polls with pagination
     */
    public function get_polls($page = 1, $per_page = 10, $user_id = null, $type = 'all') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = array("status = 'active'");
        $params = array();
        
        if ($user_id) {
            $where_conditions[] = "user_id = %d";
            $params[] = $user_id;
        }
        
        if ($type === 'weekly') {
            $where_conditions[] = "is_weekly = 1";
        } elseif ($type === 'contest') {
            $where_conditions[] = "is_contest = 1";
        } elseif ($type === 'regular') {
            $where_conditions[] = "is_weekly = 0 AND is_contest = 0";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $params[] = $per_page;
        $params[] = $offset;
        
        $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get total polls count
     */
    public function get_polls_count($user_id = null, $type = 'all') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        $where_conditions = array("status = 'active'");
        $params = array();
        
        if ($user_id) {
            $where_conditions[] = "user_id = %d";
            $params[] = $user_id;
        }
        
        if ($type === 'weekly') {
            $where_conditions[] = "is_weekly = 1";
        } elseif ($type === 'contest') {
            $where_conditions[] = "is_contest = 1";
        } elseif ($type === 'regular') {
            $where_conditions[] = "is_weekly = 0 AND is_contest = 0";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $sql = "SELECT COUNT(*) FROM $table WHERE $where_clause";
        
        if (empty($params)) {
            return $wpdb->get_var($sql);
        }
        
        return $wpdb->get_var($wpdb->prepare($sql, $params));
    }
    
    /**
     * Create new poll
     */
    public function create_poll($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        $poll_data = array(
            'user_id' => $data['user_id'],
            'question' => sanitize_text_field($data['question']),
            'option_a' => sanitize_text_field($data['option_a']),
            'option_b' => sanitize_text_field($data['option_b']),
            'image_url' => isset($data['image_url']) ? esc_url_raw($data['image_url']) : null,
            'is_weekly' => isset($data['is_weekly']) ? (int)$data['is_weekly'] : 0,
            'is_contest' => isset($data['is_contest']) ? (int)$data['is_contest'] : 0,
            'contest_prize' => isset($data['contest_prize']) ? sanitize_textarea_field($data['contest_prize']) : null,
            'contest_end_date' => isset($data['contest_end_date']) ? $data['contest_end_date'] : null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table, $poll_data);
        
        if ($result !== false) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update poll
     */
    public function update_poll($poll_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        $poll_data = array(
            'question' => sanitize_text_field($data['question']),
            'option_a' => sanitize_text_field($data['option_a']),
            'option_b' => sanitize_text_field($data['option_b']),
            'updated_at' => current_time('mysql')
        );
        
        if (isset($data['image_url'])) {
            $poll_data['image_url'] = esc_url_raw($data['image_url']);
        }
        
        if (isset($data['is_weekly'])) {
            $poll_data['is_weekly'] = (int)$data['is_weekly'];
        }
        
        if (isset($data['is_contest'])) {
            $poll_data['is_contest'] = (int)$data['is_contest'];
        }
        
        if (isset($data['contest_prize'])) {
            $poll_data['contest_prize'] = sanitize_textarea_field($data['contest_prize']);
        }
        
        if (isset($data['contest_end_date'])) {
            $poll_data['contest_end_date'] = $data['contest_end_date'];
        }
        
        return $wpdb->update(
            $table,
            $poll_data,
            array('id' => $poll_id),
            null,
            array('%d')
        );
    }
    
    /**
     * Delete poll
     */
    public function delete_poll($poll_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        return $wpdb->update(
            $table,
            array('status' => 'deleted', 'updated_at' => current_time('mysql')),
            array('id' => $poll_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Cast vote
     */
    public function cast_vote($poll_id, $user_id, $vote_option) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_votes';
        
        // Check if user already voted
        $existing_vote = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE poll_id = %d AND user_id = %d",
                $poll_id,
                $user_id
            )
        );
        
        if ($existing_vote) {
            return false; // User already voted
        }
        
        $vote_data = array(
            'poll_id' => $poll_id,
            'user_id' => $user_id,
            'vote_option' => sanitize_text_field($vote_option),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'voted_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table, $vote_data);
        
        return $result !== false;
    }
    
    /**
     * Get poll results
     */
    public function get_poll_results($poll_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_votes';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vote_option, COUNT(*) as count FROM $table WHERE poll_id = %d GROUP BY vote_option",
                $poll_id
            )
        );
        
        $total_votes = 0;
        $vote_counts = array('option_a' => 0, 'option_b' => 0);
        
        foreach ($results as $result) {
            $vote_counts[$result->vote_option] = (int)$result->count;
            $total_votes += (int)$result->count;
        }
        
        $percentages = array(
            'option_a' => $total_votes > 0 ? round(($vote_counts['option_a'] / $total_votes) * 100, 1) : 0,
            'option_b' => $total_votes > 0 ? round(($vote_counts['option_b'] / $total_votes) * 100, 1) : 0
        );
        
        return array(
            'total_votes' => $total_votes,
            'vote_counts' => $vote_counts,
            'percentages' => $percentages
        );
    }
    
    /**
     * Check if user voted on poll
     */
    public function has_user_voted($poll_id, $user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_votes';
        
        $vote = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE poll_id = %d AND user_id = %d",
                $poll_id,
                $user_id
            )
        );
        
        return !empty($vote);
    }
    
    /**
     * Record social share
     */
    public function record_share($poll_id, $platform, $user_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_shares';
        
        $share_data = array(
            'poll_id' => $poll_id,
            'user_id' => $user_id,
            'platform' => sanitize_text_field($platform),
            'ip_address' => $this->get_user_ip(),
            'shared_at' => current_time('mysql')
        );
        
        return $wpdb->insert($table, $share_data);
    }
    
    /**
     * Get contest winners
     */
    public function get_contest_winners($limit = 10) {
        global $wpdb;
        
        $winners_table = $wpdb->prefix . 'pollmaster_contest_winners';
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT w.*, p.question, p.image_url, u.display_name 
                 FROM $winners_table w 
                 LEFT JOIN $polls_table p ON w.poll_id = p.id 
                 LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID 
                 ORDER BY w.announced_at DESC 
                 LIMIT %d",
                $limit
            )
        );
    }
    
    /**
     * Announce contest winner
     */
    public function announce_contest_winner($poll_id, $user_id, $prize_description, $total_votes) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_contest_winners';
        
        $winner_data = array(
            'poll_id' => $poll_id,
            'user_id' => $user_id,
            'prize_description' => sanitize_textarea_field($prize_description),
            'total_votes' => (int)$total_votes,
            'announced_at' => current_time('mysql')
        );
        
        return $wpdb->insert($table, $winner_data);
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
}