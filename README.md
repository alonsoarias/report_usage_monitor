# Moodle Usage Monitor Plugin v5.0

![Status: Stable](https://img.shields.io/badge/Status-Stable-brightgreen)
![Version: 5.0.0](https://img.shields.io/badge/Version-5.0.0-blue)
![Moodle: 3.9+](https://img.shields.io/badge/Moodle-3.9+-orange)
![Author: Alonso Arias](https://img.shields.io/badge/Author-Alonso%20Arias-purple)

A comprehensive monitoring solution for Moodle hosting platforms, specifically designed for IngeWeb's hosting infrastructure. This plugin provides real-time monitoring, intelligent notifications, and predictive analytics for disk usage and user activity.

## 🌟 Version 5.0 Features

### Complete Architecture Rewrite
- **Unified Data Management**: Centralized data handling with intelligent caching
- **Modern REST API**: Fully integrated with Moodle's web services framework
- **Enhanced Performance**: Optimized queries and reduced memory footprint
- **Modular Design**: Clean separation of concerns with dedicated classes

### Advanced Monitoring Capabilities
- **Real-time Dashboard**: Interactive charts and visualizations
- **Predictive Analytics**: Growth projections and threshold forecasting
- **Smart Notifications**: Adaptive notification frequency based on severity
- **Historical Analysis**: Comprehensive trend analysis and data retention

### Professional Integration
- **REST API v5.0**: Complete API for external monitoring systems
- **Hosting Platform Ready**: Designed specifically for hosting providers
- **Multi-tenant Support**: Scalable architecture for multiple sites
- **IngeWeb Integration**: Seamless integration with IngeWeb's infrastructure

## 📊 Dashboard Features

### Modern Interface
- Real-time usage statistics with visual indicators
- Interactive charts powered by Chart.js
- Responsive design for all devices
- Professional color schemes and animations

### Comprehensive Monitoring
- Disk usage breakdown by directory type
- Daily user activity tracking
- 90-day peak usage analysis
- System information overview
- Largest courses identification

### Intelligent Recommendations
- Context-aware optimization suggestions
- Automated threshold management
- Proactive maintenance alerts
- Growth projection warnings

## 🔌 REST API v5.0

### Core Endpoints

| Endpoint | Description | Method |
|----------|-------------|--------|
| `get_usage_statistics` | Comprehensive usage data with optional history | GET |
| `get_dashboard_data` | Optimized data for real-time dashboards | GET |
| `update_thresholds` | Dynamic threshold and warning level management | POST |
| `get_notification_history` | Historical notification data with filtering | GET |

### Enhanced Features
- **Intelligent Caching**: Reduced response times by up to 60%
- **Flexible Parameters**: Customizable data retrieval options
- **Comprehensive Validation**: Robust error handling and data validation
- **Security Enhanced**: Advanced permission controls and token management

## 📋 Requirements

- **Moodle**: 3.9 or higher (tested up to 4.5+, ready for 5.x)
- **PHP**: 7.3 or higher
- **Database**: Any supported by Moodle
- **System**: Linux recommended for optimal disk analysis
- **Memory**: Minimum 256MB PHP memory limit

## 🔧 Installation

### Automatic Installation
1. Download the plugin from the official repository
2. Upload via Site Administration > Plugins > Install plugins
3. Follow the installation wizard
4. Configure settings as needed

### Manual Installation
1. Extract the plugin to `/path/to/moodle/report/usage_monitor/`
2. Visit Site Administration > Notifications
3. Complete the installation process
4. Configure plugin settings

## ⚙️ Configuration

### Basic Setup
1. Navigate to Site Administration > Reports > Usage Monitor
2. Configure essential settings:
   - **Disk Quota**: Total allocated space in GB
   - **User Threshold**: Maximum daily active users
   - **Notification Email**: Alert destination
   - **Warning Levels**: Customizable threshold percentages

### Advanced Configuration
- **API Access**: Enable/disable REST API functionality
- **Data Retention**: Configure historical data retention period
- **System Paths**: Optimize disk calculation commands
- **Notification Frequency**: Adaptive alert intervals

### IngeWeb Integration
For IngeWeb hosting customers, the plugin includes pre-configured settings optimized for the hosting environment. Contact IngeWeb support for platform-specific configuration assistance.

## 🚀 Usage

### Dashboard Access
1. Navigate to Site Administration > Reports > Usage Monitor
2. View real-time statistics and trends
3. Access recommendations and projections
4. Monitor system health indicators

### API Integration
```php
// Example: Get current usage statistics
$response = $curl->get($moodle_url . '/webservice/rest/server.php', [
    'wstoken' => $token,
    'wsfunction' => 'report_usage_monitor_get_usage_statistics',
    'moodlewsrestformat' => 'json'
]);

$data = json_decode($response);
echo "Disk usage: " . $data->disk_usage->percentage . "%";
```

### Notification Management
- Automatic email alerts when thresholds are exceeded
- Adaptive notification frequency based on severity levels
- Professional HTML email templates with detailed analytics
- Historical notification tracking and analysis

## 🛠️ For Developers

### Architecture Overview
```
report_usage_monitor/
├── classes/
│   ├── external.php              # REST API implementation
│   ├── task/                     # Scheduled tasks
│   │   ├── disk_usage.php        # Disk calculation
│   │   ├── users_daily.php       # User statistics
│   │   ├── notification_*.php    # Alert processing
│   │   └── cleanup_history.php   # Data maintenance
│   └── ...
├── templates/
│   └── dashboard.php             # Dashboard template
├── locallib.php                  # Core functionality
└── ...
```

### Key Classes
- **`usage_monitor_data_manager`**: Central data management with caching
- **`usage_monitor_analytics`**: Growth calculations and projections
- **`usage_monitor_disk_analyzer`**: Disk usage analysis utilities
- **`usage_monitor_user_queries`**: User activity data retrieval
- **`usage_monitor_notifications`**: Email notification system

### Extension Points
```php
// Custom data processing
$stats = usage_monitor_data_manager::get_usage_statistics();

// Custom analytics
$growth_rate = usage_monitor_analytics::calculate_growth_rate('disk', 30);

// Custom notifications
usage_monitor_notifications::send_custom_alert($data, $template);
```

## 📝 Version 5.0 Changelog

### Major Changes
- **Complete rewrite** of core architecture
- **New REST API** with enhanced functionality
- **Improved performance** with intelligent caching
- **Modern dashboard** with interactive visualizations
- **Enhanced security** with granular permissions

### Breaking Changes
- API endpoints have been updated (backward compatibility maintained)
- Database schema improvements (automatic migration)
- Configuration structure reorganized (settings preserved)

### Migration Guide
Existing installations will be automatically migrated to v5.0. No manual intervention required for standard configurations.

## 🏢 About IngeWeb

This plugin is developed by **Alonso Arias** specifically for **IngeWeb's** Moodle hosting platform. IngeWeb provides professional Moodle hosting services with:

- High-performance infrastructure
- 24/7 technical support
- Automated monitoring and maintenance
- Scalable hosting solutions
- Expert Moodle consultation

For hosting services and support, visit [IngeWeb.co](https://ingeweb.co/)

## 📄 License

This plugin is licensed under the GNU GPL v3 or later. See the LICENSE file for details.

## 👥 Support & Development

- **Developer**: Alonso Arias <alonso@aloarias.com>
- **Company**: IngeWeb - Solutions to succeed on the Internet
- **Support**: For IngeWeb hosting customers, contact support@ingeweb.co
- **Documentation**: Full API documentation available in-plugin

---

*Usage Monitor v5.0 - Professional monitoring for professional hosting.*