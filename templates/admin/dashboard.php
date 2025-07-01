<?php
/**
 * Admin Dashboard Template
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard data
$database = new PollMaster_Database();
$stats = $database->get_dashboard_stats();
$recent_polls = $database->get_polls(['limit' => 5, 'orderby' => 'created_at', 'order' => 'DESC']);
$recent_votes = $database->get_recent_votes(10);

?>

<div class="wrap pollmaster-admin-dashboard">
    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <span class="title-icon">📊</span>
            PollMaster Dashboard
        </h1>
        <p class="dashboard-subtitle">Manage your polls, view statistics, and monitor engagement</p>
    </div>

    <!-- Quick Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card total-polls">
            <div class="stat-icon">
                <span class="icon">📋</span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo esc_html($stats['total_polls'] ?? 0); ?></div>
                <div class="stat-label">Total Polls</div>
                <div class="stat-change positive">
                    <span class="change-icon">↗️</span>
                    <span class="change-text">+<?php echo esc_html($stats['polls_this_month'] ?? 0); ?> this month</span>
                </div>
            </div>
        </div>

        <div class="stat-card total-votes">
            <div class="stat-icon">
                <span class="icon">🗳️</span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo esc_html($stats['total_votes'] ?? 0); ?></div>
                <div class="stat-label">Total Votes</div>
                <div class="stat-change positive">
                    <span class="change-icon">↗️</span>
                    <span class="change-text">+<?php echo esc_html($stats['votes_today'] ?? 0); ?> today</span>
                </div>
            </div>
        </div>

        <div class="stat-card active-polls">
            <div class="stat-icon">
                <span class="icon">🔴</span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo esc_html($stats['active_polls'] ?? 0); ?></div>
                <div class="stat-label">Active Polls</div>
                <div class="stat-change neutral">
                    <span class="change-icon">📈</span>
                    <span class="change-text"><?php echo esc_html($stats['engagement_rate'] ?? 0); ?>% engagement</span>
                </div>
            </div>
        </div>

        <div class="stat-card contests">
            <div class="stat-icon">
                <span class="icon">🏆</span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo esc_html($stats['total_contests'] ?? 0); ?></div>
                <div class="stat-label">Contests</div>
                <div class="stat-change positive">
                    <span class="change-icon">🎯</span>
                    <span class="change-text"><?php echo esc_html($stats['active_contests'] ?? 0); ?> active</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2 class="section-title">
            <span class="title-icon">⚡</span>
            Quick Actions
        </h2>
        
        <div class="actions-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll')); ?>" class="action-card create-poll">
                <div class="action-icon">
                    <span class="icon">➕</span>
                </div>
                <div class="action-content">
                    <h3 class="action-title">Create New Poll</h3>
                    <p class="action-description">Start a new poll or contest</p>
                </div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="action-card manage-polls">
                <div class="action-icon">
                    <span class="icon">📝</span>
                </div>
                <div class="action-content">
                    <h3 class="action-title">Manage Polls</h3>
                    <p class="action-description">Edit, delete, or archive polls</p>
                </div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-weekly-poll')); ?>" class="action-card weekly-poll">
                <div class="action-icon">
                    <span class="icon">📅</span>
                </div>
                <div class="action-content">
                    <h3 class="action-title">Weekly Poll</h3>
                    <p class="action-description">Set up weekly featured poll</p>
                </div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-contests')); ?>" class="action-card contests">
                <div class="action-icon">
                    <span class="icon">🎯</span>
                </div>
                <div class="action-content">
                    <h3 class="action-title">Contests</h3>
                    <p class="action-description">Manage contest polls and winners</p>
                </div>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-settings')); ?>" class="action-card settings">
                <div class="action-icon">
                    <span class="icon">⚙️</span>
                </div>
                <div class="action-content">
                    <h3 class="action-title">Settings</h3>
                    <p class="action-description">Configure plugin options</p>
                </div>
            </a>

            <button class="action-card export-data" data-action="export-polls">
                <div class="action-icon">
                    <span class="icon">📤</span>
                </div>
                <div class="action-content">
                    <h3 class="action-title">Export Data</h3>
                    <p class="action-description">Download poll data and results</p>
                </div>
            </button>
        </div>
    </div>

    <!-- Dashboard Content Grid -->
    <div class="dashboard-content">
        <!-- Recent Polls -->
        <div class="dashboard-section recent-polls">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-icon">📋</span>
                    Recent Polls
                </h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="section-link">
                    View All
                    <span class="link-icon">→</span>
                </a>
            </div>
            
            <div class="section-content">
                <?php if (!empty($recent_polls)): ?>
                    <div class="polls-list">
                        <?php foreach ($recent_polls as $poll): ?>
                            <?php
                            $poll_id = $poll['id'];
                            $title = $poll['title'];
                            $status = $poll['status'];
                            $total_votes = $poll['total_votes'] ?? 0;
                            $created_date = date('M j, Y', strtotime($poll['created_at']));
                            $is_contest = !empty($poll['is_contest']);
                            $is_weekly = !empty($poll['is_weekly']);
                            ?>
                            
                            <div class="poll-item">
                                <div class="poll-info">
                                    <div class="poll-title">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-edit-poll&poll_id=' . $poll_id)); ?>" class="poll-link">
                                            <?php echo esc_html($title); ?>
                                        </a>
                                    </div>
                                    
                                    <div class="poll-meta">
                                        <span class="poll-date">
                                            <span class="meta-icon">📅</span>
                                            <?php echo esc_html($created_date); ?>
                                        </span>
                                        
                                        <span class="poll-votes">
                                            <span class="meta-icon">🗳️</span>
                                            <?php echo esc_html($total_votes); ?> votes
                                        </span>
                                        
                                        <?php if ($is_contest): ?>
                                            <span class="poll-badge contest">
                                                <span class="badge-icon">🏆</span>
                                                Contest
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($is_weekly): ?>
                                            <span class="poll-badge weekly">
                                                <span class="badge-icon">📅</span>
                                                Weekly
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="poll-status">
                                    <span class="status-badge status-<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html(ucfirst($status)); ?>
                                    </span>
                                </div>
                                
                                <div class="poll-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-edit-poll&poll_id=' . $poll_id)); ?>" class="action-button edit" title="Edit Poll">
                                        <span class="button-icon">✏️</span>
                                    </a>
                                    
                                    <button class="action-button view-results" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="View Results">
                                        <span class="button-icon">📊</span>
                                    </button>
                                    
                                    <button class="action-button delete" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Delete Poll">
                                        <span class="button-icon">🗑️</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <h3 class="empty-title">No Polls Yet</h3>
                        <p class="empty-message">Create your first poll to get started!</p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll')); ?>" class="empty-action">
                            Create Poll
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-section recent-activity">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-icon">🔔</span>
                    Recent Activity
                </h2>
            </div>
            
            <div class="section-content">
                <?php if (!empty($recent_votes)): ?>
                    <div class="activity-list">
                        <?php foreach ($recent_votes as $vote): ?>
                            <?php
                            $poll_title = $vote['poll_title'] ?? 'Unknown Poll';
                            $option_text = $vote['option_text'] ?? 'Unknown Option';
                            $vote_time = human_time_diff(strtotime($vote['voted_at'])) . ' ago';
                            $voter_name = $vote['voter_name'] ?? 'Anonymous';
                            ?>
                            
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <span class="icon">🗳️</span>
                                </div>
                                
                                <div class="activity-content">
                                    <div class="activity-text">
                                        <strong><?php echo esc_html($voter_name); ?></strong> voted for 
                                        <em>"<?php echo esc_html($option_text); ?>"</em> in 
                                        <strong><?php echo esc_html($poll_title); ?></strong>
                                    </div>
                                    
                                    <div class="activity-time">
                                        <?php echo esc_html($vote_time); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔔</div>
                        <h3 class="empty-title">No Recent Activity</h3>
                        <p class="empty-message">Activity will appear here when users start voting.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Performance Chart -->
        <div class="dashboard-section performance-chart">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-icon">📈</span>
                    Performance Overview
                </h2>
                
                <div class="chart-controls">
                    <select class="chart-period" data-chart="performance">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
            </div>
            
            <div class="section-content">
                <div class="chart-container">
                    <canvas id="performance-chart" width="400" height="200"></canvas>
                </div>
                
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color polls"></span>
                        <span class="legend-label">Polls Created</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color votes"></span>
                        <span class="legend-label">Votes Cast</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color engagement"></span>
                        <span class="legend-label">Engagement Rate</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="dashboard-section system-status">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-icon">⚙️</span>
                    System Status
                </h2>
            </div>
            
            <div class="section-content">
                <div class="status-grid">
                    <div class="status-item">
                        <div class="status-icon good">
                            <span class="icon">✅</span>
                        </div>
                        <div class="status-content">
                            <div class="status-label">Database</div>
                            <div class="status-value">Connected</div>
                        </div>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-icon good">
                            <span class="icon">✅</span>
                        </div>
                        <div class="status-content">
                            <div class="status-label">Cron Jobs</div>
                            <div class="status-value">Running</div>
                        </div>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-icon good">
                            <span class="icon">✅</span>
                        </div>
                        <div class="status-content">
                            <div class="status-label">File Uploads</div>
                            <div class="status-value">Enabled</div>
                        </div>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-icon warning">
                            <span class="icon">⚠️</span>
                        </div>
                        <div class="status-content">
                            <div class="status-label">Cache</div>
                            <div class="status-value">Recommended</div>
                        </div>
                    </div>
                </div>
                
                <div class="system-info">
                    <h3 class="info-title">Plugin Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Version:</span>
                            <span class="info-value">1.0.0</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Database Version:</span>
                            <span class="info-value">1.0.0</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Update:</span>
                            <span class="info-value"><?php echo esc_html(date('M j, Y')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Help & Support -->
    <div class="dashboard-footer">
        <div class="help-section">
            <h3 class="help-title">
                <span class="title-icon">❓</span>
                Need Help?
            </h3>
            
            <div class="help-links">
                <a href="#" class="help-link" data-action="show-documentation">
                    <span class="link-icon">📖</span>
                    Documentation
                </a>
                
                <a href="#" class="help-link" data-action="show-tutorials">
                    <span class="link-icon">🎥</span>
                    Video Tutorials
                </a>
                
                <a href="#" class="help-link" data-action="contact-support">
                    <span class="link-icon">💬</span>
                    Contact Support
                </a>
                
                <a href="#" class="help-link" data-action="feature-request">
                    <span class="link-icon">💡</span>
                    Feature Request
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard Styles */
.pollmaster-admin-dashboard {
    padding: 20px 0;
}

.dashboard-header {
    margin-bottom: 30px;
    text-align: center;
}

.dashboard-title {
    font-size: 2.5rem;
    margin: 0 0 10px 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.dashboard-subtitle {
    font-size: 1.1rem;
    color: #7f8c8d;
    margin: 0;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2.2rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 1rem;
    color: #7f8c8d;
    margin-bottom: 8px;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
}

.stat-change.positive {
    color: #27ae60;
}

.stat-change.negative {
    color: #e74c3c;
}

.stat-change.neutral {
    color: #f39c12;
}

/* Quick Actions */
.quick-actions {
    margin-bottom: 40px;
}

.section-title {
    font-size: 1.5rem;
    color: #2c3e50;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 15px;
    border: none;
    cursor: pointer;
    text-align: left;
    width: 100%;
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    color: inherit;
}

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.action-title {
    font-size: 1.1rem;
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.action-description {
    font-size: 0.9rem;
    color: #7f8c8d;
    margin: 0;
}

/* Dashboard Content */
.dashboard-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.dashboard-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.section-header {
    padding: 20px 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-link {
    color: #3498db;
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: color 0.3s ease;
}

.section-link:hover {
    color: #2980b9;
}

.section-content {
    padding: 25px;
}

/* Recent Polls */
.polls-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.poll-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: background 0.3s ease;
}

.poll-item:hover {
    background: #e9ecef;
}

.poll-info {
    flex: 1;
}

.poll-title {
    margin-bottom: 8px;
}

.poll-link {
    color: #2c3e50;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.poll-link:hover {
    color: #3498db;
}

.poll-meta {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
    font-size: 0.85rem;
    color: #7f8c8d;
}

.poll-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 3px;
}

.poll-badge.contest {
    background: #fff3cd;
    color: #856404;
}

.poll-badge.weekly {
    background: #d1ecf1;
    color: #0c5460;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.status-active {
    background: #d4edda;
    color: #155724;
}

.status-badge.status-ended {
    background: #f8d7da;
    color: #721c24;
}

.status-badge.status-archived {
    background: #e2e3e5;
    color: #383d41;
}

.poll-actions {
    display: flex;
    gap: 8px;
}

.action-button {
    width: 35px;
    height: 35px;
    border: none;
    border-radius: 50%;
    background: #f8f9fa;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.action-button:hover {
    background: #e9ecef;
    transform: scale(1.1);
}

.action-button.edit:hover {
    background: #3498db;
    color: white;
}

.action-button.delete:hover {
    background: #e74c3c;
    color: white;
}

/* Activity List */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #3498db;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-text {
    margin-bottom: 5px;
    line-height: 1.4;
}

.activity-time {
    font-size: 0.85rem;
    color: #7f8c8d;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-title {
    font-size: 1.3rem;
    color: #2c3e50;
    margin: 0 0 10px 0;
}

.empty-message {
    color: #7f8c8d;
    margin: 0 0 20px 0;
}

.empty-action {
    display: inline-block;
    padding: 10px 20px;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: background 0.3s ease;
}

.empty-action:hover {
    background: #2980b9;
    color: white;
}

/* Chart Section */
.chart-controls {
    display: flex;
    gap: 10px;
}

.chart-period {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.chart-container {
    margin-bottom: 20px;
    height: 200px;
}

.chart-legend {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.legend-color.polls {
    background: #3498db;
}

.legend-color.votes {
    background: #2ecc71;
}

.legend-color.engagement {
    background: #f39c12;
}

/* System Status */
.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.status-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.status-icon.good {
    background: #d4edda;
    color: #155724;
}

.status-icon.warning {
    background: #fff3cd;
    color: #856404;
}

.status-icon.error {
    background: #f8d7da;
    color: #721c24;
}

.status-label {
    font-size: 0.9rem;
    color: #7f8c8d;
    margin-bottom: 2px;
}

.status-value {
    font-weight: 600;
    color: #2c3e50;
}

.system-info {
    border-top: 1px solid #e9ecef;
    padding-top: 20px;
}

.info-title {
    font-size: 1.1rem;
    color: #2c3e50;
    margin: 0 0 15px 0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.info-label {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.info-value {
    color: #2c3e50;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Dashboard Footer */
.dashboard-footer {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.help-title {
    font-size: 1.3rem;
    color: #2c3e50;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.help-links {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.help-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    background: #f8f9fa;
    color: #2c3e50;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.help-link:hover {
    background: #3498db;
    color: white;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .dashboard-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .poll-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .help-links {
        flex-direction: column;
    }
    
    .dashboard-title {
        font-size: 2rem;
        flex-direction: column;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .poll-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .poll-actions {
        align-self: flex-end;
    }
    
    .status-grid {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard
    initializeDashboard();
    
    function initializeDashboard() {
        // Initialize chart
        initializePerformanceChart();
        
        // Bind event handlers
        bindEventHandlers();
        
        // Auto-refresh data every 5 minutes
        setInterval(refreshDashboardData, 300000);
    }
    
    function bindEventHandlers() {
        // Chart period change
        document.addEventListener('change', function(e) {
            if (e.target.matches('.chart-period')) {
                updateChart(e.target.dataset.chart, e.target.value);
            }
        });
        
        // Action buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.view-results')) {
                const pollId = e.target.closest('.view-results').dataset.pollId;
                showPollResults(pollId);
            }
            
            if (e.target.closest('.delete')) {
                const pollId = e.target.closest('.delete').dataset.pollId;
                deletePoll(pollId);
            }
            
            if (e.target.closest('[data-action="export-polls"]')) {
                exportPollData();
            }
        });
    }
    
    function initializePerformanceChart() {
        const canvas = document.getElementById('performance-chart');
        if (!canvas) return;
        
        // Initialize chart with Chart.js or similar library
        // This is a placeholder for the actual chart implementation
        console.log('Performance chart initialized');
    }
    
    function updateChart(chartType, period) {
        // Update chart data based on period
        console.log('Updating chart:', chartType, 'Period:', period);
    }
    
    function showPollResults(pollId) {
        // Show poll results in modal or redirect
        window.location.href = `admin.php?page=pollmaster-poll-results&poll_id=${pollId}`;
    }
    
    function deletePoll(pollId) {
        if (confirm('Are you sure you want to delete this poll? This action cannot be undone.')) {
            // Send AJAX request to delete poll
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'pollmaster_delete_poll',
                    poll_id: pollId,
                    nonce: pollmaster_admin.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting poll: ' + data.data);
                }
            })
            .catch(error => {
                alert('Error deleting poll');
            });
        }
    }
    
    function exportPollData() {
        // Export poll data
        window.location.href = 'admin.php?page=pollmaster-export&action=export-polls';
    }
    
    function refreshDashboardData() {
        // Refresh dashboard statistics
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'pollmaster_refresh_dashboard',
                nonce: pollmaster_admin.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data.data);
            }
        })
        .catch(error => {
            console.log('Error refreshing dashboard data');
        });
    }
    
    function updateDashboardStats(stats) {
        // Update dashboard statistics
        Object.keys(stats).forEach(key => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                element.textContent = stats[key];
            }
        });
    }
});
</script>