<?php
/**
 * Export/Import Template
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle form submissions
$message = '';
$error = '';

if (isset($_POST['action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['pollmaster_nonce'], 'pollmaster_export_action')) {
        $error = 'Security check failed.';
    } else {
        $action = sanitize_text_field($_POST['action']);
        
        switch ($action) {
            case 'export_polls':
                $this->handle_export_polls();
                break;
                
            case 'export_settings':
                $this->handle_export_settings();
                break;
                
            case 'import_data':
                $result = $this->handle_import_data();
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get export statistics
$db = new PollMaster_Database();
$total_polls = $db->get_polls_count();
$total_votes = $db->get_total_votes();
$active_polls = $db->get_polls_count(['status' => 'active']);
?>

<div class="wrap pollmaster-export-page">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-download"></span>
        Export & Import
    </h1>
    
    <hr class="wp-header-end">
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="pollmaster-export-container">
        <!-- Export Section -->
        <div class="pollmaster-card export-section">
            <div class="card-header">
                <h2><span class="dashicons dashicons-upload"></span> Export Data</h2>
                <p>Export your polls, votes, and settings for backup or migration purposes.</p>
            </div>
            
            <div class="card-body">
                <div class="export-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_polls); ?></span>
                        <span class="stat-label">Total Polls</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_votes); ?></span>
                        <span class="stat-label">Total Votes</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($active_polls); ?></span>
                        <span class="stat-label">Active Polls</span>
                    </div>
                </div>
                
                <div class="export-options">
                    <form method="post" class="export-form">
                        <?php wp_nonce_field('pollmaster_export_action', 'pollmaster_nonce'); ?>
                        
                        <div class="export-type-selection">
                            <h3>What would you like to export?</h3>
                            
                            <label class="export-option">
                                <input type="radio" name="export_type" value="all" checked>
                                <span class="option-title">Complete Export</span>
                                <span class="option-desc">All polls, votes, settings, and configuration</span>
                            </label>
                            
                            <label class="export-option">
                                <input type="radio" name="export_type" value="polls_only">
                                <span class="option-title">Polls Only</span>
                                <span class="option-desc">Poll data and votes (no settings)</span>
                            </label>
                            
                            <label class="export-option">
                                <input type="radio" name="export_type" value="settings_only">
                                <span class="option-title">Settings Only</span>
                                <span class="option-desc">Plugin configuration and preferences</span>
                            </label>
                        </div>
                        
                        <div class="export-filters">
                            <h3>Export Filters</h3>
                            
                            <div class="filter-row">
                                <label for="date_from">From Date:</label>
                                <input type="date" id="date_from" name="date_from" class="regular-text">
                            </div>
                            
                            <div class="filter-row">
                                <label for="date_to">To Date:</label>
                                <input type="date" id="date_to" name="date_to" class="regular-text">
                            </div>
                            
                            <div class="filter-row">
                                <label for="poll_status">Poll Status:</label>
                                <select id="poll_status" name="poll_status">
                                    <option value="all">All Statuses</option>
                                    <option value="active">Active Only</option>
                                    <option value="ended">Ended Only</option>
                                    <option value="draft">Draft Only</option>
                                </select>
                            </div>
                            
                            <div class="filter-row">
                                <label>
                                    <input type="checkbox" name="include_votes" value="1" checked>
                                    Include vote data
                                </label>
                            </div>
                            
                            <div class="filter-row">
                                <label>
                                    <input type="checkbox" name="include_images" value="1">
                                    Include poll images (larger file size)
                                </label>
                            </div>
                        </div>
                        
                        <div class="export-format">
                            <h3>Export Format</h3>
                            
                            <label class="format-option">
                                <input type="radio" name="export_format" value="json" checked>
                                <span class="format-title">JSON</span>
                                <span class="format-desc">Recommended for re-importing</span>
                            </label>
                            
                            <label class="format-option">
                                <input type="radio" name="export_format" value="csv">
                                <span class="format-title">CSV</span>
                                <span class="format-desc">For spreadsheet analysis</span>
                            </label>
                            
                            <label class="format-option">
                                <input type="radio" name="export_format" value="xml">
                                <span class="format-title">XML</span>
                                <span class="format-desc">For external integrations</span>
                            </label>
                        </div>
                        
                        <div class="export-actions">
                            <button type="submit" name="action" value="export_polls" class="button button-primary button-large">
                                <span class="dashicons dashicons-download"></span>
                                Export Data
                            </button>
                            
                            <button type="button" class="button button-secondary" id="preview-export">
                                <span class="dashicons dashicons-visibility"></span>
                                Preview Export
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Import Section -->
        <div class="pollmaster-card import-section">
            <div class="card-header">
                <h2><span class="dashicons dashicons-download"></span> Import Data</h2>
                <p>Import polls and settings from a previously exported file.</p>
            </div>
            
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="import-form">
                    <?php wp_nonce_field('pollmaster_export_action', 'pollmaster_nonce'); ?>
                    
                    <div class="import-file-selection">
                        <div class="file-upload-area" id="file-upload-area">
                            <div class="upload-icon">
                                <span class="dashicons dashicons-cloud-upload"></span>
                            </div>
                            <div class="upload-text">
                                <p><strong>Choose a file to import</strong></p>
                                <p>or drag and drop it here</p>
                                <p class="file-types">Supported: .json, .csv, .xml (Max: 10MB)</p>
                            </div>
                            <input type="file" id="import_file" name="import_file" accept=".json,.csv,.xml" required>
                        </div>
                        
                        <div class="file-info" id="file-info" style="display: none;">
                            <div class="file-details">
                                <span class="file-name"></span>
                                <span class="file-size"></span>
                                <button type="button" class="remove-file" id="remove-file">
                                    <span class="dashicons dashicons-no"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="import-options">
                        <h3>Import Options</h3>
                        
                        <div class="import-mode">
                            <label class="import-option">
                                <input type="radio" name="import_mode" value="merge" checked>
                                <span class="option-title">Merge Data</span>
                                <span class="option-desc">Add imported data to existing polls</span>
                            </label>
                            
                            <label class="import-option">
                                <input type="radio" name="import_mode" value="replace">
                                <span class="option-title">Replace Data</span>
                                <span class="option-desc">Replace existing polls with imported data</span>
                            </label>
                        </div>
                        
                        <div class="import-settings">
                            <label>
                                <input type="checkbox" name="import_settings" value="1" checked>
                                Import plugin settings
                            </label>
                            
                            <label>
                                <input type="checkbox" name="import_votes" value="1" checked>
                                Import vote data
                            </label>
                            
                            <label>
                                <input type="checkbox" name="validate_data" value="1" checked>
                                Validate data before import
                            </label>
                            
                            <label>
                                <input type="checkbox" name="create_backup" value="1" checked>
                                Create backup before import
                            </label>
                        </div>
                    </div>
                    
                    <div class="import-actions">
                        <button type="submit" name="action" value="import_data" class="button button-primary button-large" disabled id="import-btn">
                            <span class="dashicons dashicons-upload"></span>
                            Import Data
                        </button>
                        
                        <button type="button" class="button button-secondary" id="validate-import" disabled>
                            <span class="dashicons dashicons-yes-alt"></span>
                            Validate File
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="pollmaster-card quick-actions">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-tools"></span> Quick Actions</h2>
            </div>
            
            <div class="card-body">
                <div class="quick-action-grid">
                    <a href="#" class="quick-action-btn" id="export-all-polls">
                        <span class="dashicons dashicons-database-export"></span>
                        <span class="action-title">Export All Polls</span>
                        <span class="action-desc">Download complete poll database</span>
                    </a>
                    
                    <a href="#" class="quick-action-btn" id="export-settings">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <span class="action-title">Export Settings</span>
                        <span class="action-desc">Download plugin configuration</span>
                    </a>
                    
                    <a href="#" class="quick-action-btn" id="export-votes">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <span class="action-title">Export Vote Data</span>
                        <span class="action-desc">Download voting statistics</span>
                    </a>
                    
                    <a href="#" class="quick-action-btn" id="create-backup">
                        <span class="dashicons dashicons-backup"></span>
                        <span class="action-title">Create Backup</span>
                        <span class="action-desc">Full system backup</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Import Progress Modal -->
    <div id="import-progress-modal" class="pollmaster-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Importing Data</h3>
            </div>
            <div class="modal-body">
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="import-progress-fill"></div>
                    </div>
                    <div class="progress-text" id="import-progress-text">Preparing import...</div>
                </div>
                <div class="import-log" id="import-log"></div>
            </div>
        </div>
    </div>
    
    <!-- Export Preview Modal -->
    <div id="export-preview-modal" class="pollmaster-modal" style="display: none;">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Export Preview</h3>
                <button type="button" class="modal-close" id="close-preview-modal">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="preview-tabs">
                    <button type="button" class="tab-btn active" data-tab="summary">Summary</button>
                    <button type="button" class="tab-btn" data-tab="data">Data Preview</button>
                </div>
                
                <div class="tab-content" id="summary-tab">
                    <div class="preview-summary" id="preview-summary"></div>
                </div>
                
                <div class="tab-content" id="data-tab" style="display: none;">
                    <pre class="preview-data" id="preview-data"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" id="close-preview-modal-btn">Close</button>
                <button type="button" class="button button-primary" id="proceed-export">Proceed with Export</button>
            </div>
        </div>
    </div>
</div>

<style>
.pollmaster-export-page {
    max-width: 1200px;
}

.pollmaster-export-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.pollmaster-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    background: #f9f9f9;
}

.card-header h2 {
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
}

.card-header p {
    margin: 0;
    color: #666;
}

.card-body {
    padding: 20px;
}

.export-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    margin-top: 5px;
}

.export-type-selection,
.export-filters,
.export-format,
.import-options {
    margin-bottom: 30px;
}

.export-type-selection h3,
.export-filters h3,
.export-format h3,
.import-options h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
}

.export-option,
.format-option,
.import-option {
    display: block;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.export-option:hover,
.format-option:hover,
.import-option:hover {
    border-color: #0073aa;
    background: #f8f9fa;
}

.export-option input,
.format-option input,
.import-option input {
    margin-right: 10px;
}

.option-title,
.format-title {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

.option-desc,
.format-desc {
    display: block;
    font-size: 12px;
    color: #666;
}

.filter-row {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    gap: 10px;
}

.filter-row label {
    min-width: 100px;
    font-weight: bold;
}

.export-actions,
.import-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.file-upload-area {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.file-upload-area:hover {
    border-color: #0073aa;
    background: #f8f9fa;
}

.file-upload-area.dragover {
    border-color: #0073aa;
    background: #e3f2fd;
}

.upload-icon {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 15px;
}

.upload-text p {
    margin: 5px 0;
}

.file-types {
    font-size: 12px;
    color: #666;
}

#import_file {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.file-info {
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 15px;
}

.file-details {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.remove-file {
    background: none;
    border: none;
    color: #dc3232;
    cursor: pointer;
    padding: 5px;
}

.import-mode {
    margin-bottom: 20px;
}

.import-settings label {
    display: block;
    margin-bottom: 10px;
}

.quick-actions {
    grid-column: 1 / -1;
}

.quick-action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.quick-action-btn {
    display: block;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
    text-align: center;
}

.quick-action-btn:hover {
    border-color: #0073aa;
    background: #f8f9fa;
    color: inherit;
    text-decoration: none;
}

.quick-action-btn .dashicons {
    font-size: 32px;
    display: block;
    margin-bottom: 10px;
    color: #0073aa;
}

.action-title {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

.action-desc {
    display: block;
    font-size: 12px;
    color: #666;
}

.pollmaster-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    border-radius: 4px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow: auto;
}

.modal-content.large {
    max-width: 800px;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 5px;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

.modal-footer .button {
    margin-left: 10px;
}

.progress-container {
    margin-bottom: 20px;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: #0073aa;
    width: 0%;
    transition: width 0.3s;
}

.progress-text {
    text-align: center;
    font-weight: bold;
}

.import-log {
    max-height: 200px;
    overflow-y: auto;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
}

.preview-tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.tab-btn {
    background: none;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
}

.tab-btn.active {
    border-bottom-color: #0073aa;
    color: #0073aa;
}

.preview-data {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    max-height: 400px;
    overflow: auto;
    font-size: 12px;
}

@media (max-width: 768px) {
    .pollmaster-export-container {
        grid-template-columns: 1fr;
    }
    
    .export-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .filter-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filter-row label {
        min-width: auto;
    }
    
    .export-actions,
    .import-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .quick-action-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // File upload handling
    const fileUploadArea = $('#file-upload-area');
    const fileInput = $('#import_file');
    const fileInfo = $('#file-info');
    const importBtn = $('#import-btn');
    const validateBtn = $('#validate-import');
    
    // Drag and drop functionality
    fileUploadArea.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    fileUploadArea.on('dragleave dragend', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });
    
    fileUploadArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            fileInput[0].files = files;
            handleFileSelection(files[0]);
        }
    });
    
    // File input change
    fileInput.on('change', function() {
        if (this.files.length > 0) {
            handleFileSelection(this.files[0]);
        }
    });
    
    // Handle file selection
    function handleFileSelection(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['application/json', 'text/csv', 'application/xml', 'text/xml'];
        
        if (file.size > maxSize) {
            alert('File size exceeds 10MB limit.');
            return;
        }
        
        if (!allowedTypes.includes(file.type) && !file.name.match(/\.(json|csv|xml)$/i)) {
            alert('Invalid file type. Please select a JSON, CSV, or XML file.');
            return;
        }
        
        // Show file info
        fileInfo.show();
        fileInfo.find('.file-name').text(file.name);
        fileInfo.find('.file-size').text(formatFileSize(file.size));
        
        // Enable buttons
        importBtn.prop('disabled', false);
        validateBtn.prop('disabled', false);
        
        // Hide upload area
        fileUploadArea.hide();
    }
    
    // Remove file
    $('#remove-file').on('click', function() {
        fileInput.val('');
        fileInfo.hide();
        fileUploadArea.show();
        importBtn.prop('disabled', true);
        validateBtn.prop('disabled', true);
    });
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Export preview
    $('#preview-export').on('click', function() {
        const formData = $('.export-form').serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pollmaster_preview_export',
                nonce: pollmaster_admin.nonce,
                form_data: formData
            },
            success: function(response) {
                if (response.success) {
                    showExportPreview(response.data);
                } else {
                    alert('Error generating preview: ' + response.data);
                }
            },
            error: function() {
                alert('Error generating preview.');
            }
        });
    });
    
    // Show export preview modal
    function showExportPreview(data) {
        $('#preview-summary').html(data.summary);
        $('#preview-data').text(data.preview);
        $('#export-preview-modal').show();
    }
    
    // Close preview modal
    $('#close-preview-modal, #close-preview-modal-btn').on('click', function() {
        $('#export-preview-modal').hide();
    });
    
    // Preview tabs
    $('.tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').hide();
        $('#' + tab + '-tab').show();
    });
    
    // Proceed with export
    $('#proceed-export').on('click', function() {
        $('#export-preview-modal').hide();
        $('.export-form').submit();
    });
    
    // Validate import file
    $('#validate-import').on('click', function() {
        const formData = new FormData($('.import-form')[0]);
        formData.append('action', 'pollmaster_validate_import');
        formData.append('nonce', pollmaster_admin.nonce);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('File validation successful: ' + response.data.message);
                } else {
                    alert('Validation failed: ' + response.data);
                }
            },
            error: function() {
                alert('Error validating file.');
            }
        });
    });
    
    // Import form submission
    $('.import-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to import this data? This action cannot be undone.')) {
            return;
        }
        
        showImportProgress();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        updateImportProgress(percentComplete, 'Uploading file...');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                hideImportProgress();
                if (response.success) {
                    alert('Import completed successfully!');
                    location.reload();
                } else {
                    alert('Import failed: ' + response.data);
                }
            },
            error: function() {
                hideImportProgress();
                alert('Error during import.');
            }
        });
    });
    
    // Show import progress modal
    function showImportProgress() {
        $('#import-progress-modal').show();
        updateImportProgress(0, 'Preparing import...');
    }
    
    // Update import progress
    function updateImportProgress(percent, text) {
        $('#import-progress-fill').css('width', percent + '%');
        $('#import-progress-text').text(text);
    }
    
    // Hide import progress modal
    function hideImportProgress() {
        $('#import-progress-modal').hide();
    }
    
    // Quick action buttons
    $('#export-all-polls').on('click', function(e) {
        e.preventDefault();
        triggerQuickExport('all_polls');
    });
    
    $('#export-settings').on('click', function(e) {
        e.preventDefault();
        triggerQuickExport('settings');
    });
    
    $('#export-votes').on('click', function(e) {
        e.preventDefault();
        triggerQuickExport('votes');
    });
    
    $('#create-backup').on('click', function(e) {
        e.preventDefault();
        triggerQuickExport('backup');
    });
    
    // Trigger quick export
    function triggerQuickExport(type) {
        window.location.href = ajaxurl + '?action=pollmaster_quick_export&type=' + type + '&nonce=' + pollmaster_admin.nonce;
    }
    
    // Modal close on outside click
    $('.pollmaster-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
});
</script>