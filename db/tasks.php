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
 * Definition of scheduled tasks for the daily user report.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

// List of scheduled tasks for the report_usage_monitor plugin.
// These tasks will run automatically at the specified time intervals.
$du_command_available = !empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu));
$tasks = array(
    // Disk usage calculation task
    array(
        'classname' => 'report_usage_monitor\task\disk_usage',
        'blocking' => 0,
        'minute' => '0',
        'hour' => $du_command_available ? '*/6' : '12', // Every 6 hours if du is active, otherwise every 12 hours
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Recent users calculation task
    array(
        'classname' => 'report_usage_monitor\task\last_users',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/2',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Unified notifications task (replaces separate disk and user limit notifications)
    array(
        'classname' => 'report_usage_monitor\task\unified_notifications',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/4',  // Run every 4 hours
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Calculate top users in last 90 days
    array(
        'classname' => 'report_usage_monitor\task\users_daily_90_days',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Calculate daily users
    array(
        'classname' => 'report_usage_monitor\task\users_daily',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    )
);