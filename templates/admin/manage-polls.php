<?php
/**
 * Manage Polls Admin Template
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get polls data
$database = new PollMaster_Database();

// Handle pagination
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($current_page - 1) * $per_page;

// Handle filters
$filters = [
    'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
    'type' => isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '',
    'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
    'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
    'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
];

// Get polls with filters
$polls_data = $database->get_polls_with_pagination($filters, $per_page, $offset);
$polls = $polls_data['polls'];
$total_polls = $polls_data['total'];
$total_pages = ceil($total_polls / $per_page);

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['poll_ids']) && wp_verify_nonce($_POST['_wpnonce'], 'bulk_polls_action')) {
    $action = sanitize_text_field($_POST['bulk_action']);
    $poll_ids = array_map('intval', $_POST['poll_ids']);
    
    switch ($action) {
        case 'delete':
            foreach ($poll_ids as $poll_id) {
                $database->delete_poll($poll_id);
            }
            $message = count($poll_ids) . ' polls deleted successfully.';
            break;
            
        case 'archive':
            foreach ($poll_ids as $poll_id) {
                $database->update_poll($poll_id, ['status' => 'archived']);
            }
            $message = count($poll_ids) . ' polls archived successfully.';
            break;
            
        case 'activate':
            foreach ($poll_ids as $poll_id) {
                $database->update_poll($poll_id, ['status' => 'active']);
            }
            $message = count($poll_ids) . ' polls activated successfully.';
            break;
            
        case 'make_weekly':
            // First, remove weekly status from all polls
            $database->remove_all_weekly_polls();
            // Then set the selected polls as weekly
            foreach ($poll_ids as $poll_id) {
                $database->update_poll($poll_id, ['is_weekly' => 1]);
            }
            $message = count($poll_ids) . ' polls set as weekly.';
            break;
    }
    
    if (isset($message)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
}

?>

<div class="wrap pollmaster-manage-polls">
    <div class="page-header">
        <h1 class="page-title">
            <span class="title-icon">📋</span>
            Manage Polls
        </h1>
        
        <div class="page-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll')); ?>" class="button button-primary">
                <span class="button-icon">➕</span>
                Add New Poll
            </a>
            
            <button class="button button-secondary" data-action="export-polls">
                <span class="button-icon">📤</span>
                Export Polls
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="polls-filters">
        <form method="get" class="filters-form">
            <input type="hidden" name="page" value="pollmaster-manage-polls">
            
            <div class="filter-group">
                <label for="search" class="filter-label">Search</label>
                <input type="text" id="search" name="search" class="search-input" 
                       placeholder="Search polls..." value="<?php echo esc_attr($filters['search']); ?>">
            </div>
            
            <div class="filter-group">
                <label for="status" class="filter-label">Status</label>
                <select id="status" name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?php selected($filters['status'], 'active'); ?>>Active</option>
                    <option value="ended" <?php selected($filters['status'], 'ended'); ?>>Ended</option>
                    <option value="archived" <?php selected($filters['status'], 'archived'); ?>>Archived</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="type" class="filter-label">Type</label>
                <select id="type" name="type" class="filter-select">
                    <option value="">All Types</option>
                    <option value="regular" <?php selected($filters['type'], 'regular'); ?>>Regular</option>
                    <option value="contest" <?php selected($filters['type'], 'contest'); ?>>Contest</option>
                    <option value="weekly" <?php selected($filters['type'], 'weekly'); ?>>Weekly</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date_from" class="filter-label">Date From</label>
                <input type="date" id="date_from" name="date_from" class="filter-input" 
                       value="<?php echo esc_attr($filters['date_from']); ?>">
            </div>
            
            <div class="filter-group">
                <label for="date_to" class="filter-label">Date To</label>
                <input type="date" id="date_to" name="date_to" class="filter-input" 
                       value="<?php echo esc_attr($filters['date_to']); ?>">
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="button button-primary filter-button">
                    <span class="button-icon">🔍</span>
                    Filter
                </button>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="button clear-filters">
                    <span class="button-icon">🔄</span>
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Results Info -->
    <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['type'])): ?>
        <div class="results-info">
            <p>
                Showing <?php echo esc_html($total_polls); ?> poll(s)
                <?php if (!empty($filters['search'])): ?>
                    matching "<?php echo esc_html($filters['search']); ?>"
                <?php endif; ?>
                <?php if (!empty($filters['status'])): ?>
                    with status "<?php echo esc_html($filters['status']); ?>"
                <?php endif; ?>
                <?php if (!empty($filters['type'])): ?>
                    of type "<?php echo esc_html($filters['type']); ?>"
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Polls Table -->
    <?php if (!empty($polls)): ?>
        <form method="post" class="polls-table-form">
            <?php wp_nonce_field('bulk_polls_action'); ?>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <select name="bulk_action" class="bulk-action-select">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                    <option value="archive">Archive</option>
                    <option value="activate">Activate</option>
                    <option value="make_weekly">Make Weekly</option>
                </select>
                
                <button type="submit" class="button bulk-action-button" onclick="return confirm('Are you sure you want to perform this bulk action?');">
                    Apply
                </button>
                
                <span class="bulk-info">
                    <span class="selected-count">0</span> polls selected
                </span>
            </div>
            
            <div class="polls-table-container">
                <table class="wp-list-table widefat fixed striped polls-table">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" class="select-all">
                            </td>
                            <th class="manage-column column-title sortable">
                                <a href="<?php echo esc_url(add_query_arg(['orderby' => 'title', 'order' => 'asc'])); ?>">
                                    <span>Title</span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th class="manage-column column-status">Status</th>
                            <th class="manage-column column-type">Type</th>
                            <th class="manage-column column-votes sortable">
                                <a href="<?php echo esc_url(add_query_arg(['orderby' => 'votes', 'order' => 'desc'])); ?>">
                                    <span>Votes</span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th class="manage-column column-created sortable">
                                <a href="<?php echo esc_url(add_query_arg(['orderby' => 'created_at', 'order' => 'desc'])); ?>">
                                    <span>Created</span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th class="manage-column column-actions">Actions</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php foreach ($polls as $poll): ?>
                            <?php
                            $poll_id = $poll['id'];
                            $title = $poll['title'];
                            $status = $poll['status'];
                            $total_votes = $poll['total_votes'] ?? 0;
                            $created_date = date('M j, Y', strtotime($poll['created_at']));
                            $is_contest = !empty($poll['is_contest']);
                            $is_weekly = !empty($poll['is_weekly']);
                            $end_date = $poll['end_date'] ? date('M j, Y', strtotime($poll['end_date'])) : null;
                            ?>
                            
                            <tr class="poll-row" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                                <th class="check-column">
                                    <input type="checkbox" name="poll_ids[]" value="<?php echo esc_attr($poll_id); ?>" class="poll-checkbox">
                                </th>
                                
                                <td class="column-title">
                                    <div class="poll-title-wrapper">
                                        <strong class="poll-title">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-edit-poll&poll_id=' . $poll_id)); ?>" class="poll-title-link">
                                                <?php echo esc_html($title); ?>
                                            </a>
                                        </strong>
                                        
                                        <?php if (!empty($poll['description'])): ?>
                                            <div class="poll-description">
                                                <?php echo esc_html(wp_trim_words($poll['description'], 15)); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="poll-badges">
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
                                            
                                            <?php if ($end_date): ?>
                                                <span class="poll-badge end-date">
                                                    <span class="badge-icon">🏁</span>
                                                    Ends: <?php echo esc_html($end_date); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-edit-poll&poll_id=' . $poll_id)); ?>">Edit</a> |
                                            </span>
                                            <span class="view">
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-poll-results&poll_id=' . $poll_id)); ?>">View Results</a> |
                                            </span>
                                            <span class="duplicate">
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll&duplicate=' . $poll_id)); ?>">Duplicate</a> |
                                            </span>
                                            <span class="delete">
                                                <a href="#" class="delete-poll" data-poll-id="<?php echo esc_attr($poll_id); ?>">Delete</a>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="column-status">
                                    <span class="status-badge status-<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html(ucfirst($status)); ?>
                                    </span>
                                </td>
                                
                                <td class="column-type">
                                    <div class="poll-type">
                                        <?php if ($is_contest): ?>
                                            <span class="type-icon">🏆</span>
                                            Contest
                                        <?php elseif ($is_weekly): ?>
                                            <span class="type-icon">📅</span>
                                            Weekly
                                        <?php else: ?>
                                            <span class="type-icon">📋</span>
                                            Regular
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="column-votes">
                                    <div class="votes-info">
                                        <span class="votes-count"><?php echo esc_html($total_votes); ?></span>
                                        <span class="votes-label">votes</span>
                                    </div>
                                </td>
                                
                                <td class="column-created">
                                    <div class="created-info">
                                        <span class="created-date"><?php echo esc_html($created_date); ?></span>
                                        <span class="created-time"><?php echo esc_html(date('g:i A', strtotime($poll['created_at']))); ?></span>
                                    </div>
                                </td>
                                
                                <td class="column-actions">
                                    <div class="action-buttons">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-edit-poll&poll_id=' . $poll_id)); ?>" 
                                           class="action-button edit" title="Edit Poll">
                                            <span class="button-icon">✏️</span>
                                        </a>
                                        
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-poll-results&poll_id=' . $poll_id)); ?>" 
                                           class="action-button results" title="View Results">
                                            <span class="button-icon">📊</span>
                                        </a>
                                        
                                        <button class="action-button preview" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Preview Poll">
                                            <span class="button-icon">👁️</span>
                                        </button>
                                        
                                        <?php if ($is_contest && $status === 'ended'): ?>
                                            <button class="action-button announce-winner" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Announce Winner">
                                                <span class="button-icon">🏆</span>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="action-button delete" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Delete Poll">
                                            <span class="button-icon">🗑️</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="polls-pagination">
                <div class="pagination-info">
                    <span class="pagination-text">
                        Showing <?php echo esc_html(($current_page - 1) * $per_page + 1); ?>-<?php echo esc_html(min($current_page * $per_page, $total_polls)); ?> 
                        of <?php echo esc_html($total_polls); ?> polls
                    </span>
                </div>
                
                <div class="pagination-links">
                    <?php if ($current_page > 1): ?>
                        <a href="<?php echo esc_url(add_query_arg('paged', 1)); ?>" class="pagination-link first">
                            <span class="link-icon">⏮️</span>
                            <span class="link-text">First</span>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('paged', $current_page - 1)); ?>" class="pagination-link prev">
                            <span class="link-icon">⬅️</span>
                            <span class="link-text">Previous</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <?php if ($i == $current_page): ?>
                            <span class="pagination-link current"><?php echo esc_html($i); ?></span>
                        <?php else: ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $i)); ?>" class="pagination-link"><?php echo esc_html($i); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?php echo esc_url(add_query_arg('paged', $current_page + 1)); ?>" class="pagination-link next">
                            <span class="link-text">Next</span>
                            <span class="link-icon">➡️</span>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('paged', $total_pages)); ?>" class="pagination-link last">
                            <span class="link-text">Last</span>
                            <span class="link-icon">⏭️</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- No Polls Found -->
        <div class="no-polls-found">
            <div class="no-polls-icon">📋</div>
            <h2 class="no-polls-title">No Polls Found</h2>
            <p class="no-polls-message">
                <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['type'])): ?>
                    No polls match your current filters. Try adjusting your search criteria.
                <?php else: ?>
                    You haven't created any polls yet. Create your first poll to get started!
                <?php endif; ?>
            </p>
            
            <div class="no-polls-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll')); ?>" class="button button-primary">
                    <span class="button-icon">➕</span>
                    Create Your First Poll
                </a>
                
                <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['type'])): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="button button-secondary">
                        <span class="button-icon">🔄</span>
                        Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Poll Preview Modal -->
<div id="poll-preview-modal" class="pollmaster-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Poll Preview</h3>
                <button class="modal-close" aria-label="Close modal">
                    <span class="close-icon">×</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div id="poll-preview-content">
                    <!-- Poll preview will be loaded here via AJAX -->
                    <div class="loading-placeholder">
                        <div class="loading-spinner"></div>
                        <p>Loading poll preview...</p>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="modal-button secondary" data-action="close-modal">
                    Close
                </button>
                <button class="modal-button primary" data-action="edit-poll">
                    <span class="button-icon">✏️</span>
                    Edit Poll
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Manage Polls Styles */
.pollmaster-manage-polls {
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
    font-size: 2rem;
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
}

/* Filters */
.polls-filters {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 20px;
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
    min-width: 150px;
}

.filter-label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
}

.search-input,
.filter-select,
.filter-input {
    padding: 8px 12px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.search-input:focus,
.filter-select:focus,
.filter-input:focus {
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
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
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

/* Bulk Actions */
.bulk-actions {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.bulk-action-select {
    padding: 6px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.bulk-action-button {
    padding: 6px 12px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.bulk-action-button:hover {
    background: #5a6268;
}

.bulk-info {
    color: #7f8c8d;
    font-size: 0.9rem;
}

/* Polls Table */
.polls-table-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.polls-table {
    margin: 0;
}

.polls-table th,
.polls-table td {
    padding: 15px 12px;
    vertical-align: top;
}

.polls-table th {
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
    color: #2c3e50;
}

.polls-table th a {
    color: #2c3e50;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
}

.polls-table th a:hover {
    color: #3498db;
}

.poll-row {
    transition: background-color 0.3s ease;
}

.poll-row:hover {
    background-color: #f8f9fa;
}

.poll-row.selected {
    background-color: #e3f2fd;
}

/* Poll Title Column */
.poll-title-wrapper {
    max-width: 300px;
}

.poll-title {
    margin-bottom: 8px;
}

.poll-title-link {
    color: #2c3e50;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.poll-title-link:hover {
    color: #3498db;
}

.poll-description {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin-bottom: 10px;
    line-height: 1.4;
}

.poll-badges {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
    flex-wrap: wrap;
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

.poll-badge.end-date {
    background: #f8d7da;
    color: #721c24;
}

.row-actions {
    font-size: 0.85rem;
}

.row-actions a {
    color: #3498db;
    text-decoration: none;
}

.row-actions a:hover {
    color: #2980b9;
}

.row-actions .delete a {
    color: #e74c3c;
}

.row-actions .delete a:hover {
    color: #c0392b;
}

/* Status Column */
.status-badge {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-block;
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

/* Type Column */
.poll-type {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    color: #2c3e50;
}

/* Votes Column */
.votes-info {
    text-align: center;
}

.votes-count {
    display: block;
    font-size: 1.2rem;
    font-weight: bold;
    color: #2c3e50;
}

.votes-label {
    display: block;
    font-size: 0.8rem;
    color: #7f8c8d;
}

/* Created Column */
.created-info {
    text-align: center;
}

.created-date {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 2px;
}

.created-time {
    display: block;
    font-size: 0.8rem;
    color: #7f8c8d;
}

/* Actions Column */
.action-buttons {
    display: flex;
    gap: 5px;
    justify-content: center;
}

.action-button {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 50%;
    background: #f8f9fa;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.action-button:hover {
    background: #e9ecef;
    transform: scale(1.1);
}

.action-button.edit:hover {
    background: #3498db;
    color: white;
}

.action-button.results:hover {
    background: #2ecc71;
    color: white;
}

.action-button.preview:hover {
    background: #f39c12;
    color: white;
}

.action-button.announce-winner:hover {
    background: #e67e22;
    color: white;
}

.action-button.delete:hover {
    background: #e74c3c;
    color: white;
}

/* Pagination */
.polls-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    background: white;
    border-radius: 8px;
    margin-top: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.pagination-info {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.pagination-links {
    display: flex;
    gap: 5px;
    align-items: center;
}

.pagination-link {
    padding: 8px 12px;
    border: 1px solid #e9ecef;
    background: white;
    color: #2c3e50;
    text-decoration: none;
    border-radius: 4px;
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
    opacity: 0.5;
}

.no-polls-title {
    font-size: 1.8rem;
    color: #2c3e50;
    margin: 0 0 15px 0;
}

.no-polls-message {
    font-size: 1.1rem;
    color: #7f8c8d;
    margin: 0 0 30px 0;
    line-height: 1.6;
}

.no-polls-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
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
    max-width: 800px;
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
@media (max-width: 1200px) {
    .polls-table {
        font-size: 0.9rem;
    }
    
    .polls-table th,
    .polls-table td {
        padding: 10px 8px;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .polls-table-container {
        overflow-x: auto;
    }
    
    .polls-table {
        min-width: 800px;
    }
    
    .polls-pagination {
        flex-direction: column;
        gap: 15px;
    }
    
    .pagination-links {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .bulk-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1.5rem;
        flex-direction: column;
        gap: 8px;
    }
    
    .page-actions {
        flex-direction: column;
        width: 100%;
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
// Manage Polls JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize manage polls functionality
    initializeManagePolls();
    
    function initializeManagePolls() {
        bindEventHandlers();
        updateBulkActionInfo();
    }
    
    function bindEventHandlers() {
        // Select all checkbox
        const selectAllCheckbox = document.querySelector('.select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.poll-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    updateRowSelection(checkbox);
                });
                updateBulkActionInfo();
            });
        }
        
        // Individual checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.matches('.poll-checkbox')) {
                updateRowSelection(e.target);
                updateBulkActionInfo();
                updateSelectAllCheckbox();
            }
        });
        
        // Delete poll buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-poll')) {
                e.preventDefault();
                const pollId = e.target.closest('.delete-poll').dataset.pollId;
                deletePoll(pollId);
            }
            
            // Preview poll buttons
            if (e.target.closest('.preview')) {
                e.preventDefault();
                const pollId = e.target.closest('.preview').dataset.pollId;
                previewPoll(pollId);
            }
            
            // Announce winner buttons
            if (e.target.closest('.announce-winner')) {
                e.preventDefault();
                const pollId = e.target.closest('.announce-winner').dataset.pollId;
                announceWinner(pollId);
            }
            
            // Modal close
            if (e.target.closest('.modal-close') || e.target.closest('[data-action="close-modal"]')) {
                e.preventDefault();
                closeModal();
            }
            
            // Modal overlay click
            if (e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
            
            // Export polls
            if (e.target.closest('[data-action="export-polls"]')) {
                e.preventDefault();
                exportPolls();
            }
        });
        
        // Search input with debounce
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
        
        // Filter selects auto-submit
        document.addEventListener('change', function(e) {
            if (e.target.matches('.filter-select')) {
                e.target.closest('form').submit();
            }
        });
    }
    
    function updateRowSelection(checkbox) {
        const row = checkbox.closest('.poll-row');
        if (checkbox.checked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    }
    
    function updateBulkActionInfo() {
        const selectedCheckboxes = document.querySelectorAll('.poll-checkbox:checked');
        const countElement = document.querySelector('.selected-count');
        if (countElement) {
            countElement.textContent = selectedCheckboxes.length;
        }
    }
    
    function updateSelectAllCheckbox() {
        const selectAllCheckbox = document.querySelector('.select-all');
        const checkboxes = document.querySelectorAll('.poll-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.poll-checkbox:checked');
        
        if (selectAllCheckbox) {
            if (checkedCheckboxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCheckboxes.length === checkboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }
    }
    
    function deletePoll(pollId) {
        if (confirm('Are you sure you want to delete this poll? This action cannot be undone.')) {
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
    
    function previewPoll(pollId) {
        const modal = document.getElementById('poll-preview-modal');
        const content = document.getElementById('poll-preview-content');
        
        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Load poll preview via AJAX
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'pollmaster_preview_poll',
                poll_id: pollId,
                nonce: pollmaster_admin.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.data.html;
                // Store poll ID for edit button
                document.querySelector('[data-action="edit-poll"]').dataset.pollId = pollId;
            } else {
                content.innerHTML = '<p class="error">Failed to load poll preview.</p>';
            }
        })
        .catch(error => {
            content.innerHTML = '<p class="error">Error loading poll preview.</p>';
        });
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
    
    function exportPolls() {
        window.location.href = 'admin.php?page=pollmaster-export&action=export-polls';
    }
    
    function closeModal() {
        const modal = document.getElementById('poll-preview-modal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    // Handle escape key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    
    // Handle edit poll button in modal
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-action="edit-poll"]')) {
            const pollId = e.target.closest('[data-action="edit-poll"]').dataset.pollId;
            if (pollId) {
                window.location.href = `admin.php?page=pollmaster-edit-poll&poll_id=${pollId}`;
            }
        }
    });
});
</script>