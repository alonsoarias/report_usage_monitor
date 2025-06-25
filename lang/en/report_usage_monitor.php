<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     report_usage_monitor
 * @category    string
 * @copyright   2025 Alonso Arias <alonso@aloarias.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general strings
$string['pluginname'] = 'Usage Monitor';
$string['reportinfotext'] = 'Usage Monitor v5.0 - Developed by <strong>Alonso Arias</strong> for <strong>IngeWeb</strong>. A comprehensive monitoring solution for Moodle hosting platforms.';
$string['exclusivedisclaimer'] = 'This plugin is part of the Moodle hosting service provided by <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';
$string['privacy:metadata'] = 'The Usage Monitor plugin does not store any personal data.';

// Dashboard strings
$string['dashboard'] = 'Dashboard';
$string['dashboard_title'] = 'Usage Monitor Dashboard';
$string['diskusage'] = 'Disk Usage';
$string['users_today_card'] = 'Daily Active Users';
$string['max_userdaily_for_90_days'] = 'Peak Users (90 days)';
$string['notcalculatedyet'] = 'Not calculated yet';
$string['lastexecutioncalculate'] = 'Last disk calculation: {$a}';
$string['lastexecution'] = 'Last user calculation: {$a}';
$string['date'] = 'Date';
$string['last_calculation'] = 'Last calculation';
$string['usersquantity'] = 'Daily Users';
$string['disk_usage_distribution'] = 'Disk Usage Distribution';
$string['disk_usage_history'] = 'Disk Usage History (30 Days)';
$string['percentage_used'] = 'Percentage Used';

// Dashboard sections
$string['disk_usage_by_directory'] = 'Storage Breakdown';
$string['largest_courses'] = 'Largest Courses';
$string['database'] = 'Database';
$string['files_dir'] = 'Files';
$string['cache'] = 'Cache';
$string['others'] = 'Others';
$string['directory'] = 'Directory';
$string['size'] = 'Size';
$string['percentage'] = 'Percentage';
$string['course'] = 'Course';
$string['backup_count'] = 'Backups';
$string['topuser'] = 'Top Usage Days';
$string['lastusers'] = 'Recent Activity (10 days)';
$string['usertable'] = 'Table View';
$string['userchart'] = 'Chart View';
$string['system_info'] = 'System Information';
$string['moodle_version'] = 'Moodle Version';
$string['total_courses'] = 'Total Courses';
$string['backup_per_course'] = 'Backups per Course';
$string['registered_users'] = 'Registered Users';
$string['active_users'] = 'active';
$string['suspended_users'] = 'suspended';
$string['recommendations'] = 'Recommendations';
$string['projections'] = 'Projections';

// Warning levels and indicators
$string['warning70'] = 'Caution (70%)';
$string['critical90'] = 'Warning (90%)';
$string['limit100'] = 'Critical (100%)';
$string['percent_of_threshold'] = '% of limit';

// Recommendation tips
$string['space_saving_tips'] = 'Storage optimization tips:';
$string['tip_backups'] = 'Consider reducing automatic backups per course (current: {$a})';
$string['tip_files'] = 'Clean up old unused files using file management tools';
$string['tip_courses'] = 'Archive or remove unused courses';
$string['tip_cache'] = 'Clear system cache to free temporary space';
$string['disk_usage_ok'] = 'Disk usage is at optimal levels.';
$string['user_count_ok'] = 'User activity is within normal parameters.';
$string['user_limit_tips'] = 'User management recommendations:';
$string['tip_user_inactive'] = 'Review and clean up inactive user accounts';
$string['tip_user_limit'] = 'Consider increasing user limits if consistently approaching threshold';

// Task strings
$string['calculatediskusagetask'] = 'Calculate disk usage statistics';
$string['calculateuserstask'] = 'Calculate user activity statistics';
$string['getlastusers'] = 'Calculate daily user statistics';
$string['getlastusers90days'] = 'Calculate 90-day peak usage';
$string['processdisknotificationtask'] = 'Process disk usage notifications';
$string['processuserlimitnotificationtask'] = 'Process user limit notifications';
$string['cleanuphistorytask'] = 'Clean up historical data';

// Settings strings
$string['mainsettings'] = 'Main Configuration';
$string['email'] = 'Notification Email';
$string['configemail'] = 'Email address for system notifications and alerts.';
$string['max_daily_users_threshold'] = 'Daily User Limit';
$string['configmax_daily_users_threshold'] = 'Maximum number of daily active users allowed.';
$string['disk_quota'] = 'Disk Quota (GB)';
$string['configdisk_quota'] = 'Total disk space allocation in gigabytes.';
$string['notificationsettings'] = 'Notification Settings';
$string['notificationsettingsinfo'] = 'Configure alert thresholds and notification behavior.';
$string['disk_warning_level'] = 'Disk Warning Threshold';
$string['configdisk_warning_level'] = 'Percentage of disk usage that triggers warnings.';
$string['users_warning_level'] = 'User Warning Threshold';
$string['configusers_warning_level'] = 'Percentage of user limit that triggers warnings.';
$string['enable_api'] = 'Enable REST API';
$string['configenable_api'] = 'Allow external systems to access usage data via REST API.';
$string['data_retention_days'] = 'Data Retention (days)';
$string['configdata_retention_days'] = 'Number of days to keep historical data (default: 90).';

// System paths
$string['pathtodu'] = 'Path to du command';
$string['configpathtodu'] = 'System path to the disk usage (du) command for accurate calculations.';
$string['pathtodurecommendation'] = 'For optimal disk usage calculations, configure the path to the "du" command in System Paths.';
$string['pathtodunote'] = 'The du command path will be auto-detected on Linux systems when available.';
$string['activateshellexec'] = 'The shell_exec function is disabled. Enable it for enhanced disk usage calculations.';

// Email notification strings
$string['subjectemail1'] = 'User Limit Alert - ';
$string['subjectemail2'] = 'Disk Usage Alert - ';

// API strings
$string['api_documentation'] = 'API Documentation';
$string['user_threshold_updated'] = 'User threshold updated successfully.';
$string['disk_threshold_updated'] = 'Disk threshold updated successfully.';
$string['error_user_threshold_negative'] = 'User threshold must be greater than 0.';
$string['error_disk_threshold_negative'] = 'Disk threshold must be greater than 0.';
$string['error_no_thresholds_provided'] = 'No valid thresholds provided for update.';

// Capabilities
$string['usage_monitor:view'] = 'View usage monitor reports';
$string['usage_monitor:manage'] = 'Manage usage monitor settings';
$string['usage_monitor:apiuse'] = 'Access usage monitor API';

// Email templates
$string['messagehtml_userlimit'] = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Limit Alert - {$a->sitename}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #d32f2f; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .alert { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>User Limit Alert</h1>
            <p>Daily user threshold exceeded on {$a->sitename}</p>
        </div>
        <div class="content">
            <div class="alert">
                <strong>Alert Details:</strong><br>
                Date: {$a->lastday}<br>
                Active Users: {$a->numberofusers}<br>
                Threshold: {$a->threshold}<br>
                Percentage: {$a->percentaje}%
            </div>
            <p>The platform has exceeded the configured daily user limit. Please review user activity and consider adjusting limits if necessary.</p>
            <p><a href="{$a->referer}">View Dashboard</a></p>
        </div>
        <div class="footer">
            <p>Usage Monitor v5.0 by Alonso Arias for IngeWeb</p>
        </div>
    </div>
</body>
</html>';

$string['messagehtml_diskusage'] = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Disk Usage Alert - {$a->sitename}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ff9800; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .alert { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Disk Usage Alert</h1>
            <p>Storage threshold exceeded on {$a->sitename}</p>
        </div>
        <div class="content">
            <div class="alert">
                <strong>Alert Details:</strong><br>
                Date: {$a->lastday}<br>
                Used Space: {$a->diskusage}<br>
                Total Quota: {$a->quotadisk}<br>
                Percentage: {$a->percentage}%
            </div>
            <p>The platform has exceeded the configured disk usage threshold. Please review storage usage and consider cleanup actions.</p>
            <p><a href="{$a->referer}">View Dashboard</a></p>
        </div>
        <div class="footer">
            <p>Usage Monitor v5.0 by Alonso Arias for IngeWeb</p>
        </div>
    </div>
</body>
</html>';