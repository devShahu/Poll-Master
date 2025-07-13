<?php
/**
 * Test page template for PollMaster
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle sample polls creation
if (isset($_POST['create_sample_polls']) && wp_verify_nonce($_POST['_wpnonce'], 'create_sample_polls')) {
    $database = new PollMaster_Database();
    $created_polls = $database->create_sample_polls();
    
    if (!empty($created_polls)) {
        echo '<div class="notice notice-success is-dismissible"><p>Successfully created ' . count($created_polls) . ' sample polls!</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Failed to create sample polls. Please check your database connection.</p></div>';
    }
}

$database = new PollMaster_Database();
$tables_exist = $database->check_tables_exist();
$total_polls = $database->get_polls_count();
?>

<div class="wrap">
    <h1><?php echo esc_html__('PollMaster Plugin Test', 'pollmaster'); ?></h1>
    
    <div class="notice notice-success">
        <p><?php echo esc_html__('Plugin is working correctly!', 'pollmaster'); ?></p>
    </div>
    
    <h2><?php echo esc_html__('Available Pages:', 'pollmaster'); ?></h2>
    
    <ul>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster')); ?>"><?php echo esc_html__('Dashboard', 'pollmaster'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-all-polls')); ?>"><?php echo esc_html__('All Polls', 'pollmaster'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>"><?php echo esc_html__('Manage Polls', 'pollmaster'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-settings')); ?>"><?php echo esc_html__('Settings', 'pollmaster'); ?></a></li>
    </ul>
    
    <div class="card" style="max-width: 600px; margin-top: 20px;">
        <h3><?php echo esc_html__('Plugin Status', 'pollmaster'); ?></h3>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong><?php echo esc_html__('Plugin Version:', 'pollmaster'); ?></strong></td>
                    <td><?php echo esc_html(POLLMASTER_VERSION); ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('WordPress Version:', 'pollmaster'); ?></strong></td>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('PHP Version:', 'pollmaster'); ?></strong></td>
                    <td><?php echo esc_html(PHP_VERSION); ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Database Status:', 'pollmaster'); ?></strong></td>
                    <td>
                        <?php
                        if ($tables_exist) {
                            echo '<span style="color: green;">✓ Tables exist</span>';
                        } else {
                            echo '<span style="color: red;">✗ Tables missing</span>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Total Polls:', 'pollmaster'); ?></strong></td>
                    <td><?php echo esc_html($total_polls); ?> polls</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <?php if ($tables_exist): ?>
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h3><?php echo esc_html__('Testing Tools', 'pollmaster'); ?></h3>
            <p><?php echo esc_html__('Use these tools to test the plugin functionality:', 'pollmaster'); ?></p>
            
            <form method="post" style="margin-bottom: 15px;">
                <?php wp_nonce_field('create_sample_polls'); ?>
                <input type="submit" name="create_sample_polls" class="button button-primary" value="<?php echo esc_attr__('Create Sample Polls', 'pollmaster'); ?>">
                <p class="description"><?php echo esc_html__('This will create 3 sample polls for testing purposes.', 'pollmaster'); ?></p>
            </form>
            
            <div style="margin-top: 20px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-add-poll')); ?>" class="button button-secondary"><?php echo esc_html__('Add New Poll', 'pollmaster'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pollmaster-manage-polls')); ?>" class="button button-secondary"><?php echo esc_html__('View All Polls', 'pollmaster'); ?></a>
            </div>
        </div>
    <?php else: ?>
        <div class="card" style="max-width: 600px; margin-top: 20px; border-left: 4px solid #dc3232;">
            <h3 style="color: #dc3232;"><?php echo esc_html__('Database Issue Detected', 'pollmaster'); ?></h3>
            <p><?php echo esc_html__('The plugin database tables are missing. Please try deactivating and reactivating the plugin.', 'pollmaster'); ?></p>
        </div>
    <?php endif; ?>
</div>