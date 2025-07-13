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
                $database->update_poll_status($poll_id, 'archived');
            }
            $message = count($poll_ids) . ' polls archived successfully.';
            break;
            
        case 'activate':
            foreach ($poll_ids as $poll_id) {
                $database->update_poll_status($poll_id, 'active');
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
        // Store message in transient for display after redirect
        set_transient('pollmaster_admin_notice', $message, 30);
        echo '<script>window.location.href = "' . esc_url(admin_url('admin.php?page=pollmaster-manage-polls')) . '";</script>';
        exit;
    }
}

?>

<div class="pollmaster-admin-page bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    <?php
    // Display admin notices from transients
    if ($notice = get_transient('pollmaster_admin_notice')) {
        echo '<div class="alert alert-success mb-6 shadow-lg"><div><svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' . esc_html($notice) . '</span></div></div>';
        delete_transient('pollmaster_admin_notice');
    }
    
    // Display URL parameter messages
    if (isset($_GET['message'])) {
        switch ($_GET['message']) {
            case 'deleted':
                echo '<div class="alert alert-success mb-6 shadow-lg"><div><svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' . esc_html__('Poll deleted successfully.', 'pollmaster') . '</span></div></div>';
                break;
        }
    }
    
    if (isset($_GET['error'])) {
        switch ($_GET['error']) {
            case 'delete_failed':
                echo '<div class="alert alert-error mb-6 shadow-lg"><div><svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' . esc_html__('Failed to delete poll.', 'pollmaster') . '</span></div></div>';
                break;
        }
    }
    ?>
    
    <!-- Modern Page Header -->
    <div class="hero bg-gradient-to-r from-primary to-secondary text-primary-content rounded-2xl mb-8 shadow-2xl">
        <div class="hero-content text-center py-12">
            <div class="max-w-md">
                <div class="text-6xl mb-4">📊</div>
                <h1 class="text-5xl font-bold mb-4">Manage Polls</h1>
                <p class="text-lg opacity-90 mb-6">Create, edit, and manage all your polls from one powerful dashboard</p>
                <div class="flex gap-4 justify-center">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll')); ?>" class="btn btn-accent btn-lg gap-2 shadow-lg hover:shadow-xl transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Create New Poll
                    </a>
                    
                    <button class="btn btn-outline btn-lg gap-2 text-white border-white hover:bg-white hover:text-primary" onclick="exportPollData()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filters Section -->
    <div class="card bg-base-100 shadow-xl mb-8">
        <div class="card-body">
            <div class="flex items-center gap-3 mb-6">
                <div class="text-2xl">🔍</div>
                <h2 class="card-title text-2xl">Search & Filter Polls</h2>
            </div>
            
            <form method="get" class="space-y-6">
                <input type="hidden" name="page" value="pollmaster-manage-polls">
                
                <!-- Search Bar -->
                <div class="form-control">
                    <div class="input-group">
                        <input type="text" id="search" name="search" value="<?php echo esc_attr($filters['search']); ?>" placeholder="Search polls by question, options, or description..." class="input input-bordered input-lg flex-1" />
                        <button type="submit" class="btn btn-primary btn-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </button>
                    </div>
                </div>
                
                <!-- Filter Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="form-control">
                        <label class="label" for="status">
                            <span class="label-text font-semibold">📊 Status</span>
                        </label>
                        <select id="status" name="status" class="select select-bordered">
                            <option value="">All Statuses</option>
                            <option value="active" <?php selected($filters['status'], 'active'); ?>>🟢 Active</option>
                            <option value="ended" <?php selected($filters['status'], 'ended'); ?>>🔴 Ended</option>
                            <option value="archived" <?php selected($filters['status'], 'archived'); ?>>📦 Archived</option>
                        </select>
                    </div>
                    
                    <div class="form-control">
                        <label class="label" for="type">
                            <span class="label-text font-semibold">🏷️ Type</span>
                        </label>
                        <select id="type" name="type" class="select select-bordered">
                            <option value="">All Types</option>
                            <option value="regular" <?php selected($filters['type'], 'regular'); ?>>📝 Regular</option>
                            <option value="weekly" <?php selected($filters['type'], 'weekly'); ?>>📅 Weekly</option>
                            <option value="contest" <?php selected($filters['type'], 'contest'); ?>>🏆 Contest</option>
                        </select>
                    </div>
                    
                    <div class="form-control">
                        <label class="label" for="date_from">
                            <span class="label-text font-semibold">📅 From Date</span>
                        </label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($filters['date_from']); ?>" class="input input-bordered" />
                    </div>
                    
                    <div class="form-control">
                        <label class="label" for="date_to">
                            <span class="label-text font-semibold">📅 To Date</span>
                        </label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($filters['date_to']); ?>" class="input input-bordered" />
                    </div>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="btn btn-ghost gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Clear Filters
                    </a>
                    <button type="submit" class="btn btn-primary gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" /></svg>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modern Polls Management -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <form method="post" id="polls-form">
                <?php wp_nonce_field('bulk_polls_action'); ?>
                
                <!-- Bulk Actions & Stats -->
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <div class="stats shadow">
                            <div class="stat">
                                <div class="stat-title">Total Polls</div>
                                <div class="stat-value text-primary"><?php echo esc_html($total_polls); ?></div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <select name="bulk_action" id="bulk-action-selector" class="select select-bordered">
                                <option value="-1">Bulk Actions</option>
                                <option value="activate">✅ Activate</option>
                                <option value="archive">📦 Archive</option>
                                <option value="delete">🗑️ Delete</option>
                                <option value="make_weekly">📅 Make Weekly</option>
                            </select>
                            <button type="submit" id="doaction" class="btn btn-primary">Apply</button>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="join">
                            <?php if ($current_page > 1): ?>
                                <a class="join-item btn" href="<?php echo esc_url(add_query_arg('paged', 1)); ?>">«</a>
                                <a class="join-item btn" href="<?php echo esc_url(add_query_arg('paged', $current_page - 1)); ?>">‹</a>
                            <?php endif; ?>
                            
                            <button class="join-item btn btn-active"><?php echo esc_html($current_page); ?></button>
                            <span class="join-item btn btn-disabled">of <?php echo esc_html($total_pages); ?></span>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a class="join-item btn" href="<?php echo esc_url(add_query_arg('paged', $current_page + 1)); ?>">›</a>
                                <a class="join-item btn" href="<?php echo esc_url(add_query_arg('paged', $total_pages)); ?>">»</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
        
                <!-- Responsive Table View -->
                <?php if (empty($polls)): ?>
                    <div class="hero bg-base-200 rounded-2xl">
                        <div class="hero-content text-center">
                            <div class="max-w-md">
                                <div class="text-6xl mb-4">📊</div>
                                <h3 class="text-3xl font-bold mb-4">No polls found</h3>
                                <p class="text-lg mb-6">Create your first poll to get started with engaging your audience!</p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll')); ?>" class="btn btn-primary btn-lg gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    Create Your First Poll
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr class="bg-base-200">
                                    <th class="w-12">
                                        <input type="checkbox" id="select-all" class="checkbox checkbox-primary" />
                                    </th>
                                    <th class="text-left font-bold">📊 Poll Question</th>
                                    <th class="text-center font-bold">🏷️ Type</th>
                                    <th class="text-center font-bold">📈 Status</th>
                                    <th class="text-center font-bold">🗳️ Votes</th>
                                    <th class="text-center font-bold">📅 Created</th>
                                    <th class="text-center font-bold">⚙️ Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($polls as $poll): ?>
                                    <?php
                                    $poll_results = $database->get_poll_results($poll['id']);
                                    $total_votes = $poll_results['total_votes'];
                                    $status_colors = [
                                        'active' => 'badge-success',
                                        'ended' => 'badge-error', 
                                        'archived' => 'badge-neutral'
                                    ];
                                    ?>
                                    <tr class="hover:bg-base-100 transition-colors">
                                        <td>
                                            <input type="checkbox" name="poll_ids[]" value="<?php echo esc_attr($poll['id']); ?>" class="checkbox checkbox-primary poll-checkbox" />
                                        </td>
                                        <td>
                                            <div class="flex flex-col">
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-poll-results&poll_id=' . $poll['id'])); ?>" class="font-bold text-lg hover:text-primary transition-colors link link-hover">
                                                    <?php echo esc_html($poll['question']); ?>
                                                </a>
                                                <div class="text-sm text-base-content/70 mt-1">
                                                    <span class="font-medium">A:</span> <?php echo esc_html($poll['option_a']); ?> • 
                                                    <span class="font-medium">B:</span> <?php echo esc_html($poll['option_b']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex flex-col gap-1 items-center">
                                                <?php if ($poll['is_contest']): ?>
                                                    <div class="badge badge-warning gap-1">
                                                        🏆 Contest
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($poll['is_weekly']): ?>
                                                    <div class="badge badge-info gap-1">
                                                        📅 Weekly
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="badge badge-outline gap-1">
                                                    📝 <?php echo esc_html(ucfirst($poll['type'] ?? 'regular')); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="badge <?php echo esc_attr($status_colors[$poll['status']] ?? 'badge-neutral'); ?> gap-1">
                                                <?php echo esc_html(ucfirst($poll['status'])); ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="stat-value text-2xl text-primary font-bold"><?php echo esc_html($total_votes); ?></div>
                                            <div class="text-xs text-base-content/70">total votes</div>
                                        </td>
                                        <td class="text-center">
                                            <div class="text-sm font-medium"><?php echo esc_html(date('M j, Y', strtotime($poll['created_at']))); ?></div>
                                            <div class="text-xs text-base-content/70"><?php echo esc_html(date('g:i A', strtotime($poll['created_at']))); ?></div>
                                        </td>
                                        <td>
                                            <div class="flex gap-1 justify-center">
                                                <div class="tooltip" data-tip="Edit Poll">
                                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-edit-poll&poll_id=' . $poll['id'])); ?>" class="btn btn-sm btn-primary btn-square">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                    </a>
                                                </div>
                                                
                                                <div class="tooltip" data-tip="View Results">
                                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-poll-results&poll_id=' . $poll['id'])); ?>" class="btn btn-sm btn-info btn-square">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                                    </a>
                                                </div>
                                                
                                                <div class="tooltip" data-tip="Duplicate Poll">
                                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll&duplicate=' . $poll['id'])); ?>" class="btn btn-sm btn-secondary btn-square">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                                    </a>
                                                </div>
                                                
                                                <div class="tooltip" data-tip="Delete Poll">
                                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pollmaster-manage-polls&action=delete&poll_id=' . $poll['id']), 'delete_poll_' . $poll['id'])); ?>" class="btn btn-sm btn-error btn-square" onclick="return confirm('Are you sure you want to delete this poll?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile Card View -->
                    <div class="lg:hidden space-y-4">
                        <?php foreach ($polls as $poll): ?>
                            <?php
                            $poll_results = $database->get_poll_results($poll['id']);
                            $total_votes = $poll_results['total_votes'];
                            $status_colors = [
                                'active' => 'badge-success',
                                'ended' => 'badge-error', 
                                'archived' => 'badge-neutral'
                            ];
                            ?>
                            <div class="card bg-base-100 border border-base-300 hover:shadow-lg transition-all duration-300">
                                <div class="card-body p-4">
                                    <div class="flex items-start justify-between mb-3">
                                        <input type="checkbox" name="poll_ids[]" value="<?php echo esc_attr($poll['id']); ?>" class="checkbox checkbox-primary poll-checkbox" />
                                        
                                        <div class="flex gap-1">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-edit-poll&poll_id=' . $poll['id'])); ?>" class="btn btn-xs btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </a>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-poll-results&poll_id=' . $poll['id'])); ?>" class="btn btn-xs btn-info">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <h3 class="font-bold text-lg mb-2">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-poll-results&poll_id=' . $poll['id'])); ?>" class="link link-hover">
                                            <?php echo esc_html($poll['question']); ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <div class="badge <?php echo esc_attr($status_colors[$poll['status']] ?? 'badge-neutral'); ?>">
                                            <?php echo esc_html(ucfirst($poll['status'])); ?>
                                        </div>
                                        
                                        <?php if ($poll['is_contest']): ?>
                                            <div class="badge badge-warning">🏆 Contest</div>
                                        <?php endif; ?>
                                        
                                        <?php if ($poll['is_weekly']): ?>
                                            <div class="badge badge-info">📅 Weekly</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="stats stats-horizontal shadow-sm">
                                        <div class="stat">
                                            <div class="stat-title text-xs">Votes</div>
                                            <div class="stat-value text-lg text-primary"><?php echo esc_html($total_votes); ?></div>
                                        </div>
                                        <div class="stat">
                                            <div class="stat-title text-xs">Created</div>
                                            <div class="stat-value text-sm"><?php echo esc_html(date('M j, Y', strtotime($poll['created_at']))); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
    <?php endif; ?>
    
    <!-- Bottom Actions -->
    <?php if (!empty($polls)): ?>
        <div class="flex justify-between items-center mt-6 pt-6 border-t border-base-300">
            <div class="flex gap-2">
                <select name="bulk_action" class="select select-bordered">
                    <option value="-1">Bulk Actions</option>
                    <option value="activate">✅ Activate</option>
                    <option value="archive">📦 Archive</option>
                    <option value="delete">🗑️ Delete</option>
                    <option value="make_weekly">📅 Make Weekly</option>
                </select>
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
            
            <div class="text-sm text-base-content/70">
                Showing <?php echo esc_html(min($per_page, $total_polls)); ?> of <?php echo esc_html($total_polls); ?> polls
            </div>
        </div>
    <?php endif; ?>
    
</form>
        </div>
    </div>
</div>

<style>
/* Modern Manage Polls Styles */
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --card-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    --card-shadow-hover: 0 20px 60px rgba(0, 0, 0, 0.15);
    --border-radius: 16px;
    --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.pollmaster-admin-page {
    max-width: none !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    box-sizing: border-box;
}

/* Enhanced Hero Section */
.hero {
    background: var(--primary-gradient);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    position: relative;
    overflow: hidden;
    margin-bottom: 32px;
}

.hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 1;
    padding: 48px 32px;
    text-align: center;
}

.hero-content h1 {
    font-size: 3rem;
    font-weight: 800;
    color: white;
    margin-bottom: 16px;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.hero-content p {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 32px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Modern Cards */
.card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: var(--transition);
    overflow: hidden;
    max-width: 100%;
}

.card:hover {
    box-shadow: var(--card-shadow-hover);
    transform: translateY(-4px);
}

.card-body {
    padding: 32px;
}

.card-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Enhanced Buttons */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    position: relative;
    overflow: hidden;
    text-transform: none;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.6s;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
    background: linear-gradient(135deg, #6b7280, #374151);
    color: white;
}

.btn-accent {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
}

.btn-accent:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
}

.btn-outline {
    background: transparent;
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.5);
}

.btn-outline:hover {
    background: white;
    color: #3b82f6;
    transform: translateY(-2px);
}

.btn-info {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    color: white;
}

.btn-error {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.btn-ghost {
    background: transparent;
    color: #6b7280;
    border: 1px solid #e5e7eb;
}

.btn-ghost:hover {
    background: #f3f4f6;
    color: #374151;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

.btn-lg {
    padding: 16px 32px;
    font-size: 16px;
}

/* Enhanced Form Controls */
.form-control {
    margin-bottom: 16px;
}

.label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.label-text {
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.input, .select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: var(--transition);
    background: white;
    font-family: inherit;
}

.input:focus, .select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
}

.input-bordered {
    border-color: #d1d5db;
}

.select-bordered {
    border-color: #d1d5db;
}

.input-group {
    display: flex;
    align-items: stretch;
}

.input-group .input {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-right: none;
}

.input-group .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

/* Enhanced Grid */
.grid {
    display: grid;
    gap: 24px;
}

.grid-cols-1 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

@media (min-width: 768px) {
    .md\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .md\:grid-cols-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (min-width: 1024px) {
    .lg\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .lg\:grid-cols-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

/* Spacing Utilities */
.space-y-4 > * + * {
    margin-top: 16px;
}

.space-y-6 > * + * {
    margin-top: 24px;
}

.gap-2 {
    gap: 8px;
}

.gap-3 {
    gap: 12px;
}

.gap-4 {
    gap: 16px;
}

.gap-6 {
    gap: 24px;
}

.mb-4 {
    margin-bottom: 16px;
}

.mb-6 {
    margin-bottom: 24px;
}

.mb-8 {
    margin-bottom: 32px;
}

.mt-6 {
    margin-top: 24px;
}

.pt-6 {
    padding-top: 24px;
}

.p-6 {
    padding: 24px;
}

/* Flexbox Utilities */
.flex {
    display: flex;
}

.flex-1 {
    flex: 1 1 0%;
}

.flex-col {
    flex-direction: column;
}

.flex-wrap {
    flex-wrap: wrap;
}

.items-center {
    align-items: center;
}

.items-start {
    align-items: flex-start;
}

.justify-center {
    justify-content: center;
}

.justify-between {
    justify-content: space-between;
}

.justify-end {
    justify-content: flex-end;
}

/* Enhanced Alerts */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid;
    font-weight: 500;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
    border-color: #10b981;
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
    border-color: #ef4444;
}

/* Enhanced Badges */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    gap: 4px;
}

.badge-success {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
}

.badge-error {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
}

.badge-neutral {
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    color: #374151;
}

.badge-warning {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
}

.badge-info {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
}

.badge-outline {
    background: transparent;
    border: 1px solid #e5e7eb;
    color: #6b7280;
}

/* Enhanced Stats */
.stats {
    display: flex;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.stats-horizontal {
    flex-direction: row;
}

.stat {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    border-right: 1px solid #f3f4f6;
}

.stat:last-child {
    border-right: none;
}

.stat-title {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 2px;
}

.stat-desc {
    font-size: 11px;
    color: #9ca3af;
}

/* Enhanced Pagination */
.join {
    display: flex;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.join-item {
    border: none;
    border-radius: 0;
    margin: 0;
}

.join-item:first-child {
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}

.join-item:last-child {
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}

.btn-active {
    background: #3b82f6;
    color: white;
}

.btn-disabled {
    background: #f3f4f6;
    color: #9ca3af;
    cursor: not-allowed;
}

/* Enhanced Checkboxes */
.checkbox {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    transition: var(--transition);
    accent-color: #3b82f6;
}

.checkbox:checked {
    background: #3b82f6;
    border-color: #3b82f6;
}

.checkbox-primary {
    accent-color: #3b82f6;
}

/* Poll Card Enhancements */
.poll-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: var(--border-radius);
    padding: 24px;
    margin-bottom: 20px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.poll-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.05), transparent);
    transition: left 0.6s;
}

.poll-card:hover::before {
    left: 100%;
}

.poll-card:hover {
    border-color: #3b82f6;
    transform: translateY(-4px);
    box-shadow: var(--card-shadow-hover);
}

/* Link Styling */
.link {
    color: #3b82f6;
    text-decoration: none;
    transition: var(--transition);
}

.link:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.link-hover:hover {
    text-decoration: underline;
}

/* Text Utilities */
.text-xs {
    font-size: 12px;
}

.text-sm {
    font-size: 14px;
}

.text-lg {
    font-size: 18px;
}

.text-xl {
    font-size: 20px;
}

.text-2xl {
    font-size: 24px;
}

.text-3xl {
    font-size: 30px;
}

.text-primary {
    color: #3b82f6;
}

.text-primary-content {
    color: white;
}

.font-bold {
    font-weight: 700;
}

.font-semibold {
    font-weight: 600;
}

/* Background Utilities */
.bg-base-100 {
    background: white;
}

.bg-base-200 {
    background: #f8fafc;
}

.bg-base-300 {
    background: #e2e8f0;
}

.bg-gradient-to-br {
    background: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

.bg-gradient-to-r {
    background: linear-gradient(to right, var(--tw-gradient-stops));
}

.from-slate-50 {
    --tw-gradient-from: #f8fafc;
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(248, 250, 252, 0));
}

.to-blue-50 {
    --tw-gradient-to: #eff6ff;
}

.from-primary {
    --tw-gradient-from: #3b82f6;
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(59, 130, 246, 0));
}

.to-secondary {
    --tw-gradient-to: #6366f1;
}

/* Border Utilities */
.border {
    border-width: 1px;
}

.border-t {
    border-top-width: 1px;
}

.border-base-300 {
    border-color: #e2e8f0;
}

.rounded-2xl {
    border-radius: 16px;
}

.rounded-lg {
    border-radius: 8px;
}

/* Shadow Utilities */
.shadow {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.shadow-sm {
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.shadow-xl {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Size Utilities */
.min-h-screen {
    min-height: 100vh;
}

.max-w-md {
    max-width: auto !important;
}

.w-6 {
    width: 24px;
}

.h-6 {
    height: 24px;
}

.h-4 {
    height: 16px;
}

.w-4 {
    width: 16px;
}

/* Transition Utilities */
.transition-all {
    transition: all 0.3s ease;
}

.transition-colors {
    transition: color 0.3s ease;
}

.duration-300 {
    transition-duration: 300ms;
}

/* Hover Effects */
.hover\:shadow-lg:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.hover\:shadow-xl:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.hover\:text-primary:hover {
    color: #3b82f6;
}

/* Responsive Design */
@media (max-width: 768px) {
    .pollmaster-admin-page {
        padding: 16px;
    }
    
    .hero-content {
        padding: 32px 24px;
    }
    
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .grid {
        gap: 16px;
    }
    
    .md\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    .md\:grid-cols-4 {
        grid-template-columns: 1fr;
    }
    
    .lg\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    .lg\:grid-cols-4 {
        grid-template-columns: 1fr;
    }
    
    .flex-col {
        flex-direction: column;
    }
    
    .stats {
        flex-direction: column;
    }
    
    .stat {
        border-right: none;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .stat:last-child {
        border-bottom: none;
    }
}

@media (max-width: 480px) {
    .hero-content h1 {
        font-size: 1.8rem;
    }
    
    .btn {
        padding: 10px 16px;
        font-size: 13px;
    }
    
    .btn-lg {
        padding: 14px 24px;
        font-size: 15px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .bg-base-100 {
        background: #1f2937;
        color: #f9fafb;
    }
    
    .card {
        background: #374151;
        border-color: #4b5563;
    }
    
    .input, .select {
        background: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }
    
    .pollmaster-admin-page {
        background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    }
}
</style>

<script>
// Export function
function exportPollData() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "ID,Question,Option A,Option B,Type,Status,Total Votes,Created\n";
    
    // Add poll data
    <?php foreach ($polls as $poll): ?>
        <?php $poll_results = $database->get_poll_results($poll['id']); ?>
        csvContent += "<?php echo esc_js($poll['id']); ?>,";
        csvContent += "\"<?php echo esc_js($poll['question']); ?>\",";
        csvContent += "\"<?php echo esc_js($poll['option_a']); ?>\",";
        csvContent += "\"<?php echo esc_js($poll['option_b']); ?>\",";
        csvContent += "<?php echo esc_js(($poll['is_contest'] ? 'Contest' : ($poll['is_weekly'] ? 'Weekly' : 'Regular'))); ?>,";
        csvContent += "<?php echo esc_js(ucfirst($poll['status'])); ?>,";
        csvContent += "<?php echo esc_js($poll_results['total_votes']); ?>,";
        csvContent += "<?php echo esc_js(date('Y-m-d H:i:s', strtotime($poll['created_at']))); ?>\n";
    <?php endforeach; ?>
    
    // Create download link
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "polls_export_" + new Date().toISOString().slice(0,10) + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Checkbox functionality
jQuery(document).ready(function($) {
    // Select all checkboxes
    $('#select-all').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.poll-checkbox').prop('checked', isChecked);
    });
    
    // Update select all when individual checkboxes change
    $(document).on('change', '.poll-checkbox', function() {
        const totalCheckboxes = $('.poll-checkbox').length;
        const checkedCheckboxes = $('.poll-checkbox:checked').length;
        
        $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // Bulk actions functionality
    $('form').on('submit', function(e) {
        const selectedPolls = $('.poll-checkbox:checked').length;
        const bulkAction = $('select[name="bulk_action"]').val();
        
        if (bulkAction !== '-1' && selectedPolls === 0) {
            e.preventDefault();
            alert('Please select at least one poll to perform bulk actions.');
            return false;
        }
        
        if (bulkAction === 'delete' && selectedPolls > 0) {
            if (!confirm('Are you sure you want to delete the selected polls? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>