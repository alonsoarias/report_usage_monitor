<?php
// This file is part of Moodle - https://www.gnu.org/
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
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English language strings for the Usage Monitor Report plugin.
 *
 * @package     report_usage_monitor
 * @category    string
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// ------------------------------------------------------
// GENERAL PLUGIN STRINGS
// ------------------------------------------------------
$string['pluginname'] = 'Usage Monitor';
$string['exclusivedisclaimer'] = 'This plugin is for the exclusive use of the IngeWeb Moodle support team.';

// ------------------------------------------------------
// HEADINGS / SECTIONS
// ------------------------------------------------------
$string['userstopnum'] = 'Daily users';
$string['diskusage']   = 'Disk usage';

// ------------------------------------------------------
// USERS SECTION
// ------------------------------------------------------
$string['lastusers']     = 'Daily users of the last 10 days';
$string['topuser']       = 'Top 10 Daily Users';
$string['date']          = 'Date';
$string['usersquantity'] = 'Number of daily users';
$string['usertable']     = 'User table';
$string['userchart']     = 'User chart';

// ------------------------------------------------------
// DISK USAGE SECTION
// ------------------------------------------------------
$string['notcalculatedyet']       = 'Not calculated yet';
$string['lastexecutioncalculate'] = 'Last disk usage calculation: {$a}';
$string['sizeusage']              = 'Total disk usage';
$string['avalilabledisk']         = '% of available disk';
$string['sizedatabase']           = 'Database size';

// ------------------------------------------------------
// REPORT INFO / FOOTER
// ------------------------------------------------------
$string['reportinfotext'] = 'This plugin has been created for another success story of IngeWeb. Visit us at <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';

// ------------------------------------------------------
// CONFIGURATION / SETTINGS
// ------------------------------------------------------
$string['email']       = 'Email for notifications';
$string['configemail'] = 'Email address for receiving alerts.';

$string['max_daily_users_threshold']       = 'User limit';
$string['configmax_daily_users_threshold'] = 'Set the daily user limit.';
$string['disk_quota']       = 'Disk quota';
$string['configdisk_quota'] = 'Configure the disk quota (in gigabytes) for notifications.';


$string['activateshellexec']  = 'The shell_exec function is not active on this server. To use the auto-detection of the path to du, you need to enable shell_exec in your server configuration.';
$string['pathtodu']           = 'Path to du command';
$string['configpathtodu']     = 'Configure the path to the du command (disk usage). This is necessary for calculating disk usage.';
$string['pathtodurecommendation'] = 'We recommend that you review and configure the path to "du" in the Moodle System Paths.';
$string['pathtodunote']       = 'Note: The path to "du" will be automatically detected only if this plugin is on a Linux system and if the location of "du" can be successfully detected.';

// ------------------------------------------------------
// DATE FORMATS
// ------------------------------------------------------
$string['dateformatsql'] = '%m/%d/%Y';
$string['dateformat']    = 'm/d/Y';

// ------------------------------------------------------
// TASKS AND NOTIFICATIONS
// ------------------------------------------------------
$string['check_php_functions_taskname'] = 'Check PHP functions (adhoc task)';
$string['check_env_scheduler_taskname'] = 'Schedule check of PHP functions every 3 hours';
$string['notification_usage_taskname']  = 'Unified usage notification (disk + users)';

// AÑADIMOS las cadenas que piden tus clases de tareas
$string['calculatediskusagetask']     = 'Task to calculate the disk usage';
$string['getlastusersconnected']      = 'Task to calculate the last users connected';
$string['getlastusers']              = 'Task to calculate the top daily unique users';
$string['getlastusers90days']        = 'Task to get top users in last 90 days';

// ------------------------------------------------------
// UNIFIED USAGE NOTIFICATION STRINGS
// ------------------------------------------------------
$string['subjectemail_unified'] = 'Usage alert on {$a->sitename}';
$string['messagehtml_unified'] = '
<p>The platform <a href="{$a->siteurl}"><strong>{$a->sitename}</strong></a> has been checked:</p>
<ul>
    <li><strong>Daily user limit exceeded?</strong> {$a->exceededUsersLabel}</li>
    <li>Users (yesterday): <strong>{$a->users}</strong> / {$a->userthreshold} ({$a->userpercent}%)</li>
    <li><strong>Disk quota exceeded?</strong> {$a->exceededDiskLabel}</li>
    <li>Disk usage: <strong>{$a->diskusage}</strong> / {$a->diskquota} ({$a->diskpercent}%)</li>
    <li>Database size: <strong>{$a->databasesize}</strong></li>
    <li>Courses: <strong>{$a->coursescount}</strong></li>
    <li>Backups per course: <strong>{$a->backupcount}</strong></li>
</ul>
<hr>
{$a->table} <!-- Se imprime la tabla HTML de últimos 10 días o lo que quieras -->
<hr>
<p>This message was automatically generated by the "Usage Monitor" plugin of IngeWeb.</p>';
