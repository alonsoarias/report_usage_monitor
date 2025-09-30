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
 * Scheduled tasks definition for report_usage_monitor.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// List of scheduled tasks for the report_usage_monitor plugin.
$tasks = array(
    // Task to calculate disk usage.
    array(
        'classname' => 'report_usage_monitor\task\disk_usage',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/6',  // Every 6 hours.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Task to calculate recent connected users.
    array(
        'classname' => 'report_usage_monitor\task\last_users',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/2',  // Every 2 hours.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Task to process disk space notifications.
    array(
        'classname' => 'report_usage_monitor\task\notification_disk',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/12',  // Every 12 hours.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Task to process daily user limit notifications.
    array(
        'classname' => 'report_usage_monitor\task\notification_userlimit',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '8',  // Once a day at 8 AM.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Task to calculate top users in the last 90 days.
    array(
        'classname' => 'report_usage_monitor\task\users_daily_90_days',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',  // Once a day at midnight.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Task to calculate daily users.
    array(
        'classname' => 'report_usage_monitor\task\users_daily',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',  // Once a day at midnight.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    )
);