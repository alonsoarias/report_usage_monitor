# Changelog

## Version 4.5.4 (2025030403) - March 2025

This update represents a major evolution of the Usage Monitor plugin, transforming it from a basic monitoring tool into a comprehensive analytics solution with a modern dashboard, predictive capabilities, and a new API for external integrations.

### 🌟 New Features

#### REST API & Integration
- Added a complete REST API with endpoints for monitoring, history, and configuration
- Implemented comprehensive API documentation with interactive examples
- Added new permission capabilities for API access control (`report/usage_monitor:apiuse`)
- Created configuration option to enable/disable API access

#### Historical Data Analysis
- Added new database table `report_usage_monitor_history` for tracking usage over time
- Implemented trend analysis and growth rate calculations
- Created visualizations for historical data with customizable date ranges
- Added data retention policies with automatic cleanup for old records

#### Advanced Dashboard
- Completely redesigned the main dashboard with a modern UI
- Added interactive charts for disk distribution, usage history, and user statistics
- Implemented visual indicators for warning thresholds and critical levels
- Added system information summary and quick access to key metrics
- Redesigned data tables with performance improvements

#### Smart Notifications
- Split notification handling into specialized tasks for disk usage and user limits
- Implemented dynamic notification frequencies based on severity
- Created professional HTML email templates with detailed analytics
- Added customizable warning thresholds for different notification types

#### Predictive Analytics
- Added growth rate calculations for disk usage and user activity
- Implemented threshold projection to estimate when limits will be reached
- Created recommendation system with context-specific suggestions
- Added course space impact analysis to identify optimization opportunities

### 🔧 Improvements

#### Performance Enhancements
- Refactored all database queries to use timestamp arithmetic instead of string formatting
- Added proper indexing for critical database fields
- Implemented precomputed values for dashboard statistics
- Optimized scheduled task frequency based on server capabilities
- Improved directory size calculation with better `du` command integration

#### Data Integrity
- Fixed fundamental date handling issues by changing storage from string to UNIX timestamps
- Implemented comprehensive data validation throughout the codebase
- Added database transactions to ensure consistency during updates
- Improved error handling with detailed logging
- Added migration scripts to fix historical data

#### Configuration 
- Reorganized settings page with logical grouping
- Added configurable warning thresholds for different notification types
- Improved auto-detection of system tools across different operating systems
- Added clear explanations and links to system configuration

### 📄 Changed Files

#### New Files
- `classes/external.php` - API implementation
- `classes/task/notification_disk.php` - Specialized disk notification task
- `classes/task/notification_userlimit.php` - Specialized user limit notification task
- `api-documentation.php` - Interactive API documentation
- `db/services.php` - Web services definition

#### Modified Files
- `version.php` - Version update and metadata changes
- `db/install.xml` - Database schema updates for timestamp handling and new tables
- `db/upgrade.php` - Migration scripts for existing installations
- `db/access.php` - New capabilities for API and management
- `db/tasks.php` - Optimized task scheduling and new tasks
- `classes/task/disk_usage.php` - Enhanced disk analysis with detailed reporting
- `classes/task/last_users.php` - Improved user tracking with validation
- `classes/task/users_daily.php` - Refactored for timestamp handling and data cleanup
- `classes/task/users_daily_90_days.php` - Enhanced long-term analysis
- `locallib.php` - Completely refactored core functions with new analysis capabilities
- `settings.php` - Reorganized settings with new configuration options
- `index.php` - Complete dashboard redesign with modern UI and visualizations
- `lang/en/report_usage_monitor.php` - Added 100+ new strings for UI and features
- `lang/es/report_usage_monitor.php` - Updated Spanish translations

#### Removed Files
- `classes/task/notification.php` - Replaced by specialized notification tasks

### 🐛 Bug Fixes
- Fixed critical issue with date storage format in database
- Resolved inconsistencies in disk usage calculations
- Fixed potential data loss during parallel task execution
- Improved error handling to prevent UI breakage with invalid data
- Enhanced validation to prevent notification errors with missing data

### ⚙️ Technical Details

#### Database Changes
- Changed `fecha` field from CHAR to INTEGER to store UNIX timestamps
- Added indexes on `fecha` and `cantidad_usuarios` columns
- Created new `report_usage_monitor_history` table with type, percentage, value, threshold, and timestamp
- Added indexes for efficient history queries

#### API Endpoints
- `report_usage_monitor_get_monitor_stats` - Comprehensive system statistics
- `report_usage_monitor_get_notification_history` - Historical notification data
- `report_usage_monitor_get_usage_data` - Optimized basic usage metrics
- `report_usage_monitor_set_usage_thresholds` - Update configuration thresholds

#### Permission Changes
- Changed context level from COURSE to SYSTEM for better access control
- Added `report/usage_monitor:manage` capability for configuration access
- Added `report/usage_monitor:apiuse` capability for API access

---

## Previous Versions

### Version 3.11 (20240401001) - April 2024
- Initial stable release