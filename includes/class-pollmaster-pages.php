<?php
/**
 * PollMaster Pages Class
 * 
 * Handles frontend pages for user poll creation and voting
 */

if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Pages {
    private $database;
    
    public function __construct() {
        $this->database = new PollMaster_Database();
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('pollmaster_create_poll', array($this, 'render_create_poll_form'));
        add_shortcode('pollmaster_user_dashboard', array($this, 'render_user_dashboard'));
        add_shortcode('pollmaster_poll_list', array($this, 'render_poll_list'));
        add_action('wp_ajax_create_user_poll', array($this, 'handle_create_poll'));
        add_action('wp_ajax_nopriv_create_user_poll', array($this, 'handle_create_poll'));
    }
    
    public function init() {
        // Add rewrite rules for custom pages
        add_rewrite_rule('^polls/create/?$', 'index.php?pollmaster_page=create', 'top');
        add_rewrite_rule('^polls/dashboard/?$', 'index.php?pollmaster_page=dashboard', 'top');
        add_rewrite_rule('^polls/browse/?$', 'index.php?pollmaster_page=browse', 'top');
        add_rewrite_rule('^polls/all/?$', 'index.php?pollmaster_page=all', 'top');
        add_rewrite_rule('^polls/past/?$', 'index.php?pollmaster_page=past', 'top');
        
        // Add query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Template redirect
        add_action('template_redirect', array($this, 'template_redirect'));
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'pollmaster_page';
        return $vars;
    }
    
    public function template_redirect() {
        $page = get_query_var('pollmaster_page');
        
        if ($page) {
            switch ($page) {
                case 'create':
                    $this->load_template('create-poll');
                    break;
                case 'dashboard':
                    $this->load_template('user-dashboard');
                    break;
                case 'browse':
                    $this->load_template('poll-list');
                    break;
                case 'all':
                    $this->load_template('all-polls');
                    break;
                case 'past':
                    $this->load_template('past-polls');
                    break;
            }
        }
    }
    
    private function load_template($template) {
        // Load header
        get_header();
        
        echo '<div class="pollmaster-page-wrapper" style="max-width: 1200px; margin: 0 auto; padding: 2rem;">';
        
        switch ($template) {
            case 'create-poll':
                $this->render_create_poll_page();
                break;
            case 'user-dashboard':
                $this->render_user_dashboard_page();
                break;
            case 'poll-list':
                $this->render_poll_list_page();
                break;
            case 'all-polls':
                $this->render_all_polls_page();
                break;
            case 'past-polls':
                $this->render_past_polls_page();
                break;
        }
        
        echo '</div>';
        
        // Load footer
        get_footer();
        exit;
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('pollmaster-pages', POLLMASTER_PLUGIN_URL . 'assets/js/pollmaster-pages.js', array('jquery'), POLLMASTER_VERSION, true);
        wp_localize_script('pollmaster-pages', 'pollmaster_pages_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pollmaster_pages_nonce'),
            'strings' => array(
                'creating' => __('Creating poll...', 'pollmaster'),
                'success' => __('Poll created successfully!', 'pollmaster'),
                'error' => __('Error creating poll. Please try again.', 'pollmaster'),
                'login_required' => __('Please log in to create polls.', 'pollmaster')
            )
        ));
    }
    
    public function render_create_poll_page() {
        ?>
        <div class="hero min-h-screen bg-base-200" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 80vh; display: flex; align-items: center; justify-content: center; border-radius: 1rem; margin-bottom: 2rem;">
            <div class="hero-content text-center">
                <div class="max-w-md" style="color: white;">
                    <h1 class="text-5xl font-bold" style="font-size: 3rem; font-weight: bold; margin-bottom: 1rem;">Create Your Poll</h1>
                    <p class="py-6" style="font-size: 1.125rem; margin-bottom: 2rem;">Share your questions with the world and get instant feedback from the community.</p>
                    <a href="#create-form" class="btn btn-primary" style="background: white; color: #667eea; padding: 0.75rem 2rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600;">Get Started</a>
                </div>
            </div>
        </div>
        
        <div id="create-form" class="card bg-base-100 shadow-xl" style="background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h2 class="card-title text-center" style="font-size: 2rem; font-weight: bold; text-align: center; margin-bottom: 2rem; color: #1f2937;">Create New Poll</h2>
                
                <?php echo do_shortcode('[pollmaster_create_poll]'); ?>
            </div>
        </div>
        <?php
    }
    
    public function render_user_dashboard_page() {
        if (!is_user_logged_in()) {
            ?>
            <div class="alert alert-warning" style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 0.5rem; padding: 1rem; text-align: center;">
                <p><?php _e('Please log in to access your dashboard.', 'pollmaster'); ?></p>
                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-primary" style="background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none; margin-top: 1rem; display: inline-block;">
                    <?php _e('Log In', 'pollmaster'); ?>
                </a>
            </div>
            <?php
            return;
        }
        
        ?>
        <div class="hero bg-base-200 mb-8" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 3rem; border-radius: 1rem; color: white; text-align: center;">
            <div class="hero-content">
                <div>
                    <h1 class="text-4xl font-bold" style="font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">Welcome back, <?php echo esc_html(wp_get_current_user()->display_name); ?>!</h1>
                    <p class="text-lg" style="font-size: 1.125rem;">Manage your polls and see how they're performing.</p>
                </div>
            </div>
        </div>
        
        <?php echo do_shortcode('[pollmaster_user_dashboard]'); ?>
        <?php
    }
    
    public function render_poll_list_page() {
        ?>
        <div class="hero bg-base-200 mb-8" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 3rem; border-radius: 1rem; color: white; text-align: center;">
            <div class="hero-content">
                <div>
                    <h1 class="text-4xl font-bold" style="font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">Browse Polls</h1>
                    <p class="text-lg" style="font-size: 1.125rem;">Discover and vote on polls from the community.</p>
                </div>
            </div>
        </div>
        
        <?php echo do_shortcode('[pollmaster_poll_list]'); ?>
        <?php
    }
    
    public function render_create_poll_form($atts) {
        ob_start();
        
        if (!is_user_logged_in()) {
            ?>
            <div class="alert alert-warning" style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 0.5rem; padding: 1rem; text-align: center;">
                <p><?php _e('Please log in to create polls.', 'pollmaster'); ?></p>
                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-primary" style="background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none; margin-top: 1rem; display: inline-block;">
                    <?php _e('Log In', 'pollmaster'); ?>
                </a>
            </div>
            <?php
            return ob_get_clean();
        }
        
        ?>
        <form id="pollmaster-create-form" class="space-y-6" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="form-control">
                <label class="label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">
                    <span class="label-text" style="color: #374151;"><?php _e('Poll Question', 'pollmaster'); ?> *</span>
                </label>
                <textarea name="question" class="textarea textarea-bordered" placeholder="<?php _e('What would you like to ask?', 'pollmaster'); ?>" required style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem; resize: vertical; min-height: 100px;"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-control">
                    <label class="label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">
                        <span class="label-text" style="color: #374151;"><?php _e('Option A', 'pollmaster'); ?> *</span>
                    </label>
                    <input type="text" name="option_a" class="input input-bordered" placeholder="<?php _e('First option', 'pollmaster'); ?>" required style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem;">
                </div>
                
                <div class="form-control">
                    <label class="label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">
                        <span class="label-text" style="color: #374151;"><?php _e('Option B', 'pollmaster'); ?> *</span>
                    </label>
                    <input type="text" name="option_b" class="input input-bordered" placeholder="<?php _e('Second option', 'pollmaster'); ?>" required style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem;">
                </div>
            </div>
            
            <div class="form-control">
                <label class="label" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">
                    <span class="label-text" style="color: #374151;"><?php _e('Poll Image (Optional)', 'pollmaster'); ?></span>
                </label>
                <input type="file" name="poll_image" class="file-input file-input-bordered" accept="image/*" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 0.5rem;">
            </div>
            
            <div class="form-control">
                <label class="label cursor-pointer" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="is_contest" class="checkbox" style="width: 1.25rem; height: 1.25rem;">
                    <span class="label-text" style="color: #374151;"><?php _e('Submit as contest entry', 'pollmaster'); ?></span>
                </label>
            </div>
            
            <div class="form-control mt-6">
                <button type="submit" class="btn btn-primary" style="background: #3b82f6; color: white; padding: 0.75rem 2rem; border-radius: 0.5rem; border: none; cursor: pointer; font-weight: 600; width: 100%;">
                    <?php _e('Create Poll', 'pollmaster'); ?>
                </button>
            </div>
        </form>
        
        <div id="poll-creation-result" class="mt-4" style="margin-top: 1rem;"></div>
        <?php
        
        return ob_get_clean();
    }
    
    public function render_user_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'pollmaster') . '</p>';
        }
        
        ob_start();
        
        $user_id = get_current_user_id();
        global $wpdb;
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        
        // Get user's polls
        $user_polls = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, COUNT(v.id) as vote_count 
             FROM $polls_table p 
             LEFT JOIN $votes_table v ON p.id = v.poll_id 
             WHERE p.user_id = %d 
             GROUP BY p.id 
             ORDER BY p.created_at DESC",
            $user_id
        ));
        
        // Get user stats
        $total_polls = count($user_polls);
        $total_votes = array_sum(array_column($user_polls, 'vote_count'));
        $active_polls = count(array_filter($user_polls, function($poll) { return $poll->status === 'active'; }));
        
        ?>
        <!-- User Stats -->
        <div class="stats shadow mb-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; background: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="stat" style="text-align: center; padding: 1rem;">
                <div class="stat-figure text-primary" style="font-size: 2rem; color: #3b82f6; margin-bottom: 0.5rem;">📊</div>
                <div class="stat-title" style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;"><?php _e('My Polls', 'pollmaster'); ?></div>
                <div class="stat-value" style="font-size: 2rem; font-weight: bold; color: #1f2937;"><?php echo $total_polls; ?></div>
            </div>
            
            <div class="stat" style="text-align: center; padding: 1rem;">
                <div class="stat-figure text-secondary" style="font-size: 2rem; color: #10b981; margin-bottom: 0.5rem;">✅</div>
                <div class="stat-title" style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;"><?php _e('Active', 'pollmaster'); ?></div>
                <div class="stat-value" style="font-size: 2rem; font-weight: bold; color: #1f2937;"><?php echo $active_polls; ?></div>
            </div>
            
            <div class="stat" style="text-align: center; padding: 1rem;">
                <div class="stat-figure text-accent" style="font-size: 2rem; color: #f59e0b; margin-bottom: 0.5rem;">🗳️</div>
                <div class="stat-title" style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;"><?php _e('Total Votes', 'pollmaster'); ?></div>
                <div class="stat-value" style="font-size: 2rem; font-weight: bold; color: #1f2937;"><?php echo $total_votes; ?></div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card bg-base-100 shadow-xl mb-6" style="background: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h2 class="card-title" style="font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem;"><?php _e('Quick Actions', 'pollmaster'); ?></h2>
                <div class="card-actions" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="/polls/create" class="btn btn-primary" style="background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none;">
                        <?php _e('Create New Poll', 'pollmaster'); ?>
                    </a>
                    <a href="/polls/browse" class="btn btn-secondary" style="background: #6b7280; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none;">
                        <?php _e('Browse Polls', 'pollmaster'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- User's Polls -->
        <div class="card bg-base-100 shadow-xl" style="background: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h2 class="card-title" style="font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem;"><?php _e('My Polls', 'pollmaster'); ?></h2>
                
                <?php if ($user_polls): ?>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full" style="width: 100%;">
                            <thead>
                                <tr style="background: #f9fafb;">
                                    <th style="padding: 0.75rem; font-weight: 600;"><?php _e('Question', 'pollmaster'); ?></th>
                                    <th style="padding: 0.75rem; font-weight: 600;"><?php _e('Status', 'pollmaster'); ?></th>
                                    <th style="padding: 0.75rem; font-weight: 600;"><?php _e('Votes', 'pollmaster'); ?></th>
                                    <th style="padding: 0.75rem; font-weight: 600;"><?php _e('Created', 'pollmaster'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_polls as $poll): ?>
                                <tr>
                                    <td style="padding: 0.75rem;"><?php echo esc_html(wp_trim_words($poll->question, 10)); ?></td>
                                    <td style="padding: 0.75rem;">
                                        <span class="badge <?php echo $poll->status === 'active' ? 'badge-success' : ($poll->status === 'pending' ? 'badge-warning' : 'badge-error'); ?>" style="padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; <?php echo $poll->status === 'active' ? 'background: #10b981; color: white;' : ($poll->status === 'pending' ? 'background: #f59e0b; color: white;' : 'background: #ef4444; color: white;'); ?>">
                                            <?php echo ucfirst($poll->status); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem;"><?php echo $poll->vote_count; ?></td>
                                    <td style="padding: 0.75rem;"><?php echo date('M j, Y', strtotime($poll->created_at)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8" style="text-align: center; padding: 2rem;">
                        <p style="color: #6b7280; margin-bottom: 1rem;"><?php _e('You haven\'t created any polls yet.', 'pollmaster'); ?></p>
                        <a href="/polls/create" class="btn btn-primary" style="background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none;">
                            <?php _e('Create Your First Poll', 'pollmaster'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    public function render_poll_list($atts) {
        ob_start();
        
        global $wpdb;
        $polls_table = $wpdb->prefix . 'pollmaster_polls';
        $votes_table = $wpdb->prefix . 'pollmaster_votes';
        
        // Get active polls
        $polls = $wpdb->get_results("
            SELECT p.*, COUNT(v.id) as vote_count, u.display_name as author_name
            FROM $polls_table p 
            LEFT JOIN $votes_table v ON p.id = v.poll_id 
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE p.status = 'active' 
            GROUP BY p.id 
            ORDER BY p.created_at DESC 
            LIMIT 20
        ");
        
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($polls as $poll): ?>
                <div class="card bg-base-100 shadow-xl" style="background: white; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden;">
                    <?php if ($poll->image_url): ?>
                        <figure style="height: 200px; overflow: hidden;">
                            <img src="<?php echo esc_url($poll->image_url); ?>" alt="Poll image" style="width: 100%; height: 100%; object-fit: cover;">
                        </figure>
                    <?php endif; ?>
                    
                    <div class="card-body" style="padding: 1.5rem;">
                        <h2 class="card-title" style="font-size: 1.125rem; font-weight: bold; margin-bottom: 1rem; line-height: 1.4;">
                            <?php echo esc_html($poll->question); ?>
                        </h2>
                        
                        <div class="poll-options" style="margin-bottom: 1rem;">
                            <div class="option" style="background: #f3f4f6; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 0.5rem;">
                                <strong>A:</strong> <?php echo esc_html($poll->option_a); ?>
                            </div>
                            <div class="option" style="background: #f3f4f6; padding: 0.75rem; border-radius: 0.375rem;">
                                <strong>B:</strong> <?php echo esc_html($poll->option_b); ?>
                            </div>
                        </div>
                        
                        <div class="poll-meta" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">
                            <span><?php echo $poll->vote_count; ?> votes</span>
                            <span>by <?php echo esc_html($poll->author_name); ?></span>
                        </div>
                        
                        <div class="card-actions justify-end" style="display: flex; justify-content: flex-end;">
                            <button class="btn btn-primary btn-sm vote-btn" data-poll-id="<?php echo $poll->id; ?>" style="background: #3b82f6; color: white; padding: 0.375rem 0.75rem; border-radius: 0.375rem; border: none; cursor: pointer; font-size: 0.875rem;">
                                <?php _e('Vote Now', 'pollmaster'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($polls)): ?>
            <div class="text-center py-12" style="text-align: center; padding: 3rem;">
                <p style="color: #6b7280; font-size: 1.125rem;"><?php _e('No active polls found.', 'pollmaster'); ?></p>
            </div>
        <?php endif; ?>
        <?php
        
        return ob_get_clean();
    }
    
    public function handle_create_poll() {
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_pages_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'pollmaster')));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in to create polls.', 'pollmaster')));
        }
        
        $data = array(
            'user_id' => get_current_user_id(),
            'question' => sanitize_textarea_field($_POST['question']),
            'option_a' => sanitize_text_field($_POST['option_a']),
            'option_b' => sanitize_text_field($_POST['option_b']),
            'is_contest' => isset($_POST['is_contest']) ? 1 : 0,
            'status' => 'pending' // Require admin approval
        );
        
        // Handle image upload if provided
        if (isset($_FILES['poll_image']) && $_FILES['poll_image']['error'] === UPLOAD_ERR_OK) {
            $image_url = $this->handle_image_upload($_FILES['poll_image']);
            if ($image_url) {
                $data['image_url'] = $image_url;
            }
        }
        
        $poll_id = $this->database->create_poll($data);
        
        if ($poll_id) {
            wp_send_json_success(array(
                'message' => __('Poll created successfully! It will be reviewed by an administrator.', 'pollmaster'),
                'poll_id' => $poll_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to create poll. Please try again.', 'pollmaster')));
        }
    }
    
    private function handle_image_upload($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            return $movefile['url'];
        }
        
        return false;
    }
    
    public function render_all_polls_page() {
        ?>
        <div class="hero bg-base-200 mb-8" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 3rem; border-radius: 1rem; color: white; text-align: center;">
            <div class="hero-content">
                <div>
                    <h1 class="text-4xl font-bold" style="font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">All Polls</h1>
                    <p class="text-lg" style="font-size: 1.125rem;">Discover and participate in all available polls from our community.</p>
                </div>
            </div>
        </div>
        
        <?php echo do_shortcode('[pollmaster_poll_list status="all"]'); ?>
        <?php
    }
    
    public function render_past_polls_page() {
        ?>
        <div class="hero bg-base-200 mb-8" style="background: linear-gradient(135deg, #6b7280 0%, #374151 100%); padding: 3rem; border-radius: 1rem; color: white; text-align: center;">
            <div class="hero-content">
                <div>
                    <h1 class="text-4xl font-bold" style="font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">Past Polls</h1>
                    <p class="text-lg" style="font-size: 1.125rem;">Browse through completed and archived polls to see historical results.</p>
                </div>
            </div>
        </div>
        
        <?php echo do_shortcode('[pollmaster_poll_list status="past"]'); ?>
        <?php
    }
}

// Initialize the pages class
new PollMaster_Pages();