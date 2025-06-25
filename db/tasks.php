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
 * Scheduled tasks for Usage Monitor plugin.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2025 Alonso Arias <alonso@aloarias.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

$du_command_available = !empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu));

$tasks = [
    // Disk usage calculation task
    [
        'classname' => 'report_usage_monitor\task\disk_usage',
        'blocking' => 0,
        'minute' => '0',
        'hour' => $du_command_available ? '*/6' : '12', // Every 6 hours if du is available, otherwise every 12 hours
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
    
    // Daily users calculation task
    [
        'classname' => 'report_usage_monitor\task\users_daily',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/2', // Every 2 hours
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
    
    // 90-day peak users calculation task
    [
        'classname' => 'report_usage_monitor\task\users_daily_90_days',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '1', // Daily at 1 AM
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
    
    // Disk usage notification task
    [
        'classname' => 'report_usage_monitor\task\notification_disk',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/12', // Every 12 hours
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
    
    // User limit notification task
    [
        'classname' => 'report_usage_monitor\task\notification_userlimit',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '8', // Daily at 8 AM
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
    
    // History cleanup task
    [
        'classname' => 'report_usage_monitor\task\cleanup_history',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2', // Daily at 2 AM
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ]
];