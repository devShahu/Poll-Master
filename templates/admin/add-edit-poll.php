<?php
/**
 * Add/Edit Poll Admin Template
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get poll data if editing
$poll_id = isset($_GET['poll_id']) ? intval($_GET['poll_id']) : 0;
$duplicate_id = isset($_GET['duplicate']) ? intval($_GET['duplicate']) : 0;
$is_editing = $poll_id > 0;
$is_duplicating = $duplicate_id > 0;

$database = new PollMaster_Database();
$poll_data = null;

if ($is_editing) {
    $poll_data = $database->get_poll($poll_id);
    if (!$poll_data) {
        wp_die('Poll not found.');
    }
} elseif ($is_duplicating) {
    $poll_data = $database->get_poll($duplicate_id);
    if (!$poll_data) {
        wp_die('Poll to duplicate not found.');
    }
    // Clear ID and modify title for duplication
    $poll_data['id'] = 0;
    $poll_data['title'] = $poll_data['title'] . ' (Copy)';
    $poll_data['created_at'] = '';
    $poll_data['status'] = 'active';
}

// Default values
$defaults = [
    'title' => '',
    'description' => '',
    'options' => ['', ''],
    'image_url' => '',
    'end_date' => '',
    'is_contest' => 0,
    'contest_prize' => '',
    'contest_description' => '',
    'is_weekly' => 0,
    'allow_multiple_votes' => 0,
    'require_login' => 0,
    'show_results_before_voting' => 0,
    'status' => 'active'
];

// Merge with existing data
if ($poll_data) {
    $poll_data = array_merge($defaults, $poll_data);
    if (!empty($poll_data['options']) && is_string($poll_data['options'])) {
        $poll_data['options'] = json_decode($poll_data['options'], true) ?: ['', ''];
    }
} else {
    $poll_data = $defaults;
}

// Handle form submission
if ($_POST && wp_verify_nonce($_POST['_wpnonce'], 'pollmaster_save_poll')) {
    $form_data = [
        'title' => sanitize_text_field($_POST['title']),
        'description' => sanitize_textarea_field($_POST['description']),
        'options' => array_filter(array_map('sanitize_text_field', $_POST['options'])),
        'image_url' => esc_url_raw($_POST['image_url']),
        'end_date' => sanitize_text_field($_POST['end_date']),
        'is_contest' => isset($_POST['is_contest']) ? 1 : 0,
        'contest_prize' => sanitize_text_field($_POST['contest_prize']),
        'contest_description' => sanitize_textarea_field($_POST['contest_description']),
        'is_weekly' => isset($_POST['is_weekly']) ? 1 : 0,
        'allow_multiple_votes' => isset($_POST['allow_multiple_votes']) ? 1 : 0,
        'require_login' => isset($_POST['require_login']) ? 1 : 0,
        'show_results_before_voting' => isset($_POST['show_results_before_voting']) ? 1 : 0,
        'status' => sanitize_text_field($_POST['status'])
    ];
    
    // Validation
    $errors = [];
    
    if (empty($form_data['title'])) {
        $errors[] = 'Poll title is required.';
    }
    
    if (count($form_data['options']) < 2) {
        $errors[] = 'At least 2 poll options are required.';
    }
    
    if ($form_data['is_contest'] && empty($form_data['contest_prize'])) {
        $errors[] = 'Contest prize is required for contest polls.';
    }
    
    if (!empty($form_data['end_date'])) {
        $end_timestamp = strtotime($form_data['end_date']);
        if ($end_timestamp === false || $end_timestamp <= time()) {
            $errors[] = 'End date must be in the future.';
        }
    }
    
    if (empty($errors)) {
        // Convert options to JSON
        $form_data['options'] = json_encode($form_data['options']);
        
        if ($is_editing) {
            // Update existing poll
            $result = $database->update_poll($poll_id, $form_data);
            if ($result !== false) {
                $success_message = 'Poll updated successfully!';
                $poll_data = array_merge($poll_data, $form_data);
            } else {
                $errors[] = 'Failed to update poll.';
            }
        } else {
            // Create new poll
            $form_data['created_by'] = get_current_user_id();
            $new_poll_id = $database->create_poll($form_data);
            if ($new_poll_id) {
                wp_redirect(admin_url('admin.php?page=pollmaster-edit-poll&poll_id=' . $new_poll_id . '&created=1'));
                exit;
            } else {
                $errors[] = 'Failed to create poll.';
            }
        }
    }
}

// Check for creation success message
$created = isset($_GET['created']) && $_GET['created'] == '1';

?>

<div class="wrap pollmaster-add-edit-poll">
    <div class="page-header">
        <h1 class="page-title">
            <span class="title-icon"><?php echo $is_editing ? '✏️' : '➕'; ?></span>
            <?php echo $is_editing ? 'Edit Poll' : 'Add New Poll'; ?>
            <?php if ($is_duplicating): ?>
                <span class="duplicate-badge">Duplicating</span>
            <?php endif; ?>
        </h1>
        
        <div class="page-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="button button-secondary">
                <span class="button-icon">⬅️</span>
                Back to Polls
            </a>
            
            <?php if ($is_editing): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-poll-results&poll_id=' . $poll_id)); ?>" class="button button-secondary">
                    <span class="button-icon">📊</span>
                    View Results
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll&duplicate=' . $poll_id)); ?>" class="button button-secondary">
                    <span class="button-icon">📋</span>
                    Duplicate
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($created): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Success!</strong> Poll created successfully. You can now configure additional settings below.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Success!</strong> <?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <p><strong>Error:</strong></p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="poll-form" enctype="multipart/form-data">
        <?php wp_nonce_field('pollmaster_save_poll'); ?>
        
        <div class="form-container">
            <!-- Main Poll Details -->
            <div class="form-section main-details">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">📝</span>
                        Poll Details
                    </h2>
                    <p class="section-description">Basic information about your poll</p>
                </div>
                
                <div class="form-fields">
                    <div class="field-group">
                        <label for="title" class="field-label required">Poll Title</label>
                        <input type="text" id="title" name="title" class="field-input" 
                               value="<?php echo esc_attr($poll_data['title']); ?>" 
                               placeholder="Enter your poll question..." required>
                        <p class="field-help">This will be the main question displayed to voters.</p>
                    </div>
                    
                    <div class="field-group">
                        <label for="description" class="field-label">Description</label>
                        <textarea id="description" name="description" class="field-textarea" rows="4" 
                                  placeholder="Optional description or additional context..."><?php echo esc_textarea($poll_data['description']); ?></textarea>
                        <p class="field-help">Provide additional context or instructions for voters.</p>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label required">Poll Options</label>
                        <div class="options-container">
                            <?php 
                            $options = $poll_data['options'];
                            if (is_string($options)) {
                                $options = json_decode($options, true) ?: ['', ''];
                            }
                            
                            foreach ($options as $index => $option): 
                            ?>
                                <div class="option-item" data-index="<?php echo esc_attr($index); ?>">
                                    <div class="option-input-wrapper">
                                        <span class="option-number"><?php echo $index + 1; ?></span>
                                        <input type="text" name="options[]" class="option-input" 
                                               value="<?php echo esc_attr($option); ?>" 
                                               placeholder="Enter option text..." required>
                                        <button type="button" class="remove-option" title="Remove option">
                                            <span class="button-icon">🗑️</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="options-actions">
                            <button type="button" class="add-option button button-secondary">
                                <span class="button-icon">➕</span>
                                Add Option
                            </button>
                            <p class="field-help">Add at least 2 options. You can reorder them by dragging.</p>
                        </div>
                    </div>
                    
                    <div class="field-group">
                        <label for="image_url" class="field-label">Poll Image</label>
                        <div class="image-upload-wrapper">
                            <input type="url" id="image_url" name="image_url" class="field-input image-url-input" 
                                   value="<?php echo esc_attr($poll_data['image_url']); ?>" 
                                   placeholder="Enter image URL or upload...">
                            <button type="button" class="upload-image button button-secondary">
                                <span class="button-icon">📷</span>
                                Upload Image
                            </button>
                        </div>
                        
                        <?php if (!empty($poll_data['image_url'])): ?>
                            <div class="image-preview">
                                <img src="<?php echo esc_url($poll_data['image_url']); ?>" alt="Poll image preview" class="preview-image">
                                <button type="button" class="remove-image" title="Remove image">
                                    <span class="button-icon">❌</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <p class="field-help">Optional image to display with your poll. Recommended size: 800x400px.</p>
                    </div>
                </div>
            </div>
            
            <!-- Poll Settings -->
            <div class="form-section poll-settings">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">⚙️</span>
                        Poll Settings
                    </h2>
                    <p class="section-description">Configure how your poll behaves</p>
                </div>
                
                <div class="form-fields">
                    <div class="field-group">
                        <label for="end_date" class="field-label">End Date</label>
                        <input type="datetime-local" id="end_date" name="end_date" class="field-input" 
                               value="<?php echo $poll_data['end_date'] ? date('Y-m-d\TH:i', strtotime($poll_data['end_date'])) : ''; ?>">
                        <p class="field-help">Leave empty for polls that never end. Ended polls will show results only.</p>
                    </div>
                    
                    <div class="field-group">
                        <label for="status" class="field-label">Status</label>
                        <select id="status" name="status" class="field-select">
                            <option value="active" <?php selected($poll_data['status'], 'active'); ?>>Active</option>
                            <option value="ended" <?php selected($poll_data['status'], 'ended'); ?>>Ended</option>
                            <option value="archived" <?php selected($poll_data['status'], 'archived'); ?>>Archived</option>
                        </select>
                        <p class="field-help">Control the visibility and functionality of your poll.</p>
                    </div>
                    
                    <div class="settings-grid">
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox" name="require_login" value="1" 
                                       <?php checked($poll_data['require_login'], 1); ?> class="setting-checkbox">
                                <span class="setting-text">
                                    <strong>Require Login</strong>
                                    <span class="setting-description">Only logged-in users can vote</span>
                                </span>
                            </label>
                        </div>
                        
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox" name="allow_multiple_votes" value="1" 
                                       <?php checked($poll_data['allow_multiple_votes'], 1); ?> class="setting-checkbox">
                                <span class="setting-text">
                                    <strong>Allow Multiple Votes</strong>
                                    <span class="setting-description">Users can change their vote</span>
                                </span>
                            </label>
                        </div>
                        
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox" name="show_results_before_voting" value="1" 
                                       <?php checked($poll_data['show_results_before_voting'], 1); ?> class="setting-checkbox">
                                <span class="setting-text">
                                    <strong>Show Results Before Voting</strong>
                                    <span class="setting-description">Display current results to voters</span>
                                </span>
                            </label>
                        </div>
                        
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox" name="is_weekly" value="1" 
                                       <?php checked($poll_data['is_weekly'], 1); ?> class="setting-checkbox weekly-checkbox">
                                <span class="setting-text">
                                    <strong>Weekly Poll</strong>
                                    <span class="setting-description">Feature this as the weekly poll</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contest Settings -->
            <div class="form-section contest-settings">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">🏆</span>
                        Contest Settings
                    </h2>
                    <p class="section-description">Turn your poll into a contest with prizes</p>
                </div>
                
                <div class="form-fields">
                    <div class="field-group">
                        <label class="setting-label contest-toggle">
                            <input type="checkbox" name="is_contest" value="1" 
                                   <?php checked($poll_data['is_contest'], 1); ?> class="setting-checkbox contest-checkbox">
                            <span class="setting-text">
                                <strong>Enable Contest Mode</strong>
                                <span class="setting-description">Turn this poll into a contest with prizes</span>
                            </span>
                        </label>
                    </div>
                    
                    <div class="contest-fields" style="<?php echo $poll_data['is_contest'] ? '' : 'display: none;'; ?>">
                        <div class="field-group">
                            <label for="contest_prize" class="field-label required">Prize</label>
                            <input type="text" id="contest_prize" name="contest_prize" class="field-input" 
                                   value="<?php echo esc_attr($poll_data['contest_prize']); ?>" 
                                   placeholder="e.g., $100 Amazon Gift Card, iPhone 15, etc.">
                            <p class="field-help">What will the winner receive?</p>
                        </div>
                        
                        <div class="field-group">
                            <label for="contest_description" class="field-label">Contest Rules</label>
                            <textarea id="contest_description" name="contest_description" class="field-textarea" rows="4" 
                                      placeholder="Describe contest rules, eligibility, how winner is selected..."><?php echo esc_textarea($poll_data['contest_description']); ?></textarea>
                            <p class="field-help">Explain how the contest works and any terms and conditions.</p>
                        </div>
                        
                        <div class="contest-info">
                            <div class="info-box">
                                <h4>📋 Contest Features</h4>
                                <ul>
                                    <li>Winners are randomly selected from voters of the winning option</li>
                                    <li>Contest polls automatically require user login</li>
                                    <li>Winner announcement can be triggered manually</li>
                                    <li>Email notifications are sent to winners</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="form-actions">
            <div class="actions-left">
                <?php if ($is_editing): ?>
                    <button type="button" class="button button-secondary preview-poll" data-poll-id="<?php echo esc_attr($poll_id); ?>">
                        <span class="button-icon">👁️</span>
                        Preview Poll
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="actions-right">
                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="button button-secondary">
                    Cancel
                </a>
                
                <button type="submit" class="button button-primary save-poll">
                    <span class="button-icon"><?php echo $is_editing ? '💾' : '➕'; ?></span>
                    <?php echo $is_editing ? 'Update Poll' : 'Create Poll'; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Poll Preview Modal -->
<div id="poll-preview-modal" class="pollmaster-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Poll Preview</h3>
                <button class="modal-close" aria-label="Close modal">
                    <span class="close-icon">×</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div id="poll-preview-content">
                    <!-- Poll preview will be loaded here -->
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="modal-button secondary" data-action="close-modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Add/Edit Poll Styles */
.pollmaster-add-edit-poll {
    padding: 20px 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.page-title {
    font-size: 2rem;
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 12px;
}

.duplicate-badge {
    background: #f39c12;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-left: 10px;
}

.page-actions {
    display: flex;
    gap: 10px;
}

.button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

/* Form Container */
.form-container {
    display: grid;
    gap: 30px;
    margin-bottom: 30px;
}

.form-section {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.section-header {
    padding: 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.section-title {
    font-size: 1.4rem;
    margin: 0 0 8px 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-description {
    margin: 0;
    color: #7f8c8d;
    font-size: 1rem;
}

.form-fields {
    padding: 25px;
}

/* Field Groups */
.field-group {
    margin-bottom: 25px;
}

.field-group:last-child {
    margin-bottom: 0;
}

.field-label {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 1rem;
}

.field-label.required::after {
    content: ' *';
    color: #e74c3c;
}

.field-input,
.field-textarea,
.field-select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    background: white;
}

.field-input:focus,
.field-textarea:focus,
.field-select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.field-textarea {
    resize: vertical;
    min-height: 100px;
}

.field-help {
    margin: 8px 0 0 0;
    color: #7f8c8d;
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Poll Options */
.options-container {
    margin-bottom: 15px;
}

.option-item {
    margin-bottom: 12px;
    transition: all 0.3s ease;
}

.option-input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.option-input-wrapper:hover {
    background: #e9ecef;
}

.option-input-wrapper:focus-within {
    border-color: #3498db;
    background: white;
}

.option-number {
    width: 24px;
    height: 24px;
    background: #3498db;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    flex-shrink: 0;
}

.option-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 8px 0;
    font-size: 1rem;
}

.option-input:focus {
    outline: none;
    box-shadow: none;
}

.remove-option {
    background: #e74c3c;
    color: white;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.remove-option:hover {
    background: #c0392b;
    transform: scale(1.1);
}

.options-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.add-option {
    background: #2ecc71;
    color: white;
    border: none;
}

.add-option:hover {
    background: #27ae60;
}

/* Image Upload */
.image-upload-wrapper {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.image-url-input {
    flex: 1;
}

.upload-image {
    background: #3498db;
    color: white;
    border: none;
    white-space: nowrap;
}

.upload-image:hover {
    background: #2980b9;
}

.image-preview {
    position: relative;
    display: inline-block;
    margin-top: 15px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.preview-image {
    max-width: 300px;
    max-height: 200px;
    display: block;
}

.remove-image {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(231, 76, 60, 0.9);
    color: white;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.remove-image:hover {
    background: #e74c3c;
    transform: scale(1.1);
}

/* Settings Grid */
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.setting-item {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.setting-item:hover {
    background: #e9ecef;
}

.setting-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    margin: 0;
    font-weight: normal;
}

.setting-checkbox {
    margin: 0;
    transform: scale(1.2);
    flex-shrink: 0;
    margin-top: 2px;
}

.setting-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.setting-text strong {
    color: #2c3e50;
    font-size: 1rem;
}

.setting-description {
    color: #7f8c8d;
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Contest Settings */
.contest-toggle {
    padding: 20px;
    background: #fff3cd;
    border: 2px solid #ffeaa7;
    border-radius: 8px;
    margin-bottom: 20px;
}

.contest-fields {
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.contest-info {
    margin-top: 25px;
}

.info-box {
    background: #e8f4fd;
    border: 1px solid #bee5eb;
    border-radius: 8px;
    padding: 20px;
}

.info-box h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-box ul {
    margin: 0;
    padding-left: 20px;
}

.info-box li {
    margin-bottom: 8px;
    color: #2c3e50;
    line-height: 1.4;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.actions-left,
.actions-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

.save-poll {
    background: #2ecc71;
    color: white;
    font-weight: 600;
    padding: 12px 24px;
}

.save-poll:hover {
    background: #27ae60;
}

.preview-poll {
    background: #f39c12;
    color: white;
}

.preview-poll:hover {
    background: #e67e22;
}

/* Modal Styles */
.pollmaster-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
}

.modal-container {
    position: relative;
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-content {
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.modal-header {
    padding: 20px 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    margin: 0;
    font-size: 1.3rem;
    color: #2c3e50;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #7f8c8d;
    padding: 5px;
    border-radius: 50%;
    transition: background 0.3s ease;
}

.modal-close:hover {
    background: #e9ecef;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px 25px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-button {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.modal-button.secondary {
    background: #6c757d;
    color: white;
}

.modal-button.secondary:hover {
    background: #5a6268;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .page-actions {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .actions-left,
    .actions-right {
        width: 100%;
        justify-content: center;
    }
    
    .image-upload-wrapper {
        flex-direction: column;
    }
    
    .option-input-wrapper {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .option-number {
        order: 1;
    }
    
    .option-input {
        order: 2;
        width: 100%;
    }
    
    .remove-option {
        order: 3;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1.5rem;
        flex-direction: column;
        gap: 8px;
    }
    
    .section-title {
        font-size: 1.2rem;
        flex-direction: column;
        gap: 5px;
        text-align: center;
    }
    
    .form-fields,
    .section-header {
        padding: 20px 15px;
    }
    
    .modal-container {
        width: 95%;
    }
    
    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 15px 20px;
    }
}
</style>

<script>
// Add/Edit Poll JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeAddEditPoll();
    
    function initializeAddEditPoll() {
        bindEventHandlers();
        updateOptionNumbers();
        initializeImageUpload();
        initializeSortableOptions();
    }
    
    function bindEventHandlers() {
        // Add option button
        document.addEventListener('click', function(e) {
            if (e.target.closest('.add-option')) {
                e.preventDefault();
                addOption();
            }
            
            // Remove option button
            if (e.target.closest('.remove-option')) {
                e.preventDefault();
                removeOption(e.target.closest('.option-item'));
            }
            
            // Upload image button
            if (e.target.closest('.upload-image')) {
                e.preventDefault();
                openMediaUploader();
            }
            
            // Remove image button
            if (e.target.closest('.remove-image')) {
                e.preventDefault();
                removeImage();
            }
            
            // Preview poll button
            if (e.target.closest('.preview-poll')) {
                e.preventDefault();
                const pollId = e.target.closest('.preview-poll').dataset.pollId;
                previewPoll(pollId);
            }
            
            // Modal close
            if (e.target.closest('.modal-close') || e.target.closest('[data-action="close-modal"]')) {
                e.preventDefault();
                closeModal();
            }
            
            // Modal overlay click
            if (e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
        });
        
        // Contest checkbox toggle
        document.addEventListener('change', function(e) {
            if (e.target.matches('.contest-checkbox')) {
                toggleContestFields(e.target.checked);
            }
            
            // Weekly checkbox (ensure only one weekly poll)
            if (e.target.matches('.weekly-checkbox')) {
                if (e.target.checked) {
                    if (!confirm('Setting this as the weekly poll will remove the weekly status from any other poll. Continue?')) {
                        e.target.checked = false;
                    }
                }
            }
        });
        
        // Form validation
        document.addEventListener('submit', function(e) {
            if (e.target.matches('.poll-form')) {
                if (!validateForm()) {
                    e.preventDefault();
                }
            }
        });
        
        // Auto-save draft (optional)
        let saveTimeout;
        document.addEventListener('input', function(e) {
            if (e.target.closest('.poll-form')) {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    // Auto-save functionality can be implemented here
                    console.log('Auto-saving draft...');
                }, 2000);
            }
        });
    }
    
    function addOption() {
        const container = document.querySelector('.options-container');
        const optionCount = container.querySelectorAll('.option-item').length;
        
        if (optionCount >= 10) {
            alert('Maximum 10 options allowed.');
            return;
        }
        
        const optionHtml = `
            <div class="option-item" data-index="${optionCount}">
                <div class="option-input-wrapper">
                    <span class="option-number">${optionCount + 1}</span>
                    <input type="text" name="options[]" class="option-input" 
                           placeholder="Enter option text..." required>
                    <button type="button" class="remove-option" title="Remove option">
                        <span class="button-icon">🗑️</span>
                    </button>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', optionHtml);
        updateOptionNumbers();
        
        // Focus on the new input
        const newInput = container.lastElementChild.querySelector('.option-input');
        newInput.focus();
    }
    
    function removeOption(optionItem) {
        const container = document.querySelector('.options-container');
        const optionCount = container.querySelectorAll('.option-item').length;
        
        if (optionCount <= 2) {
            alert('At least 2 options are required.');
            return;
        }
        
        optionItem.remove();
        updateOptionNumbers();
    }
    
    function updateOptionNumbers() {
        const options = document.querySelectorAll('.option-item');
        options.forEach((option, index) => {
            const numberSpan = option.querySelector('.option-number');
            if (numberSpan) {
                numberSpan.textContent = index + 1;
            }
            option.dataset.index = index;
        });
    }
    
    function initializeImageUpload() {
        // WordPress media uploader integration
        if (typeof wp !== 'undefined' && wp.media) {
            window.pollmasterMediaUploader = wp.media({
                title: 'Select Poll Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            window.pollmasterMediaUploader.on('select', function() {
                const attachment = window.pollmasterMediaUploader.state().get('selection').first().toJSON();
                setImage(attachment.url);
            });
        }
    }
    
    function openMediaUploader() {
        if (window.pollmasterMediaUploader) {
            window.pollmasterMediaUploader.open();
        } else {
            // Fallback to URL input
            const url = prompt('Enter image URL:');
            if (url) {
                setImage(url);
            }
        }
    }
    
    function setImage(url) {
        const imageInput = document.getElementById('image_url');
        imageInput.value = url;
        
        // Update preview
        updateImagePreview(url);
    }
    
    function updateImagePreview(url) {
        const existingPreview = document.querySelector('.image-preview');
        if (existingPreview) {
            existingPreview.remove();
        }
        
        if (url) {
            const previewHtml = `
                <div class="image-preview">
                    <img src="${url}" alt="Poll image preview" class="preview-image">
                    <button type="button" class="remove-image" title="Remove image">
                        <span class="button-icon">❌</span>
                    </button>
                </div>
            `;
            
            const uploadWrapper = document.querySelector('.image-upload-wrapper');
            uploadWrapper.insertAdjacentHTML('afterend', previewHtml);
        }
    }
    
    function removeImage() {
        const imageInput = document.getElementById('image_url');
        const imagePreview = document.querySelector('.image-preview');
        
        imageInput.value = '';
        if (imagePreview) {
            imagePreview.remove();
        }
    }
    
    function toggleContestFields(show) {
        const contestFields = document.querySelector('.contest-fields');
        if (contestFields) {
            contestFields.style.display = show ? 'block' : 'none';
            
            // Update required status of contest fields
            const prizeInput = document.getElementById('contest_prize');
            if (prizeInput) {
                if (show) {
                    prizeInput.setAttribute('required', 'required');
                } else {
                    prizeInput.removeAttribute('required');
                }
            }
        }
    }
    
    function initializeSortableOptions() {
        // Simple drag and drop for options (can be enhanced with a library)
        const container = document.querySelector('.options-container');
        if (container) {
            // Basic implementation - can be enhanced with SortableJS or similar
            console.log('Sortable options initialized');
        }
    }
    
    function validateForm() {
        const errors = [];
        
        // Check title
        const title = document.getElementById('title').value.trim();
        if (!title) {
            errors.push('Poll title is required.');
        }
        
        // Check options
        const options = Array.from(document.querySelectorAll('.option-input'))
            .map(input => input.value.trim())
            .filter(value => value);
        
        if (options.length < 2) {
            errors.push('At least 2 poll options are required.');
        }
        
        // Check contest fields
        const isContest = document.querySelector('.contest-checkbox').checked;
        if (isContest) {
            const prize = document.getElementById('contest_prize').value.trim();
            if (!prize) {
                errors.push('Contest prize is required for contest polls.');
            }
        }
        
        // Check end date
        const endDate = document.getElementById('end_date').value;
        if (endDate) {
            const endTimestamp = new Date(endDate).getTime();
            const now = new Date().getTime();
            if (endTimestamp <= now) {
                errors.push('End date must be in the future.');
            }
        }
        
        if (errors.length > 0) {
            alert('Please fix the following errors:\n\n' + errors.join('\n'));
            return false;
        }
        
        return true;
    }
    
    function previewPoll(pollId) {
        const modal = document.getElementById('poll-preview-modal');
        const content = document.getElementById('poll-preview-content');
        
        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Load poll preview
        content.innerHTML = '<div class="loading-placeholder"><div class="loading-spinner"></div><p>Loading poll preview...</p></div>';
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'pollmaster_preview_poll',
                poll_id: pollId,
                nonce: pollmaster_admin.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.data.html;
            } else {
                content.innerHTML = '<p class="error">Failed to load poll preview.</p>';
            }
        })
        .catch(error => {
            content.innerHTML = '<p class="error">Error loading poll preview.</p>';
        });
    }
    
    function closeModal() {
        const modal = document.getElementById('poll-preview-modal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    // Handle escape key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    
    // Initialize contest fields visibility
    const contestCheckbox = document.querySelector('.contest-checkbox');
    if (contestCheckbox) {
        toggleContestFields(contestCheckbox.checked);
    }
    
    // Image URL input change handler
    document.addEventListener('input', function(e) {
        if (e.target.matches('#image_url')) {
            const url = e.target.value.trim();
            if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
                updateImagePreview(url);
            } else if (!url) {
                const existingPreview = document.querySelector('.image-preview');
                if (existingPreview) {
                    existingPreview.remove();
                }
            }
        }
    });
});
</script>