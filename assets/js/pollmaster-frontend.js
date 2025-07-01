/**
 * PollMaster Frontend JavaScript
 * 
 * Handles all frontend interactions for the PollMaster plugin
 */

(function($) {
    'use strict';
    
    // Plugin namespace
    window.PollMaster = window.PollMaster || {};
    
    // Configuration
    const config = {
        ajaxUrl: pollmaster_ajax.ajax_url,
        nonce: pollmaster_ajax.nonce,
        strings: pollmaster_ajax.strings || {}
    };
    
    /**
     * Main PollMaster class
     */
    class PollMasterFrontend {
        constructor() {
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initPopups();
            this.initPolls();
            this.initCharts();
        }
        
        /**
         * Bind event listeners
         */
        bindEvents() {
            // Vote button clicks
            $(document).on('click', '.pollmaster-vote-btn', this.handleVote.bind(this));
            
            // Share button clicks
            $(document).on('click', '.pollmaster-share-btn', this.handleShare.bind(this));
            
            // Popup close buttons
            $(document).on('click', '.pollmaster-popup-close', this.closePopup.bind(this));
            
            // Popup overlay clicks
            $(document).on('click', '.pollmaster-popup-overlay', this.closePopup.bind(this));
            
            // Dismiss buttons
            $(document).on('click', '.pollmaster-dismiss-btn', this.handleDismiss.bind(this));
            
            // Escape key to close popup
            $(document).on('keydown', this.handleKeydown.bind(this));
            
            // Results refresh
            $(document).on('click', '.pollmaster-refresh-results', this.refreshResults.bind(this));
            
            // Window resize for responsive popups
            $(window).on('resize', this.handleResize.bind(this));
        }
        
        /**
         * Initialize popups
         */
        initPopups() {
            $('.pollmaster-popup-container').each((index, element) => {
                const $popup = $(element);
                const autoShow = $popup.data('auto-show');
                const delay = parseInt($popup.data('delay')) || 3000;
                
                if (autoShow) {
                    setTimeout(() => {
                        this.showPopup($popup);
                    }, delay);
                }
            });
        }
        
        /**
         * Initialize embedded polls
         */
        initPolls() {
            $('.pollmaster-poll-embed').each((index, element) => {
                const $poll = $(element);
                this.setupPollInteractions($poll);
            });
        }
        
        /**
         * Initialize charts
         */
        initCharts() {
            if (typeof Chart !== 'undefined') {
                $('.pollmaster-chart-container canvas').each((index, element) => {
                    this.createChart(element);
                });
            }
        }
        
        /**
         * Handle vote submission
         */
        handleVote(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const pollId = $button.data('poll-id');
            const voteOption = $button.data('option');
            
            if ($button.hasClass('loading') || $button.prop('disabled')) {
                return;
            }
            
            // Set loading state
            this.setButtonLoading($button, true);
            
            // Disable all vote buttons for this poll
            $(`.pollmaster-vote-btn[data-poll-id="${pollId}"]`).prop('disabled', true);
            
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pollmaster_vote',
                    poll_id: pollId,
                    vote_option: voteOption,
                    nonce: config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.handleVoteSuccess(response.data, pollId);
                    } else {
                        this.handleVoteError(response.data.message, pollId);
                    }
                },
                error: () => {
                    this.handleVoteError(config.strings.vote_error || 'An error occurred while voting.', pollId);
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        }
        
        /**
         * Handle successful vote
         */
        handleVoteSuccess(data, pollId) {
            // Show success message
            this.showMessage(data.message, 'success', pollId);
            
            // Update poll display with results
            setTimeout(() => {
                this.updatePollWithResults(data, pollId);
            }, 1000);
        }
        
        /**
         * Handle vote error
         */
        handleVoteError(message, pollId) {
            this.showMessage(message, 'error', pollId);
            
            // Re-enable vote buttons
            $(`.pollmaster-vote-btn[data-poll-id="${pollId}"]`).prop('disabled', false);
        }
        
        /**
         * Handle social sharing
         */
        handleShare(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const pollId = $button.data('poll-id');
            const platform = $button.data('platform');
            
            // Track share
            this.trackShare(pollId, platform);
            
            // Get share URL
            const shareUrl = this.generateShareUrl(pollId, platform);
            
            // Open share window
            this.openShareWindow(shareUrl, platform);
        }
        
        /**
         * Track social share
         */
        trackShare(pollId, platform) {
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pollmaster_share',
                    poll_id: pollId,
                    platform: platform,
                    nonce: config.nonce
                }
            });
        }
        
        /**
         * Generate share URL
         */
        generateShareUrl(pollId, platform) {
            const pollUrl = encodeURIComponent(window.location.href + '?poll_id=' + pollId);
            const pollText = encodeURIComponent('Check out this poll!');
            const siteName = encodeURIComponent(document.title);
            
            switch (platform) {
                case 'facebook':
                    return `https://www.facebook.com/sharer/sharer.php?u=${pollUrl}`;
                    
                case 'twitter':
                    return `https://twitter.com/intent/tweet?text=${pollText}&url=${pollUrl}&via=${siteName}`;
                    
                case 'whatsapp':
                    return `https://wa.me/?text=${pollText}%20${pollUrl}`;
                    
                case 'linkedin':
                    return `https://www.linkedin.com/sharing/share-offsite/?url=${pollUrl}`;
                    
                default:
                    return window.location.href;
            }
        }
        
        /**
         * Open share window
         */
        openShareWindow(url, platform) {
            const width = 600;
            const height = 400;
            const left = (window.innerWidth - width) / 2;
            const top = (window.innerHeight - height) / 2;
            
            window.open(
                url,
                `share_${platform}`,
                `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes`
            );
        }
        
        /**
         * Handle popup dismissal
         */
        handleDismiss(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $popup = $button.closest('.pollmaster-popup-container');
            const pollId = $popup.data('poll-id');
            const dismissDuration = $button.data('dismiss');
            
            // Send dismiss request
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pollmaster_dismiss_popup',
                    poll_id: pollId,
                    dismiss_duration: dismissDuration,
                    nonce: config.nonce
                },
                success: () => {
                    this.closePopup(e);
                }
            });
        }
        
        /**
         * Show popup
         */
        showPopup($popup) {
            $popup.fadeIn(300);
            $('body').addClass('pollmaster-popup-open');
            
            // Focus management for accessibility
            const $firstFocusable = $popup.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').first();
            if ($firstFocusable.length) {
                $firstFocusable.focus();
            }
        }
        
        /**
         * Close popup
         */
        closePopup(e) {
            if (e) {
                e.preventDefault();
            }
            
            const $popup = $(e.target).closest('.pollmaster-popup-container');
            if ($popup.length === 0) {
                $('.pollmaster-popup-container:visible').fadeOut(300);
            } else {
                $popup.fadeOut(300);
            }
            
            $('body').removeClass('pollmaster-popup-open');
        }
        
        /**
         * Handle keyboard events
         */
        handleKeydown(e) {
            if (e.key === 'Escape') {
                this.closePopup(e);
            }
        }
        
        /**
         * Handle window resize
         */
        handleResize() {
            // Adjust popup positioning if needed
            $('.pollmaster-popup-container:visible').each((index, element) => {
                const $popup = $(element);
                const $content = $popup.find('.pollmaster-popup-content');
                
                // Ensure popup fits in viewport
                const maxHeight = window.innerHeight * 0.9;
                $content.css('max-height', maxHeight + 'px');
            });
        }
        
        /**
         * Set button loading state
         */
        setButtonLoading($button, loading) {
            if (loading) {
                $button.addClass('loading').prop('disabled', true);
                const originalText = $button.text();
                $button.data('original-text', originalText);
                $button.html('<span class="pollmaster-loading"></span>' + (config.strings.voting || 'Voting...'));
            } else {
                $button.removeClass('loading').prop('disabled', false);
                const originalText = $button.data('original-text');
                if (originalText) {
                    $button.text(originalText);
                }
            }
        }
        
        /**
         * Show message
         */
        showMessage(message, type, pollId) {
            const $container = $(`.pollmaster-poll-voting[data-poll-id="${pollId}"], .pollmaster-popup-inner`);
            const $message = $(`<div class="pollmaster-${type}-message">${message}</div>`);
            
            $container.prepend($message);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        /**
         * Update poll with results
         */
        updatePollWithResults(data, pollId) {
            const $pollContainer = $(`.pollmaster-poll-voting[data-poll-id="${pollId}"]`);
            
            if ($pollContainer.length === 0) {
                return;
            }
            
            // Create results HTML
            const resultsHtml = this.createResultsHtml(data.poll, data.results);
            
            // Replace voting interface with results
            $pollContainer.fadeOut(300, function() {
                $(this).replaceWith(resultsHtml);
                $(`.pollmaster-poll-results[data-poll-id="${pollId}"]`).fadeIn(300);
            });
        }
        
        /**
         * Create results HTML
         */
        createResultsHtml(poll, results) {
            const totalVotes = results.total_votes;
            const percentageA = results.percentages.option_a;
            const percentageB = results.percentages.option_b;
            const votesA = results.vote_counts.option_a;
            const votesB = results.vote_counts.option_b;
            
            return `
                <div class="pollmaster-poll-results" data-poll-id="${poll.id}">
                    ${poll.is_contest ? '<div class="pollmaster-contest-badge"><span>Contest Results</span></div>' : ''}
                    
                    <h3 class="pollmaster-poll-question">${poll.question}</h3>
                    
                    <div class="pollmaster-results-summary">
                        <p>Total votes: ${totalVotes}</p>
                    </div>
                    
                    <div class="pollmaster-results-options">
                        <div class="pollmaster-result-option">
                            <div class="pollmaster-option-header">
                                <span class="pollmaster-option-text">${poll.option_a}</span>
                                <span class="pollmaster-option-stats">
                                    <span class="pollmaster-vote-count">${votesA}</span>
                                    <span class="pollmaster-percentage">(${percentageA}%)</span>
                                </span>
                            </div>
                            <div class="pollmaster-progress-bar">
                                <div class="pollmaster-progress-fill" style="width: ${percentageA}%;"></div>
                            </div>
                        </div>
                        
                        <div class="pollmaster-result-option">
                            <div class="pollmaster-option-header">
                                <span class="pollmaster-option-text">${poll.option_b}</span>
                                <span class="pollmaster-option-stats">
                                    <span class="pollmaster-vote-count">${votesB}</span>
                                    <span class="pollmaster-percentage">(${percentageB}%)</span>
                                </span>
                            </div>
                            <div class="pollmaster-progress-bar">
                                <div class="pollmaster-progress-fill" style="width: ${percentageB}%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        /**
         * Refresh poll results
         */
        refreshResults(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const pollId = $button.data('poll-id');
            
            $button.addClass('loading');
            
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pollmaster_get_results',
                    poll_id: pollId,
                    nonce: config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateResultsDisplay(response.data, pollId);
                    }
                },
                complete: () => {
                    $button.removeClass('loading');
                }
            });
        }
        
        /**
         * Update results display
         */
        updateResultsDisplay(data, pollId) {
            const $results = $(`.pollmaster-poll-results[data-poll-id="${pollId}"]`);
            
            if ($results.length === 0) {
                return;
            }
            
            // Update vote counts and percentages
            $results.find('.pollmaster-results-summary p').text(`Total votes: ${data.results.total_votes}`);
            
            const $optionA = $results.find('.pollmaster-result-option').first();
            const $optionB = $results.find('.pollmaster-result-option').last();
            
            // Update option A
            $optionA.find('.pollmaster-vote-count').text(data.results.vote_counts.option_a);
            $optionA.find('.pollmaster-percentage').text(`(${data.results.percentages.option_a}%)`);
            $optionA.find('.pollmaster-progress-fill').css('width', `${data.results.percentages.option_a}%`);
            
            // Update option B
            $optionB.find('.pollmaster-vote-count').text(data.results.vote_counts.option_b);
            $optionB.find('.pollmaster-percentage').text(`(${data.results.percentages.option_b}%)`);
            $optionB.find('.pollmaster-progress-fill').css('width', `${data.results.percentages.option_b}%`);
        }
        
        /**
         * Setup poll interactions
         */
        setupPollInteractions($poll) {
            // Add hover effects
            $poll.find('.pollmaster-vote-btn').on('mouseenter', function() {
                $(this).addClass('hover');
            }).on('mouseleave', function() {
                $(this).removeClass('hover');
            });
            
            // Add keyboard navigation
            $poll.find('.pollmaster-vote-btn').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });
        }
        
        /**
         * Create chart
         */
        createChart(canvas) {
            const $canvas = $(canvas);
            const chartType = $canvas.data('chart-type') || 'bar';
            const optionA = $canvas.data('option-a');
            const optionB = $canvas.data('option-b');
            const votesA = parseInt($canvas.data('votes-a')) || 0;
            const votesB = parseInt($canvas.data('votes-b')) || 0;
            
            const ctx = canvas.getContext('2d');
            
            const chartConfig = {
                type: chartType,
                data: {
                    labels: [optionA, optionB],
                    datasets: [{
                        data: [votesA, votesB],
                        backgroundColor: [
                            '#007cba',
                            '#0056b3'
                        ],
                        borderColor: [
                            '#005a87',
                            '#004085'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: chartType !== 'bar'
                        }
                    },
                    scales: chartType === 'bar' ? {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    } : {}
                }
            };
            
            new Chart(ctx, chartConfig);
        }
        
        /**
         * Load poll dynamically
         */
        loadPoll(pollId, container) {
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pollmaster_load_poll',
                    poll_id: pollId,
                    nonce: config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $(container).html(response.data.poll_html);
                        this.setupPollInteractions($(container));
                    }
                }
            });
        }
    }
    
    /**
     * Utility functions
     */
    window.PollMaster.utils = {
        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },
        
        /**
         * Throttle function
         */
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },
        
        /**
         * Get poll data
         */
        getPollData: function(pollId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'pollmaster_get_results',
                        poll_id: pollId,
                        nonce: config.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(response.data.message);
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(error);
                    }
                });
            });
        }
    };
    
    /**
     * Public API
     */
    window.PollMaster.api = {
        /**
         * Show popup programmatically
         */
        showPopup: function(selector) {
            const $popup = $(selector);
            if ($popup.length) {
                window.PollMaster.frontend.showPopup($popup);
            }
        },
        
        /**
         * Close popup programmatically
         */
        closePopup: function(selector) {
            const $popup = selector ? $(selector) : $('.pollmaster-popup-container:visible');
            $popup.fadeOut(300);
            $('body').removeClass('pollmaster-popup-open');
        },
        
        /**
         * Load poll into container
         */
        loadPoll: function(pollId, container) {
            window.PollMaster.frontend.loadPoll(pollId, container);
        },
        
        /**
         * Refresh poll results
         */
        refreshResults: function(pollId) {
            const $refreshBtn = $(`.pollmaster-refresh-results[data-poll-id="${pollId}"]`);
            if ($refreshBtn.length) {
                $refreshBtn.click();
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        window.PollMaster.frontend = new PollMasterFrontend();
    });
    
    // Add CSS for popup open state
    const style = document.createElement('style');
    style.textContent = `
        body.pollmaster-popup-open {
            overflow: hidden;
        }
        
        @media (max-width: 768px) {
            body.pollmaster-popup-open {
                position: fixed;
                width: 100%;
            }
        }
    `;
    document.head.appendChild(style);
    
})(jQuery);