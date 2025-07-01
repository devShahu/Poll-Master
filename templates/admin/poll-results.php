<?php
/**
 * Poll Results Admin Template
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get poll ID
$poll_id = isset($_GET['poll_id']) ? intval($_GET['poll_id']) : 0;
if (!$poll_id) {
    wp_die('Poll ID is required.');
}

$database = new PollMaster_Database();

// Get poll data
$poll = $database->get_poll($poll_id);
if (!$poll) {
    wp_die('Poll not found.');
}

// Get poll results
$results = $database->get_poll_results($poll_id);
$total_votes = array_sum(array_column($results, 'votes'));

// Get poll options
$options = json_decode($poll['options'], true) ?: [];

// Get voting history
$votes_history = $database->get_poll_votes_history($poll_id);

// Get contest winner if applicable
$contest_winner = null;
if ($poll['is_contest']) {
    $contest_winner = $database->get_contest_winner($poll_id);
}

// Get social shares
$shares = $database->get_poll_shares($poll_id);
$total_shares = array_sum(array_column($shares, 'count'));

// Calculate statistics
$stats = [
    'total_votes' => $total_votes,
    'total_shares' => $total_shares,
    'engagement_rate' => $total_votes > 0 ? round(($total_shares / $total_votes) * 100, 2) : 0,
    'votes_per_day' => 0,
    'peak_voting_time' => null
];

// Calculate votes per day
if (!empty($votes_history)) {
    $first_vote = strtotime($votes_history[0]['voted_at']);
    $last_vote = strtotime(end($votes_history)['voted_at']);
    $days = max(1, ceil(($last_vote - $first_vote) / (24 * 60 * 60)));
    $stats['votes_per_day'] = round($total_votes / $days, 2);
}

// Find peak voting time
if (!empty($votes_history)) {
    $hourly_votes = [];
    foreach ($votes_history as $vote) {
        $hour = date('H', strtotime($vote['voted_at']));
        $hourly_votes[$hour] = ($hourly_votes[$hour] ?? 0) + 1;
    }
    arsort($hourly_votes);
    $peak_hour = key($hourly_votes);
    $stats['peak_voting_time'] = date('g A', strtotime($peak_hour . ':00'));
}

?>

<div class="wrap pollmaster-poll-results">
    <div class="page-header">
        <h1 class="page-title">
            <span class="title-icon">📊</span>
            Poll Results: <?php echo esc_html($poll['title']); ?>
        </h1>
        
        <div class="page-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="button button-secondary">
                <span class="button-icon">⬅️</span>
                Back to Polls
            </a>
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-edit-poll&poll_id=' . $poll_id)); ?>" class="button button-secondary">
                <span class="button-icon">✏️</span>
                Edit Poll
            </a>
            
            <button class="button button-secondary" data-action="export-results">
                <span class="button-icon">📤</span>
                Export Results
            </button>
            
            <?php if ($poll['is_contest'] && $poll['status'] === 'ended' && !$contest_winner): ?>
                <button class="button button-primary" data-action="announce-winner" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                    <span class="button-icon">🏆</span>
                    Announce Winner
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Poll Info -->
    <div class="poll-info-section">
        <div class="poll-info-card">
            <div class="poll-meta">
                <div class="meta-item">
                    <span class="meta-label">Status:</span>
                    <span class="status-badge status-<?php echo esc_attr($poll['status']); ?>">
                        <?php echo esc_html(ucfirst($poll['status'])); ?>
                    </span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label">Created:</span>
                    <span class="meta-value"><?php echo esc_html(date('M j, Y g:i A', strtotime($poll['created_at']))); ?></span>
                </div>
                
                <?php if ($poll['end_date']): ?>
                    <div class="meta-item">
                        <span class="meta-label">End Date:</span>
                        <span class="meta-value"><?php echo esc_html(date('M j, Y g:i A', strtotime($poll['end_date']))); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="meta-item">
                    <span class="meta-label">Type:</span>
                    <div class="poll-badges">
                        <?php if ($poll['is_contest']): ?>
                            <span class="poll-badge contest">
                                <span class="badge-icon">🏆</span>
                                Contest
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($poll['is_weekly']): ?>
                            <span class="poll-badge weekly">
                                <span class="badge-icon">📅</span>
                                Weekly
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!$poll['is_contest'] && !$poll['is_weekly']): ?>
                            <span class="poll-badge regular">
                                <span class="badge-icon">📋</span>
                                Regular
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($poll['description'])): ?>
                <div class="poll-description">
                    <h3>Description</h3>
                    <p><?php echo esc_html($poll['description']); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Overview -->
    <div class="stats-overview">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🗳️</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo esc_html($stats['total_votes']); ?></div>
                    <div class="stat-label">Total Votes</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📤</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo esc_html($stats['total_shares']); ?></div>
                    <div class="stat-label">Social Shares</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo esc_html($stats['engagement_rate']); ?>%</div>
                    <div class="stat-label">Engagement Rate</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo esc_html($stats['votes_per_day']); ?></div>
                    <div class="stat-label">Votes per Day</div>
                </div>
            </div>
            
            <?php if ($stats['peak_voting_time']): ?>
                <div class="stat-card">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['peak_voting_time']); ?></div>
                        <div class="stat-label">Peak Voting Time</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contest Winner -->
    <?php if ($poll['is_contest'] && $contest_winner): ?>
        <div class="contest-winner-section">
            <div class="winner-card">
                <div class="winner-header">
                    <h2 class="winner-title">
                        <span class="winner-icon">🏆</span>
                        Contest Winner
                    </h2>
                </div>
                
                <div class="winner-content">
                    <div class="winner-info">
                        <div class="winner-name"><?php echo esc_html($contest_winner['winner_name'] ?: 'Anonymous'); ?></div>
                        <div class="winner-email"><?php echo esc_html($contest_winner['winner_email']); ?></div>
                        <div class="winner-option">Voted for: <strong><?php echo esc_html($contest_winner['winning_option']); ?></strong></div>
                        <div class="winner-date">Announced: <?php echo esc_html(date('M j, Y g:i A', strtotime($contest_winner['announced_at']))); ?></div>
                    </div>
                    
                    <div class="prize-info">
                        <h4>Prize</h4>
                        <p><?php echo esc_html($poll['contest_prize']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Results Visualization -->
    <div class="results-section">
        <div class="results-header">
            <h2 class="results-title">
                <span class="results-icon">📊</span>
                Voting Results
            </h2>
            
            <div class="results-controls">
                <button class="view-toggle active" data-view="chart">
                    <span class="button-icon">📊</span>
                    Chart View
                </button>
                <button class="view-toggle" data-view="table">
                    <span class="button-icon">📋</span>
                    Table View
                </button>
            </div>
        </div>
        
        <!-- Chart View -->
        <div class="results-content chart-view">
            <div class="chart-container">
                <canvas id="results-chart" width="400" height="200"></canvas>
            </div>
            
            <div class="results-list">
                <?php foreach ($options as $index => $option): ?>
                    <?php
                    $option_votes = 0;
                    foreach ($results as $result) {
                        if ($result['option_index'] == $index) {
                            $option_votes = $result['votes'];
                            break;
                        }
                    }
                    $percentage = $total_votes > 0 ? round(($option_votes / $total_votes) * 100, 1) : 0;
                    ?>
                    
                    <div class="result-item">
                        <div class="result-header">
                            <div class="option-info">
                                <span class="option-number"><?php echo $index + 1; ?></span>
                                <span class="option-text"><?php echo esc_html($option); ?></span>
                            </div>
                            <div class="result-stats">
                                <span class="vote-count"><?php echo esc_html($option_votes); ?> votes</span>
                                <span class="percentage"><?php echo esc_html($percentage); ?>%</span>
                            </div>
                        </div>
                        
                        <div class="result-bar">
                            <div class="bar-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Table View -->
        <div class="results-content table-view" style="display: none;">
            <div class="results-table-container">
                <table class="wp-list-table widefat fixed striped results-table">
                    <thead>
                        <tr>
                            <th class="column-option">Option</th>
                            <th class="column-votes">Votes</th>
                            <th class="column-percentage">Percentage</th>
                            <th class="column-bar">Visual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($options as $index => $option): ?>
                            <?php
                            $option_votes = 0;
                            foreach ($results as $result) {
                                if ($result['option_index'] == $index) {
                                    $option_votes = $result['votes'];
                                    break;
                                }
                            }
                            $percentage = $total_votes > 0 ? round(($option_votes / $total_votes) * 100, 1) : 0;
                            ?>
                            
                            <tr>
                                <td class="column-option">
                                    <div class="option-cell">
                                        <span class="option-number"><?php echo $index + 1; ?></span>
                                        <span class="option-text"><?php echo esc_html($option); ?></span>
                                    </div>
                                </td>
                                <td class="column-votes">
                                    <strong><?php echo esc_html($option_votes); ?></strong>
                                </td>
                                <td class="column-percentage">
                                    <strong><?php echo esc_html($percentage); ?>%</strong>
                                </td>
                                <td class="column-bar">
                                    <div class="table-bar">
                                        <div class="table-bar-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Voting History -->
    <?php if (!empty($votes_history)): ?>
        <div class="voting-history-section">
            <div class="history-header">
                <h2 class="history-title">
                    <span class="history-icon">📈</span>
                    Voting Timeline
                </h2>
                
                <div class="history-controls">
                    <select class="history-filter" data-filter="period">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                    
                    <button class="button button-secondary" data-action="export-history">
                        <span class="button-icon">📤</span>
                        Export History
                    </button>
                </div>
            </div>
            
            <div class="timeline-chart-container">
                <canvas id="timeline-chart" width="800" height="300"></canvas>
            </div>
            
            <div class="recent-votes">
                <h3>Recent Votes</h3>
                <div class="votes-list">
                    <?php 
                    $recent_votes = array_slice(array_reverse($votes_history), 0, 10);
                    foreach ($recent_votes as $vote): 
                    ?>
                        <div class="vote-item">
                            <div class="vote-info">
                                <span class="voter-name"><?php echo esc_html($vote['voter_name'] ?: 'Anonymous'); ?></span>
                                <span class="vote-option">voted for "<?php echo esc_html($options[$vote['option_index']] ?? 'Unknown'); ?>"</span>
                            </div>
                            <div class="vote-time">
                                <?php echo esc_html(human_time_diff(strtotime($vote['voted_at']), current_time('timestamp')) . ' ago'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Social Shares -->
    <?php if (!empty($shares)): ?>
        <div class="social-shares-section">
            <div class="shares-header">
                <h2 class="shares-title">
                    <span class="shares-icon">📤</span>
                    Social Sharing
                </h2>
            </div>
            
            <div class="shares-grid">
                <?php foreach ($shares as $share): ?>
                    <div class="share-card">
                        <div class="share-platform">
                            <span class="platform-icon">
                                <?php
                                switch ($share['platform']) {
                                    case 'facebook': echo '📘'; break;
                                    case 'twitter': echo '🐦'; break;
                                    case 'whatsapp': echo '💬'; break;
                                    case 'linkedin': echo '💼'; break;
                                    default: echo '📤'; break;
                                }
                                ?>
                            </span>
                            <span class="platform-name"><?php echo esc_html(ucfirst($share['platform'])); ?></span>
                        </div>
                        <div class="share-count"><?php echo esc_html($share['count']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Poll Results Styles */
.pollmaster-poll-results {
    padding: 20px 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.page-title {
    font-size: 1.8rem;
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-actions {
    display: flex;
    gap: 10px;
}

.button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

/* Poll Info Section */
.poll-info-section {
    margin-bottom: 30px;
}

.poll-info-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.poll-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.meta-label {
    font-weight: 600;
    color: #7f8c8d;
    min-width: 80px;
}

.meta-value {
    color: #2c3e50;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
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

.poll-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.poll-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.poll-badge.contest {
    background: #fff3cd;
    color: #856404;
}

.poll-badge.weekly {
    background: #d1ecf1;
    color: #0c5460;
}

.poll-badge.regular {
    background: #e2e3e5;
    color: #383d41;
}

.poll-description {
    border-top: 1px solid #e9ecef;
    padding-top: 20px;
}

.poll-description h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.poll-description p {
    margin: 0;
    color: #7f8c8d;
    line-height: 1.6;
}

/* Statistics Overview */
.stats-overview {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Contest Winner Section */
.contest-winner-section {
    margin-bottom: 30px;
}

.winner-card {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(243, 156, 18, 0.3);
}

.winner-header {
    margin-bottom: 20px;
}

.winner-title {
    font-size: 1.5rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.winner-content {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 30px;
    align-items: start;
}

.winner-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.winner-name {
    font-size: 1.3rem;
    font-weight: bold;
}

.winner-email {
    opacity: 0.9;
}

.winner-option,
.winner-date {
    opacity: 0.8;
    font-size: 0.9rem;
}

.prize-info {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    min-width: 200px;
}

.prize-info h4 {
    margin: 0 0 10px 0;
    font-size: 1.1rem;
}

.prize-info p {
    margin: 0;
    font-size: 1rem;
}

/* Results Section */
.results-section {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.results-header {
    padding: 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.results-title {
    font-size: 1.4rem;
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.results-controls {
    display: flex;
    gap: 5px;
}

.view-toggle {
    padding: 8px 16px;
    border: 1px solid #e9ecef;
    background: white;
    color: #7f8c8d;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
}

.view-toggle:hover {
    border-color: #3498db;
    color: #3498db;
}

.view-toggle.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.results-content {
    padding: 25px;
}

/* Chart View */
.chart-container {
    margin-bottom: 30px;
    text-align: center;
}

.results-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.result-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
}

.result-header {
    padding: 15px 20px;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.option-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.option-number {
    width: 28px;
    height: 28px;
    background: #3498db;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 600;
}

.option-text {
    font-weight: 600;
    color: #2c3e50;
}

.result-stats {
    display: flex;
    align-items: center;
    gap: 15px;
}

.vote-count {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.percentage {
    font-size: 1.2rem;
    font-weight: bold;
    color: #2c3e50;
}

.result-bar {
    height: 8px;
    background: #e9ecef;
    position: relative;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2980b9);
    transition: width 0.8s ease;
}

/* Table View */
.results-table-container {
    overflow-x: auto;
}

.results-table {
    margin: 0;
}

.results-table th,
.results-table td {
    padding: 15px;
    text-align: left;
}

.results-table th {
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
    color: #2c3e50;
}

.option-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.table-bar {
    width: 100px;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.table-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2980b9);
    transition: width 0.8s ease;
}

/* Voting History Section */
.voting-history-section {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.history-header {
    padding: 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.history-title {
    font-size: 1.4rem;
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.history-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.history-filter {
    padding: 6px 12px;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    background: white;
}

.timeline-chart-container {
    padding: 25px;
    text-align: center;
}

.recent-votes {
    padding: 25px;
    border-top: 1px solid #e9ecef;
}

.recent-votes h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.votes-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.vote-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.vote-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.voter-name {
    font-weight: 600;
    color: #2c3e50;
}

.vote-option {
    color: #7f8c8d;
}

.vote-time {
    color: #7f8c8d;
    font-size: 0.9rem;
}

/* Social Shares Section */
.social-shares-section {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.shares-header {
    padding: 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.shares-title {
    font-size: 1.4rem;
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.shares-grid {
    padding: 25px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.share-card {
    text-align: center;
    padding: 20px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.share-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.share-platform {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 10px;
}

.platform-icon {
    font-size: 1.5rem;
}

.platform-name {
    font-weight: 600;
    color: #2c3e50;
}

.share-count {
    font-size: 1.5rem;
    font-weight: bold;
    color: #3498db;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .page-actions {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .poll-meta {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .results-header,
    .history-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .result-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .winner-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .vote-item {
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1.4rem;
        flex-direction: column;
        gap: 8px;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .shares-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Poll Results JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializePollResults();
    
    function initializePollResults() {
        bindEventHandlers();
        initializeCharts();
        animateResults();
    }
    
    function bindEventHandlers() {
        // View toggle buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.view-toggle')) {
                e.preventDefault();
                const button = e.target.closest('.view-toggle');
                const view = button.dataset.view;
                toggleResultsView(view);
            }
            
            // Export results
            if (e.target.closest('[data-action="export-results"]')) {
                e.preventDefault();
                exportResults();
            }
            
            // Export history
            if (e.target.closest('[data-action="export-history"]')) {
                e.preventDefault();
                exportHistory();
            }
            
            // Announce winner
            if (e.target.closest('[data-action="announce-winner"]')) {
                e.preventDefault();
                const pollId = e.target.closest('[data-action="announce-winner"]').dataset.pollId;
                announceWinner(pollId);
            }
        });
        
        // History filter
        document.addEventListener('change', function(e) {
            if (e.target.matches('[data-filter="period"]')) {
                filterHistory(e.target.value);
            }
        });
    }
    
    function toggleResultsView(view) {
        // Update active button
        document.querySelectorAll('.view-toggle').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${view}"]`).classList.add('active');
        
        // Show/hide content
        document.querySelectorAll('.results-content').forEach(content => {
            content.style.display = 'none';
        });
        document.querySelector(`.${view}-view`).style.display = 'block';
    }
    
    function initializeCharts() {
        // Initialize results chart
        const resultsCanvas = document.getElementById('results-chart');
        if (resultsCanvas && typeof Chart !== 'undefined') {
            const ctx = resultsCanvas.getContext('2d');
            
            // Get data from PHP
            const options = <?php echo json_encode($options); ?>;
            const results = <?php echo json_encode($results); ?>;
            
            const chartData = options.map((option, index) => {
                const result = results.find(r => r.option_index == index);
                return result ? result.votes : 0;
            });
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: options,
                    datasets: [{
                        data: chartData,
                        backgroundColor: [
                            '#3498db',
                            '#e74c3c',
                            '#2ecc71',
                            '#f39c12',
                            '#9b59b6',
                            '#1abc9c',
                            '#34495e',
                            '#e67e22',
                            '#95a5a6',
                            '#f1c40f'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ${context.raw} votes (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize timeline chart
        const timelineCanvas = document.getElementById('timeline-chart');
        if (timelineCanvas && typeof Chart !== 'undefined') {
            const ctx = timelineCanvas.getContext('2d');
            
            // Process voting history data
            const votesHistory = <?php echo json_encode($votes_history); ?>;
            const timelineData = processTimelineData(votesHistory);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: timelineData.labels,
                    datasets: [{
                        label: 'Cumulative Votes',
                        data: timelineData.data,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }
    
    function processTimelineData(votesHistory) {
        if (!votesHistory.length) {
            return { labels: [], data: [] };
        }
        
        const dailyVotes = {};
        let cumulativeVotes = 0;
        
        votesHistory.forEach(vote => {
            const date = vote.voted_at.split(' ')[0]; // Get date part
            if (!dailyVotes[date]) {
                dailyVotes[date] = 0;
            }
            dailyVotes[date]++;
        });
        
        const labels = [];
        const data = [];
        
        Object.keys(dailyVotes).sort().forEach(date => {
            cumulativeVotes += dailyVotes[date];
            labels.push(new Date(date).toLocaleDateString());
            data.push(cumulativeVotes);
        });
        
        return { labels, data };
    }
    
    function animateResults() {
        // Animate progress bars
        const bars = document.querySelectorAll('.bar-fill, .table-bar-fill');
        bars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
        
        // Animate stat numbers
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(stat => {
            const finalValue = parseInt(stat.textContent);
            if (!isNaN(finalValue)) {
                animateNumber(stat, 0, finalValue, 1000);
            }
        });
    }
    
    function animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(start + (end - start) * progress);
            element.textContent = current;
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        requestAnimationFrame(update);
    }
    
    function exportResults() {
        const pollId = <?php echo $poll_id; ?>;
        window.location.href = `admin.php?page=pollmaster-export&action=export-results&poll_id=${pollId}`;
    }
    
    function exportHistory() {
        const pollId = <?php echo $poll_id; ?>;
        const period = document.querySelector('[data-filter="period"]').value;
        window.location.href = `admin.php?page=pollmaster-export&action=export-history&poll_id=${pollId}&period=${period}`;
    }
    
    function announceWinner(pollId) {
        if (confirm('Are you sure you want to announce the winner for this contest? This action cannot be undone.')) {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'pollmaster_announce_winner',
                    poll_id: pollId,
                    nonce: pollmaster_admin.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Winner announced successfully!');
                    location.reload();
                } else {
                    alert('Error announcing winner: ' + data.data);
                }
            })
            .catch(error => {
                alert('Error announcing winner');
            });
        }
    }
    
    function filterHistory(period) {
        // This would filter the timeline chart based on the selected period
        console.log('Filtering history by:', period);
        // Implementation would depend on the specific requirements
    }
});
</script>