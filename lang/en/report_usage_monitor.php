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
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Usage Report';
$string['topuser'] = 'Top 10 Daily Users';
$string['lastusers'] = 'Daily users of the last 10 days';
$string['email'] = 'Email for notifications';
$string['configemail'] = 'Email address where you want to send the attendance notifications.';
$string['max_daily_users_threshold'] = 'Limit Users';
$string['configmax_daily_users_threshold'] = 'Number of Limit Users.';
$string['processnotificationstask'] = 'Notify if the number of daily connected users was exceeded';
$string['diskusage'] = 'Disk usage';
$string['notcalculatedyet'] = 'Not calculated yet';
$string['calculatediskusagetask'] = 'Task to calculate the disk usage';
$string['getlastusers'] = 'Task to calculate the top of unique accesses';
$string['getlastusers90days'] = 'Task to get the top users in the last 90 days';
$string['getlastusersconnected'] = 'Task to calculate the number of daily users today';
$string['date'] = 'Date';
$string['usersquantity'] = 'Number of daily users';
$string['lastexecution'] = 'Last daily users calculation run: {$a}';
$string['lastexecutioncalculate'] = 'Last disk space calculation: {$a}';
$string['max_userdaily_for_90_days'] = 'Maximum daily users in the last 90 days';
$string['users_today'] = 'Number of daily users today: {$a}';
$string['sizeusage'] = 'Total disk use';
$string['sizedatabase'] = 'Database size';
$string['subjectemail1'] = 'Daily User Limit Exceeded on Platform:';
$string['subjectemail2'] = 'Disk Space Alert on Platform:';
$string['userstopnum'] = 'Daily users';
$string['usertable'] = 'Top users table';
$string['userchart'] = 'Graph top users';
$string['processdisknotificationtask'] = 'Process disk usage notification task';
$string['processuserlimitnotificationtask'] = 'Process daily user limit notification task';
$string['dateformatsql'] = '%m/%d/%Y';
$string['dateformat'] = 'm/d/Y';
$string['disk_quota'] = 'Disk Quota';
$string['configdisk_quota'] = 'Disk Quota in gigabytes';
$string['avalilabledisk'] = '% of disk space available';
$string['activateshellexec'] = 'The shell_exec function is not active on this server. To use the auto-detection of the path to du, you need to enable shell_exec in your server configuration.';
$string['pathtodu'] = 'Path to du command';
$string['configpathtodu'] = 'Configure the path to the du command (disk usage). This is necessary for calculating disk usage. <strong>This setting is reflected in Moodle system paths</strong>)';
$string['pathtodurecommendation'] = 'We recommend that you review and configure the path to \'du\' in the Moodle System Paths. You can find this setting under Site administration > Server > System Paths. <a target="_blank" href="settings.php?section=systempaths#id_s__pathtodu">Click here to go to System Paths</a>.';
$string['pathtodunote'] = 'Note: The path to \'du\' will be automatically detected only if this plugin is on a Linux system and if the location of \'du\' can be successfully detected.';

// New strings for enhanced notifications
$string['messagehtml_userlimit'] = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Limit Alert - {$a->sitename}</title>
    <style>
        body {
            font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #e74c3c;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .alert-badge {
            display: inline-block;
            background-color: white;
            color: #e74c3c;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }
        .content {
            padding: 20px 30px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .info-table td:first-child {
            font-weight: 500;
            width: 40%;
            color: #555;
        }
        .info-table td:last-child {
            font-weight: 600;
        }
        .progress-container {
            background-color: #f5f5f5;
            border-radius: 20px;
            height: 25px;
            width: 100%;
            margin: 15px 0;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(to right, #3498db, #e74c3c);
            text-align: center;
            line-height: 25px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            transition: width 0.5s ease;
        }
        .warning-level-high {
            background: linear-gradient(to right, #e74c3c, #c0392b);
        }
        .warning-level-medium {
            background: linear-gradient(to right, #f39c12, #e67e22);
        }
        .warning-level-low {
            background: linear-gradient(to right, #2ecc71, #27ae60);
        }
        .cta-button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #2980b9;
        }
        .historical-data {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .historical-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .historical-table th {
            background-color: #e8e8e8;
            font-weight: 600;
            text-align: left;
            padding: 10px;
        }
        .historical-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        .historical-table tr:last-child td {
            border-bottom: none;
        }
        .footer {
            background-color: #ecf0f1;
            padding: 15px;
            text-align: center;
            color: #7f8c8d;
            font-size: 12px;
        }
        .platform-info {
            border-left: 4px solid #3498db;
            padding: 10px 15px;
            background-color: #f8f9fa;
            margin: 15px 0;
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 100%;
                border-radius: 0;
            }
            .content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Daily User Limit Exceeded</h1>
            <div class="alert-badge">{$a->percentaje}% of limit</div>
        </div>
        
        <div class="content">
            <p>The platform <a href="{$a->siteurl}" style="color: #3498db; font-weight: bold;">{$a->sitename}</a> has exceeded the daily user threshold.</p>
            
            <div class="section">
                <h2 class="section-title">Alert Summary</h2>
                
                <div class="progress-container">
                    <div class="progress-bar warning-level-high" style="width: {$a->percentaje}%;">
                        {$a->percentaje}%
                    </div>
                </div>
                
                <table class="info-table">
                    <tr>
                        <td>Date:</td>
                        <td>{$a->lastday}</td>
                    </tr>
                    <tr>
                        <td>Active users:</td>
                        <td>{$a->numberofusers}</td>
                    </tr>
                    <tr>
                        <td>User limit:</td>
                        <td>{$a->threshold} users</td>
                    </tr>
                    <tr>
                        <td>Excess:</td>
                        <td>{$a->excess_users} users ({$a->percentaje}%)</td>
                    </tr>
                </table>
            </div>
            
            <div class="section">
                <h2 class="section-title">Platform Information</h2>
                <table class="info-table">
                    <tr>
                        <td>Moodle version:</td>
                        <td>{$a->moodle_release} ({$a->moodle_version})</td>
                    </tr>
                    <tr>
                        <td>Total courses:</td>
                        <td>{$a->courses_count}</td>
                    </tr>
                    <tr>
                        <td>Backups per course:</td>
                        <td>{$a->backup_auto_max_kept}</td>
                    </tr>
                    <tr>
                        <td>Disk space:</td>
                        <td>{$a->diskusage} / {$a->quotadisk} ({$a->disk_percent}%)</td>
                    </tr>
                </table>
                
                <div class="platform-info">
                    <p><strong>Projection:</strong> If the current trend continues, it is estimated that in {$a->days_to_critical} days it will reach {$a->critical_threshold}% of the limit.</p>
                </div>
            </div>
            
            <div class="section historical-data">
                <h2 class="section-title">Recent User History</h2>
                <table class="historical-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Active Users</th>
                            <th>% of Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic historical data -->
                        {$a->historical_data_rows}
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: center;">
                <a href="{$a->referer}" class="cta-button">View Dashboard</a>
            </div>
        </div>
        
        <div class="footer">
            <p>This message has been automatically generated by "Usage Report" from <a href="https://ingeweb.co/" style="color: #3498db;">ingeweb.co</a></p>
            <p><em>*Only distinct users who authenticated on the indicated date are counted. Users who authenticate more than once are only counted once.</em></p>
        </div>
    </div>
</body>
</html>';

$string['messagehtml_diskusage'] = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disk Space Alert - {$a->sitename}</title>
    <style>
        body {
            font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #e67e22;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .alert-badge {
            display: inline-block;
            background-color: white;
            color: #e67e22;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }
        .content {
            padding: 20px 30px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .info-table td:first-child {
            font-weight: 500;
            width: 40%;
            color: #555;
        }
        .info-table td:last-child {
            font-weight: 600;
        }
        .progress-container {
            background-color: #f5f5f5;
            border-radius: 20px;
            height: 25px;
            width: 100%;
            margin: 15px 0;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(to right, #3498db, #e67e22);
            text-align: center;
            line-height: 25px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            transition: width 0.5s ease;
        }
        .warning-level-high {
            background: linear-gradient(to right, #e74c3c, #c0392b);
        }
        .warning-level-medium {
            background: linear-gradient(to right, #f39c12, #e67e22);
        }
        .warning-level-low {
            background: linear-gradient(to right, #2ecc71, #27ae60);
        }
        .cta-button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #2980b9;
        }
        .directory-chart {
            margin: 20px 0;
            width: 100%;
        }
        .directory-chart-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .directory-bar {
            height: 35px;
            display: flex;
            margin-bottom: 8px;
            border-radius: 5px;
            overflow: hidden;
        }
        .directory-label {
            width: 150px;
            background-color: #34495e;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 10px;
            font-size: 14px;
        }
        .directory-value {
            flex-grow: 1;
            background-color: #3498db;
            display: flex;
            align-items: center;
            padding: 0 10px;
            color: white;
            font-weight: 600;
            position: relative;
        }
        .directory-value-text {
            position: relative;
            z-index: 2;
        }
        .directory-value-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.2);
        }
        .top-items {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .top-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .top-items-table th {
            background-color: #e8e8e8;
            font-weight: 600;
            text-align: left;
            padding: 10px;
        }
        .top-items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        .top-items-table tr:last-child td {
            border-bottom: none;
        }
        .footer {
            background-color: #ecf0f1;
            padding: 15px;
            text-align: center;
            color: #7f8c8d;
            font-size: 12px;
        }
        .recommendation {
            background-color: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 10px 15px;
            margin: 15px 0;
        }
        .recommendation h3 {
            margin-top: 0;
            color: #2980b9;
            font-size: 16px;
            font-weight: 600;
        }
        .recommendation ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .recommendation li {
            margin-bottom: 5px;
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 100%;
                border-radius: 0;
            }
            .content {
                padding: 15px;
            }
            .directory-label {
                width: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Disk Space Alert</h1>
            <div class="alert-badge">{$a->percentage}% used</div>
        </div>
        
        <div class="content">
            <p>The platform <a href="{$a->siteurl}" style="color: #3498db; font-weight: bold;">{$a->sitename}</a> has exceeded {$a->percentage}% of the assigned disk space.</p>
            
            <div class="section">
                <h2 class="section-title">Disk Usage Summary</h2>
                
                <div class="progress-container">
                    <div class="progress-bar {$a->warning_level_class}" style="width: {$a->percentage}%;">
                        {$a->percentage}%
                    </div>
                </div>
                
                <table class="info-table">
                    <tr>
                        <td>Date:</td>
                        <td>{$a->lastday}</td>
                    </tr>
                    <tr>
                        <td>Used space:</td>
                        <td>{$a->diskusage}</td>
                    </tr>
                    <tr>
                        <td>Assigned quota:</td>
                        <td>{$a->quotadisk}</td>
                    </tr>
                    <tr>
                        <td>Available space:</td>
                        <td>{$a->available_space} ({$a->available_percent}%)</td>
                    </tr>
                </table>
            </div>
            
            <div class="section">
                <h2 class="section-title">Space Distribution</h2>
                
                <div class="directory-chart">
                    <div class="directory-chart-title">Usage by directory</div>
                    <!-- Visual bar for each directory -->
                    <div class="directory-bar">
                        <div class="directory-label">Database</div>
                        <div class="directory-value" style="background-color: #9b59b6;">
                            <div class="directory-value-bar" style="width: {$a->db_percent}%;"></div>
                            <span class="directory-value-text">{$a->databasesize} ({$a->db_percent}%)</span>
                        </div>
                    </div>
                    <div class="directory-bar">
                        <div class="directory-label">Files (filedir)</div>
                        <div class="directory-value" style="background-color: #2ecc71;">
                            <div class="directory-value-bar" style="width: {$a->filedir_percent}%;"></div>
                            <span class="directory-value-text">{$a->filedir_size} ({$a->filedir_percent}%)</span>
                        </div>
                    </div>
                    <div class="directory-bar">
                        <div class="directory-label">Cache</div>
                        <div class="directory-value" style="background-color: #e67e22;">
                            <div class="directory-value-bar" style="width: {$a->cache_percent}%;"></div>
                            <span class="directory-value-text">{$a->cache_size} ({$a->cache_percent}%)</span>
                        </div>
                    </div>
                    <div class="directory-bar">
                        <div class="directory-label">Others</div>
                        <div class="directory-value" style="background-color: #95a5a6;">
                            <div class="directory-value-bar" style="width: {$a->other_percent}%;"></div>
                            <span class="directory-value-text">{$a->other_size} ({$a->other_percent}%)</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2 class="section-title">Platform Information</h2>
                <table class="info-table">
                    <tr>
                        <td>Moodle version:</td>
                        <td>{$a->moodle_release} ({$a->moodle_version})</td>
                    </tr>
                    <tr>
                        <td>Total courses:</td>
                        <td>{$a->coursescount}</td>
                    </tr>
                    <tr>
                        <td>Backups per course:</td>
                        <td>{$a->backupcount}</td>
                    </tr>
                    <tr>
                        <td>Active users:</td>
                        <td>{$a->numberofusers} / {$a->threshold} ({$a->user_percent}%)</td>
                    </tr>
                </table>
            </div>
            
            <div class="section top-items">
                <h2 class="section-title">Largest Courses</h2>
                <table class="top-items-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Size</th>
                            <th>% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic course data -->
                        {$a->top_courses_rows}
                    </tbody>
                </table>
            </div>
            
            <div class="recommendation">
                <h3>Recommendations to Free Up Space</h3>
                <ul>
                    <li>Reduce the number of automatic backups per course (currently: {$a->backupcount})</li>
                    <li>Remove old unused files using the file cleanup tool</li>
                    <li>Review and clean up the largest courses identified in the table above</li>
                    <li>Purge the system cache to free up temporary space</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="{$a->referer}" class="cta-button">View Dashboard</a>
            </div>
        </div>
        
        <div class="footer">
            <p>This message has been automatically generated by "Usage Report" from <a href="https://ingeweb.co/" style="color: #3498db;">ingeweb.co</a></p>
            <p>If you need technical assistance, please do not reply to this email and contact your hosting administrator.</p>
        </div>
    </div>
</body>
</html>';

// New strings for the dashboard
$string['dashboard'] = 'Dashboard';
$string['dashboard_title'] = 'Usage Monitor Dashboard';
$string['users_today_card'] = 'Daily Users Today';
$string['max_90days'] = 'Maximum in 90 Days';
$string['of'] = 'of';
$string['of_limit'] = '% of limit ({$a})';
$string['disk_summary'] = 'Disk Usage Overview';
$string['users_summary'] = 'Users Activity Overview';
$string['disk_usage_distribution'] = 'Disk Usage Distribution';
$string['directory'] = 'Directory';
$string['size'] = 'Size';
$string['percentage'] = 'Percentage';
$string['database'] = 'Database';
$string['files_dir'] = 'Files (filedir)';
$string['backups'] = 'Backups';
$string['cache'] = 'Cache';
$string['others'] = 'Others';
$string['daily_users'] = 'Daily Users';
$string['threshold'] = 'Threshold';
$string['system_info'] = 'System Information';
$string['moodle_version'] = 'Moodle Version';
$string['total_courses'] = 'Total Courses';
$string['backup_per_course'] = 'Backups per Course';
$string['registered_users'] = 'Registered Users';
$string['disk_usage_overview'] = 'Disk Usage Overview';
$string['disk_usage_by_directory'] = 'Disk Usage by Directory';
$string['largest_courses'] = 'Largest Courses';
$string['course'] = 'Course';
$string['backup_count'] = 'Backup Count';
$string['recommendations'] = 'Recommendations';
$string['space_saving_tips'] = 'Tips to save disk space:';
$string['tip_backups'] = 'Reduce the number of automatic backups per course (currently: {$a})';
$string['tip_files'] = 'Clean up old unused files using the file cleanup tool';
$string['tip_courses'] = 'Archive or delete old courses that are no longer used';
$string['tip_cache'] = 'Purge the system cache to free up temporary space';
$string['size_in_gb'] = 'Size (GB)';
$string['totaldiskusage'] = 'Total disk usage';
$string['diskquota'] = 'Disk quota';

// Strings for settings.php
$string['mainsettings'] = 'Main settings';
$string['notificationsettings'] = 'Notification settings';
$string['notificationsettingsinfo'] = 'Configure when and how notifications are sent.';
$string['disk_warning_level'] = 'Disk warning level';
$string['configdisk_warning_level'] = 'Percentage of disk usage that triggers warnings.';
$string['users_warning_level'] = 'Users warning level';
$string['configusers_warning_level'] = 'Percentage of user limit that triggers warnings.';
$string['integrationsettings'] = 'Integration settings';
$string['integrationsettingsinfo'] = 'Configure integration with external systems through API and webhooks.';
$string['enable_api'] = 'Enable API';
$string['configenable_api'] = 'Enable API access for external systems to retrieve usage information.';
$string['enable_webhooks'] = 'Enable webhooks';
$string['configenable_webhooks'] = 'Enable webhooks for sending notifications to external systems.';

// Strings for the API
$string['api_documentation'] = 'API Documentation';
$string['api_key'] = 'API Key';
$string['api_url'] = 'API URL';
$string['webhook_url'] = 'Webhook URL';
$string['webhook_secret'] = 'Webhook Secret';
$string['webhook_events'] = 'Webhook Events';
$string['webhook_test'] = 'Test Webhook';
$string['webhook_test_success'] = 'Webhook test successful.';
$string['webhook_test_failure'] = 'Webhook test failed: {$a}';
$string['webhook_added'] = 'Webhook added successfully.';
$string['webhook_updated'] = 'Webhook updated successfully.';
$string['webhook_deleted'] = 'Webhook deleted successfully.';
$string['webhook_event_disk_warning'] = 'Disk space warning';
$string['webhook_event_user_warning'] = 'User limit warning';

// Strings for error and success messages
$string['error_invalid_url'] = 'Invalid URL format.';
$string['error_connection_failed'] = 'Connection failed: {$a}';
$string['error_response_invalid'] = 'Invalid response received.';
$string['error_permission_denied'] = 'Permission denied.';
$string['error_data_not_found'] = 'Data not found.';
$string['success_data_saved'] = 'Data saved successfully.';
$string['success_operation_completed'] = 'Operation completed successfully.';

// Strings for data visualization
$string['disk_usage_over_time'] = 'Disk usage over time';
$string['user_count_over_time'] = 'User count over time';
$string['notification_history'] = 'Notification history';
$string['last_7_days'] = 'Last 7 days';
$string['last_30_days'] = 'Last 30 days';
$string['last_90_days'] = 'Last 90 days';
$string['custom_range'] = 'Custom range';
$string['date_from'] = 'From date';
$string['date_to'] = 'To date';
$string['apply_filter'] = 'Apply filter';
$string['reset_filter'] = 'Reset filter';
$string['export_data'] = 'Export data';
$string['print_report'] = 'Print report';
$string['refresh_data'] = 'Refresh data';
$string['auto_refresh'] = 'Auto refresh';

$string['messagehtml1'] = '<p>The platform <a href="{$a->siteurl}" target="_blank" ><strong>\'{$a->sitename}\'</strong></a> has exceeded the user threshold by {$a->percentaje}%</p>
<p>Date (DD/MM/YYYY): {$a->lastday} </p>
<p>Users*: <strong>{$a->numberofusers}</strong></p>
<p>Maximum daily users threshold: {$a->threshold} users</p>
<p>Disk space threshold: <strong>{$a->quotadisk}</strong></p>
<p>Disk space used*: <strong>{$a->diskusage}</strong></p>
<strong>Monitor URL: </strong> {$a->referer}
<br>
<br>
{$a->table}
<br>
<hr>
<i><p>This message has been automatically generated by "Usage Report" from <a href="https://ingeweb.co/" target="_blank" ><strong>ingeweb.co</strong></a></p>
*Only distinct users who authenticated on the indicated date are counted. Users who authenticate more than once are only counted once.<i>';

$string['messagehtml2'] = '
<p>The platform <a href="{$a->siteurl}" target="_blank"><strong>\'{$a->sitename}\'</strong></a> has exceeded 90% of the assigned disk space threshold</p>
<p>Date (DD/MM/YYYY): {$a->lastday}</p>
<p>Disk space used*: <strong>{$a->diskusage}</strong></p>
<p>Assigned disk space threshold: <strong>{$a->quotadisk}</strong></p>
<p>Maximum daily users threshold: <strong>{$a->threshold} users</strong></p>
<p>Users*: <strong>{$a->numberofusers}</strong></p>
<strong>Monitor URL: </strong> {$a->referer}
<br>
<br>
<table border="1" style="border-collapse: collapse; width: 50%;">
    <tr>
        <th style="padding: 8px; background-color: #f2f2f2;">Description</th>
        <th style="padding: 8px; background-color: #f2f2f2;">Detail</th>
    </tr>
    <tr>
        <td style="padding: 8px;">Disk usage percentage</td>
        <td style="padding: 8px;"><strong>{$a->percentage}%</strong></td>
    </tr>
    <tr>
        <td style="padding: 8px;">Database size</td>
        <td style="padding: 8px;"><strong>{$a->databasesize}</strong></td>
    </tr>
    <tr>
        <td style="padding: 8px;">Number of courses</td>
        <td style="padding: 8px;"><strong>{$a->coursescount}</strong></td>
    </tr>
    <tr>
        <td style="padding: 8px;">Number of backups per course</td>
        <td style="padding: 8px;"><strong>{$a->backupcount}</strong></td>
    </tr>
</table>
<hr>
<p>This message has been automatically generated by "Usage Report" from <a href="https://ingeweb.co/" target="_blank"><strong>ingeweb.co</strong></a>. Please do not reply to this message.</p>';

$string['reportinfotext'] = 'This plugin has been created for another success story of <strong>IngeWeb</strong>. Visit us at <a target="_blank" href="http://ingeweb.co/">IngeWeb - Solutions to succeed on the Internet</a>.';

$string['disk_usage_ok'] = 'Disk usage is at an acceptable level. No immediate action required.';
$string['user_count_ok'] = 'User count is at an acceptable level. No immediate action required.';
$string['user_limit_tips'] = 'Tips for managing user limit:';
$string['tip_user_inactive'] = 'Consider cleaning up inactive user accounts that haven\'t logged in for a long time.';
$string['tip_user_limit'] = 'If the number of users is consistently approaching the limit, consider increasing your quota.';
