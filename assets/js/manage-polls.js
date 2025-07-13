jQuery(document).ready(function($) {
    'use strict';
    
    // Modern Poll Cards JavaScript
    
    // Handle Select All functionality
    $('#select-all-polls').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.poll-checkbox-modern').prop('checked', isChecked);
        updateBulkActionsCounter();
    });
    
    // Handle individual checkbox changes
    $(document).on('change', '.poll-checkbox-modern', function() {
        updateBulkActionsCounter();
        updateSelectAllState();
    });
    
    // Update bulk actions counter
    function updateBulkActionsCounter() {
        const checkedCount = $('.poll-checkbox-modern:checked').length;
        const counterText = checkedCount === 0 ? '0 polls selected' : 
                           checkedCount === 1 ? '1 poll selected' : 
                           `${checkedCount} polls selected`;
        $('.bulk-counter').text(counterText);
        
        // Enable/disable bulk actions
        $('.bulk-actions-modern .action-btn').prop('disabled', checkedCount === 0);
    }
    
    // Update select all checkbox state
    function updateSelectAllState() {
        const totalCheckboxes = $('.poll-checkbox-modern').length;
        const checkedCheckboxes = $('.poll-checkbox-modern:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#select-all-polls').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#select-all-polls').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#select-all-polls').prop('indeterminate', true);
        }
    }
    
    // Handle duplicate poll action
    $(document).on('click', '.action-btn.duplicate', function(e) {
        e.preventDefault();
        const pollId = $(this).data('poll-id');
        
        if (confirm('Are you sure you want to duplicate this poll?')) {
            window.location.href = `admin.php?page=pollmaster-add-poll&duplicate=${pollId}`;
        }
    });
    
    // Handle delete poll action
    $(document).on('click', '.action-btn.delete', function(e) {
        e.preventDefault();
        const pollId = $(this).data('poll-id');
        const pollCard = $(this).closest('.poll-card');
        const pollTitle = pollCard.find('.poll-card-title a').text().trim();
        
        if (confirm(`Are you sure you want to delete the poll "${pollTitle}"? This action cannot be undone.`)) {
            deletePoll(pollId, pollCard);
        }
    });
    
    // Handle announce winner action
    $(document).on('click', '.action-btn.announce-winner', function(e) {
        e.preventDefault();
        const pollId = $(this).data('poll-id');
        
        if (confirm('Are you sure you want to announce the winner for this contest?')) {
            announceWinner(pollId);
        }
    });
    
    // Delete poll function
    function deletePoll(pollId, pollCard) {
        // Add loading state
        pollCard.addClass('deleting');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pollmaster_delete_poll',
                poll_id: pollId,
                nonce: pollmaster_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Animate card removal
                    pollCard.fadeOut(300, function() {
                        $(this).remove();
                        updateBulkActionsCounter();
                        updateSelectAllState();
                        
                        // Show success message
                        showNotification('Poll deleted successfully!', 'success');
                        
                        // Check if no polls left
                        if ($('.poll-card').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    pollCard.removeClass('deleting');
                    showNotification(response.data || 'Failed to delete poll.', 'error');
                }
            },
            error: function() {
                pollCard.removeClass('deleting');
                showNotification('An error occurred while deleting the poll.', 'error');
            }
        });
    }
    
    // Announce winner function
    function announceWinner(pollId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pollmaster_announce_winner',
                poll_id: pollId,
                nonce: pollmaster_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Winner announced successfully!', 'success');
                    // Optionally reload or update the UI
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(response.data || 'Failed to announce winner.', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while announcing the winner.', 'error');
            }
        });
    }
    
    // Handle bulk actions
    $('.bulk-apply-btn').on('click', function(e) {
        e.preventDefault();
        
        const selectedAction = $('.bulk-actions-select').val();
        const selectedPolls = $('.poll-checkbox-modern:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedPolls.length === 0) {
            showNotification('Please select at least one poll.', 'warning');
            return;
        }
        
        if (!selectedAction || selectedAction === '') {
            showNotification('Please select an action.', 'warning');
            return;
        }
        
        // Confirm bulk action
        const actionText = $('.bulk-actions-select option:selected').text();
        const confirmMessage = `Are you sure you want to ${actionText.toLowerCase()} ${selectedPolls.length} poll(s)?`;
        
        if (confirm(confirmMessage)) {
            performBulkAction(selectedAction, selectedPolls);
        }
    });
    
    // Perform bulk action
    function performBulkAction(action, pollIds) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pollmaster_bulk_action',
                bulk_action: action,
                poll_ids: pollIds,
                nonce: pollmaster_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message || 'Bulk action completed successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(response.data || 'Bulk action failed.', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while performing the bulk action.', 'error');
            }
        });
    }
    
    // Show notification function
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        $('.pollmaster-notification').remove();
        
        // Create notification element
        const notification = $(`
            <div class="pollmaster-notification notification-${type}">
                <div class="notification-content">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            </div>
        `);
        
        // Add to page
        $('body').append(notification);
        
        // Show with animation
        setTimeout(() => notification.addClass('show'), 100);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
        
        // Handle close button
        notification.find('.notification-close').on('click', function() {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        });
    }
    
    // Handle sorting
    $('.sort-btn').on('click', function(e) {
        e.preventDefault();
        
        const sortBy = $(this).data('sort');
        const currentOrder = $(this).data('order') || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        
        // Update URL with sort parameters
        const url = new URL(window.location);
        url.searchParams.set('orderby', sortBy);
        url.searchParams.set('order', newOrder);
        
        window.location.href = url.toString();
    });
    
    // Handle filter form submission
    $('.filter-btn').on('click', function(e) {
        e.preventDefault();
        $('#polls-filter-form').submit();
    });
    
    // Handle clear filters
    $('.clear-btn').on('click', function(e) {
        e.preventDefault();
        
        // Clear all filter inputs
        $('#search-polls').val('');
        $('#filter-status').val('');
        $('#filter-type').val('');
        $('#filter-date-from').val('');
        $('#filter-date-to').val('');
        
        // Submit form to clear filters
        $('#polls-filter-form').submit();
    });
    
    // Initialize on page load
    updateBulkActionsCounter();
    updateSelectAllState();
    
    // Add CSS for notifications
    if (!$('#pollmaster-notification-styles').length) {
        $('head').append(`
            <style id="pollmaster-notification-styles">
                .pollmaster-notification {
                    position: fixed;
                    top: 32px;
                    right: 20px;
                    z-index: 999999;
                    max-width: 400px;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                }
                
                .pollmaster-notification.show {
                    transform: translateX(0);
                }
                
                .notification-content {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 12px 16px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    font-size: 14px;
                    font-weight: 500;
                }
                
                .notification-success .notification-content {
                    background: #10b981;
                    color: white;
                }
                
                .notification-error .notification-content {
                    background: #ef4444;
                    color: white;
                }
                
                .notification-warning .notification-content {
                    background: #f59e0b;
                    color: white;
                }
                
                .notification-info .notification-content {
                    background: #6366f1;
                    color: white;
                }
                
                .notification-close {
                    background: none;
                    border: none;
                    color: inherit;
                    font-size: 18px;
                    cursor: pointer;
                    padding: 0;
                    margin-left: 12px;
                    opacity: 0.8;
                }
                
                .notification-close:hover {
                    opacity: 1;
                }
                
                .poll-card.deleting {
                    opacity: 0.5;
                    pointer-events: none;
                }
            </style>
        `);
    }
});