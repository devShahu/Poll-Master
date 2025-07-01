<?php
/**
 * Past Polls Page Template
 * 
 * This template can be overridden by copying it to yourtheme/pollmaster/past-polls-page.php
 * 
 * @package PollMaster
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get page data
$polls = $args['polls'] ?? [];
$pagination = $args['pagination'] ?? [];
$filters = $args['filters'] ?? [];
$current_filter = $args['current_filter'] ?? 'all';
$search_query = $args['search_query'] ?? '';
$total_polls = $args['total_polls'] ?? 0;

get_header();
?>

<div class="pollmaster-past-polls-page">
    <div class="container">
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">
                    <span class="title-icon">📊</span>
                    Past Polls
                </h1>
                <p class="page-description">
                    Explore our archive of completed polls and see how the community voted on various topics.
                </p>
            </div>
            
            <div class="header-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($total_polls); ?></span>
                    <span class="stat-label">Total Polls</span>
                </div>
                
                <?php if (!empty($filters['contest_count'])): ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html($filters['contest_count']); ?></span>
                        <span class="stat-label">Contests</span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($filters['weekly_count'])): ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html($filters['weekly_count']); ?></span>
                        <span class="stat-label">Weekly Polls</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filters and Search -->
        <div class="polls-filters">
            <form class="filters-form" method="get">
                <div class="filter-group">
                    <label for="poll-search" class="filter-label">Search:</label>
                    <input type="text" 
                           id="poll-search" 
                           name="search" 
                           value="<?php echo esc_attr($search_query); ?>" 
                           placeholder="Search polls..." 
                           class="search-input">
                </div>
                
                <div class="filter-group">
                    <label for="poll-filter" class="filter-label">Filter:</label>
                    <select id="poll-filter" name="filter" class="filter-select">
                        <option value="all" <?php selected($current_filter, 'all'); ?>>All Polls</option>
                        <option value="contest" <?php selected($current_filter, 'contest'); ?>>Contests Only</option>
                        <option value="weekly" <?php selected($current_filter, 'weekly'); ?>>Weekly Polls</option>
                        <option value="regular" <?php selected($current_filter, 'regular'); ?>>Regular Polls</option>
                        <option value="ended" <?php selected($current_filter, 'ended'); ?>>Ended Polls</option>
                        <option value="archived" <?php selected($current_filter, 'archived'); ?>>Archived Polls</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="poll-sort" class="filter-label">Sort by:</label>
                    <select id="poll-sort" name="sort" class="filter-select">
                        <option value="newest" <?php selected($_GET['sort'] ?? 'newest', 'newest'); ?>>Newest First</option>
                        <option value="oldest" <?php selected($_GET['sort'] ?? 'newest', 'oldest'); ?>>Oldest First</option>
                        <option value="most_votes" <?php selected($_GET['sort'] ?? 'newest', 'most_votes'); ?>>Most Votes</option>
                        <option value="least_votes" <?php selected($_GET['sort'] ?? 'newest', 'least_votes'); ?>>Least Votes</option>
                        <option value="alphabetical" <?php selected($_GET['sort'] ?? 'newest', 'alphabetical'); ?>>Alphabetical</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="filter-button">
                        <span class="button-icon">🔍</span>
                        Apply Filters
                    </button>
                    
                    <?php if ($search_query || $current_filter !== 'all'): ?>
                        <a href="<?php echo esc_url(remove_query_arg(['search', 'filter', 'sort'])); ?>" class="clear-filters">
                            <span class="button-icon">✖️</span>
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Results Info -->
        <?php if ($search_query || $current_filter !== 'all'): ?>
            <div class="results-info">
                <p class="results-text">
                    <?php if ($search_query): ?>
                        Showing results for "<strong><?php echo esc_html($search_query); ?></strong>"
                    <?php endif; ?>
                    
                    <?php if ($current_filter !== 'all'): ?>
                        <?php if ($search_query): ?> in <?php endif; ?>
                        <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $current_filter))); ?></strong> polls
                    <?php endif; ?>
                    
                    (<?php echo esc_html(count($polls)); ?> found)
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Polls Grid -->
        <?php if (empty($polls)): ?>
            <div class="no-polls-found">
                <div class="no-polls-icon">📭</div>
                <h3 class="no-polls-title">No Polls Found</h3>
                <p class="no-polls-message">
                    <?php if ($search_query || $current_filter !== 'all'): ?>
                        No polls match your current search criteria. Try adjusting your filters or search terms.
                    <?php else: ?>
                        There are no past polls to display at the moment. Check back later!
                    <?php endif; ?>
                </p>
                
                <?php if ($search_query || $current_filter !== 'all'): ?>
                    <a href="<?php echo esc_url(remove_query_arg(['search', 'filter', 'sort'])); ?>" class="view-all-button">
                        View All Polls
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="polls-grid">
                <?php foreach ($polls as $poll): 
                    $poll_id = $poll['id'];
                    $options = json_decode($poll['options'], true) ?: [];
                    $results = $args['poll_results'][$poll_id] ?? [];
                    $total_votes = array_sum($results);
                    $is_contest = $poll['is_contest'];
                    $is_weekly = $poll['is_weekly'];
                    $poll_image = $args['poll_images'][$poll_id] ?? '';
                    $created_date = date_i18n(get_option('date_format'), strtotime($poll['created_at']));
                    $end_date = $poll['end_date'] ? date_i18n(get_option('date_format'), strtotime($poll['end_date'])) : null;
                    $winner_option = !empty($results) ? array_search(max($results), $results) : null;
                ?>
                    <div class="poll-card" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                        
                        <!-- Poll Image (for contests) -->
                        <?php if ($is_contest && $poll_image): ?>
                            <div class="poll-card-image">
                                <img src="<?php echo esc_url($poll_image); ?>" alt="<?php echo esc_attr($poll['title']); ?>" />
                                <div class="image-overlay">
                                    <div class="contest-badge">
                                        <span class="badge-icon">🏆</span>
                                        <span class="badge-text">Contest</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Poll Header -->
                        <div class="poll-card-header">
                            <div class="poll-badges">
                                <?php if ($is_contest && !$poll_image): ?>
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
                                
                                <span class="poll-badge status <?php echo esc_attr($poll['status']); ?>">
                                    <?php echo esc_html(ucfirst($poll['status'])); ?>
                                </span>
                            </div>
                            
                            <h3 class="poll-card-title">
                                <a href="#" class="poll-title-link" data-action="view-poll" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                                    <?php echo esc_html($poll['title']); ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($poll['description'])): ?>
                                <p class="poll-card-description">
                                    <?php echo esc_html(wp_trim_words($poll['description'], 20)); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Poll Stats -->
                        <div class="poll-card-stats">
                            <div class="stat-item">
                                <span class="stat-icon">👥</span>
                                <span class="stat-value"><?php echo esc_html($total_votes); ?></span>
                                <span class="stat-label"><?php echo _n('vote', 'votes', $total_votes, 'pollmaster'); ?></span>
                            </div>
                            
                            <div class="stat-item">
                                <span class="stat-icon">📊</span>
                                <span class="stat-value"><?php echo esc_html(count($options)); ?></span>
                                <span class="stat-label"><?php echo _n('option', 'options', count($options), 'pollmaster'); ?></span>
                            </div>
                            
                            <div class="stat-item">
                                <span class="stat-icon">📅</span>
                                <span class="stat-value"><?php echo esc_html($created_date); ?></span>
                                <span class="stat-label">created</span>
                            </div>
                        </div>
                        
                        <!-- Poll Results Preview -->
                        <?php if (!empty($options) && $total_votes > 0): ?>
                            <div class="poll-card-results">
                                <h4 class="results-title">Results Preview</h4>
                                <div class="results-preview">
                                    <?php 
                                    $preview_count = min(3, count($options)); // Show top 3 options
                                    $sorted_results = arsort($results) ? $results : [];
                                    $shown = 0;
                                    
                                    foreach ($results as $index => $votes):
                                        if ($shown >= $preview_count) break;
                                        
                                        $option = $options[$index] ?? '';
                                        $percentage = ($votes / $total_votes) * 100;
                                        $is_winner = $index === $winner_option;
                                        $shown++;
                                    ?>
                                        <div class="result-item <?php echo $is_winner ? 'winner' : ''; ?>">
                                            <div class="result-header">
                                                <span class="option-text"><?php echo esc_html(wp_trim_words($option, 5)); ?></span>
                                                <span class="result-stats">
                                                    <?php if ($is_winner): ?>
                                                        <span class="winner-icon">👑</span>
                                                    <?php endif; ?>
                                                    <span class="vote-count"><?php echo esc_html($votes); ?></span>
                                                    <span class="percentage">(<?php echo esc_html(number_format($percentage, 1)); ?>%)</span>
                                                </span>
                                            </div>
                                            <div class="result-bar-container">
                                                <div class="result-bar" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($options) > $preview_count): ?>
                                        <div class="more-results">
                                            <span class="more-text">+<?php echo esc_html(count($options) - $preview_count); ?> more options</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Poll Actions -->
                        <div class="poll-card-actions">
                            <button class="poll-action-button view-details" data-action="view-poll" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                                <span class="button-icon">👁️</span>
                                <span class="button-text">View Details</span>
                            </button>
                            
                            <button class="poll-action-button share-poll" data-action="share-poll" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                                <span class="button-icon">📤</span>
                                <span class="button-text">Share</span>
                            </button>
                            
                            <?php if ($is_contest && $winner_option !== null): ?>
                                <span class="contest-winner-info">
                                    <span class="winner-icon">🏆</span>
                                    <span class="winner-text">Winner: <?php echo esc_html($options[$winner_option] ?? 'Unknown'); ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Poll Footer -->
                        <div class="poll-card-footer">
                            <?php if ($end_date): ?>
                                <span class="poll-date end-date">
                                    <span class="date-icon">🏁</span>
                                    Ended: <?php echo esc_html($end_date); ?>
                                </span>
                            <?php endif; ?>
                            
                            <span class="poll-date created-date">
                                <span class="date-icon">📅</span>
                                Created: <?php echo esc_html($created_date); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
            <div class="polls-pagination">
                <div class="pagination-info">
                    <span class="pagination-text">
                        Showing <?php echo esc_html($pagination['start']); ?>-<?php echo esc_html($pagination['end']); ?> 
                        of <?php echo esc_html($pagination['total']); ?> polls
                    </span>
                </div>
                
                <div class="pagination-links">
                    <?php if ($pagination['current_page'] > 1): ?>
                        <a href="<?php echo esc_url(add_query_arg('paged', 1)); ?>" class="pagination-link first">
                            <span class="link-icon">⏮️</span>
                            <span class="link-text">First</span>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('paged', $pagination['current_page'] - 1)); ?>" class="pagination-link prev">
                            <span class="link-icon">⬅️</span>
                            <span class="link-text">Previous</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $pagination['current_page'] - 2);
                    $end_page = min($pagination['total_pages'], $pagination['current_page'] + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <?php if ($i == $pagination['current_page']): ?>
                            <span class="pagination-link current"><?php echo esc_html($i); ?></span>
                        <?php else: ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $i)); ?>" class="pagination-link"><?php echo esc_html($i); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <a href="<?php echo esc_url(add_query_arg('paged', $pagination['current_page'] + 1)); ?>" class="pagination-link next">
                            <span class="link-text">Next</span>
                            <span class="link-icon">➡️</span>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('paged', $pagination['total_pages'])); ?>" class="pagination-link last">
                            <span class="link-text">Last</span>
                            <span class="link-icon">⏭️</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Back to Top -->
        <div class="back-to-top">
            <button class="back-to-top-button" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
                <span class="button-icon">⬆️</span>
                <span class="button-text">Back to Top</span>
            </button>
        </div>
    </div>
</div>

<!-- Poll Detail Modal -->
<div id="poll-detail-modal" class="pollmaster-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Poll Details</h3>
                <button class="modal-close" aria-label="Close modal">
                    <span class="close-icon">×</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div id="poll-detail-content">
                    <!-- Poll details will be loaded here via AJAX -->
                    <div class="loading-placeholder">
                        <div class="loading-spinner"></div>
                        <p>Loading poll details...</p>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="modal-button secondary" data-action="close-modal">
                    Close
                </button>
                <button class="modal-button primary" data-action="share-poll">
                    <span class="button-icon">📤</span>
                    Share Poll
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Past Polls Page Styles */
.pollmaster-past-polls-page {
    padding: 40px 0;
    background: #f8f9fa;
    min-height: 100vh;
}

.pollmaster-past-polls-page .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.header-content .page-title {
    font-size: 2.5rem;
    margin: 0 0 10px 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 15px;
}

.header-content .page-description {
    font-size: 1.1rem;
    color: #7f8c8d;
    margin: 0;
}

.header-stats {
    display: flex;
    gap: 30px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    color: #3498db;
}

.stat-label {
    display: block;
    font-size: 0.9rem;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Filters */
.polls-filters {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.filters-form {
    display: flex;
    gap: 20px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
}

.search-input,
.filter-select {
    padding: 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.search-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #3498db;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.filter-button,
.clear-filters {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.filter-button {
    background: #3498db;
    color: white;
}

.filter-button:hover {
    background: #2980b9;
}

.clear-filters {
    background: #e74c3c;
    color: white;
}

.clear-filters:hover {
    background: #c0392b;
    color: white;
}

/* Results Info */
.results-info {
    margin-bottom: 20px;
    padding: 15px 20px;
    background: #e8f4fd;
    border-radius: 8px;
    border-left: 4px solid #3498db;
}

/* Polls Grid */
.polls-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.poll-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.poll-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.poll-card-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.poll-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3), transparent);
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    padding: 15px;
}

.contest-badge {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: white;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Poll Card Content */
.poll-card-header {
    padding: 20px 20px 15px 20px;
}

.poll-badges {
    display: flex;
    gap: 8px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.poll-badge {
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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

.poll-badge.status.active {
    background: #d4edda;
    color: #155724;
}

.poll-badge.status.ended {
    background: #f8d7da;
    color: #721c24;
}

.poll-badge.status.archived {
    background: #e2e3e5;
    color: #383d41;
}

.poll-card-title {
    margin: 0 0 10px 0;
    font-size: 1.3rem;
    line-height: 1.4;
}

.poll-title-link {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s ease;
}

.poll-title-link:hover {
    color: #3498db;
}

.poll-card-description {
    color: #7f8c8d;
    margin: 0;
    line-height: 1.5;
}

/* Poll Stats */
.poll-card-stats {
    display: flex;
    justify-content: space-around;
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
}

.poll-card-stats .stat-item {
    text-align: center;
    flex: 1;
}

.poll-card-stats .stat-icon {
    display: block;
    font-size: 1.2rem;
    margin-bottom: 5px;
}

.poll-card-stats .stat-value {
    display: block;
    font-weight: bold;
    color: #2c3e50;
    font-size: 1.1rem;
}

.poll-card-stats .stat-label {
    display: block;
    font-size: 0.8rem;
    color: #7f8c8d;
}

/* Results Preview */
.poll-card-results {
    padding: 20px;
}

.results-title {
    margin: 0 0 15px 0;
    font-size: 1rem;
    color: #2c3e50;
}

.result-item {
    margin-bottom: 12px;
}

.result-item.winner {
    background: #fff3cd;
    padding: 10px;
    border-radius: 8px;
    border-left: 4px solid #f39c12;
}

.result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.option-text {
    font-weight: 500;
    color: #2c3e50;
}

.result-stats {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
    color: #7f8c8d;
}

.winner-icon {
    color: #f39c12;
}

.result-bar-container {
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.result-bar {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2980b9);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.more-results {
    text-align: center;
    padding: 10px;
    color: #7f8c8d;
    font-style: italic;
    font-size: 0.9rem;
}

/* Poll Actions */
.poll-card-actions {
    padding: 15px 20px;
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.poll-action-button {
    padding: 8px 15px;
    border: 2px solid #3498db;
    background: transparent;
    color: #3498db;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.poll-action-button:hover {
    background: #3498db;
    color: white;
}

.contest-winner-info {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
    color: #f39c12;
    font-weight: 600;
}

/* Poll Footer */
.poll-card-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: #7f8c8d;
}

.poll-date {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* No Polls Found */
.no-polls-found {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.no-polls-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.no-polls-title {
    font-size: 1.8rem;
    color: #2c3e50;
    margin: 0 0 15px 0;
}

.no-polls-message {
    font-size: 1.1rem;
    color: #7f8c8d;
    margin: 0 0 25px 0;
    line-height: 1.6;
}

.view-all-button {
    display: inline-block;
    padding: 12px 25px;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    transition: background 0.3s ease;
}

.view-all-button:hover {
    background: #2980b9;
    color: white;
}

/* Pagination */
.polls-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.pagination-info {
    color: #7f8c8d;
    font-size: 0.95rem;
}

.pagination-links {
    display: flex;
    gap: 8px;
    align-items: center;
}

.pagination-link {
    padding: 8px 12px;
    border: 2px solid #e9ecef;
    background: white;
    color: #2c3e50;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.pagination-link:hover {
    border-color: #3498db;
    color: #3498db;
}

.pagination-link.current {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

/* Back to Top */
.back-to-top {
    text-align: center;
    margin-top: 40px;
}

.back-to-top-button {
    padding: 12px 25px;
    background: #2c3e50;
    color: white;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background 0.3s ease;
}

.back-to-top-button:hover {
    background: #34495e;
}

/* Modal Styles */
.pollmaster-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
}

.modal-container {
    position: relative;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-content {
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.modal-header {
    padding: 20px 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    margin: 0;
    font-size: 1.3rem;
    color: #2c3e50;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #7f8c8d;
    padding: 5px;
    border-radius: 50%;
    transition: background 0.3s ease;
}

.modal-close:hover {
    background: #e9ecef;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px 25px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-button {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.modal-button.primary {
    background: #3498db;
    color: white;
}

.modal-button.primary:hover {
    background: #2980b9;
}

.modal-button.secondary {
    background: #6c757d;
    color: white;
}

.modal-button.secondary:hover {
    background: #5a6268;
}

.loading-placeholder {
    text-align: center;
    padding: 40px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e9ecef;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .header-stats {
        justify-content: center;
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .polls-grid {
        grid-template-columns: 1fr;
    }
    
    .polls-pagination {
        flex-direction: column;
        gap: 15px;
    }
    
    .pagination-links {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .poll-card-actions {
        justify-content: center;
    }
    
    .poll-card-footer {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .header-content .page-title {
        font-size: 2rem;
    }
    
    .polls-grid {
        gap: 15px;
    }
    
    .poll-card-header,
    .poll-card-results,
    .poll-card-actions {
        padding: 15px;
    }
    
    .modal-container {
        width: 95%;
    }
    
    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 15px 20px;
    }
}
</style>

<script>
// Past Polls Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Handle poll detail viewing
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-action="view-poll"]')) {
            e.preventDefault();
            const button = e.target.closest('[data-action="view-poll"]');
            const pollId = button.dataset.pollId;
            showPollDetails(pollId);
        }
        
        // Handle poll sharing
        if (e.target.closest('[data-action="share-poll"]')) {
            e.preventDefault();
            const button = e.target.closest('[data-action="share-poll"]');
            const pollId = button.dataset.pollId;
            sharePoll(pollId);
        }
        
        // Handle modal close
        if (e.target.closest('.modal-close') || e.target.closest('[data-action="close-modal"]')) {
            e.preventDefault();
            closeModal();
        }
        
        // Handle modal overlay click
        if (e.target.classList.contains('modal-overlay')) {
            closeModal();
        }
    });
    
    // Handle form auto-submit on filter change
    document.addEventListener('change', function(e) {
        if (e.target.matches('.filter-select')) {
            e.target.closest('form').submit();
        }
    });
    
    // Handle search input with debounce
    let searchTimeout;
    document.addEventListener('input', function(e) {
        if (e.target.matches('.search-input')) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                if (e.target.value.length >= 3 || e.target.value.length === 0) {
                    e.target.closest('form').submit();
                }
            }, 500);
        }
    });
    
    function showPollDetails(pollId) {
        const modal = document.getElementById('poll-detail-modal');
        const content = document.getElementById('poll-detail-content');
        
        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Load poll details via AJAX
        if (typeof pollmaster_frontend !== 'undefined') {
            fetch(pollmaster_frontend.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'pollmaster_get_poll_details',
                    poll_id: pollId,
                    nonce: pollmaster_frontend.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = data.data.html;
                } else {
                    content.innerHTML = '<p class="error">Failed to load poll details.</p>';
                }
            })
            .catch(error => {
                content.innerHTML = '<p class="error">Error loading poll details.</p>';
            });
        }
    }
    
    function sharePoll(pollId) {
        const url = window.location.origin + '/?poll_id=' + pollId;
        const title = 'Check out this poll!';
        
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            });
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(url).then(function() {
                alert('Poll link copied to clipboard!');
            }).catch(function() {
                prompt('Copy this link:', url);
            });
        }
    }
    
    function closeModal() {
        const modal = document.getElementById('poll-detail-modal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    // Handle escape key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
});
</script>

<?php
get_footer();
?>