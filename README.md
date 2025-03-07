# Moodle Usage Monitor Plugin

![Status: Stable](https://img.shields.io/badge/Status-Stable-brightgreen)
![Version: 4.5.4](https://img.shields.io/badge/Version-4.5.4-blue)
![Moodle: 3.9+](https://img.shields.io/badge/Moodle-3.9+-orange)

A comprehensive monitoring solution for Moodle administrators to track disk usage, user activity, and receive intelligent notifications when approaching configured thresholds.

## 🌟 Features

### Modern Dashboard
- Real-time overview of disk usage and user statistics
- Interactive charts and data visualizations
- Top storage consumers identification
- Visual indicators for warning thresholds
- Historical trends and usage patterns
- Customizable views for different metrics

### Disk Usage Monitoring
- Total disk space utilization with threshold alerts
- Detailed breakdown by directory types (database, files, cache)
- Largest courses identification with size analysis
- Backup storage impact assessment
- Growth trend analysis and projections

### User Activity Tracking
- Daily active user monitoring
- Peak usage identification and historical records
- 90-day usage trends and patterns
- User limit threshold notifications
- Top 10 highest usage days

### Smart Notifications
- Customizable email alerts for disk and user thresholds
- Professional HTML email templates with detailed analytics
- **Adaptive notification frequency** based on threshold severity
- Actionable recommendations for administrators
- Historical notification log

### REST API for Integration
- Comprehensive endpoints for monitoring, history, and configuration
- Authentication and permission-based access control
- Standardized responses with detailed metrics
- Integration examples for external monitoring systems

### Predictive Analytics
- Growth rate calculations for disk and user metrics
- Threshold projection to estimate when limits will be reached
- Intelligent recommendations for resource optimization
- Impact analysis for administrative decisions

### Integrated Threshold System
- **Configurable warning thresholds** for disk usage and user limits
- **Adaptive notification intervals** based on threshold levels
- **Dynamic visualization colors** tied to configured thresholds
- **Comprehensive explanation** of threshold effects throughout the system

## 📊 Dashboard Preview

The Usage Monitor provides a modern dashboard with multiple visualizations:

- Status cards with threshold indicators
- Disk usage distribution charts
- Historical trend graphs
- Daily user statistics
- System information summary
- Actionable recommendations

## 🔌 API Documentation

The plugin includes a comprehensive REST API for integration with external monitoring systems:

### Main Endpoints

| Endpoint | Description | Method |
|----------|-------------|--------|
| `report_usage_monitor_get_usage_data` | Quick access to current usage statistics | GET |
| `report_usage_monitor_get_monitor_stats` | Comprehensive system statistics | GET |
| `report_usage_monitor_get_notification_history` | Historical notification data | GET |
| `report_usage_monitor_set_usage_thresholds` | Update configuration thresholds | POST |

Access the full API documentation at `{your-moodle-url}/report/usage_monitor/api-documentation.php`

## 📋 Requirements

- Moodle 3.9 or higher
- PHP 7.3 or higher
- Database: Any supported by your Moodle installation
- For optimal disk analysis: Linux system with `du` command available

## 🔧 Installation

### From Moodle Plugin Directory
1. Log in as an admin and go to Site administration > Plugins > Install plugins
2. Click the button 'Install plugins from Moodle plugins directory'
3. Search for "Usage Monitor" and install the plugin

### Manual Installation
1. Extract the folder and rename it to `usage_monitor`
2. Place the folder in your Moodle installation under `report/`
3. Visit the notifications page on your Moodle site to complete the installation

## ⚙️ Configuration

1. Navigate to Site administration > Reports > Usage Report
2. Configure the following settings:
   - **Disk quota**: Set your total disk space allocation in GB
   - **User threshold**: Maximum daily active users allowed
   - **Email notifications**: Email address for alerts
   - **Warning thresholds**: Customize when alerts are triggered and their frequency
   - **API access**: Enable/disable API functionality
3. For optimal disk usage monitoring on Linux systems:
   - Configure the path to `du` command in Site administration > Server > System paths

### Understanding Warning Thresholds

The configurable warning thresholds affect several aspects of the system:

- **Notification triggers**: Determines when email alerts are first sent
- **Notification frequency**: Higher severity = more frequent notifications
  - At threshold level: Notifications every 5 days (disk) or 3 days (users)
  - Moderately above threshold: Notifications every 1 day
  - Critically above threshold: Notifications every 12 hours (disk) or 1 day (users)
- **Dashboard colors**: Visual indicators adapt to configured thresholds
  - Green: Below caution level (threshold - 20%)
  - Yellow: Between caution and warning level
  - Red: Above warning level

## 🚀 Usage

### Viewing the Dashboard
1. Navigate to Site administration > Reports > Usage Report
2. The dashboard displays current usage status with visual indicators
3. Use the tabs to switch between different data views

### Receiving Notifications
- Automated email notifications are sent when thresholds are approached
- Notification frequency adapts based on severity
- Emails include detailed analytics and actionable recommendations

### Using the API
1. Create a user with the appropriate capabilities (`report/usage_monitor:apiuse`)
2. Generate a token for API access in Site administration > Plugins > Web services > Manage tokens
3. Use the token in your API requests to the provided endpoints

## 🛠️ For Developers

The plugin provides extensive hooks for custom integrations:

```php
// Example: Get current usage data from your custom code
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

// Get disk analysis
$dir_analysis = analyze_disk_usage_by_directory($CFG->dataroot);

// Calculate growth projections
$disk_growth_rate = calculate_growth_rate('disk');
$days_to_threshold = project_limit_date($current_disk_usage, $threshold_value, $disk_growth_rate);

// Get largest courses
$largest_courses = get_largest_courses(5);

// Get threshold-based warning class
$disk_warning_level = !empty($reportconfig->disk_warning_level) ? (float)$reportconfig->disk_warning_level : 90;
$disk_caution_level = max(70, $disk_warning_level - 20);
$warning_class = ($percent < $disk_caution_level) ? 'bg-success' : 
                 (($percent < $disk_warning_level) ? 'bg-warning' : 'bg-danger');
```

## 📝 FAQ

**Q: Does the plugin affect Moodle performance?**
A: The plugin uses scheduled tasks to perform calculations during off-peak hours and stores pre-computed values to ensure dashboard views load quickly.

**Q: How accurate is the disk usage calculation?**
A: On Linux systems with the `du` command properly configured, calculations are highly accurate. Without `du`, the plugin falls back to PHP's directory calculation methods, which may be less precise.

**Q: Can I customize notification thresholds?**
A: Yes, you can configure separate warning thresholds for disk usage and user limits in the plugin settings. These thresholds determine not only when notifications are sent but also their frequency and dashboard visualization colors.

**Q: How do the notification frequencies work?**
A: The system automatically adjusts notification frequency based on severity. More critical situations trigger more frequent alerts, allowing administrators to respond appropriately to the level of urgency.

**Q: Does this plugin work with cloud storage?**
A: The plugin calculates disk usage for files stored in the local filesystem. Cloud storage systems like S3 would require custom adaptations.

## 📄 License

This plugin is licensed under the GNU GPL v3 or later. See the LICENSE file for details.

## 👥 Credits

Developed and maintained by [IngeWeb](https://ingeweb.co/) - Solutions to succeed on the Internet.

For support or feature requests, please contact us at soporte@ingeweb.co.