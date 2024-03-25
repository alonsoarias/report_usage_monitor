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
$string['subjectemail1'] = 'Daily user limit exceeded';
$string['subjectemail2'] = 'Disk usage warning';
$string['userstopnum'] = 'Daily users';
$string['usertable'] = 'Top users table';
$string['userchart'] = 'Graph top users';
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
$string['messagehtml1'] = '<p>The platform <a href="{$a->siteurl}" target="_blank" ><strong>\'{$a->sitename}\'</strong></a> has exceeded 90% allocated disk space</p>
<p>Date (MM/DD/AAAA): {$a->lastday} </p>
<p>Users*: <strong>{$a->numberofusers}</strong></p>
<p>Set threshold of maximum daily users: {$a->threshold} users</p>
<strong>Url monitor: </strong> {$a->referer}
<br>
<br>
{$a->table}
<br>
<hr>
<i><p>This message was automatically sent by  "Usage Report" - <a href="https://ingeweb.co/" target="_blank" ><strong>ingeweb.co</strong></a></p>
*Indicates the number of different users connected on the corresponding date. Users who connected more than once are counted only once.<i>';
$string['messagehtml2'] = '<p>The platform <a href="{$a->siteurl}" target="_blank" ><strong>\'{$a->sitename}\'</strong></a> has exceeded the user threshold by {$a->percentaje}%</p>
<p>Allocated disk space: {$a->quotadisk} </p>
<p>Used disk space: <strong>{$a->diskusage}</strong></p>
<strong>Url monitor: </strong> {$a->referer}
<br>
<hr>
<i><p>This message was automatically sent by  "Usage Report" - <a href="https://ingeweb.co/" target="_blank" ><strong>ingeweb.co</strong></a></p>';
$string['reportinfotext'] = 'This plugin was created for <strong> another Moodle project </strong> by <a target="_blank" href="http://ingeweb.co/">IngeWeb - Soluciones para triunfar en Internet</a>.';