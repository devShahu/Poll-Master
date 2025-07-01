<?php
/**
 * Poll Display Template
 * 
 * This template can be overridden by copying it to yourtheme/pollmaster/poll-display.php
 * 
 * @package PollMaster
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get poll data
$poll_id = $args['poll_id'] ?? 0;
$poll = $args['poll'] ?? null;
$show_results = $args['show_results'] ?? false;
$show_share = $args['show_share'] ?? true;
$user_vote = $args['user_vote'] ?? null;
$results = $args['results'] ?? [];
$can_vote = $args['can_vote'] ?? true;
$is_contest = $poll['is_contest'] ?? false;
$poll_image = $args['poll_image'] ?? '';

if (!$poll) {
    return;
}

$total_votes = array_sum($results);
$poll_status = $poll['status'] ?? 'active';
$is_ended = $poll_status === 'ended' || (!empty($poll['end_date']) && strtotime($poll['end_date']) < time());
$options = json_decode($poll['options'], true) ?: [];
?>

<div class="pollmaster-poll" data-poll-id="<?php echo esc_attr($poll_id); ?>" data-contest="<?php echo $is_contest ? 'true' : 'false'; ?>">
    <?php if ($is_contest && $poll_image): ?>
        <div class="pollmaster-poll-image">
            <img src="<?php echo esc_url($poll_image); ?>" alt="<?php echo esc_attr($poll['title']); ?>" />
            <div class="pollmaster-contest-badge">
                <span class="contest-icon">🏆</span>
                Contest
            </div>
        </div>
    <?php endif; ?>
    
    <div class="pollmaster-poll-header">
        <h3 class="pollmaster-poll-title"><?php echo esc_html($poll['title']); ?></h3>
        
        <?php if (!empty($poll['description'])): ?>
            <p class="pollmaster-poll-description"><?php echo wp_kses_post($poll['description']); ?></p>
        <?php endif; ?>
        
        <div class="pollmaster-poll-meta">
            <?php if ($is_contest): ?>
                <span class="pollmaster-meta-item contest">
                    <span class="meta-icon">🏆</span>
                    Photo Contest
                </span>
            <?php endif; ?>
            
            <?php if ($poll['is_weekly']): ?>
                <span class="pollmaster-meta-item weekly">
                    <span class="meta-icon">📅</span>
                    Weekly Poll
                </span>
            <?php endif; ?>
            
            <span class="pollmaster-meta-item votes">
                <span class="meta-icon">👥</span>
                <?php echo sprintf(_n('%d vote', '%d votes', $total_votes, 'pollmaster'), $total_votes); ?>
            </span>
            
            <?php if (!empty($poll['end_date']) && !$is_ended): ?>
                <span class="pollmaster-meta-item deadline">
                    <span class="meta-icon">⏰</span>
                    Ends: <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($poll['end_date']))); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($is_ended): ?>
                <span class="pollmaster-meta-item ended">
                    <span class="meta-icon">🔒</span>
                    Poll Ended
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="pollmaster-poll-content">
        <?php if ($show_results || $user_vote !== null || $is_ended): ?>
            <!-- Show Results -->
            <div class="pollmaster-results">
                <h4 class="pollmaster-results-title">Results</h4>
                
                <?php if (empty($options)): ?>
                    <p class="pollmaster-no-options">No poll options available.</p>
                <?php else: ?>
                    <div class="pollmaster-options-results">
                        <?php foreach ($options as $index => $option): 
                            $votes = $results[$index] ?? 0;
                            $percentage = $total_votes > 0 ? ($votes / $total_votes) * 100 : 0;
                            $is_user_choice = $user_vote !== null && $user_vote == $index;
                            $is_winning = $total_votes > 0 && $votes === max($results);
                        ?>
                            <div class="pollmaster-option-result <?php echo $is_user_choice ? 'user-choice' : ''; ?> <?php echo $is_winning ? 'winning-option' : ''; ?>">
                                <div class="option-header">
                                    <span class="option-text"><?php echo esc_html($option); ?></span>
                                    <span class="option-stats">
                                        <span class="vote-count"><?php echo esc_html($votes); ?></span>
                                        <span class="vote-percentage">(<?php echo esc_html(number_format($percentage, 1)); ?>%)</span>
                                    </span>
                                    <?php if ($is_user_choice): ?>
                                        <span class="user-choice-indicator" title="Your choice">✓</span>
                                    <?php endif; ?>
                                    <?php if ($is_winning && $total_votes > 0): ?>
                                        <span class="winning-indicator" title="Leading option">👑</span>
                                    <?php endif; ?>
                                </div>
                                <div class="option-bar-container">
                                    <div class="option-bar" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($total_votes > 0): ?>
                    <div class="pollmaster-total-votes">
                        Total votes: <strong><?php echo esc_html($total_votes); ?></strong>
                    </div>
                <?php endif; ?>
                
                <?php if ($is_contest && $total_votes > 0): ?>
                    <div class="pollmaster-contest-info">
                        <p class="contest-description">
                            <span class="contest-icon">🎯</span>
                            Winner will be randomly selected from the most voted option!
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- Show Voting Form -->
            <div class="pollmaster-voting">
                <?php if (!$can_vote): ?>
                    <div class="pollmaster-vote-restriction">
                        <p>You need to be logged in to vote in this poll.</p>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="pollmaster-login-link">Login to Vote</a>
                    </div>
                <?php elseif (empty($options)): ?>
                    <p class="pollmaster-no-options">No poll options available.</p>
                <?php else: ?>
                    <form class="pollmaster-vote-form" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                        <div class="pollmaster-options">
                            <?php foreach ($options as $index => $option): ?>
                                <label class="pollmaster-option">
                                    <input type="radio" name="poll_option" value="<?php echo esc_attr($index); ?>" required>
                                    <span class="option-button">
                                        <span class="option-text"><?php echo esc_html($option); ?></span>
                                        <span class="option-radio"></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="pollmaster-vote-actions">
                            <button type="submit" class="pollmaster-vote-button">
                                <span class="button-text">Cast Your Vote</span>
                                <span class="button-loading">Voting...</span>
                            </button>
                            
                            <?php if (!$is_ended): ?>
                                <button type="button" class="pollmaster-results-button" data-action="show-results">
                                    View Results
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <?php wp_nonce_field('pollmaster_vote', 'pollmaster_vote_nonce'); ?>
                    </form>
                    
                    <?php if ($is_contest): ?>
                        <div class="pollmaster-contest-info">
                            <div class="contest-rules">
                                <h5>Contest Rules:</h5>
                                <ul>
                                    <li>Vote for your favorite option</li>
                                    <li>Winner will be randomly selected from the most voted option</li>
                                    <li>Contest ends on <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($poll['end_date']))); ?></li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($show_share): ?>
        <div class="pollmaster-poll-footer">
            <div class="pollmaster-share-section">
                <span class="share-label">Share this poll:</span>
                <div class="pollmaster-share-buttons">
                    <button class="pollmaster-share-button facebook" data-platform="facebook" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Share on Facebook">
                        <span class="share-icon">📘</span>
                        <span class="share-text">Facebook</span>
                    </button>
                    
                    <button class="pollmaster-share-button twitter" data-platform="twitter" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Share on Twitter">
                        <span class="share-icon">🐦</span>
                        <span class="share-text">Twitter</span>
                    </button>
                    
                    <button class="pollmaster-share-button whatsapp" data-platform="whatsapp" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Share on WhatsApp">
                        <span class="share-icon">💬</span>
                        <span class="share-text">WhatsApp</span>
                    </button>
                    
                    <button class="pollmaster-share-button linkedin" data-platform="linkedin" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Share on LinkedIn">
                        <span class="share-icon">💼</span>
                        <span class="share-text">LinkedIn</span>
                    </button>
                    
                    <button class="pollmaster-share-button copy" data-action="copy-link" data-poll-id="<?php echo esc_attr($poll_id); ?>" title="Copy Link">
                        <span class="share-icon">🔗</span>
                        <span class="share-text">Copy Link</span>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Loading overlay -->
    <div class="pollmaster-loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <div class="loading-text">Processing...</div>
    </div>
    
    <!-- Success/Error messages -->
    <div class="pollmaster-messages"></div>
</div>

<?php
// Add inline styles for dynamic colors if needed
$settings = get_option('pollmaster_settings', []);
$primary_color = $settings['primary_color'] ?? '#007cba';
$secondary_color = $settings['secondary_color'] ?? '#46b450';
?>

<style>
.pollmaster-poll[data-poll-id="<?php echo esc_attr($poll_id); ?>"] {
    --primary-color: <?php echo esc_attr($primary_color); ?>;
    --secondary-color: <?php echo esc_attr($secondary_color); ?>;
}

.pollmaster-poll[data-poll-id="<?php echo esc_attr($poll_id); ?>"] .option-bar {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
}

.pollmaster-poll[data-poll-id="<?php echo esc_attr($poll_id); ?>"] .pollmaster-vote-button {
    background: var(--primary-color);
}

.pollmaster-poll[data-poll-id="<?php echo esc_attr($poll_id); ?>"] .pollmaster-vote-button:hover {
    background: var(--secondary-color);
}
</style>