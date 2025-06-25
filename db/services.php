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
 * External services for Usage Monitor plugin.
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'report_usage_monitor_get_usage_statistics' => [
        'classname'     => 'report_usage_monitor_external',
        'methodname'    => 'get_usage_statistics',
        'classpath'     => 'report/usage_monitor/classes/external.php',
        'description'   => 'Get comprehensive usage statistics including disk, users, and projections.',
        'type'          => 'read',
        'capabilities'  => 'report/usage_monitor:view',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'report_usage_monitor_update_thresholds' => [
        'classname'     => 'report_usage_monitor_external',
        'methodname'    => 'update_thresholds',
        'classpath'     => 'report/usage_monitor/classes/external.php',
        'description'   => 'Update configuration thresholds for users and disk space.',
        'type'          => 'write',
        'capabilities'  => 'report/usage_monitor:manage',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'report_usage_monitor_get_notification_history' => [
        'classname'     => 'report_usage_monitor_external',
        'methodname'    => 'get_notification_history',
        'classpath'     => 'report/usage_monitor/classes/external.php',
        'description'   => 'Get notification history with filtering and pagination.',
        'type'          => 'read',
        'capabilities'  => 'report/usage_monitor:view',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'report_usage_monitor_get_dashboard_data' => [
        'classname'     => 'report_usage_monitor_external',
        'methodname'    => 'get_dashboard_data',
        'classpath'     => 'report/usage_monitor/classes/external.php',
        'description'   => 'Get optimized data for dashboard display.',
        'type'          => 'read',
        'capabilities'  => 'report/usage_monitor:view',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];

$services = [
    'Usage Monitor API' => [
        'functions' => [
            'report_usage_monitor_get_usage_statistics',
            'report_usage_monitor_update_thresholds',
            'report_usage_monitor_get_notification_history',
            'report_usage_monitor_get_dashboard_data',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'report_usage_monitor',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];