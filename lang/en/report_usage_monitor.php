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
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General plugin strings
$string['pluginname'] = 'Usage Report';
$string['exclusivedisclaimer'] = 'This plugin is part of, and is to be exclusively used with the Moodle hosting service provided by <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';
$string['reportinfotext'] = 'This plugin has been created for another success story of <strong>IngeWeb</strong>. Visit us at <a target="_blank" href="http://ingeweb.co/">IngeWeb - Solutions to succeed on the Internet</a>.';

// Configuration strings
$string['email'] = 'Email for notifications';
$string['configemail'] = 'Email address where you want to send the attendance notifications.';
$string['max_daily_users_threshold'] = 'Limit Users';
$string['configmax_daily_users_threshold'] = 'Number of Limit Users.';
$string['disk_quota'] = 'Disk Quota';
$string['configdisk_quota'] = 'Disk Quota in gigabytes';
$string['pathtodu'] = 'Path to du command';
$string['configpathtodu'] = 'Configure the path to the du command (disk usage). This is necessary for calculating disk usage. <strong>This setting is reflected in Moodle system paths</strong>)';

// Task descriptions
$string['processunifiednotificationtask'] = 'Process unified system monitoring notifications';
$string['calculatediskusagetask'] = 'Task to calculate the disk usage';
$string['getlastusers'] = 'Task to calculate the top of unique accesses';
$string['getlastusers90days'] = 'Task to get the top users in the last 90 days';
$string['getlastusersconnected'] = 'Task to calculate the number of daily users today';

// Alert and threshold levels
$string['critical_threshold'] = 'CRITICAL';
$string['high_threshold'] = 'HIGH';
$string['medium_threshold'] = 'MEDIUM';
$string['normal_threshold'] = 'NORMAL';
$string['threshold_info'] = 'Alert thresholds: CRITICAL (95%), HIGH (90%), MEDIUM (80%)';

// Status messages
$string['disk_usage_status'] = 'Disk usage has reached {$a->level} level';
$string['user_count_status'] = 'User count has reached {$a->level} level';
$string['notcalculatedyet'] = 'Not calculated yet';

// Report sections
$string['topuser'] = 'Top 10 Daily Users';
$string['lastusers'] = 'Daily users of the last 10 days';
$string['max_userdaily_for_90_days'] = 'Maximum daily users in the last 90 days';
$string['userstopnum'] = 'Daily users';
$string['user_count_title'] = 'User Count';
$string['additional_info'] = 'Additional Information';

// Interface elements
$string['usertable'] = 'Top users table';
$string['userchart'] = 'Graph top users';
$string['date'] = 'Date';
$string['usersquantity'] = 'Number of daily users';
$string['dateformatsql'] = '%m/%d/%Y';
$string['dateformat'] = 'm/d/Y';

// Disk usage related
$string['diskusage'] = 'Disk usage';
$string['sizeusage'] = 'Total disk use';
$string['sizedatabase'] = 'Database size';
$string['avalilabledisk'] = '% of disk space available';
$string['disk_metrics_details'] = 'Detailed Disk Metrics';

// Last execution information
$string['lastexecution'] = 'Last daily users calculation run: {$a}';
$string['lastexecutioncalculate'] = 'Last disk space calculation: {$a}';
$string['last_execution_title'] = 'Last Report Execution';
$string['today_users_title'] = 'Current Daily Users';
$string['users_today'] = 'Number of daily users today: {$a}';

// System requirements and recommendations
$string['activateshellexec'] = 'The shell_exec function is not active on this server. To use the auto-detection of the path to du, you need to enable shell_exec in your server configuration.';
$string['pathtodurecommendation'] = 'We recommend that you review and configure the path to \'du\' in the Moodle System Paths. You can find this setting under Site administration > Server > System Paths. <a target="_blank" href="settings.php?section=systempaths#id_s__pathtodu">Click here to go to System Paths</a>.';
$string['pathtodunote'] = 'Note: The path to \'du\' will be automatically detected only if this plugin is on a Linux system and if the location of \'du\' can be successfully detected.';

// Unified notification strings
$string['unifiednotification_subject'] = 'System Monitoring Alert - {$a}';
$string['system_metrics'] = 'System Metrics Overview';
$string['disk_metrics'] = 'Disk Usage Metrics';
$string['user_metrics'] = 'User Activity Metrics';
$string['additional_metrics'] = 'Additional System Information';
$string['system_status'] = 'Current System Status';
$string['disk_usage_title'] = 'Current Disk Usage';
$string['disk_quota_title'] = 'Total Disk Quota';
$string['database_size_title'] = 'Database Size';
$string['available_space'] = 'Available Space';
$string['active_users'] = 'Active Users';
$string['user_limit'] = 'User Limit';
$string['monitoring_date'] = 'Monitoring Date';
$string['total_courses'] = 'Total Courses';
$string['backup_retention'] = 'Backup Retention';
$string['days'] = 'days';
$string['notification_footer'] = 'This is an automated monitoring notification. System metrics are collected and analyzed periodically to ensure optimal platform performance.';
$string['historical_data'] = 'Historical User Access Data';

// Email template
$string['unifiednotification_html'] = '
<p>Platform: <a href="{$a->siteurl}" target="_blank"><strong>{$a->sitename}</strong></a></p>
<p><strong>Alert Summary:</strong></p>
<ul>
    <li>Disk Usage: {$a->diskusage} of {$a->quotadisk} ({$a->disk_percent}%) - Level: {$a->disk_alert}</li>
    <li>Active Users: {$a->numberofusers} of {$a->threshold} ({$a->user_percent}%) - Level: {$a->user_alert}</li>
    <li>Monitoring Date: {$a->lastday}</li>
</ul>

<p><strong>Monitor URL:</strong> <a href="{$a->referer}" target="_blank">System Monitor Dashboard</a></p>

{$a->table}

<hr>
<p style="font-size: 0.9em; color: #666;">
This message has been automatically generated by "Usage Report" from <a href="https://ingeweb.co/" target="_blank"><strong>ingeweb.co</strong></a>
</p>';