/**
 * PollMaster Admin JavaScript
 * 
 * Handles admin interface interactions and AJAX requests
 */

(function($) {
    'use strict';

    /**
     * Main Admin Class
     */
    class PollMasterAdmin {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initComponents();
            this.setupAjax();
        }

        bindEvents() {
            // Form submissions
            $(document).on('submit', '.pollmaster-form', this.handleFormSubmit.bind(this));
            
            // Delete confirmations
            $(document).on('click', '.pollmaster-delete', this.handleDelete.bind(this));
            
            // Bulk actions
            $(document).on('change', '#bulk-action-selector-top', this.handleBulkAction.bind(this));
            $(document).on('click', '#doaction', this.processBulkAction.bind(this));
            
            // Image upload
            $(document).on('click', '.pollmaster-upload-button', this.handleImageUpload.bind(this));
            $(document).on('click', '.pollmaster-remove-image', this.handleImageRemove.bind(this));
            
            // Tab navigation
            $(document).on('click', '.pollmaster-tab-button', this.handleTabSwitch.bind(this));
            
            // Modal controls
            $(document).on('click', '[data-modal]', this.openModal.bind(this));
            $(document).on('click', '.pollmaster-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.pollmaster-modal', this.handleModalBackdrop.bind(this));
            
            // Search and filters
            $(document).on('input', '.pollmaster-search-input', this.debounce(this.handleSearch.bind(this), 300));
            $(document).on('change', '.pollmaster-filter-select', this.handleFilter.bind(this));
            
            // Contest management
            $(document).on('click', '.announce-winner', this.announceWinner.bind(this));
            $(document).on('click', '.make-weekly', this.makeWeekly.bind(this));
            $(document).on('click', '.remove-weekly', this.removeWeekly.bind(this));
            
            // Settings
            $(document).on('change', '.pollmaster-color-picker', this.handleColorChange.bind(this));
            $(document).on('click', '.reset-settings', this.resetSettings.bind(this));
            
            // Cron management
            $(document).on('click', '.trigger-cron', this.triggerCron.bind(this));
            
            // Export/Import
            $(document).on('click', '.export-polls', this.exportPolls.bind(this));
            $(document).on('change', '.import-polls', this.importPolls.bind(this));
        }

        initComponents() {
            this.initTabs();
            this.initColorPickers();
            this.initCharts();
            this.initTooltips();
            this.initDatePickers();
        }

        setupAjax() {
            // Set up CSRF token for all AJAX requests
            $.ajaxSetup({
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', pollmaster_admin.nonce);
                }
            });
        }

        /**
         * Form Handling
         */
        handleFormSubmit(e) {
            const $form = $(e.target);
            const $submitBtn = $form.find('[type="submit"]');
            
            // Validate form
            if (!this.validateForm($form)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            $submitBtn.addClass('loading').prop('disabled', true);
            
            // Handle AJAX forms
            if ($form.hasClass('ajax-form')) {
                e.preventDefault();
                this.submitAjaxForm($form);
            }
        }

        validateForm($form) {
            let isValid = true;
            
            // Clear previous errors
            $form.find('.pollmaster-form-error').remove();
            $form.find('.error').removeClass('error');
            
            // Check required fields
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<div class="pollmaster-form-error">This field is required.</div>');
                }
            });
            
            // Validate poll options
            if ($form.hasClass('poll-form')) {
                const options = $form.find('.poll-option').filter(function() {
                    return $(this).val().trim() !== '';
                }).length;
                
                if (options < 2) {
                    isValid = false;
                    this.showNotice('error', 'Please provide at least 2 poll options.');
                }
            }
            
            return isValid;
        }

        submitAjaxForm($form) {
            const formData = new FormData($form[0]);
            const action = $form.data('action') || $form.find('input[name="action"]').val();
            formData.append('action', action);
            
            // Don't append general nonce if form already has specific nonce
            if (!$form.find('input[name*="nonce"]').length) {
                formData.append('nonce', pollmaster_admin.nonce);
            }
            
            $.ajax({
                url: pollmaster_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else if (response.data.reload) {
                            window.location.reload();
                        }
                    } else {
                        this.showNotice('error', response.data.message || 'An error occurred.');
                    }
                },
                error: () => {
                    this.showNotice('error', 'Network error. Please try again.');
                },
                complete: () => {
                    $form.find('[type="submit"]').removeClass('loading').prop('disabled', false);
                }
            });
        }

        /**
         * Delete Handling
         */
        handleDelete(e) {
            e.preventDefault();
            
            const $link = $(e.target);
            const itemType = $link.data('type') || 'item';
            const itemName = $link.data('name') || 'this item';
            
            if (confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`)) {
                this.performDelete($link.attr('href'), itemType);
            }
        }

        performDelete(url, type) {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    action: 'pollmaster_delete_' + type,
                    nonce: pollmaster_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        // Remove row from table or reload page
                        if (response.data.reload) {
                            window.location.reload();
                        }
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Failed to delete item.');
                }
            });
        }

        /**
         * Bulk Actions
         */
        handleBulkAction(e) {
            const action = $(e.target).val();
            const $button = $('#doaction');
            
            if (action === '-1') {
                $button.prop('disabled', true);
            } else {
                $button.prop('disabled', false);
            }
        }

        processBulkAction(e) {
            e.preventDefault();
            
            const action = $('#bulk-action-selector-top').val();
            const selected = $('input[name="poll[]"]:checked').map(function() {
                return this.value;
            }).get();
            
            if (action === '-1') {
                this.showNotice('error', 'Please select an action.');
                return;
            }
            
            if (selected.length === 0) {
                this.showNotice('error', 'Please select items to perform bulk action.');
                return;
            }
            
            if (action === 'delete' && !confirm(`Are you sure you want to delete ${selected.length} items?`)) {
                return;
            }
            
            this.performBulkAction(action, selected);
        }

        performBulkAction(action, items) {
            $.ajax({
                url: pollmaster_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'pollmaster_bulk_action',
                    bulk_action: action,
                    items: items,
                    nonce: pollmaster_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        window.location.reload();
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Bulk action failed.');
                }
            });
        }

        /**
         * Image Upload
         */
        handleImageUpload(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const $container = $button.closest('.pollmaster-image-upload');
            
            // Create file input
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            fileInput.style.display = 'none';
            
            fileInput.onchange = (event) => {
                const file = event.target.files[0];
                if (file) {
                    this.uploadImage(file, $container);
                }
            };
            
            document.body.appendChild(fileInput);
            fileInput.click();
            document.body.removeChild(fileInput);
        }

        uploadImage(file, $container) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'pollmaster_upload_image');
            formData.append('nonce', pollmaster_admin.nonce);
            
            $container.addClass('uploading');
            
            $.ajax({
                url: pollmaster_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.displayUploadedImage(response.data, $container);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Image upload failed.');
                },
                complete: () => {
                    $container.removeClass('uploading');
                }
            });
        }

        displayUploadedImage(data, $container) {
            $container.addClass('has-image');
            $container.find('.pollmaster-upload-text').hide();
            
            const $preview = $('<img class="pollmaster-image-preview" />');
            $preview.attr('src', data.url);
            $container.prepend($preview);
            
            // Update hidden input
            $container.find('input[type="hidden"]').val(data.id);
            
            // Show remove button
            $container.find('.pollmaster-remove-image').show();
        }

        handleImageRemove(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const $container = $button.closest('.pollmaster-image-upload');
            
            $container.removeClass('has-image');
            $container.find('.pollmaster-image-preview').remove();
            $container.find('.pollmaster-upload-text').show();
            $container.find('input[type="hidden"]').val('');
            $button.hide();
        }

        /**
         * Tab Navigation
         */
        initTabs() {
            $('.pollmaster-tab-button').first().addClass('active');
            $('.pollmaster-tab-content').first().addClass('active');
        }

        handleTabSwitch(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const target = $button.data('tab');
            
            // Update active states
            $button.siblings().removeClass('active');
            $button.addClass('active');
            
            $('.pollmaster-tab-content').removeClass('active');
            $(target).addClass('active');
        }

        /**
         * Modal Management
         */
        openModal(e) {
            e.preventDefault();
            
            const modalId = $(e.target).data('modal');
            const $modal = $(modalId);
            
            if ($modal.length) {
                $modal.css('display', 'flex');
                $('body').addClass('modal-open');
            }
        }

        closeModal(e) {
            e.preventDefault();
            
            $(e.target).closest('.pollmaster-modal').hide();
            $('body').removeClass('modal-open');
        }

        handleModalBackdrop(e) {
            if (e.target === e.currentTarget) {
                this.closeModal(e);
            }
        }

        /**
         * Search and Filtering
         */
        handleSearch(e) {
            const query = $(e.target).val();
            const $form = $(e.target).closest('form');
            
            if (query.length >= 3 || query.length === 0) {
                $form.submit();
            }
        }

        handleFilter(e) {
            $(e.target).closest('form').submit();
        }

        /**
         * Contest Management
         */
        announceWinner(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const pollId = $button.data('poll-id');
            
            if (confirm('Are you sure you want to announce the winner for this contest?')) {
                this.performContestAction('announce_winner', pollId, $button);
            }
        }

        makeWeekly(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const pollId = $button.data('poll-id');
            
            this.performContestAction('make_weekly', pollId, $button);
        }

        removeWeekly(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const pollId = $button.data('poll-id');
            
            this.performContestAction('remove_weekly', pollId, $button);
        }

        performContestAction(action, pollId, $button) {
            $button.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: pollmaster_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'pollmaster_' + action,
                    poll_id: pollId,
                    nonce: pollmaster_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        if (response.data.reload) {
                            window.location.reload();
                        }
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Action failed.');
                },
                complete: () => {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        }

        /**
         * Settings Management
         */
        initColorPickers() {
            if (typeof $.fn.wpColorPicker !== 'undefined') {
                $('.pollmaster-color-picker').wpColorPicker();
            }
        }

        handleColorChange(e) {
            const $input = $(e.target);
            const color = $input.val();
            const preview = $input.data('preview');
            
            if (preview) {
                $(preview).css('background-color', color);
            }
        }

        resetSettings(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to reset all settings to default values?')) {
                $.ajax({
                    url: pollmaster_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pollmaster_reset_settings',
                        nonce: pollmaster_admin.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotice('success', 'Settings reset successfully.');
                            window.location.reload();
                        } else {
                            this.showNotice('error', response.data.message);
                        }
                    },
                    error: () => {
                        this.showNotice('error', 'Failed to reset settings.');
                    }
                });
            }
        }

        /**
         * Cron Management
         */
        triggerCron(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const cronType = $button.data('cron');
            
            $button.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: pollmaster_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'pollmaster_trigger_cron',
                    cron_type: cronType,
                    nonce: pollmaster_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Failed to trigger cron job.');
                },
                complete: () => {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        }

        /**
         * Export/Import
         */
        exportPolls(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            $button.addClass('loading');
            
            window.location.href = pollmaster_admin.ajax_url + '?action=pollmaster_export_polls&nonce=' + pollmaster_admin.nonce;
            
            setTimeout(() => {
                $button.removeClass('loading');
            }, 2000);
        }

        importPolls(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'pollmaster_import_polls');
            formData.append('nonce', pollmaster_admin.nonce);
            
            $.ajax({
                url: pollmaster_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        window.location.reload();
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Import failed.');
                }
            });
        }

        /**
         * Charts and Visualization
         */
        initCharts() {
            $('.pollmaster-chart').each((index, element) => {
                this.renderChart($(element));
            });
        }

        renderChart($container) {
            const data = $container.data('chart-data');
            const type = $container.data('chart-type') || 'bar';
            
            if (!data) return;
            
            // Simple chart implementation using CSS
            const total = data.reduce((sum, item) => sum + item.votes, 0);
            
            data.forEach((item, index) => {
                const percentage = total > 0 ? (item.votes / total) * 100 : 0;
                const $bar = $('<div class="chart-bar"></div>');
                const $label = $('<div class="chart-label"></div>').text(item.option);
                const $value = $('<div class="chart-value"></div>').text(`${item.votes} (${percentage.toFixed(1)}%)`);
                
                $bar.css({
                    width: percentage + '%',
                    backgroundColor: this.getChartColor(index)
                });
                
                $container.append($label, $bar, $value);
            });
        }

        getChartColor(index) {
            const colors = ['#007cba', '#46b450', '#dc3232', '#ffb900', '#826eb4', '#f56e28'];
            return colors[index % colors.length];
        }

        /**
         * Tooltips
         */
        initTooltips() {
            $('[data-tooltip]').each(function() {
                const $element = $(this);
                const text = $element.data('tooltip');
                
                $element.attr('title', text);
            });
        }

        /**
         * Date Pickers
         */
        initDatePickers() {
            if (typeof $.fn.datepicker !== 'undefined') {
                $('.pollmaster-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        }

        /**
         * Utility Functions
         */
        showNotice(type, message) {
            const $notice = $(`<div class="pollmaster-notice ${type}">${message}</div>`);
            
            // Remove existing notices
            $('.pollmaster-notice').remove();
            
            // Add new notice
            $('.pollmaster-admin-page').prepend($notice);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
            
            // Scroll to top
            $('html, body').animate({ scrollTop: 0 }, 300);
        }

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        throttle(func, limit) {
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
        }
    }

    /**
     * Statistics Dashboard
     */
    class PollMasterStats {
        constructor() {
            this.init();
        }

        init() {
            this.loadStats();
            this.bindEvents();
        }

        bindEvents() {
            $(document).on('change', '.stats-period', this.handlePeriodChange.bind(this));
            $(document).on('click', '.refresh-stats', this.refreshStats.bind(this));
        }

        loadStats() {
            const period = $('.stats-period').val() || '7days';
            
            $.ajax({
                url: pollmaster_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'pollmaster_get_stats',
                    period: period,
                    nonce: pollmaster_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStatsDisplay(response.data);
                    }
                }
            });
        }

        handlePeriodChange() {
            this.loadStats();
        }

        refreshStats() {
            $('.pollmaster-stat-card').addClass('loading');
            this.loadStats();
        }

        updateStatsDisplay(data) {
            $('.pollmaster-stat-card').removeClass('loading');
            
            Object.keys(data).forEach(key => {
                $(`.stat-${key} .pollmaster-stat-number`).text(data[key]);
            });
        }
    }

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Initialize main admin functionality
        window.pollMasterAdmin = new PollMasterAdmin();
        
        // Initialize stats if on dashboard
        if ($('.pollmaster-stats-grid').length) {
            window.pollMasterStats = new PollMasterStats();
        }
        
        // Initialize select2 if available
        if (typeof $.fn.select2 !== 'undefined') {
            $('.pollmaster-select2').select2({
                width: '100%'
            });
        }
        
        // Initialize sortable if available
        if (typeof $.fn.sortable !== 'undefined') {
            $('.pollmaster-sortable').sortable({
                handle: '.sort-handle',
                placeholder: 'sort-placeholder',
                update: function(event, ui) {
                    // Handle sort order update
                    const order = $(this).sortable('toArray', { attribute: 'data-id' });
                    // Send AJAX request to update order
                }
            });
        }
    });

})(jQuery);