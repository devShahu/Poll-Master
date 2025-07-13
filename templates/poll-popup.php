<?php
/**
 * Poll Popup Template
 * 
 * This template can be overridden by copying it to yourtheme/pollmaster/poll-popup.php
 * 
 * @package PollMaster
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get popup data
$poll_id = $args['poll_id'] ?? 0;
$poll = $args['poll'] ?? null;
$auto_show = $args['auto_show'] ?? false;
$dismissible = $args['dismissible'] ?? true;
$show_share = $args['show_share'] ?? true;
$user_vote = $args['user_vote'] ?? null;
$results = $args['results'] ?? [];
$can_vote = $args['can_vote'] ?? true;
$is_contest = $poll['is_contest'] ?? false;
$poll_image = $args['poll_image'] ?? '';
$popup_id = 'pollmaster-popup-' . $poll_id;

if (!$poll) {
    return;
}

$total_votes = array_sum($results);
$poll_status = $poll['status'] ?? 'active';
$is_ended = $poll_status === 'ended' || (!empty($poll['end_date']) && strtotime($poll['end_date']) < time());
$options = json_decode($poll['options'], true) ?: [];
$has_voted = $user_vote !== null;
?>

<div id="<?php echo esc_attr($popup_id); ?>" class="pollmaster-popup" 
     data-poll-id="<?php echo esc_attr($poll_id); ?>" 
     data-auto-show="<?php echo $auto_show ? 'true' : 'false'; ?>"
     data-dismissible="<?php echo $dismissible ? 'true' : 'false'; ?>"
     style="display: none;">
    
    <div class="pollmaster-popup-overlay"></div>
    
    <div class="pollmaster-popup-container">
        <div class="pollmaster-popup-content">
            
            <!-- Popup Header -->
            <div class="pollmaster-popup-header">
                <?php if ($is_contest): ?>
                    <div class="popup-badge contest">
                        <img src="<?php echo plugins_url('/assets/images/trophy-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Trophy" class="badge-icon" style="width: 16px; height: 16px;">
                        <span class="badge-text">Contest</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($poll['is_weekly']): ?>
                    <div class="popup-badge weekly">
                        <span class="badge-icon">📅</span>
                        <span class="badge-text">Weekly Poll</span>
                    </div>
                <?php endif; ?>
                
                <button class="pollmaster-popup-close" aria-label="Close popup">
                    <span class="close-icon">×</span>
                </button>
            </div>
            
            <!-- Contest Image -->
            <?php if ($is_contest && $poll_image): ?>
                <div class="pollmaster-popup-image">
                    <img src="<?php echo esc_url($poll_image); ?>" alt="<?php echo esc_attr($poll['title']); ?>" />
                    <div class="image-overlay">
                        <div class="contest-label">
                            <img src="<?php echo plugins_url('/assets/images/trophy-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Trophy" class="contest-icon" style="width: 16px; height: 16px;">
                            Photo Contest
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Poll Content -->
            <div class="pollmaster-popup-body">
                <div class="poll-header">
                    <h2 class="poll-title"><?php echo esc_html($poll['title']); ?></h2>
                    
                    <?php if (!empty($poll['description'])): ?>
                        <p class="poll-description"><?php echo wp_kses_post($poll['description']); ?></p>
                    <?php endif; ?>
                    
                    <div class="poll-meta">
                        <span class="meta-item votes">
                            <span class="meta-icon">👥</span>
                            <span class="meta-text"><?php echo sprintf(_n('%d vote', '%d votes', $total_votes, 'pollmaster'), $total_votes); ?></span>
                        </span>
                        
                        <?php if (!empty($poll['end_date']) && !$is_ended): ?>
                            <span class="meta-item deadline">
                                <span class="meta-icon">⏰</span>
                                <span class="meta-text">Ends <?php echo esc_html(human_time_diff(time(), strtotime($poll['end_date']))); ?></span>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($is_ended): ?>
                            <span class="meta-item ended">
                                <span class="meta-icon">🔒</span>
                                <span class="meta-text">Poll Ended</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="poll-content">
                    <?php if ($has_voted || $is_ended): ?>
                        <!-- Show Results -->
                        <div class="pollmaster-popup-results">
                            <h3 class="results-title">
                                <?php if ($has_voted): ?>
                                    <span class="success-icon">✅</span>
                                    Thank you for voting!
                                <?php else: ?>
                                    <img src="<?php echo plugins_url('/assets/images/chart-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Chart" class="info-icon" style="width: 16px; height: 16px;">
                                    Final Results
                                <?php endif; ?>
                            </h3>
                            
                            <?php if (empty($options)): ?>
                                <p class="no-options">No poll options available.</p>
                            <?php else: ?>
                                <div class="options-results">
                                    <?php foreach ($options as $index => $option): 
                                        $votes = $results[$index] ?? 0;
                                        $percentage = $total_votes > 0 ? ($votes / $total_votes) * 100 : 0;
                                        $is_user_choice = $user_vote !== null && $user_vote == $index;
                                        $is_winning = $total_votes > 0 && $votes === max($results);
                                    ?>
                                        <div class="option-result <?php echo $is_user_choice ? 'user-choice' : ''; ?> <?php echo $is_winning ? 'winning-option' : ''; ?>">
                                            <div class="option-info">
                                                <span class="option-text"><?php echo esc_html($option); ?></span>
                                                <div class="option-indicators">
                                                    <?php if ($is_user_choice): ?>
                                                        <span class="user-indicator" title="Your choice">✓</span>
                                                    <?php endif; ?>
                                                    <?php if ($is_winning && $total_votes > 0): ?>
                                                        <span class="winner-indicator" title="Leading option">👑</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="option-stats">
                                                <span class="vote-count"><?php echo esc_html($votes); ?></span>
                                                <span class="vote-percentage"><?php echo esc_html(number_format($percentage, 1)); ?>%</span>
                                            </div>
                                            <div class="option-bar-container">
                                                <div class="option-bar" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($is_contest && $total_votes > 0): ?>
                                <div class="contest-info">
                                    <div class="contest-message">
                                        <img src="<?php echo plugins_url('/assets/images/target-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Target" class="contest-icon" style="width: 16px; height: 16px;">
                                        <p>Winner will be randomly selected from the most voted option!</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <!-- Show Voting Form -->
                        <div class="pollmaster-popup-voting">
                            <?php if (!$can_vote): ?>
                                <div class="vote-restriction">
                                    <div class="restriction-icon">🔐</div>
                                    <h3>Login Required</h3>
                                    <p>You need to be logged in to participate in this poll.</p>
                                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="login-button">
                                        Login to Vote
                                    </a>
                                </div>
                            <?php elseif (empty($options)): ?>
                                <div class="no-options">
                                    <div class="error-icon">❌</div>
                                    <p>No poll options available.</p>
                                </div>
                            <?php else: ?>
                                <form class="pollmaster-popup-vote-form" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                                    <div class="voting-instructions">
                                        <h3>Cast Your Vote</h3>
                                        <p>Choose your preferred option below:</p>
                                    </div>
                                    
                                    <div class="popup-options">
                                        <?php foreach ($options as $index => $option): ?>
                                            <label class="popup-option">
                                                <input type="radio" name="poll_option" value="<?php echo esc_attr($index); ?>" required>
                                                <span class="option-content">
                                                    <span class="option-radio"></span>
                                                    <span class="option-text"><?php echo esc_html($option); ?></span>
                                                    <span class="option-check">✓</span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="popup-vote-actions">
                                        <button type="submit" class="popup-vote-button">
                                            <span class="button-icon">🗳️</span>
                                            <span class="button-text">Submit Vote</span>
                                            <span class="button-loading">Submitting...</span>
                                        </button>
                                        
                                        <button type="button" class="popup-results-button" data-action="show-results">
                                            <img src="<?php echo plugins_url('/assets/images/chart-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Chart" class="button-icon" style="width: 16px; height: 16px;">
                                            <span class="button-text">View Results</span>
                                        </button>
                                    </div>
                                    
                                    <?php wp_nonce_field('pollmaster_vote', 'pollmaster_vote_nonce'); ?>
                                </form>
                                
                                <?php if ($is_contest): ?>
                                    <div class="contest-rules">
                                        <div class="rules-header">
                                            <span class="rules-icon">📋</span>
                                            <h4>Contest Rules</h4>
                                        </div>
                                        <ul class="rules-list">
                                            <li>Vote for your favorite option</li>
                                            <li>Winner randomly selected from most voted option</li>
                                            <?php if (!empty($poll['end_date'])): ?>
                                                <li>Contest ends on <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($poll['end_date']))); ?></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Popup Footer -->
            <div class="pollmaster-popup-footer">
                <?php if ($show_share): ?>
                    <div class="popup-share-section">
                        <span class="share-label">Share:</span>
                        <div class="popup-share-buttons">
                            <button class="popup-share-button facebook" data-platform="facebook" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Share on Facebook">
                                <img src="<?php echo plugins_url('/assets/images/facebook-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Facebook" class="share-icon">
                            </button>
                            
                            <button class="popup-share-button twitter" data-platform="twitter" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Share on Twitter">
                                <img src="<?php echo plugins_url('/assets/images/twitter-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Twitter" class="share-icon">
                            </button>
                            
                            <button class="popup-share-button whatsapp" data-platform="whatsapp" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Share on WhatsApp">
                                <img src="<?php echo plugins_url('/assets/images/whatsapp-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="WhatsApp" class="share-icon">
                            </button>
                            
                            <button class="popup-share-button copy" data-action="copy-link" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Copy Link">
                                <img src="<?php echo plugins_url('/assets/images/copy-icon.svg', POLLMASTER_PLUGIN_FILE); ?>" alt="Copy Link" class="share-icon">
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($dismissible && !$has_voted): ?>
                    <div class="popup-dismiss-section">
                        <button class="popup-dismiss-button" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                            <span class="dismiss-icon">👋</span>
                            <span class="dismiss-text">Don't show again</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Loading overlay -->
            <div class="popup-loading-overlay" style="display: none;">
                <div class="loading-spinner"></div>
                <div class="loading-text">Processing...</div>
            </div>
            
            <!-- Messages -->
            <div class="popup-messages"></div>
        </div>
    </div>
</div>

<?php
// Add popup-specific styles
$settings = get_option('pollmaster_settings', []);
$primary_color = $settings['primary_color'] ?? '#007cba';
$secondary_color = $settings['secondary_color'] ?? '#46b450';
$popup_bg = $settings['popup_background'] ?? '#ffffff';
$popup_overlay = $settings['popup_overlay'] ?? 'rgba(0, 0, 0, 0.8)';
?>

<style>
#<?php echo esc_attr($popup_id); ?> {
    --primary-color: <?php echo esc_attr($primary_color); ?>;
    --secondary-color: <?php echo esc_attr($secondary_color); ?>;
    --popup-bg: <?php echo esc_attr($popup_bg); ?>;
    --popup-overlay: <?php echo esc_attr($popup_overlay); ?>;
}

#<?php echo esc_attr($popup_id); ?> .pollmaster-popup-overlay {
    background: var(--popup-overlay);
}

#<?php echo esc_attr($popup_id); ?> .pollmaster-popup-content {
    background: var(--popup-bg);
}

#<?php echo esc_attr($popup_id); ?> .option-bar {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
}

#<?php echo esc_attr($popup_id); ?> .popup-vote-button {
    background: var(--primary-color);
}

#<?php echo esc_attr($popup_id); ?> .popup-vote-button:hover {
    background: var(--secondary-color);
}

#<?php echo esc_attr($popup_id); ?> .popup-option:hover {
    border-color: var(--primary-color);
}

#<?php echo esc_attr($popup_id); ?> .popup-option input:checked + .option-content {
    border-color: var(--primary-color);
    background: rgba(<?php echo esc_attr(implode(', ', sscanf($primary_color, '#%02x%02x%02x'))); ?>, 0.1);
}
</style>

<script>
// Auto-show popup if enabled
<?php if ($auto_show): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if popup should be shown
        const popup = document.getElementById('<?php echo esc_js($popup_id); ?>');
        const pollId = <?php echo intval($poll_id); ?>;
        
        // Check if user has dismissed this popup
        const dismissed = localStorage.getItem('pollmaster_dismissed_' + pollId);
        const hasVoted = <?php echo $has_voted ? 'true' : 'false'; ?>;
        
        if (!dismissed && !hasVoted && popup) {
            setTimeout(function() {
                if (typeof PollMasterFrontend !== 'undefined') {
                    PollMasterFrontend.showPopup(pollId);
                }
            }, 1000); // Show after 1 second
        }
    });
<?php endif; ?>
</script>