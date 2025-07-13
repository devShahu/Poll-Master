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

<div class="pollmaster-admin-page min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 relative overflow-hidden">
    <!-- Animated Background Particles -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-r from-purple-400 to-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-r from-yellow-400 to-red-400 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse animation-delay-2000"></div>
        <div class="absolute top-40 left-1/2 w-80 h-80 bg-gradient-to-r from-green-400 to-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse animation-delay-4000"></div>
    </div>
    <!-- Stunning Header with Advanced Gradient and Animations -->
    <div class="hero min-h-[60vh] bg-gradient-to-br from-violet-900 via-purple-900 to-indigo-900 relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-10 left-10 w-72 h-72 bg-gradient-to-r from-pink-400 to-violet-600 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
            <div class="absolute top-20 right-10 w-72 h-72 bg-gradient-to-r from-yellow-400 to-pink-600 rounded-full mix-blend-multiply filter blur-xl animate-pulse animation-delay-2000"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-gradient-to-r from-blue-400 to-indigo-600 rounded-full mix-blend-multiply filter blur-xl animate-pulse animation-delay-4000"></div>
        </div>
        
        <div class="hero-content text-center relative z-10">
            <div class="max-w-4xl">
                <!-- Animated Icon -->
                <div class="text-8xl mb-6 animate-bounce">
                    <img src="<?php echo plugins_url('/assets/images/chart-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Chart" class="inline-block transform hover:scale-110 transition-transform duration-300" style="width: 80px; height: 80px;">
                </div>
                
                <!-- Main Title with Gradient Text -->
                <h1 class="text-6xl md:text-7xl font-black mb-6 bg-gradient-to-r from-white via-blue-100 to-purple-200 bg-clip-text text-transparent">
                    PollMaster
                </h1>
                
                <!-- Subtitle -->
                <p class="text-2xl md:text-3xl font-light text-blue-100 mb-8 leading-relaxed">
                    Professional Polling & Contest Management
                </p>
                
                <!-- Feature Highlights -->
                <div class="flex flex-wrap justify-center gap-4 mb-8">
                    <div class="badge badge-lg bg-white/20 text-white border-white/30 backdrop-blur-sm">
                        <img src="<?php echo plugins_url('/assets/images/lightning-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Lightning" class="mr-2" style="width: 16px; height: 16px; filter: brightness(0) invert(1);"> Real-time Analytics
                    </div>
                    <div class="badge badge-lg bg-white/20 text-white border-white/30 backdrop-blur-sm">
                        <img src="<?php echo plugins_url('/assets/images/target-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Target" class="mr-2" style="width: 16px; height: 16px; filter: brightness(0) invert(1);"> Advanced Targeting
                    </div>
                    <div class="badge badge-lg bg-white/20 text-white border-white/30 backdrop-blur-sm">
                        <img src="<?php echo plugins_url('/assets/images/trophy-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Trophy" class="mr-2" style="width: 16px; height: 16px; filter: brightness(0) invert(1);"> Contest Management
                    </div>
                </div>
                
                <!-- CTA Button -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll')); ?>" class="btn btn-lg bg-gradient-to-r from-pink-500 to-violet-600 hover:from-pink-600 hover:to-violet-700 text-white border-none shadow-2xl hover:shadow-pink-500/25 transform hover:scale-105 transition-all duration-300">
                    <img src="<?php echo plugins_url('/assets/images/rocket-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Rocket" class="text-2xl mr-2" style="width: 24px; height: 24px; filter: brightness(0) invert(1);">
                    Create Your First Poll
                </a>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
    </div>

    <!-- Revolutionary Stats Dashboard -->
    <div class="container mx-auto px-6 -mt-0 relative z-20 mb-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Polls Card -->
            <div class="card bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-2xl hover:shadow-blue-500/25 transform hover:scale-105 transition-all duration-300">
                <div class="card-body items-center text-center relative overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-white rounded-full -translate-y-10 translate-x-10"></div>
                        <div class="absolute bottom-0 left-0 w-16 h-16 bg-white rounded-full translate-y-8 -translate-x-8"></div>
                    </div>
                    
                    <div class="text-5xl mb-4 animate-pulse">
                        <img src="<?php echo plugins_url('/assets/images/chart-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Chart" style="width: 48px; height: 48px;">
                    </div>
                    <div class="stat-title text-blue-100 text-sm font-medium">Total Polls</div>
                    <div class="stat-value text-4xl font-black mb-2"><?php echo esc_html($stats['total_polls'] ?? 0); ?></div>
                    <div class="stat-desc text-blue-200 flex items-center gap-1">
                        <span class="text-green-300">↗️</span>
                        <span>+<?php echo esc_html($stats['polls_this_month'] ?? 0); ?> this month</span>
                    </div>
                </div>
            </div>

            <!-- Total Votes Card -->
            <div class="card bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-2xl hover:shadow-emerald-500/25 transform hover:scale-105 transition-all duration-300">
                <div class="card-body items-center text-center relative overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-white rounded-full -translate-y-10 translate-x-10"></div>
                        <div class="absolute bottom-0 left-0 w-16 h-16 bg-white rounded-full translate-y-8 -translate-x-8"></div>
                    </div>
                    
                    <div class="text-5xl mb-4 animate-pulse">🗳️</div>
                    <div class="stat-title text-emerald-100 text-sm font-medium">Total Votes</div>
                    <div class="stat-value text-4xl font-black mb-2"><?php echo esc_html($stats['total_votes'] ?? 0); ?></div>
                    <div class="stat-desc text-emerald-200 flex items-center gap-1">
                        <span class="text-green-300">↗️</span>
                        <span>+<?php echo esc_html($stats['votes_today'] ?? 0); ?> today</span>
                    </div>
                </div>
            </div>

            <!-- Active Polls Card -->
            <div class="card bg-gradient-to-br from-purple-500 to-purple-700 text-white shadow-2xl hover:shadow-purple-500/25 transform hover:scale-105 transition-all duration-300">
                <div class="card-body items-center text-center relative overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-white rounded-full -translate-y-10 translate-x-10"></div>
                        <div class="absolute bottom-0 left-0 w-16 h-16 bg-white rounded-full translate-y-8 -translate-x-8"></div>
                    </div>
                    
                    <div class="text-5xl mb-4 animate-pulse">
                        <img src="<?php echo plugins_url('/assets/images/lightning-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Lightning" style="width: 48px; height: 48px;">
                    </div>
                    <div class="stat-title text-purple-100 text-sm font-medium">Active Polls</div>
                    <div class="stat-value text-4xl font-black mb-2"><?php echo esc_html($stats['active_polls'] ?? 0); ?></div>
                    <div class="stat-desc text-purple-200 flex items-center gap-1">
                        <img src="<?php echo plugins_url('/assets/images/trending-up-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Trending Up" class="text-yellow-300" style="width: 20px; height: 20px; filter: brightness(0) saturate(100%) invert(84%) sepia(84%) saturate(2500%) hue-rotate(2deg) brightness(104%) contrast(97%);">
                        <span><?php echo esc_html($stats['engagement_rate'] ?? 0); ?>% engagement</span>
                    </div>
                </div>
            </div>

            <!-- Contests Card -->
            <div class="card bg-gradient-to-br from-amber-500 to-amber-700 text-white shadow-2xl hover:shadow-amber-500/25 transform hover:scale-105 transition-all duration-300">
                <div class="card-body items-center text-center relative overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-white rounded-full -translate-y-10 translate-x-10"></div>
                        <div class="absolute bottom-0 left-0 w-16 h-16 bg-white rounded-full translate-y-8 -translate-x-8"></div>
                    </div>
                    
                    <div class="text-5xl mb-4 animate-pulse">
                        <img src="<?php echo plugins_url('/assets/images/trophy-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Trophy" style="width: 48px; height: 48px;">
                    </div>
                    <div class="stat-title text-amber-100 text-sm font-medium">Contests</div>
                    <div class="stat-value text-4xl font-black mb-2"><?php echo esc_html($stats['total_contests'] ?? 0); ?></div>
                    <div class="stat-desc text-amber-200 flex items-center gap-1">
                        <img src="<?php echo plugins_url('/assets/images/target-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Target" class="text-green-300" style="width: 20px; height: 20px; filter: brightness(0) saturate(100%) invert(87%) sepia(15%) saturate(1458%) hue-rotate(74deg) brightness(96%) contrast(86%);">
                        <span><?php echo esc_html($stats['active_contests'] ?? 0); ?> active</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Spectacular Quick Actions Section -->
    <div class="container mx-auto px-6 mb-16">
        <!-- Section Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-4 bg-white/80 backdrop-blur-sm rounded-full px-8 py-4 shadow-xl">
                <div class="text-4xl animate-bounce">
                    <img src="<?php echo plugins_url('/assets/images/lightning-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Lightning" style="width: 32px; height: 32px;">
                </div>
                <h2 class="text-3xl font-black bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">Quick Actions</h2>
            </div>
            <p class="text-gray-600 mt-4 text-lg">Everything you need to manage your polls efficiently</p>
        </div>
        
        <!-- Action Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Add New Poll -->
            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll')); ?>" class="group relative">
                <div class="card bg-white shadow-2xl hover:shadow-blue-500/25 transform hover:scale-105 transition-all duration-500 border-0 overflow-hidden">
                    <!-- Gradient Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-indigo-600/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <div class="card-body items-center text-center relative z-10">
                        <!-- Animated Icon -->
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                            <span class="text-3xl text-white">➕</span>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">Add New Poll</h3>
                        <p class="text-gray-600 mb-4 leading-relaxed">Create engaging polls and contests with our intuitive builder</p>
                        
                        <div class="flex gap-2">
                            <div class="badge badge-primary badge-outline">Create</div>
                            <div class="badge badge-ghost">New</div>
                        </div>
                        
                        <!-- Hover Arrow -->
                        <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Manage Polls -->
            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="group relative">
                <div class="card bg-white shadow-2xl hover:shadow-emerald-500/25 transform hover:scale-105 transition-all duration-500 border-0 overflow-hidden">
                    <!-- Gradient Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-green-600/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <div class="card-body items-center text-center relative z-10">
                        <!-- Animated Icon -->
                        <div class="w-20 h-20 bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                            <span class="text-3xl text-white">📝</span>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-emerald-600 transition-colors">Manage Polls</h3>
                        <p class="text-gray-600 mb-4 leading-relaxed">Edit, organize, and monitor all your polls in one place</p>
                        
                        <div class="flex gap-2">
                            <div class="badge badge-success badge-outline">Manage</div>
                            <div class="badge badge-ghost">Edit</div>
                        </div>
                        
                        <!-- Hover Arrow -->
                        <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Weekly Poll -->
            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls&type=weekly')); ?>" class="group relative">
                <div class="card bg-white shadow-2xl hover:shadow-purple-500/25 transform hover:scale-105 transition-all duration-500 border-0 overflow-hidden">
                    <!-- Gradient Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-violet-600/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <div class="card-body items-center text-center relative z-10">
                        <!-- Animated Icon -->
                        <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                            <span class="text-3xl text-white">📅</span>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-purple-600 transition-colors">Weekly Poll</h3>
                        <p class="text-gray-600 mb-4 leading-relaxed">Set up and manage your featured weekly polls</p>
                        
                        <div class="flex gap-2">
                            <div class="badge badge-secondary badge-outline">Weekly</div>
                            <div class="badge badge-ghost">Featured</div>
                        </div>
                        
                        <!-- Hover Arrow -->
                        <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Contests -->
            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls&type=contest')); ?>" class="group relative">
                <div class="card bg-white shadow-2xl hover:shadow-amber-500/25 transform hover:scale-105 transition-all duration-500 border-0 overflow-hidden">
                    <!-- Gradient Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-orange-600/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <div class="card-body items-center text-center relative z-10">
                        <!-- Animated Icon -->
                        <div class="w-20 h-20 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                            <img src="<?php echo plugins_url('/assets/images/trophy-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Trophy" class="text-3xl text-white" style="width: 32px; height: 32px; filter: brightness(0) invert(1);">
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-amber-600 transition-colors">Contests</h3>
                        <p class="text-gray-600 mb-4 leading-relaxed">Run exciting contests and manage winners</p>
                        
                        <div class="flex gap-2">
                            <div class="badge badge-warning badge-outline">Contest</div>
                            <div class="badge badge-ghost">Winners</div>
                        </div>
                        
                        <!-- Hover Arrow -->
                        <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Settings -->
            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-settings')); ?>" class="group relative">
                <div class="card bg-white shadow-2xl hover:shadow-gray-500/25 transform hover:scale-105 transition-all duration-500 border-0 overflow-hidden">
                    <!-- Gradient Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-500/10 to-slate-600/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <div class="card-body items-center text-center relative z-10">
                        <!-- Animated Icon -->
                        <div class="w-20 h-20 bg-gradient-to-br from-gray-500 to-slate-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                            <span class="text-3xl text-white">⚙️</span>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-gray-600 transition-colors">Settings</h3>
                        <p class="text-gray-600 mb-4 leading-relaxed">Configure plugin options and preferences</p>
                        
                        <div class="flex gap-2">
                            <div class="badge badge-neutral badge-outline">Config</div>
                            <div class="badge badge-ghost">Options</div>
                        </div>
                        
                        <!-- Hover Arrow -->
                        <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Export Data -->
            <button onclick="exportPollData()" class="group relative">
                <div class="card bg-white shadow-2xl hover:shadow-indigo-500/25 transform hover:scale-105 transition-all duration-500 border-0 overflow-hidden">
                    <!-- Gradient Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-blue-600/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <div class="card-body items-center text-center relative z-10">
                        <!-- Animated Icon -->
                        <div class="w-20 h-20 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                            <img src="<?php echo plugins_url('/assets/images/copy-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Share" class="text-3xl text-white" style="width: 32px; height: 32px; filter: brightness(0) invert(1);">
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-indigo-600 transition-colors">Export Data</h3>
                        <p class="text-gray-600 mb-4 leading-relaxed">Download comprehensive poll data and analytics</p>
                        
                        <div class="flex gap-2">
                            <div class="badge badge-info badge-outline">Export</div>
                            <div class="badge badge-ghost">Download</div>
                        </div>
                        
                        <!-- Hover Arrow -->
                        <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </button>
        </div>
    </div>

    <!-- Dashboard Content Grid -->
    <div class="container mx-auto px-6 relative z-10">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Recent Polls -->
        <div class="card bg-white/80 backdrop-blur-sm shadow-2xl border border-white/20 hover:shadow-3xl transition-all duration-500">
            <div class="card-body">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="card-title text-2xl">
                        <span class="text-3xl mr-2">📋</span>
                        Recent Polls
                    </h2>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="btn btn-outline btn-sm">
                        View All
                        <span class="ml-1">→</span>
                    </a>
                </div>
                
                <?php if (!empty($recent_polls)): ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_polls as $poll): ?>
                            <?php
                            $poll_id = $poll->id;
                            $title = $poll->question ?? $poll->title ?? '';
                            $status = $poll->status;
                            // Get actual vote count for this poll
                            $poll_results = $database->get_poll_results($poll_id);
                            $total_votes = $poll_results['total_votes'] ?? 0;
                            $created_date = date('M j, Y', strtotime($poll->created_at));
                            $is_contest = !empty($poll->is_contest);
                            $is_weekly = !empty($poll->is_weekly);
                            ?>
                            
                            <div class="card bg-base-200 hover:bg-base-300 transition-colors duration-200">
                                <div class="card-body p-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-lg mb-2">
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-edit-poll&poll_id=' . $poll_id)); ?>" class="link link-hover text-primary">
                                    <?php echo esc_html($title); ?>
                                </a>
                                            </h3>
                                            
                                            <div class="flex flex-wrap gap-3 text-sm text-base-content/70 mb-3">
                                                <span class="flex items-center gap-1">
                                                    <span>📅</span>
                                                    <?php echo esc_html($created_date); ?>
                                                </span>
                                                
                                                <span class="flex items-center gap-1">
                                                    <span>🗳️</span>
                                                    <?php echo esc_html($total_votes); ?> votes
                                                </span>
                                            </div>
                                            
                                            <div class="flex gap-2">
                                                <?php if ($status === 'active'): ?>
                                                    <div class="badge badge-success">Active</div>
                                                <?php elseif ($status === 'draft'): ?>
                                                    <div class="badge badge-warning">Draft</div>
                                                <?php else: ?>
                                                    <div class="badge badge-neutral"><?php echo esc_html(ucfirst($status)); ?></div>
                                                <?php endif; ?>
                                                
                                                <?php if ($is_contest): ?>
                                                    <div class="badge badge-secondary">
                                                        <img src="<?php echo plugins_url('/assets/images/trophy-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Trophy" class="mr-1" style="width: 16px; height: 16px;">
                                                        Contest
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($is_weekly): ?>
                                                    <div class="badge badge-info">
                                                        <span class="mr-1">📅</span>
                                                        Weekly
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="dropdown dropdown-end">
                                            <div tabindex="0" role="button" class="btn btn-ghost btn-sm">
                                                <span class="text-lg">⋮</span>
                                            </div>
                                            <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                                                <li>
                                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-all-polls&action=edit&poll_id=' . $poll_id)); ?>">
                                                    <span>✏️</span> Edit Poll
                                                </a>
                                                </li>
                                                <li>
                                                    <button class="view-results" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                                                        <img src="<?php echo plugins_url('/assets/images/chart-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Chart" style="width: 16px; height: 16px; margin-right: 4px;"> View Results
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="text-error" data-poll-id="<?php echo esc_attr($poll_id); ?>" onclick="deletePoll(<?php echo esc_attr($poll_id); ?>)">
                                                        <span>🗑️</span> Delete
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📋</div>
                        <h3 class="text-xl font-semibold mb-2">No Polls Yet</h3>
                        <p class="text-base-content/70 mb-4">Create your first poll to get started!</p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-all-polls&action=add')); ?>" class="btn btn-primary">
                            <span class="mr-2">➕</span>
                            Create Poll
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card bg-white/80 backdrop-blur-sm shadow-2xl border border-white/20 hover:shadow-3xl transition-all duration-500">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-6">
                    <span class="text-3xl mr-2">🔔</span>
                    Recent Activity
                </h2>
                
                <?php if (!empty($recent_votes)): ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_votes as $vote): ?>
                            <?php
                            $poll_title = $vote->question ?? 'Unknown Poll';
                            // Determine which option was voted for
                            $option_text = 'Unknown Option';
                            if ($vote->vote_option === 'option_a' && !empty($vote->option_a)) {
                                $option_text = $vote->option_a;
                            } elseif ($vote->vote_option === 'option_b' && !empty($vote->option_b)) {
                                $option_text = $vote->option_b;
                            }
                            $vote_time = human_time_diff(strtotime($vote->voted_at)) . ' ago';
                            $voter_name = $vote->voter_name ?? 'Anonymous';
                            ?>
                            
                            <div class="flex items-start gap-3 p-3 bg-base-200 rounded-lg hover:bg-base-300 transition-colors duration-200">
                                <div class="avatar placeholder">
                                    <div class="bg-primary text-primary-content rounded-full w-10 h-10">
                                        <span class="text-lg">🗳️</span>
                                    </div>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm">
                                        <span class="font-semibold text-primary"><?php echo esc_html($voter_name); ?></span> voted for 
                                        <span class="font-medium text-secondary">"<?php echo esc_html($option_text); ?>"</span> in 
                                        <span class="font-semibold"><?php echo esc_html($poll_title); ?></span>
                                    </div>
                                    
                                    <div class="text-xs text-base-content/60 mt-1">
                                        <?php echo esc_html($vote_time); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">🔔</div>
                        <h3 class="text-xl font-semibold mb-2">No Recent Activity</h3>
                        <p class="text-base-content/70">Activity will appear here when users start voting.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Additional Dashboard Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Performance Chart -->
        <div class="card bg-white/80 backdrop-blur-sm shadow-2xl border border-white/20 hover:shadow-3xl transition-all duration-500">
            <div class="card-body">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="card-title text-2xl">
                        <img src="<?php echo plugins_url('/assets/images/trending-up-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Trending Up" class="text-3xl mr-2" style="width: 32px; height: 32px;">
                        Performance Overview
                    </h2>
                    
                    <select class="select select-bordered select-sm" data-chart="performance">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
                
                <div class="bg-base-200 rounded-lg p-4 mb-4">
                    <canvas id="performance-chart" width="400" height="200"></canvas>
                </div>
                
                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-primary rounded-full"></div>
                        <span>Polls Created</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-secondary rounded-full"></div>
                        <span>Votes Cast</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-accent rounded-full"></div>
                        <span>Engagement Rate</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="card bg-white/80 backdrop-blur-sm shadow-2xl border border-white/20 hover:shadow-3xl transition-all duration-500">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-6">
                    <span class="text-3xl mr-2">⚙️</span>
                    System Status
                </h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div class="flex items-center gap-3 p-3 bg-success/10 rounded-lg">
                        <div class="text-2xl">✅</div>
                        <div>
                            <div class="font-semibold text-success">Database</div>
                            <div class="text-sm text-base-content/70">Connected</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 p-3 bg-success/10 rounded-lg">
                        <div class="text-2xl">✅</div>
                        <div>
                            <div class="font-semibold text-success">Cron Jobs</div>
                            <div class="text-sm text-base-content/70">Running</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 p-3 bg-success/10 rounded-lg">
                        <div class="text-2xl">✅</div>
                        <div>
                            <div class="font-semibold text-success">File Uploads</div>
                            <div class="text-sm text-base-content/70">Enabled</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 p-3 bg-warning/10 rounded-lg">
                        <div class="text-2xl">⚠️</div>
                        <div>
                            <div class="font-semibold text-warning">Cache</div>
                            <div class="text-sm text-base-content/70">Recommended</div>
                        </div>
                    </div>
                </div>
                
                <div class="divider">Plugin Information</div>
                
                <div class="grid grid-cols-1 gap-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-base-content/70">Version:</span>
                        <span class="font-semibold">1.0.0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/70">Database Version:</span>
                        <span class="font-semibold">1.0.0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/70">Last Update:</span>
                        <span class="font-semibold"><?php echo esc_html(date('M j, Y')); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Help & Support -->
    <div class="card bg-white/80 backdrop-blur-sm shadow-2xl border border-white/20 hover:shadow-3xl transition-all duration-500 mb-8">
        <div class="card-body">
            <h2 class="card-title text-2xl mb-6">
                <span class="text-3xl mr-2">❓</span>
                Need Help?
            </h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="#" class="btn btn-outline btn-lg flex-col h-auto py-4" data-action="show-documentation">
                    <span class="text-2xl mb-2">📖</span>
                    <span>Documentation</span>
                </a>
                
                <a href="#" class="btn btn-outline btn-lg flex-col h-auto py-4" data-action="show-tutorials">
                    <span class="text-2xl mb-2">🎥</span>
                    <span>Video Tutorials</span>
                </a>
                
                <a href="#" class="btn btn-outline btn-lg flex-col h-auto py-4" data-action="contact-support">
                    <img src="<?php echo plugins_url('/assets/images/whatsapp-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Chat" class="text-2xl mb-2" style="width: 24px; height: 24px;">
                    <span>Contact Support</span>
                </a>
                
                <a href="#" class="btn btn-outline btn-lg flex-col h-auto py-4" data-action="feature-request">
                    <span class="text-2xl mb-2">💡</span>
                    <span>Feature Request</span>
                </a>
            </div>
        </div>
        </div>
</div>
</div>
</div>

<style>
/* Revolutionary PollMaster Dashboard Design */
.pollmaster-admin-dashboard {
    padding: 20px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    position: relative;
}

.pollmaster-admin-dashboard::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
    pointer-events: none;
    z-index: 0;
}

.pollmaster-admin-dashboard > * {
    position: relative;
    z-index: 1;
}

/* Enhanced Hero Section */
.hero-section {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 25px;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
    pointer-events: none;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

.hero-content h1 {
    color: white;
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 15px;
    text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-content p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.2rem;
    margin-bottom: 25px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Spectacular Stats Cards */
.stats-grid .card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.15) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}

.stats-grid .card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.stats-grid .card:hover::before {
    left: 100%;
}

.stats-grid .card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.25);
}

.stat-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    margin-bottom: 5px;
}

.stat-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-change {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin-top: 8px;
}

/* Enhanced Quick Actions */
.quick-actions .card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 18px;
    transition: all 0.3s ease;
}

.quick-actions .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.2) 100%);
}

.action-btn {
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 12px 24px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(255, 107, 107, 0.6);
    color: white;
}

/* Recent Activity Cards */
.recent-polls .card,
.recent-votes .card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 18px;
}

.card-title {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Custom utility classes */
.text-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Enhanced Animation Delays */
.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

/* Spectacular Shadow Effects */
.shadow-3xl {
    box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1);
}

/* Glassmorphism Enhancement */
.card {
    backdrop-filter: blur(16px) saturate(180%);
    -webkit-backdrop-filter: blur(16px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.125);
}

/* Enhanced Hover Effects */
.card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 50px 100px -20px rgba(50, 50, 93, 0.25), 0 30px 60px -30px rgba(0, 0, 0, 0.3);
}

/* Pulse Animation Enhancement */
@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
    }
    50% {
        box-shadow: 0 0 30px rgba(102, 126, 234, 0.5);
    }
}

.hover-glow:hover {
    animation: pulse-glow 2s infinite;
}

/* Floating Animation for Background Elements */
@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    33% {
        transform: translateY(-30px) rotate(120deg);
    }
    66% {
        transform: translateY(-20px) rotate(240deg);
    }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

/* Enhanced Button Styles */
.btn {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
}

/* Enhanced Stats Cards */
.stat-value {
    background: linear-gradient(135deg, currentColor 0%, rgba(255, 255, 255, 0.8) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

/* Improved Typography */
h1, h2, h3 {
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Enhanced Badge Styles */
.badge {
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Additional custom styles can be added here if needed */

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
// Enhanced Dashboard JavaScript with Spectacular Effects
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard with enhanced features
    initializeDashboard();
    
    function initializeDashboard() {
        // Initialize chart
        initializePerformanceChart();
        
        // Bind event handlers
        bindEventHandlers();
        
        // Add spectacular visual effects
        initializeVisualEffects();
        
        // Auto-refresh data every 5 minutes
        setInterval(refreshDashboardData, 300000);
        
        // Initialize real-time updates
        initializeRealTimeUpdates();
    }
    
    function initializeVisualEffects() {
        // Add floating animation to background particles
        const particles = document.querySelectorAll('.absolute.w-80.h-80');
        particles.forEach((particle, index) => {
            particle.style.animationDelay = `${index * 2}s`;
            particle.classList.add('animate-float');
        });
        
        // Add stagger animation to cards
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.style.animation = 'fadeInUp 0.6s ease-out forwards';
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
        });
        
        // Add CSS for fadeInUp animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    function initializeRealTimeUpdates() {
        // Simulate real-time stat updates with smooth animations
        setInterval(() => {
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach(stat => {
                stat.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    stat.style.transform = 'scale(1)';
                }, 200);
            });
        }, 30000);
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