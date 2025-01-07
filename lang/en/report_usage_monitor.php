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
 * English language strings.
 *
 * @package     report_usage_monitor
 * @category    string
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General plugin strings
$string['pluginname'] = 'Usage Monitor Report';
$string['exclusivedisclaimer'] = 'This plugin is part of, and is to be exclusively used with the Moodle hosting service provided by <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';
$string['reportinfotext'] = 'This plugin has been created by <strong>IngeWeb</strong>. Visit us at <a target="_blank" href="http://ingeweb.co/">IngeWeb - Solutions to succeed on the Internet</a>.';

// Configuration strings
$string['email'] = 'Notification email';
$string['configemail'] = 'Email address where system notifications will be sent';
$string['max_daily_users_threshold'] = 'Daily users limit';
$string['configmax_daily_users_threshold'] = 'Maximum number of daily users allowed before triggering alerts';
$string['disk_quota'] = 'Disk quota';
$string['configdisk_quota'] = 'Disk quota in gigabytes';
$string['pathtodu'] = 'Path to du command';
$string['configpathtodu'] = 'Configure the path to the du command for disk usage calculations';
$string['pathtodurecommendation'] = 'We recommend that you review and configure the path to \'du\' in the Moodle System Paths. You can find this setting under Site administration > Server > System Paths. <a target="_blank" href="settings.php?section=systempaths#id_s__pathtodu">Click here to go to System Paths</a>.';
$string['pathtodunote'] = 'Note: The path to \'du\' will be automatically detected only if this plugin is on a Linux system and if the location of \'du\' can be successfully detected.';

// Task descriptions
$string['processunifiednotificationtask'] = 'Process unified system monitoring notifications';
$string['calculatediskusagetask'] = 'Calculate disk usage';
$string['getlastusers'] = 'Calculate daily users statistics';
$string['getlastusers90days'] = 'Get peak daily users in last 90 days';
$string['getlastusersconnected'] = 'Calculate current daily users';

// Alert levels
$string['critical_threshold'] = 'CRITICAL';
$string['high_threshold'] = 'HIGH';
$string['medium_threshold'] = 'MEDIUM';
$string['normal_threshold'] = 'NORMAL';
$string['threshold_info'] = 'Alert thresholds: CRITICAL (95%), HIGH (90%), MEDIUM (80%)';

// Peak users information
$string['peak_users_title'] = 'Peak Daily Users (90 Days)';
$string['peak_date_label'] = 'Peak Date';
$string['peak_users_label'] = 'Maximum Daily Users';
$string['peak_percent_label'] = 'Peak Usage';

// Status messages
$string['notcalculatedyet'] = 'Not calculated yet';
$string['disk_usage_status'] = 'Disk usage status: {$a->level}';
$string['user_count_status'] = 'Daily users status: {$a->level}';
$string['activateshellexec'] = 'The shell_exec function is not active on this server. To use the auto-detection of the path to du, you need to enable shell_exec in your server configuration.';

// Report sections
$string['topuser'] = 'Top 10 Daily Users';
$string['lastusers'] = 'Last 10 Days Daily Users';
$string['max_userdaily_for_90_days'] = 'Peak Daily Users (90 Days)';
$string['userstopnum'] = 'Daily Users';
$string['user_count_title'] = 'Daily Users Count';
$string['additional_info'] = 'Additional Information';
$string['system_status'] = 'System Status';
$string['historical_data'] = 'Historical Data';

// Interface elements
$string['usertable'] = 'Users table';
$string['userchart'] = 'Users chart';
$string['date'] = 'Date';
$string['usersquantity'] = 'Number of daily users';
$string['dateformatsql'] = '%m/%d/%Y';
$string['dateformat'] = 'm/d/Y';

// Disk usage metrics
$string['diskusage'] = 'Disk Usage';
$string['sizeusage'] = 'Total disk usage';
$string['sizedatabase'] = 'Database size';
$string['avalilabledisk'] = 'Available disk space';
$string['disk_metrics_details'] = 'Disk Metrics Details';
$string['disk_usage_title'] = 'Current Disk Usage';
$string['database_size_title'] = 'Database Size';

// Execution information
$string['lastexecution'] = 'Last daily users calculation run: {$a}';
$string['lastexecutioncalculate'] = 'Last disk space calculation: {$a}';
$string['last_execution_title'] = 'Last Report Execution';
$string['today_users_title'] = 'Current Daily Users';
$string['users_today'] = 'Number of daily users today: {$a}';

// Additional metrics
$string['total_courses'] = 'Total Courses';
$string['backup_retention'] = 'Backup Retention';
$string['per_course'] = 'per course';

// Notification strings
$string['unifiednotification_subject'] = 'System Monitoring Alert - {$a}';
$string['unifiednotification_html'] = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }
        .metric-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            min-height: 150px;
        }
        .metric-box {
            flex: 1;
            min-width: 200px;
            max-width: calc(33.33% - 14px);
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .metric-title {
            font-size: 14px;
            color: #495057;
            margin-bottom: 10px;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #212529;
            margin: 10px 0;
        }
        .metric-subtitle {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .metric-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 100%;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .alert-critical { background: #ffebee; border-left: 4px solid #c62828; }
        .alert-high { background: #fff3e0; border-left: 4px solid #ef6c00; }
        .alert-medium { background: #fff8e1; border-left: 4px solid #f9a825; }
        .alert-normal { background: #e8f5e9; border-left: 4px solid #2e7d32; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #1a73e8; margin-bottom: 20px;">System Status Report - {$a->sitename}</h1>
        
        <!-- Disk Usage Card -->
        <div class="metric-card alert-{$a->disk_alert_class}">
            <h2>Disk Usage Status</h2>
            <div class="metric-container">
                <div class="metric-box">
                    <div class="metric-title">Total Usage</div>
                    <div class="metric-value">{$a->diskusage}</div>
                    <div class="metric-subtitle">of {$a->quotadisk}</div>
                </div>
                <div class="metric-box">
                    <div class="metric-title">Usage Level</div>
                    <div class="metric-value">{$a->disk_percent}%</div>
                    <div class="metric-subtitle">{$a->disk_alert}</div>
                </div>
                <div class="metric-box">
                    <div class="metric-title">Database Size</div>
                    <div class="metric-value">{$a->databasesize}</div>
                </div>
            </div>
        </div>

        <!-- Daily Users Card -->
        <div class="metric-card alert-{$a->user_alert_class}">
            <h2>Daily Users Status</h2>
            <div class="metric-container">
                <div class="metric-box">
                    <div class="metric-title">Current Daily Users</div>
                    <div class="metric-value">{$a->numberofusers}</div>
                    <div class="metric-subtitle">of {$a->threshold} limit</div>
                </div>
                <div class="metric-box">
                    <div class="metric-title">Usage Level</div>
                    <div class="metric-value">{$a->user_percent}%</div>
                    <div class="metric-subtitle">{$a->user_alert}</div>
                </div>
            </div>
        </div>

        <!-- Peak Daily Users Card -->
        <div class="metric-card">
            <h2>Peak Daily Users (90 Days)</h2>
            <div class="metric-container">
                <div class="metric-box">
                    <div class="metric-title">Peak Date</div>
                    <div class="metric-value">{$a->max_90_days_date}</div>
                </div>
                <div class="metric-box">
                    <div class="metric-title">Maximum Daily Users</div>
                    <div class="metric-value">{$a->max_90_days_users}</div>
                    <div class="metric-subtitle">of {$a->threshold} limit</div>
                </div>
                <div class="metric-box">
                    <div class="metric-title">Peak Usage</div>
                    <div class="metric-value">{$a->max_90_days_percent}%</div>
                    <div class="metric-subtitle">of total capacity</div>
                </div>
            </div>
        </div>

        <!-- Additional Metrics -->
        {$a->table}

        <div class="footer">
            <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
            <i>
                <p style="font-size: 0.9em; color: #666;">This message has been automatically generated by "Usage Report" from <a href="https://ingeweb.co/" target="_blank"><strong>ingeweb.co</strong></a></p>
                <p style="font-size: 0.9em; color: #666;">*Only distinct users who logged in on the indicated date are counted. Multiple logins from the same user on the same day count as one daily user.</p>
            </i>
            <p style="font-size: 0.9em; color: #666;">This is an automated monitoring notification. System metrics are collected and analyzed periodically to ensure optimal platform performance.</p>
        </div>
    </div>
</body>
</html>';