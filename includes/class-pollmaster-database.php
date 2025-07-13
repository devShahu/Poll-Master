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
            description text DEFAULT NULL,
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
        
        // Check for database upgrades
        $this->upgrade_database();
    }
    
    /**
     * Upgrade database schema if needed
     */
    public function upgrade_database() {
        global $wpdb;
        
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        
        // Check if description column exists
        $description_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'description'",
                DB_NAME,
                $polls_table
            )
        );
        
        // Add description column if it doesn't exist
        if (empty($description_exists)) {
            $wpdb->query(
                "ALTER TABLE $polls_table ADD COLUMN description text DEFAULT NULL AFTER image_url"
            );
        }
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
     * Check if database tables exist
     */
    public function check_tables_exist() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'pollmaster_polls',
            $wpdb->prefix . 'pollmaster_votes',
            $wpdb->prefix . 'pollmaster_contest_winners',
            $wpdb->prefix . 'pollmaster_shares'
        );
        
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            if (!$exists) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get poll by ID (for public use - only active polls)
     */
    public function get_poll($poll_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        $poll = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND status = 'active'",
                $poll_id
            )
        );
        
        // Add options property for backward compatibility
        if ($poll) {
            $poll->options = json_encode([$poll->option_a, $poll->option_b]);
        }
        
        return $poll;
    }
    
    /**
     * Get poll by ID (for admin use - any status)
     */
    public function get_poll_admin($poll_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        $poll = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $poll_id
            )
        );
        
        // Add options property for backward compatibility
        if ($poll) {
            $poll->options = json_encode([$poll->option_a, $poll->option_b]);
        }
        
        return $poll;
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
     * Get polls with pagination or array parameters
     */
    public function get_polls($args = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        // Handle legacy parameters (page, per_page, user_id, type)
        if (!is_array($args)) {
            $page = $args;
            $per_page = func_num_args() > 1 ? func_get_arg(1) : 10;
            $user_id = func_num_args() > 2 ? func_get_arg(2) : null;
            $type = func_num_args() > 3 ? func_get_arg(3) : 'all';
            
            $args = array(
                'page' => $page,
                'per_page' => $per_page,
                'user_id' => $user_id,
                'type' => $type
            );
        }
        
        // Default arguments
        $defaults = array(
            'page' => 1,
            'per_page' => 10,
            'limit' => null,
            'user_id' => null,
            'type' => 'all',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'status' => 'active'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = array("status = %s");
        $params = array($args['status']);
        
        if ($args['user_id']) {
            $where_conditions[] = "user_id = %d";
            $params[] = $args['user_id'];
        }
        
        if ($args['type'] === 'weekly') {
            $where_conditions[] = "is_weekly = 1";
        } elseif ($args['type'] === 'contest') {
            $where_conditions[] = "is_contest = 1";
        } elseif ($args['type'] === 'regular') {
            $where_conditions[] = "is_weekly = 0 AND is_contest = 0";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Order clause
        $order_clause = "ORDER BY {$args['orderby']} {$args['order']}";
        
        // Limit clause
        if ($args['limit']) {
            $limit_clause = "LIMIT %d";
            $params[] = $args['limit'];
        } else {
            $offset = ($args['page'] - 1) * $args['per_page'];
            $limit_clause = "LIMIT %d OFFSET %d";
            $params[] = $args['per_page'];
            $params[] = $offset;
        }
        
        $sql = "SELECT * FROM $table WHERE $where_clause $order_clause $limit_clause";
        
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
     * Get pending polls (user-submitted polls awaiting approval)
     */
    public function get_pending_polls($page = 1, $per_page = 10) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT * FROM $table WHERE status = 'pending' ORDER BY created_at DESC LIMIT %d OFFSET %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
    }
    
    /**
     * Get pending polls count
     */
    public function get_pending_polls_count() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'");
    }
    
    /**
     * Approve a pending poll
     */
    public function approve_poll($poll_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        return $wpdb->update(
            $table,
            array('status' => 'active', 'updated_at' => current_time('mysql')),
            array('id' => $poll_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Reject a pending poll
     */
    public function reject_poll($poll_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        return $wpdb->update(
            $table,
            array('status' => 'rejected', 'updated_at' => current_time('mysql')),
            array('id' => $poll_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Update poll status
     */
    public function update_poll_status($poll_id, $status) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        return $wpdb->update(
            $table,
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('id' => $poll_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Create new poll
     */
    public function create_poll($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data) || !is_array($data)) {
            return new WP_Error('invalid_data', 'Poll data is required and must be an array.');
        }
        
        // Set default user_id if not provided
        $user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        if ($user_id <= 0) {
            // If no valid user_id provided, use current user or default to 1
            $current_user_id = get_current_user_id();
            $user_id = $current_user_id > 0 ? $current_user_id : 1;
        }
        
        // Check required fields
        $question = isset($data['question']) ? trim($data['question']) : '';
        $option_a = isset($data['option_a']) ? trim($data['option_a']) : '';
        $option_b = isset($data['option_b']) ? trim($data['option_b']) : '';
        
        if (empty($question)) {
            return new WP_Error('missing_field', 'Poll question is required.');
        }
        
        if (empty($option_a) || empty($option_b)) {
            return new WP_Error('missing_field', 'Both poll options (option_a and option_b) are required.');
        }
        
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        $poll_data = array(
            'user_id' => $user_id,
            'question' => sanitize_text_field($question),
            'option_a' => sanitize_text_field($option_a),
            'option_b' => sanitize_text_field($option_b),
            'image_url' => isset($data['image_url']) && !empty($data['image_url']) ? esc_url_raw($data['image_url']) : null,
            'description' => isset($data['description']) && !empty($data['description']) ? sanitize_textarea_field($data['description']) : null,
            'is_weekly' => isset($data['is_weekly']) ? (int)$data['is_weekly'] : 0,
            'is_contest' => isset($data['is_contest']) ? (int)$data['is_contest'] : 0,
            'contest_prize' => isset($data['contest_prize']) && !empty($data['contest_prize']) ? sanitize_textarea_field($data['contest_prize']) : null,
            'contest_end_date' => isset($data['contest_end_date']) && !empty($data['contest_end_date']) ? $data['contest_end_date'] : null,
            'status' => isset($data['status']) && !empty($data['status']) ? sanitize_text_field($data['status']) : 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // Additional validation
        if (strlen($poll_data['question']) < 5) {
            return new WP_Error('invalid_question', 'Question must be at least 5 characters long.');
        }
        
        if (strlen($poll_data['option_a']) < 1 || strlen($poll_data['option_b']) < 1) {
            return new WP_Error('invalid_options', 'Both poll options must be provided.');
        }
        
        $result = $wpdb->insert($table, $poll_data);
        
        if ($result !== false) {
            return $wpdb->insert_id;
        }
        
        return new WP_Error('database_error', 'Failed to create poll: ' . $wpdb->last_error);
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
     * Check if IP address voted on poll
     */
    public function has_ip_voted($poll_id, $ip_address) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pollmaster_votes';
        
        $vote = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE poll_id = %d AND ip_address = %s AND user_id IS NULL",
                $poll_id,
                $ip_address
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
     * Get contest winner for specific poll
     */
    public function get_contest_winner($poll_id) {
        global $wpdb;
        
        $winners_table = $wpdb->prefix . 'pollmaster_contest_winners';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT w.*, u.display_name as winner_name 
                 FROM $winners_table w 
                 LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID 
                 WHERE w.poll_id = %d 
                 ORDER BY w.announced_at DESC 
                 LIMIT 1",
                $poll_id
            )
        );
    }
    
    /**
     * Get weekly poll
     */
    public function get_weekly_poll() {
        global $wpdb;
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        
        $poll = $wpdb->get_row(
            "SELECT * FROM {$polls_table} WHERE is_weekly = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 1"
        );
        
        return $poll;
    }
    
    /**
     * Get random poll
     */
    public function get_random_poll() {
        global $wpdb;
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        
        $poll = $wpdb->get_row(
            "SELECT * FROM {$polls_table} WHERE status = 'active' ORDER BY RAND() LIMIT 1"
        );
        
        return $poll;
    }
    
    /**
     * Get past polls
     */
    public function get_past_polls($limit = 10, $type = 'all') {
        global $wpdb;
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        
        $where_clause = "WHERE status = 'active'";
        
        if ($type === 'weekly') {
            $where_clause .= " AND is_weekly = 1";
        } elseif ($type === 'contest') {
            $where_clause .= " AND is_contest = 1";
        }
        
        $polls = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$polls_table} {$where_clause} ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
        
        return $polls;
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
     * Get recent votes
     */
    public function get_recent_votes($limit = 10) {
        global $wpdb;
        
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT v.*, p.question, p.option_a, p.option_b, u.display_name as voter_name 
                 FROM $votes_table v 
                 LEFT JOIN $polls_table p ON v.poll_id = p.id 
                 LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID 
                 ORDER BY v.voted_at DESC 
                 LIMIT %d",
                $limit
            )
        );
    }
    
    /**
     * Get polls with pagination
     */
    public function get_polls_with_pagination($filters = array(), $per_page = 10, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pollmaster_polls';
        $where_conditions = array('1=1');
        $where_values = array();
        
        // Apply filters
        if (!empty($filters['search'])) {
            $where_conditions[] = '(question LIKE %s OR description LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['type'])) {
            switch ($filters['type']) {
                case 'contest':
                    $where_conditions[] = 'is_contest = 1';
                    break;
                case 'weekly':
                    $where_conditions[] = 'is_weekly = 1';
                    break;
                case 'regular':
                    $where_conditions[] = 'is_contest = 0 AND is_weekly = 0';
                    break;
            }
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'DATE(created_at) >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'DATE(created_at) <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
        if (!empty($where_values)) {
            $total = $wpdb->get_var($wpdb->prepare($count_query, $where_values));
        } else {
            $total = $wpdb->get_var($count_query);
        }
        
        // Get polls
        $polls_query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, array($per_page, $offset));
        
        if (!empty($where_values)) {
            $polls = $wpdb->get_results($wpdb->prepare($polls_query, $query_values), ARRAY_A);
        } else {
            $polls = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
        }
        
        return array(
             'polls' => $polls ?: array(),
             'total' => intval($total)
         );
     }
     
     /**
      * Remove weekly status from all polls
      */
     public function remove_all_weekly_polls() {
         global $wpdb;
         
         $table_name = $wpdb->prefix . 'pollmaster_polls';
         
         return $wpdb->update(
             $table_name,
             array('is_weekly' => 0),
             array('is_weekly' => 1),
             array('%d'),
             array('%d')
         );
     }
     
     /**
      * Get dashboard statistics
      */
    public function get_dashboard_stats() {
        global $wpdb;
        
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        $winners_table = $wpdb->prefix . 'pollmaster_contest_winners';
        
        // Get total polls count
        $total_polls = $wpdb->get_var(
            "SELECT COUNT(*) FROM $polls_table WHERE status = 'active'"
        );
        
        // Get total votes count
        $total_votes = $wpdb->get_var(
            "SELECT COUNT(*) FROM $votes_table"
        );
        
        // Get active polls count (same as total for now)
        $active_polls = $total_polls;
        
        // Get active contests count
        $active_contests = $wpdb->get_var(
            "SELECT COUNT(*) FROM $polls_table WHERE is_contest = 1 AND status = 'active'"
        );
        
        // Get total contests count
        $total_contests = $wpdb->get_var(
            "SELECT COUNT(*) FROM $polls_table WHERE is_contest = 1"
        );
        
        // Get total winners count
        $total_winners = $wpdb->get_var(
            "SELECT COUNT(*) FROM $winners_table"
        );
        
        // Get polls created this month
        $polls_this_month = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $polls_table WHERE status = 'active' AND created_at >= %s",
                date('Y-m-01 00:00:00')
            )
        );
        
        // Get votes today
        $votes_today = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $votes_table WHERE voted_at >= %s",
                date('Y-m-d 00:00:00')
            )
        );
        
        // Calculate engagement rate (simplified)
        $engagement_rate = $total_polls > 0 ? round(($total_votes / $total_polls), 1) : 0;
        
        // Get recent activity (polls created in last 7 days)
        $recent_polls = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $polls_table WHERE status = 'active' AND created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-7 days'))
            )
        );
        
        // Get recent votes (votes in last 7 days)
        $recent_votes = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $votes_table WHERE voted_at >= %s",
                date('Y-m-d H:i:s', strtotime('-7 days'))
            )
        );
        
        return array(
            'total_polls' => (int)$total_polls,
            'total_votes' => (int)$total_votes,
            'active_polls' => (int)$active_polls,
            'active_contests' => (int)$active_contests,
            'total_contests' => (int)$total_contests,
            'total_winners' => (int)$total_winners,
            'polls_this_month' => (int)$polls_this_month,
            'votes_today' => (int)$votes_today,
            'engagement_rate' => (float)$engagement_rate,
            'recent_polls' => (int)$recent_polls,
            'recent_votes' => (int)$recent_votes
        );
    }
    
    /**
     * Get poll votes history
     */
    public function get_poll_votes_history($poll_id) {
        global $wpdb;
        
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        $users_table = $wpdb->users;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT v.*, u.display_name as voter_name 
                 FROM $votes_table v 
                 LEFT JOIN $users_table u ON v.user_id = u.ID 
                 WHERE v.poll_id = %d 
                 ORDER BY v.voted_at ASC",
                $poll_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get poll shares
     */
    public function get_poll_shares($poll_id) {
        global $wpdb;
        
        // For now, return empty array as shares table might not exist
        // This can be implemented later when social sharing is added
        return [];
    }
    
    /**
     * Create sample polls for testing
     */
    public function create_sample_polls() {
        $sample_polls = [
            [
                'question' => 'What is your favorite programming language?',
                'option_a' => 'PHP',
                'option_b' => 'JavaScript',
                'description' => 'Help us understand developer preferences in our community.',
                'is_contest' => 0,
                'is_weekly' => 0,
                'status' => 'active'
            ],
            [
                'question' => 'Which framework do you prefer for web development?',
                'option_a' => 'React',
                'option_b' => 'Vue.js',
                'description' => 'Frontend framework preference survey.',
                'is_contest' => 1,
                'contest_prize' => '$100 Amazon Gift Card',
                'contest_end_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'is_weekly' => 0,
                'status' => 'active'
            ],
            [
                'question' => 'What is the best time for team meetings?',
                'option_a' => 'Morning (9-11 AM)',
                'option_b' => 'Afternoon (2-4 PM)',
                'description' => 'Weekly poll to determine optimal meeting times.',
                'is_contest' => 0,
                'is_weekly' => 1,
                'status' => 'active'
            ]
        ];
        
        $created_polls = [];
        foreach ($sample_polls as $poll_data) {
            $poll_id = $this->create_poll($poll_data);
            if (!is_wp_error($poll_id)) {
                $created_polls[] = $poll_id;
            }
        }
        
        return $created_polls;
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
    
    /**
     * Get user IP address (public method)
     */
    public function get_user_ip_public() {
        return $this->get_user_ip();
    }
}