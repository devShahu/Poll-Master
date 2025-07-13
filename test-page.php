<?php
/**
 * Test Page for PollMaster Plugin
 * 
 * This file demonstrates the PollMaster plugin functionality
 * Place this in your active theme directory and create a page with the template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="pollmaster-test-page" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 40px;">PollMaster Plugin Test Page</h1>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px;">
        
        <!-- Latest Poll Popup Shortcode -->
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
            <h2>Latest Poll Popup</h2>
            <p>This will show the latest poll in a popup format:</p>
            <div style="border: 1px solid #ddd; padding: 15px; background: white; border-radius: 4px;">
                <?php echo do_shortcode('[pollmaster_popup type="latest" auto_show="false" show_share="true"]'); ?>
            </div>
        </div>
        
        <!-- Latest Poll Embed -->
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
            <h2>Latest Poll Embed</h2>
            <p>This will show the latest poll embedded in the page:</p>
            <div style="border: 1px solid #ddd; padding: 15px; background: white; border-radius: 4px;">
                <?php echo do_shortcode('[pollmaster_poll type="latest" show_results="false" show_share="true"]'); ?>
            </div>
        </div>
        
        <!-- Poll Results -->
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
            <h2>Poll Results</h2>
            <p>This will show results for the latest poll:</p>
            <div style="border: 1px solid #ddd; padding: 15px; background: white; border-radius: 4px;">
                <?php echo do_shortcode('[pollmaster_results type="latest" show_votes="true" show_percentages="true"]'); ?>
            </div>
        </div>
        
        <!-- Weekly Poll -->
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
            <h2>Weekly Poll</h2>
            <p>This will show the current weekly poll:</p>
            <div style="border: 1px solid #ddd; padding: 15px; background: white; border-radius: 4px;">
                <?php echo do_shortcode('[pollmaster_poll type="weekly" show_share="true"]'); ?>
            </div>
        </div>
        
        <!-- Contest Poll -->
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
            <h2>Contest Poll</h2>
            <p>This will show the latest contest:</p>
            <div style="border: 1px solid #ddd; padding: 15px; background: white; border-radius: 4px;">
                <?php echo do_shortcode('[pollmaster_contest show_prize="true" show_end_date="true"]'); ?>
            </div>
        </div>
        
        <!-- Specific Poll -->
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
            <h2>Specific Poll (ID: 1)</h2>
            <p>This will show a specific poll by ID:</p>
            <div style="border: 1px solid #ddd; padding: 15px; background: white; border-radius: 4px;">
                <?php echo do_shortcode('[pollmaster_poll poll_id="1" show_share="true"]'); ?>
            </div>
        </div>
        
    </div>
    
    <!-- Plugin Status Information -->
    <div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
        <h2>Plugin Status</h2>
        <?php
        // Check if PollMaster is active
        if (class_exists('PollMaster')) {
            echo '<p style="color: green;">✓ PollMaster plugin is active and loaded</p>';
            
            // Check database tables
            global $wpdb;
            $polls_table = $wpdb->prefix . 'pollmaster_polls';
            $votes_table = $wpdb->prefix . 'pollmaster_votes';
            
            $polls_exist = $wpdb->get_var("SHOW TABLES LIKE '$polls_table'") == $polls_table;
            $votes_exist = $wpdb->get_var("SHOW TABLES LIKE '$votes_table'") == $votes_table;
            
            if ($polls_exist && $votes_exist) {
                echo '<p style="color: green;">✓ Database tables are created</p>';
                
                // Count polls
                $poll_count = $wpdb->get_var("SELECT COUNT(*) FROM $polls_table WHERE status = 'active'");
                echo '<p>📊 Active polls: ' . $poll_count . '</p>';
                
                if ($poll_count == 0) {
                    echo '<p style="color: orange;">⚠️ No polls found. Please create some polls in the admin area.</p>';
                }
            } else {
                echo '<p style="color: red;">✗ Database tables are missing</p>';
            }
        } else {
            echo '<p style="color: red;">✗ PollMaster plugin is not active</p>';
        }
        ?>
    </div>
    
    <!-- Instructions -->
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px;">
        <h2>Instructions</h2>
        <ol>
            <li><strong>Activate the Plugin:</strong> Make sure PollMaster is activated in your WordPress admin</li>
            <li><strong>Create Polls:</strong> Go to <code>PollMaster → All Polls</code> in your admin and create some polls</li>
            <li><strong>Test Shortcodes:</strong> The shortcodes above will display your polls once created</li>
            <li><strong>Check Functionality:</strong> Test voting, sharing, and popup features</li>
        </ol>
        
        <h3>Available Shortcodes:</h3>
        <ul>
            <li><code>[pollmaster_popup]</code> - Shows poll in popup format</li>
            <li><code>[pollmaster_poll]</code> - Embeds poll in page content</li>
            <li><code>[pollmaster_results]</code> - Shows poll results</li>
            <li><code>[pollmaster_latest]</code> - Shows latest poll</li>
            <li><code>[pollmaster_contest]</code> - Shows contest poll</li>
        </ul>
        
        <h3>Admin Areas:</h3>
        <ul>
            <li><strong>All Polls:</strong> Manage all your polls</li>
            <li><strong>Add New Poll:</strong> Create new polls</li>
            <li><strong>Weekly Polls:</strong> Manage weekly poll schedule</li>
            <li><strong>Contests:</strong> Manage contest polls and winners</li>
            <li><strong>Settings:</strong> Configure plugin options</li>
        </ul>
    </div>
</div>

<style>
/* Additional test page styles */
.pollmaster-test-page h2 {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 18px;
}

.pollmaster-test-page h3 {
    color: #34495e;
    margin: 15px 0 10px 0;
    font-size: 16px;
}

.pollmaster-test-page p {
    margin-bottom: 10px;
    line-height: 1.6;
}

.pollmaster-test-page ul,
.pollmaster-test-page ol {
    margin: 10px 0;
    padding-left: 20px;
}

.pollmaster-test-page li {
    margin-bottom: 5px;
    line-height: 1.5;
}

.pollmaster-test-page code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
}
</style>

<?php
get_footer();
?>