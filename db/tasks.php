<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Scheduled tasks definition for the Usage Monitor Report.
 *
 * This file defines all the scheduled tasks used by the plugin.
 * Each task has its own schedule and can be configured in the
 * Moodle task scheduling interface.
 *
 * @package    report_usage_monitor
 * @category   task
 * @copyright  2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// List of scheduled tasks for the plugin
$tasks = [
    // 1. Disk usage calculation task
    [
        'classname' => 'report_usage_monitor\task\disk_usage',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '12',  // Default value, may be updated by check_php_functions
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ],

    // 2. Recent users calculation task
    [
        'classname' => 'report_usage_monitor\task\last_users',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/2',  // Every 2 hours
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ],

    // 3. Users in last 90 days calculation task
    [
        'classname' => 'report_usage_monitor\task\users_daily_90_days',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',  // At midnight
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ],

    // 4. Daily users calculation task
    [
        'classname' => 'report_usage_monitor\task\users_daily',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',  // At midnight
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ],

    // 5. Unified usage notification task
    [
        'classname' => 'report_usage_monitor\task\notification_usage',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '8',  // At 8 AM
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ],

    // 6. Environment check scheduler task
    [
        'classname' => 'report_usage_monitor\task\check_env_scheduler',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/3',  // Every 3 hours
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ]
];

/*
Task Schedule Information:

1. disk_usage:
   - Default: Daily at 12:00
   - May run every 6 hours if shell_exec and du are available
   - Calculates disk space usage

2. last_users:
   - Every 2 hours
   - Calculates recent user activity

3. users_daily_90_days:
   - Daily at midnight
   - Calculates user statistics for the past 90 days

4. users_daily:
   - Daily at midnight
   - Calculates daily user statistics

5. notification_usage:
   - Daily at 8:00 AM
   - Sends unified notifications for disk and user thresholds

6. check_env_scheduler:
   - Every 3 hours
   - Schedules environment checks

Notes:
- All times are in server timezone
- blocking = 0 means tasks won't block other tasks
- disabled = 0 means tasks are enabled by default
- Tasks can be manually disabled in the task scheduling interface
*/