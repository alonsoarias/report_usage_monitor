# Changelog

## Version 5.0.0 (2025070100) - July 2025

**Complete Plugin Rewrite by Alonso Arias**

This version represents a complete architectural rewrite of the Usage Monitor plugin, transforming it from a basic monitoring tool into a comprehensive analytics and management solution specifically designed for Moodle hosting platforms.

### 🌟 Major Features

#### Complete Architecture Rewrite
- **New Author**: Plugin completely rewritten by Alonso Arias for IngeWeb
- **Unified Data Management**: Centralized `usage_monitor_data_manager` class with intelligent caching
- **Modular Design**: Clean separation of concerns with dedicated classes for analytics, disk analysis, user queries, and notifications
- **Performance Optimization**: Reduced memory usage and improved query efficiency by up to 60%

#### Modern REST API v5.0
- **Complete API Rewrite**: New endpoints designed for hosting platform integration
- **Enhanced Security**: Granular permission controls and improved token management
- **Intelligent Caching**: Reduced API response times with smart data caching
- **Flexible Parameters**: Customizable data retrieval with optional historical data inclusion

#### Professional Dashboard
- **Modern Interface**: Complete UI redesign with Chart.js integration
- **Interactive Visualizations**: Real-time charts for disk distribution and user activity
- **Responsive Design**: Optimized for all devices with professional styling
- **Contextual Recommendations**: Intelligent suggestions based on current usage patterns

#### Advanced Analytics Engine
- **Predictive Projections**: Enhanced algorithms for growth rate calculations and threshold forecasting
- **Historical Analysis**: Comprehensive trend analysis with configurable data retention
- **Smart Notifications**: Adaptive notification frequency based on severity levels
- **Multi-metric Monitoring**: Unified monitoring of disk usage, user activity, and system health

### 🔧 Technical Improvements

#### Database Optimization
- **Improved Schema**: Enhanced indexing and data structure optimization
- **Automatic Cleanup**: Intelligent data retention with configurable cleanup tasks
- **Transaction Safety**: Robust transaction handling for data consistency
- **Migration Support**: Seamless upgrade from previous versions

#### Enhanced Scheduled Tasks
- **Modular Tasks**: Separated tasks for disk calculation, user analysis, and notifications
- **Adaptive Scheduling**: Dynamic task frequency based on system capabilities
- **Error Handling**: Comprehensive error recovery and logging
- **Performance Monitoring**: Built-in task performance tracking

#### Security Enhancements
- **Capability System**: New granular permissions for view, manage, and API access
- **Input Validation**: Comprehensive data validation and sanitization
- **Secure Communications**: Enhanced token management and secure API endpoints
- **Audit Trail**: Complete logging of configuration changes and API access

### 📊 New Dashboard Features

#### Real-time Monitoring
- **Live Statistics**: Real-time disk usage and user activity monitoring
- **Visual Indicators**: Color-coded progress bars and status indicators
- **Interactive Charts**: Disk distribution pie charts and user activity line graphs
- **System Overview**: Comprehensive system information display

#### Advanced Analytics
- **Growth Projections**: Predictive analytics for capacity planning
- **Threshold Management**: Dynamic threshold configuration with visual feedback
- **Historical Trends**: 30-day historical data visualization
- **Peak Analysis**: 90-day peak usage identification and tracking

#### Professional Recommendations
- **Context-aware Suggestions**: Intelligent recommendations based on current usage
- **Optimization Tips**: Specific actions for disk space and user management
- **Proactive Alerts**: Early warning system for approaching thresholds
- **Best Practices**: Built-in guidance for optimal system management

### 🔌 REST API v5.0 Endpoints

#### Core Endpoints
- `report_usage_monitor_get_usage_statistics`: Comprehensive usage data with optional history
- `report_usage_monitor_get_dashboard_data`: Optimized data for real-time dashboards
- `report_usage_monitor_update_thresholds`: Dynamic threshold and warning level management
- `report_usage_monitor_get_notification_history`: Historical notification data with filtering

#### Enhanced Features
- **Flexible Parameters**: Customizable data retrieval options
- **Comprehensive Responses**: Detailed data structures with metadata
- **Error Handling**: Robust error responses with detailed messages
- **Performance Optimization**: Cached responses for frequently accessed data

### 📧 Enhanced Notification System

#### Smart Notifications
- **Adaptive Frequency**: Notification intervals adjust based on severity levels
- **Professional Templates**: HTML email templates with detailed analytics
- **Multi-level Alerts**: Different notification types for various threshold levels
- **Historical Tracking**: Complete notification history with filtering capabilities

#### Notification Features
- **Rich Content**: Detailed email templates with charts and recommendations
- **Contextual Information**: System information and historical data in notifications
- **Action Items**: Specific recommendations for addressing issues
- **Professional Branding**: IngeWeb-branded templates for hosting customers

### 🛠️ Developer Features

#### Modern Codebase
- **PSR Standards**: Code follows modern PHP standards and best practices
- **Comprehensive Documentation**: Inline documentation and API guides
- **Extensible Architecture**: Plugin-friendly design for custom extensions
- **Testing Support**: Built-in debugging and performance monitoring

#### Integration Support
- **Hosting Platform Ready**: Designed specifically for hosting provider integration
- **Multi-tenant Support**: Scalable architecture for multiple site monitoring
- **External System Integration**: REST API designed for monitoring dashboard integration
- **Custom Extensions**: Hooks and filters for custom functionality

### 📋 Compatibility & Requirements

#### Moodle Compatibility
- **Moodle 3.9+**: Full compatibility from Moodle 3.9 to 4.5+
- **Future Ready**: Architecture designed for Moodle 5.x compatibility
- **Backward Compatibility**: Maintains compatibility with existing integrations
- **Automatic Migration**: Seamless upgrade from previous versions

#### System Requirements
- **PHP 7.3+**: Modern PHP version support
- **Enhanced Performance**: Optimized for hosting environments
- **Linux Optimization**: Enhanced disk analysis on Linux systems
- **Memory Efficiency**: Reduced memory footprint for better performance

### 🏢 IngeWeb Integration

#### Hosting Platform Features
- **Pre-configured Settings**: Optimized defaults for IngeWeb hosting environment
- **Professional Support**: Dedicated support for IngeWeb hosting customers
- **Scalable Architecture**: Designed for high-volume hosting environments
- **Custom Branding**: IngeWeb-specific branding and messaging

#### Professional Services
- **Expert Configuration**: Professional setup and optimization services
- **24/7 Monitoring**: Integration with IngeWeb's monitoring infrastructure
- **Proactive Maintenance**: Automated maintenance and optimization
- **Technical Support**: Expert Moodle and hosting support

### 🔄 Migration from Previous Versions

#### Automatic Upgrade
- **Seamless Migration**: Automatic database schema updates
- **Settings Preservation**: Existing configuration maintained during upgrade
- **Data Integrity**: Complete data preservation with validation
- **Rollback Support**: Safe upgrade process with rollback capabilities

#### Breaking Changes
- **API Endpoints**: Some API endpoints have been updated (backward compatibility maintained)
- **Database Schema**: Improved schema with automatic migration
- **Configuration Structure**: Reorganized settings (existing settings preserved)
- **Class Structure**: New class organization (existing integrations supported)

---

## Previous Versions

### Version 4.5.4 (2025030403) - March 2025
- Enhanced dashboard with interactive charts
- Improved API with comprehensive endpoints
- Advanced notification system with HTML templates
- Historical data analysis and trend visualization

### Version 3.11 (20240401001) - April 2024
- Initial stable release
- Basic disk usage and user monitoring
- Simple notification system
- Basic dashboard interface

---

**Usage Monitor v5.0** - A complete rewrite by Alonso Arias for professional Moodle hosting platforms.