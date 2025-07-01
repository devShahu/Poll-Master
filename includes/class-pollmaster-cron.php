<?php
/**
 * PollMaster Cron Class
 * 
 * Handles scheduled tasks for the PollMaster plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Cron {
    
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new PollMaster_Database();
        
        // Hook into WordPress cron
        add_action('pollmaster_weekly_poll_check', array($this, 'check_weekly_polls'));
        add_action('pollmaster_contest_end_check', array($this, 'check_contest_endings'));
        add_action('pollmaster_cleanup_old_data', array($this, 'cleanup_old_data'));
        add_action('pollmaster_send_notifications', array($this, 'send_notifications'));
        
        // Schedule events if not already scheduled
        add_action('init', array($this, 'schedule_events'));
        
        // Clean up scheduled events on deactivation
        register_deactivation_hook(POLLMASTER_PLUGIN_FILE, array($this, 'clear_scheduled_events'));
    }
    
    /**
     * Schedule cron events
     */
    public function schedule_events() {
        // Weekly poll check - runs every Monday at 9 AM
        if (!wp_next_scheduled('pollmaster_weekly_poll_check')) {
            wp_schedule_event(
                strtotime('next Monday 9:00 AM'),
                'weekly',
                'pollmaster_weekly_poll_check'
            );
        }
        
        // Contest end check - runs daily at midnight
        if (!wp_next_scheduled('pollmaster_contest_end_check')) {
            wp_schedule_event(
                strtotime('tomorrow midnight'),
                'daily',
                'pollmaster_contest_end_check'
            );
        }
        
        // Cleanup old data - runs monthly
        if (!wp_next_scheduled('pollmaster_cleanup_old_data')) {
            wp_schedule_event(
                strtotime('first day of next month midnight'),
                'monthly',
                'pollmaster_cleanup_old_data'
            );
        }
        
        // Send notifications - runs hourly
        if (!wp_next_scheduled('pollmaster_send_notifications')) {
            wp_schedule_event(
                time(),
                'hourly',
                'pollmaster_send_notifications'
            );
        }
    }
    
    /**
     * Clear all scheduled events
     */
    public function clear_scheduled_events() {
        wp_clear_scheduled_hook('pollmaster_weekly_poll_check');
        wp_clear_scheduled_hook('pollmaster_contest_end_check');
        wp_clear_scheduled_hook('pollmaster_cleanup_old_data');
        wp_clear_scheduled_hook('pollmaster_send_notifications');
    }
    
    /**
     * Check and manage weekly polls
     */
    public function check_weekly_polls() {
        global $wpdb;
        $table = $wpdb->prefix . 'pollmaster_polls';
        
        // Get current weekly poll
        $current_weekly = $wpdb->get_row(
            "SELECT * FROM $table 
             WHERE is_weekly = 1 AND status = 'active' 
             ORDER BY created_at DESC LIMIT 1"
        );
        
        $settings = get_option('pollmaster_settings', array());
        $auto_weekly = isset($settings['auto_weekly_polls']) ? $settings['auto_weekly_polls'] : false;
        
        // If no current weekly poll and auto-creation is enabled
        if (!$current_weekly && $auto_weekly) {
            $this->create_auto_weekly_poll();
        }
        
        // Archive old weekly polls (older than 1 week)
        $one_week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));
        $wpdb->update(
            $table,
            array('status' => 'archived'),
            array(
                'is_weekly' => 1,
                'status' => 'active'
            ),
            array('%s'),
            array('%d', '%s')
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET status = 'archived' 
                 WHERE is_weekly = 1 AND status = 'active' AND created_at < %s",
                $one_week_ago
            )
        );
        
        // Log weekly poll check
        $this->log_cron_activity('weekly_poll_check', array(
            'current_weekly_poll' => $current_weekly ? $current_weekly->id : null,
            'auto_creation_enabled' => $auto_weekly
        ));
    }
    
    /**
     * Check for contest endings and announce winners
     */
    public function check_contest_endings() {
        global $wpdb;
        $table = $wpdb->prefix . 'pollmaster_polls';
        $winners_table = $wpdb->prefix . 'pollmaster_contest_winners';
        
        // Get contests that have ended but don't have winners yet
        $ended_contests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.* FROM $table p
                 LEFT JOIN $winners_table w ON p.id = w.poll_id
                 WHERE p.is_contest = 1 
                 AND p.status = 'active'
                 AND p.contest_end_date IS NOT NULL
                 AND p.contest_end_date < %s
                 AND w.poll_id IS NULL",
                current_time('mysql')
            )
        );
        
        $settings = get_option('pollmaster_settings', array());
        $auto_announce = isset($settings['auto_announce_winners']) ? $settings['auto_announce_winners'] : false;
        
        foreach ($ended_contests as $contest) {
            // Update contest status to ended
            $wpdb->update(
                $table,
                array('status' => 'ended'),
                array('id' => $contest->id),
                array('%s'),
                array('%d')
            );
            
            if ($auto_announce) {
                $this->auto_announce_contest_winner($contest);
            }
            
            // Send notification to admin
            $this->send_contest_ended_notification($contest);
        }
        
        // Log contest check
        $this->log_cron_activity('contest_end_check', array(
            'ended_contests_count' => count($ended_contests),
            'auto_announce_enabled' => $auto_announce
        ));
    }
    
    /**
     * Cleanup old data
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        $settings = get_option('pollmaster_settings', array());
        $cleanup_days = isset($settings['cleanup_days']) ? (int) $settings['cleanup_days'] : 365;
        
        if ($cleanup_days <= 0) {
            return; // Cleanup disabled
        }
        
        $cleanup_date = date('Y-m-d H:i:s', strtotime("-{$cleanup_days} days"));
        
        // Get tables
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        $shares_table = $wpdb->prefix . 'pollmaster_shares';
        $winners_table = $wpdb->prefix . 'pollmaster_contest_winners';
        
        // Get old poll IDs
        $old_poll_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM $polls_table 
                 WHERE created_at < %s AND status IN ('archived', 'ended')",
                $cleanup_date
            )
        );
        
        if (!empty($old_poll_ids)) {
            $poll_ids_placeholder = implode(',', array_fill(0, count($old_poll_ids), '%d'));
            
            // Delete related votes
            $votes_deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $votes_table WHERE poll_id IN ($poll_ids_placeholder)",
                    ...$old_poll_ids
                )
            );
            
            // Delete related shares
            $shares_deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $shares_table WHERE poll_id IN ($poll_ids_placeholder)",
                    ...$old_poll_ids
                )
            );
            
            // Delete contest winners
            $winners_deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $winners_table WHERE poll_id IN ($poll_ids_placeholder)",
                    ...$old_poll_ids
                )
            );
            
            // Delete polls
            $polls_deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $polls_table WHERE id IN ($poll_ids_placeholder)",
                    ...$old_poll_ids
                )
            );
            
            // Log cleanup activity
            $this->log_cron_activity('cleanup_old_data', array(
                'cleanup_days' => $cleanup_days,
                'polls_deleted' => $polls_deleted,
                'votes_deleted' => $votes_deleted,
                'shares_deleted' => $shares_deleted,
                'winners_deleted' => $winners_deleted
            ));
        }
        
        // Clean up orphaned user meta
        $this->cleanup_orphaned_user_meta();
    }
    
    /**
     * Send scheduled notifications
     */
    public function send_notifications() {
        // Get pending notifications
        $notifications = get_option('pollmaster_pending_notifications', array());
        
        if (empty($notifications)) {
            return;
        }
        
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($notifications as $key => $notification) {
            // Check if it's time to send
            if (time() >= $notification['send_time']) {
                $result = $this->send_notification($notification);
                
                if ($result) {
                    $sent_count++;
                    unset($notifications[$key]);
                } else {
                    $failed_count++;
                    // Retry later (add 1 hour)
                    $notifications[$key]['send_time'] = time() + 3600;
                    $notifications[$key]['retry_count'] = isset($notification['retry_count']) ? $notification['retry_count'] + 1 : 1;
                    
                    // Remove after 3 failed attempts
                    if ($notifications[$key]['retry_count'] >= 3) {
                        unset($notifications[$key]);
                    }
                }
            }
        }
        
        // Update notifications queue
        update_option('pollmaster_pending_notifications', array_values($notifications));
        
        // Log notification activity
        if ($sent_count > 0 || $failed_count > 0) {
            $this->log_cron_activity('send_notifications', array(
                'sent_count' => $sent_count,
                'failed_count' => $failed_count,
                'pending_count' => count($notifications)
            ));
        }
    }
    
    /**
     * Create automatic weekly poll
     */
    private function create_auto_weekly_poll() {
        $settings = get_option('pollmaster_settings', array());
        $weekly_questions = isset($settings['weekly_poll_questions']) ? $settings['weekly_poll_questions'] : array();
        
        if (empty($weekly_questions)) {
            // Create a default weekly poll
            $question = __('What\'s your favorite day of the week?', 'pollmaster');
            $option_a = __('Weekdays', 'pollmaster');
            $option_b = __('Weekends', 'pollmaster');
        } else {
            // Pick a random question from the pool
            $random_question = $weekly_questions[array_rand($weekly_questions)];
            $question = $random_question['question'];
            $option_a = $random_question['option_a'];
            $option_b = $random_question['option_b'];
        }
        
        $poll_data = array(
            'question' => $question,
            'option_a' => $option_a,
            'option_b' => $option_b,
            'is_weekly' => 1,
            'created_by' => 1, // Admin user
            'status' => 'active'
        );
        
        $poll_id = $this->database->create_poll($poll_data);
        
        if ($poll_id) {
            // Schedule notification about new weekly poll
            $this->schedule_notification(array(
                'type' => 'weekly_poll_created',
                'poll_id' => $poll_id,
                'send_time' => time() + 300 // Send in 5 minutes
            ));
        }
        
        return $poll_id;
    }
    
    /**
     * Auto announce contest winner
     */
    private function auto_announce_contest_winner($contest) {
        // Get poll results
        $results = $this->database->get_poll_results($contest->id);
        
        if ($results['total_votes'] == 0) {
            return false; // No votes, no winner
        }
        
        // Determine winning option
        $winning_option = $results['vote_counts']['option_a'] > $results['vote_counts']['option_b'] ? 'option_a' : 'option_b';
        $winning_votes = max($results['vote_counts']['option_a'], $results['vote_counts']['option_b']);
        
        // Get random winner from winning option
        global $wpdb;
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        $winner = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT user_id FROM $votes_table 
                 WHERE poll_id = %d AND vote_option = %s 
                 ORDER BY RAND() LIMIT 1",
                $contest->id,
                $winning_option
            )
        );
        
        if (!$winner) {
            return false;
        }
        
        // Announce winner
        $result = $this->database->announce_contest_winner(
            $contest->id,
            $winner->user_id,
            $contest->contest_prize,
            $winning_votes
        );
        
        if ($result) {
            // Schedule winner notification
            $this->schedule_notification(array(
                'type' => 'contest_winner_announced',
                'poll_id' => $contest->id,
                'winner_id' => $winner->user_id,
                'send_time' => time() + 600 // Send in 10 minutes
            ));
        }
        
        return $result;
    }
    
    /**
     * Send contest ended notification to admin
     */
    private function send_contest_ended_notification($contest) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] Contest Ended: %s', 'pollmaster'), $site_name, $contest->question);
        
        $message = sprintf(
            __('A contest has ended on your website:\n\nContest: %s\nPrize: %s\nEnd Date: %s\n\nPlease review the results and announce the winner if auto-announcement is disabled.\n\nView Contest: %s', 'pollmaster'),
            $contest->question,
            $contest->contest_prize,
            $contest->contest_end_date,
            admin_url('admin.php?page=pollmaster-contests&poll_id=' . $contest->id)
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Schedule a notification
     */
    private function schedule_notification($notification) {
        $notifications = get_option('pollmaster_pending_notifications', array());
        $notifications[] = $notification;
        update_option('pollmaster_pending_notifications', $notifications);
    }
    
    /**
     * Send a notification
     */
    private function send_notification($notification) {
        switch ($notification['type']) {
            case 'weekly_poll_created':
                return $this->send_weekly_poll_notification($notification['poll_id']);
                
            case 'contest_winner_announced':
                return $this->send_winner_notification($notification['poll_id'], $notification['winner_id']);
                
            case 'contest_reminder':
                return $this->send_contest_reminder($notification['poll_id']);
                
            default:
                return false;
        }
    }
    
    /**
     * Send weekly poll notification
     */
    private function send_weekly_poll_notification($poll_id) {
        $poll = $this->database->get_poll($poll_id);
        if (!$poll) {
            return false;
        }
        
        $settings = get_option('pollmaster_settings', array());
        $notify_users = isset($settings['notify_weekly_polls']) ? $settings['notify_weekly_polls'] : false;
        
        if (!$notify_users) {
            return true; // Notification disabled
        }
        
        // Get subscribers (users who opted in for notifications)
        $subscribers = get_users(array(
            'meta_key' => 'pollmaster_notifications',
            'meta_value' => '1'
        ));
        
        $site_name = get_bloginfo('name');
        $subject = sprintf(__('[%s] New Weekly Poll: %s', 'pollmaster'), $site_name, $poll->question);
        
        $sent_count = 0;
        
        foreach ($subscribers as $user) {
            $message = sprintf(
                __('Hi %s,\n\nA new weekly poll is now available:\n\n%s\n\nVote now: %s\n\nTo unsubscribe from these notifications, visit your profile settings.', 'pollmaster'),
                $user->display_name,
                $poll->question,
                home_url('/?poll_id=' . $poll->id)
            );
            
            if (wp_mail($user->user_email, $subject, $message)) {
                $sent_count++;
            }
        }
        
        return $sent_count > 0;
    }
    
    /**
     * Send winner notification
     */
    private function send_winner_notification($poll_id, $winner_id) {
        $poll = $this->database->get_poll($poll_id);
        $winner = get_userdata($winner_id);
        
        if (!$poll || !$winner) {
            return false;
        }
        
        $site_name = get_bloginfo('name');
        $subject = sprintf(__('[%s] Congratulations! You won: %s', 'pollmaster'), $site_name, $poll->contest_prize);
        
        $message = sprintf(
            __('Congratulations %s!\n\nYou have won the contest "%s"!\n\nPrize: %s\n\nWe will contact you soon with details on how to claim your prize.\n\nThank you for participating!', 'pollmaster'),
            $winner->display_name,
            $poll->question,
            $poll->contest_prize
        );
        
        return wp_mail($winner->user_email, $subject, $message);
    }
    
    /**
     * Send contest reminder
     */
    private function send_contest_reminder($poll_id) {
        $poll = $this->database->get_poll($poll_id);
        if (!$poll || !$poll->is_contest) {
            return false;
        }
        
        // Get users who haven't voted yet
        global $wpdb;
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        
        $voted_users = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT user_id FROM $votes_table WHERE poll_id = %d",
                $poll_id
            )
        );
        
        $all_users = get_users(array('fields' => 'ID'));
        $non_voted_users = array_diff($all_users, $voted_users);
        
        if (empty($non_voted_users)) {
            return true; // Everyone has voted
        }
        
        $site_name = get_bloginfo('name');
        $subject = sprintf(__('[%s] Contest Ending Soon: %s', 'pollmaster'), $site_name, $poll->question);
        
        $sent_count = 0;
        
        foreach ($non_voted_users as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) continue;
            
            $message = sprintf(
                __('Hi %s,\n\nDon\'t miss out on our contest!\n\n%s\n\nPrize: %s\nEnds: %s\n\nVote now: %s', 'pollmaster'),
                $user->display_name,
                $poll->question,
                $poll->contest_prize,
                date_i18n(get_option('date_format'), strtotime($poll->contest_end_date)),
                home_url('/?poll_id=' . $poll->id)
            );
            
            if (wp_mail($user->user_email, $subject, $message)) {
                $sent_count++;
            }
        }
        
        return $sent_count > 0;
    }
    
    /**
     * Cleanup orphaned user meta
     */
    private function cleanup_orphaned_user_meta() {
        global $wpdb;
        
        // Clean up popup dismissal meta for deleted polls
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        
        $existing_poll_ids = $wpdb->get_col("SELECT id FROM $polls_table");
        
        if (empty($existing_poll_ids)) {
            return;
        }
        
        $poll_ids_placeholder = implode(',', array_fill(0, count($existing_poll_ids), '%d'));
        
        // Get all popup dismissal meta keys
        $meta_keys = $wpdb->get_col(
            "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} 
             WHERE meta_key LIKE 'pollmaster_popup_dismissed_%'"
        );
        
        foreach ($meta_keys as $meta_key) {
            $poll_id = str_replace('pollmaster_popup_dismissed_', '', $meta_key);
            
            if (is_numeric($poll_id) && !in_array($poll_id, $existing_poll_ids)) {
                // Delete orphaned meta
                $wpdb->delete(
                    $wpdb->usermeta,
                    array('meta_key' => $meta_key),
                    array('%s')
                );
            }
        }
    }
    
    /**
     * Log cron activity
     */
    private function log_cron_activity($activity, $data = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'activity' => $activity,
            'data' => $data
        );
        
        $logs = get_option('pollmaster_cron_logs', array());
        $logs[] = $log_entry;
        
        // Keep only last 100 log entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('pollmaster_cron_logs', $logs);
    }
    
    /**
     * Get cron logs for admin
     */
    public function get_cron_logs($limit = 50) {
        $logs = get_option('pollmaster_cron_logs', array());
        return array_slice(array_reverse($logs), 0, $limit);
    }
    
    /**
     * Manual trigger for cron jobs (admin only)
     */
    public function manual_trigger($job_name) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        switch ($job_name) {
            case 'weekly_poll_check':
                $this->check_weekly_polls();
                break;
                
            case 'contest_end_check':
                $this->check_contest_endings();
                break;
                
            case 'cleanup_old_data':
                $this->cleanup_old_data();
                break;
                
            case 'send_notifications':
                $this->send_notifications();
                break;
                
            default:
                return false;
        }
        
        return true;
    }
}