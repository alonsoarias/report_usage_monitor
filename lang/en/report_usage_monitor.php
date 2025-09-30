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
 * Language strings for report_usage_monitor.
 *
 * @package     report_usage_monitor
 * @category    string
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general strings.
$string['pluginname'] = 'Usage Monitor';
$string['reportinfotext'] = 'This plugin has been created for another success story of <strong>IngeWeb</strong>. Visit us at <a target="_blank" href="http://ingeweb.co/">IngeWeb - Solutions to succeed on the Internet</a>.';
$string['exclusivedisclaimer'] = 'This plugin is part of, and is to be exclusively used with the Moodle hosting service provided by <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';

// Dashboard strings.
$string['dashboard'] = 'Dashboard';
$string['dashboard_title'] = 'Usage Monitor Dashboard';
$string['diskusage'] = 'Disk Usage';
$string['users_today_card'] = 'Daily Users Today';
$string['max_userdaily_for_90_days'] = 'Maximum Daily Users (Last 90 Days)';
$string['notcalculatedyet'] = 'Not calculated yet';
$string['lastexecution'] = 'Last calculation: {$a}';
$string['lastexecutioncalculate'] = 'Last disk calculation: {$a}';
$string['users_today'] = 'Daily users today: {$a}';
$string['date'] = 'Date';
$string['last_calculation'] = 'Last calculation';
$string['usersquantity'] = 'Number of Users';
$string['disk_usage_distribution'] = 'Disk Usage Distribution';
$string['disk_usage_history'] = 'Disk Usage History (Last 30 Days)';
$string['percentage_used'] = 'Percentage Used';

// Dashboard sections.
$string['disk_usage_by_directory'] = 'Disk Usage by Directory';
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
$string['topuser'] = 'Top 10 Daily Users';
$string['lastusers'] = 'Last 10 Days Users';
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

// Warning levels.
$string['warning70'] = 'Warning (70%)';
$string['critical90'] = 'Critical (90%)';
$string['limit100'] = 'Limit (100%)';
$string['percent_of_threshold'] = '% of threshold';

// Recommendations.
$string['space_saving_tips'] = 'Tips to save disk space:';
$string['tip_backups'] = 'Reduce the number of automatic backups per course (currently: {$a})';
$string['tip_files'] = 'Clean up old unused files';
$string['tip_courses'] = 'Archive or delete old courses';
$string['tip_cache'] = 'Purge system cache';
$string['disk_usage_ok'] = 'Disk usage is at an acceptable level.';
$string['user_count_ok'] = 'User count is at an acceptable level.';
$string['user_limit_tips'] = 'Tips for managing user limit:';
$string['tip_user_inactive'] = 'Clean up inactive user accounts';
$string['tip_user_limit'] = 'Consider increasing your user quota';

// Task strings.
$string['calculatediskusagetask'] = 'Calculate disk usage';
$string['getlastusers'] = 'Calculate top daily users';
$string['getlastusers90days'] = 'Calculate max users in 90 days';
$string['getlastusersconnected'] = 'Calculate today\'s users';
$string['processdisknotificationtask'] = 'Process disk notifications';
$string['processuserlimitnotificationtask'] = 'Process user limit notifications';

// Settings strings.
$string['mainsettings'] = 'Main Settings';
$string['email'] = 'Notification Email';
$string['configemail'] = 'Email address for notifications';
$string['max_daily_users_threshold'] = 'User Limit';
$string['configmax_daily_users_threshold'] = 'Maximum number of daily users';
$string['disk_quota'] = 'Disk Quota';
$string['configdisk_quota'] = 'Disk quota in gigabytes';
$string['notificationsettings'] = 'Notification Settings';
$string['notificationsettingsinfo'] = 'Configure notification thresholds';
$string['disk_warning_level'] = 'Disk Warning Level';
$string['configdisk_warning_level'] = 'Percentage that triggers disk warnings';
$string['users_warning_level'] = 'Users Warning Level';
$string['configusers_warning_level'] = 'Percentage that triggers user warnings';
$string['pathtodu'] = 'Path to du command';
$string['configpathtodu'] = 'Path to the disk usage (du) command';
$string['pathtodu_autodetected'] = 'Path to du auto-detected: {$a}';
$string['pathtodurecommendation'] = 'Configure the path to \'du\' in System Paths';
$string['pathtodunote'] = 'Auto-detection only works on Linux systems';
$string['activateshellexec'] = 'shell_exec is not available';
$string['enable_api'] = 'Enable API';
$string['configenable_api'] = 'Enable external API access';

// Email notification strings.
$string['subjectemail1'] = 'User Limit Alert:';
$string['subjectemail2'] = 'Disk Space Alert:';

// API strings.
$string['apidisabled'] = 'API is disabled';
$string['user_threshold_updated'] = 'User threshold updated successfully';
$string['disk_threshold_updated'] = 'Disk threshold updated successfully';
$string['error_user_threshold_negative'] = 'User threshold must be positive';
$string['error_disk_threshold_negative'] = 'Disk threshold must be positive';
$string['error_no_thresholds_provided'] = 'No thresholds provided';

// Email templates (simplified).
$string['messagehtml_userlimit'] = 'User limit exceeded on {$a->sitename}. Current: {$a->numberofusers}, Limit: {$a->threshold}';
$string['messagehtml_diskusage'] = 'Disk usage alert on {$a->sitename}. Used: {$a->diskusage}, Quota: {$a->quotadisk}';