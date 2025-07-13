<?php
/**
 * PollMaster Share Class
 * 
 * Handles social sharing functionality with dynamic content generation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PollMaster_Share {
    
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new PollMaster_Database();
        add_action('wp_ajax_pollmaster_generate_share_content', array($this, 'generate_share_content'));
        add_action('wp_ajax_nopriv_pollmaster_generate_share_content', array($this, 'generate_share_content'));
        add_action('wp_head', array($this, 'add_og_meta_tags'));
    }
    
    /**
     * Generate share content based on user type and platform
     */
    public function generate_share_content() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pollmaster_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        $poll_id = intval($_POST['poll_id']);
        $platform = sanitize_text_field($_POST['platform']);
        $user_type = $this->get_user_type();
        
        $poll = $this->database->get_poll($poll_id);
        if (!$poll) {
            wp_send_json_error(array('message' => 'Poll not found.'));
        }
        
        $share_data = $this->create_share_content($poll, $platform, $user_type);
        
        // Track the share
        $this->database->record_share($poll_id, $platform, get_current_user_id());
        
        wp_send_json_success($share_data);
    }
    
    /**
     * Create share content based on user type
     */
    private function create_share_content($poll, $platform, $user_type) {
        $site_name = get_bloginfo('name');
        $poll_url = $this->get_poll_url($poll->id);
        $poll_image = $poll->image_url;
        
        // Base content
        $content = array(
            'url' => $poll_url,
            'image' => $poll_image,
            'hashtags' => $this->get_hashtags($poll, $platform)
        );
        
        // Generate content based on user type and platform
        switch ($user_type) {
            case 'admin':
                $content = array_merge($content, $this->get_admin_share_content($poll, $platform, $site_name));
                break;
                
            case 'manager':
                $content = array_merge($content, $this->get_manager_share_content($poll, $platform, $site_name));
                break;
                
            default:
                $content = array_merge($content, $this->get_user_share_content($poll, $platform, $site_name));
                break;
        }
        
        return $content;
    }
    
    /**
     * Get admin share content
     */
    private function get_admin_share_content($poll, $platform, $site_name) {
        $results = $this->database->get_poll_results($poll->id);
        $total_votes = $results['total_votes'];
        
        switch ($platform) {
            case 'facebook':
                return array(
                    'title' => "📊 New Poll Alert from {$site_name}!",
                    'description' => "We've just launched an exciting new poll: \"{$poll->question}\"\n\n" .
                                   ($poll->is_contest ? "🏆 This is a CONTEST with amazing prizes! " : "") .
                                   "Join {$total_votes} others who have already voted and make your voice heard!\n\n" .
                                   "Your opinion matters to us. Vote now and see real-time results!"
                );
                
            case 'twitter':
                $contest_text = $poll->is_contest ? "🏆 CONTEST ALERT! " : "";
                $vote_text = $total_votes > 0 ? "Join {$total_votes} voters! " : "Be the first to vote! ";
                
                return array(
                    'text' => "{$contest_text}📊 {$poll->question}\n\n{$vote_text}What's your take? 🤔\n\n#Poll #Community #{$site_name}"
                );
                
            case 'linkedin':
                return array(
                    'title' => "Community Poll: {$poll->question}",
                    'description' => "We're seeking insights from our community on an important topic.\n\n" .
                                   ($poll->is_contest ? "This poll is part of our community contest series. " : "") .
                                   "Your participation helps us understand our audience better and make informed decisions.\n\n" .
                                   "Join the conversation and share your perspective!"
                );
                
            case 'whatsapp':
                $contest_emoji = $poll->is_contest ? "🏆 " : "";
                return array(
                    'text' => "{$contest_emoji}Hey! We just posted a new poll on {$site_name}:\n\n" .
                             "📊 {$poll->question}\n\n" .
                             ($total_votes > 0 ? "{$total_votes} people have already voted! " : "Be among the first to vote! ") .
                             "What do you think? 🤔"
                );
                
            default:
                return array(
                    'title' => "New Poll: {$poll->question}",
                    'description' => "Join our community poll and share your opinion!"
                );
        }
    }
    
    /**
     * Get manager share content
     */
    private function get_manager_share_content($poll, $platform, $site_name) {
        $results = $this->database->get_poll_results($poll->id);
        $total_votes = $results['total_votes'];
        
        switch ($platform) {
            case 'facebook':
                return array(
                    'title' => "📊 Community Poll from {$site_name}",
                    'description' => "As a community manager, I'm excited to share this poll with you: \"{$poll->question}\"\n\n" .
                                   ($poll->is_contest ? "🎯 Special Contest Edition! " : "") .
                                   "We value your input and want to hear from everyone in our community.\n\n" .
                                   ($total_votes > 0 ? "{$total_votes} community members have already participated. " : "") .
                                   "Your voice matters - let's see what you think!"
                );
                
            case 'twitter':
                $contest_text = $poll->is_contest ? "🎯 Contest Poll: " : "📊 ";
                return array(
                    'text' => "{$contest_text}{$poll->question}\n\n" .
                             "Community managers want to hear from YOU! 🗣️\n\n" .
                             ($total_votes > 0 ? "Join {$total_votes} others! " : "Be the first! ") .
                             "#CommunityVoice #Poll"
                );
                
            case 'linkedin':
                return array(
                    'title' => "Community Insight Poll: {$poll->question}",
                    'description' => "As part of our community engagement initiative, we're conducting this poll to gather valuable insights.\n\n" .
                                   ($poll->is_contest ? "This is a special contest poll with exciting rewards for participants. " : "") .
                                   "Your professional perspective is important to us and helps shape our community direction.\n\n" .
                                   "Please take a moment to share your thoughts!"
                );
                
            case 'whatsapp':
                return array(
                    'text' => "👋 Hi! I'm sharing this community poll from {$site_name}:\n\n" .
                             "📊 {$poll->question}\n\n" .
                             ($poll->is_contest ? "🎯 It's a contest poll with prizes! " : "") .
                             "We'd love to get your input on this. " .
                             ($total_votes > 0 ? "{$total_votes} people have voted so far. " : "") .
                             "What's your opinion?"
                );
                
            default:
                return array(
                    'title' => "Community Poll: {$poll->question}",
                    'description' => "Share your thoughts on this community poll!"
                );
        }
    }
    
    /**
     * Get user share content
     */
    private function get_user_share_content($poll, $platform, $site_name) {
        $results = $this->database->get_poll_results($poll->id);
        $total_votes = $results['total_votes'];
        
        switch ($platform) {
            case 'facebook':
                return array(
                    'title' => "Check out this poll I found!",
                    'description' => "I just voted on this interesting poll: \"{$poll->question}\"\n\n" .
                                   ($poll->is_contest ? "🏆 It's actually a contest too! " : "") .
                                   "The results are really interesting so far. " .
                                   ($total_votes > 0 ? "{$total_votes} people have voted. " : "") .
                                   "What would you choose? I'd love to see your take on this!"
                );
                
            case 'twitter':
                $contest_text = $poll->is_contest ? "🏆 Contest poll! " : "";
                return array(
                    'text' => "{$contest_text}Just voted on: {$poll->question}\n\n" .
                             "Really interesting question! 🤔 " .
                             ($total_votes > 0 ? "{$total_votes} votes so far. " : "") .
                             "What would you pick? #Poll #Opinion"
                );
                
            case 'linkedin':
                return array(
                    'title' => "Interesting Poll: {$poll->question}",
                    'description' => "I came across this thought-provoking poll and wanted to share it with my network.\n\n" .
                                   ($poll->is_contest ? "It's part of a contest series, which makes it even more engaging. " : "") .
                                   "The question really made me think, and I'm curious about different perspectives on this topic.\n\n" .
                                   "Would love to hear your thoughts!"
                );
                
            case 'whatsapp':
                return array(
                    'text' => "Hey! 👋 Found this cool poll and thought you might be interested:\n\n" .
                             "📊 {$poll->question}\n\n" .
                             ($poll->is_contest ? "🏆 It's a contest poll btw! " : "") .
                             "I already voted and the results are pretty interesting. " .
                             "What do you think? Would love to know your opinion! 😊"
                );
                
            default:
                return array(
                    'title' => "Check out this poll: {$poll->question}",
                    'description' => "I found this interesting poll and wanted to share it!"
                );
        }
    }
    
    /**
     * Get user type (admin, manager, or user)
     */
    private function get_user_type() {
        if (!is_user_logged_in()) {
            return 'user';
        }
        
        $user = wp_get_current_user();
        
        if (in_array('administrator', $user->roles)) {
            return 'admin';
        }
        
        if (in_array('pollmaster_manager', $user->roles)) {
            return 'manager';
        }
        
        return 'user';
    }
    
    /**
     * Get poll URL
     */
    private function get_poll_url($poll_id) {
        return add_query_arg('poll_id', $poll_id, home_url());
    }
    
    /**
     * Get hashtags for platform
     */
    private function get_hashtags($poll, $platform) {
        $hashtags = array('Poll', 'Community', 'Vote');
        
        if ($poll->is_contest) {
            $hashtags[] = 'Contest';
            $hashtags[] = 'Win';
        }
        
        if ($poll->is_weekly) {
            $hashtags[] = 'WeeklyPoll';
        }
        
        // Platform-specific hashtags
        switch ($platform) {
            case 'twitter':
                return '#' . implode(' #', $hashtags);
            case 'linkedin':
                return implode(', ', array_map(function($tag) { return '#' . $tag; }, $hashtags));
            default:
                return $hashtags;
        }
    }
    
    /**
     * Add Open Graph meta tags for better sharing
     */
    public function add_og_meta_tags() {
        if (isset($_GET['poll_id'])) {
            $poll_id = intval($_GET['poll_id']);
            $poll = $this->database->get_poll($poll_id);
            
            if ($poll) {
                $site_name = get_bloginfo('name');
                $poll_url = $this->get_poll_url($poll_id);
                
                echo '<meta property="og:title" content="' . esc_attr($poll->question) . '" />' . "\n";
                echo '<meta property="og:description" content="Join the conversation and vote on this poll from ' . esc_attr($site_name) . '!" />' . "\n";
                echo '<meta property="og:url" content="' . esc_url($poll_url) . '" />' . "\n";
                echo '<meta property="og:type" content="website" />' . "\n";
                echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
                
                if ($poll->image_url) {
                    echo '<meta property="og:image" content="' . esc_url($poll->image_url) . '" />' . "\n";
                    echo '<meta property="og:image:width" content="1200" />' . "\n";
                    echo '<meta property="og:image:height" content="630" />' . "\n";
                }
                
                // Twitter Card tags
                echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
                echo '<meta name="twitter:title" content="' . esc_attr($poll->question) . '" />' . "\n";
                echo '<meta name="twitter:description" content="Vote on this poll from ' . esc_attr($site_name) . '!" />' . "\n";
                
                if ($poll->image_url) {
                    echo '<meta name="twitter:image" content="' . esc_url($poll->image_url) . '" />' . "\n";
                }
            }
        }
    }
    
    /**
     * Generate share URL for platform
     */
    public function get_share_url($poll_id, $platform, $content = array()) {
        $poll_url = $this->get_poll_url($poll_id);
        $encoded_url = urlencode($poll_url);
        
        switch ($platform) {
            case 'facebook':
                return "https://www.facebook.com/sharer/sharer.php?u={$encoded_url}";
                
            case 'twitter':
                $text = isset($content['text']) ? urlencode($content['text']) : '';
                $hashtags = isset($content['hashtags']) ? urlencode($content['hashtags']) : '';
                return "https://twitter.com/intent/tweet?text={$text}&url={$encoded_url}&hashtags={$hashtags}";
                
            case 'linkedin':
                $title = isset($content['title']) ? urlencode($content['title']) : '';
                $summary = isset($content['description']) ? urlencode($content['description']) : '';
                return "https://www.linkedin.com/sharing/share-offsite/?url={$encoded_url}&title={$title}&summary={$summary}";
                
            case 'whatsapp':
                $text = isset($content['text']) ? urlencode($content['text'] . ' ' . $poll_url) : $encoded_url;
                return "https://wa.me/?text={$text}";
                
            default:
                return $poll_url;
        }
    }
}