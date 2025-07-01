# PollMaster WordPress Plugin

A comprehensive polling system for WordPress with Elementor integration, contest features, and advanced poll management capabilities.

## Features

### Core Polling System
- **Poll Creation**: Registered users can create polls with multiple options
- **Voting System**: Secure voting with duplicate prevention
- **Real-time Results**: Live poll results with charts and statistics
- **Social Sharing**: Share polls on Facebook, Twitter, WhatsApp, and LinkedIn
- **Responsive Design**: Mobile-friendly interface with accessibility support

### Elementor Integration
- **Pop-up Shortcode**: `[pollmaster_popup]` for seamless Elementor integration
- **Auto-show Options**: Configurable pop-up behavior
- **Dismissal System**: Users can dismiss pop-ups with memory retention
- **Customizable Display**: Various shortcode attributes for flexibility

### Contest Features
- **Photo-voting Contests**: Upload images for visual polls
- **Winner Selection**: Automatic random winner selection from winning option
- **Contest Management**: Admin tools for contest oversight
- **Winner Announcements**: Automated winner notifications

### Admin Features
- **Dashboard**: Comprehensive poll statistics and management
- **Weekly Polls**: Automated weekly poll system
- **Bulk Operations**: Manage multiple polls simultaneously
- **Settings Panel**: Customize colors, behavior, and social sharing
- **Export/Import**: Backup and restore poll data

### Technical Features
- **Custom Database Tables**: Dedicated tables for optimal performance
- **AJAX Integration**: Smooth user experience without page reloads
- **Security**: Nonce verification and input sanitization
- **Cron Jobs**: Automated tasks for poll management
- **REST API**: Programmatic access to poll data

## Installation

1. **Upload Plugin**:
   - Upload the `pollmaster` folder to `/wp-content/plugins/`
   - Or install via WordPress admin: Plugins > Add New > Upload Plugin

2. **Activate Plugin**:
   - Go to Plugins > Installed Plugins
   - Find "PollMaster" and click "Activate"

3. **Database Setup**:
   - Plugin automatically creates required database tables on activation
   - No manual database configuration needed

4. **Configure Settings**:
   - Go to PollMaster > Settings
   - Configure pop-up behavior, colors, and social sharing options

## Usage

### Creating Polls

#### For Users (Frontend)
1. Log in to your WordPress account
2. Go to your user profile or designated poll creation page
3. Fill in poll details:
   - Poll question
   - Multiple choice options (minimum 2)
   - Optional image upload for contests
   - Set end date if desired
4. Submit to create the poll

#### For Admins (Backend)
1. Go to PollMaster > Add New Poll
2. Enter poll information:
   - Title and description
   - Poll options
   - Contest settings (if applicable)
   - Weekly poll designation
3. Save the poll

### Shortcodes

#### `[pollmaster_popup]`
Displays a poll in a pop-up format, perfect for Elementor integration.

**Attributes:**
- `type`: Poll type (`latest`, `weekly`, `contest`, `specific`)
- `id`: Specific poll ID (when type="specific")
- `auto_show`: Auto-show pop-up (`true`/`false`)
- `show_share`: Display share buttons (`true`/`false`)
- `dismissible`: Allow dismissal (`true`/`false`)

**Examples:**
```shortcode
[pollmaster_popup type="latest" auto_show="true"]
[pollmaster_popup type="specific" id="123" show_share="false"]
[pollmaster_popup type="weekly" dismissible="true"]
```

#### `[pollmaster_poll]`
Embeds a poll directly in content.

**Attributes:**
- `id`: Poll ID
- `show_results`: Show results immediately (`true`/`false`)
- `show_share`: Display share buttons (`true`/`false`)

**Example:**
```shortcode
[pollmaster_poll id="123" show_results="false"]
```

#### `[pollmaster_results]`
Displays poll results only.

**Attributes:**
- `id`: Poll ID
- `chart_type`: Chart display (`bar`, `pie`, `list`)

**Example:**
```shortcode
[pollmaster_results id="123" chart_type="pie"]
```

#### `[pollmaster_latest]`
Shows the most recent poll.

**Example:**
```shortcode
[pollmaster_latest]
```

#### `[pollmaster_contest]`
Displays contest-specific polls with enhanced features.

**Attributes:**
- `status`: Contest status (`active`, `ended`, `all`)
- `limit`: Number of contests to show

**Example:**
```shortcode
[pollmaster_contest status="active" limit="5"]
```

### Admin Management

#### Dashboard
- View poll statistics and performance metrics
- Quick access to recent polls and activities
- System status and health checks

#### Manage Polls
- List all polls with filtering and search
- Bulk operations (delete, archive, make weekly)
- Individual poll editing and management

#### Weekly Polls
- Set up automated weekly poll rotation
- Configure weekly poll settings
- Manual weekly poll management

#### Contests
- Manage photo-voting contests
- Announce winners manually or automatically
- View contest statistics and participation

#### Settings
- **Pop-up Behavior**: Auto-show settings, dismissal options
- **Styling**: Custom colors and appearance
- **Social Sharing**: Enable/disable platforms, custom messages
- **Performance**: Caching and optimization settings

## Database Structure

The plugin creates four custom tables:

### `pollmaster_polls`
- `id`: Primary key
- `title`: Poll title
- `description`: Poll description
- `options`: JSON array of poll options
- `image_id`: Attachment ID for contest images
- `is_contest`: Contest flag
- `is_weekly`: Weekly poll flag
- `created_by`: User ID of creator
- `start_date`: Poll start date
- `end_date`: Poll end date
- `status`: Poll status (active, ended, archived)
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### `pollmaster_votes`
- `id`: Primary key
- `poll_id`: Foreign key to polls table
- `user_id`: Voter user ID (0 for guests)
- `option_index`: Selected option index
- `ip_address`: Voter IP address
- `user_agent`: Voter user agent
- `voted_at`: Vote timestamp

### `pollmaster_contest_winners`
- `id`: Primary key
- `poll_id`: Foreign key to polls table
- `user_id`: Winner user ID
- `option_index`: Winning option
- `announced_at`: Announcement timestamp

### `pollmaster_shares`
- `id`: Primary key
- `poll_id`: Foreign key to polls table
- `platform`: Social media platform
- `user_id`: Sharing user ID
- `ip_address`: Sharer IP address
- `shared_at`: Share timestamp

## API Reference

### JavaScript API

#### Frontend API
```javascript
// Show poll pop-up
PollMasterFrontend.showPopup(pollId);

// Hide poll pop-up
PollMasterFrontend.hidePopup();

// Load poll data
PollMasterFrontend.loadPoll(pollId, container);

// Refresh poll results
PollMasterFrontend.refreshResults(pollId);

// Submit vote
PollMasterFrontend.vote(pollId, optionIndex);
```

#### Admin API
```javascript
// Show admin notice
pollMasterAdmin.showNotice('success', 'Message');

// Trigger bulk action
pollMasterAdmin.performBulkAction('delete', [1, 2, 3]);

// Upload image
pollMasterAdmin.uploadImage(file, container);
```

### PHP Hooks

#### Actions
```php
// Before poll creation
do_action('pollmaster_before_create_poll', $poll_data);

// After poll creation
do_action('pollmaster_after_create_poll', $poll_id, $poll_data);

// Before vote submission
do_action('pollmaster_before_vote', $poll_id, $option_index, $user_id);

// After vote submission
do_action('pollmaster_after_vote', $poll_id, $option_index, $user_id);

// Contest winner announced
do_action('pollmaster_contest_winner_announced', $poll_id, $winner_id);
```

#### Filters
```php
// Modify poll data before saving
$poll_data = apply_filters('pollmaster_poll_data', $poll_data);

// Customize poll display
$poll_html = apply_filters('pollmaster_poll_html', $html, $poll_id);

// Modify vote validation
$can_vote = apply_filters('pollmaster_can_vote', true, $poll_id, $user_id);

// Customize social share URLs
$share_url = apply_filters('pollmaster_share_url', $url, $platform, $poll_id);
```

## Customization

### CSS Customization
Add custom styles to your theme's CSS:

```css
/* Customize poll container */
.pollmaster-poll {
    border: 2px solid #your-color;
    border-radius: 10px;
}

/* Style vote buttons */
.pollmaster-option-button {
    background: #your-color;
    color: white;
}

/* Customize results display */
.pollmaster-results .option-bar {
    background: linear-gradient(to right, #start-color, #end-color);
}
```

### Template Overrides
Copy plugin templates to your theme:

1. Create folder: `your-theme/pollmaster/`
2. Copy templates from `plugins/pollmaster/templates/`
3. Modify as needed

### Custom Poll Types
Register custom poll types:

```php
function register_custom_poll_type() {
    add_filter('pollmaster_poll_types', function($types) {
        $types['custom'] = 'Custom Poll Type';
        return $types;
    });
}
add_action('init', 'register_custom_poll_type');
```

## Troubleshooting

### Common Issues

#### Polls Not Displaying
1. Check if plugin is activated
2. Verify shortcode syntax
3. Ensure polls exist and are active
4. Check user permissions

#### Voting Not Working
1. Verify AJAX is enabled
2. Check browser console for errors
3. Ensure nonce verification is working
4. Check user login status (if required)

#### Pop-ups Not Showing
1. Verify Elementor integration
2. Check pop-up settings
3. Ensure auto-show is enabled
4. Check dismissal status

#### Database Issues
1. Deactivate and reactivate plugin
2. Check database table creation
3. Verify WordPress database permissions
4. Check error logs

### Debug Mode
Enable WordPress debug mode in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Performance Optimization

1. **Caching**: Enable object caching for better performance
2. **Database**: Regular cleanup of old votes and shares
3. **Images**: Optimize contest images for web
4. **AJAX**: Minimize AJAX requests where possible

## Security

### Best Practices
- All user inputs are sanitized and validated
- Nonce verification for all AJAX requests
- Capability checks for admin functions
- SQL injection prevention with prepared statements
- XSS protection with proper output escaping

### User Permissions
- **Administrator**: Full access to all features
- **Editor**: Can manage all polls
- **Author**: Can create and manage own polls
- **Contributor**: Can create polls (pending approval)
- **Subscriber**: Can vote on polls

## Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Elementor**: 3.0 or higher (for pop-up integration)

## Support

For support and documentation:

1. Check this README file
2. Review plugin settings and configuration
3. Enable debug mode for error details
4. Check WordPress and plugin logs

## Changelog

### Version 1.0.0
- Initial release
- Core polling functionality
- Elementor integration
- Contest features
- Admin dashboard
- Social sharing
- Database optimization
- Security implementation

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed with WordPress best practices and modern web standards.

---

**PollMaster** - Comprehensive WordPress Polling Solution