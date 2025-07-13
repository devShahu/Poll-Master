/**
 * PollMaster Pages JavaScript
 * Handles frontend page interactions
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initCreatePollForm();
        initVoteButtons();
        initSmoothScrolling();
    });
    
    /**
     * Initialize create poll form
     */
    function initCreatePollForm() {
        $('#pollmaster-create-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const $result = $('#poll-creation-result');
            
            // Disable submit button
            $submitBtn.prop('disabled', true).text(pollmaster_pages_ajax.strings.creating);
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'create_user_poll');
            formData.append('nonce', pollmaster_pages_ajax.nonce);
            formData.append('question', $form.find('[name="question"]').val());
            formData.append('option_a', $form.find('[name="option_a"]').val());
            formData.append('option_b', $form.find('[name="option_b"]').val());
            formData.append('is_contest', $form.find('[name="is_contest"]').is(':checked') ? '1' : '0');
            
            // Add image if selected
            const imageFile = $form.find('[name="poll_image"]')[0].files[0];
            if (imageFile) {
                formData.append('poll_image', imageFile);
            }
            
            // Submit via AJAX
            $.ajax({
                url: pollmaster_pages_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showMessage($result, response.data.message, 'success');
                        $form[0].reset();
                        
                        // Redirect to dashboard after 2 seconds
                        setTimeout(function() {
                            window.location.href = '/polls/dashboard';
                        }, 2000);
                    } else {
                        showMessage($result, response.data.message, 'error');
                    }
                },
                error: function() {
                    showMessage($result, pollmaster_pages_ajax.strings.error, 'error');
                },
                complete: function() {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).text('Create Poll');
                }
            });
        });
    }
    
    /**
     * Initialize vote buttons
     */
    function initVoteButtons() {
        $('.vote-btn').on('click', function(e) {
            e.preventDefault();
            
            const pollId = $(this).data('poll-id');
            
            // Create and show voting modal
            showVotingModal(pollId);
        });
    }
    
    /**
     * Show voting modal
     */
    function showVotingModal(pollId) {
        // Get poll data via AJAX
        $.ajax({
            url: pollmaster_pages_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_poll_data',
                nonce: pollmaster_pages_ajax.nonce,
                poll_id: pollId
            },
            success: function(response) {
                if (response.success) {
                    createVotingModal(response.data);
                }
            }
        });
    }
    
    /**
     * Create voting modal
     */
    function createVotingModal(pollData) {
        const modalHtml = `
            <div id="voting-modal" class="modal modal-open" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
                <div class="modal-box" style="background: white; padding: 2rem; border-radius: 1rem; max-width: 500px; width: 90%;">
                    <h3 class="font-bold text-lg mb-4" style="font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem;">${pollData.question}</h3>
                    
                    ${pollData.image_url ? `<img src="${pollData.image_url}" alt="Poll image" style="width: 100%; height: 200px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 1rem;">` : ''}
                    
                    <div class="voting-options" style="margin-bottom: 1.5rem;">
                        <button class="vote-option-btn" data-option="a" style="width: 100%; padding: 1rem; margin-bottom: 0.5rem; background: #f3f4f6; border: 2px solid #d1d5db; border-radius: 0.5rem; cursor: pointer; text-align: left; transition: all 0.2s;">
                            <strong>A:</strong> ${pollData.option_a}
                        </button>
                        <button class="vote-option-btn" data-option="b" style="width: 100%; padding: 1rem; background: #f3f4f6; border: 2px solid #d1d5db; border-radius: 0.5rem; cursor: pointer; text-align: left; transition: all 0.2s;">
                            <strong>B:</strong> ${pollData.option_b}
                        </button>
                    </div>
                    
                    <div class="modal-action" style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button class="btn" id="close-modal" style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background: white; cursor: pointer;">Cancel</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Handle option selection
        $('.vote-option-btn').on('click', function() {
            $('.vote-option-btn').css({
                'background': '#f3f4f6',
                'border-color': '#d1d5db'
            });
            
            $(this).css({
                'background': '#dbeafe',
                'border-color': '#3b82f6'
            });
            
            const option = $(this).data('option');
            
            // Submit vote
            submitVote(pollData.id, option);
        });
        
        // Handle modal close
        $('#close-modal').on('click', function() {
            $('#voting-modal').remove();
        });
        
        // Close on backdrop click
        $('#voting-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).remove();
            }
        });
    }
    
    /**
     * Submit vote
     */
    function submitVote(pollId, option) {
        $.ajax({
            url: pollmaster_pages_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_vote',
                nonce: pollmaster_pages_ajax.nonce,
                poll_id: pollId,
                option: option
            },
            success: function(response) {
                if (response.success) {
                    // Show results
                    showVoteResults(response.data);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Error submitting vote. Please try again.');
            }
        });
    }
    
    /**
     * Show vote results
     */
    function showVoteResults(results) {
        const resultsHtml = `
            <div class="vote-results" style="margin-top: 1rem;">
                <h4 style="font-weight: bold; margin-bottom: 1rem;">Results:</h4>
                
                <div class="result-option" style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span><strong>A:</strong> ${results.poll.option_a}</span>
                        <span>${results.percentages.option_a}%</span>
                    </div>
                    <div style="width: 100%; height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden;">
                        <div style="width: ${results.percentages.option_a}%; height: 100%; background: #3b82f6; transition: width 0.5s;"></div>
                    </div>
                    <small style="color: #6b7280;">${results.vote_counts.option_a} votes</small>
                </div>
                
                <div class="result-option" style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span><strong>B:</strong> ${results.poll.option_b}</span>
                        <span>${results.percentages.option_b}%</span>
                    </div>
                    <div style="width: 100%; height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden;">
                        <div style="width: ${results.percentages.option_b}%; height: 100%; background: #10b981; transition: width 0.5s;"></div>
                    </div>
                    <small style="color: #6b7280;">${results.vote_counts.option_b} votes</small>
                </div>
                
                <div style="text-align: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <strong>Total Votes: ${results.total_votes}</strong>
                </div>
            </div>
        `;
        
        $('.voting-options').html(resultsHtml);
        
        // Update modal action
        $('.modal-action').html(`
            <button class="btn btn-primary" id="close-modal" style="background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; cursor: pointer;">Close</button>
        `);
        
        // Handle close
        $('#close-modal').on('click', function() {
            $('#voting-modal').remove();
        });
    }
    
    /**
     * Initialize smooth scrolling
     */
    function initSmoothScrolling() {
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            }
        });
    }
    
    /**
     * Show message
     */
    function showMessage($container, message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        const bgColor = type === 'success' ? '#d1fae5' : '#fee2e2';
        const borderColor = type === 'success' ? '#10b981' : '#ef4444';
        const textColor = type === 'success' ? '#065f46' : '#991b1b';
        
        const messageHtml = `
            <div class="alert ${alertClass}" style="background: ${bgColor}; border: 1px solid ${borderColor}; color: ${textColor}; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem;">
                ${message}
            </div>
        `;
        
        $container.html(messageHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $container.fadeOut();
        }, 5000);
    }
    
})(jQuery);